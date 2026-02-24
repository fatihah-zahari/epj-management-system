<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require __DIR__ . '/../../app/helpers/csrf.php';
require_role(['user']);

$error = '';

function make_invoice_no()
{
    $date = date('Ymd');
    $rand = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "EPJ-$date-$rand";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Invalid request (CSRF).";
    } else {

        $deceased_name = trim($_POST['deceased_name'] ?? '');
        $deceased_ic   = trim($_POST['deceased_ic'] ?? '');
        $death_date    = $_POST['death_date'] ?? null;
        $address       = trim($_POST['address'] ?? '');
        $notes         = trim($_POST['notes'] ?? '');

        if ($deceased_name === '') {
            $error = "Sila isi nama jenazah.";
        }

        // =========================
        // FILE UPLOAD SECTION
        // =========================

        $docPath = null;
        $docType = 'sijil_kematian';

        if (!$error && !empty($_FILES['document']['name'])) {

            $f = $_FILES['document'];

            if ($f['error'] !== UPLOAD_ERR_OK) {
                $error = "Upload gagal. Error code: " . $f['error'];
            } else {

                $maxSize = 5 * 1024 * 1024; // 5MB

                if ($f['size'] > $maxSize) {
                    $error = "Fail terlalu besar. Max 5MB.";
                } else {

                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

                    if (!in_array($ext, $allowed, true)) {
                        $error = "Jenis fail tidak dibenarkan. (PDF/JPG/PNG sahaja)";
                    } else {

                        $newName = "kafan_" . current_user()['id'] . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

                        $destDir = __DIR__ . "/../../uploads";

                        if (!is_dir($destDir)) {
                            mkdir($destDir, 0777, true);
                        }

                        $destPath = $destDir . "/" . $newName;

                        if (!move_uploaded_file($f['tmp_name'], $destPath)) {
                            $error = "Gagal simpan fail upload.";
                        } else {
                            $docPath = "uploads/" . $newName;
                        }
                    }
                }
            }
        }

        // =========================
        // INSERT DATA + INVOICE
        // =========================

        if (!$error) {

            $pdo->beginTransaction();

            try {

                $stmt = $pdo->prepare("
          INSERT INTO kafan_requests
          (user_id, deceased_name, deceased_ic, death_date, address, notes, document_path, document_type)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

                $stmt->execute([
                    current_user()['id'],
                    $deceased_name,
                    $deceased_ic ?: null,
                    $death_date ?: null,
                    $address ?: null,
                    $notes ?: null,
                    $docPath,
                    $docPath ? $docType : null
                ]);

                $service_id = (int)$pdo->lastInsertId();

                // Create invoice
                $invoice_no = make_invoice_no();
                $amount = 50.00;

                $stmt = $pdo->prepare("
          INSERT INTO invoices (user_id, service_type, service_id, invoice_no, amount, payment_status)
          VALUES (?, 'kafan', ?, ?, ?, 'unpaid')
        ");

                $stmt->execute([
                    current_user()['id'],
                    $service_id,
                    $invoice_no,
                    $amount
                ]);

                $pdo->commit();

                header("Location: /epj/modules/user/invoice_view.php?type=kafan&id=" . $service_id);
                exit;
            } catch (Exception $e) {

                $pdo->rollBack();
                $error = "Gagal simpan permohonan: " . $e->getMessage();
            }
        }
    }
}
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Permohonan Kafan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Permohonan Servis Kafan</h4>
            <a class="btn btn-outline-secondary" href="/epj/modules/user/dashboard.php">Back</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">

                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="mb-2">
                        <label class="form-label">Nama Jenazah *</label>
                        <input class="form-control" name="deceased_name" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">IC Jenazah</label>
                        <input class="form-control" name="deceased_ic">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Tarikh Meninggal</label>
                        <input type="date" class="form-control" name="death_date">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="address"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="notes"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Dokumen (PDF/JPG/PNG, max 5MB)</label>
                        <input type="file" class="form-control" name="document" accept=".pdf,.jpg,.jpeg,.png">
                    </div>

                    <button class="btn btn-primary">Submit & Jana Invois</button>

                </form>
            </div>
        </div>

    </div>
</body>

</html>