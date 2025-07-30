-- MySQL Database Schema for Oroma TV
-- MySQL version of the database schema

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database (run this separately if needed)
-- CREATE DATABASE IF NOT EXISTS u850523537_oroma_web;
-- USE u850523537_oroma_web;

-- Users table for admin authentication
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','editor','viewer') DEFAULT 'viewer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog posts table for news/content
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `excerpt` text,
  `content` longtext NOT NULL,
  `featured_image` varchar(500),
  `author_id` int(11),
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `published_at` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_author` (`author_id`),
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page views tracking
CREATE TABLE IF NOT EXISTS `page_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_url` varchar(500) NOT NULL,
  `ip_address` varchar(45),
  `user_agent` text,
  `referrer` varchar(500),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_url` (`page_url`(255)),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stream statistics
CREATE TABLE IF NOT EXISTS `stream_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_type` enum('tv','radio') NOT NULL,
  `viewers_count` int(11) DEFAULT 0,
  `status` enum('online','offline','error') DEFAULT 'offline',
  `quality` varchar(20),
  `latency` varchar(20),
  `bitrate` varchar(20),
  `recorded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stream_type` (`stream_type`),
  KEY `idx_recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages (replacing WebSocket with PHP polling)
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `stream_type` enum('tv','radio') DEFAULT 'tv',
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stream_type` (`stream_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactions (replacing WebSocket with PHP polling)
CREATE TABLE IF NOT EXISTS `reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reaction_type` varchar(10) NOT NULL,
  `stream_type` enum('tv','radio') DEFAULT 'tv',
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reaction_type` (`reaction_type`),
  KEY `idx_stream_type` (`stream_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact form submissions
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45),
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table for configuration
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `description` varchar(255),
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@oromatv.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'Oroma TV', 'Website name'),
('site_tagline', 'Northern Uganda live TV and QFM Radio 94.3 FM', 'Website tagline'),
('stream_url', 'https://mediaserver.oromatv.com/LiveApp/streams/12345.m3u8', 'TV stream URL'),
('radio_url', 'https://hoth.alonhosting.com:3975/stream', 'Radio stream URL'),
('contact_phone1', '+256 772 123 456', 'Primary contact phone'),
('contact_phone2', '+256 751 789 012', 'Secondary contact phone'),
('contact_email', 'info@oromatv.com', 'Contact email'),
('contact_address', 'Plot 000 Semsem Building Won Nyaci Road, Lira City, Northern Uganda', 'Physical address'),
('whatsapp_number', '+256777676206', 'WhatsApp contact number');

-- Insert sample stream stats
INSERT IGNORE INTO `stream_stats` (`stream_type`, `viewers_count`, `status`, `quality`, `latency`, `bitrate`) VALUES
('tv', 245, 'online', 'HD', '2.3s', '2.5 Mbps'),
('radio', 180, 'online', 'High', '1.8s', '128 kbps');

COMMIT;