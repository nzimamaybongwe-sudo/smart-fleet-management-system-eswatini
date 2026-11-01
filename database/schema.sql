-- database schema for Logico Fleet (core & fleet tables)
CREATE DATABASE IF NOT EXISTS `logico_fleet` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `logico_fleet`;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(36) DEFAULT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  phone VARCHAR(50) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive','locked') DEFAULT 'active',
  last_login_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  role_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT,
  ip VARCHAR(45) DEFAULT NULL,
  meta JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed roles for the initial environment
INSERT IGNORE INTO roles (name, description) VALUES
('admin', 'System administrator with full access'),
('fleet_manager', 'Fleet manager with operational access'),
('driver', 'Driver account');

-- =========================================================================
-- FLEET MANAGEMENT TABLES
-- =========================================================================

-- Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  license_plate VARCHAR(20) NOT NULL UNIQUE,
  make VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  year INT NOT NULL,
  type ENUM('truck', 'van', 'car', 'trailer') DEFAULT 'truck',
  status ENUM('active', 'maintenance', 'out_of_service') DEFAULT 'active',
  odometer INT DEFAULT 0,
  fuel_level INT DEFAULT 100, -- percentage
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Drivers Table (linked to users table)
CREATE TABLE IF NOT EXISTS drivers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  license_number VARCHAR(50) NOT NULL UNIQUE,
  license_expiry DATE NOT NULL,
  status ENUM('active', 'inactive', 'on_trip') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Trips Table
CREATE TABLE IF NOT EXISTS trips (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  driver_id INT NOT NULL,
  origin VARCHAR(255) NOT NULL,
  destination VARCHAR(255) NOT NULL,
  status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
  start_time DATETIME NULL,
  end_time DATETIME NULL,
  start_odometer INT DEFAULT NULL,
  end_odometer INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
  FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE RESTRICT
);

-- Maintenance Logs Table
CREATE TABLE IF NOT EXISTS maintenance_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  service_date DATE NOT NULL,
  service_type VARCHAR(100) NOT NULL,
  description TEXT,
  cost DECIMAL(10, 2) NOT NULL,
  odometer_reading INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Fuel Logs Table
CREATE TABLE IF NOT EXISTS fuel_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  driver_id INT NOT NULL,
  refuel_date DATE NOT NULL,
  liters DECIMAL(8, 2) NOT NULL,
  cost DECIMAL(10, 2) NOT NULL,
  odometer_reading INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
);

-- Seed initial vehicles
INSERT IGNORE INTO vehicles (license_plate, make, model, year, type, status, odometer, fuel_level) VALUES
('LV-201-TR', 'Volvo', 'FH16 Heavy Truck', 2022, 'truck', 'active', 124500, 85),
('LV-304-VN', 'Ford', 'Transit Cargo Van', 2020, 'van', 'active', 89200, 62),
('LV-409-CR', 'Toyota', 'Prius Hybrid', 2021, 'car', 'maintenance', 45000, 45),
('LV-512-TR', 'Scania', 'R500 Hauler', 2019, 'truck', 'active', 312000, 90),
('LV-108-CR', 'Tesla', 'Model Y Cargo', 2023, 'car', 'out_of_service', 15400, 12);
