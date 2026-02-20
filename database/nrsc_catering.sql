-- NRSC Catering & Meeting Management System Database Schema
-- Generated: 2026-02-20
-- Target Version: MySQL 8.0+ / MariaDB 10.4+
-- PHP Version: 8.1+ Compatible

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- --------------------------------------------------------
-- Database Creation
-- --------------------------------------------------------
DROP DATABASE IF EXISTS `nrsc_catering`;
CREATE DATABASE IF NOT EXISTS `nrsc_catering` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nrsc_catering`;

-- --------------------------------------------------------
-- 1. Users Table
-- --------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `userid` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Login ID',
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `designation` VARCHAR(100) DEFAULT NULL COMMENT 'Job Title',
    `department` VARCHAR(100) DEFAULT NULL,
    `role` ENUM('employee', 'officer', 'canteen', 'admin') NOT NULL DEFAULT 'employee',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Menu Items Table (Standard Catering Items)
-- --------------------------------------------------------
CREATE TABLE `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(100) NOT NULL,
    `category` ENUM('breakfast', 'lunch', 'snacks', 'dinner', 'beverages') NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `description` TEXT,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_availability` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Catering Requests Table (Meeting & Service Workflow)
-- --------------------------------------------------------
CREATE TABLE `catering_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique Reference Number',
    `employee_id` INT NOT NULL COMMENT 'Requester',
    
    -- Meeting Metadata
    `meeting_name` VARCHAR(200) NOT NULL,
    `meeting_date` DATE NOT NULL,
    `meeting_time` TIME NOT NULL,
    `meeting_area` VARCHAR(150) NOT NULL COMMENT 'General Area/Building',
    `venue` VARCHAR(200) NOT NULL COMMENT 'Specific Room/Venue',
    `lic` VARCHAR(100) DEFAULT NULL COMMENT 'Leader/Officer In Charge',
    
    -- Primary Service Information (Defaults for the request)
    `service_date` DATE NOT NULL,
    `service_time` TIME NOT NULL,
    `service_location` VARCHAR(200) DEFAULT NULL,
    `hall_code` VARCHAR(50) DEFAULT NULL,

    -- Financials
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    -- Workflow Status
    `status` ENUM('draft', 'pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    
    -- Approval Details
    `approving_officer_id` INT DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    FOREIGN KEY (`employee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approving_officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`meeting_date`, `service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Request Items Table (Line Items with Specific Service Details)
-- --------------------------------------------------------
CREATE TABLE `request_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    
    -- Item Details
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL COMMENT 'Snapshot of price at time of order',
    `subtotal` DECIMAL(12,2) NOT NULL GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,

    -- Item Specific Service Details (Overrides request defaults if needed)
    `service_date` DATE DEFAULT NULL,
    `service_time` TIME DEFAULT NULL,
    `service_location` VARCHAR(200) DEFAULT NULL,
    `hall_code` VARCHAR(50) DEFAULT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`request_id`) REFERENCES `catering_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Activity Log Table
-- --------------------------------------------------------
CREATE TABLE `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SAMPLE DATA INJECTION
-- --------------------------------------------------------

-- 1. Users
-- Password for all: 'password123' -> hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `users` (`userid`, `password`, `name`, `email`, `designation`, `department`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@nrsc.gov.in', 'IT Lead', 'IT Services', 'admin', 'active'),
('officer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajesh Kumar', 'officer@nrsc.gov.in', 'Senior Scientist', 'Remote Sensing', 'officer', 'active'),
('canteen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Canteen Manager', 'canteen@nrsc.gov.in', 'Manager', 'Hospitality', 'canteen', 'active'),
('emp01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Suresh Reddy', 'suresh@nrsc.gov.in', 'Technical Assistant', 'Ground Station', 'employee', 'active');

-- 2. Menu Items
INSERT INTO `menu_items` (`item_name`, `category`, `price`, `description`) VALUES
('Masala Tea', 'beverages', 15.00, 'Spiced Indian tea'),
('Coffee', 'beverages', 20.00, 'Freshly brewed coffee'),
('Veg Sandwich', 'snacks', 45.00, 'Cucumber and tomato sandwich with mint chutney'),
('Samosa (2pcs)', 'snacks', 30.00, 'Potato stuffed pastry'),
('Working Lunch (Veg)', 'lunch', 150.00, 'Rice, Dal, Curd, Sweet, 2 Roti, Curry'),
('Premium Lunch', 'lunch', 250.00, 'Paneer, Dal Makhani, Jeera Rice, Roti, Salad, Sweet');

COMMIT;
