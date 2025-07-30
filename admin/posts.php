<?php
session_start();
require_once '../config.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$message = '';

// Handle post actions
if ($_GET['action'] ?? '' === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = 'Post deleted successfully';
}

if ($_GET['action'] ?? '' === 'publish' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE blog_posts SET status = 'published', published_at = NOW() WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = 'Post published successfully';
}

if ($_GET['action'] ?? '' === 'unpublish' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE blog_posts SET status = 'draft' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $message = 'Post unpublished successfully';
}

// Get posts with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(title ILIKE ? OR content ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get posts
$stmt = $pdo->prepare("
    SELECT bp.*, u.username as author_name 
    FROM blog_posts bp 
    LEFT JOIN users u ON bp.author_id = u.id 
    $where_clause
    ORDER BY bp.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, $limit, $offset]);
$posts = $stmt->fetchAll();

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts bp $where_clause");
$stmt->execute($params);
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - <?php echo $site_config['site_name']; ?> Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-red: #8B0000;
            --primary-red-light: #B71C1C;
        }
        
        .sidebar {
            background: var(--primary-red);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .content-area {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .posts-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: var(--primary-red);
            border-color: var(--primary-red);
        }
        
        .btn-primary:hover {
            background: var(--primary-red-light);
            border-color: var(--primary-red-light);
        }
        
        .action-btn {
            padding: 0.25rem 0.5rem;
            margin: 0.1rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="p-3">
                <h5><i class="fas fa-tv me-2"></i><?php echo $site_config['site_name']; ?></h5>
                <small>Admin Panel</small>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="nav-link active" href="posts.php">
                    <i class="fas fa-newspaper me-2"></i> Manage Posts
                </a>
                <a class="nav-link" href="new-post.php">
                    <i class="fas fa-plus me-2"></i> New Post
                </a>
                <a class="nav-link" href="analytics.php">
                    <i class="fas fa-chart-bar me-2"></i> Analytics
                </a>
                <hr class="text-white-50">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i> View Site
                </a>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-newspaper me-2"></i>Manage Posts</h2>
                <a href="new-post.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> New Post
                </a>
            </div>
            
            <!-- Message -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   placeholder="Search posts..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-3">
                            <a href="posts.php" class="btn btn-outline-secondary">
                                <i class="fas fa-refresh"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Posts Table -->
            <div class="posts-table">
                <?php if (empty($posts)): ?>
                    <div class="text-center p-5">
                        <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                        <h4>No Posts Found</h4>
                        <p class="text-muted">Start by creating your first post!</p>
                        <a href="new-post.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Post
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Views</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                <?php if ($post['brief']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($post['brief'], 0, 100)); ?>...</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                                <br><?php echo date('g:i A', strtotime($post['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group-vertical" role="group">
                                                <a href="edit-post.php?id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-outline-primary action-btn">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                
                                                <?php if ($post['status'] === 'published'): ?>
                                                    <a href="../post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                                       target="_blank"
                                                       class="btn btn-outline-info action-btn">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <a href="?action=unpublish&id=<?php echo $post['id']; ?>" 
                                                       class="btn btn-outline-warning action-btn"
                                                       onclick="return confirm('Unpublish this post?')">
                                                        <i class="fas fa-eye-slash"></i> Unpublish
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?action=publish&id=<?php echo $post['id']; ?>" 
                                                       class="btn btn-outline-success action-btn"
                                                       onclick="return confirm('Publish this post?')">
                                                        <i class="fas fa-upload"></i> Publish
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="?action=delete&id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-outline-danger action-btn"
                                                   onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center p-3">
                            <nav aria-label="Posts pagination">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Summary -->
            <div class="mt-3 text-muted">
                Showing <?php echo count($posts); ?> of <?php echo number_format($total_posts); ?> posts
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>