<?php
session_start();
require_once '../config.php';

// Simple authentication for demo
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_POST['password'] ?? '' === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    }
    
    if (isset($_POST['password'])) {
        $error = 'Invalid password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - <?php echo $site_config['site_name']; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f8f9fa; }
            .login-container { max-width: 400px; margin: 10vh auto; }
            .login-card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="login-container">
                <div class="card login-card">
                    <div class="card-header bg-danger text-white text-center">
                        <h4><i class="fas fa-shield-alt"></i> Admin Access</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Demo password: admin123</small>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
$published_posts = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'");
$draft_posts = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(views) FROM blog_posts");
$total_views = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM page_views WHERE created_at >= NOW() - INTERVAL '24 hours'");
$daily_views = $stmt->fetchColumn();

// Get recent posts
$stmt = $pdo->prepare("
    SELECT bp.*, u.username as author_name 
    FROM blog_posts bp 
    LEFT JOIN users u ON bp.author_id = u.id 
    ORDER BY bp.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $site_config['site_name']; ?></title>
    
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
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-red);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-red);
        }
        
        .content-area {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .recent-posts .list-group-item {
            border-left: 3px solid var(--primary-red);
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
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="nav-link" href="posts.php">
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
                <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                <div class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    <?php echo date('F j, Y g:i A'); ?>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?php echo number_format($published_posts); ?></div>
                                <div class="text-muted">Published Posts</div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-newspaper fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?php echo number_format($draft_posts); ?></div>
                                <div class="text-muted">Draft Posts</div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-edit fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?php echo number_format($total_views); ?></div>
                                <div class="text-muted">Total Views</div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-eye fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-number"><?php echo number_format($daily_views); ?></div>
                                <div class="text-muted">Today's Views</div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Posts -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Posts</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_posts)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-newspaper fa-3x mb-3"></i>
                                    <p>No posts yet. <a href="new-post.php">Create your first post</a></p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush recent-posts">
                                    <?php foreach ($recent_posts as $post): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($post['title']); ?>
                                                        </a>
                                                    </h6>
                                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($post['brief'] ?: 'No brief provided'); ?></p>
                                                    <small class="text-muted">
                                                        By <?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?> • 
                                                        <?php echo format_time_ago($post['created_at']); ?>
                                                    </small>
                                                </div>
                                                <div class="ms-2">
                                                    <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($post['status']); ?>
                                                    </span>
                                                    <div class="text-muted small mt-1">
                                                        <i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white text-center">
                            <a href="posts.php" class="btn btn-outline-primary">
                                <i class="fas fa-list me-1"></i> View All Posts
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="new-post.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i> Create New Post
                                </a>
                                <a href="posts.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-list me-2"></i> Manage Posts
                                </a>
                                <a href="../newsroom.php" target="_blank" class="btn btn-outline-info">
                                    <i class="fas fa-external-link-alt me-2"></i> View Newsroom
                                </a>
                                <a href="../index.php" target="_blank" class="btn btn-outline-success">
                                    <i class="fas fa-home me-2"></i> View Homepage
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Info</h5>
                        </div>
                        <div class="card-body">
                            <small class="text-muted">
                                <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                                <strong>Server:</strong> <?php echo $_SERVER['HTTP_HOST']; ?><br>
                                <strong>Last Login:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>