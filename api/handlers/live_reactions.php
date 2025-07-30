<?php
/**
 * Live Reactions API Handler
 */

function handleLiveReactionsRequest($db, $method, $id, $action) {
    switch($method) {
        case 'GET':
            if ($id) {
                getLiveReactionsByStream($db, $id);
            } else {
                getAllLiveReactions($db);
            }
            break;
            
        case 'POST':
            createLiveReaction($db);
            break;
            
        case 'DELETE':
            if ($action === 'cleanup') {
                cleanupOldReactions($db);
            } elseif ($id) {
                deleteLiveReaction($db, $id);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
}

function getAllLiveReactions($db) {
    try {
        // Clean old reactions first
        cleanOldReactions($db);
        
        $stmt = $db->prepare("
            SELECT * FROM live_reactions 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute();
        $reactions = $stmt->fetchAll();
        
        sendJsonResponse($reactions);
    } catch(Exception $e) {
        error_log("Get reactions error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch reactions'], 500);
    }
}

function getLiveReactionsByStream($db, $streamType) {
    try {
        // Clean old reactions first
        cleanOldReactions($db);
        
        $stmt = $db->prepare("
            SELECT * FROM live_reactions 
            WHERE stream_type = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$streamType]);
        $reactions = $stmt->fetchAll();
        
        sendJsonResponse($reactions);
    } catch(Exception $e) {
        error_log("Get stream reactions error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch reactions'], 500);
    }
}

function createLiveReaction($db) {
    try {
        $input = getJsonInput();
        
        // Validate input
        if (empty($input['reaction_type'])) {
            sendJsonResponse(['error' => 'Reaction type is required'], 400);
        }
        
        $streamType = $input['stream_type'] ?? 'tv';
        $reactionType = sanitizeInput($input['reaction_type']);
        $userSession = $input['user_session'] ?? getUserSession();
        
        // Validate reaction type (emoji)
        $allowedReactions = ['❤️', '👍', '👀', '👏', '🔥', '🎵', '🎧', '💃', '🎤', '🔊'];
        if (!in_array($reactionType, $allowedReactions)) {
            sendJsonResponse(['error' => 'Invalid reaction type'], 400);
        }
        
        // Rate limiting: max 1 reaction per user per 2 seconds
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM live_reactions 
            WHERE user_session = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 2 SECOND)
        ");
        $stmt->execute([$userSession]);
        $recent = $stmt->fetch();
        
        if ($recent['count'] > 0) {
            sendJsonResponse(['error' => 'Please wait before sending another reaction'], 429);
        }
        
        // Insert reaction
        $stmt = $db->prepare("
            INSERT INTO live_reactions (id, stream_type, reaction_type, user_session) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            generateUUID(),
            $streamType,
            $reactionType,
            $userSession
        ]);
        
        // Log analytics
        logAnalytics($db, 'live_reaction', [
            'stream_type' => $streamType,
            'reaction_type' => $reactionType
        ], $userSession);
        
        sendJsonResponse(['success' => true, 'id' => $db->lastInsertId()], 201);
        
    } catch(Exception $e) {
        error_log("Create reaction error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to create reaction'], 500);
    }
}

function deleteLiveReaction($db, $id) {
    try {
        $stmt = $db->prepare("DELETE FROM live_reactions WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendJsonResponse(['success' => true]);
        } else {
            sendJsonResponse(['error' => 'Reaction not found'], 404);
        }
    } catch(Exception $e) {
        error_log("Delete reaction error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to delete reaction'], 500);
    }
}

function cleanupOldReactions($db) {
    try {
        cleanOldReactions($db);
        sendJsonResponse(['success' => true, 'message' => 'Old reactions cleaned up']);
    } catch(Exception $e) {
        error_log("Cleanup reactions error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to cleanup reactions'], 500);
    }
}
?>