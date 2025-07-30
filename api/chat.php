<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getChatMessages($limit = 20, $since = 0) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM chat_messages WHERE strftime('%s', created_at) > ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$since, $limit]);
        $messages = $stmt->fetchAll();
        
        // Format for frontend
        foreach ($messages as &$message) {
            $message['timestamp'] = strtotime($message['created_at']);
            $message['formatted_time'] = date('H:i', $message['timestamp']);
            $message['name'] = $message['username']; // Compatibility with frontend
        }
        
        return array_reverse($messages);
    } catch (PDOException $e) {
        error_log("Chat error: " . $e->getMessage());
        return [];
    }
}

function addChatMessage($username, $message, $stream_type = 'tv') {
    global $pdo;
    
    try {
        $sql = "INSERT INTO chat_messages (username, message, stream_type, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            htmlspecialchars($username),
            htmlspecialchars($message),
            $stream_type,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Get the inserted message
        $messageId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $newMessage = $stmt->fetch();
        
        if ($newMessage) {
            $newMessage['timestamp'] = strtotime($newMessage['created_at']);
            $newMessage['formatted_time'] = date('H:i', $newMessage['timestamp']);
            $newMessage['name'] = $newMessage['username']; // Compatibility with frontend
        }
        
        // Clean old messages (keep last 200)
        $pdo->exec("DELETE FROM chat_messages WHERE id NOT IN (SELECT id FROM (SELECT id FROM chat_messages ORDER BY created_at DESC LIMIT 200) AS t)");
        
        return $newMessage;
    } catch (PDOException $e) {
        error_log("Chat error: " . $e->getMessage());
        return false;
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $since = intval($_GET['since'] ?? 0);
    
    $messages = getChatMessages($limit, $since);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'messages' => $messages,
            'last_updated' => time()
        ]
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = trim($input['name'] ?? $input['username'] ?? ''); // Support both field names
    $message = trim($input['message'] ?? '');
    $stream_type = $input['stream_type'] ?? 'tv';
    
    // Validate input
    if (empty($username) || empty($message)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Username and message are required'
        ]);
        exit;
    }
    
    if (strlen($username) > 50 || strlen($message) > 500) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Username or message too long'
        ]);
        exit;
    }
    
    // Simple rate limiting
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE ip_address = ? AND created_at > datetime('now', '-1 minute')");
        $stmt->execute([$_SERVER['REMOTE_ADDR']]);
        $recentMessages = $stmt->fetchColumn();
        
        if ($recentMessages > 10) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Too many messages. Please wait a moment.'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        // Continue without rate limiting if query fails
        error_log("Rate limiting error: " . $e->getMessage());
    }
    
    $newMessage = addChatMessage($username, $message, $stream_type);
    
    if ($newMessage) {
        echo json_encode([
            'success' => true,
            'data' => $newMessage
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save message'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
?>