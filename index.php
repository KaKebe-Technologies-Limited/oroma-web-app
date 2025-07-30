<?php
/**
 * Oroma TV - Main Homepage
 * PHP version of the streaming platform
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();

// Database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch(Exception $e) {
    die("Database connection failed");
}

// Get recent news
$stmt = $db->prepare("SELECT * FROM news WHERE published = 1 ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$news = $stmt->fetchAll();

// Get upcoming events
$stmt = $db->prepare("SELECT * FROM events WHERE event_date > NOW() ORDER BY event_date ASC LIMIT 3");
$stmt->execute();
$events = $stmt->fetchAll();

// Get site settings
$siteName = getSiteSetting($db, 'site_name', 'Oroma TV');
$siteTagline = getSiteSetting($db, 'site_tagline', 'Dwon tumalo me Uganda');
$tvStreamUrl = getSiteSetting($db, 'tv_stream_url');
$radioStreamUrl = getSiteSetting($db, 'radio_stream_url');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?> - <?php echo htmlspecialchars($siteTagline); ?></title>
    <meta name="description" content="Watch live TV and listen to QFM Radio 94.3 FM from Northern Uganda. Stay updated with local news, events, and community programs.">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- HLS.js for video streaming -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Navigation -->
    <nav class="bg-black/80 backdrop-blur-sm fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <img src="assets/images/oromatv-logo.png" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-10 w-auto">
                    <div>
                        <h1 class="text-xl font-bold text-red-500"><?php echo htmlspecialchars($siteName); ?></h1>
                        <p class="text-xs text-gray-300"><?php echo htmlspecialchars($siteTagline); ?></p>
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8">
                    <a href="index.php" class="text-red-500 hover:text-red-400 transition-colors">Home</a>
                    <a href="newsroom.php" class="text-white hover:text-red-400 transition-colors">Newsroom</a>
                    <a href="about.php" class="text-white hover:text-red-400 transition-colors">About Us</a>
                    <a href="events.php" class="text-white hover:text-red-400 transition-colors">Events</a>
                    <a href="programs.php" class="text-white hover:text-red-400 transition-colors">Programs</a>
                    <a href="contact.php" class="text-white hover:text-red-400 transition-colors">Contact</a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden bg-black/95 hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="index.php" class="block px-3 py-2 text-red-500">Home</a>
                <a href="newsroom.php" class="block px-3 py-2 text-white hover:text-red-400">Newsroom</a>
                <a href="about.php" class="block px-3 py-2 text-white hover:text-red-400">About Us</a>
                <a href="events.php" class="block px-3 py-2 text-white hover:text-red-400">Events</a>
                <a href="programs.php" class="block px-3 py-2 text-white hover:text-red-400">Programs</a>
                <a href="contact.php" class="block px-3 py-2 text-white hover:text-red-400">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16">
        <!-- Live Streaming Section -->
        <section class="bg-gradient-to-br from-gray-900 via-gray-800 to-black py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stream Tabs -->
                <div class="flex justify-center mb-6">
                    <div class="bg-black/50 rounded-lg p-1 flex">
                        <button id="tv-tab" class="px-6 py-3 rounded-md bg-red-600 text-white transition-all">
                            <i class="fas fa-tv mr-2"></i>TV Oroma
                        </button>
                        <button id="radio-tab" class="px-6 py-3 rounded-md text-gray-300 hover:text-white transition-all">
                            <i class="fas fa-radio mr-2"></i>QFM Radio 94.3
                        </button>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Video/Audio Player -->
                    <div class="lg:col-span-2">
                        <!-- TV Player -->
                        <div id="tv-player" class="aspect-video bg-black rounded-lg relative overflow-hidden">
                            <video id="tv-video" class="w-full h-full" controls poster="assets/images/tv-placeholder.jpg">
                                <source src="<?php echo htmlspecialchars($tvStreamUrl); ?>" type="application/x-mpegURL">
                                Your browser does not support the video tag.
                            </video>
                            <div id="tv-loading" class="absolute inset-0 bg-black/80 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500 mx-auto mb-4"></div>
                                    <p class="text-white">Loading TV Stream...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Radio Player -->
                        <div id="radio-player" class="hidden bg-gradient-to-br from-red-900 to-black rounded-lg p-8 text-center">
                            <div class="mb-6">
                                <div class="w-24 h-24 bg-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-radio text-3xl text-white"></i>
                                </div>
                                <h3 class="text-2xl font-bold mb-2">QFM Radio 94.3 FM</h3>
                                <p class="text-gray-300">Northern Uganda's Premier Radio Station</p>
                            </div>
                            
                            <audio id="radio-audio" controls class="w-full mb-4">
                                <source src="<?php echo htmlspecialchars($radioStreamUrl); ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                            
                            <div class="flex justify-center space-x-4">
                                <button id="play-btn" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-full">
                                    <i class="fas fa-play mr-2"></i>Play
                                </button>
                                <button id="stop-btn" class="bg-gray-600 hover:bg-gray-700 px-6 py-2 rounded-full">
                                    <i class="fas fa-stop mr-2"></i>Stop
                                </button>
                            </div>
                        </div>

                        <!-- Stream Info -->
                        <div class="mt-4 bg-black/50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 id="stream-title" class="text-lg font-semibold">Live Stream</h3>
                                    <p id="stream-status" class="text-sm text-gray-400">Connecting...</p>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse mr-2"></div>
                                        <span class="text-sm">LIVE</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-users mr-2 text-gray-400"></i>
                                        <span id="viewer-count" class="text-sm">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Interaction Sidebar -->
                    <div class="space-y-6">
                        <!-- Live Reactions -->
                        <div class="bg-black/50 rounded-lg p-4">
                            <h4 class="text-lg font-semibold mb-4">Quick Reactions</h4>
                            <div id="reaction-buttons" class="grid grid-cols-5 gap-2">
                                <button class="reaction-btn text-2xl p-3 hover:bg-red-600/20 rounded-lg transition-colors" data-reaction="❤️">❤️</button>
                                <button class="reaction-btn text-2xl p-3 hover:bg-red-600/20 rounded-lg transition-colors" data-reaction="👍">👍</button>
                                <button class="reaction-btn text-2xl p-3 hover:bg-red-600/20 rounded-lg transition-colors" data-reaction="👀">👀</button>
                                <button class="reaction-btn text-2xl p-3 hover:bg-red-600/20 rounded-lg transition-colors" data-reaction="👏">👏</button>
                                <button class="reaction-btn text-2xl p-3 hover:bg-red-600/20 rounded-lg transition-colors" data-reaction="🔥">🔥</button>
                            </div>
                            <div id="recent-reactions" class="mt-4 max-h-20 overflow-hidden">
                                <!-- Recent reactions will appear here -->
                            </div>
                        </div>

                        <!-- Live Comments -->
                        <div class="bg-black/50 rounded-lg p-4">
                            <h4 class="text-lg font-semibold mb-4">Live Chat</h4>
                            <div id="comments-container" class="space-y-2 max-h-48 overflow-y-auto mb-4">
                                <!-- Comments will appear here -->
                            </div>
                            <div class="space-y-2">
                                <input type="text" id="username-input" placeholder="Your name" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-400">
                                <textarea id="comment-input" placeholder="Join the conversation..." rows="2" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-400 resize-none"></textarea>
                                <button id="send-comment-btn" class="w-full bg-red-600 hover:bg-red-700 py-2 rounded-lg transition-colors">
                                    Send Message
                                </button>
                            </div>
                        </div>

                        <!-- Song Requests -->
                        <div class="bg-black/50 rounded-lg p-4">
                            <h4 class="text-lg font-semibold mb-4">Request a Song</h4>
                            <form id="song-request-form" class="space-y-3">
                                <input type="text" name="song_title" placeholder="Song title" required class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-400">
                                <input type="text" name="artist" placeholder="Artist name" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-400">
                                <input type="text" name="requester_name" placeholder="Your name" required class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-400">
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-music mr-2"></i>Request Song
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- News Section -->
        <?php if (!empty($news)): ?>
        <section class="py-12 bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-white mb-4">Latest News</h2>
                    <p class="text-gray-300">Stay updated with the latest from Northern Uganda</p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($news as $article): ?>
                    <article class="bg-black/50 rounded-lg overflow-hidden hover:bg-black/70 transition-colors">
                        <?php if($article['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-48 object-cover">
                        <?php endif; ?>
                        <div class="p-4">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs uppercase"><?php echo htmlspecialchars($article['category']); ?></span>
                                <span class="text-gray-400 text-xs"><?php echo timeAgo($article['created_at']); ?></span>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2"><?php echo htmlspecialchars($article['title']); ?></h3>
                            <p class="text-gray-300 text-sm mb-3"><?php echo htmlspecialchars(substr($article['summary'] ?? $article['content'], 0, 100)); ?>...</p>
                            <a href="news-article.php?id=<?php echo $article['id']; ?>" class="text-red-500 hover:text-red-400 text-sm font-medium">Read More →</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-8">
                    <a href="newsroom.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors">
                        View All News
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Events Section -->
        <?php if (!empty($events)): ?>
        <section class="py-12 bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-white mb-4">Upcoming Events</h2>
                    <p class="text-gray-300">Join us for exciting community events</p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($events as $event): ?>
                    <div class="bg-black/50 rounded-lg overflow-hidden hover:bg-black/70 transition-colors">
                        <?php if($event['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="w-full h-48 object-cover">
                        <?php endif; ?>
                        <div class="p-4">
                            <div class="flex items-center space-x-2 mb-2">
                                <i class="fas fa-calendar text-red-500"></i>
                                <span class="text-gray-400 text-sm"><?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></span>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="text-gray-300 text-sm mb-3"><?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...</p>
                            <?php if($event['location']): ?>
                            <div class="flex items-center text-gray-400 text-sm mb-3">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?php echo htmlspecialchars($event['location']); ?>
                            </div>
                            <?php endif; ?>
                            <a href="event-details.php?id=<?php echo $event['id']; ?>" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm transition-colors">
                                Learn More
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-black py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="assets/images/oromatv-logo.png" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-12 w-auto">
                        <div>
                            <h3 class="text-xl font-bold text-red-500"><?php echo htmlspecialchars($siteName); ?></h3>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($siteTagline); ?></p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm mb-4">Northern Uganda's premier television and radio station bringing you the latest news, entertainment, and community programs.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-red-500"><i class="fab fa-facebook text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500"><i class="fab fa-twitter text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500"><i class="fab fa-instagram text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500"><i class="fab fa-youtube text-xl"></i></a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="newsroom.php" class="text-gray-400 hover:text-red-500">Newsroom</a></li>
                        <li><a href="programs.php" class="text-gray-400 hover:text-red-500">Programs</a></li>
                        <li><a href="events.php" class="text-gray-400 hover:text-red-500">Events</a></li>
                        <li><a href="about.php" class="text-gray-400 hover:text-red-500">About Us</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-red-500">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-semibold mb-4">Contact Info</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><i class="fas fa-envelope mr-2"></i><?php echo getSiteSetting($db, 'contact_email', 'info@oromatv.com'); ?></li>
                        <li><i class="fas fa-phone mr-2"></i><?php echo getSiteSetting($db, 'contact_phone', '+256 123 456 789'); ?></li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i>Lira, Northern Uganda</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400 text-sm">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Widget -->
    <div class="fixed bottom-4 right-4 z-50">
        <a href="https://wa.me/<?php echo getSiteSetting($db, 'whatsapp_number', '256123456789'); ?>" target="_blank" class="bg-green-500 hover:bg-green-600 text-white p-3 rounded-full shadow-lg transition-colors">
            <i class="fab fa-whatsapp text-2xl"></i>
        </a>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script>
        // Initialize the streaming platform
        document.addEventListener('DOMContentLoaded', function() {
            initializeStreaming();
            initializeLiveFeatures();
        });
    </script>
</body>
</html>