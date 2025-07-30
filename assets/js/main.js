// Global variables
let videoPlayer = null;
let radioPlayer = null;
let currentStream = 'tv';
let lastChatUpdate = 0;
let pollingIntervals = {
    chat: null,
    reactions: null,
    status: null
};

// Add missing function for WebSocket compatibility
function sendWebSocketMessage(message) {
    console.log('WebSocket message converted to polling:', message);
    // This function is kept for compatibility but does nothing
    // since we're using PHP polling instead of WebSocket
}

// Initialize the main application
document.addEventListener('DOMContentLoaded', function() {
    initializePlayers();
    initializePolling();
    initializeStreamSwitching();
    setupEventListeners();
});

function initializePlayers() {
    // Initialize video player with Video.js
    const videoElement = document.getElementById('videoPlayer');
    if (videoElement && typeof videojs !== 'undefined') {
        videoPlayer = videojs(videoElement, {
            controls: true,
            preload: 'auto',
            responsive: true,
            fluid: true,
            html5: {
                hls: {
                    enableLowInitialPlaylist: true,
                    smoothQualityChange: true,
                    overrideNative: true
                }
            }
        });

        videoPlayer.on('loadstart', () => {
            updateConnectionStatus('connecting');
        });

        videoPlayer.on('canplay', () => {
            updateConnectionStatus('online');
        });

        videoPlayer.on('waiting', () => {
            updateConnectionStatus('buffering');
        });

        videoPlayer.on('error', () => {
            showNotification('Video stream error. Please try again.', 'error');
        });
    }

    // Initialize radio player
    radioPlayer = document.getElementById('radioPlayer');
    if (radioPlayer) {
        radioPlayer.addEventListener('loadstart', () => {
            updateConnectionStatus('connecting');
        });

        radioPlayer.addEventListener('canplay', () => {
            updateConnectionStatus('online');
        });

        radioPlayer.addEventListener('error', () => {
            showNotification('Radio stream error. Please try again.', 'error');
        });
    }
}

function initializePolling() {
    console.log('Initializing PHP polling for real-time features');
    
    // Poll chat messages every 2 seconds
    pollingIntervals.chat = setInterval(() => {
        loadChatMessages();
    }, 2000);
    
    // Poll reactions every 3 seconds
    pollingIntervals.reactions = setInterval(() => {
        loadReactions();
    }, 3000);
    
    // Poll stream status every 10 seconds
    pollingIntervals.status = setInterval(() => {
        loadStreamStatus();
    }, 10000);
    
    // Initial load
    loadChatMessages();
    loadReactions();
    loadStreamStatus();
    
    console.log('PHP polling initialized');
}

function stopPolling() {
    Object.values(pollingIntervals).forEach(interval => {
        if (interval) clearInterval(interval);
    });
    pollingIntervals = { chat: null, reactions: null, status: null };
}

function loadChatMessages() {
    const since = lastChatUpdate;
    fetch(`/api/chat.php?limit=20&since=${since}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.messages && Array.isArray(data.data.messages) && data.data.messages.length > 0) {
                data.data.messages.forEach(message => {
                    addChatMessage(message);
                    lastChatUpdate = Math.max(lastChatUpdate, message.timestamp || 0);
                });
            }
        })
        .catch(error => {
            console.error('Error loading chat messages:', error);
        });
}

function loadReactions() {
    fetch(`/api/reactions.php?stream_type=${currentStream}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionsDisplay(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading reactions:', error);
        });
}

function loadStreamStatus() {
    fetch('/api/stream-status.php?type=all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStreamStatusDisplay(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading stream status:', error);
        });
}

function sendChatMessage(name, message) {
    if (!name.trim() || !message.trim()) {
        showNotification('Please enter both name and message', 'warning');
        return;
    }

    fetch('/api/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name: name.trim(),
            message: message.trim(),
            stream_type: currentStream
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Message will be loaded by next polling cycle
            document.getElementById('chatMessage').value = '';
        } else {
            showNotification(data.error || 'Failed to send message', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending chat message:', error);
        showNotification('Failed to send message', 'error');
    });
}

function sendReaction(reaction) {
    const button = document.querySelector(`[onclick="sendReaction('${reaction}')"]`);
    if (button) {
        button.disabled = true;
        setTimeout(() => button.disabled = false, 2000);
    }

    fetch('/api/reactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            type: reaction,
            stream_type: currentStream
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFloatingReaction(reaction);
            // Reactions will be updated by next polling cycle
        } else {
            showNotification(data.error || 'Failed to send reaction', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending reaction:', error);
        showNotification('Failed to send reaction', 'error');
    });
}

