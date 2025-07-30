<?php
require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getStreamStatus($type = 'all') {
    global $pdo;
    
    try {
        if ($type === 'all') {
            $sql = "SELECT * FROM stream_stats ORDER BY recorded_at DESC LIMIT 2";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        } else {
            $sql = "SELECT * FROM stream_stats WHERE stream_type = ? ORDER BY recorded_at DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$type]);
        }
        
        $results = $stmt->fetchAll();
        
        if ($type === 'all') {
            $status = [
                'tv' => [
                    'status' => 'online',
                    'quality' => 'HD',
                    'latency' => '2.3s',
                    'viewers' => 245,
                    'last_updated' => time()
                ],
                'radio' => [
                    'status' => 'online',
                    'quality' => '128kbps',
                    'latency' => '1.8s',
                    'listeners' => 180,
                    'current_song' => 'Live Broadcasting',
                    'last_updated' => time()
                ]
            ];
            
            // Update with database values if available
            foreach ($results as $result) {
                $streamType = $result['stream_type'];
                if (isset($status[$streamType])) {
                    $status[$streamType]['status'] = $result['status'];
                    $status[$streamType]['quality'] = $result['quality'] ?: $status[$streamType]['quality'];
                    $status[$streamType]['latency'] = $result['latency'] ?: $status[$streamType]['latency'];
                    $status[$streamType]['viewers'] = $result['viewers_count'];
                    $status[$streamType]['last_updated'] = strtotime($result['recorded_at']);
                }
            }
            
            return $status;
        } else {
            if (empty($results)) {
                // Default status if no data
                return [
                    'status' => 'online',
                    'quality' => $type === 'tv' ? 'HD' : '128kbps',
                    'latency' => $type === 'tv' ? '2.3s' : '1.8s',
                    'viewers' => $type === 'tv' ? 245 : 180,
                    'last_updated' => time()
                ];
            }
            
            $result = $results[0];
            return [
                'status' => $result['status'],
                'quality' => $result['quality'],
                'latency' => $result['latency'],
                'viewers' => $result['viewers_count'],
                'last_updated' => strtotime($result['recorded_at'])
            ];
        }
    } catch (PDOException $e) {
        error_log("Stream status error: " . $e->getMessage());
        // Return default status on error
        if ($type === 'all') {
            return [
                'tv' => [
                    'status' => 'online',
                    'quality' => 'HD',
                    'latency' => '2.3s',
                    'viewers' => 245,
                    'last_updated' => time()
                ],
                'radio' => [
                    'status' => 'online',
                    'quality' => '128kbps',
                    'latency' => '1.8s',
                    'listeners' => 180,
                    'current_song' => 'Live Broadcasting',
                    'last_updated' => time()
                ]
            ];
        } else {
            return [
                'status' => 'online',
                'quality' => $type === 'tv' ? 'HD' : '128kbps',
                'latency' => $type === 'tv' ? '2.3s' : '1.8s',
                'viewers' => $type === 'tv' ? 245 : 180,
                'last_updated' => time()
            ];
        }
    }
}

function updateStreamStatus($type, $updates) {
    global $pdo;
    
    $allowedTypes = ['tv', 'radio'];
    if (!in_array($type, $allowedTypes)) {
        return false;
    }
    
    try {
        $sql = "INSERT INTO stream_stats (stream_type, viewers_count, status, quality, latency, bitrate) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $type,
            $updates['viewers'] ?? 0,
            $updates['status'] ?? 'online',
            $updates['quality'] ?? null,
            $updates['latency'] ?? null,
            $updates['bitrate'] ?? null
        ]);
        
        return getStreamStatus($type);
    } catch (PDOException $e) {
        error_log("Update stream status error: " . $e->getMessage());
        return false;
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $type = $_GET['type'] ?? 'all';
        $status = getStreamStatus($type);
        
        if ($status) {
            echo json_encode(['success' => true, 'data' => $status]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Stream type not found']);
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['type'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing stream type']);
            break;
        }
        
        $result = updateStreamStatus($input['type'], $input);
        
        if ($result) {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to update stream status']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
?>