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

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `max_capacity` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('draft','approved','rejected','cancelled') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` text DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_name`, `permission_description`, `module`, `created_at`) VALUES
(1, 'event.submit', 'Submit event idea/request', 'events', '2025-06-18 15:34:42'),
(2, 'event.create_official', 'Create official event', 'events', '2025-06-18 15:34:42'),
(3, 'event.edit', 'Edit event details', 'events', '2025-06-18 15:34:42'),
(4, 'event.delete', 'Delete event', 'events', '2025-06-18 15:34:42'),
(5, 'venue.reserve', 'Reserve venue for events', 'venues', '2025-06-18 15:34:42'),
(6, 'event.approve_reject', 'Approve or reject events', 'events', '2025-06-18 15:34:42'),
(7, 'staff.assign', 'Assign staff for security/maintenance', 'staff', '2025-06-18 15:34:42'),
(8, 'documents.upload', 'Upload supporting documents', 'documents', '2025-06-18 15:34:42'),
(9, 'documents.download', 'Download event documents', 'documents', '2025-06-18 15:34:42'),
(10, 'calendar.view', 'View event calendar', 'calendar', '2025-06-18 15:34:42'),
(11, 'event.view_details', 'View event details', 'events', '2025-06-18 15:34:42'),
(12, 'reservation.cancel', 'Cancel reservations', 'reservations', '2025-06-18 15:34:42'),
(13, 'announcements.post', 'Post announcements', 'announcements', '2025-06-18 15:34:42'),
(14, 'announcements.view', 'View announcements', 'announcements', '2025-06-18 15:34:42'),
(15, 'staff.notify', 'Notify assigned staff', 'notifications', '2025-06-18 15:34:42'),
(16, 'reports.generate', 'Generate event reports', 'reports', '2025-06-18 15:34:42'),
(17, 'users.manage', 'Manage system users', 'users', '2025-06-18 15:34:42'),
(18, 'venues.manage', 'Manage venues', 'venues', '2025-06-18 15:34:42'),
(19, 'reservation.view_history', 'View reservation history', 'reservations', '2025-06-18 15:34:42'),
(20, 'audit.view_logs', 'View audit logs', 'audit', '2025-06-18 15:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reservation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('confirmed','cancelled','pending') DEFAULT 'confirmed',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `role_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `role_description`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'System administrator with full access', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(2, 'Event Coordinator', 'Coordinates and manages events', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(3, 'Approver/Dean/Head', 'Approves events and manages staff', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(4, 'Student', 'Student user with basic access', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(5, 'Faculty', 'Faculty member with teaching privileges', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(6, 'Security Officer', 'Security and safety management', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(7, 'Maintenance Staff', 'Facility maintenance and setup', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(8, 'Finance Officer', 'Financial management and reporting', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(9, 'Guest User', 'External guest with limited access', '2025-06-18 15:34:42', '2025-06-18 15:34:42'),
(10, 'Auditor', 'System auditing and compliance', '2025-06-18 15:34:42', '2025-06-18 15:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_permission_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_permission_id`, `role_id`, `permission_id`, `granted_at`) VALUES
(1, 1, 13, '2025-06-18 15:34:42'),
(2, 1, 14, '2025-06-18 15:34:42'),
(3, 1, 20, '2025-06-18 15:34:42'),
(4, 1, 10, '2025-06-18 15:34:42'),
(5, 1, 9, '2025-06-18 15:34:42'),
(6, 1, 8, '2025-06-18 15:34:42'),
(7, 1, 6, '2025-06-18 15:34:42'),
(8, 1, 2, '2025-06-18 15:34:42'),
(9, 1, 4, '2025-06-18 15:34:42'),
(10, 1, 3, '2025-06-18 15:34:42'),
(11, 1, 1, '2025-06-18 15:34:42'),
(12, 1, 11, '2025-06-18 15:34:42'),
(13, 1, 16, '2025-06-18 15:34:42'),
(14, 1, 12, '2025-06-18 15:34:42'),
(15, 1, 19, '2025-06-18 15:34:42'),
(16, 1, 7, '2025-06-18 15:34:42'),
(17, 1, 15, '2025-06-18 15:34:42'),
(18, 1, 17, '2025-06-18 15:34:42'),
(19, 1, 5, '2025-06-18 15:34:42'),
(20, 1, 18, '2025-06-18 15:34:42'),
(32, 2, 13, '2025-06-18 15:34:42'),
(33, 2, 14, '2025-06-18 15:34:42'),
(34, 2, 10, '2025-06-18 15:34:42'),
(35, 2, 9, '2025-06-18 15:34:42'),
(36, 2, 8, '2025-06-18 15:34:42'),
(37, 2, 2, '2025-06-18 15:34:42'),
(38, 2, 1, '2025-06-18 15:34:42'),
(39, 2, 11, '2025-06-18 15:34:42'),
(40, 2, 16, '2025-06-18 15:34:42'),
(41, 2, 19, '2025-06-18 15:34:42'),
(42, 2, 15, '2025-06-18 15:34:42'),
(43, 2, 5, '2025-06-18 15:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('employee','customer') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `user_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', NULL, 'employee', 'active', '2025-06-18 15:34:42', '2025-06-18 15:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_role_id`, `user_id`, `role_id`, `assigned_by`, `assigned_at`) VALUES
(1, 1, 1, 1, '2025-06-18 15:34:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_permission_id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_role_id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `role_permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `user_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
