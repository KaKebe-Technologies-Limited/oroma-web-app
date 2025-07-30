-- MySQL Database Schema for Oroma TV
-- This is the final MySQL version for production deployment

-- Create database (run this first if database doesn't exist)
-- CREATE DATABASE u850523537_oroma_web;
-- USE u850523537_oroma_web;

-- Drop tables if they exist (for development)
DROP TABLE IF EXISTS contact_submissions;
DROP TABLE IF EXISTS reactions;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS stream_stats;
DROP TABLE IF EXISTS page_views;
DROP TABLE IF EXISTS blog_posts;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;

-- Users table for admin authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') DEFAULT 'viewer',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Chat messages (replacing WebSocket with PHP polling)
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    stream_type ENUM('tv', 'radio') DEFAULT 'tv',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reactions (replacing WebSocket with PHP polling)
CREATE TABLE reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reaction_type VARCHAR(10) NOT NULL,
    stream_type ENUM('tv', 'radio') DEFAULT 'tv',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stream statistics
CREATE TABLE stream_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stream_type ENUM('tv', 'radio') NOT NULL,
    viewers_count INT DEFAULT 0,
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    quality VARCHAR(20),
    latency VARCHAR(20),
    bitrate VARCHAR(20),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Page views tracking
CREATE TABLE page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact form submissions
CREATE TABLE contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Blog posts for newsroom
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT,
    excerpt TEXT,
    featured_image VARCHAR(500),
    author_id INT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Settings table for configuration
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial data
INSERT INTO stream_stats (stream_type, viewers_count, status, quality, latency, bitrate) VALUES
('tv', 245, 'online', 'HD', '2.3s', '2.5 Mbps'),
('radio', 180, 'online', 'High', '1.8s', '128 kbps');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_title', 'Oroma TV', 'Website title'),
('site_description', 'Live TV and QFM Radio 94.3 FM streaming platform', 'Website description'),
('contact_email', 'info@oromatv.com', 'Contact email address'),
('stream_tv_url', '', 'TV stream URL'),
('stream_radio_url', '', 'Radio stream URL');

-- Create indexes for better performance
CREATE INDEX idx_chat_created_at ON chat_messages(created_at);
CREATE INDEX idx_reactions_created_at ON reactions(created_at);
CREATE INDEX idx_reactions_stream_type ON reactions(stream_type);
CREATE INDEX idx_stream_stats_recorded_at ON stream_stats(recorded_at);
CREATE INDEX idx_page_views_created_at ON page_views(created_at);
CREATE INDEX idx_blog_posts_status ON blog_posts(status);
CREATE INDEX idx_blog_posts_published_at ON blog_posts(published_at);