<?php
// database/upgrade_enterprise.php
// Enterprise upgrade migration for Logico Fleet Management System

require_once __DIR__ . '/../config/database.php';
$pdo = $pdo; // from database.php

echo "=== Starting Logico Fleet Enterprise Migration ===\n";

try {
    // Convert core tables to InnoDB to support transactions and foreign keys
    $tablesToConvert = ['users', 'roles', 'user_roles', 'vehicles', 'drivers', 'trips', 'maintenance_logs', 'fuel_logs', 'audit_logs'];
    foreach ($tablesToConvert as $t) {
        try {
            $pdo->exec("ALTER TABLE `{$t}` ENGINE=InnoDB");
        } catch (Exception $e) {
            // ignore if table doesn't exist yet
        }
    }

    // Helper to safely add column if missing
    function addColumnIfNotExists($pdo, $table, $column, $definition) {
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            echo "  [+] Added column {$column} to {$table}\n";
        } else {
            echo "  [.] Column {$column} already exists in {$table}\n";
        }
    }

    // 1. Upgrade `vehicles` table
    echo "1. Upgrading vehicles table...\n";
    addColumnIfNotExists($pdo, 'vehicles', 'cold_chain_capable', "TINYINT(1) NOT NULL DEFAULT 0");
    addColumnIfNotExists($pdo, 'vehicles', 'temp_celsius', "DECIMAL(4,1) DEFAULT NULL");
    addColumnIfNotExists($pdo, 'vehicles', 'depot_location', "VARCHAR(100) DEFAULT 'Matsapha DC'");
    addColumnIfNotExists($pdo, 'vehicles', 'last_service_odo', "INT DEFAULT 0");
    addColumnIfNotExists($pdo, 'vehicles', 'next_service_odo', "INT DEFAULT 15000");
    addColumnIfNotExists($pdo, 'vehicles', 'latitude', "DECIMAL(10,6) DEFAULT -26.5167");
    addColumnIfNotExists($pdo, 'vehicles', 'longitude', "DECIMAL(10,6) DEFAULT 31.3167");

    // 2. Upgrade `trips` table
    echo "2. Upgrading trips table...\n";
    addColumnIfNotExists($pdo, 'trips', 'consignment_no', "VARCHAR(50) DEFAULT NULL");
    addColumnIfNotExists($pdo, 'trips', 'cargo_type', "VARCHAR(100) DEFAULT 'General FMCG'");
    addColumnIfNotExists($pdo, 'trips', 'client_name', "VARCHAR(100) DEFAULT 'Shoprite Matsapha'");
    addColumnIfNotExists($pdo, 'trips', 'pod_signature', "LONGTEXT DEFAULT NULL");
    addColumnIfNotExists($pdo, 'trips', 'pod_recipient', "VARCHAR(100) DEFAULT NULL");
    addColumnIfNotExists($pdo, 'trips', 'pod_timestamp', "DATETIME DEFAULT NULL");
    addColumnIfNotExists($pdo, 'trips', 'pod_notes', "TEXT DEFAULT NULL");
    addColumnIfNotExists($pdo, 'trips', 'temperature_status', "ENUM('compliant','warning','breached') DEFAULT 'compliant'");

    // 3. Create `incidents` table
    echo "3. Creating incidents table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS incidents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trip_id INT NULL,
        vehicle_id INT NOT NULL,
        driver_id INT NOT NULL,
        incident_type ENUM('breakdown', 'puncture', 'accident', 'cold_chain_breach', 'delay', 'other') DEFAULT 'breakdown',
        severity ENUM('low', 'medium', 'critical') DEFAULT 'medium',
        location VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('reported', 'dispatched', 'resolved') DEFAULT 'reported',
        resolution_notes TEXT NULL,
        resolved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "  [+] incidents table verified.\n";

    // 4. Create `inspections` table
    echo "4. Creating inspections table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS inspections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT NOT NULL,
        driver_id INT NOT NULL,
        odometer_reading INT NOT NULL,
        tires_passed TINYINT(1) DEFAULT 1,
        brakes_passed TINYINT(1) DEFAULT 1,
        lights_passed TINYINT(1) DEFAULT 1,
        fluids_passed TINYINT(1) DEFAULT 1,
        reefer_temp_checked TINYINT(1) DEFAULT 1,
        recorded_temp DECIMAL(4,1) DEFAULT NULL,
        status ENUM('passed', 'flagged_for_maintenance') DEFAULT 'passed',
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "  [+] inspections table verified.\n";

    // 5. Seed Logico Fleet Eswatini Vehicles
    echo "5. Seeding Logico Eswatini Fleet Vehicles...\n";
    $seedVehicles = [
        ['L-101-SD', 'Scania', 'R450 24T Tautliner', 2023, 'truck', 'active', 142300, 85, 0, null, 'Matsapha DC', 135000, 150000, -26.5167, 31.3167],
        ['L-204-SD', 'Volvo', 'FH16 14T Cold-Reefer', 2024, 'truck', 'active', 68400, 92, 1, 3.8, 'Mbabane Depot', 60000, 75000, -26.3167, 31.1333],
        ['L-305-SD', 'Isuzu', 'FTR 850 8T Reefer', 2022, 'truck', 'active', 95200, 68, 1, -18.2, 'Manzini Hub', 90000, 105000, -26.4833, 31.3667],
        ['L-409-SD', 'Mercedes-Benz', 'Actros 2645 Long-Haul', 2021, 'truck', 'maintenance', 215000, 40, 0, null, 'Matsapha Workshop', 200000, 215000, -26.5200, 31.3100],
        ['L-512-SD', 'Toyota', 'Hilux 2.8 GD-6 Supervisor', 2023, 'car', 'active', 41200, 78, 0, null, 'Matsapha DC', 40000, 50000, -26.5150, 31.3200],
        ['L-618-SD', 'UD Trucks', 'Croner MKE 12T Distribution', 2023, 'truck', 'active', 54100, 60, 1, 4.1, 'Nhlangano Route', 45000, 60000, -27.1167, 31.2000],
    ];

    $s_ins_v = $pdo->prepare("INSERT INTO vehicles (license_plate, make, model, year, type, status, odometer, fuel_level, cold_chain_capable, temp_celsius, depot_location, last_service_odo, next_service_odo, latitude, longitude) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            make=VALUES(make), model=VALUES(model), year=VALUES(year), type=VALUES(type), status=VALUES(status), 
            odometer=VALUES(odometer), fuel_level=VALUES(fuel_level), cold_chain_capable=VALUES(cold_chain_capable), 
            temp_celsius=VALUES(temp_celsius), depot_location=VALUES(depot_location), last_service_odo=VALUES(last_service_odo), 
            next_service_odo=VALUES(next_service_odo), latitude=VALUES(latitude), longitude=VALUES(longitude)");

    foreach ($seedVehicles as $v) {
        $s_ins_v->execute($v);
    }
    echo "  [+] Logico Eswatini fleet vehicles seeded/updated.\n";

    // 6. Ensure Driver 1 has an active FMCG trip assigned
    $stmtDriver = $pdo->query("SELECT d.id FROM drivers d JOIN users u ON d.user_id = u.id WHERE u.email = 'driver1@logico.test' LIMIT 1");
    $driver1Id = $stmtDriver->fetchColumn();

    $stmtVeh = $pdo->query("SELECT id FROM vehicles WHERE license_plate = 'L-204-SD' LIMIT 1");
    $vehId = $stmtVeh->fetchColumn();

    if ($driver1Id && $vehId) {
        // Check if active trip exists
        $stmtCheckTrip = $pdo->prepare("SELECT id FROM trips WHERE driver_id = ? AND status = 'in_progress' LIMIT 1");
        $stmtCheckTrip->execute([$driver1Id]);
        if (!$stmtCheckTrip->fetch()) {
            $stmtNewTrip = $pdo->prepare("INSERT INTO trips (vehicle_id, driver_id, origin, destination, status, start_time, start_odometer, consignment_no, cargo_type, client_name, temperature_status) 
                VALUES (?, ?, 'Logico Central DC, Matsapha', 'Shoprite Hyper, Mbabane', 'in_progress', NOW(), 68350, 'LOG-ESW-84920', 'Chilled Dairy & Cold Deli (Lactalis)', 'Shoprite Mbabane Store #12', 'compliant')");
            $stmtNewTrip->execute([$vehId, $driver1Id]);
            $pdo->query("UPDATE drivers SET status = 'on_trip' WHERE id = {$driver1Id}");
            echo "  [+] Seeded active in-progress trip for Driver 1.\n";
        }
    }

    // 7. Seed sample inspection
    if ($driver1Id && $vehId) {
        $pdo->prepare("INSERT INTO inspections (vehicle_id, driver_id, odometer_reading, tires_passed, brakes_passed, lights_passed, fluids_passed, reefer_temp_checked, recorded_temp, status, notes) 
            VALUES (?, ?, 68350, 1, 1, 1, 1, 1, 3.8, 'passed', 'Pre-departure check complete at Matsapha DC. Cold chain sealed at 3.8°C.')")->execute([$vehId, $driver1Id]);
        echo "  [+] Seeded initial pre-trip inspection.\n";
    }

    // 8. Seed sample incident for realistic dispatcher management
    $stmtIncCount = $pdo->query("SELECT COUNT(*) FROM incidents");
    if ((int)$stmtIncCount->fetchColumn() === 0 && $driver1Id && $vehId) {
        $pdo->prepare("INSERT INTO incidents (vehicle_id, driver_id, incident_type, severity, location, description, status) 
            VALUES (?, ?, 'puncture', 'medium', 'MR3 Highway near Malkerns Turnoff', 'Left rear trailer tire pressure drop. Driver safely parked on shoulder waiting for mobile tire response.', 'reported')")
            ->execute([$vehId, $driver1Id]);
        echo "  [+] Seeded sample incident report.\n";
    }

    echo "=== Migration Complete: Enterprise Logico System Ready ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
