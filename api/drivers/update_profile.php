<?php
// api/drivers/update_profile.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/init.php';
require_once APP_ROOT . '/includes/auth.php';

require_login();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$phone = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';

if (empty($phone) && empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nothing to update']);
    exit;
}

try {
    global $pdo;
    $pdo->beginTransaction();

    if (!empty($phone)) {
        $stmt = $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?');
        $stmt->execute([$phone, $user_id]);
    }

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $user_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
