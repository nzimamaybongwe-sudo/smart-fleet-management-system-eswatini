<?php
// api/inspections.php - handles pre-trip safety & cold chain compliance inspections
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
            $stmt = $pdo->query('SELECT insp.*, v.license_plate, v.make, v.model, u.name as driver_name 
                                 FROM inspections insp 
                                 JOIN vehicles v ON insp.vehicle_id = v.id 
                                 JOIN drivers d ON insp.driver_id = d.id 
                                 JOIN users u ON d.user_id = u.id 
                                 ORDER BY insp.id DESC LIMIT 50');
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $vehicle_id = (int)($data['vehicle_id'] ?? 0);
            $odometer = (int)($data['odometer_reading'] ?? 0);
            $tires_passed = !empty($data['tires_passed']) ? 1 : 0;
            $brakes_passed = !empty($data['brakes_passed']) ? 1 : 0;
            $lights_passed = !empty($data['lights_passed']) ? 1 : 0;
            $fluids_passed = !empty($data['fluids_passed']) ? 1 : 0;
            $reefer_temp_checked = !empty($data['reefer_temp_checked']) ? 1 : 0;
            $recorded_temp = isset($data['recorded_temp']) && $data['recorded_temp'] !== '' ? (float)$data['recorded_temp'] : null;
            $notes = trim($data['notes'] ?? '');

            // Get driver_id
            $driver_id = (int)($data['driver_id'] ?? 0);
            if ($driver_id === 0) {
                $stmtDrv = $pdo->prepare('SELECT id FROM drivers WHERE user_id = ? LIMIT 1');
                $stmtDrv->execute([$_SESSION['user_id']]);
                $driver_id = (int)$stmtDrv->fetchColumn();
            }

            if ($vehicle_id <= 0 || $driver_id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid vehicle or driver ID']);
                exit;
            }

            // Determine status
            $allPassed = ($tires_passed && $brakes_passed && $lights_passed && $fluids_passed);
            $status = $allPassed ? 'passed' : 'flagged_for_maintenance';

            // If recorded temp provided, update vehicle's temp_celsius
            if ($recorded_temp !== null) {
                $pdo->prepare('UPDATE vehicles SET temp_celsius = ? WHERE id = ?')->execute([$recorded_temp, $vehicle_id]);
            }

            $stmt = $pdo->prepare('INSERT INTO inspections (vehicle_id, driver_id, odometer_reading, tires_passed, brakes_passed, lights_passed, fluids_passed, reefer_temp_checked, recorded_temp, status, notes) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$vehicle_id, $driver_id, $odometer, $tires_passed, $brakes_passed, $lights_passed, $fluids_passed, $reefer_temp_checked, $recorded_temp, $status, $notes]);
            $inspectionId = $pdo->lastInsertId();

            if ($status === 'flagged_for_maintenance') {
                $pdo->prepare('UPDATE vehicles SET status = "maintenance" WHERE id = ?')->execute([$vehicle_id]);
            }

            log_audit($pdo, 'INSPECTION_SUBMITTED', "Pre-trip inspection #{$inspectionId} for vehicle ID {$vehicle_id}: {$status}");

            echo json_encode([
                'success' => true, 
                'id' => $inspectionId, 
                'status' => $status,
                'message' => $status === 'passed' ? 'Pre-trip inspection passed. Ready for dispatch!' : 'Inspection flagged items for maintenance!'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
