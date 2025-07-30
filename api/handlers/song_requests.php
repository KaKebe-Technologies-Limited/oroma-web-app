<?php
/**
 * Song Requests API Handler
 */

function handleSongRequestsRequest($db, $method, $id, $action) {
    switch($method) {
        case 'GET':
            if ($id) {
                getSongRequest($db, $id);
            } else {
                getAllSongRequests($db);
            }
            break;
            
        case 'POST':
            createSongRequest($db);
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                updateSongRequest($db, $id);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteSongRequest($db, $id);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
}

function getAllSongRequests($db) {
    try {
        // Default to recent requests, limit for performance
        $limit = $_GET['limit'] ?? 50;
        $status = $_GET['status'] ?? null;
        
        $sql = "SELECT * FROM song_requests";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll();
        
        sendJsonResponse($requests);
    } catch(Exception $e) {
        error_log("Get song requests error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch song requests'], 500);
    }
}

function getSongRequest($db, $id) {
    try {
        $stmt = $db->prepare("SELECT * FROM song_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
        if ($request) {
            sendJsonResponse($request);
        } else {
            sendJsonResponse(['error' => 'Song request not found'], 404);
        }
    } catch(Exception $e) {
        error_log("Get song request error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch song request'], 500);
    }
}

function createSongRequest($db) {
    try {
        $input = getJsonInput();
        
        // Validate required fields
        if (empty($input['song_title']) || empty($input['requester_name'])) {
            sendJsonResponse(['error' => 'Song title and requester name are required'], 400);
        }
        
        $songTitle = sanitizeInput($input['song_title']);
        $artist = isset($input['artist']) ? sanitizeInput($input['artist']) : null;
        $requesterName = sanitizeInput($input['requester_name']);
        $requesterPhone = isset($input['requester_phone']) ? sanitizeInput($input['requester_phone']) : null;
        $message = isset($input['message']) ? sanitizeInput($input['message']) : null;
        
        // Validate lengths
        if (strlen($songTitle) > 255 || strlen($requesterName) > 255) {
            sendJsonResponse(['error' => 'Song title or requester name too long'], 400);
        }
        
        // Rate limiting: max 3 requests per IP per hour
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM song_requests sr
            LEFT JOIN analytics a ON a.user_session = ?
            WHERE a.ip_address = ? 
            AND sr.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([getUserSession(), $clientIP]);
        $recent = $stmt->fetch();
        
        if ($recent['count'] >= 3) {
            sendJsonResponse(['error' => 'Too many requests. Please wait before submitting another.'], 429);
        }
        
        // Insert song request
        $stmt = $db->prepare("
            INSERT INTO song_requests (id, song_title, artist, requester_name, requester_phone, message, status, priority) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', 0)
        ");
        
        $id = generateUUID();
        $stmt->execute([
            $id,
            $songTitle,
            $artist,
            $requesterName,
            $requesterPhone,
            $message
        ]);
        
        // Log analytics
        logAnalytics($db, 'song_request', [
            'song_title' => $songTitle,
            'artist' => $artist,
            'requester_name' => $requesterName
        ], getUserSession());
        
        sendJsonResponse([
            'success' => true, 
            'id' => $id,
            'message' => 'Song request submitted successfully!'
        ], 201);
        
    } catch(Exception $e) {
        error_log("Create song request error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to create song request'], 500);
    }
}

function updateSongRequest($db, $id) {
    try {
        // Require admin authentication for updates
        requireAdmin($db);
        
        $input = getJsonInput();
        
        // Build update query dynamically based on provided fields
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['status', 'priority', 'artist', 'message'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = sanitizeInput($input[$field]);
            }
        }
        
        if (empty($updateFields)) {
            sendJsonResponse(['error' => 'No valid fields to update'], 400);
        }
        
        $params[] = $id; // Add ID for WHERE clause
        
        $sql = "UPDATE song_requests SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            // Get updated record
            $stmt = $db->prepare("SELECT * FROM song_requests WHERE id = ?");
            $stmt->execute([$id]);
            $updated = $stmt->fetch();
            
            sendJsonResponse(['success' => true, 'data' => $updated]);
        } else {
            sendJsonResponse(['error' => 'Song request not found'], 404);
        }
        
    } catch(Exception $e) {
        error_log("Update song request error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to update song request'], 500);
    }
}

function deleteSongRequest($db, $id) {
    try {
        // Require admin authentication for deletion
        requireAdmin($db);
        
        $stmt = $db->prepare("DELETE FROM song_requests WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            sendJsonResponse(['success' => true, 'message' => 'Song request deleted']);
        } else {
            sendJsonResponse(['error' => 'Song request not found'], 404);
        }
    } catch(Exception $e) {
        error_log("Delete song request error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to delete song request'], 500);
    }
}
?>