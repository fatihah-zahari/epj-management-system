<?php
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/helpers/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf'] ?? '';

    if (!csrf_check($csrf)) {
        $error = "Invalid request (CSRF). Refresh page and try again.";
    } else {
        $stmt = $pdo->prepare("SELECT id, fullname, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active') {
            $error = "Akaun tidak wujud / tidak aktif.";
        } else if (!password_verify($password, $user['password_hash'])) {
            $error = "Email atau kata laluan salah.";
        } else {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];

            // redirect by role
            if ($user['role'] === 'admin') header("Location: /epj/modules/admin/dashboard.php");
            else if ($user['role'] === 'imam') header("Location: /epj/modules/imam/dashboard.php");
            else header("Location: /epj/modules/user/dashboard.php");
            exit;
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login - EPJ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3">Login</h4>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <div class="mb-2">
                                <label class="form-label">Email</label>
                                <input name="email" type="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kata Laluan</label>
                                <input name="password" type="password" class="form-control" required>
                            </div>
                            <button class="btn btn-primary w-100">Masuk</button>
                        </form>
                        <div class="mt-3">
                            <a href="/epj/public/register.php">Daftar Akaun Waris</a>
                        </div>
                        <hr>
                        <small class="text-muted">
                            Demo accounts akan kita set password guna script setup bawah.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>