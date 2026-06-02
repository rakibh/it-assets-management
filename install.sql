-- IT Management System - Database Schema
-- Version 2.0 (Final)

CREATE DATABASE IF NOT EXISTS `it_assets_management`;
USE `it_assets_management`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `employee_id` VARCHAR(20) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    `email` VARCHAR(150),
    `phone` VARCHAR(20),
    `designation` VARCHAR(100),
    `profile_photo` VARCHAR(255),
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `force_password_change` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (`role`),
    INDEX idx_user_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Preferences
CREATE TABLE IF NOT EXISTS `user_preferences` (
    `user_id` INT PRIMARY KEY,
    `theme` ENUM('light', 'dark') DEFAULT 'light',
    `timezone` VARCHAR(50) DEFAULT 'Asia/Dhaka',
    `time_format` ENUM('12', '24') DEFAULT '12',
    `desktop_notifications` BOOLEAN DEFAULT FALSE,
    `notification_types` JSON DEFAULT NULL,
    `toast_position` ENUM('top-right', 'bottom-right') DEFAULT 'top-right',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `target_table` VARCHAR(50) NOT NULL,
    `target_id` INT,
    `old_values` JSON,
    `new_values` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (`user_id`),
    INDEX idx_audit_target (`target_table`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Predefined Blocks (Reusable Field Groups)
CREATE TABLE IF NOT EXISTS `equipment_blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) UNIQUE NOT NULL,
    `fields_schema` JSON NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Equipment Types (Dynamic Form Schemas)
CREATE TABLE IF NOT EXISTS `equipment_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) UNIQUE NOT NULL, -- e.g., 'Laptop', 'Printer'
    `form_schema` JSON NOT NULL, -- Defines custom fields
    `block_ids` JSON, -- Array of IDs from equipment_blocks
    `has_network` BOOLEAN DEFAULT FALSE, -- Toggle for IP/MAC fields
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Equipments
CREATE TABLE IF NOT EXISTS `equipments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `brand` VARCHAR(100),
    `model` VARCHAR(100),
    `serial_number` VARCHAR(100),
    `mac_address` VARCHAR(17),
    `status` ENUM('In Use', 'Available', 'Under Repair', 'Retired', 'Lost/Stolen') DEFAULT 'Available',
    `location` VARCHAR(100),
    `office_location` VARCHAR(100),
    `floor` VARCHAR(50),
    `department` VARCHAR(100),
    `assigned_to` VARCHAR(100),
    `condition` ENUM('excellent', 'good', 'fair', 'poor', 'broken') DEFAULT 'excellent',
    `warranty_seller` VARCHAR(255),
    `warranty_purchase_date` DATE,
    `warranty_expiry` DATE,
    `warranty_file` VARCHAR(255),
    `custom_data` JSON, -- Stores values for fields defined in equipment_types
    `images` JSON, -- Stores gallery photo paths
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`type_id`) REFERENCES `equipment_types`(`id`) ON DELETE RESTRICT,
    INDEX idx_equip_type (`type_id`),
    INDEX idx_equip_status (`status`),
    INDEX idx_serial_number (`serial_number`),
    INDEX idx_warranty_expiry (`warranty_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Network Info
CREATE TABLE IF NOT EXISTS `network_info` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) UNIQUE NOT NULL,
    `mac_address` VARCHAR(17) UNIQUE,
    `cable_no` VARCHAR(50),
    `patch_panel_no` VARCHAR(50),
    `patch_panel_port` VARCHAR(50),
    `patch_panel_location` VARCHAR(100),
    `switch_no` VARCHAR(50),
    `switch_port` VARCHAR(50),
    `switch_location` VARCHAR(100),
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Equipment-Network Mapping (Pivot)
CREATE TABLE IF NOT EXISTS `equipment_network_map` (
    `equipment_id` INT,
    `network_id` INT,
    PRIMARY KEY (`equipment_id`, `network_id`),
    FOREIGN KEY (`equipment_id`) REFERENCES `equipments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`network_id`) REFERENCES `network_info`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tasks
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `tags` VARCHAR(255),
    `status` ENUM('todo', 'doing', 'past_due', 'done', 'dropped') DEFAULT 'todo',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `recurrence` ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
    `recurrence_metadata` JSON,
    `deadline` DATETIME,
    `creator_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `dropped_at` TIMESTAMP NULL,
    FOREIGN KEY (`creator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX idx_task_status (`status`),
    INDEX idx_task_priority (`priority`),
    INDEX idx_task_deadline (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Task Assignees
CREATE TABLE IF NOT EXISTS `task_assignees` (
    `task_id` INT,
    `user_id` INT,
    PRIMARY KEY (`task_id`, `user_id`),
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Task Attachments
CREATE TABLE IF NOT EXISTS `task_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Task Comments
CREATE TABLE IF NOT EXISTS `task_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `message` TEXT NOT NULL,
    `data` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. User Notifications (Status Tracking)
CREATE TABLE IF NOT EXISTS `user_notifications` (
    `user_id` INT,
    `notification_id` INT,
    `is_read` BOOLEAN DEFAULT FALSE,
    `is_acknowledged` BOOLEAN DEFAULT FALSE,
    `read_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`user_id`, `notification_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`notification_id`) REFERENCES `notifications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. System Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('system_name', 'IT Management System'),
('records_per_page', '20'),
('notification_refresh', '30'),
('log_retention_days', '90'),
('enable_log_cleanup', '1'),
('session_timeout', '60')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
-- 14. System Logs
CREATE TABLE IF NOT EXISTS `system_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `level` ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    `category` VARCHAR(50) DEFAULT 'system',
    `user_id` INT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `message` TEXT NOT NULL,
    `context` JSON,
    INDEX idx_log_level (`level`),
    INDEX idx_log_category (`category`),
    INDEX idx_log_user (`user_id`),
    INDEX idx_log_timestamp (`timestamp`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Data
INSERT INTO `users` (`username`, `employee_id`, `password`, `first_name`, `last_name`, `role`) VALUES 
('admin', 'EMP001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin'); -- password: password

INSERT INTO `user_preferences` (`user_id`, `notification_types`) VALUES (1, '["all"]');

SET FOREIGN_KEY_CHECKS = 1;
