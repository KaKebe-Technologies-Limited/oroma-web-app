<?php
// Database configuration for Oroma TV - MySQL Configuration
$host = 'localhost';
$dbname = 'u850523537_oroma_web';
$username = 'u850523537_oroma_user';
$password = 'Oroma 101619';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // For development/testing, create in-memory SQLite database
    try {
        $pdo = new PDO("sqlite::memory:", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Create tables for SQLite (development mode)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                stream_type VARCHAR(10) DEFAULT 'tv',
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS reactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reaction_type VARCHAR(10) NOT NULL,
                stream_type VARCHAR(10) DEFAULT 'tv',
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS stream_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                stream_type VARCHAR(10) NOT NULL,
                viewers_count INTEGER DEFAULT 0,
                status VARCHAR(20) DEFAULT 'offline',
                quality VARCHAR(20),
                latency VARCHAR(20),
                bitrate VARCHAR(20),
                recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS page_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                page_url VARCHAR(500) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                referrer VARCHAR(500),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            INSERT OR IGNORE INTO stream_stats (id, stream_type, viewers_count, status, quality, latency, bitrate) VALUES
            (1, 'tv', 245, 'online', 'HD', '2.3s', '2.5 Mbps'),
            (2, 'radio', 180, 'online', 'High', '1.8s', '128 kbps');
        ");
        
        error_log("Using SQLite database for development (MySQL connection failed)");
    } catch (PDOException $e2) {
        die("Database connection failed: " . $e->getMessage() . " (SQLite fallback also failed: " . $e2->getMessage() . ")");
    }
}

// Site configuration
$site_config = [
    'site_name' => 'Oroma TV',
    'site_tagline' => 'Northern Uganda live TV and QFM Radio 94.3 FM',
    'stream_url' => 'https://mediaserver.oromatv.com/LiveApp/streams/12345.m3u8',
    'radio_url' => 'https://hoth.alonhosting.com:3975/stream',
    'upload_dir' => 'uploads/news/',
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
];

// Create uploads directory if it doesn't exist
if (!file_exists($site_config['upload_dir'])) {
    mkdir($site_config['upload_dir'], 0755, true);
}

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_admin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $script;
}

function format_time_ago($timestamp) {
    $time = time() - strtotime($timestamp);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($timestamp));
}

session_start();
?>