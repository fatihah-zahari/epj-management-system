<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit("Invalid user ID");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("User not found");
}
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>User Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>User Details #<?= (int)$user['id'] ?></h4>
            <a class="btn btn-outline-secondary" href="users_approve.php">Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">

                <h6 class="text-muted">Maklumat Akaun</h6>

                <div><b>Nama:</b> <?= htmlspecialchars($user['fullname']) ?></div>
                <div><b>Email:</b> <?= htmlspecialchars($user['email']) ?></div>
                <div><b>Telefon:</b> <?= htmlspecialchars($user['phone'] ?? '-') ?></div>
                <div><b>Role:</b> <?= htmlspecialchars($user['role']) ?></div>
                <div><b>Status:</b> <?= htmlspecialchars($user['status']) ?></div>
                <div><b>Tarikh Daftar:</b> <?= htmlspecialchars($user['created_at']) ?></div>
                <?php if (!empty($user['ic_doc_path'])): ?>
                    <hr>
                    <a class="btn btn-outline-dark"
                        href="/epj/public/user_file.php?id=<?= (int)$user['id'] ?>">
                        View IC Document
                    </a>
                <?php else: ?>
                    <hr>
                    <div class="text-muted">Tiada IC document.</div>
                <?php endif; ?>
                <hr>

                <div class="alert alert-info">
                    Admin boleh semak maklumat ini sebelum approve atau reject.
                </div>

            </div>
        </div>

    </div>
</body>

</html>