<?php
require __DIR__ . '/../../app/config/db.php';
require __DIR__ . '/../../app/middleware/auth.php';
require __DIR__ . '/../../app/helpers/csrf.php';
require_role(['admin']);

$error = '';

function badgeClass($status)
{
    if ($status === 'pending') return 'pending';
    if ($status === 'active') return 'approved';
    if ($status === 'inactive') return 'rejected';
    return 'secondary';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = "Invalid CSRF.";
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($id > 0 && in_array($action, ['approve', 'reject'], true)) {
            $newStatus = $action === 'approve' ? 'active' : 'inactive';
            $stmt = $pdo->prepare("UPDATE users SET status=? WHERE id=? AND status='pending'");
            $stmt->execute([$newStatus, $id]);

            header("Location: users_approve.php");
            exit;
        }
    }
}

$users = $pdo->query("
  SELECT id, fullname, email, role, status, created_at
  FROM users
  WHERE status='pending'
  ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Approve Users (Pending)";
require __DIR__ . '/../../app/views/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Approve Users (Pending)</h4>
    <a class="btn btn-outline-secondary" href="/epj/modules/admin/dashboard.php">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">

        <?php if (!$users): ?>
            <div class="text-muted">Tiada user pending.</div>
        <?php else: ?>
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="width:320px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td><?= htmlspecialchars($u['fullname']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td><span class="badge badge-<?= badgeClass($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="user_details.php?id=<?= (int)$u['id'] ?>">Details</a>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                        <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</div>

<?php require __DIR__ . '/../../app/views/footer.php'; ?>