<?php
require_once 'config.php';

// Track page view
$stmt = $pdo->prepare("INSERT INTO page_views (page_url, ip_address, user_agent, referrer) VALUES (?, ?, ?, ?)");
$stmt->execute([
    $_SERVER['REQUEST_URI'],
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT'] ?? '',
    $_SERVER['HTTP_REFERER'] ?? ''
]);

// Get published blog posts
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT bp.*, u.username as author_name 
    FROM blog_posts bp 
    LEFT JOIN users u ON bp.author_id = u.id 
    WHERE bp.status = 'published' 
    ORDER BY bp.published_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$posts = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
$stmt->execute();
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_config['site_name']; ?> - Newsroom</title>
    <meta name="description" content="Latest news and updates from <?php echo $site_config['site_name']; ?>. Stay informed with Northern Uganda news.">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo $site_config['site_name']; ?> - Newsroom">
    <meta property="og:description" content="Latest news and updates from Northern Uganda">
    <meta property="og:image" content="<?php echo get_base_url(); ?>/assets/images/oroma-social.jpg">
    <meta property="og:url" content="<?php echo get_base_url(); ?>/newsroom.php">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/blog.css">
</head>
<body>
    <!-- Header -->
    <header class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <div class="navbar-brand d-flex align-items-center">
                <img src="assets/images/logo.png" alt="Oroma TV" class="logo">
            </div>
            
            <div class="navbar-nav ms-auto d-flex flex-row">
                <a class="nav-link me-3" href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <a class="nav-link" href="contact.php">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <div class="page-header bg-primary text-white py-5">
        <div class="container">
            <h1><i class="fas fa-newspaper me-3"></i>Newsroom</h1>
            <p class="lead">Stay updated with the latest news from Northern Uganda and beyond</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if (empty($posts)): ?>
            <div class="text-center py-5">
                <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                <h3>No News Yet</h3>
                <p class="text-muted">Check back soon for the latest updates!</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($posts as $post): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <article class="blog-card h-100">
                            <?php if ($post['featured_image']): ?>
                                <div class="blog-card-image">
                                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                         class="img-fluid">
                                </div>
                            <?php endif; ?>
                            
                            <div class="blog-card-content">
                                <h5 class="blog-card-title">
                                    <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h5>
                                
                                <?php if ($post['brief']): ?>
                                    <p class="blog-card-brief">
                                        <?php echo htmlspecialchars($post['brief']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="blog-card-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Staff'); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock"></i> <?php echo format_time_ago($post['published_at']); ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?> views
                                    </small>
                                </div>
                                
                                <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                   class="btn btn-primary btn-sm mt-3">
                                    Read More <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Blog pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- WhatsApp Floating Button -->
    <div class="whatsapp-float">
        <a href="#" id="whatsappShare" title="Share on WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">Powered by <strong>Kakebe Technologies Limited</strong></p>
            <small class="text-muted">© <?php echo date('Y'); ?> <?php echo $site_config['site_name']; ?>. All rights reserved.</small>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // WhatsApp share functionality
        document.getElementById('whatsappShare').addEventListener('click', function(e) {
            e.preventDefault();
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('Check out the latest news from <?php echo $site_config['site_name']; ?>: ');
            window.open(`https://wa.me/?text=${text}${url}`, '_blank');
        });
    </script>
</body>
</html>