<?php
// includes/auth.php - authentication helpers
require_once __DIR__ . '/init.php';

function login_user(string $email, string $password): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, password_hash, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash']) && $user['status'] === 'active') {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        // load roles
        $r = $pdo->prepare('SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
        $r->execute([(int)$user['id']]);
        $_SESSION['roles'] = $r->fetchAll(PDO::FETCH_COLUMN);
        // update last_login
        $u = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $u->execute([(int)$user['id']]);
        return true;
    }
    return false;
}

function require_login()
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function current_user()
{
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare('SELECT id, uuid, name, email, phone, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logout_user()
{
    session_unset();
    session_destroy();
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
}
