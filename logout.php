<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) {
    logAudit($conn, currentUserId(), 'LOGOUT: ' . currentUserName());
}

session_unset();
session_destroy();

header('Location: ' . appUrl('/login.php'));
exit;
