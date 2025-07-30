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

$success = '';
$error = '';

// Handle contact form submission
if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just show a success message
        $success = 'Thank you for your message! We will get back to you soon.';
        $_POST = []; // Clear form
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Oroma TV - Get in Touch | Lira City, Northern Uganda</title>
    <meta name="description" content="Contact Oroma TV in Lira City, Northern Uganda. Send us a message, call us, or chat on WhatsApp. Address: Plot 000 Semsem Building Won Nyaci Road."
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Contact <?php echo $site_config['site_name']; ?>">
    <meta property="og:description" content="Get in touch with us for inquiries, support, or feedback">
    <meta property="og:url" content="<?php echo get_base_url(); ?>/contact.php">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .contact-info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            height: 100%;
            border-top: 4px solid var(--primary-red);
        }
        
        .contact-info-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .contact-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .whatsapp-section {
            background: linear-gradient(135deg, #25d366 0%, #20b358 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }
        
        .social-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .social-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-facebook { background: #1877f2; }
        .btn-twitter { background: #1da1f2; }
        .btn-instagram { background: #e4405f; }
        .btn-youtube { background: #ff0000; }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-red) 0%, var(--primary-red-dark) 100%);
            color: white;
            padding: 4rem 0 2rem;
        }
    </style>
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
                <a class="nav-link active" href="contact.php">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 mb-3">Get In Touch</h1>
                    <p class="lead">We'd love to hear from you. Contact us for inquiries, support, or feedback.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Contact Information Cards -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h5>Address</h5>
                    <p class="mb-0">
                        Plot 000 Semsem Building Won Nyaci Road<br>
                        Lira City, Northern Uganda
                    </p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h5>Phone</h5>
                    <p class="mb-0">
                        <a href="tel:+256772123456" class="text-decoration-none">+256 772 123 456</a><br>
                        <a href="tel:+256751789012" class="text-decoration-none">+256 751 789 012</a>
                    </p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5>Email</h5>
                    <p class="mb-0">
                        <a href="mailto:info@oromatv.com" class="text-decoration-none">info@oromatv.com</a><br>
                        <a href="mailto:news@oromatv.com" class="text-decoration-none">news@oromatv.com</a>
                    </p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Office Hours</h5>
                    <p class="mb-0">
                        Mon - Fri: 8:00 AM - 6:00 PM<br>
                        Sat: 9:00 AM - 4:00 PM
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="contact-form">
                    <h3 class="mb-4"><i class="fas fa-paper-plane me-2 text-danger"></i>Send us a Message</h3>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       placeholder="Enter your name"
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="Enter your email"
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="subject" 
                                   name="subject" 
                                   value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                                   placeholder="What is this about?"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" 
                                      id="message" 
                                      name="message" 
                                      rows="6"
                                      placeholder="Tell us more about your inquiry..."
                                      required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- WhatsApp & Social -->
            <div class="col-lg-4">
                <!-- WhatsApp Section -->
                <div class="whatsapp-section">
                    <div class="mb-3">
                        <i class="fab fa-whatsapp fa-3x"></i>
                    </div>
                    <h4>WhatsApp</h4>
                    <p class="mb-3">Quick messages and support</p>
                    <h5 class="mb-3">+256 777 676 206</h5>
                    <a href="https://wa.me/256777676206" target="_blank" class="btn btn-light btn-lg">
                        <i class="fab fa-whatsapp me-2"></i>Chat on WhatsApp
                    </a>
                </div>
                
                <!-- Social Media -->
                <div class="contact-form">
                    <h4 class="mb-4"><i class="fas fa-share-alt me-2 text-danger"></i>Follow Us</h4>
                    <div class="social-buttons">
                        <a href="#" class="social-btn btn-facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="#" class="social-btn btn-twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <a href="#" class="social-btn btn-instagram">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                        <a href="#" class="social-btn btn-youtube">
                            <i class="fab fa-youtube"></i> YouTube
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Floating Button -->
    <div class="whatsapp-float">
        <a href="https://wa.me/256777676206" target="_blank" title="Chat on WhatsApp">
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
</body>
</html>