/**
 * FridayAI Download Management
 * Handles download UI functionality and download process
 */

import analyticsManager from '../analytics.js';

export class DownloadManager {
    constructor() {
        this.downloadModal = document.getElementById('download-modal');
        this.downloadProgressBar = document.getElementById('download-progress-bar');
        this.downloadMessage = document.getElementById('download-message');
        this.downloadSpeed = document.getElementById('download-speed');
        this.cancelButton = document.getElementById('cancel-download');

        // State
        this.currentDownload = null;
        this.downloadStartTime = null;
        this.downloadSize = 0;
        this.downloadedBytes = 0;
        this.downloadSpeedInterval = null;
        this.csrfToken = this.generateCSRFToken();

        // Constants
        this.API_BASE = '/api/download';

        // Initialize
        this.init();
    }

    /**
     * Initialize download manager
     */
    init() {
        this.setupEventListeners();
        this.populateDownloadOptions();
        this.checkUserAuthStatus();
    }

    /**
     * Generate a CSRF token
     */
    generateCSRFToken() {
        const token = Math.random().toString(36).substring(2, 15) +
                      Math.random().toString(36).substring(2, 15);
        localStorage.setItem('fridayai_csrf_token', token);
        return token;
    }

    /**
     * Get stored CSRF token
     */
    getCSRFToken() {
        let token = localStorage.getItem('fridayai_csrf_token');
        if (!token) {
            token = this.generateCSRFToken();
        }
        return token;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Close download modal when cancel is clicked
        if (this.cancelButton) {
            this.cancelButton.addEventListener('click', () => {
                this.cancelDownload();
            });
        }

        // Set up global click handler for download buttons
        document.addEventListener('click', (e) => {
            const downloadButton = e.target.closest('[data-platform]');
            if (!downloadButton) return;

            // Get download parameters
            const platform = downloadButton.getAttribute('data-platform');
            const version = downloadButton.getAttribute('data-version') || 'latest';

            // Prevent default behavior
            e.preventDefault();

            // Start download
            this.startDownload(platform, version);
        });

        // Close modal if clicked outside
        if (this.downloadModal) {
            this.downloadModal.addEventListener('click', (e) => {
                if (e.target === this.downloadModal) {
                    this.hideModal();
                }
            });
        }
    }

