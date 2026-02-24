<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require __DIR__ . '/../../app/helpers/csrf.php';
require_role(['imam']);

$error = '';

function badgeClass($status)
{
    if ($status === 'pending') return 'pending';
    if ($status === 'approved') return 'approved';
    if ($status === 'rejected') return 'rejected';
    if ($status === 'paid') return 'paid';
    return 'secondary';
}

/* ===== Approve/Reject ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Invalid CSRF.";
    } else {
        $type = $_POST['type'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        $map = [
            'kafan' => 'kafan_requests',
            'burial' => 'burial_requests',
            'khairat' => 'khairat_claims'
        ];

        if (isset($map[$type]) && $id > 0 && in_array($action, ['approve', 'reject'], true)) {
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $table = $map[$type];

            $stmt = $pdo->prepare("UPDATE {$table} SET status=?, decided_by=?, decided_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$newStatus, current_user()['id'], $id]);

            header("Location: dashboard.php?tab=" . urlencode($type));
            exit;
        }
    }
}

/* ===== Tab ===== */
$tab = $_GET['tab'] ?? 'kafan';
if (!in_array($tab, ['kafan', 'burial', 'khairat'], true)) $tab = 'kafan';

/* ===== Search + Filter ===== */
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.fullname LIKE ? OR t.deceased_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (in_array($statusFilter, ['pending', 'approved', 'rejected', 'paid'], true)) {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ===== Pagination ===== */
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

/* ===== Build queries by tab ===== */
if ($tab === 'kafan') {
    $countSql = "SELECT COUNT(*) FROM kafan_requests t JOIN users u ON u.id=t.user_id $whereSQL";
    $dataSql = "
    SELECT t.id, u.fullname AS requester, t.deceased_name, t.status, t.document_path
    FROM kafan_requests t
    JOIN users u ON u.id=t.user_id
    $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perPage OFFSET $offset
  ";
} elseif ($tab === 'burial') {
    $countSql = "SELECT COUNT(*) FROM burial_requests t JOIN users u ON u.id=t.user_id $whereSQL";
    $dataSql = "
    SELECT t.id, u.fullname AS requester, t.deceased_name, t.status, t.document_path
    FROM burial_requests t
    JOIN users u ON u.id=t.user_id
    $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perPage OFFSET $offset
  ";
} else {
    $countSql = "SELECT COUNT(*) FROM khairat_claims t JOIN users u ON u.id=t.user_id $whereSQL";
    $dataSql = "
    SELECT t.id, u.fullname AS requester, t.deceased_name, t.claim_amount, t.status, t.document_path
    FROM khairat_claims t
    JOIN users u ON u.id=t.user_id
    $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perPage OFFSET $offset
  ";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare($dataSql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Imam Dashboard";
require __DIR__ . '/../../app/views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Imam Dashboard</h4>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link <?= $tab === 'kafan' ? 'active' : '' ?>" href="?tab=kafan">Kafan</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'burial' ? 'active' : '' ?>" href="?tab=burial">Perkuburan</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'khairat' ? 'active' : '' ?>" href="?tab=khairat">Khairat</a></li>
</ul>

<!-- Search + Filter -->
<form class="row g-2 mb-3" method="get">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

    <div class="col-md-5">
        <input class="form-control" name="search" placeholder="Search waris / nama jenazah"
            value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-4">
        <select class="form-select" name="status">
            <option value="">All Status</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            <?php if ($tab === 'khairat'): ?>
                <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
            <?php endif; ?>
        </select>
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary w-100">Filter</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">

        <?php if (!$rows): ?>
            <div class="text-muted">Tiada rekod.</div>
        <?php else: ?>

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Waris</th>
                        <th>Nama Jenazah</th>
                        <?php if ($tab === 'khairat'): ?><th>Jumlah</th><?php endif; ?>
                        <th style="width:130px;">Status</th>
                        <th style="width:380px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td><?= htmlspecialchars($r['requester']) ?></td>
                            <td><?= htmlspecialchars($r['deceased_name']) ?></td>

                            <?php if ($tab === 'khairat'): ?>
                                <td>RM <?= number_format((float)$r['claim_amount'], 2) ?></td>
                            <?php endif; ?>

                            <td>
                                <span class="badge badge-<?= badgeClass($r['status']) ?>">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex flex-wrap gap-2">

                                    <a class="btn btn-sm btn-outline-primary"
                                        href="/epj/modules/imam/request_details.php?type=<?= htmlspecialchars($tab) ?>&id=<?= (int)$r['id'] ?>">
                                        Details
                                    </a>

                                    <?php if (!empty($r['document_path'])): ?>
                                        <a class="btn btn-sm btn-outline-dark"
                                            href="/epj/public/file.php?type=<?= htmlspecialchars($tab) ?>&id=<?= (int)$r['id'] ?>">
                                            Doc
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="type" value="<?= htmlspecialchars($tab) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                            <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Selesai</span>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link"
                        href="?tab=<?= urlencode($tab) ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../../app/views/footer.php'; ?>