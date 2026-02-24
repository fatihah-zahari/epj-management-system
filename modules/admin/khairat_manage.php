<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require __DIR__ . '/../../app/helpers/csrf.php';
require_role(['admin']);

function badgeClass($status)
{
    if ($status === 'pending') return 'pending';
    if ($status === 'approved') return 'approved';
    if ($status === 'rejected') return 'rejected';
    if ($status === 'paid') return 'paid';
    return 'secondary';
}

function make_receipt_no()
{
    $date = date('Ymd');
    $rand = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    return "RCPT-$date-$rand";
}

$error = '';

/* ===== Mark Paid ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Invalid CSRF.";
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($id > 0 && $action === 'paid') {
            // only allow if currently approved
            $stmt = $pdo->prepare("SELECT status FROM khairat_claims WHERE id=? LIMIT 1");
            $stmt->execute([$id]);
            $cur = $stmt->fetchColumn();

            if ($cur !== 'approved') {
                $error = "Hanya status 'approved' boleh ditanda paid.";
            } else {
                $receipt = make_receipt_no();
                $stmt = $pdo->prepare("
          UPDATE khairat_claims
          SET status='paid', receipt_no=?, decided_by=?, decided_at=NOW(), updated_at=NOW()
          WHERE id=?
        ");
                $stmt->execute([$receipt, current_user()['id'], $id]);

                header("Location: khairat_manage.php");
                exit;
            }
        }
    }
}

/* ===== Search + Filter ===== */
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.fullname LIKE ? OR k.deceased_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (in_array($statusFilter, ['pending', 'approved', 'rejected', 'paid'], true)) {
    $where[] = "k.status = ?";
    $params[] = $statusFilter;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ===== Pagination ===== */
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM khairat_claims k
  JOIN users u ON u.id=k.user_id
  $whereSQL
");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("
  SELECT k.id, u.fullname AS waris, k.deceased_name, k.claim_amount, k.status, k.created_at, k.receipt_no, k.document_path
  FROM khairat_claims k
  JOIN users u ON u.id=k.user_id
  $whereSQL
  ORDER BY k.created_at DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Admin - Manage Khairat";
require __DIR__ . '/../../app/views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Manage Khairat</h4>
    <a class="btn btn-outline-secondary" href="/epj/modules/admin/dashboard.php">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

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
            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
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
                    <th>Jumlah</th>
                    <th style="width:140px;">Status</th>
                    <th>Receipt No</th>
                    <th style="width:260px;">Aksi</th>
                </tr>
            </thead>
            <tbody>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="text-muted text-center">Tiada rekod.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['waris']) ?></td>
                        <td><?= htmlspecialchars($r['deceased_name']) ?></td>
                        <td>RM <?= number_format((float)$r['claim_amount'], 2) ?></td>
                        <td>
                            <span class="badge badge-<?= badgeClass($r['status']) ?>">
                                <?= htmlspecialchars($r['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['receipt_no'] ?? '-') ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">

                                <a class="btn btn-sm btn-outline-primary"
                                    href="/epj/modules/imam/request_details.php?type=khairat&id=<?= (int)$r['id'] ?>">
                                    Details
                                </a>

                                <?php if (!empty($r['document_path'])): ?>
                                    <a class="btn btn-sm btn-outline-dark"
                                        href="/epj/public/file.php?type=khairat&id=<?= (int)$r['id'] ?>">
                                        Doc
                                    </a>
                                <?php endif; ?>

                                <?php if ($r['status'] === 'approved'): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button name="action" value="paid" class="btn btn-sm btn-success">
                                            Mark Paid
                                        </button>
                                    </form>
                                <?php endif; ?>

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