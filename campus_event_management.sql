-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2025 at 05:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campus_event_management`
--

DROP DATABASE IF EXISTS `campus_event_management`;
CREATE DATABASE IF NOT EXISTS `campus_event_management`;
USE `campus_event_management`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `department` VARCHAR(100) NULL,
    `position` VARCHAR(100) NULL,
    `student_number` VARCHAR(20) NULL,
    `phone` VARCHAR(20) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_department` (`department`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_roles`
--

CREATE TABLE IF NOT EXISTS `user_roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `assigned_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `activity_logs`
--

CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `departments`
--
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `venues`
--
CREATE TABLE IF NOT EXISTS `venues` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `location` VARCHAR(255) NULL,
    `capacity` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `venue_id` INT NOT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    `created_by` INT NOT NULL,
    `approved_by` INT NULL,
    `max_participants` INT NULL,
    `budget` DECIMAL(10, 2) NULL,
    `event_access_level` ENUM('department_only', 'school_wide') NOT NULL DEFAULT 'school_wide',
    `department_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`),
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    INDEX `idx_access_level` (`event_access_level`),
    INDEX `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `event_participants`
--

CREATE TABLE IF NOT EXISTS `event_participants` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `status` ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_event_participant` (`event_id`, `user_id`),
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `event_attachments`
--

CREATE TABLE IF NOT EXISTS `event_attachments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `file_size` INT NOT NULL,
    `uploaded_by` INT NOT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_event` (`event_id`),
    INDEX `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `event_comments`
--

CREATE TABLE IF NOT EXISTS `event_comments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `event_ideas`
--
CREATE TABLE IF NOT EXISTS `event_ideas` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `submitted_by` INT NOT NULL,
    `status` ENUM('new', 'under_review', 'approved', 'rejected') DEFAULT 'new',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `event_staff`
--
CREATE TABLE IF NOT EXISTS `event_staff` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `assigned_by` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_event_staff` (`event_id`, `user_id`),
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `notifications`
--
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `event_id` INT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    INDEX `idx_user_is_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `announcements`
--
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `user_id` INT NOT NULL,
    `start_date` DATETIME NOT NULL,
    `expiry_date` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `venue_reservations`
--
CREATE TABLE IF NOT EXISTS `venue_reservations` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `venue_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `event_id` INT NULL,
    `title` VARCHAR(255) NOT NULL COMMENT 'Purpose of the reservation',
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('pending', 'confirmed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
    INDEX `idx_venue_datetime` (`venue_id`, `start_time`, `end_time`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Insert default roles
--

SET FOREIGN_KEY_CHECKS=0;

-- Clean up tables before seeding
DELETE FROM `user_roles`;
DELETE FROM `roles`;
DELETE FROM `users`;
DELETE FROM `departments`;
DELETE FROM `venues`;
DELETE FROM `event_ideas`;
DELETE FROM `announcements`;

-- Reset auto-increment values to 1
ALTER TABLE `user_roles` AUTO_INCREMENT = 1;
ALTER TABLE `roles` AUTO_INCREMENT = 1;
ALTER TABLE `users` AUTO_INCREMENT = 1;
ALTER TABLE `departments` AUTO_INCREMENT = 1;
ALTER TABLE `venues` AUTO_INCREMENT = 1;
ALTER TABLE `event_ideas` AUTO_INCREMENT = 1;
ALTER TABLE `announcements` AUTO_INCREMENT = 1;

INSERT INTO `roles` (`name`, `description`) VALUES
('Admin', 'System administrator with full access'),
('Event Coordinator', 'Manages and coordinates events'),
('Approver (Dean/Head)', 'Approves or rejects events (Deans, Department Heads)'),
('Student', 'Regular student user'),
('Faculty', 'Faculty member'),
('Security Officer', 'Security personnel'),
('Maintenance Staff', 'Maintenance staff'),
('Finance Officer', 'Finance department staff'),
('Guest User', 'Guest user with limited access'),
('Auditor', 'Audits event logs and system activities');

--
-- Insert default departments
--
INSERT INTO `departments` (`name`) VALUES
('CET- College of Engineering and Technology'),
('CCJ - College of Criminal Justice'),
('CTE- College of Teachers Education'),
('CAS- College of Arts and Sciences'),
('CBE- College of Business Education'),
('COAS- College of Allied Health and Sciences');

--
-- Insert default venues
--
INSERT INTO `venues` (`name`, `location`, `capacity`) VALUES
('Main Auditorium', 'Admin Building, 1st Floor', 500),
('Conference Hall A', 'CET Building, 2nd Floor', 100),
('Library Discussion Room', 'Library, 3rd Floor', 25),
('Sports Gymnasium', 'Sports Complex', 1000),
('Open Field', 'Central Campus Area', 2000);

--
-- Insert default admin user (password: admin123)
--

INSERT INTO `users` (
    `username`,
    `password`,
    `full_name`,
    `email`,
    `department`,
    `position`,
    `status`
) VALUES (
    'admin',
    '$2y$10$4Bvwk..m4bK/iV5E2n64b.jH7JgJp2/a2xQ1g3y.fG3LqI.w.u5iK',
    'System Administrator',
    'admin@campus.edu',
    'IT',
    'Administrator',
    'active'
);

--
-- Assign admin role to admin user
--

SET @admin_id = LAST_INSERT_ID();
SET @admin_role_id = (SELECT `id` FROM `roles` WHERE `name` = 'Admin');
INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_by`) VALUES (@admin_id, @admin_role_id, @admin_id);

SET FOREIGN_KEY_CHECKS=1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;