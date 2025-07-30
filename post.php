<?php
require_once 'config.php';

// Get post slug from URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: newsroom.php');
    exit;
}

// Get post details
$stmt = $pdo->prepare("
    SELECT bp.*, u.username as author_name, u.email as author_email
    FROM blog_posts bp 
    LEFT JOIN users u ON bp.author_id = u.id 
    WHERE bp.slug = ? AND bp.status = 'published'
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Increment view count
$stmt = $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
$stmt->execute([$post['id']]);

// Track page view
$stmt = $pdo->prepare("INSERT INTO page_views (page_url, ip_address, user_agent, referrer) VALUES (?, ?, ?, ?)");
$stmt->execute([
    $_SERVER['REQUEST_URI'],
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT'] ?? '',
    $_SERVER['HTTP_REFERER'] ?? ''
]);

// Get related posts
$stmt = $pdo->prepare("
    SELECT bp.*, u.username as author_name 
    FROM blog_posts bp 
    LEFT JOIN users u ON bp.author_id = u.id 
    WHERE bp.status = 'published' AND bp.id != ? 
    ORDER BY bp.published_at DESC 
    LIMIT 3
");
$stmt->execute([$post['id']]);
$related_posts = $stmt->fetchAll();

// Parse post images if any
$post_images = [];
if ($post['images']) {
    $post_images = json_decode($post['images'], true) ?? [];
}

$post_url = get_base_url() . '/post.php?slug=' . urlencode($post['slug']);
$post_title = htmlspecialchars($post['title']);
$post_brief = htmlspecialchars($post['brief'] ?: strip_tags(substr($post['content'], 0, 160)) . '...');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post_title; ?> - <?php echo $site_config['site_name']; ?></title>
    <meta name="description" content="<?php echo $post_brief; ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo $post_title; ?>">
    <meta property="og:description" content="<?php echo $post_brief; ?>">
    <meta property="og:image" content="<?php echo $post['featured_image'] ? htmlspecialchars($post['featured_image']) : get_base_url() . '/assets/images/oroma-social.jpg'; ?>">
    <meta property="og:url" content="<?php echo $post_url; ?>">
    <meta property="og:type" content="article">
    <meta property="article:published_time" content="<?php echo $post['published_at']; ?>">
    <meta property="article:author" content="<?php echo htmlspecialchars($post['author_name'] ?? 'Staff'); ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $post_title; ?>">
    <meta name="twitter:description" content="<?php echo $post_brief; ?>">
    <meta name="twitter:image" content="<?php echo $post['featured_image'] ? htmlspecialchars($post['featured_image']) : get_base_url() . '/assets/images/oroma-social.jpg'; ?>">
    
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
                <img src="assets/images/logo.svg" alt="Oroma TV" class="logo me-2">
                <div>
                    <div class="brand-name text-dark"><?php echo $site_config['site_name']; ?></div>
                    <div class="brand-tagline text-muted"><?php echo $site_config['site_tagline']; ?></div>
                </div>
            </div>
            
            <div class="navbar-nav ms-auto d-flex flex-row">
                <a class="nav-link me-3 text-dark" href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <a class="nav-link me-3 text-dark" href="newsroom.php">
                    <i class="fas fa-newspaper"></i> Newsroom
                </a>
                <a class="nav-link me-3 text-dark" href="about.php">
                    <i class="fas fa-info-circle"></i> About
                </a>
                <a class="nav-link text-dark" href="contact.php">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </div>
        </div>
    </header>

    <!-- Post Header -->
    <div class="post-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-light">Home</a></li>
                    <li class="breadcrumb-item"><a href="newsroom.php" class="text-light">Newsroom</a></li>
                    <li class="breadcrumb-item active text-light" aria-current="page"><?php echo $post_title; ?></li>
                </ol>
            </nav>
            
            <h1 class="display-4 mb-3"><?php echo $post_title; ?></h1>
            
            <?php if ($post['brief']): ?>
                <p class="lead"><?php echo htmlspecialchars($post['brief']); ?></p>
            <?php endif; ?>
            
            <div class="post-meta">
                <div class="row text-center">
                    <div class="col-md-3">
                        <i class="fas fa-user"></i>
                        <strong><?php echo htmlspecialchars($post['author_name'] ?? 'Staff'); ?></strong>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-clock"></i>
                        <strong><?php echo format_time_ago($post['published_at']); ?></strong>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-eye"></i>
                        <strong><?php echo number_format($post['views'] + 1); ?> views</strong>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-calendar"></i>
                        <strong><?php echo date('M j, Y', strtotime($post['published_at'])); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <article class="post-article">
                    <?php if ($post['featured_image']): ?>
                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                             alt="<?php echo $post_title; ?>" 
                             class="post-featured-image">
                    <?php endif; ?>
                    
                    <div class="post-content">
                        <?php echo $post['content']; ?>
                    </div>
                    
                    <?php if (!empty($post_images)): ?>
                        <div class="post-images">
                            <h4><i class="fas fa-images me-2"></i>Gallery</h4>
                            <div class="row">
                                <?php foreach ($post_images as $image): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="Post image" 
                                             class="img-fluid"
                                             onclick="openImageModal('<?php echo htmlspecialchars($image); ?>')">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Social Sharing -->
                    <div class="social-share">
                        <h5><i class="fas fa-share-alt me-2"></i>Share This Article</h5>
                        <a href="#" onclick="shareOnFacebook()" class="social-btn facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="#" onclick="shareOnTwitter()" class="social-btn twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <a href="#" onclick="shareOnWhatsApp()" class="social-btn whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </article>
            </div>
            
            <div class="col-lg-4">
                <!-- Related Posts -->
                <?php if (!empty($related_posts)): ?>
                    <div class="related-posts">
                        <h3><i class="fas fa-newspaper me-2"></i>Related News</h3>
                        <?php foreach ($related_posts as $related): ?>
                            <div class="blog-card mb-3">
                                <?php if ($related['featured_image']): ?>
                                    <div class="blog-card-image" style="height: 120px;">
                                        <img src="<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                             class="img-fluid">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="blog-card-content">
                                    <h6 class="blog-card-title">
                                        <a href="post.php?slug=<?php echo urlencode($related['slug']); ?>">
                                            <?php echo htmlspecialchars($related['title']); ?>
                                        </a>
                                    </h6>
                                    
                                    <div class="blog-card-meta">
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?php echo format_time_ago($related['published_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="newsroom.php" class="btn btn-outline-primary">
                            <i class="fas fa-newspaper"></i> View All News
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- WhatsApp Floating Button -->
    <div class="whatsapp-float">
        <a href="#" onclick="shareOnWhatsApp()" title="Share on WhatsApp">
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

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Full size image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        const postUrl = <?php echo json_encode($post_url); ?>;
        const postTitle = <?php echo json_encode($post_title); ?>;
        const postBrief = <?php echo json_encode($post_brief); ?>;
        
        function shareOnFacebook() {
            const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(postUrl)}`;
            window.open(url, '_blank', 'width=600,height=400');
        }
        
        function shareOnTwitter() {
            const text = `${postTitle} - ${postBrief}`;
            const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(postUrl)}`;
            window.open(url, '_blank', 'width=600,height=400');
        }
        
        function shareOnWhatsApp() {
            const text = `${postTitle}\n\n${postBrief}\n\nRead more: ${postUrl}`;
            const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
        }
        
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
    </script>
</body>
</html>