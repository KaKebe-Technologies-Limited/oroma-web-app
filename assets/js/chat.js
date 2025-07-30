// Chat functionality
let lastChatMessageTime = 0;
let chatAutoScroll = true;

function initializeChat() {
    const chatSendBtn = document.getElementById('chatSendBtn');
    const chatMessageInput = document.getElementById('chatMessageInput');
    const chatNameInput = document.getElementById('chatNameInput');
    const chatMessages = document.getElementById('chatMessages');
    
    // Load saved name from localStorage
    const savedName = localStorage.getItem('oromaTV_chatName');
    if (savedName && chatNameInput) {
        chatNameInput.value = savedName;
    }
    
    // Send message function
    const sendMessage = () => {
        const name = chatNameInput?.value?.trim();
        const message = chatMessageInput?.value?.trim();
        
        if (!name) {
            showNotification('Please enter your name', 'warning');
            chatNameInput?.focus();
            return;
        }
        
        if (!message) {
            showNotification('Please enter a message', 'warning');
            chatMessageInput?.focus();
            return;
        }
        
        if (message.length > 500) {
            showNotification('Message too long (max 500 characters)', 'warning');
            return;
        }
        
        // Save name to localStorage
        localStorage.setItem('oromaTV_chatName', name);
        
        // Disable send button temporarily to prevent spam
        if (chatSendBtn) {
            chatSendBtn.disabled = true;
            chatSendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        // Send via WebSocket for real-time updates
        sendWebSocketMessage({
            type: 'chat_message',
            data: { name, message }
        });
        
        // Also send to API as backup
        fetch('/api/chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name, message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Message sent successfully');
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
        });
        
        // Clear message input
        if (chatMessageInput) {
            chatMessageInput.value = '';
        }
        
        // Re-enable send button after 2 seconds
        setTimeout(() => {
            if (chatSendBtn) {
                chatSendBtn.disabled = false;
                chatSendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
        }, 2000);
    };
    
    // Event listeners
    if (chatSendBtn) {
        chatSendBtn.addEventListener('click', sendMessage);
    }
    
    if (chatMessageInput) {
        chatMessageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Character counter
        chatMessageInput.addEventListener('input', (e) => {
            const length = e.target.value.length;
            const maxLength = 500;
            
            if (length > maxLength - 50) {
                const remaining = maxLength - length;
                if (remaining <= 0) {
                    e.target.value = e.target.value.substring(0, maxLength);
                    showNotification('Message limit reached', 'warning');
                }
            }
        });
    }
    
    // Auto-scroll detection
    if (chatMessages) {
        chatMessages.addEventListener('scroll', () => {
            const isScrolledToBottom = chatMessages.scrollTop + chatMessages.clientHeight >= chatMessages.scrollHeight - 10;
            chatAutoScroll = isScrolledToBottom;
        });
    }
    
    // Load initial chat messages
    loadChatHistory();
}

function loadChatHistory() {
    fetch(`/api/chat.php?limit=20&since=${lastChatMessageTime}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.messages) {
                loadChatMessages(data.data.messages);
            }
        })
        .catch(error => {
            console.error('Error loading chat history:', error);
        });
}

function loadChatMessages(messages) {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;
    
    // Remove welcome message if it exists
    const welcomeMessage = chatMessages.querySelector('.chat-welcome');
    if (welcomeMessage && messages.length > 0) {
        welcomeMessage.remove();
    }
    
    // Add messages
    messages.forEach(message => {
        addChatMessage(message, false);
    });
}

function addChatMessage(messageData, shouldScroll = true) {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;
    
    // Remove welcome message if it exists
    const welcomeMessage = chatMessages.querySelector('.chat-welcome');
    if (welcomeMessage) {
        welcomeMessage.remove();
    }
    
    // Create message element
    const messageElement = document.createElement('div');
    messageElement.className = 'chat-message';
    messageElement.innerHTML = `
        <div class="chat-message-header">
            <span class="chat-message-name">${escapeHtml(messageData.name)}</span>
            <span class="chat-message-time">${messageData.formatted_time || formatTime(messageData.timestamp)}</span>
        </div>
        <div class="chat-message-text">${escapeHtml(messageData.message)}</div>
    `;
    
    // Add animation for new messages
    messageElement.style.opacity = '0';
    messageElement.style.transform = 'translateY(20px)';
    
    chatMessages.appendChild(messageElement);
    
    // Animate in
    setTimeout(() => {
        messageElement.style.opacity = '1';
        messageElement.style.transform = 'translateY(0)';
        messageElement.style.transition = 'all 0.3s ease';
    }, 50);
    
    // Update last message time
    if (messageData.timestamp) {
        lastChatMessageTime = Math.max(lastChatMessageTime, messageData.timestamp);
    }
    
    // Auto-scroll if user is at bottom
    if (shouldScroll && chatAutoScroll) {
        setTimeout(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 100);
    }
    
    // Limit number of messages in DOM to prevent performance issues
    const messages = chatMessages.querySelectorAll('.chat-message');
    if (messages.length > 50) {
        messages[0].remove();
    }
    
    // Play notification sound for new messages (optional)
    if (shouldScroll && 'Audio' in window) {
        try {
            // Create a subtle notification sound using Web Audio API
            playNotificationSound();
        } catch (error) {
            // Silently fail if audio is not supported
        }
    }
}

function playNotificationSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    } catch (error) {
        // Silently fail
    }
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { 
        hour12: false, 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Poll for new messages every 5 seconds as backup to WebSocket
setInterval(() => {
    if (lastChatMessageTime > 0) {
        fetch(`/api/chat.php?since=${lastChatMessageTime}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.messages && data.data.messages.length > 0) {
                    data.data.messages.forEach(message => {
                        addChatMessage(message, true);
                    });
                }
            })
            .catch(error => {
                console.error('Error polling for new messages:', error);
            });
    }
}, 5000);

// Chat moderation (basic profanity filter)
const profanityWords = ['spam', 'scam', 'fake', 'bot']; // Add more as needed

function moderateMessage(message) {
    const lowerMessage = message.toLowerCase();
    
    for (const word of profanityWords) {
        if (lowerMessage.includes(word)) {
            return message.replace(new RegExp(word, 'gi'), '*'.repeat(word.length));
        }
    }
    
    return message;
}

// Enhanced chat features
function addChatCommands() {
    const chatMessageInput = document.getElementById('chatMessageInput');
    
    if (chatMessageInput) {
        chatMessageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                
                const message = e.target.value;
                const lastWord = message.split(' ').pop();
                
                // Auto-complete common words/phrases
                const suggestions = {
                    'good': 'good evening',
                    'nice': 'nice show',
                    'thank': 'thank you',
                    'love': 'love this',
                    'great': 'great program'
                };
                
                if (suggestions[lastWord.toLowerCase()]) {
                    const words = message.split(' ');
                    words[words.length - 1] = suggestions[lastWord.toLowerCase()];
                    e.target.value = words.join(' ');
                }
            }
        });
    }
}

// Initialize chat commands
document.addEventListener('DOMContentLoaded', addChatCommands);

// Export functions for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        escapeHtml,
        formatTime,
        moderateMessage
    };
}
