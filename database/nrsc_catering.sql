-- NRSC Catering & Meeting Management System Database Schema
-- Compatible with XAMPP MariaDB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

DROP DATABASE IF EXISTS `nrsc_catering`;
CREATE DATABASE `nrsc_catering` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nrsc_catering`;

-- --------------------------------------------------------
-- 1. USERS TABLE
-- --------------------------------------------------------

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `userid` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(15) DEFAULT NULL,
    `designation` VARCHAR(100) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,

    `role` ENUM('employee','officer','canteen','admin') 
    NOT NULL DEFAULT 'employee',

    `status` ENUM('active','inactive') 
    NOT NULL DEFAULT 'active',

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX (`role`),
    INDEX (`status`)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2. MENU ITEMS TABLE
-- --------------------------------------------------------

CREATE TABLE `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(100) NOT NULL,

    `category` ENUM('breakfast','lunch','snacks','dinner','beverages') 
    NOT NULL,

    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `description` TEXT,
    `is_available` TINYINT(1) DEFAULT 1,
    `image_url` VARCHAR(255) DEFAULT NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX (`category`)
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3. CATERING REQUESTS
-- --------------------------------------------------------

CREATE TABLE `catering_requests` (

    `id` INT AUTO_INCREMENT PRIMARY KEY,

    `request_number` VARCHAR(50) NOT NULL UNIQUE,

    `employee_id` INT NOT NULL,

    `requesting_person` VARCHAR(100) NOT NULL,
    `requesting_department` VARCHAR(100),
    `requesting_designation` VARCHAR(100),
    `phone_number` VARCHAR(20),

    `meeting_name` VARCHAR(200) NOT NULL,
    `meeting_date` DATE NOT NULL,
    `meeting_time` TIME NOT NULL,

    `area` VARCHAR(150) NOT NULL,
    `lic` VARCHAR(100),

    `service_date` DATE NOT NULL,
    `service_time` TIME NOT NULL,
    `service_location` VARCHAR(200),
    `hall_code` VARCHAR(50),

    `approving_officer_id` INT DEFAULT NULL,
    `approving_by` VARCHAR(100),
    `approving_department` VARCHAR(100),

    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `rejection_reason` TEXT,
    `return_reason` TEXT,

    `total_amount` DECIMAL(12,2) DEFAULT 0.00,

    `status` ENUM(
        'new',
        'pending',
        'approved',
        'rejected',
        'in_progress',
        'completed',
        'cancelled',
        'returned'
    ) DEFAULT 'pending',

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`employee_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE,

    FOREIGN KEY (`approving_officer_id`)
    REFERENCES `users`(`id`)
    ON DELETE SET NULL

) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 4. REQUEST ITEMS
-- --------------------------------------------------------

CREATE TABLE `request_items` (

    `id` INT AUTO_INCREMENT PRIMARY KEY,

    `request_id` INT NOT NULL,
    `item_id` INT NOT NULL,

    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,

    -- removed generated column (MariaDB compatibility)
    `subtotal` DECIMAL(12,2) NOT NULL,

    `service_date` DATE DEFAULT NULL,
    `service_time` TIME DEFAULT NULL,
    `service_location` VARCHAR(200),
    `hall_code` VARCHAR(50),

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`request_id`)
    REFERENCES `catering_requests`(`id`)
    ON DELETE CASCADE,

    FOREIGN KEY (`item_id`)
    REFERENCES `menu_items`(`id`)
    ON DELETE RESTRICT

) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 5. ACTIVITY LOG
-- --------------------------------------------------------

CREATE TABLE `activity_log` (

    `id` INT AUTO_INCREMENT PRIMARY KEY,

    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45),

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE SET NULL

) ENGINE=InnoDB;

-- --------------------------------------------------------
-- SAMPLE USERS
-- --------------------------------------------------------

INSERT INTO `users`
(`userid`,`password`,`name`,`email`,`designation`,`department`,`role`,`status`)
VALUES
(
'admin',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'System Administrator',
'admin@nrsc.gov.in',
'IT Lead',
'IT Services',
'admin',
'active'
),

(
'officer',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'Rajesh Kumar',
'officer@nrsc.gov.in',
'Senior Scientist',
'Remote Sensing',
'officer',
'active'
),

(
'canteen',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'Canteen Manager',
'canteen@nrsc.gov.in',
'Manager',
'Hospitality',
'canteen',
'active'
),

(
'emp01',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'Suresh Reddy',
'suresh@nrsc.gov.in',
'Technical Assistant',
'Ground Station',
'employee',
'active'
);

-- --------------------------------------------------------
-- SAMPLE MENU ITEMS
-- --------------------------------------------------------

INSERT INTO `menu_items`
(`item_name`,`category`,`price`,`description`)
VALUES

('Masala Tea','beverages',15,'Spiced Indian tea'),

('Coffee','beverages',20,'Freshly brewed coffee'),

('Veg Sandwich','snacks',45,'Cucumber tomato sandwich'),

('Samosa (2pcs)','snacks',30,'Potato stuffed pastry'),

('Working Lunch (Veg)','lunch',150,'Rice Dal Curd Sweet'),

('Premium Lunch','lunch',250,'Paneer Dal Makhani Jeera Rice');


-- --------------------------------------------------------
-- 6. VIMIS EMPLOYEE TABLE (Master Data)
-- --------------------------------------------------------

CREATE TABLE `VIMIS_EMPLOYEE` (
    `EMPLOYEECODE` VARCHAR(20) PRIMARY KEY,
    `EMPLOYEENAME` VARCHAR(100) NOT NULL,
    `SALUTATIONCODE` VARCHAR(10),
    `SERVICESTATCODE` ENUM('SERV', 'PROB') NOT NULL,
    `WORKINGCITYNAME` VARCHAR(100),
    `WORKSITE` VARCHAR(100),
    `SEX` VARCHAR(10),
    `SUPERANNUATNDT` DATE,
    `JOINGOVTDATE` DATE,
    `DESGCODE` VARCHAR(20),
    `DESGFULLNAME` VARCHAR(100),
    `OFFICERTYPE` VARCHAR(50),
    `DIVNCODE` VARCHAR(20),
    `DIVNFULLNAME` VARCHAR(100),
    `GROUPCODE` VARCHAR(20),
    `GROUPSHORTNAME` VARCHAR(50),
    `ENTITYCODE` VARCHAR(20),
    `ENTITYSHORTNAME` VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 7. EMPLOYEE VS REPORTING OFFICER MAPPING
-- --------------------------------------------------------

CREATE TABLE `TBAD_EMPVSREPEMPPLOYEE` (
    `EMPLOYEECODE` VARCHAR(20) PRIMARY KEY,
    `REPEMPLOYEECODE` VARCHAR(20) NOT NULL,
    FOREIGN KEY (`EMPLOYEECODE`) REFERENCES `VIMIS_EMPLOYEE`(`EMPLOYEECODE`) ON DELETE CASCADE,
    FOREIGN KEY (`REPEMPLOYEECODE`) REFERENCES `VIMIS_EMPLOYEE`(`EMPLOYEECODE`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- SAMPLE VIMIS EMPLOYEES
-- --------------------------------------------------------

INSERT INTO `VIMIS_EMPLOYEE` 
(`EMPLOYEECODE`, `EMPLOYEENAME`, `SALUTATIONCODE`, `SERVICESTATCODE`, `WORKINGCITYNAME`, `WORKSITE`, `SEX`, `SUPERANNUATNDT`, `JOINGOVTDATE`, `DESGCODE`, `DESGFULLNAME`, `OFFICERTYPE`, `DIVNCODE`, `DIVNFULLNAME`, `GROUPCODE`, `GROUPSHORTNAME`, `ENTITYCODE`, `ENTITYSHORTNAME`) 
VALUES 
('NR01234', 'Dr. Anil Kumar', 'Dr.', 'SERV', 'Hyderabad', 'Jeedimetla', 'Male', '2040-05-31', '2010-06-15', 'SCI-E', 'Scientist/Engineer-E', 'Gazetted', 'RSAM', 'Remote Sensing Applications', 'ER-C', 'Earth Resources', 'NRSC', 'National Remote Sensing Centre'),
('NR04567', 'Ms. Sunita Sharma', 'Ms.', 'PROB', 'Hyderabad', 'Balanagar', 'Female', '2045-12-31', '2023-01-10', 'TA-B', 'Technical Assistant-B', 'Non-Gazetted', 'GSG', 'Ground Station Group', 'DP', 'Data Processing', 'NRSC', 'National Remote Sensing Centre'),
('NR07890', 'Mr. Rajesh Varma', 'Mr.', 'SERV', 'Hyderabad', 'Shadnagar', 'Male', '2038-08-31', '2005-09-01', 'AO', 'Administrative Officer', 'Gazetted', 'ADM', 'Administration', 'PERS', 'Personnel & Administration', 'NRSC', 'National Remote Sensing Centre');


-- --------------------------------------------------------
-- SAMPLE REPORTING HIERARCHY
-- --------------------------------------------------------

INSERT INTO `TBAD_EMPVSREPEMPPLOYEE` (`EMPLOYEECODE`, `REPEMPLOYEECODE`) VALUES ('NR01234', 'NR04567');

COMMIT;


