<?php
/**
 * Oroma TV API Router
 * Handles all API requests and routes them to appropriate handlers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session for user tracking
session_start();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path); // Remove /api prefix
$path = trim($path, '/');

// Parse path segments
$segments = explode('/', $path);
$endpoint = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

// Database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Route requests
try {
    switch($endpoint) {
        case 'news':
            require_once '../api/handlers/news.php';
            handleNewsRequest($db, $method, $id, $action);
            break;
            
        case 'programs':
            require_once '../api/handlers/programs.php';
            handleProgramsRequest($db, $method, $id, $action);
            break;
            
        case 'events':
            require_once '../api/handlers/events.php';
            handleEventsRequest($db, $method, $id, $action);
            break;
            
        case 'contacts':
            require_once '../api/handlers/contacts.php';
            handleContactsRequest($db, $method, $id, $action);
            break;
            
        case 'subscribers':
            require_once '../api/handlers/subscribers.php';
            handleSubscribersRequest($db, $method, $id, $action);
            break;
            
        case 'song-requests':
            require_once '../api/handlers/song_requests.php';
            handleSongRequestsRequest($db, $method, $id, $action);
            break;
            
        case 'live-reactions':
            require_once '../api/handlers/live_reactions.php';
            handleLiveReactionsRequest($db, $method, $id, $action);
            break;
            
        case 'live-comments':
            require_once '../api/handlers/live_comments.php';
            handleLiveCommentsRequest($db, $method, $id, $action);
            break;
            
        case 'active-users':
            require_once '../api/handlers/active_users.php';
            handleActiveUsersRequest($db, $method, $id, $action);
            break;
            
        case 'interview-requests':
            require_once '../api/handlers/interview_requests.php';
            handleInterviewRequestsRequest($db, $method, $id, $action);
            break;
            
        case 'program-proposals':
            require_once '../api/handlers/program_proposals.php';
            handleProgramProposalsRequest($db, $method, $id, $action);
            break;
            
        case 'auth':
            require_once '../api/handlers/auth.php';
            handleAuthRequest($db, $method, $id, $action);
            break;
            
        case 'admin':
            require_once '../api/handlers/admin.php';
            handleAdminRequest($db, $method, $id, $action);
            break;
            
        case 'analytics':
            require_once '../api/handlers/analytics.php';
            handleAnalyticsRequest($db, $method, $id, $action);
            break;
            
        case 'settings':
            require_once '../api/handlers/settings.php';
            handleSettingsRequest($db, $method, $id, $action);
            break;
            
        case 'user':
            require_once '../api/handlers/user.php';
            handleUserRequest($db, $method, $id, $action);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
} catch(Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>