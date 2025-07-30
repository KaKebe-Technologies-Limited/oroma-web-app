-- Oroma TV Database Schema for MySQL
-- Created: July 2025

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS oromatv;
CREATE DATABASE oromatv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oromatv;

-- Users table for admin authentication
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    profile_image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Programs/Shows table
CREATE TABLE programs (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    schedule_time TIME,
    schedule_days JSON,
    duration_minutes INT DEFAULT 60,
    host VARCHAR(255),
    category ENUM('news', 'entertainment', 'music', 'talk', 'sports', 'education') DEFAULT 'entertainment',
    is_active BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- News articles table
CREATE TABLE news (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    summary TEXT,
    author VARCHAR(255),
    category ENUM('local', 'national', 'international', 'sports', 'entertainment', 'politics') DEFAULT 'local',
    image_url VARCHAR(500),
    published BOOLEAN DEFAULT FALSE,
    featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    location VARCHAR(255),
    image_url VARCHAR(500),
    ticket_price DECIMAL(10,2) DEFAULT 0.00,
    max_attendees INT,
    current_attendees INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contact messages table
CREATE TABLE contacts (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Newsletter subscribers table
CREATE TABLE subscribers (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    status ENUM('active', 'unsubscribed') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Song requests table
CREATE TABLE song_requests (
    id VARCHAR(36) PRIMARY KEY,
    song_title VARCHAR(255) NOT NULL,
    artist VARCHAR(255),
    requester_name VARCHAR(255) NOT NULL,
    requester_phone VARCHAR(50),
    message TEXT,
    status ENUM('pending', 'approved', 'played', 'rejected') DEFAULT 'pending',
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Live reactions table
CREATE TABLE live_reactions (
    id VARCHAR(36) PRIMARY KEY,
    stream_type ENUM('tv', 'radio') DEFAULT 'tv',
    reaction_type VARCHAR(10) NOT NULL,
    user_session VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Live comments table
CREATE TABLE live_comments (
    id VARCHAR(36) PRIMARY KEY,
    stream_type ENUM('tv', 'radio') DEFAULT 'tv',
    username VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    user_session VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Active users tracking table
CREATE TABLE active_users (
    id VARCHAR(36) PRIMARY KEY,
    user_session VARCHAR(255) NOT NULL UNIQUE,
    stream_type ENUM('tv', 'radio') DEFAULT 'tv',
    username VARCHAR(255),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Interview requests table
CREATE TABLE interview_requests (
    id VARCHAR(36) PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    expertise VARCHAR(255),
    topic VARCHAR(255) NOT NULL,
    availability TEXT,
    message TEXT,
    status ENUM('pending', 'approved', 'scheduled', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Program proposals table
CREATE TABLE program_proposals (
    id VARCHAR(36) PRIMARY KEY,
    program_title VARCHAR(255) NOT NULL,
    host_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    program_type ENUM('talk', 'music', 'news', 'entertainment', 'education', 'sports') DEFAULT 'talk',
    description TEXT NOT NULL,
    target_audience VARCHAR(255),
    proposed_schedule VARCHAR(255),
    experience TEXT,
    status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Site settings table
CREATE TABLE site_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Analytics tracking table
CREATE TABLE analytics (
    id VARCHAR(36) PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    user_session VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Event RSVPs table
CREATE TABLE event_rsvps (
    id VARCHAR(36) PRIMARY KEY,
    event_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    guests INT DEFAULT 0,
    status ENUM('attending', 'maybe', 'not_attending') DEFAULT 'attending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (id, username, email, password, full_name, role) VALUES 
('admin-001', 'admin', 'admin@oromatv.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');

-- Insert default site settings
INSERT INTO site_settings (id, setting_key, setting_value, setting_type, description) VALUES
('set-001', 'site_name', 'Oroma TV', 'text', 'Site name'),
('set-002', 'site_tagline', 'Dwon tumalo me Uganda', 'text', 'Site tagline'),
('set-003', 'tv_stream_url', 'https://mediaserver.oromatv.com/LiveApp/streams/12345.m3u8', 'text', 'TV stream URL'),
('set-004', 'radio_stream_url', 'https://hoth.alonhosting.com:3975/stream', 'text', 'Radio stream URL'),
('set-005', 'contact_email', 'info@oromatv.com', 'text', 'Contact email'),
('set-006', 'contact_phone', '+256 123 456 789', 'text', 'Contact phone'),
('set-007', 'whatsapp_number', '256123456789', 'text', 'WhatsApp number');

SET FOREIGN_KEY_CHECKS = 1;