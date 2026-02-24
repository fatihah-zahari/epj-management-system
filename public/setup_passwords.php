<?php
require __DIR__ . '/../app/config/db.php';

$adminPw = 'Admin@12345';
$imamPw  = 'Imam@12345';

$pdo->prepare("UPDATE users SET password_hash=? WHERE email='admin@epj.local'")
    ->execute([password_hash($adminPw, PASSWORD_DEFAULT)]);

$pdo->prepare("UPDATE users SET password_hash=? WHERE email='imam@epj.local'")
    ->execute([password_hash($imamPw, PASSWORD_DEFAULT)]);

echo "DONE. Admin: admin@epj.local / $adminPw <br> Imam: imam@epj.local / $imamPw";
