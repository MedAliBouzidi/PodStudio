<?php

require_once __DIR__ . '/utils/session.php';

if (!isLoggedIn()) {
    header("Location: /pages/auth/login.php");
    exit;
}

if (isAdmin()) {
    header("Location: /pages/admin/dashboard.php");
    exit;
}

header("Location: /pages/client/home.php");
exit;