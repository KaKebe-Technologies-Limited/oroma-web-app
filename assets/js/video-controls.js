// Video controls functionality
function initializeCustomControls() {
    initializeBrightnessControl();
    initializePictureInPicture();
    initializeRadioControls();
    initializeSongRequest();
}

function initializeBrightnessControl() {
    const brightnessRange = document.getElementById('brightnessRange');
    
    if (brightnessRange && videoPlayer) {
        brightnessRange.addEventListener('input', (e) => {
            const brightness = e.target.value;
            const videoElement = videoPlayer.el().querySelector('video');
            
            if (videoElement) {
                videoElement.style.filter = `brightness(${brightness})`;
            }
        });
        
        // Reset brightness when video changes
        videoPlayer.on('loadstart', () => {
            brightnessRange.value = 1;
            const videoElement = videoPlayer.el().querySelector('video');
            if (videoElement) {
                videoElement.style.filter = 'brightness(1)';
            }
        });
    }
}

function initializePictureInPicture() {
    const pipBtn = document.getElementById('pipBtn');
    
    if (pipBtn && videoPlayer) {
        pipBtn.addEventListener('click', () => {
            const videoElement = videoPlayer.el().querySelector('video');
            
            if (videoElement && 'pictureInPictureEnabled' in document) {
                if (document.pictureInPictureElement) {
                    document.exitPictureInPicture().catch(error => {
                        console.error('Error exiting Picture-in-Picture:', error);
                        showNotification('Could not exit Picture-in-Picture mode', 'error');
                    });
                } else {
                    videoElement.requestPictureInPicture().then(() => {
                        showNotification('Entered Picture-in-Picture mode', 'success');
                    }).catch(error => {
                        console.error('Error entering Picture-in-Picture:', error);
                        showNotification('Picture-in-Picture not supported', 'warning');
                    });
                }
            } else {
                showNotification('Picture-in-Picture not supported in this browser', 'warning');
            }
        });
        
        // Update button state
        document.addEventListener('enterpictureinpicture', () => {
            pipBtn.innerHTML = '<i class="fas fa-compress"></i>';
            pipBtn.title = 'Exit Picture in Picture';
        });
        
        document.addEventListener('leavepictureinpicture', () => {
            pipBtn.innerHTML = '<i class="fas fa-external-link-alt"></i>';
            pipBtn.title = 'Picture in Picture';
        });
    }
}

function initializeRadioControls() {
    const radioPlayBtn = document.getElementById('radioPlayBtn');
    const radioStopBtn = document.getElementById('radioStopBtn');
    const radioVolumeRange = document.getElementById('radioVolumeRange');
    
    if (radioPlayer) {
        // Play button
        if (radioPlayBtn) {
            radioPlayBtn.addEventListener('click', () => {
                if (radioPlayer.paused) {
                    radioPlayer.play().then(() => {
                        radioPlayBtn.innerHTML = '<i class="fas fa-pause"></i>';
                        showNotification('Radio started playing', 'success');
                    }).catch(error => {
                        console.error('Error playing radio:', error);
                        showNotification('Could not start radio playback', 'error');
                    });
                } else {
                    radioPlayer.pause();
                    radioPlayBtn.innerHTML = '<i class="fas fa-play"></i>';
                }
            });
        }
        
        // Stop button
        if (radioStopBtn) {
            radioStopBtn.addEventListener('click', () => {
                radioPlayer.pause();
                radioPlayer.currentTime = 0;
                if (radioPlayBtn) {
                    radioPlayBtn.innerHTML = '<i class="fas fa-play"></i>';
                }
            });
        }
        
        // Volume control
        if (radioVolumeRange) {
            radioVolumeRange.addEventListener('input', (e) => {
                radioPlayer.volume = e.target.value;
            });
            
            // Set initial volume
            radioPlayer.volume = radioVolumeRange.value;
        }
        
        // Radio player events
        radioPlayer.addEventListener('play', () => {
            if (radioPlayBtn) {
                radioPlayBtn.innerHTML = '<i class="fas fa-pause"></i>';
            }
        });
        
        radioPlayer.addEventListener('pause', () => {
            if (radioPlayBtn) {
                radioPlayBtn.innerHTML = '<i class="fas fa-play"></i>';
            }
        });
        
        radioPlayer.addEventListener('ended', () => {
            if (radioPlayBtn) {
                radioPlayBtn.innerHTML = '<i class="fas fa-play"></i>';
            }
        });
        
        radioPlayer.addEventListener('error', (e) => {
            console.error('Radio player error:', e);
            showNotification('Radio stream error', 'error');
            if (radioPlayBtn) {
                radioPlayBtn.innerHTML = '<i class="fas fa-play"></i>';
            }
        });
    }
}