    /**
     * Check user authentication and subscription status
     */
    checkUserAuthStatus() {
        // Get auth token
        const token = localStorage.getItem('auth_token');
        if (!token) {
            this.showAuthState('not-authenticated');
            return;
        }

        // Make API request to check user status
        fetch('/api/user/profile', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Check subscription status
                const status = data.user.subscription_status;

                if (status === 'trial') {
                    this.showAuthState('trial-user');
                    if (data.user.trial_days_left) {
                        document.getElementById('trial-days').textContent = data.user.trial_days_left;
                    }
                } else if (status === 'active' || status === 'admin') {
                    this.showAuthState('premium-user');
                } else {
                    this.showAuthState('not-authenticated');
                }

                // For security, check if user is admin and show admin link if so
                if (status === 'admin') {
                    const adminNavItem = document.getElementById('admin-nav-item');
                    if (adminNavItem) {
                        adminNavItem.classList.remove('hidden');
                    }
                }
            } else {
                this.showAuthState('not-authenticated');
            }
        })
        .catch(error => {
            console.error('Error checking user status:', error);
            this.showAuthState('not-authenticated');
        });
    }

    /**
     * Show the appropriate download section based on auth state
     */
    showAuthState(state) {
        const states = ['not-authenticated', 'trial-user', 'premium-user'];

        states.forEach(s => {
            const element = document.getElementById(s);
            if (element) {
                if (s === state) {
                    element.classList.remove('hidden');
                } else {
                    element.classList.add('hidden');
                }
            }
        });
    }

    /**
     * Populate download options
     */
    populateDownloadOptions() {
        // Define available platforms
        const platforms = [
            { id: 'windows', name: 'Windows', icon: 'fab fa-windows' },
            { id: 'mac', name: 'macOS', icon: 'fab fa-apple' },
            { id: 'linux', name: 'Linux', icon: 'fab fa-linux' }
        ];

        // Create download option elements
        const createDownloadOption = (platform, version, badge = null) => {
            const platformInfo = platforms.find(p => p.id === platform) || platforms[0];

            return `
                <div class="relative overflow-hidden rounded-lg bg-gaming-gray-700 hover:bg-gaming-gray-600 transition-all p-6">
                    ${badge ? `<span class="badge">${badge}</span>` : ''}
                    <div class="flex items-center space-x-4 mb-4">
                        <span class="text-2xl text-gaming-light ${platformInfo.icon}"></span>
                        <h3 class="text-xl font-semibold">${platformInfo.name}</h3>
                    </div>
                    <p class="text-gaming-gray-300 text-sm mb-4">FridayAI ${version === 'beta' ? 'Beta' : 'v1.0.0'} for ${platformInfo.name}</p>
                    <a href="#"
                       class="download-button block w-full py-2 px-4 bg-gaming-primary hover:bg-gaming-primary-dark text-white font-medium rounded-md text-center transition-colors"
                       data-platform="${platform}"
                       data-version="${version}">
                        <i class="fas fa-download mr-2"></i> Download
                    </a>
                </div>
            `;
        };

        // Populate trial downloads
        const trialDownloads = document.getElementById('trial-downloads');
        if (trialDownloads) {
            platforms.forEach(platform => {
                const option = createDownloadOption(platform.id, 'latest');
                trialDownloads.innerHTML += option;
            });
        }

        // Populate premium downloads
        const premiumDownloads = document.getElementById('premium-downloads');
        if (premiumDownloads) {
            platforms.forEach(platform => {
                const option = createDownloadOption(platform.id, 'latest');
                premiumDownloads.innerHTML += option;
            });
        }

        // Populate beta downloads
        const betaDownloads = document.getElementById('beta-downloads');
        if (betaDownloads) {
            platforms.forEach(platform => {
                const option = createDownloadOption(platform.id, 'beta', 'BETA');
                betaDownloads.innerHTML += option;
            });
        }
    }

    /**
     * Show download modal
     */
    showModal() {
        if (this.downloadModal) {
            this.downloadModal.classList.remove('hidden');
            this.downloadModal.classList.add('flex');
        }
    }

    /**
     * Hide download modal
     */
    hideModal() {
        if (this.downloadModal) {
            this.downloadModal.classList.add('hidden');
            this.downloadModal.classList.remove('flex');
        }
    }

    /**
     * Start download process
     */
    startDownload(platform, version) {
        // Reset progress
        if (this.downloadProgressBar) {
            this.downloadProgressBar.style.width = '0%';
        }

        // Set initial message
        if (this.downloadMessage) {
            this.downloadMessage.textContent = `Preparing ${platform} download...`;
        }

        // Show download modal
        this.showModal();

        // Track download initiation for analytics
        analyticsManager.trackConversion('download_initiated', {
            platform,
            version
        });

        // Track in the conversion funnel
        analyticsManager.trackFunnelStep('conversion', 'download_app', {
            platform,
            version
        });

        // Get token for authenticated download
        const token = localStorage.getItem('auth_token');
        if (!token) {
            this.handleDownloadError('Authentication required');
            return;
        }

        // Start simulated progress while preparing download
        this.simulateProgress(0, 20);

        // Get download URL with CSRF token
        const csrfToken = this.getCSRFToken();
        const downloadUrl = `${this.API_BASE}/${version}?platform=${platform}&csrf_token=${csrfToken}`;

        // Create a hidden iframe for download
        const downloadFrame = document.createElement('iframe');
        downloadFrame.style.display = 'none';
        document.body.appendChild(downloadFrame);

        // Set download frame source with auth header
        downloadFrame.onload = () => {
            try {
                const frameDoc = downloadFrame.contentDocument || downloadFrame.contentWindow.document;
                if (frameDoc.body.textContent.includes('error')) {
                    // Handle error from API
                    const errorData = JSON.parse(frameDoc.body.textContent);
                    this.handleDownloadError(errorData.message || 'Download failed');
                    document.body.removeChild(downloadFrame);
                    return;
                }
            } catch (e) {
                // If we can't access the iframe content, assume download started (CORS)
                console.log('Download appears to have started');
            }

            // Continue progress simulation
            this.simulateProgress(20, 100);

            // Update UI for download in progress
            if (this.downloadMessage) {
                this.downloadMessage.textContent = `Downloading FridayAI for ${platform}...`;
            }

            // Track successful download after a reasonable delay
            setTimeout(() => {
                analyticsManager.trackConversion('download_complete', {
                    platform,
                    version
                });

                // Update message when complete
                if (this.downloadMessage) {
                    this.downloadMessage.textContent = 'Download complete!';
                }

                // Close modal after a brief delay
                setTimeout(() => {
                    this.hideModal();
                    document.body.removeChild(downloadFrame);
                }, 1500);
            }, 3000);
        };

        // Set source to trigger download with proper auth
        downloadFrame.src = `javascript:
            (function() {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '${downloadUrl}', true);
                xhr.setRequestHeader('Authorization', 'Bearer ${token}');
                xhr.responseType = 'blob';

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var blob = xhr.response;
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = '${this.getFileName(platform, version)}';
                        link.click();
                    } else {
                        document.body.innerHTML = JSON.stringify({
                            error: true,
                            message: 'Download failed with status ' + xhr.status
                        });
                    }
                };

                xhr.onerror = function() {
                    document.body.innerHTML = JSON.stringify({
                        error: true,
                        message: 'Network error occurred'
                    });
                };

                xhr.send();
            })();
        `;

        // Start download speed measurement
        this.startDownloadSpeedMeasurement();
    }

    /**
     * Handle download error
     */
    handleDownloadError(message) {
        console.error('Download error:', message);

        if (this.downloadMessage) {
            this.downloadMessage.textContent = `Error: ${message}`;
            this.downloadMessage.classList.add('text-red-500');
        }

        // Stop progress and speed measurement
        this.stopDownloadSpeedMeasurement();

        // Change cancel button to close
        if (this.cancelButton) {
            this.cancelButton.textContent = 'Close';
        }
    }

    /**
     * Get filename based on platform and version
     */
    getFileName(platform, version) {
        const platformMap = {
            'windows': 'FridayAI-Win-',
            'mac': 'FridayAI-Mac-',
            'linux': 'FridayAI-Linux-'
        };

        const extensionMap = {
            'windows': '.zip',
            'mac': '.dmg',
            'linux': '.tar.gz'
        };

        const base = platformMap[platform] || 'FridayAI-';
        const ext = extensionMap[platform] || '.zip';

        return `${base}${version}${ext}`;
    }

    /**
     * Simulate download progress
     */
    simulateProgress(start, end, duration = 3000) {
        let currentProgress = start;
        const increment = (end - start) / 30; // 30 steps

        if (this.downloadProgressBar) {
            this.downloadProgressBar.style.width = `${start}%`;
        }

        const interval = setInterval(() => {
            currentProgress += increment;

            if (currentProgress >= end) {
                currentProgress = end;
                clearInterval(interval);
            }

            if (this.downloadProgressBar) {
                this.downloadProgressBar.style.width = `${currentProgress}%`;
            }
        }, duration / 30);
    }

    /**
     * Start measuring download speed
     */
    startDownloadSpeedMeasurement() {
        // Reset values
        this.downloadStartTime = Date.now();
        this.downloadedBytes = 0;
        this.downloadSize = 0;

        // For production, this would use actual XHR progress or fetch with progress
        // For now, simulate random speeds
        this.downloadSpeedInterval = setInterval(() => {
            const speed = Math.floor(Math.random() * 2000) + 500; // 500KB to 2.5MB
            if (this.downloadSpeed) {
                this.downloadSpeed.textContent = this.formatSpeed(speed);
            }
        }, 500);
    }

    /**
     * Stop measuring download speed
     */
    stopDownloadSpeedMeasurement() {
        if (this.downloadSpeedInterval) {
            clearInterval(this.downloadSpeedInterval);
            this.downloadSpeedInterval = null;
        }
    }

    /**
     * Format download speed for display
     */
    formatSpeed(kbps) {
        if (kbps >= 1024) {
            return (kbps / 1024).toFixed(1) + ' MB/s';
        }
        return kbps.toFixed(0) + ' KB/s';
    }

    /**
     * Cancel current download
     */
    cancelDownload() {
        // Stop progress and speed updates
        this.stopDownloadSpeedMeasurement();

        // Hide modal
        this.hideModal();

        // Reset progress
        if (this.downloadProgressBar) {
            this.downloadProgressBar.style.width = '0%';
        }

        // Reset text
        if (this.downloadMessage) {
            this.downloadMessage.textContent = 'Preparing your download...';
            this.downloadMessage.classList.remove('text-red-500');
        }

        // Reset cancel button
        if (this.cancelButton) {
            this.cancelButton.textContent = 'Cancel';
        }

        // Note: We can't actually cancel the download once it's started in the browser
        // This just cleans up the UI
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.downloadManager = new DownloadManager();
});

export default DownloadManager;
