/**
 * Oroma TV Main JavaScript
 * Handles streaming, live interactions, and UI functionality
 */

class OromaTVApp {
    constructor() {
        this.currentStream = 'tv';
        this.videoPlayer = null;
        this.audioPlayer = null;
        this.hls = null;
        this.userSession = this.generateSessionId();
        this.username = localStorage.getItem('username') || '';
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setupMobileMenu();
        this.initializeStreaming();
        this.startLiveUpdates();
        this.requestUsername();
    }
    
    generateSessionId() {
        return 'session_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
    }
    
    setupEventListeners() {
        // Stream tab switching
        const tvTab = document.getElementById('tv-tab');
        const radioTab = document.getElementById('radio-tab');
        
        if (tvTab) tvTab.addEventListener('click', () => this.switchStream('tv'));
        if (radioTab) radioTab.addEventListener('click', () => this.switchStream('radio'));
        
        // Reaction buttons
        document.querySelectorAll('.reaction-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const reaction = e.target.getAttribute('data-reaction');
                this.sendReaction(reaction);
                this.createFloatingReaction(reaction, e.target);
            });
        });
        
        // Comment system
        const sendBtn = document.getElementById('send-comment-btn');
        const commentInput = document.getElementById('comment-input');
        
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.sendComment());
        }
        
        if (commentInput) {
            commentInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendComment();
                }
            });
        }
        
        // Song request form
        const songForm = document.getElementById('song-request-form');
        if (songForm) {
            songForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitSongRequest(new FormData(songForm));
            });
        }
        
        // Radio controls
        const playBtn = document.getElementById('play-btn');
        const stopBtn = document.getElementById('stop-btn');
        
        if (playBtn) playBtn.addEventListener('click', () => this.playRadio());
        if (stopBtn) stopBtn.addEventListener('click', () => this.stopRadio());
    }
    
    setupMobileMenu() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    }
    
    async initializeStreaming() {
        this.videoPlayer = document.getElementById('tv-video');
        this.audioPlayer = document.getElementById('radio-audio');
        
        if (this.videoPlayer) {
            this.setupVideoPlayer();
        }
        
        // Join active users
        await this.joinActiveUsers();
    }
    
    setupVideoPlayer() {
        if (Hls.isSupported()) {
            this.hls = new Hls({
                enableWorker: true,
                lowLatencyMode: true,
                backBufferLength: 90
            });
            
            this.hls.loadSource(this.videoPlayer.querySelector('source').src);
            this.hls.attachMedia(this.videoPlayer);
            
            this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                console.log('HLS manifest parsed');
                this.updateStreamStatus('Connected', 'success');
                this.hideLoading();
            });
            
            this.hls.on(Hls.Events.ERROR, (event, data) => {
                if (data.fatal) {
                    this.updateStreamStatus('Connection Error', 'error');
                    this.handleStreamError(data);
                }
            });
        } else if (this.videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
            // Native HLS support (Safari)
            this.videoPlayer.src = this.videoPlayer.querySelector('source').src;
            this.videoPlayer.addEventListener('loadedmetadata', () => {
                this.updateStreamStatus('Connected', 'success');
                this.hideLoading();
            });
        }
    }
    
    switchStream(type) {
        this.currentStream = type;
        
        const tvTab = document.getElementById('tv-tab');
        const radioTab = document.getElementById('radio-tab');
        const tvPlayer = document.getElementById('tv-player');
        const radioPlayer = document.getElementById('radio-player');
        const streamTitle = document.getElementById('stream-title');
        
        // Update tabs
        tvTab.className = type === 'tv' ? 
            'px-6 py-3 rounded-md bg-red-600 text-white transition-all' :
            'px-6 py-3 rounded-md text-gray-300 hover:text-white transition-all';
            
        radioTab.className = type === 'radio' ? 
            'px-6 py-3 rounded-md bg-red-600 text-white transition-all' :
            'px-6 py-3 rounded-md text-gray-300 hover:text-white transition-all';
        
        // Update players
        if (type === 'tv') {
            tvPlayer.classList.remove('hidden');
            radioPlayer.classList.add('hidden');
            streamTitle.textContent = 'TV Oroma Live';
        } else {
            tvPlayer.classList.add('hidden');
            radioPlayer.classList.remove('hidden');
            streamTitle.textContent = 'QFM Radio 94.3 FM';
        }
        
        // Update reaction buttons for context
        this.updateReactionButtons(type);
        
        // Update viewer count
        this.updateViewerCount();
    }
    
    updateReactionButtons(streamType) {
        const buttons = document.querySelectorAll('.reaction-btn');
        
        if (streamType === 'tv') {
            // TV reactions: ❤️ 👍 👀 👏 🔥
            const tvReactions = ['❤️', '👍', '👀', '👏', '🔥'];
            buttons.forEach((btn, index) => {
                if (tvReactions[index]) {
                    btn.textContent = tvReactions[index];
                    btn.setAttribute('data-reaction', tvReactions[index]);
                }
            });
        } else {
            // Radio reactions: 🎵 🎧 💃 🎤 🔊
            const radioReactions = ['🎵', '🎧', '💃', '🎤', '🔊'];
            buttons.forEach((btn, index) => {
                if (radioReactions[index]) {
                    btn.textContent = radioReactions[index];
                    btn.setAttribute('data-reaction', radioReactions[index]);
                }
            });
        }
    }
    
    playRadio() {
        const audio = this.audioPlayer;
        if (audio) {
            audio.play().then(() => {
                this.updateStreamStatus('Playing', 'success');
            }).catch(err => {
                console.error('Radio play error:', err);
                this.updateStreamStatus('Play Error', 'error');
            });
        }
    }
    
    stopRadio() {
        const audio = this.audioPlayer;
        if (audio) {
            audio.pause();
            audio.currentTime = 0;
            this.updateStreamStatus('Stopped', 'warning');
        }
    }
    
    async sendReaction(reaction) {
        try {
            await this.apiRequest('/api/live-reactions', {
                method: 'POST',
                body: JSON.stringify({
                    stream_type: this.currentStream,
                    reaction_type: reaction,
                    user_session: this.userSession
                })
            });
        } catch (error) {
            console.error('Failed to send reaction:', error);
        }
    }
    
    createFloatingReaction(reaction, element) {
        const rect = element.getBoundingClientRect();
        const floatingReaction = document.createElement('div');
        floatingReaction.className = 'floating-reaction';
        floatingReaction.textContent = reaction;
        floatingReaction.style.left = rect.left + 'px';
        floatingReaction.style.top = rect.top + 'px';
        
        document.body.appendChild(floatingReaction);
        
        setTimeout(() => {
            floatingReaction.remove();
        }, 2000);
    }
    
    async sendComment() {
        const commentInput = document.getElementById('comment-input');
        const usernameInput = document.getElementById('username-input');
        
        const comment = commentInput.value.trim();
        const username = usernameInput.value.trim() || this.username || 'Anonymous';
        
        if (!comment) return;
        
        try {
            await this.apiRequest('/api/live-comments', {
                method: 'POST',
                body: JSON.stringify({
                    stream_type: this.currentStream,
                    username: username,
                    comment: comment,
                    user_session: this.userSession
                })
            });
            
            commentInput.value = '';
            if (username !== 'Anonymous') {
                this.username = username;
                localStorage.setItem('username', username);
            }
            
        } catch (error) {
            console.error('Failed to send comment:', error);
            this.showToast('Failed to send message', 'error');
        }
    }
    
    async submitSongRequest(formData) {
        try {
            const data = {
                song_title: formData.get('song_title'),
                artist: formData.get('artist'),
                requester_name: formData.get('requester_name'),
                message: formData.get('message') || ''
            };
            
            await this.apiRequest('/api/song-requests', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            document.getElementById('song-request-form').reset();
            this.showToast('Song request submitted successfully!', 'success');
            
        } catch (error) {
            console.error('Failed to submit song request:', error);
            this.showToast('Failed to submit song request', 'error');
        }
    }
    
    async joinActiveUsers() {
        try {
            await this.apiRequest('/api/active-users/join', {
                method: 'POST',
                body: JSON.stringify({
                    stream_type: this.currentStream,
                    user_session: this.userSession,
                    username: this.username || null
                })
            });
        } catch (error) {
            console.error('Failed to join active users:', error);
        }
    }
    
    startLiveUpdates() {
        // Update viewer count every 5 seconds
        setInterval(() => this.updateViewerCount(), 5000);
        
        // Update comments every 3 seconds
        setInterval(() => this.updateComments(), 3000);
        
        // Update reactions every 2 seconds
        setInterval(() => this.updateReactions(), 2000);
        
        // Send heartbeat every 30 seconds
        setInterval(() => this.sendHeartbeat(), 30000);
        
        // Clean up old data every minute
        setInterval(() => this.cleanup(), 60000);
    }
    
    async updateViewerCount() {
        try {
            const response = await this.apiRequest(`/api/active-users/count/${this.currentStream}`);
            const data = await response.json();
            
            const viewerCount = document.getElementById('viewer-count');
            if (viewerCount) {
                viewerCount.textContent = data.count || 0;
            }
        } catch (error) {
            console.error('Failed to update viewer count:', error);
        }
    }
    
    async updateComments() {
        try {
            const response = await this.apiRequest(`/api/live-comments/${this.currentStream}`);
            const comments = await response.json();
            
            const container = document.getElementById('comments-container');
            if (container) {
                container.innerHTML = '';
                
                comments.forEach(comment => {
                    const commentEl = this.createCommentElement(comment);
                    container.appendChild(commentEl);
                });
                
                container.scrollTop = container.scrollHeight;
            }
        } catch (error) {
            console.error('Failed to update comments:', error);
        }
    }
    
    async updateReactions() {
        try {
            const response = await this.apiRequest(`/api/live-reactions/${this.currentStream}`);
            const reactions = await response.json();
            
            const container = document.getElementById('recent-reactions');
            if (container) {
                container.innerHTML = '';
                
                reactions.slice(-10).forEach(reaction => {
                    const reactionEl = document.createElement('span');
                    reactionEl.className = 'inline-block text-lg mr-1 animate-pulse';
                    reactionEl.textContent = reaction.reaction_type;
                    container.appendChild(reactionEl);
                });
            }
        } catch (error) {
            console.error('Failed to update reactions:', error);
        }
    }
    
    async sendHeartbeat() {
        try {
            await this.apiRequest('/api/active-users/heartbeat', {
                method: 'POST',
                body: JSON.stringify({
                    user_session: this.userSession,
                    stream_type: this.currentStream
                })
            });
        } catch (error) {
            console.error('Failed to send heartbeat:', error);
        }
    }
    
    createCommentElement(comment) {
        const div = document.createElement('div');
        div.className = 'chat-message';
        
        div.innerHTML = `
            <div class="flex justify-between items-start">
                <span class="chat-username">${this.escapeHtml(comment.username)}</span>
                <span class="chat-time">${this.timeAgo(comment.created_at)}</span>
            </div>
            <div class="chat-content">${this.escapeHtml(comment.comment)}</div>
        `;
        
        return div;
    }
    
    updateStreamStatus(status, type = 'info') {
        const statusEl = document.getElementById('stream-status');
        if (statusEl) {
            statusEl.textContent = status;
            statusEl.className = `text-sm ${
                type === 'success' ? 'text-green-400' :
                type === 'error' ? 'text-red-400' :
                type === 'warning' ? 'text-yellow-400' :
                'text-gray-400'
            }`;
        }
    }
    
    hideLoading() {
        const loading = document.getElementById('tv-loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }
    
    handleStreamError(data) {
        console.error('HLS Error:', data);
        
        // Try to recover from error
        if (this.hls) {
            if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                this.hls.startLoad();
            } else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                this.hls.recoverMediaError();
            }
        }
    }
    
    requestUsername() {
        if (!this.username) {
            const username = prompt('Enter your name for chat (optional):');
            if (username) {
                this.username = username.trim();
                localStorage.setItem('username', this.username);
                
                const usernameInput = document.getElementById('username-input');
                if (usernameInput) {
                    usernameInput.value = this.username;
                }
            }
        } else {
            const usernameInput = document.getElementById('username-input');
            if (usernameInput) {
                usernameInput.value = this.username;
            }
        }
    }
    
    async apiRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        return fetch(url, { ...defaultOptions, ...options });
    }
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`;
        return `${Math.floor(seconds / 86400)}d`;
    }
    
    cleanup() {
        // Remove old floating reactions
        document.querySelectorAll('.floating-reaction').forEach(el => {
            if (Date.now() - parseInt(el.dataset.created || 0) > 5000) {
                el.remove();
            }
        });
    }
}

// Initialize streaming functions for backward compatibility
function initializeStreaming() {
    // Already handled in OromaTVApp constructor
}

function initializeLiveFeatures() {
    // Already handled in OromaTVApp constructor
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.oromaTVApp = new OromaTVApp();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, reduce update frequency
        if (window.oromaTVApp) {
            window.oromaTVApp.sendHeartbeat();
        }
    } else {
        // Page is visible, resume normal updates
        if (window.oromaTVApp) {
            window.oromaTVApp.updateViewerCount();
            window.oromaTVApp.updateComments();
        }
    }
});

// Handle before unload
window.addEventListener('beforeunload', function() {
    if (window.oromaTVApp) {
        // Send leave signal
        navigator.sendBeacon('/api/active-users/leave', JSON.stringify({
            user_session: window.oromaTVApp.userSession
        }));
    }
});