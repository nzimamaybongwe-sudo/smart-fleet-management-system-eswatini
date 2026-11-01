<?php
// api/trips.php - manages trip schedules, dispatches, e-POD, and status updates
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
            // Check if filtering for the currently logged in driver
            $myTrips = isset($_GET['my_trips']) || (isset($_SESSION['roles']) && in_array('driver', $_SESSION['roles']) && !in_array('admin', $_SESSION['roles']));

            if ($myTrips) {
                // Fetch driver record ID
                $stmtDrv = $pdo->prepare('SELECT id FROM drivers WHERE user_id = ? LIMIT 1');
                $stmtDrv->execute([$_SESSION['user_id']]);
                $driverId = (int)$stmtDrv->fetchColumn();

                if (!$driverId) {
                    echo json_encode(['active_trip' => null, 'past_trips' => []]);
                    exit;
                }

                // Active or scheduled trip
                $stmtActive = $pdo->prepare('SELECT t.*, v.license_plate, v.make, v.model, v.type as vehicle_type, v.fuel_level, v.cold_chain_capable, v.temp_celsius, v.odometer as current_vehicle_odo, u.name as driver_name 
                                             FROM trips t 
                                             JOIN vehicles v ON t.vehicle_id = v.id 
                                             JOIN drivers d ON t.driver_id = d.id 
                                             JOIN users u ON d.user_id = u.id 
                                             WHERE t.driver_id = ? AND (t.status = "in_progress" OR t.status = "scheduled") 
                                             ORDER BY (t.status = "in_progress") DESC, t.id DESC LIMIT 1');
                $stmtActive->execute([$driverId]);
                $activeTrip = $stmtActive->fetch();

                // Past trips
                $stmtPast = $pdo->prepare('SELECT t.*, v.license_plate, v.make, v.model 
                                           FROM trips t 
                                           JOIN vehicles v ON t.vehicle_id = v.id 
                                           WHERE t.driver_id = ? AND (t.status = "completed" OR t.status = "cancelled") 
                                           ORDER BY t.id DESC LIMIT 10');
                $stmtPast->execute([$driverId]);
                $pastTrips = $stmtPast->fetchAll();

                echo json_encode([
                    'driver_id' => $driverId,
                    'active_trip' => $activeTrip ?: null,
                    'past_trips' => $pastTrips
                ]);
                exit;
            }

            // Otherwise, fetch all trips for dispatcher / admin
            $stmt = $pdo->query('SELECT t.*, v.license_plate, v.make, v.model, v.cold_chain_capable, v.temp_celsius, u.name as driver_name 
                                 FROM trips t 
                                 JOIN vehicles v ON t.vehicle_id = v.id 
                                 JOIN drivers d ON t.driver_id = d.id 
                                 JOIN users u ON d.user_id = u.id 
                                 ORDER BY t.id DESC');
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            
            $vehicle_id = (int)($data['vehicle_id'] ?? 0);
            $driver_id = (int)($data['driver_id'] ?? 0);
            $origin = trim($data['origin'] ?? 'Logico Central DC, Matsapha');
            $destination = trim($data['destination'] ?? '');
            $status = $data['status'] ?? 'scheduled';
            $consignment_no = trim($data['consignment_no'] ?? '') ?: ('LOG-ESW-' . rand(10000, 99999));
            $cargo_type = trim($data['cargo_type'] ?? 'General FMCG Goods');
            $client_name = trim($data['client_name'] ?? 'Shoprite / Spar Retail Partner');

            if ($vehicle_id <= 0 || $driver_id <= 0 || empty($origin) || empty($destination)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or missing fields']);
                exit;
            }

            // Fetch vehicle status
            $s_veh = $pdo->prepare('SELECT status, odometer, cold_chain_capable, temp_celsius FROM vehicles WHERE id = ?');
            $s_veh->execute([$vehicle_id]);
            $veh = $s_veh->fetch();
            if (!$veh) {
                http_response_code(404);
                echo json_encode(['error' => 'Vehicle not found']);
                exit;
            }
            if ($veh['status'] !== 'active') {
                http_response_code(400);
                echo json_encode(['error' => "Vehicle is currently {$veh['status']} and cannot be dispatched."]);
                exit;
            }

            // Fetch driver status
            $s_drv = $pdo->prepare('SELECT status, user_id FROM drivers WHERE id = ?');
            $s_drv->execute([$driver_id]);
            $drv = $s_drv->fetch();
            if (!$drv) {
                http_response_code(404);
                echo json_encode(['error' => 'Driver not found']);
                exit;
            }
            if ($drv['status'] !== 'active') {
                http_response_code(400);
                echo json_encode(['error' => "Driver is currently {$drv['status']} and cannot be assigned."]);
                exit;
            }

            $pdo->beginTransaction();

            $startTime = null;
            $startOdo = null;
            if ($status === 'in_progress') {
                $startTime = date('Y-m-d H:i:s');
                $startOdo = (int)$veh['odometer'];

                // Mark driver as on trip
                $u_drv = $pdo->prepare("UPDATE drivers SET status = 'on_trip' WHERE id = ?");
                $u_drv->execute([$driver_id]);
            }

            $stmt = $pdo->prepare('INSERT INTO trips (vehicle_id, driver_id, origin, destination, status, start_time, start_odometer, consignment_no, cargo_type, client_name, temperature_status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "compliant")');
            $stmt->execute([$vehicle_id, $driver_id, $origin, $destination, $status, $startTime, $startOdo, $consignment_no, $cargo_type, $client_name]);
            $tripId = $pdo->lastInsertId();

            $pdo->commit();

            log_audit($pdo, 'DISPATCH_TRIP', "Dispatched trip ID {$tripId} ({$consignment_no}) from {$origin} to {$destination} for {$client_name}");

            echo json_encode(['success' => true, 'id' => $tripId, 'consignment_no' => $consignment_no]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            $status = $data['status'] ?? '';
            
            if ($id <= 0 || empty($status)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing fields']);
                exit;
            }

            // Fetch current trip status
            $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = ?');
            $stmt->execute([$id]);
            $trip = $stmt->fetch();
            if (!$trip) {
                http_response_code(404);
                echo json_encode(['error' => 'Trip not found']);
                exit;
            }

            $pdo->beginTransaction();

            if ($status === 'in_progress') {
                $s_veh = $pdo->prepare('SELECT status, odometer FROM vehicles WHERE id = ?');
                $s_veh->execute([$trip['vehicle_id']]);
                $veh = $s_veh->fetch();

                $startTime = date('Y-m-d H:i:s');
                $startOdo = (int)($veh['odometer'] ?? $trip['start_odometer'] ?? 0);

                $stmt = $pdo->prepare("UPDATE trips SET status = 'in_progress', start_time = ?, start_odometer = ? WHERE id = ?");
                $stmt->execute([$startTime, $startOdo, $id]);

                $u_drv = $pdo->prepare("UPDATE drivers SET status = 'on_trip' WHERE id = ?");
                $u_drv->execute([$trip['driver_id']]);

                $pdo->commit();
                log_audit($pdo, 'TRIP_IN_PROGRESS', "Trip ID {$id} started by driver.");
                echo json_encode(['success' => true, 'message' => 'Trip started']);
                exit;

            } elseif ($status === 'completed') {
                $endOdo = (int)($data['end_odometer'] ?? 0);
                $endFuel = (int)($data['end_fuel_level'] ?? 85);
                $podSignature = $data['pod_signature'] ?? null;
                $podRecipient = trim($data['pod_recipient'] ?? 'Store Manager');
                $podNotes = trim($data['pod_notes'] ?? 'Goods received in good order. Cold chain seal verified.');

                // Fallback odometer check if not passed
                if ($endOdo <= 0) {
                    $endOdo = (int)$trip['start_odometer'] + rand(25, 120);
                }

                $endTime = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE trips SET status = 'completed', end_time = ?, end_odometer = ?, pod_signature = ?, pod_recipient = ?, pod_timestamp = NOW(), pod_notes = ? WHERE id = ?");
                $stmt->execute([$endTime, $endOdo, $podSignature, $podRecipient, $podNotes, $id]);

                // Update vehicle odometer and fuel
                $stmtVeh = $pdo->prepare('UPDATE vehicles SET odometer = ?, fuel_level = ? WHERE id = ?');
                $stmtVeh->execute([$endOdo, $endFuel, $trip['vehicle_id']]);

                // Free the driver
                $u_drv = $pdo->prepare("UPDATE drivers SET status = 'active' WHERE id = ?");
                $u_drv->execute([$trip['driver_id']]);

                $pdo->commit();
                log_audit($pdo, 'TRIP_COMPLETED', "Trip ID {$id} ({$trip['consignment_no']}) completed with digital e-POD signature signed by {$podRecipient}.");
                echo json_encode(['success' => true, 'message' => 'Trip marked completed with digital e-POD']);
                exit;

            } elseif ($status === 'cancelled') {
                $endTime = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE trips SET status = 'cancelled', end_time = ? WHERE id = ?");
                $stmt->execute([$endTime, $id]);

                // Free the driver
                $u_drv = $pdo->prepare("UPDATE drivers SET status = 'active' WHERE id = ?");
                $u_drv->execute([$trip['driver_id']]);

                $pdo->commit();
                log_audit($pdo, 'TRIP_CANCELLED', "Trip ID {$id} cancelled.");
                echo json_encode(['success' => true, 'message' => 'Trip cancelled']);
                exit;
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
