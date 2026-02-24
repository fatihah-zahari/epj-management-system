<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['admin']);

// KPI counts
$totalKafan   = (int)$pdo->query("SELECT COUNT(*) FROM kafan_requests")->fetchColumn();
$totalBurial  = (int)$pdo->query("SELECT COUNT(*) FROM burial_requests")->fetchColumn();
$totalKhairat = (int)$pdo->query("SELECT COUNT(*) FROM khairat_claims")->fetchColumn();

$pendingAll = (int)$pdo->query("
  SELECT
    (SELECT COUNT(*) FROM kafan_requests WHERE status='pending') +
    (SELECT COUNT(*) FROM burial_requests WHERE status='pending') +
    (SELECT COUNT(*) FROM khairat_claims WHERE status='pending')
")->fetchColumn();

$approvedAll = (int)$pdo->query("
  SELECT
    (SELECT COUNT(*) FROM kafan_requests WHERE status='approved') +
    (SELECT COUNT(*) FROM burial_requests WHERE status='approved') +
    (SELECT COUNT(*) FROM khairat_claims WHERE status='approved')
")->fetchColumn();

$paidKhairatTotal = (float)$pdo->query("
  SELECT COALESCE(SUM(claim_amount),0) FROM khairat_claims WHERE status='paid'
")->fetchColumn();

// Monthly counts for last 6 months (kafan/burial/khairat)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i month"));
}

function monthlyCounts(PDO $pdo, string $table, string $dateCol, array $months): array
{
    $out = array_fill_keys($months, 0);
    $stmt = $pdo->prepare("
    SELECT DATE_FORMAT($dateCol, '%Y-%m') AS ym, COUNT(*) AS c
    FROM $table
    WHERE $dateCol >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
  ");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (isset($out[$r['ym']])) $out[$r['ym']] = (int)$r['c'];
    }
    return array_values($out);
}

$kafanSeries  = monthlyCounts($pdo, 'kafan_requests', 'created_at', $months);
$burialSeries = monthlyCounts($pdo, 'burial_requests', 'created_at', $months);
$khairatSeries = monthlyCounts($pdo, 'khairat_claims', 'created_at', $months);

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Analytics - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Admin Analytics</h4>
            <a class="btn btn-outline-secondary" href="/epj/modules/admin/dashboard.php">Back</a>
        </div>

        <!-- KPI cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted">Total Kafan</div>
                        <div class="display-6"><?= $totalKafan ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted">Total Perkuburan</div>
                        <div class="display-6"><?= $totalBurial ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted">Total Khairat</div>
                        <div class="display-6"><?= $totalKhairat ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted">Paid Khairat (RM)</div>
                        <div class="display-6"><?= number_format($paidKhairatTotal, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted">Pending (All Services)</div>
                        <div class="display-6"><?= $pendingAll ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted">Approved (All Services)</div>
                        <div class="display-6"><?= $approvedAll ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Permohonan Mengikut Bulan (Last 6 Months)</h5>
                <canvas id="svcChart" height="110"></canvas>
                <div class="text-muted small mt-2">Data diambil daripada `created_at` setiap modul.</div>
            </div>
        </div>

    </div>

    <script>
        const labels = <?= json_encode($months) ?>;
        const data = {
            labels,
            datasets: [{
                    label: 'Kafan',
                    data: <?= json_encode($kafanSeries) ?>
                },
                {
                    label: 'Perkuburan',
                    data: <?= json_encode($burialSeries) ?>
                },
                {
                    label: 'Khairat',
                    data: <?= json_encode($khairatSeries) ?>
                }
            ]
        };
        new Chart(document.getElementById('svcChart'), {
            type: 'line',
            data,
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
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
</body>

</html>