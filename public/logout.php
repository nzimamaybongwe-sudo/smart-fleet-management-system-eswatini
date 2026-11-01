<?php
require_once __DIR__ . '/../includes/init.php';
require_once APP_ROOT . '/includes/auth.php';
logout_user();
header('Location: login.php');
exit;
