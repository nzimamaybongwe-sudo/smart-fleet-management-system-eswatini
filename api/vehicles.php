<?php
// api/vehicles.php - handles CRUD operations for fleet vehicles
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/init.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Simple audit log helper
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
            // Fetch all vehicles
            $stmt = $pdo->query('SELECT * FROM vehicles ORDER BY id DESC');
            $vehicles = $stmt->fetchAll();
            echo json_encode($vehicles);
            break;

        case 'POST':
            // Add a new vehicle
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $plate = trim($data['license_plate'] ?? '');
            $make = trim($data['make'] ?? '');
            $model = trim($data['model'] ?? '');
            $year = (int)($data['year'] ?? 0);
            $type = $data['type'] ?? 'truck';
            $status = $data['status'] ?? 'active';
            $odometer = (int)($data['odometer'] ?? 0);
            $fuel = (int)($data['fuel_level'] ?? 100);

            if (empty($plate) || empty($make) || empty($model) || $year < 1900) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or missing fields']);
                exit;
            }

            // Verify uniqueness of license plate
            $check = $pdo->prepare('SELECT id FROM vehicles WHERE license_plate = ?');
            $check->execute([$plate]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'License plate already registered']);
                exit;
            }

            $stmt = $pdo->prepare('INSERT INTO vehicles (license_plate, make, model, year, type, status, odometer, fuel_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$plate, $make, $model, $year, $type, $status, $odometer, $fuel]);
            
            log_audit($pdo, 'ADD_VEHICLE', "Added new vehicle: {$make} {$model} ({$plate})");
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            // Update a vehicle
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            $plate = trim($data['license_plate'] ?? '');
            $make = trim($data['make'] ?? '');
            $model = trim($data['model'] ?? '');
            $year = (int)($data['year'] ?? 0);
            $type = $data['type'] ?? 'truck';
            $status = $data['status'] ?? 'active';
            $odometer = (int)($data['odometer'] ?? 0);
            $fuel = (int)($data['fuel_level'] ?? 100);

            if ($id <= 0 || empty($plate) || empty($make) || empty($model) || $year < 1900) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data provided']);
                exit;
            }

            // Verify license plate uniqueness if changed
            $check = $pdo->prepare('SELECT id FROM vehicles WHERE license_plate = ? AND id != ?');
            $check->execute([$plate, $id]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'License plate is already in use by another vehicle']);
                exit;
            }

            $stmt = $pdo->prepare('UPDATE vehicles SET license_plate = ?, make = ?, model = ?, year = ?, type = ?, status = ?, odometer = ?, fuel_level = ? WHERE id = ?');
            $stmt->execute([$plate, $make, $model, $year, $type, $status, $odometer, $fuel, $id]);

            log_audit($pdo, 'UPDATE_VEHICLE', "Updated vehicle ID {$id}: {$make} {$model} ({$plate}) to status: {$status}");

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            // Delete a vehicle
            $data = json_decode(file_get_contents('php://input'), true) ?: $_GET;
            $id = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing vehicle ID']);
                exit;
            }

            // Check if there are active trips referencing this vehicle
            $check = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE vehicle_id = ? AND status = 'in_progress'");
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Cannot delete vehicle with ongoing trip']);
                exit;
            }

            // Get plate for audit log
            $s_info = $pdo->prepare('SELECT license_plate FROM vehicles WHERE id = ?');
            $s_info->execute([$id]);
            $plate = $s_info->fetchColumn() ?: "ID $id";

            $stmt = $pdo->prepare('DELETE FROM vehicles WHERE id = ?');
            $stmt->execute([$id]);

            log_audit($pdo, 'DELETE_VEHICLE', "Deleted vehicle: {$plate}");

            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
