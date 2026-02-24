<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require_role(['admin']);

function badgeClass($status)
{
    if ($status === 'pending') return 'pending';
    if ($status === 'approved') return 'approved';
    if ($status === 'rejected') return 'rejected';
    return 'secondary';
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.fullname LIKE ? OR b.deceased_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM burial_requests b
  JOIN users u ON u.id=b.user_id
  $whereSQL
");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("
  SELECT b.id, u.fullname AS waris, b.deceased_name, b.status, b.created_at, b.document_path
  FROM burial_requests b
  JOIN users u ON u.id=b.user_id
  $whereSQL
  ORDER BY b.created_at DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Admin - Manage Perkuburan";
require __DIR__ . '/../../app/views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Manage Perkuburan</h4>
    <a class="btn btn-outline-secondary" href="/epj/modules/admin/dashboard.php">Back</a>
</div>

<form class="row g-2 mb-3" method="get">
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
        </select>
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary w-100">Filter</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">

        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Waris</th>
                    <th>Nama Jenazah</th>
                    <th style="width:140px;">Status</th>
                    <th style="width:200px;">Tarikh</th>
                    <th style="width:320px;">Aksi</th>
                </tr>
            </thead>
            <tbody>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6" class="text-muted text-center">Tiada rekod.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['waris']) ?></td>
                        <td><?= htmlspecialchars($r['deceased_name']) ?></td>
                        <td>
                            <span class="badge badge-<?= badgeClass($r['status']) ?>">
                                <?= htmlspecialchars($r['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">

                                <a class="btn btn-sm btn-outline-primary"
                                    href="/epj/modules/imam/request_details.php?type=burial&id=<?= (int)$r['id'] ?>">
                                    Details
                                </a>

                                <?php if (!empty($r['document_path'])): ?>
                                    <a class="btn btn-sm btn-outline-dark"
                                        href="/epj/public/file.php?type=burial&id=<?= (int)$r['id'] ?>">
                                        Doc
                                    </a>
                                <?php endif; ?>

                                <a class="btn btn-sm btn-outline-secondary"
                                    href="/epj/modules/user/invoice_view.php?type=burial&id=<?= (int)$r['id'] ?>">
                                    Invoice
                                </a>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link"
                        href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../../app/views/footer.php'; ?>