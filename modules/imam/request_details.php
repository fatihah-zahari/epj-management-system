<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['imam', 'admin']);

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$allowed = ['kafan', 'burial', 'khairat'];
if (!in_array($type, $allowed, true) || $id <= 0) {
    http_response_code(400);
    exit("Invalid request");
}

if ($type === 'kafan') {
    $stmt = $pdo->prepare("
    SELECT k.*, u.fullname AS waris_name, u.email AS waris_email, u.phone AS waris_phone
    FROM kafan_requests k
    JOIN users u ON u.id = k.user_id
    WHERE k.id=? LIMIT 1
  ");
} elseif ($type === 'burial') {
    $stmt = $pdo->prepare("
    SELECT b.*, u.fullname AS waris_name, u.email AS waris_email, u.phone AS waris_phone
    FROM burial_requests b
    JOIN users u ON u.id = b.user_id
    WHERE b.id=? LIMIT 1
  ");
} else {
    $stmt = $pdo->prepare("
    SELECT h.*, u.fullname AS waris_name, u.email AS waris_email, u.phone AS waris_phone
    FROM khairat_claims h
    JOIN users u ON u.id = h.user_id
    WHERE h.id=? LIMIT 1
  ");
}

$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) exit("Not found");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Details: <?= strtoupper(htmlspecialchars($type)) ?> #<?= (int)$id ?></h4>
            <?php
            $u = current_user();

            if ($u['role'] === 'admin') {
                // admin back to admin manage pages
                if ($type === 'kafan') $backUrl = "/epj/modules/admin/kafan_manage.php";
                elseif ($type === 'burial') $backUrl = "/epj/modules/admin/burial_manage.php";
                else $backUrl = "/epj/modules/admin/khairat_manage.php";
            } else {
                // imam back to imam dashboard tab
                $backUrl = "/epj/modules/imam/dashboard.php?tab=" . urlencode($type);
            }
            ?>

            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($backUrl) ?>">Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Maklumat Waris</h6>
                <div><b>Nama:</b> <?= htmlspecialchars($row['waris_name']) ?></div>
                <div><b>Email:</b> <?= htmlspecialchars($row['waris_email']) ?></div>
                <div><b>Telefon:</b> <?= htmlspecialchars($row['waris_phone'] ?? '-') ?></div>

                <hr>

                <h6 class="text-muted">Maklumat Permohonan</h6>
                <div><b>Status:</b> <?= htmlspecialchars($row['status']) ?></div>
                <div><b>Nama Jenazah:</b> <?= htmlspecialchars($row['deceased_name'] ?? '-') ?></div>

                <?php if ($type === 'khairat'): ?>
                    <div><b>Jumlah:</b> RM <?= number_format((float)$row['claim_amount'], 2) ?></div>
                    <div><b>Bank:</b> <?= htmlspecialchars($row['bank_name'] ?? '-') ?></div>
                    <div><b>No Akaun:</b> <?= htmlspecialchars($row['bank_account'] ?? '-') ?></div>
                    <div><b>Receipt No:</b> <?= htmlspecialchars($row['receipt_no'] ?? '-') ?></div>
                <?php endif; ?>

                <?php if (!empty($row['address'])): ?>
                    <div><b>Alamat:</b> <?= nl2br(htmlspecialchars($row['address'])) ?></div>
                <?php endif; ?>

                <?php if (!empty($row['notes'])): ?>
                    <div><b>Catatan:</b> <?= nl2br(htmlspecialchars($row['notes'])) ?></div>
                <?php endif; ?>

                <?php if (!empty($row['document_path'])): ?>
                    <hr>
                    <a class="btn btn-outline-dark"
                        href="/epj/public/file.php?type=<?= htmlspecialchars($type) ?>&id=<?= (int)$id ?>">
                        View Document
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</body>

</html>