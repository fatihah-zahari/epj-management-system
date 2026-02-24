<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require __DIR__ . '/../../app/helpers/csrf.php';
require_role(['admin']);

function badgeClass($status)
{
    if ($status === 'pending') return 'pending';
    if ($status === 'active') return 'approved';
    if ($status === 'inactive') return 'rejected';
    return 'secondary';
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(fullname LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (in_array($statusFilter, ['pending', 'active', 'inactive'], true)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("
  SELECT id, fullname, email, role, status, created_at
  FROM users
  $whereSQL
  ORDER BY created_at DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Manage Users";
require __DIR__ . '/../../app/views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Manage Users</h4>
    <a class="btn btn-outline-secondary" href="/epj/modules/admin/dashboard.php">Back</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-5">
        <input type="text" class="form-control" name="search"
            placeholder="Search nama / email"
            value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-4">
        <select class="form-select" name="status">
            <option value="">All Status</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
                    <th>#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th style="width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>

                <?php if (!$users): ?>
                    <tr>
                        <td colspan="6" class="text-muted text-center">Tiada rekod.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['fullname']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role']) ?></td>
                        <td>
                            <span class="badge badge-<?= badgeClass($u['status']) ?>">
                                <?= htmlspecialchars($u['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="user_details.php?id=<?= (int)$u['id'] ?>">Details</a>
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