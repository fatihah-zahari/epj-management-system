<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['user', 'admin', 'imam']); // admin/imam pun boleh view

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$allowed = ['kafan', 'burial', 'khairat'];
if (!in_array($type, $allowed, true) || $id <= 0) {
    http_response_code(400);
    echo "Invalid request";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE service_type=? AND service_id=? LIMIT 1");
$stmt->execute([$type, $id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inv) {
    echo "Invoice not found";
    exit;
}

// If user role, ensure own invoice
$u = current_user();
if ($u['role'] === 'user' && (int)$inv['user_id'] !== (int)$u['id']) {
    http_response_code(403);
    echo "403 Forbidden";
    exit;
}

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Invois Permohonan</h4>
            <a class="btn btn-outline-secondary"
                href="<?= $u['role'] === 'user' ? '/epj/modules/user/dashboard.php' : ($u['role'] === 'imam' ? '/epj/modules/imam/dashboard.php' : '/epj/modules/admin/dashboard.php') ?>">
                Back
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <p class="mb-1"><strong>No. Invois:</strong> <?= htmlspecialchars($inv['invoice_no']) ?></p>
                <p class="mb-1"><strong>Servis:</strong> <?= htmlspecialchars(strtoupper($inv['service_type'])) ?></p>
                <p class="mb-1"><strong>Jumlah:</strong> RM <?= number_format((float)$inv['amount'], 2) ?></p>
                <p class="mb-1"><strong>Status Bayaran:</strong> <?= htmlspecialchars($inv['payment_status']) ?></p>
                <p class="mb-0 text-muted"><small>Tarikh: <?= htmlspecialchars($inv['created_at']) ?></small></p>

                <hr>
                <div class="alert alert-info mb-0">
                    Untuk demo sekarang, bayaran belum diintegrasi. Nanti kita boleh tambah “Mark Paid” (admin) + resit.
                </div>
                <a class="btn btn-outline-primary mt-2"
                    href="/epj/modules/user/invoice_print.php?type=<?= htmlspecialchars($inv['service_type']) ?>&id=<?= (int)$inv['service_id'] ?>">
                    Print / Save as PDF
                </a>
            </div>
        </div>
    </div>
</body>

</html>