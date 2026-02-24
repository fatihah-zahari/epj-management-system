<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['admin']);

// ===== USERS KPI =====
$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$activeUsers  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();

// ===== KHAIRAT KPI =====
$totalKhairat   = (int)$pdo->query("SELECT COUNT(*) FROM khairat_claims")->fetchColumn();
$pendingKhairat = (int)$pdo->query("SELECT COUNT(*) FROM khairat_claims WHERE status='pending'")->fetchColumn();
$totalPaidAmount = (float)$pdo->query("
  SELECT COALESCE(SUM(claim_amount),0)
  FROM khairat_claims
  WHERE status='paid'
")->fetchColumn();

// ===== OTHER KPI (optional) =====
$totalKafan  = (int)$pdo->query("SELECT COUNT(*) FROM kafan_requests")->fetchColumn();
$totalBurial = (int)$pdo->query("SELECT COUNT(*) FROM burial_requests")->fetchColumn();

// ===== Monthly Khairat (last 6 months) =====
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i month"));
}

$monthlyMap = array_fill_keys($months, 0);

$stmt = $pdo->prepare("
  SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
  FROM khairat_claims
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY ym
");
$stmt->execute();

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    if (isset($monthlyMap[$r['ym']])) {
        $monthlyMap[$r['ym']] = (int)$r['c'];
    }
}

$khairatMonthlyLabels = array_keys($monthlyMap);
$khairatMonthlyData = array_values($monthlyMap);
$pageTitle = "Admin Dashboard";
require __DIR__ . '/../../app/views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Admin Dashboard</h4>
</div>

<div class="alert alert-info mb-4">
    <strong>E-Pengurusan Jenazah & Khairat Kematian System</strong><br>
    Role-based approval workflow & financial lifecycle tracking portal.
</div>

<!-- KPI ROW 1 -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Users</div>
                <div class="display-6"><?= $totalUsers ?></div>
                <div class="small text-muted">Active: <?= $activeUsers ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Pending Users</div>
                <div class="display-6 text-warning"><?= $pendingUsers ?></div>
                <div class="small text-muted">Need approval</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Khairat</div>
                <div class="display-6"><?= $totalKhairat ?></div>
                <div class="small text-muted">All claims</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Pending Khairat</div>
                <div class="display-6 text-warning"><?= $pendingKhairat ?></div>
                <div class="small text-muted">Awaiting review</div>
            </div>
        </div>
    </div>
</div>

<!-- KPI ROW 2 -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Paid Amount</div>
                <div class="display-6 text-success">
                    RM <?= number_format($totalPaidAmount, 2) ?>
                </div>
                <div class="small text-muted">Paid khairat total</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Kafan</div>
                <div class="display-6"><?= $totalKafan ?></div>
                <div class="small text-muted">All requests</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Perkuburan</div>
                <div class="display-6"><?= $totalBurial ?></div>
                <div class="small text-muted">All requests</div>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="mb-3">Quick Actions</h5>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-warning" href="/epj/modules/admin/users_approve.php">Approve Users (Pending)</a>
            <a class="btn btn-primary" href="/epj/modules/admin/users_manage.php">Manage Users</a>

            <a class="btn btn-primary" href="/epj/modules/admin/khairat_manage.php">Manage Khairat</a>
            <a class="btn btn-primary" href="/epj/modules/admin/kafan_manage.php">Manage Kafan</a>
            <a class="btn btn-primary" href="/epj/modules/admin/burial_manage.php">Manage Perkuburan</a>

            <a class="btn btn-dark" href="/epj/modules/admin/analytics.php">Analytics</a>
        </div>

    </div>
</div>
<div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
        <h5 class="mb-3">Khairat Claims (Last 6 Months)</h5>
        <canvas id="khairatMiniChart" height="110"></canvas>
        <div class="text-muted small mt-2">Auto-generated from real database records.</div>
    </div>
</div>

<script>
    const khairatLabels = <?= json_encode($khairatMonthlyLabels) ?>;
    const khairatData = <?= json_encode($khairatMonthlyData) ?>;

    new Chart(document.getElementById('khairatMiniChart'), {
        type: 'bar',
        data: {
            labels: khairatLabels,
            datasets: [{
                label: 'Total Claims',
                data: khairatData
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>
<?php require __DIR__ . '/../../app/views/footer.php'; ?>