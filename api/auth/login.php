<?php
// api/auth/login.php - accepts JSON or form POST and returns JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/init.php';
require_once APP_ROOT . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    // fallback to form data
    $data = $_POST;
}

$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $data['password'] ?? '';
$csrf = $data['csrf'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

if (login_user($email, $password)) {
    $roles = $_SESSION['roles'] ?? [];
    $role = in_array('admin', $roles) ? 'admin' : (in_array('driver', $roles) ? 'driver' : 'user');
    echo json_encode(['success' => true, 'role' => $role]);
    exit;
}

http_response_code(401);
echo json_encode(['error' => 'Invalid credentials']);
