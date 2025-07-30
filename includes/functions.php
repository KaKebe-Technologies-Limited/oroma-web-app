<?php
/**
 * Utility functions for Oroma TV
 */

/**
 * Generate UUID v4
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get JSON input data
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

/**
 * Require authentication
 */
function requireAuth($db) {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['error' => 'Authentication required'], 401);
    }
    
    // Verify user still exists in database
    $stmt = $db->prepare("SELECT id, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        sendJsonResponse(['error' => 'User not found'], 401);
    }
    
    return $user;
}

/**
 * Require admin role
 */
function requireAdmin($db) {
    $user = requireAuth($db);
    if ($user['role'] !== 'admin') {
        sendJsonResponse(['error' => 'Admin access required'], 403);
    }
    return $user;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Upload file helper
 */
function uploadFile($file, $uploadDir = '../uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        throw new Exception('Invalid file type');
    }
    
    if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
        throw new Exception('File too large');
    }
    
    $newFileName = generateUUID() . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;
    
    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        throw new Exception('Failed to upload file');
    }
    
    return '/uploads/' . $newFileName;
}

/**
 * Clean old reactions (older than 1 hour)
 */
function cleanOldReactions($db) {
    $stmt = $db->prepare("DELETE FROM live_reactions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute();
}

/**
 * Clean old comments (older than 24 hours)
 */
function cleanOldComments($db) {
    $stmt = $db->prepare("DELETE FROM live_comments WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
}

/**
 * Clean inactive users (no activity in last 5 minutes)
 */
function cleanInactiveUsers($db) {
    $stmt = $db->prepare("DELETE FROM active_users WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->execute();
}

/**
 * Get user session ID
 */
function getUserSession() {
    if (!isset($_SESSION['user_session'])) {
        $_SESSION['user_session'] = generateUUID();
    }
    return $_SESSION['user_session'];
}

/**
 * Log analytics event
 */
function logAnalytics($db, $eventType, $eventData = null, $userSession = null) {
    try {
        $stmt = $db->prepare("INSERT INTO analytics (id, event_type, event_data, user_session, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            generateUUID(),
            $eventType,
            $eventData ? json_encode($eventData) : null,
            $userSession ?? getUserSession(),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch(Exception $e) {
        error_log("Analytics logging error: " . $e->getMessage());
    }
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Get site setting
 */
function getSiteSetting($db, $key, $default = null) {
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

/**
 * Set site setting
 */
function setSiteSetting($db, $key, $value, $type = 'text', $description = null) {
    $stmt = $db->prepare("INSERT INTO site_settings (id, setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?, description = ?");
    $id = generateUUID();
    $stmt->execute([$id, $key, $value, $type, $description, $value, $type, $description]);
}
?>