function initializeSongRequest() {
    const songRequestBtn = document.getElementById('songRequestBtn');
    const songRequestInput = document.getElementById('songRequestInput');
    
    if (songRequestBtn && songRequestInput) {
        const submitSongRequest = () => {
            const songTitle = songRequestInput.value.trim();
            
            if (!songTitle) {
                showNotification('Please enter a song title', 'warning');
                return;
            }
            
            // Disable button temporarily
            songRequestBtn.disabled = true;
            songRequestBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Send song request via WebSocket
            sendWebSocketMessage({
                type: 'song_request',
                data: {
                    song: songTitle,
                    timestamp: Date.now()
                }
            });
            
            // Clear input
            songRequestInput.value = '';
            
            // Show success message
            showNotification('Song request sent!', 'success');
            
            // Re-enable button after 3 seconds
            setTimeout(() => {
                songRequestBtn.disabled = false;
                songRequestBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }, 3000);
        };
        
        songRequestBtn.addEventListener('click', submitSongRequest);
        
        songRequestInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                submitSongRequest();
            }
        });
        
        // Limit input length
        songRequestInput.addEventListener('input', (e) => {
            if (e.target.value.length > 100) {
                e.target.value = e.target.value.substring(0, 100);
                showNotification('Song request too long (max 100 characters)', 'warning');
            }
        });
    }
}

// Keyboard shortcuts for video player
document.addEventListener('keydown', (e) => {
    // Only handle shortcuts when not typing in input fields
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    if (videoPlayer && currentStream === 'tv') {
        switch (e.key) {
            case ' ':
                e.preventDefault();
                if (videoPlayer.paused()) {
                    videoPlayer.play();
                } else {
                    videoPlayer.pause();
                }
                break;
                
            case 'f':
            case 'F':
                e.preventDefault();
                if (videoPlayer.isFullscreen()) {
                    videoPlayer.exitFullscreen();
                } else {
                    videoPlayer.requestFullscreen();
                }
                break;
                
            case 'm':
            case 'M':
                e.preventDefault();
                videoPlayer.muted(!videoPlayer.muted());
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                const currentVolume = videoPlayer.volume();
                videoPlayer.volume(Math.min(1, currentVolume + 0.1));
                break;
                
            case 'ArrowDown':
                e.preventDefault();
                const currentVol = videoPlayer.volume();
                videoPlayer.volume(Math.max(0, currentVol - 0.1));
                break;
                
            case 'ArrowLeft':
                e.preventDefault();
                const currentTime = videoPlayer.currentTime();
                videoPlayer.currentTime(Math.max(0, currentTime - 10));
                break;
                
            case 'ArrowRight':
                e.preventDefault();
                const time = videoPlayer.currentTime();
                videoPlayer.currentTime(time + 10);
                break;
        }
    }
});

// Fullscreen change handler
document.addEventListener('fullscreenchange', () => {
    if (videoPlayer) {
        const isFullscreen = document.fullscreenElement !== null;
        const controlsOverlay = document.querySelector('.custom-controls-overlay');
        
        if (controlsOverlay) {
            controlsOverlay.style.display = isFullscreen ? 'none' : 'flex';
        }
    }
});

// Auto-hide controls overlay when video is playing
let controlsTimeout;

function hideControlsOverlay() {
    const controlsOverlay = document.querySelector('.custom-controls-overlay');
    if (controlsOverlay && currentStream === 'tv') {
        controlsOverlay.style.opacity = '0.3';
    }
}

function showControlsOverlay() {
    const controlsOverlay = document.querySelector('.custom-controls-overlay');
    if (controlsOverlay) {
        controlsOverlay.style.opacity = '1';
        
        clearTimeout(controlsTimeout);
        controlsTimeout = setTimeout(hideControlsOverlay, 3000);
    }
}

// Add mouse movement detection for controls overlay
document.addEventListener('DOMContentLoaded', () => {
    const videoContainer = document.querySelector('.video-container');
    
    if (videoContainer) {
        videoContainer.addEventListener('mousemove', showControlsOverlay);
        videoContainer.addEventListener('mouseleave', hideControlsOverlay);
        
        // Initial setup
        showControlsOverlay();
    }
});
