// Reactions functionality
function initializeReactions() {
    const reactionItems = document.querySelectorAll('.reaction-item');
    
    reactionItems.forEach(item => {
        const button = item.querySelector('.reaction-btn');
        const reactionType = item.getAttribute('data-reaction');
        
        if (button) {
            button.addEventListener('click', () => {
                // Add click animation
                button.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    button.style.transform = '';
                }, 200);
                
                // Send reaction via WebSocket
                sendReaction(reactionType);
                
                // Show local floating reaction immediately for better UX
                showFloatingReaction(reactionType);
                
                // Temporarily disable button to prevent spam
                button.disabled = true;
                setTimeout(() => {
                    button.disabled = false;
                }, 1000);
            });
        }
    });
}

function sendReaction(reactionType) {
    // Send via WebSocket for real-time updates
    sendWebSocketMessage({
        type: 'reaction',
        data: { reaction: reactionType }
    });
    
    // Also send to API as backup
    fetch('/api/reactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reaction: reactionType })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Reaction sent successfully');
        }
    })
    .catch(error => {
        console.error('Error sending reaction:', error);
    });
}

function loadReactions() {
    fetch('/api/reactions.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                updateReactionsDisplay(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading reactions:', error);
        });
}

function updateReactionsDisplay(reactionsData) {
    // Check if reactionsData exists and has the expected structure
    if (!reactionsData) {
        console.warn('No reactions data provided');
        return;
    }
    
    // Update individual reaction counts
    const reactions = ['love', 'like', 'wow', 'laugh', 'fire'];
    
    reactions.forEach(reaction => {
        updateReactionCount(reaction, reactionsData[reaction] || 0);
    });
    
    // Update total and active viewers
    updateReactionsTotal(reactionsData.total || 0, reactionsData.active_viewers || 0);
}

function updateReactionCount(reactionType, count) {
    const reactionItem = document.querySelector(`[data-reaction="${reactionType}"]`);
    if (reactionItem) {
        const countElement = reactionItem.querySelector('.reaction-count');
        if (countElement) {
            // Animate count update
            countElement.style.transform = 'scale(1.2)';
            countElement.textContent = count;
            
            setTimeout(() => {
                countElement.style.transform = '';
            }, 300);
        }
    }
}

function updateReactionsTotal(total, activeViewers) {
    const totalElement = document.getElementById('reactionsTotal');
    if (totalElement) {
        totalElement.textContent = `${total} total reactions from ${activeViewers} active viewers`;
    }
}

function updateActiveViewers(count) {
    const totalElement = document.getElementById('reactionsTotal');
    if (totalElement) {
        // Extract current total reactions from text
        const currentText = totalElement.textContent;
        const totalMatch = currentText.match(/(\d+) total reactions/);
        const currentTotal = totalMatch ? totalMatch[1] : '0';
        
        totalElement.textContent = `${currentTotal} total reactions from ${count} active viewers`;
    }
}

function showFloatingReaction(reactionType) {
    const emojis = {
        love: '❤️',
        like: '👍',
        wow: '😮',
        laugh: '😂',
        fire: '🔥'
    };
    
    const emoji = emojis[reactionType];
    if (!emoji) return;
    
    const floatingContainer = document.getElementById('floatingReactions');
    if (!floatingContainer) return;
    
    // Create floating reaction element
    const reaction = document.createElement('div');
    reaction.className = 'floating-reaction';
    reaction.textContent = emoji;
    
    // Position randomly across the screen width
    const startX = Math.random() * (window.innerWidth - 50);
    const startY = window.innerHeight - 100;
    
    reaction.style.left = startX + 'px';
    reaction.style.top = startY + 'px';
    
    // Add random horizontal drift
    const drift = (Math.random() - 0.5) * 100;
    reaction.style.setProperty('--drift', drift + 'px');
    
    floatingContainer.appendChild(reaction);
    
    // Remove after animation completes
    setTimeout(() => {
        if (reaction.parentNode) {
            reaction.parentNode.removeChild(reaction);
        }
    }, 3000);
}

// Enhanced floating animation with drift
const style = document.createElement('style');
style.textContent = `
    @keyframes floatUp {
        0% {
            opacity: 1;
            transform: translateY(0) translateX(0) scale(1) rotate(0deg);
        }
        50% {
            opacity: 1;
            transform: translateY(-100px) translateX(var(--drift, 0px)) scale(1.2) rotate(180deg);
        }
        100% {
            opacity: 0;
            transform: translateY(-200px) translateX(var(--drift, 0px)) scale(1.5) rotate(360deg);
        }
    }
`;
document.head.appendChild(style);

// Reaction burst effect for special occasions
function triggerReactionBurst(reactionType, count = 5) {
    for (let i = 0; i < count; i++) {
        setTimeout(() => {
            showFloatingReaction(reactionType);
        }, i * 200);
    }
}

// Auto-trigger random reactions occasionally for demo (remove in production)
function simulateReactions() {
    const reactions = ['love', 'like', 'wow', 'laugh', 'fire'];
    const randomReaction = reactions[Math.floor(Math.random() * reactions.length)];
    
    // Only simulate if there are few reactions to make it look more natural
    const currentTotal = parseInt(document.getElementById('reactionsTotal')?.textContent?.match(/(\d+) total/)?.[1] || '0');
    
    if (currentTotal < 10 && Math.random() < 0.3) {
        sendReaction(randomReaction);
    }
}

// Simulate reactions every 10-30 seconds (remove in production)
setInterval(simulateReactions, Math.random() * 20000 + 10000);

// Celebration effect for milestones
function checkReactionMilestones(total) {
    const milestones = [10, 25, 50, 100, 250, 500, 1000];
    
    if (milestones.includes(total)) {
        // Trigger celebration
        setTimeout(() => triggerReactionBurst('fire', 8), 500);
        showNotification(`🎉 ${total} reactions milestone reached!`, 'success');
    }
}

// Listen for reaction updates to check milestones
document.addEventListener('DOMContentLoaded', () => {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.target.id === 'reactionsTotal') {
                const text = mutation.target.textContent;
                const totalMatch = text.match(/(\d+) total reactions/);
                if (totalMatch) {
                    const total = parseInt(totalMatch[1]);
                    checkReactionMilestones(total);
                }
            }
        });
    });
    
    const totalElement = document.getElementById('reactionsTotal');
    if (totalElement) {
        observer.observe(totalElement, { childList: true, characterData: true, subtree: true });
    }
});
