<?php
// api/incidents.php - handles incident reporting and resolution
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
            $stmt = $pdo->query('SELECT i.*, v.license_plate, v.make, v.model, u.name as driver_name, u.phone as driver_phone
                                 FROM incidents i 
                                 JOIN vehicles v ON i.vehicle_id = v.id 
                                 JOIN drivers d ON i.driver_id = d.id 
                                 JOIN users u ON d.user_id = u.id 
                                 ORDER BY (i.status = "reported") DESC, i.id DESC');
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $vehicle_id = (int)($data['vehicle_id'] ?? 0);
            $incident_type = $data['incident_type'] ?? 'breakdown';
            $severity = $data['severity'] ?? 'medium';
            $location = trim($data['location'] ?? '');
            $description = trim($data['description'] ?? '');
            $trip_id = !empty($data['trip_id']) ? (int)$data['trip_id'] : null;

            // Determine driver_id from logged-in user if driver, or passed in
            $driver_id = (int)($data['driver_id'] ?? 0);
            if ($driver_id === 0) {
                $stmtDrv = $pdo->prepare('SELECT id FROM drivers WHERE user_id = ? LIMIT 1');
                $stmtDrv->execute([$_SESSION['user_id']]);
                $driver_id = (int)$stmtDrv->fetchColumn();
            }

            if ($vehicle_id <= 0 || $driver_id <= 0 || empty($location) || empty($description)) {
                http_response_code(400);
                echo json_encode(['error' => 'Please provide vehicle, location, and description']);
                exit;
            }

            $stmt = $pdo->prepare('INSERT INTO incidents (trip_id, vehicle_id, driver_id, incident_type, severity, location, description, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, "reported")');
            $stmt->execute([$trip_id, $vehicle_id, $driver_id, $incident_type, $severity, $location, $description]);
            $incidentId = $pdo->lastInsertId();

            // If critical or breakdown, mark vehicle as maintenance/out_of_service
            if ($severity === 'critical' || $incident_type === 'breakdown') {
                $pdo->prepare('UPDATE vehicles SET status = "maintenance" WHERE id = ?')->execute([$vehicle_id]);
            }

            log_audit($pdo, 'INCIDENT_REPORTED', "Incident #{$incidentId} ({$incident_type}, {$severity}) reported at {$location}");

            echo json_encode(['success' => true, 'id' => $incidentId, 'message' => 'Incident reported successfully']);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            $status = $data['status'] ?? 'resolved';
            $resolution_notes = trim($data['resolution_notes'] ?? '');

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid incident ID']);
                exit;
            }

            $resolved_at = ($status === 'resolved') ? date('Y-m-d H:i:s') : null;

            $stmt = $pdo->prepare('UPDATE incidents SET status = ?, resolution_notes = ?, resolved_at = ? WHERE id = ?');
            $stmt->execute([$status, $resolution_notes, $resolved_at, $id]);

            log_audit($pdo, 'INCIDENT_UPDATED', "Incident #{$id} updated to status: {$status}");

            echo json_encode(['success' => true, 'message' => 'Incident updated successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
