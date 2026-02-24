<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['user', 'admin', 'imam']);

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$allowed = ['kafan', 'burial'];
if (!in_array($type, $allowed, true) || $id <= 0) {
    http_response_code(400);
    exit("Invalid request");
}

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE service_type=? AND service_id=? LIMIT 1");
$stmt->execute([$type, $id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$inv) exit("Invoice not found");

$u = current_user();
if ($u['role'] === 'user' && (int)$inv['user_id'] !== (int)$u['id']) {
    http_response_code(403);
    exit("403 Forbidden");
}

// get service details
if ($type === 'kafan') {
    $stmt = $pdo->prepare("SELECT deceased_name, deceased_ic, death_date, address, status, created_at FROM kafan_requests WHERE id=? LIMIT 1");
} else {
    $stmt = $pdo->prepare("SELECT deceased_name, deceased_ic, burial_date AS death_date, address, status, created_at FROM burial_requests WHERE id=? LIMIT 1");
}
$stmt->execute([$id]);
$svc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$svc) exit("Service not found");

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Print Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/epj/public/assets/css/print.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center no-print mb-3">
            <div>
                <a class="btn btn-outline-secondary" href="/epj/modules/user/invoice_view.php?type=<?= htmlspecialchars($type) ?>&id=<?= $id ?>">Back</a>
            </div>
            <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        </div>

        <div class="card print-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-1">E-Pengurusan Jenazah</h4>
                        <div class="text-muted">Invoice</div>
                    </div>
                    <div class="text-end">
                        <div><strong>No:</strong> <?= htmlspecialchars($inv['invoice_no']) ?></div>
                        <div><strong>Tarikh:</strong> <?= htmlspecialchars($inv['created_at']) ?></div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="fw-bold mb-1">Maklumat Permohonan</div>
                        <div><strong>Servis:</strong> <?= strtoupper(htmlspecialchars($type)) ?></div>
                        <div><strong>Nama Jenazah:</strong> <?= htmlspecialchars($svc['deceased_name']) ?></div>
                        <div><strong>IC Jenazah:</strong> <?= htmlspecialchars($svc['deceased_ic'] ?? '-') ?></div>
                        <div><strong>Status:</strong> <?= htmlspecialchars($svc['status']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-bold mb-1">Maklumat Waris</div>
                        <div><strong>Nama:</strong> <?= htmlspecialchars($u['fullname']) ?></div>
                        <div><strong>Email:</strong> <?= htmlspecialchars($u['email']) ?></div>
                    </div>
                </div>

                <hr>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Jumlah (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Caj servis <?= strtoupper(htmlspecialchars($type)) ?></td>
                            <td class="text-end"><?= number_format((float)$inv['amount'], 2) ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-end">TOTAL</th>
                            <th class="text-end"><?= number_format((float)$inv['amount'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-muted small">
                    * Dokumen ini dijana oleh sistem. (Demo) Bayaran: <?= htmlspecialchars($inv['payment_status']) ?>.
                </div>
            </div>
        </div>

    </div>
</body>

</html>