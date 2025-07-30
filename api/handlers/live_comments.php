<?php
/**
 * Live Comments API Handler
 */

function handleLiveCommentsRequest($db, $method, $id, $action) {
    switch($method) {
        case 'GET':
            if ($id) {
                getLiveCommentsByStream($db, $id);
            } else {
                getAllLiveComments($db);
            }
            break;
            
        case 'POST':
            createLiveComment($db);
            break;
            
        case 'DELETE':
            if ($action === 'cleanup') {
                cleanupOldComments($db);
            } elseif ($id) {
                deleteLiveComment($db, $id);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
}

function getAllLiveComments($db) {
    try {
        // Clean old comments first
        cleanOldComments($db);
        
        $stmt = $db->prepare("
            SELECT * FROM live_comments 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC 
            LIMIT 100
        ");
        $stmt->execute();
        $comments = $stmt->fetchAll();
        
        sendJsonResponse($comments);
    } catch(Exception $e) {
        error_log("Get comments error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch comments'], 500);
    }
}

function getLiveCommentsByStream($db, $streamType) {
    try {
        // Clean old comments first
        cleanOldComments($db);
        
        $stmt = $db->prepare("
            SELECT * FROM live_comments 
            WHERE stream_type = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$streamType]);
        $comments = $stmt->fetchAll();
        
        sendJsonResponse($comments);
    } catch(Exception $e) {
        error_log("Get stream comments error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch comments'], 500);
    }
}

function createLiveComment($db) {
    try {
        $input = getJsonInput();
        
        // Validate input
        if (empty($input['comment']) || empty($input['username'])) {
            sendJsonResponse(['error' => 'Comment and username are required'], 400);
        }
        
        $streamType = $input['stream_type'] ?? 'tv';
        $username = sanitizeInput($input['username']);
        $comment = sanitizeInput($input['comment']);
        $userSession = $input['user_session'] ?? getUserSession();
        
        // Validate lengths
        if (strlen($username) > 50) {
            sendJsonResponse(['error' => 'Username too long'], 400);
        }
        
        if (strlen($comment) > 500) {
            sendJsonResponse(['error' => 'Comment too long'], 400);
        }
        
        // Rate limiting: max 1 comment per user per 10 seconds
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM live_comments 
            WHERE user_session = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
        ");
        $stmt->execute([$userSession]);
        $recent = $stmt->fetch();
        
        if ($recent['count'] > 0) {
            sendJsonResponse(['error' => 'Please wait before sending another message'], 429);
        }
        
        // Filter inappropriate content (basic)
        $bannedWords = ['spam', 'scam', 'hate', 'abuse']; // Add more as needed
        $lowerComment = strtolower($comment);
        foreach ($bannedWords as $word) {
            if (strpos($lowerComment, $word) !== false) {
                sendJsonResponse(['error' => 'Message contains inappropriate content'], 400);
            }
        }
        
        // Insert comment
        $stmt = $db->prepare("
            INSERT INTO live_comments (id, stream_type, username, comment, user_session) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            generateUUID(),
            $streamType,
            $username,
            $comment,
            $userSession
        ]);
        
        // Log analytics
        logAnalytics($db, 'live_comment', [
            'stream_type' => $streamType,
            'username' => $username,
            'comment_length' => strlen($comment)
        ], $userSession);
        
        sendJsonResponse(['success' => true, 'id' => $db->lastInsertId()], 201);
        
    } catch(Exception $e) {
        error_log("Create comment error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to create comment'], 500);
    }
}

function deleteLiveComment($db, $id) {
    try {
        $stmt = $db->prepare("DELETE FROM live_comments WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendJsonResponse(['success' => true]);
        } else {
            sendJsonResponse(['error' => 'Comment not found'], 404);
        }
    } catch(Exception $e) {
        error_log("Delete comment error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to delete comment'], 500);
    }
}

function cleanupOldComments($db) {
    try {
        cleanOldComments($db);
        sendJsonResponse(['success' => true, 'message' => 'Old comments cleaned up']);
    } catch(Exception $e) {
        error_log("Cleanup comments error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to cleanup comments'], 500);
    }
}
?>