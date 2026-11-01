<?php
// api/fuel.php - log and fetch vehicle fuel records
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
            // Fetch fuel logs
            $stmt = $pdo->query('SELECT f.*, v.license_plate, v.make, v.model, u.name as driver_name 
                                 FROM fuel_logs f 
                                 JOIN vehicles v ON f.vehicle_id = v.id 
                                 JOIN drivers d ON f.driver_id = d.id 
                                 JOIN users u ON d.user_id = u.id 
                                 ORDER BY f.refuel_date DESC, f.id DESC');
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $vehicle_id = (int)($data['vehicle_id'] ?? 0);
            $driver_id = (int)($data['driver_id'] ?? 0);
            if ($driver_id === 0 && !empty($_SESSION['user_id'])) {
                $stmtDrv = $pdo->prepare('SELECT id FROM drivers WHERE user_id = ? LIMIT 1');
                $stmtDrv->execute([$_SESSION['user_id']]);
                $driver_id = (int)$stmtDrv->fetchColumn();
            }
            $refuel_date = !empty($data['refuel_date']) ? $data['refuel_date'] : date('Y-m-d');
            $liters = (float)($data['liters'] ?? 0.00);
            $cost = (float)($data['cost'] ?? 0.00);
            $odometer = (int)($data['odometer_reading'] ?? 0);
            $new_fuel_level = (int)($data['new_fuel_level'] ?? 100);

            if ($vehicle_id <= 0 || $driver_id <= 0 || empty($refuel_date) || $liters <= 0 || $cost < 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or missing fields']);
                exit;
            }

            // Fetch vehicle details for verification
            $s_veh = $pdo->prepare('SELECT license_plate, odometer FROM vehicles WHERE id = ?');
            $s_veh->execute([$vehicle_id]);
            $veh = $s_veh->fetch();
            if (!$veh) {
                http_response_code(404);
                echo json_encode(['error' => 'Vehicle not found']);
                exit;
            }
            
            if ($odometer < (int)$veh['odometer']) {
                http_response_code(400);
                echo json_encode(['error' => 'Odometer reading cannot be less than current vehicle odometer']);
                exit;
            }

            $pdo->beginTransaction();

            // Insert fuel log
            $stmt = $pdo->prepare('INSERT INTO fuel_logs (vehicle_id, driver_id, refuel_date, liters, cost, odometer_reading) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$vehicle_id, $driver_id, $refuel_date, $liters, $cost, $odometer]);

            // Update vehicle fuel level and odometer
            $upVeh = $pdo->prepare('UPDATE vehicles SET fuel_level = ?, odometer = ? WHERE id = ?');
            $upVeh->execute([$new_fuel_level, $odometer, $vehicle_id]);

            $pdo->commit();

            log_audit($pdo, 'ADD_FUEL_LOG', "Logged fuel refill for vehicle {$veh['license_plate']} ({$liters}L, \${$cost})");

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

            $stmtDel = $pdo->prepare('DELETE FROM fuel_logs WHERE id = ?');
            $stmtDel->execute([$id]);

            log_audit($pdo, 'DELETE_FUEL_LOG', "Deleted fuel log ID {$id}");

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