function addChatMessage(message) {
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) return;

    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message';
    messageDiv.innerHTML = `
        <div class="message-header">
            <span class="username">${escapeHtml(message.name || message.username)}</span>
            <span class="timestamp">${message.formatted_time || formatTime(message.timestamp)}</span>
        </div>
        <div class="message-content">${escapeHtml(message.message)}</div>
    `;

    // Check if this message already exists
    const existingMessages = chatContainer.querySelectorAll('.chat-message');
    for (let existing of existingMessages) {
        const existingContent = existing.querySelector('.message-content').textContent;
        const existingUser = existing.querySelector('.username').textContent;
        if (existingContent === message.message && existingUser === (message.name || message.username)) {
            return; // Message already exists
        }
    }

    chatContainer.appendChild(messageDiv);
    
    // Keep only last 50 messages
    while (chatContainer.children.length > 50) {
        chatContainer.removeChild(chatContainer.firstChild);
    }
    
    // Auto-scroll to bottom
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function updateReactionsDisplay(reactions) {
    // Update reaction counts
    const reactionTypes = ['👍', '🔥', '😂', '😢', '👏'];
    reactionTypes.forEach(type => {
        const countElement = document.getElementById(`${type}-count`);
        if (countElement && reactions[type] !== undefined) {
            countElement.textContent = reactions[type];
        }
    });

    // Update total and viewers
    const totalElement = document.getElementById('total-reactions');
    if (totalElement && reactions.total !== undefined) {
        totalElement.textContent = reactions.total;
    }

    const viewersElement = document.getElementById('active-viewers');
    if (viewersElement && reactions.active_viewers !== undefined) {
        viewersElement.textContent = reactions.active_viewers;
    }
}

function updateStreamStatusDisplay(status) {
    if (status.tv) {
        const tvStatus = document.getElementById('tv-status');
        if (tvStatus) {
            tvStatus.textContent = status.tv.status;
            tvStatus.className = `status-indicator ${status.tv.status}`;
        }
        
        const tvViewers = document.getElementById('tv-viewers');
        if (tvViewers) {
            tvViewers.textContent = status.tv.viewers;
        }
    }

    if (status.radio) {
        const radioStatus = document.getElementById('radio-status');
        if (radioStatus) {
            radioStatus.textContent = status.radio.status;
            radioStatus.className = `status-indicator ${status.radio.status}`;
        }
        
        const radioListeners = document.getElementById('radio-listeners');
        if (radioListeners) {
            radioListeners.textContent = status.radio.listeners || status.radio.viewers;
        }
    }
}

function showFloatingReaction(reaction) {
    const container = document.getElementById('floating-reactions');
    if (!container) return;

    const reactionElement = document.createElement('div');
    reactionElement.className = 'floating-reaction';
    reactionElement.textContent = reaction;
    reactionElement.style.left = Math.random() * 80 + 10 + '%';
    
    container.appendChild(reactionElement);
    
    // Remove after animation
    setTimeout(() => {
        if (container.contains(reactionElement)) {
            container.removeChild(reactionElement);
        }
    }, 3000);
}

function initializeStreamSwitching() {
    const streamButtons = document.querySelectorAll('.stream-tab');
    streamButtons.forEach(button => {
        button.addEventListener('click', () => {
            const streamType = button.getAttribute('data-stream');
            switchStream(streamType);
        });
    });
}

function switchStream(streamType) {
    if (streamType === currentStream) return;
    
    currentStream = streamType;
    
    // Update active tab
    document.querySelectorAll('.stream-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-stream="${streamType}"]`).classList.add('active');
    
    // Show/hide appropriate player
    const tvContainer = document.getElementById('tv-container');
    const radioContainer = document.getElementById('radio-container');
    
    if (streamType === 'tv') {
        tvContainer.style.display = 'block';
        radioContainer.style.display = 'none';
        if (radioPlayer) radioPlayer.pause();
    } else {
        tvContainer.style.display = 'none';
        radioContainer.style.display = 'block';
        if (videoPlayer) videoPlayer.pause();
    }
    
    // Reload reactions for new stream type
    loadReactions();
    
    showNotification(`Switched to ${streamType.toUpperCase()} stream`, 'success');
}

function setupEventListeners() {
    // Chat form submission
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const nameInput = document.getElementById('chatName');
            const messageInput = document.getElementById('chatMessage');
            
            if (nameInput && messageInput) {
                sendChatMessage(nameInput.value, messageInput.value);
            }
        });
    }

    // Share button
    const shareButton = document.getElementById('shareButton');
    if (shareButton) {
        shareButton.addEventListener('click', () => {
            if (navigator.share) {
                navigator.share({
                    title: 'Oroma TV - Live Streaming',
                    text: 'Watch live TV and listen to QFM Radio 94.3 FM',
                    url: 'https://www.oromatv.com'
                });
            } else {
                // Fallback to clipboard
                navigator.clipboard.writeText('https://www.oromatv.com').then(() => {
                    showNotification('Link copied to clipboard!', 'success');
                });
            }
        });
    }
}

function updateConnectionStatus(status) {
    const statusElement = document.getElementById('connection-status');
    if (statusElement) {
        statusElement.textContent = status;
        statusElement.className = `connection-status ${status}`;
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    }, 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleTimeString('en-US', { 
        hour12: false, 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

// Page visibility handling to pause/resume polling
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        console.log('Page hidden, reducing polling frequency');
        stopPolling();
        // Restart with longer intervals when page is hidden
        pollingIntervals.chat = setInterval(loadChatMessages, 10000);
        pollingIntervals.reactions = setInterval(loadReactions, 15000);
        pollingIntervals.status = setInterval(loadStreamStatus, 30000);
    } else {
        console.log('Page visible, resuming normal polling');
        stopPolling();
        initializePolling();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    stopPolling();
});

// Tab navigation functionality (if needed for future pages)
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link[data-tab]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const tab = link.getAttribute('data-tab');
            
            // Update active nav link
            navLinks.forEach(nav => nav.classList.remove('active'));
            link.classList.add('active');
            
            // Here you could implement tab switching logic
            console.log('Switching to tab:', tab);
        });
    });
});