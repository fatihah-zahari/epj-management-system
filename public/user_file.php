<?php
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/middleware/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit("Invalid");
}

$stmt = $pdo->prepare("SELECT id, ic_doc_path FROM users WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['ic_doc_path'])) {
    http_response_code(404);
    exit("File not found");
}

$u = current_user();
// owner boleh view, admin boleh view
if ($u['role'] !== 'admin' && (int)$u['id'] !== (int)$row['id']) {
    http_response_code(403);
    exit("403 Forbidden");
}

$path = __DIR__ . '/../' . ltrim($row['ic_doc_path'], '/');
if (!file_exists($path)) {
    http_response_code(404);
    exit("Missing file");
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
