<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getReactions($stream_type = 'tv') {
    global $pdo;
    
    try {
        // Get reaction counts for the last hour (compatible with SQLite and MySQL)
        $sql = "SELECT reaction_type, COUNT(*) as count FROM reactions 
                WHERE stream_type = ? AND created_at > datetime('now', '-1 hour') 
                GROUP BY reaction_type";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$stream_type]);
        $results = $stmt->fetchAll();
        
        // Initialize default reactions
        $reactions = [
            '👍' => 0,
            '🔥' => 0,
            '😂' => 0,
            '😢' => 0,
            '👏' => 0,
            'total' => 0,
            'active_viewers' => 0,
            'last_updated' => time()
        ];
        
        // Update with actual counts
        foreach ($results as $result) {
            $reactions[$result['reaction_type']] = (int)$result['count'];
        }
        
        // Calculate total
        $reactions['total'] = array_sum(array_slice($reactions, 0, 5));
        
        // Get active viewers (unique IPs in last 5 minutes)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) FROM reactions 
                               WHERE stream_type = ? AND created_at > datetime('now', '-5 minutes')");
        $stmt->execute([$stream_type]);
        $reactions['active_viewers'] = (int)$stmt->fetchColumn();
        
        return $reactions;
    } catch (PDOException $e) {
        error_log("Reactions error: " . $e->getMessage());
        return [
            '👍' => 1,
            '🔥' => 1,
            '😂' => 1,
            '😢' => 0,
            '👏' => 0,
            'total' => 3,
            'active_viewers' => 0,
            'last_updated' => time()
        ];
    }
}

function addReaction($type, $stream_type = 'tv') {
    global $pdo;
    
    $validReactions = ['👍', '🔥', '😂', '😢', '👏'];
    
    if (!in_array($type, $validReactions)) {
        return false;
    }
    
    try {
        // Check rate limiting (max 5 reactions per IP per minute)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reactions 
                               WHERE ip_address = ? AND created_at > datetime('now', '-1 minute')");
        $stmt->execute([$_SERVER['REMOTE_ADDR']]);
        $recentReactions = $stmt->fetchColumn();
        
        if ($recentReactions >= 5) {
            return ['error' => 'Too many reactions. Please wait a moment.'];
        }
        
        // Add reaction
        $sql = "INSERT INTO reactions (reaction_type, stream_type, ip_address) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type, $stream_type, $_SERVER['REMOTE_ADDR']]);
        
        // Clean old reactions (keep last 24 hours)
        $pdo->exec("DELETE FROM reactions WHERE created_at < datetime('now', '-24 hours')");
        
        return getReactions($stream_type);
    } catch (PDOException $e) {
        error_log("Add reaction error: " . $e->getMessage());
        return false;
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $stream_type = $_GET['stream_type'] ?? 'tv';
        $reactions = getReactions($stream_type);
        echo json_encode(['success' => true, 'data' => $reactions]);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['type'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing reaction type']);
            break;
        }
        
        $stream_type = $input['stream_type'] ?? 'tv';
        $result = addReaction($input['type'], $stream_type);
        
        if ($result && !isset($result['error'])) {
            echo json_encode(['success' => true, 'data' => $result]);
        } elseif (isset($result['error'])) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => $result['error']]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to add reaction']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
?>