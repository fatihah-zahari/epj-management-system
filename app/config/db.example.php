<?php
$host = 'localhost';
$db   = 'epj_database';
$user = 'your_username';
$pass = 'your_password';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
