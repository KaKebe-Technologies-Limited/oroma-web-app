<?php
/**
 * Oroma TV Admin Dashboard
 * PHP version of the admin interface
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

// Database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch(Exception $e) {
    die("Database connection failed");
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verify admin role
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get dashboard stats
$stats = getDashboardStats($db);

function getDashboardStats($db) {
    $stats = [];
    
    // Active users count
    cleanInactiveUsers($db);
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM active_users WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->execute();
    $stats['active_users'] = $stmt->fetch()['count'];
    
    // Total news articles
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM news WHERE published = 1");
    $stmt->execute();
    $stats['published_news'] = $stmt->fetch()['count'];
    
    // Pending song requests
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM song_requests WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_songs'] = $stmt->fetch()['count'];
    
    // Recent comments
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM live_comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $stats['recent_comments'] = $stmt->fetch()['count'];
    
    return $stats;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Oroma TV</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-black/90 border-r border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <div class="flex items-center space-x-3">
                    <img src="../assets/images/oromatv-logo.png" alt="Oroma TV" class="h-10 w-auto">
                    <div>
                        <h1 class="text-lg font-bold text-red-500">Oroma TV</h1>
                        <p class="text-xs text-gray-400">Admin Panel</p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="#dashboard" class="nav-item active flex items-center space-x-3 px-4 py-3 rounded-lg bg-red-600 text-white">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#live-streams" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 text-gray-300">
                            <i class="fas fa-broadcast-tower"></i>
                            <span>Live Streams</span>
                        </a>
                    </li>
                    <li>
                        <a href="#news" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 text-gray-300">
                            <i class="fas fa-newspaper"></i>
                            <span>News</span>
                        </a>
                    </li>
                    <li>
                        <a href="#events" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 text-gray-300">
                            <i class="fas fa-calendar"></i>
                            <span>Events</span>
                        </a>
                    </li>
                    <li>
                        <a href="#programs" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 text-gray-300">
                            <i class="fas fa-tv"></i>
                            <span>Programs</span>
                        </a>
                    </li>
                    <li>
                        <a href="#song-requests" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 text-gray-300">
                            <i class="fas fa-music"></i>
                            <span>Song Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="#analytics" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 text-gray-300">
                            <i class="fas fa-chart-line"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="#settings" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 text-gray-300">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="absolute bottom-4 left-4 right-4">
                <div class="bg-gray-800 rounded-lg p-3">
                    <p class="text-sm text-gray-400">Logged in as</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <button onclick="logout()" class="text-red-400 hover:text-red-300 text-sm mt-2">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">
            <!-- Header -->
            <header class="bg-gray-800 border-b border-gray-700 px-6 py-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Dashboard</h2>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-sm text-gray-400">System Online</span>
                        </div>
                        <div class="text-sm text-gray-400">
                            <?php echo date('M j, Y g:i A'); ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="p-6 overflow-y-auto h-full">
                <div id="dashboard-content" class="content-section">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="admin-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Active Viewers</p>
                                    <p class="stat-number"><?php echo $stats['active_users']; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-users text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="admin-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Published News</p>
                                    <p class="stat-number"><?php echo $stats['published_news']; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-newspaper text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="admin-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Pending Songs</p>
                                    <p class="stat-number"><?php echo $stats['pending_songs']; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-music text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="admin-card">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-400 text-sm">Recent Comments</p>
                                    <p class="stat-number"><?php echo $stats['recent_comments']; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-comments text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="admin-card">
                            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <button onclick="showSection('news')" class="w-full bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                                    <i class="fas fa-plus"></i>
                                    <span>Add News Article</span>
                                </button>
                                <button onclick="showSection('events')" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span>Create Event</span>
                                </button>
                                <button onclick="showSection('programs')" class="w-full bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                                    <i class="fas fa-tv"></i>
                                    <span>Manage Programs</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="admin-card">
                            <h3 class="text-lg font-semibold mb-4">System Status</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">TV Stream</span>
                                    <span class="text-green-400">
                                        <i class="fas fa-check-circle mr-1"></i>Online
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Radio Stream</span>
                                    <span class="text-green-400">
                                        <i class="fas fa-check-circle mr-1"></i>Online
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Database</span>
                                    <span class="text-green-400">
                                        <i class="fas fa-check-circle mr-1"></i>Connected
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Last Backup</span>
                                    <span class="text-gray-400"><?php echo date('M j, g:i A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other sections will be loaded dynamically -->
                <div id="other-content" class="content-section hidden">
                    <div class="text-center py-12">
                        <i class="fas fa-cog fa-spin text-4xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400">Loading...</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script>
        // Admin dashboard functionality
        function showSection(section) {
            // Update active nav item
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active', 'bg-red-600', 'text-white');
                item.classList.add('text-gray-300');
            });
            
            // Load section content via AJAX or show appropriate section
            console.log('Loading section:', section);
            // This would typically load content dynamically
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../api/auth/logout', { method: 'POST' })
                    .then(() => {
                        window.location.href = 'login.php';
                    });
            }
        }
        
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>