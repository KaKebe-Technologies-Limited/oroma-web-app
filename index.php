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

// Get stream statistics
$stmt = $pdo->prepare("SELECT * FROM stream_stats ORDER BY recorded_at DESC LIMIT 2");
$stmt->execute();
$stream_stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oroma TV - Live Streaming from Northern Uganda | Watch TV & Listen to QFM Radio 94.3 FM</title>
    <meta name="description" content="Watch Oroma TV live streaming and listen to QFM Radio 94.3 FM from Lira City, Northern Uganda. Real-time chat, reactions, and news. Your source for local entertainment and information.">
    <meta name="keywords" content="Oroma TV, QFM Radio, Northern Uganda, Lira City, live streaming, TV, radio, 94.3 FM, entertainment, news, local media">
    <meta name="author" content="Oroma TV">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.oromatv.com/">
    
    <!-- Open Graph Tags for Social Media -->
    <meta property="og:title" content="Oroma TV - Live Streaming from Northern Uganda">
    <meta property="og:description" content="Watch Oroma TV live streaming and listen to QFM Radio 94.3 FM from Lira City, Northern Uganda. Real-time chat and reactions.">
    <meta property="og:image" content="https://www.oromatv.com/assets/images/logo.png">
    <meta property="og:url" content="https://www.oromatv.com/">
    <meta property="og:type" content="website">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Oroma TV">
    <meta property="og:locale" content="en_UG">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Oroma TV - Live Streaming from Northern Uganda">
    <meta name="twitter:description" content="Watch Oroma TV live streaming and listen to QFM Radio 94.3 FM from Lira City, Northern Uganda.">
    <meta name="twitter:image" content="https://www.oromatv.com/assets/images/logo.png">
    
    <!-- Additional SEO Tags -->
    <meta name="geo.region" content="UG-N">
    <meta name="geo.placename" content="Lira City, Northern Uganda">
    <meta name="language" content="English">
    <meta name="rating" content="General">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    
    <!-- Video.js CSS -->
    <link href="https://vjs.zencdn.net/8.5.2/video-js.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap for responsive layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Structured Data (JSON-LD) for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Oroma TV",
        "url": "https://www.oromatv.com",
        "logo": "https://www.oromatv.com/assets/images/logo.png",
        "sameAs": [
            "https://www.facebook.com/oromatv",
            "https://www.twitter.com/oromatv",
            "https://www.instagram.com/oromatv",
            "https://www.youtube.com/oromatv"
        ],
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Plot 000 Semsem Building Won Nyaci Road",
            "addressLocality": "Lira City",
            "addressRegion": "Northern Uganda",
            "addressCountry": "UG"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+256772123456",
            "contactType": "customer service"
        }
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BroadcastService",
        "name": "Oroma TV Live Stream",
        "description": "Live streaming TV and radio from Northern Uganda",
        "url": "https://www.oromatv.com",
        "broadcastDisplayName": "Oroma TV",
        "videoFormat": "HLS",
        "inLanguage": "en"
    }
    </script>
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

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="container-fluid px-3 px-md-4 px-lg-5">
            <h1><i class="fas fa-sun me-2"></i>Good Afternoon! Welcome to Oroma TV & QFM Radio 94.3 FM</h1>
            <p><?php echo $site_config['site_tagline']; ?></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid mt-4 px-3 px-md-4 px-lg-5">
        <div class="row">
            <!-- Left Side - Video/Radio Player -->
            <div class="col-lg-8 mb-4">
                <!-- Stream Tabs -->
                <div class="stream-tabs mb-3">
                    <button class="tab-btn active" data-stream="tv">
                        <i class="fas fa-tv me-2"></i>TV Oroma
                    </button>
                    <button class="tab-btn" data-stream="radio">
                        <i class="fas fa-radio me-2"></i>QFM Radio 94.3 FM
                    </button>
                    <button class="share-btn" id="shareBtn">
                        <i class="fas fa-share-alt me-1"></i>Share
                    </button>
                </div>

                <!-- Video Container -->
                <div class="video-container" id="tvContainer">
                    <div class="player-wrapper">
                        <video
                            id="oromaTV"
                            class="video-js vjs-default-skin"
                            controls
                            preload="auto"
                            data-setup='{}'>
                            <source src="<?php echo $site_config['stream_url']; ?>" type="application/x-mpegURL">
                            <p class="vjs-no-js">
                                To view this video please enable JavaScript, and consider upgrading to a web browser that
                                <a href="https://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>.
                            </p>
                        </video>
                        
                        <!-- Custom Controls Overlay -->
                        <div class="custom-controls-overlay">
                            <div class="brightness-control">
                                <i class="fas fa-sun"></i>
                                <input type="range" id="brightnessRange" min="0.3" max="2" step="0.1" value="1">
                            </div>
                            <div class="pip-control">
                                <button id="pipBtn" title="Picture in Picture">
                                    <i class="fas fa-external-link-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Radio Container -->
                <div class="radio-container" id="radioContainer" style="display: none;">
                    <div class="radio-player">
                        <div class="radio-info">
                            <h3><i class="fas fa-radio me-2"></i>QFM Radio 94.3 FM</h3>
                            <p>Live Broadcasting from Northern Uganda</p>
                        </div>
                        <audio id="radioPlayer" controls>
                            <source src="<?php echo $site_config['radio_url']; ?>" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        
                        <!-- Radio Controls -->
                        <div class="radio-controls mt-3">
                            <button class="btn-radio" id="radioPlayBtn">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="btn-radio" id="radioStopBtn">
                                <i class="fas fa-stop"></i>
                            </button>
                            <div class="volume-control">
                                <i class="fas fa-volume-up"></i>
                                <input type="range" id="radioVolumeRange" min="0" max="1" step="0.1" value="0.7">
                            </div>
                        </div>
                        
                        <!-- Song Request -->
                        <div class="song-request mt-4">
                            <h5><i class="fas fa-music me-2"></i>Request a Song</h5>
                            <div class="input-group">
                                <input type="text" class="form-control" id="songRequestInput" placeholder="Enter song title or artist...">
                                <button class="btn btn-primary" id="songRequestBtn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <!-- Right Side - Reactions and Chat -->
            <div class="col-lg-4">
                <!-- Live Reactions -->
                <div class="reactions-panel mb-4">
                    <div class="panel-header">
                        <h5><i class="fas fa-heart me-2"></i>Live Reactions</h5>
                        <span class="live-indicator">
                            <i class="fas fa-circle"></i> LIVE
                        </span>
                    </div>
                    
                    <div class="reactions-stats">
                        <div class="reaction-item" data-reaction="like">
                            <button class="reaction-btn">👍</button>
                            <span class="reaction-count">0</span>
                        </div>
                        <div class="reaction-item" data-reaction="fire">
                            <button class="reaction-btn">🔥</button>
                            <span class="reaction-count">0</span>
                        </div>
                        <div class="reaction-item" data-reaction="funny">
                            <button class="reaction-btn">😂</button>
                            <span class="reaction-count">0</span>
                        </div>
                        <div class="reaction-item" data-reaction="emotional">
                            <button class="reaction-btn">😢</button>
                            <span class="reaction-count">0</span>
                        </div>
                        <div class="reaction-item" data-reaction="applause">
                            <button class="reaction-btn">👏</button>
                            <span class="reaction-count">0</span>
                        </div>
                    </div>
                    
                    <div class="reactions-summary">
                        <small id="reactionsTotal">0 total reactions from 0 active viewers</small>
                    </div>
                </div>

                <!-- Live Chat -->
                <div class="chat-panel">
                    <div class="panel-header">
                        <h5><i class="fas fa-comments me-2"></i>Live Chat (Oroma TV)</h5>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-welcome">
                            <p>No comments yet. Be the first to comment!</p>
                        </div>
                    </div>
                    
                    <div class="chat-input">
                        <div class="input-group">
                            <input type="text" class="form-control" id="chatNameInput" placeholder="Your name">
                        </div>
                        <div class="input-group mt-2">
                            <input type="text" class="form-control" id="chatMessageInput" placeholder="Comment on Oroma TV...">
                            <button class="btn btn-primary" id="chatSendBtn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Reaction Animations -->
    <div id="floatingReactions"></div>
    
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
    <script src="https://vjs.zencdn.net/8.5.2/video.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/video-controls.js"></script>
    <script src="assets/js/reactions.js"></script>
    <script src="assets/js/chat.js"></script>
    
    <script>
        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize video player
            initializeVideoPlayer();
            
            // Initialize WebSocket connection
            initializeWebSocket();
            
            // Initialize custom controls
            initializeCustomControls();
            
            // Initialize reactions
            initializeReactions();
            
            // Initialize chat
            initializeChat();
            
            // Initialize stream switching
            initializeStreamSwitching();
            
            // Load initial data
            loadStreamStatus();
            loadReactions();
        });
    </script>
</body>
</html>
