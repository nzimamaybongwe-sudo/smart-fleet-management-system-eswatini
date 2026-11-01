<?php
// Run this script from CLI (php seed_users.php) to create initial users with secure password hashes.
require __DIR__ . '/../config/database.php';
$pdo = $pdo; // $pdo returned by database.php

// Admin details
$adminEmail = 'admin@logico.test';
$adminName = 'Logico Admin';
$adminPassword = 'Admin@123';

// Drivers details
$drivers = [
    [
        'name' => 'David Miller',
        'email' => 'driver1@logico.test',
        'password' => 'Driver@123',
        'license' => 'DL-982314-A',
        'expiry' => '2028-12-31'
    ],
    [
        'name' => 'Sarah Connor',
        'email' => 'driver2@logico.test',
        'password' => 'Driver@123',
        'license' => 'DL-456123-B',
        'expiry' => '2029-06-30'
    ],
    [
        'name' => 'James Carter',
        'email' => 'driver3@logico.test',
        'password' => 'Driver@123',
        'license' => 'DL-789012-C',
        'expiry' => '2027-09-15'
    ]
];

try {
    $pdo->beginTransaction();

    // Ensure roles exist
    $roles = ['admin','fleet_manager','driver'];
    $roleIds = [];
    foreach ($roles as $r) {
        $s = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
        $s->execute([$r]);
        $row = $s->fetch();
        if ($row) { 
            $roleIds[$r] = $row['id']; 
        }
    }

    // Insert admin user
    $s_check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $s_check->execute([$adminEmail]);
    if (!$s_check->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email, password_hash, status) VALUES (UUID(), ?, ?, ?, ?)');
        $stmt->execute([$adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), 'active']);
        $adminId = $pdo->lastInsertId();

        if (isset($roleIds['admin'])) {
            $ir = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            $ir->execute([$adminId, $roleIds['admin']]);
        }
        echo "Admin user created: {$adminEmail}\n";
    } else {
        echo "Admin user already exists.\n";
    }

    // Insert drivers
    foreach ($drivers as $d) {
        $s_check->execute([$d['email']]);
        if (!$s_check->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO users (uuid, name, email, password_hash, status) VALUES (UUID(), ?, ?, ?, ?)');
            $stmt->execute([$d['name'], $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), 'active']);
            $userId = $pdo->lastInsertId();

            // Link to driver role
            if (isset($roleIds['driver'])) {
                $ir = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
                $ir->execute([$userId, $roleIds['driver']]);
            }

            // Create driver profile
            $idr = $pdo->prepare('INSERT INTO drivers (user_id, license_number, license_expiry, status) VALUES (?, ?, ?, ?)');
            $idr->execute([$userId, $d['license'], $d['expiry'], 'active']);

            echo "Driver created: {$d['email']} (License: {$d['license']})\n";
        } else {
            echo "Driver {$d['email']} already exists.\n";
        }
    }

    $pdo->commit();
    echo "Database seeding completed successfully.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Failed to seed users: " . $e->getMessage() . "\n";
}
