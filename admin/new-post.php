<?php
session_start();
require_once '../config.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

if ($_POST) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $brief = trim($_POST['brief'] ?? '');
    $content = $_POST['content'] ?? '';
    $featured_image = trim($_POST['featured_image'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $images = [];
    
    // Parse additional images
    if (!empty($_POST['images'])) {
        $images = array_filter(array_map('trim', explode("\n", $_POST['images'])));
    }
    
    // Generate slug if not provided
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    
    // Validation
    if (empty($title)) {
        $error = 'Title is required';
    } elseif (empty($content)) {
        $error = 'Content is required';
    } else {
        try {
            // Check if slug exists
            $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . time();
            }
            
            // Insert post
            $stmt = $pdo->prepare("
                INSERT INTO blog_posts (title, slug, brief, content, featured_image, images, status, published_at, author_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
            $author_id = 1; // Default admin user
            
            $stmt->execute([
                $title,
                $slug,
                $brief,
                $content,
                $featured_image,
                json_encode($images),
                $status,
                $published_at,
                $author_id
            ]);
            
            $success = 'Post created successfully!';
            
            // Clear form on success
            if ($success) {
                $_POST = [];
            }
            
        } catch (Exception $e) {
            $error = 'Error creating post: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Post - <?php echo $site_config['site_name']; ?> Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- TinyMCE Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
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
        
        .form-card {
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
                <a class="nav-link" href="posts.php">
                    <i class="fas fa-newspaper me-2"></i> Manage Posts
                </a>
                <a class="nav-link active" href="new-post.php">
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
                <h2><i class="fas fa-plus me-2"></i>Create New Post</h2>
                <a href="posts.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Posts
                </a>
            </div>
            
            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Post Form -->
            <div class="form-card">
                <form method="POST" class="p-4">
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Title -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="title" 
                                       name="title" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <!-- Slug -->
                            <div class="mb-3">
                                <label for="slug" class="form-label">URL Slug</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="slug" 
                                       name="slug" 
                                       value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                                       placeholder="Auto-generated from title">
                                <small class="text-muted">Leave empty to auto-generate from title</small>
                            </div>
                            
                            <!-- Brief -->
                            <div class="mb-3">
                                <label for="brief" class="form-label">Brief Description</label>
                                <textarea class="form-control" 
                                          id="brief" 
                                          name="brief" 
                                          rows="3"
                                          placeholder="Short summary for social media and previews"><?php echo htmlspecialchars($_POST['brief'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Content -->
                            <div class="mb-3">
                                <label for="content" class="form-label">Content *</label>
                                <textarea class="form-control" 
                                          id="content" 
                                          name="content" 
                                          rows="15"
                                          required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Status -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo ($_POST['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                            </div>
                            
                            <!-- Featured Image -->
                            <div class="mb-3">
                                <label for="featured_image" class="form-label">Featured Image URL</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="featured_image" 
                                       name="featured_image" 
                                       value="<?php echo htmlspecialchars($_POST['featured_image'] ?? ''); ?>"
                                       placeholder="https://example.com/image.jpg">
                            </div>
                            
                            <!-- Additional Images -->
                            <div class="mb-3">
                                <label for="images" class="form-label">Additional Images</label>
                                <textarea class="form-control" 
                                          id="images" 
                                          name="images" 
                                          rows="5"
                                          placeholder="One image URL per line"><?php echo htmlspecialchars($_POST['images'] ?? ''); ?></textarea>
                                <small class="text-muted">Enter one image URL per line</small>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Post
                                </button>
                                <a href="posts.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                            
                            <!-- Preview -->
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-info w-100" onclick="previewPost()">
                                    <i class="fas fa-eye me-2"></i>Preview
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 16px; }'
        });
        
        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.getElementById('slug').value = slug;
        });
        
        // Preview function
        function previewPost() {
            const title = document.getElementById('title').value;
            const content = tinymce.get('content').getContent();
            
            if (!title || !content) {
                alert('Please fill in title and content to preview');
                return;
            }
            
            const previewWindow = window.open('', '_blank');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Preview: ${title}</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 2rem; }
                        .preview-badge { position: fixed; top: 10px; right: 10px; z-index: 1000; }
                    </style>
                </head>
                <body>
                    <span class="badge bg-warning preview-badge">PREVIEW</span>
                    <div class="container">
                        <h1>${title}</h1>
                        <hr>
                        <div class="content">${content}</div>
                    </div>
                </body>
                </html>
            `);
        }
    </script>
</body>
</html>