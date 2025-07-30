<?php
/**
 * News API Handler
 */

function handleNewsRequest($db, $method, $id, $action) {
    switch($method) {
        case 'GET':
            if ($id) {
                if ($action === 'view') {
                    incrementNewsView($db, $id);
                }
                getNewsArticle($db, $id);
            } else {
                getAllNews($db);
            }
            break;
            
        case 'POST':
            createNewsArticle($db);
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                updateNewsArticle($db, $id);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteNewsArticle($db, $id);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
}

function getAllNews($db) {
    try {
        $published = $_GET['published'] ?? '1';
        $category = $_GET['category'] ?? null;
        $featured = $_GET['featured'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "SELECT * FROM news WHERE 1=1";
        $params = [];
        
        if ($published !== 'all') {
            $sql .= " AND published = ?";
            $params[] = (int)$published;
        }
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($featured !== null) {
            $sql .= " AND featured = ?";
            $params[] = (int)$featured;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $news = $stmt->fetchAll();
        
        sendJsonResponse($news);
    } catch(Exception $e) {
        error_log("Get news error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch news'], 500);
    }
}

function getNewsArticle($db, $id) {
    try {
        $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        if ($article) {
            sendJsonResponse($article);
        } else {
            sendJsonResponse(['error' => 'News article not found'], 404);
        }
    } catch(Exception $e) {
        error_log("Get news article error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to fetch news article'], 500);
    }
}

function incrementNewsView($db, $id) {
    try {
        $stmt = $db->prepare("UPDATE news SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log analytics
        logAnalytics($db, 'news_view', ['article_id' => $id], getUserSession());
        
        sendJsonResponse(['success' => true]);
    } catch(Exception $e) {
        error_log("Increment news view error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to increment view count'], 500);
    }
}

function createNewsArticle($db) {
    try {
        // Require admin authentication
        requireAdmin($db);
        
        $input = getJsonInput();
        
        // Validate required fields
        if (empty($input['title']) || empty($input['content'])) {
            sendJsonResponse(['error' => 'Title and content are required'], 400);
        }
        
        $id = generateUUID();
        $title = sanitizeInput($input['title']);
        $content = sanitizeInput($input['content']);
        $summary = isset($input['summary']) ? sanitizeInput($input['summary']) : null;
        $author = isset($input['author']) ? sanitizeInput($input['author']) : null;
        $category = $input['category'] ?? 'local';
        $imageUrl = isset($input['image_url']) ? sanitizeInput($input['image_url']) : null;
        $published = isset($input['published']) ? (bool)$input['published'] : false;
        $featured = isset($input['featured']) ? (bool)$input['featured'] : false;
        
        // Handle file upload if present
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $imageUrl = uploadFile($_FILES['image']);
            } catch(Exception $e) {
                sendJsonResponse(['error' => 'Image upload failed: ' . $e->getMessage()], 400);
            }
        }
        
        // Auto-generate summary if not provided
        if (!$summary) {
            $summary = substr(strip_tags($content), 0, 200) . '...';
        }
        
        $stmt = $db->prepare("
            INSERT INTO news (id, title, content, summary, author, category, image_url, published, featured) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id, $title, $content, $summary, $author, $category, $imageUrl, $published, $featured
        ]);
        
        // Log analytics
        logAnalytics($db, 'news_created', [
            'article_id' => $id,
            'title' => $title,
            'category' => $category,
            'published' => $published
        ], getUserSession());
        
        sendJsonResponse(['success' => true, 'id' => $id], 201);
        
    } catch(Exception $e) {
        error_log("Create news error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to create news article'], 500);
    }
}

function updateNewsArticle($db, $id) {
    try {
        // Require admin authentication
        requireAdmin($db);
        
        $input = getJsonInput();
        
        // Build update query dynamically
        $updateFields = [];
        $params = [];
        
        $allowedFields = ['title', 'content', 'summary', 'author', 'category', 'image_url', 'published', 'featured'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                if (in_array($field, ['published', 'featured'])) {
                    $params[] = (bool)$input[$field];
                } else {
                    $params[] = sanitizeInput($input[$field]);
                }
            }
        }
        
        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $imageUrl = uploadFile($_FILES['image']);
                $updateFields[] = "image_url = ?";
                $params[] = $imageUrl;
            } catch(Exception $e) {
                sendJsonResponse(['error' => 'Image upload failed: ' . $e->getMessage()], 400);
            }
        }
        
        if (empty($updateFields)) {
            sendJsonResponse(['error' => 'No valid fields to update'], 400);
        }
        
        $params[] = $id;
        
        $sql = "UPDATE news SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            // Get updated record
            $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
            $stmt->execute([$id]);
            $updated = $stmt->fetch();
            
            sendJsonResponse(['success' => true, 'data' => $updated]);
        } else {
            sendJsonResponse(['error' => 'News article not found'], 404);
        }
        
    } catch(Exception $e) {
        error_log("Update news error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to update news article'], 500);
    }
}

function deleteNewsArticle($db, $id) {
    try {
        // Require admin authentication
        requireAdmin($db);
        
        // Get article to delete associated image
        $stmt = $db->prepare("SELECT image_url FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            // Delete associated image file if exists
            if ($article && $article['image_url'] && file_exists('.' . $article['image_url'])) {
                unlink('.' . $article['image_url']);
            }
            
            sendJsonResponse(['success' => true, 'message' => 'News article deleted']);
        } else {
            sendJsonResponse(['error' => 'News article not found'], 404);
        }
    } catch(Exception $e) {
        error_log("Delete news error: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to delete news article'], 500);
    }
}
?>