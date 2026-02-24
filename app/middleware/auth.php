<?php
// app/middleware/auth.php

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (empty($_SESSION['user'])) {
        header("Location: /epj/public/login.php");
        exit;
    }
}

function require_role($roles = [])
{
    require_login();
    $u = current_user();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        echo "403 Forbidden";
        exit;
    }
}
