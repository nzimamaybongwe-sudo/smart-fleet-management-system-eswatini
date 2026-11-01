<?php
// api/drivers.php - CRUD for drivers joined with users
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/init.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function log_audit($pdo, $action, $description) {
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, description, ip) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $description, $ip]);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Fetch all drivers
            $stmt = $pdo->query('SELECT d.id, d.user_id, u.name, u.email, u.phone, d.license_number, d.license_expiry, d.status 
                                 FROM drivers d 
                                 JOIN users u ON d.user_id = u.id 
                                 ORDER BY d.id DESC');
            $drivers = $stmt->fetchAll();
            echo json_encode($drivers);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $password = $data['password'] ?? 'Driver@123';
            $license = trim($data['license_number'] ?? '');
            $expiry = $data['license_expiry'] ?? '';
            $status = $data['status'] ?? 'active';

            if (empty($name) || empty($email) || empty($license) || empty($expiry)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }

            // Verify email uniqueness
            $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $checkEmail->execute([$email]);
            if ($checkEmail->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email address already registered']);
                exit;
            }

            // Verify license uniqueness
            $checkLicense = $pdo->prepare('SELECT id FROM drivers WHERE license_number = ?');
            $checkLicense->execute([$license]);
            if ($checkLicense->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'License number already registered']);
                exit;
            }

            $pdo->beginTransaction();

            // 1. Insert into users
            $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email, phone, password_hash, status) VALUES (UUID(), ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), 'active']);
            $userId = $pdo->lastInsertId();

            // 2. Fetch driver role ID
            $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE name = 'driver' LIMIT 1");
            $stmtRole->execute();
            $roleId = $stmtRole->fetchColumn();

            // 3. Assign role
            if ($roleId) {
                $ir = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
                $ir->execute([$userId, $roleId]);
            }

            // 4. Create driver profile
            $idr = $pdo->prepare('INSERT INTO drivers (user_id, license_number, license_expiry, status) VALUES (?, ?, ?, ?)');
            $idr->execute([$userId, $license, $expiry, $status]);
            $driverId = $pdo->lastInsertId();

            $pdo->commit();

            log_audit($pdo, 'ADD_DRIVER', "Registered new driver: {$name} (License: {$license})");

            echo json_encode(['success' => true, 'id' => $driverId]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            $userId = (int)($data['user_id'] ?? 0);
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $license = trim($data['license_number'] ?? '');
            $expiry = $data['license_expiry'] ?? '';
            $status = $data['status'] ?? 'active';

            if ($id <= 0 || $userId <= 0 || empty($name) || empty($email) || empty($license) || empty($expiry)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or missing fields']);
                exit;
            }

            // Verify unique constraints
            $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $checkEmail->execute([$email, $userId]);
            if ($checkEmail->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email is already in use by another user']);
                exit;
            }

            $checkLicense = $pdo->prepare('SELECT id FROM drivers WHERE license_number = ? AND id != ?');
            $checkLicense->execute([$license, $id]);
            if ($checkLicense->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'License number is already in use']);
                exit;
            }

            $pdo->beginTransaction();

            // 1. Update user details
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone, $userId]);

            // 2. Update driver details
            $stmt2 = $pdo->prepare('UPDATE drivers SET license_number = ?, license_expiry = ?, status = ? WHERE id = ?');
            $stmt2->execute([$license, $expiry, $status, $id]);

            $pdo->commit();

            log_audit($pdo, 'UPDATE_DRIVER', "Updated driver ID {$id}: {$name}");

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_GET;
            $id = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing driver ID']);
                exit;
            }

            // Get driver name and user ID for cascading
            $stmt = $pdo->prepare('SELECT d.user_id, u.name FROM drivers d JOIN users u ON d.user_id = u.id WHERE d.id = ?');
            $stmt->execute([$id]);
            $driverInfo = $stmt->fetch();

            if (!$driverInfo) {
                http_response_code(404);
                echo json_encode(['error' => 'Driver not found']);
                exit;
            }

            // Check if there are active trips referencing this driver
            $check = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE driver_id = ? AND status = 'in_progress'");
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Cannot delete driver with ongoing trip']);
                exit;
            }

            // Deleting user cascades to drivers and user_roles tables
            $stmtDel = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmtDel->execute([$driverInfo['user_id']]);

            log_audit($pdo, 'DELETE_DRIVER', "Deleted driver: {$driverInfo['name']}");

            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
