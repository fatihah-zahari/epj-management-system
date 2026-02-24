<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['user']);

$uid = current_user()['id'];

// Latest 5 Kafan
$stmt = $pdo->prepare("SELECT id, deceased_name, status, created_at FROM kafan_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$uid]);
$kafan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Latest 5 Perkuburan
$stmt = $pdo->prepare("SELECT id, deceased_name, status, created_at FROM burial_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$uid]);
$burial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Latest 5 Khairat (need id + status for receipt)
$stmt = $pdo->prepare("SELECT id, deceased_name, claim_amount, status, created_at FROM khairat_claims WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$uid]);
$khairat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper for badge class
function badgeClass($status)
{
    if ($status === 'pending') return 'pending';
    if ($status === 'approved') return 'approved';
    if ($status === 'rejected') return 'rejected';
    if ($status === 'paid') return 'paid';
    return 'secondary';
}

$pageTitle = "User Dashboard";
require __DIR__ . '/../../app/views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">User Dashboard</h4>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Servis</h5>
                <a class="btn btn-primary w-100 mb-2" href="/epj/modules/user/kafan_add.php">Mohon Kafan</a>
                <a class="btn btn-primary w-100 mb-2" href="/epj/modules/user/burial_add.php">Mohon Perkuburan</a>
                <a class="btn btn-primary w-100" href="/epj/modules/user/khairat_add.php">Tuntutan Khairat</a>
            </div>
        </div>
    </div>

    <div class="col-md-8">

        <!-- KAFAN -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Permohonan Kafan (Latest 5)</h5>

                <?php if (!$kafan): ?>
                    <div class="text-muted">Belum ada permohonan.</div>
                <?php else: ?>
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Nama Jenazah</th>
                                <th style="width:130px;">Status</th>
                                <th style="width:190px;">Tarikh</th>
                                <th style="width:110px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kafan as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['deceased_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= badgeClass($r['status']) ?>">
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="/epj/modules/user/invoice_view.php?type=kafan&id=<?= (int)$r['id'] ?>">
                                            Invoice
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </div>

        <!-- PERKUBURAN -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Permohonan Perkuburan (Latest 5)</h5>

                <?php if (!$burial): ?>
                    <div class="text-muted">Belum ada permohonan.</div>
                <?php else: ?>
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Nama Jenazah</th>
                                <th style="width:130px;">Status</th>
                                <th style="width:190px;">Tarikh</th>
                                <th style="width:110px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($burial as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['deceased_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= badgeClass($r['status']) ?>">
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="/epj/modules/user/invoice_view.php?type=burial&id=<?= (int)$r['id'] ?>">
                                            Invoice
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </div>

        <!-- KHAIRAT -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Tuntutan Khairat (Latest 5)</h5>

                <?php if (!$khairat): ?>
                    <div class="text-muted">Belum ada tuntutan khairat.</div>
                <?php else: ?>
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Nama Jenazah</th>
                                <th style="width:140px;">Jumlah (RM)</th>
                                <th style="width:130px;">Status</th>
                                <th style="width:190px;">Tarikh</th>
                                <th style="width:120px;">Resit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($khairat as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['deceased_name']) ?></td>
                                    <td><?= number_format((float)$r['claim_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge badge-<?= badgeClass($r['status']) ?>">
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                                    <td>
                                        <?php if ($r['status'] === 'paid'): ?>
                                            <a class="btn btn-sm btn-outline-success"
                                                href="/epj/modules/user/khairat_receipt_print.php?id=<?= (int)$r['id'] ?>">
                                                Receipt
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php
require __DIR__ . '/../../app/views/footer.php';
