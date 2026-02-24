<?php
if (!isset($pageTitle)) $pageTitle = "E-Pengurusan Jenazah";
$u = function_exists('current_user') ? current_user() : null;
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-approved {
            background-color: #198754;
        }

        .badge-rejected {
            background-color: #dc3545;
        }

        .badge-paid {
            background-color: #0d6efd;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/epj/">EPJ System</a>

            <div class="ms-auto d-flex align-items-center gap-3 text-white">

                <?php if ($u): ?>
                    <small>
                        <?= htmlspecialchars($u['fullname']) ?> (<?= htmlspecialchars($u['role']) ?>)
                    </small>
                    <a class="btn btn-sm btn-outline-light" href="/epj/public/logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-light" href="/epj/public/login.php">Login</a>
                <?php endif; ?>

            </div>
        </div>
    </nav>

    <div class="container py-4">