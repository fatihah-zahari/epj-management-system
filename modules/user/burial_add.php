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
        $burial_date   = $_POST['burial_date'] ?? null;
        $cemetery_area = trim($_POST['cemetery_area'] ?? '');
        $plot_no       = trim($_POST['plot_no'] ?? '');
        $address       = trim($_POST['address'] ?? '');
        $notes         = trim($_POST['notes'] ?? '');

        if ($deceased_name === '') {
            $error = "Sila isi nama jenazah.";
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
          INSERT INTO burial_requests
          (user_id, deceased_name, deceased_ic, burial_date, cemetery_area, plot_no, address, notes)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
                $stmt->execute([
                    current_user()['id'],
                    $deceased_name,
                    $deceased_ic ?: null,
                    $burial_date ?: null,
                    $cemetery_area ?: null,
                    $plot_no ?: null,
                    $address ?: null,
                    $notes ?: null
                ]);
                $service_id = (int)$pdo->lastInsertId();

                $invoice_no = make_invoice_no();
                $amount = 120.00; // contoh fee, boleh ubah

                $stmt = $pdo->prepare("
          INSERT INTO invoices (user_id, service_type, service_id, invoice_no, amount, payment_status)
          VALUES (?, 'burial', ?, ?, ?, 'unpaid')
        ");
                $stmt->execute([current_user()['id'], $service_id, $invoice_no, $amount]);

                $pdo->commit();

                header("Location: /epj/modules/user/invoice_view.php?type=burial&id=" . $service_id);
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
    <title>Permohonan Perkuburan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Permohonan Servis Perkuburan</h4>
            <a class="btn btn-outline-secondary" href="/epj/modules/user/dashboard.php">Back</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="mb-2">
                        <label class="form-label">Nama Jenazah *</label>
                        <input class="form-control" name="deceased_name" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">IC Jenazah</label>
                        <input class="form-control" name="deceased_ic" placeholder="000000-00-0000">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Tarikh Pengebumian</label>
                        <input type="date" class="form-control" name="burial_date">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Kawasan Perkuburan</label>
                            <input class="form-control" name="cemetery_area" placeholder="cth: Mukim Bakri">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No Plot / Lot</label>
                            <input class="form-control" name="plot_no" placeholder="cth: A-12">
                        </div>
                    </div>

                    <div class="mt-2 mb-2">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>

                    <button class="btn btn-primary">Submit & Jana Invois</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>