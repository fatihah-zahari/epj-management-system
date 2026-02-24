<?php
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/middleware/auth.php';
require_login();

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$map = [
    'kafan'   => ['table' => 'kafan_requests',  'owner_col' => 'user_id'],
    'burial'  => ['table' => 'burial_requests', 'owner_col' => 'user_id'],
    'khairat' => ['table' => 'khairat_claims',  'owner_col' => 'user_id'],
];

if (!isset($map[$type]) || $id <= 0) {
    http_response_code(400);
    exit("Invalid request");
}

$table = $map[$type]['table'];
$stmt = $pdo->prepare("SELECT user_id, document_path FROM {$table} WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['document_path'])) {
    http_response_code(404);
    exit("File not found");
}

// user can access own files, admin/imam can access all
$u = current_user();
if ($u['role'] === 'user' && (int)$row['user_id'] !== (int)$u['id']) {
    http_response_code(403);
    exit("403 Forbidden");
}

$path = __DIR__ . '/../' . ltrim($row['document_path'], '/');
if (!file_exists($path)) {
    http_response_code(404);
    exit("File missing");
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . basename($path) . '"');

readfile($path);
exit;
