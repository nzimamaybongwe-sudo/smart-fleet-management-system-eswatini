<?php
// api/maintenance.php - log and fetch vehicle maintenance records
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
            // Fetch maintenance logs
            $stmt = $pdo->query('SELECT m.*, v.license_plate, v.make, v.model 
                                 FROM maintenance_logs m 
                                 JOIN vehicles v ON m.vehicle_id = v.id 
                                 ORDER BY m.service_date DESC, m.id DESC');
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $vehicle_id = (int)($data['vehicle_id'] ?? 0);
            $service_date = $data['service_date'] ?? '';
            $service_type = trim($data['service_type'] ?? '');
            $description = trim($data['description'] ?? '');
            $cost = (float)($data['cost'] ?? 0.00);
            $odometer = (int)($data['odometer_reading'] ?? 0);

            if ($vehicle_id <= 0 || empty($service_date) || empty($service_type) || $cost < 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or missing fields']);
                exit;
            }

            // Fetch vehicle details for verification and audit log
            $s_veh = $pdo->prepare('SELECT license_plate FROM vehicles WHERE id = ?');
            $s_veh->execute([$vehicle_id]);
            $plate = $s_veh->fetchColumn();
            if (!$plate) {
                http_response_code(404);
                echo json_encode(['error' => 'Vehicle not found']);
                exit;
            }

            $pdo->beginTransaction();

            // Insert maintenance log
            $stmt = $pdo->prepare('INSERT INTO maintenance_logs (vehicle_id, service_date, service_type, description, cost, odometer_reading) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$vehicle_id, $service_date, $service_type, $description, $cost, $odometer]);

            // Set vehicle status to maintenance if the date is today
            if (date('Y-m-d', strtotime($service_date)) === date('Y-m-d')) {
                $upVeh = $pdo->prepare("UPDATE vehicles SET status = 'maintenance' WHERE id = ?");
                $upVeh->execute([$vehicle_id]);
            }

            $pdo->commit();

            log_audit($pdo, 'ADD_MAINTENANCE', "Logged {$service_type} for vehicle {$plate} (Cost: \${$cost})");

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_GET;
            $id = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing log ID']);
                exit;
            }

            $stmtDel = $pdo->prepare('DELETE FROM maintenance_logs WHERE id = ?');
            $stmtDel->execute([$id]);

            log_audit($pdo, 'DELETE_MAINTENANCE', "Deleted maintenance log ID {$id}");

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
