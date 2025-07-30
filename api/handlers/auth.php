<?php
/**
 * Authentication API Handler
 */

function handleAuthRequest($db, $method, $id, $action) {
    switch($method) {
        case 'POST':
            if ($action === 'login') {
                loginUser($db);
            } elseif ($action === 'logout') {
                logoutUser($db);
            } elseif ($action === 'register') {
                registerUser($db);
            }
            break;
            
        case 'GET':
            if ($action === 'check') {
                checkAuth($db);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
}

function loginUser($db) {
    try {
        $input = getJsonInput();
        
        if (empty($input['username']) || empty($input['password'])) {
            sendJsonResponse(['error' => 'Username and password are required'], 400);
        }
        
        $username = sanitizeInput($input['username']);
        $password = $input['password'];
        
        // Get user from database
        $stmt = $db->prepare("SELECT id, username, email, password, role, full_name FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($password, $user['password'])) {
            // Log failed login attempt
            logAnalytics($db, 'login_failed', ['username' => $username]);
            sendJsonResponse(['error' => 'Invalid credentials'], 401);
        }
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Log successful login
        logAnalytics($db, 'login_success', [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]);
        
        // Return user data (without password)
        unset($user['password']);
        sendJsonResponse([
            'success' => true,
            'user' => $user,
            'message' => 'Login successful'
        ]);
        
    } catch(Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Login failed'], 500);
    }
}

function logoutUser($db) {
    try {
        // Log logout
        if (isset($_SESSION['user_id'])) {
            logAnalytics($db, 'logout', ['user_id' => $_SESSION['user_id']]);
        }
        
        // Destroy session
        session_destroy();
        session_start(); // Start new session
        
        sendJsonResponse(['success' => true, 'message' => 'Logged out successfully']);
        
    } catch(Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Logout failed'], 500);
    }
}

function registerUser($db) {
    try {
        $input = getJsonInput();
        
        // Validate required fields
        if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
            sendJsonResponse(['error' => 'Username, email, and password are required'], 400);
        }
        
        $username = sanitizeInput($input['username']);
        $email = sanitizeInput($input['email']);
        $password = $input['password'];
        $fullName = isset($input['full_name']) ? sanitizeInput($input['full_name']) : null;
        
        // Validate email
        if (!validateEmail($email)) {
            sendJsonResponse(['error' => 'Invalid email format'], 400);
        }
        
        // Validate password strength
        if (strlen($password) < 6) {
            sendJsonResponse(['error' => 'Password must be at least 6 characters'], 400);
        }
        
        // Check if username or email already exists
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $exists = $stmt->fetch();
        
        if ($exists['count'] > 0) {
            sendJsonResponse(['error' => 'Username or email already exists'], 409);
        }
        
        // Hash password
        $hashedPassword = hashPassword($password);
        
        // Insert new user
        $userId = generateUUID();
        $stmt = $db->prepare("
            INSERT INTO users (id, username, email, password, full_name, role) 
            VALUES (?, ?, ?, ?, ?, 'user')
        ");
        $stmt->execute([$userId, $username, $email, $hashedPassword, $fullName]);
        
        // Log registration
        logAnalytics($db, 'user_registered', [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email
        ]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $userId
        ], 201);
        
    } catch(Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Registration failed'], 500);
    }
}

function checkAuth($db) {
    try {
        if (!isset($_SESSION['user_id'])) {
            sendJsonResponse(['authenticated' => false], 401);
        }
        
        // Get current user data
        $stmt = $db->prepare("SELECT id, username, email, role, full_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            session_destroy();
            sendJsonResponse(['authenticated' => false], 401);
        }
        
        sendJsonResponse([
            'authenticated' => true,
            'user' => $user
        ]);
        
    } catch(Exception $e) {
        error_log("Auth check error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Authentication check failed'], 500);
    }
}
?>