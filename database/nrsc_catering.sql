-- NRSC Catering System Database Schema
-- Created for National Remote Sensing Centre

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- Database creation
CREATE DATABASE IF NOT EXISTS `nrsc_catering` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nrsc_catering`;

-- --------------------------------------------------------
-- Table structure for users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `userid` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `phone` VARCHAR(15),
    `department` VARCHAR(100),
    `role` ENUM('employee', 'officer', 'canteen', 'admin') NOT NULL DEFAULT 'employee',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for menu_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(100) NOT NULL,
    `category` ENUM('breakfast', 'lunch', 'snacks', 'dinner', 'beverages') NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `description` TEXT,
    `is_available` TINYINT(1) DEFAULT 1,
    `image_url` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for catering_requests
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `catering_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_number` VARCHAR(20) NOT NULL UNIQUE,
    `employee_id` INT NOT NULL,
    `event_name` VARCHAR(200) NOT NULL,
    `event_date` DATE NOT NULL,
    `event_time` TIME NOT NULL,
    `venue` VARCHAR(200) NOT NULL,
    `guest_count` INT NOT NULL,
    `purpose` TEXT,
    `special_instructions` TEXT,
    `total_amount` DECIMAL(12,2) DEFAULT 0,
    `status` ENUM('pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `approving_officer_id` INT,
    `approved_at` TIMESTAMP NULL,
    `rejection_reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approving_officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for request_items (order details)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `request_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_id`) REFERENCES `catering_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for activity_log
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insert default admin user (password: admin123)
-- --------------------------------------------------------
INSERT INTO `users` (`userid`, `password`, `name`, `email`, `role`, `department`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@nrsc.gov.in', 'admin', 'IT Department'),
('officer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Approving Officer', 'officer@nrsc.gov.in', 'officer', 'Administration'),
('canteen1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Canteen Manager', 'canteen@nrsc.gov.in', 'canteen', 'Canteen Services'),
('emp001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Employee', 'employee@nrsc.gov.in', 'employee', 'Research Division');

-- --------------------------------------------------------
-- Insert sample menu items
-- --------------------------------------------------------
INSERT INTO `menu_items` (`item_name`, `category`, `price`, `description`, `is_available`) VALUES
-- Breakfast items
('Idli (2 pcs)', 'breakfast', 30.00, 'Soft steamed rice cakes served with sambar and chutney', 1),
('Dosa', 'breakfast', 40.00, 'Crispy rice crepe served with sambar and chutney', 1),
('Upma', 'breakfast', 35.00, 'Savory semolina porridge with vegetables', 1),
('Poori (3 pcs)', 'breakfast', 45.00, 'Deep-fried wheat bread served with potato curry', 1),
('Vada (2 pcs)', 'breakfast', 35.00, 'Crispy lentil fritters served with sambar and chutney', 1),

-- Lunch items
('Veg Thali', 'lunch', 120.00, 'Complete meal with rice, dal, sabzi, roti, salad, and dessert', 1),
('Non-Veg Thali', 'lunch', 180.00, 'Complete meal with rice, chicken curry, dal, roti, salad', 1),
('Veg Biryani', 'lunch', 100.00, 'Fragrant rice cooked with mixed vegetables and spices', 1),
('Chicken Biryani', 'lunch', 150.00, 'Aromatic rice cooked with tender chicken pieces', 1),
('Paneer Butter Masala', 'lunch', 140.00, 'Cottage cheese in rich tomato-butter gravy', 1),

-- Snacks
('Samosa (2 pcs)', 'snacks', 30.00, 'Crispy pastry filled with spiced potatoes', 1),
('Pakoda', 'snacks', 40.00, 'Mixed vegetable fritters served with chutney', 1),
('Sandwich', 'snacks', 50.00, 'Grilled vegetable sandwich', 1),
('Spring Roll (4 pcs)', 'snacks', 60.00, 'Crispy rolls filled with vegetables', 1),
('Cutlet (2 pcs)', 'snacks', 45.00, 'Spiced vegetable patties', 1),

-- Beverages
('Tea', 'beverages', 15.00, 'Hot Indian chai', 1),
('Coffee', 'beverages', 20.00, 'Filter coffee', 1),
('Fresh Lime Soda', 'beverages', 30.00, 'Refreshing lime soda (sweet/salt)', 1),
('Buttermilk', 'beverages', 25.00, 'Spiced yogurt drink', 1),
('Mango Lassi', 'beverages', 45.00, 'Sweet mango yogurt smoothie', 1),

-- Dinner items
('Chapati (4 pcs)', 'dinner', 40.00, 'Soft wheat flatbread', 1),
('Dal Tadka', 'dinner', 80.00, 'Yellow lentils tempered with spices', 1),
('Mixed Veg Curry', 'dinner', 90.00, 'Seasonal vegetables in gravy', 1),
('Jeera Rice', 'dinner', 60.00, 'Cumin-flavored basmati rice', 1),
('Raita', 'dinner', 40.00, 'Yogurt with cucumber and spices', 1);

COMMIT;
