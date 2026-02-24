<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require __DIR__ . '/../../app/helpers/csrf.php';
require_role(['admin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $ic       = trim($_POST['ic'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = $_POST['role'] ?? 'user';
        $status   = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        $allowedRoles = ['admin', 'imam', 'user'];
        $allowedStatus = ['active', 'inactive'];

        if ($fullname === '' || $email === '' || $password === '') {
            $error = "Sila isi Full Name, Email, dan Password.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak sah.";
        } elseif (!in_array($role, $allowedRoles, true)) {
            $error = "Role tidak sah.";
        } elseif (!in_array($status, $allowedStatus, true)) {
            $error = "Status tidak sah.";
        } elseif (strlen($password) < 8) {
            $error = "Password minimum 8 aksara.";
        } else {
            try {
                // check email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email ini sudah digunakan.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
            INSERT INTO users (fullname, ic, phone, email, password_hash, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
          ");
                    $stmt->execute([
                        $fullname,
                        $ic ?: null,
                        $phone ?: null,
                        $email,
                        $hash,
                        $role,
                        $status
                    ]);

                    // audit
                    $pdo->prepare("INSERT INTO audit_logs (user_id, action, meta) VALUES (?, ?, ?)")
                        ->execute([current_user()['id'], "ADMIN_CREATE_USER", json_encode(['email' => $email, 'role' => $role])]);

                    $success = "User berjaya dibuat. Email: $email (role: $role)";
                }
            } catch (Exception $e) {
                $error = "Gagal create user: " . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Create User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Create User</h4>
            <a class="btn btn-outline-secondary" href="/epj/modules/admin/users_list.php">Back</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input class="form-control" name="fullname" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input class="form-control" name="email" type="email" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">IC</label>
                            <input class="form-control" name="ic" placeholder="000000-00-0000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" placeholder="01x-xxxxxxx">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="user" selected>user (Waris)</option>
                                <option value="imam">imam</option>
                                <option value="admin">admin</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>active</option>
                                <option value="inactive">inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Password *</label>
                            <input class="form-control" name="password" type="password" minlength="8" required>
                            <small class="text-muted">Minimum 8 aksara.</small>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>