<?php
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/helpers/csrf.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Invalid CSRF.";
    } else {
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $ic       = trim($_POST['ic'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullname === '' || $email === '' || $password === '') {
            $error = "Sila isi nama, email, dan password.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email tidak sah.";
        } elseif (strlen($password) < 8) {
            $error = "Password minimum 8 aksara.";
        } else {

            // check email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email sudah digunakan.";
            }
        }

        // ========= Upload IC (optional but recommended) =========
        $docPath = null;
        $docType = 'ic';

        if (!$error && !empty($_FILES['ic_document']['name'])) {
            $f = $_FILES['ic_document'];

            if ($f['error'] !== UPLOAD_ERR_OK) {
                $error = "Upload IC gagal. Error code: " . $f['error'];
            } else {
                $maxSize = 5 * 1024 * 1024;
                if ($f['size'] > $maxSize) {
                    $error = "Fail IC terlalu besar. Max 5MB.";
                } else {
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                    if (!in_array($ext, $allowed, true)) {
                        $error = "Jenis fail IC tidak dibenarkan (PDF/JPG/PNG sahaja).";
                    } else {
                        $newName = "ic_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                        $destDir = __DIR__ . "/../uploads";
                        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
                        $destPath = $destDir . "/" . $newName;

                        if (!move_uploaded_file($f['tmp_name'], $destPath)) {
                            $error = "Gagal simpan fail IC.";
                        } else {
                            $docPath = "uploads/" . $newName;
                        }
                    }
                }
            }
        }

        // ========= Insert user =========
        if (!$error) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
        INSERT INTO users (fullname, ic, phone, email, password_hash, role, status, ic_doc_path, ic_doc_type)
        VALUES (?, ?, ?, ?, ?, 'user', 'pending', ?, ?)
      ");
            $stmt->execute([
                $fullname,
                $ic ?: null,
                $phone ?: null,
                $email,
                $hash,
                $docPath,
                $docPath ? $docType : null
            ]);

            $success = "Pendaftaran berjaya! Akaun anda sedang menunggu pengesahan admin (pending).";
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Register - Waris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3">Pendaftaran Waris</h4>

                        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

                            <div class="mb-2">
                                <label class="form-label">Nama Penuh *</label>
                                <input class="form-control" name="fullname" required>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Email *</label>
                                <input class="form-control" name="email" type="email" required>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Telefon</label>
                                <input class="form-control" name="phone">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">No IC</label>
                                <input class="form-control" name="ic" placeholder="000000-00-0000">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Password *</label>
                                <input class="form-control" name="password" type="password" minlength="8" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload IC (PDF/JPG/PNG, max 5MB)</label>
                                <input type="file" class="form-control" name="ic_document" accept=".pdf,.jpg,.jpeg,.png">
                            </div>

                            <button class="btn btn-primary w-100">Daftar</button>
                        </form>

                        <hr>
                        <a href="/epj/public/login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>