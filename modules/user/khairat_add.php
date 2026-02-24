<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require __DIR__ . '/../../app/helpers/csrf.php';
require_role(['user']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Invalid CSRF.";
    } else {
        $applicant_name = trim($_POST['applicant_name'] ?? '');
        $applicant_ic   = trim($_POST['applicant_ic'] ?? '');
        $deceased_name  = trim($_POST['deceased_name'] ?? '');
        $death_date     = $_POST['death_date'] ?? null;
        $bank_name      = trim($_POST['bank_name'] ?? '');
        $bank_account   = trim($_POST['bank_account'] ?? '');
        $claim_amount   = (float)($_POST['claim_amount'] ?? 0);

        if ($applicant_name === '' || $deceased_name === '' || $bank_account === '') {
            $error = "Sila isi semua maklumat wajib.";
        } else {

            $stmt = $pdo->prepare("
        INSERT INTO khairat_claims
        (user_id, applicant_name, applicant_ic, deceased_name, death_date,
         bank_name, bank_account, claim_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");

            $stmt->execute([
                current_user()['id'],
                $applicant_name,
                $applicant_ic ?: null,
                $deceased_name,
                $death_date ?: null,
                $bank_name ?: null,
                $bank_account,
                $claim_amount
            ]);

            header("Location: /epj/modules/user/dashboard.php");
            exit;
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tuntutan Khairat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between mb-3">
            <h4>Tuntutan Khairat Kematian</h4>
            <a class="btn btn-outline-secondary" href="/epj/modules/user/dashboard.php">Back</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="mb-2">
                        <label>Nama Pemohon *</label>
                        <input class="form-control" name="applicant_name" required>
                    </div>

                    <div class="mb-2">
                        <label>IC Pemohon</label>
                        <input class="form-control" name="applicant_ic">
                    </div>

                    <div class="mb-2">
                        <label>Nama Jenazah *</label>
                        <input class="form-control" name="deceased_name" required>
                    </div>

                    <div class="mb-2">
                        <label>Tarikh Meninggal</label>
                        <input type="date" class="form-control" name="death_date">
                    </div>

                    <div class="mb-2">
                        <label>Nama Bank</label>
                        <input class="form-control" name="bank_name">
                    </div>

                    <div class="mb-2">
                        <label>No Akaun Bank *</label>
                        <input class="form-control" name="bank_account" required>
                    </div>

                    <div class="mb-3">
                        <label>Jumlah Tuntutan (RM)</label>
                        <input type="number" step="0.01" class="form-control" name="claim_amount" value="500.00">
                    </div>

                    <button class="btn btn-primary">Hantar Tuntutan</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>