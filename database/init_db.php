<?php
// database/init_db.php - Bootstraps database creation, schema, and seeding.
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host={$DB_HOST}", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected to MySQL host.\n";
} catch (PDOException $e) {
    exit("MySQL Connection failed: " . $e->getMessage() . "\n");
}

$sql = file_get_contents(__DIR__ . '/schema.sql');
try {
    // Run the schema.sql content (creates DB, tables, and seeds roles)
    $pdo->exec($sql);
    echo "Database schema and roles initialized successfully.\n";
} catch (Exception $e) {
    exit("Failed to execute schema: " . $e->getMessage() . "\n");
}

// Include and execute seed_users.php to register the admin and drivers
require __DIR__ . '/seed_users.php';
