<?php
/**
 * Active Users API Handler
 */

function handleActiveUsersRequest($db, $method, $id, $action) {
    switch($method) {
        case 'GET':
            if ($action === 'count') {
                getActiveUserCount($db, $id);
            } else {
                getAllActiveUsers($db);
            }
            break;
            
        case 'POST':
            if ($action === 'join') {
                joinActiveUser($db);
            } elseif ($action === 'heartbeat') {
                updateHeartbeat($db);
            } elseif ($action === 'leave') {
                leaveActiveUser($db);
            } else {
                updateActiveUser($db);
            }
            break;
            
        case 'DELETE':
            if ($action === 'cleanup') {
                cleanupInactiveUsers($db);
            } elseif ($id) {
                removeActiveUser($db, $id);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
}

function getAllActiveUsers($db) {
    try {
        // Clean inactive users first
        cleanInactiveUsers($db);
        
        $stmt = $db->prepare("
            SELECT * FROM active_users 
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY last_activity DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        sendJsonResponse($users);
    } catch(Exception $e) {
        error_log("Get active users error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch active users'], 500);
    }
}

function getActiveUserCount($db, $streamType) {
    try {
        // Clean inactive users first
        cleanInactiveUsers($db);
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM active_users 
            WHERE stream_type = ? 
            AND last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute([$streamType]);
        $result = $stmt->fetch();
        
        sendJsonResponse(['count' => (int)$result['count']]);
    } catch(Exception $e) {
        error_log("Get user count error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to get user count'], 500);
    }
}

function joinActiveUser($db) {
    try {
        $input = getJsonInput();
        
        $streamType = $input['stream_type'] ?? 'tv';
        $userSession = $input['user_session'] ?? getUserSession();
        $username = isset($input['username']) ? sanitizeInput($input['username']) : null;
        
        // Insert or update user
        $stmt = $db->prepare("
            INSERT INTO active_users (id, user_session, stream_type, username, last_activity) 
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                stream_type = VALUES(stream_type),
                username = VALUES(username),
                last_activity = NOW()
        ");
        
        $id = generateUUID();
        $stmt->execute([$id, $userSession, $streamType, $username]);
        
        // Log analytics
        logAnalytics($db, 'user_join', [
            'stream_type' => $streamType,
            'username' => $username
        ], $userSession);
        
        sendJsonResponse(['success' => true, 'id' => $id, 'session' => $userSession], 201);
        
    } catch(Exception $e) {
        error_log("Join user error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to join user'], 500);
    }
}

function updateActiveUser($db) {
    try {
        $input = getJsonInput();
        
        $userSession = $input['user_session'] ?? getUserSession();
        $streamType = $input['stream_type'] ?? 'tv';
        
        // Update last activity
        $stmt = $db->prepare("
            UPDATE active_users 
            SET last_activity = NOW(), stream_type = ?
            WHERE user_session = ?
        ");
        $stmt->execute([$streamType, $userSession]);
        
        sendJsonResponse(['success' => true]);
        
    } catch(Exception $e) {
        error_log("Update user error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to update user'], 500);
    }
}

function updateHeartbeat($db) {
    try {
        $input = getJsonInput();
        
        $userSession = $input['user_session'] ?? getUserSession();
        $streamType = $input['stream_type'] ?? 'tv';
        
        // Update heartbeat
        $stmt = $db->prepare("
            UPDATE active_users 
            SET last_activity = NOW(), stream_type = ?
            WHERE user_session = ?
        ");
        $stmt->execute([$streamType, $userSession]);
        
        if ($stmt->rowCount() === 0) {
            // User not found, create new entry
            $stmt = $db->prepare("
                INSERT INTO active_users (id, user_session, stream_type, last_activity) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([generateUUID(), $userSession, $streamType]);
        }
        
        sendJsonResponse(['success' => true]);
        
    } catch(Exception $e) {
        error_log("Heartbeat error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to update heartbeat'], 500);
    }
}

function leaveActiveUser($db) {
    try {
        $input = getJsonInput();
        
        $userSession = $input['user_session'] ?? getUserSession();
        
        $stmt = $db->prepare("DELETE FROM active_users WHERE user_session = ?");
        $stmt->execute([$userSession]);
        
        // Log analytics
        logAnalytics($db, 'user_leave', [], $userSession);
        
        sendJsonResponse(['success' => true]);
        
    } catch(Exception $e) {
        error_log("Leave user error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to remove user'], 500);
    }
}

function removeActiveUser($db, $id) {
    try {
        $stmt = $db->prepare("DELETE FROM active_users WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendJsonResponse(['success' => true]);
        } else {
            sendJsonResponse(['error' => 'User not found'], 404);
        }
    } catch(Exception $e) {
        error_log("Remove user error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to remove user'], 500);
    }
}

function cleanupInactiveUsers($db) {
    try {
        cleanInactiveUsers($db);
        sendJsonResponse(['success' => true, 'message' => 'Inactive users cleaned up']);
    } catch(Exception $e) {
        error_log("Cleanup users error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to cleanup users'], 500);
    }
}
?>