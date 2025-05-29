<?php
session_start();

function isAdmin() {
    return isset($_SESSION['user']['isadmin']) && $_SESSION['user']['isadmin'] == 9;
}

function checkAdminAccess() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}