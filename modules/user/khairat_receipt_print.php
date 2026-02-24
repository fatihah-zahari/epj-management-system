<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['user', 'admin', 'imam']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit("Invalid request");

$stmt = $pdo->prepare("
  SELECT k.*, u.fullname, u.email
  FROM khairat_claims k
  JOIN users u ON u.id = k.user_id
  WHERE k.id=? LIMIT 1
");
$stmt->execute([$id]);
$k = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$k) exit("Claim not found");

$u = current_user();
if ($u['role'] === 'user' && (int)$k['user_id'] !== (int)$u['id']) {
    http_response_code(403);
    exit("403 Forbidden");
}
if ($k['status'] !== 'paid') {
    exit("Receipt only available when status = paid.");
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Receipt Khairat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/epj/public/assets/css/print.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center no-print mb-3">
            <a class="btn btn-outline-secondary" href="/epj/modules/user/dashboard.php">Back</a>
            <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        </div>

        <div class="card print-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-1">E-Pengurusan Jenazah</h4>
                        <div class="text-muted">Resit Bayaran Khairat</div>
                    </div>
                    <div class="text-end">
                        <div><strong>Receipt No:</strong> <?= htmlspecialchars($k['receipt_no'] ?? '-') ?></div>
                        <div><strong>Tarikh Bayar:</strong> <?= htmlspecialchars($k['decided_at'] ?? '-') ?></div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="fw-bold mb-1">Maklumat Pemohon</div>
                        <div><strong>Nama:</strong> <?= htmlspecialchars($k['applicant_name']) ?></div>
                        <div><strong>IC:</strong> <?= htmlspecialchars($k['applicant_ic'] ?? '-') ?></div>
                        <div><strong>Email Akaun:</strong> <?= htmlspecialchars($k['email']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-bold mb-1">Maklumat Jenazah & Bayaran</div>
                        <div><strong>Nama Jenazah:</strong> <?= htmlspecialchars($k['deceased_name'] ?? '-') ?></div>
                        <div><strong>Jumlah Dibayar:</strong> RM <?= number_format((float)$k['claim_amount'], 2) ?></div>
                        <div><strong>Bank:</strong> <?= htmlspecialchars($k['bank_name'] ?? '-') ?></div>
                        <div><strong>No Akaun:</strong> <?= htmlspecialchars($k['bank_account']) ?></div>
                    </div>
                </div>

                <hr>
                <div class="text-muted small">
                    * Resit ini dijana oleh sistem selepas status “paid”.
                </div>
            </div>
        </div>

    </div>
</body>

</html>