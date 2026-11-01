<?php
// includes/init.php - bootstrap for the app
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

$root = dirname(__DIR__);
// load PDO
$pdo = require $root . '/config/database.php';

// helpful constants
define('APP_ROOT', $root);
define('PUBLIC_ROOT', $root . '/public');

require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/functions.php';
