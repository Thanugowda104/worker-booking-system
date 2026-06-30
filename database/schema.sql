-- ============================================
-- WORKER-BOOKING-SYSTEM DATABASE SCHEMA
-- ============================================

CREATE DATABASE IF NOT EXISTS WBS
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE WBS;

-- Users table (all roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','worker','admin') NOT NULL DEFAULT 'customer',
    status ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    phone VARCHAR(20),
    address TEXT,
    payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Worker profiles
CREATE TABLE IF NOT EXISTS worker_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    bio TEXT,
    hourly_rate DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    experience_years INT NOT NULL DEFAULT 0,
    avg_rating DECIMAL(3,2) DEFAULT 0.00,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    languages VARCHAR(255),
    availability_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Service categories
CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Availability slots
CREATE TABLE IF NOT EXISTS availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    available_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_booked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slot (worker_id, available_date, start_time)
);

-- Bookings
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    worker_id INT NOT NULL,
    category_id INT,
    booking_type ENUM('slot','request') NOT NULL DEFAULT 'request',
    status ENUM('pending','accepted','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
    scheduled_date DATE,
    scheduled_start TIME,
    scheduled_end TIME,
    estimated_hours DECIMAL(4,2),
    total_amount DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE SET NULL
);

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    worker_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password resets
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments / Receipts (worker registration)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    payment_method ENUM('online','cash') NOT NULL,
    payment_status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(100),
    receipt_number VARCHAR(50) NOT NULL UNIQUE,
    paid_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Booking Payments (customer pays worker after job completion)
CREATE TABLE IF NOT EXISTS booking_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    worker_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('online','cash') NOT NULL,
    payment_status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(100),
    receipt_number VARCHAR(50) NOT NULL UNIQUE,
    paid_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed demo admin user (password: admin123)
INSERT INTO users (name, email, password, role, email_verified, payment_status, status) VALUES
('System Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 'pending', 'active')
ON DUPLICATE KEY UPDATE email = email;

-- Seed demo worker user (password: worker123, already paid and verified)
INSERT IGNORE INTO users (name, email, password, role, email_verified, payment_status, status) VALUES
('Ramesh Kumar', 'worker@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', 1, 'paid', 'active');

-- Seed demo customer (password: customer123)
INSERT IGNORE INTO users (name, email, password, role, email_verified, payment_status, status) VALUES
('Test Customer', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1, 'pending', 'active');

-- Seed worker profile
INSERT IGNORE INTO worker_profiles (user_id, bio, hourly_rate, experience_years, avg_rating, is_verified) VALUES
(2, 'Experienced home service professional with 5+ years in plumbing and electrical work.', 300.00, 5, 4.50, 1)
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Seed demo availability slots for worker (next 7 days)
INSERT INTO availability (worker_id, available_date, start_time, end_time, is_booked) VALUES
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '12:00:00', 0),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', '17:00:00', 0),
(2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', '12:00:00', 0),
(2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', '13:00:00', 0),
(2, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:00:00', '17:00:00', 0),
(2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', '12:00:00', 0),
(2, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '14:00:00', '18:00:00', 0)
ON DUPLICATE KEY UPDATE worker_id = worker_id;

-- Seed service categories
INSERT INTO service_categories (name, description, icon) VALUES
('House Cleaning', 'Deep cleaning, regular chores, and sanitization', 'bi-basket3'),
('Plumbing', 'Pipe repair, installation, and maintenance', 'bi-wrench'),
('Electrical', 'Wiring, repairs, and electrical installations', 'bi-lightning-charge'),
('Gardening', 'Lawn care, plant maintenance, and landscaping', 'bi-flower1'),
('Painting', 'Interior and exterior painting services', 'bi-brush'),
('Carpentry', 'Furniture, fittings, and woodwork', 'bi-hammer'),
('AC / Appliance Repair', 'Air conditioning and home appliance servicing', 'bi-tv'),
('Moving / Packing', 'Packing, loading, and moving services', 'bi-truck')
ON DUPLICATE KEY UPDATE name = name;
