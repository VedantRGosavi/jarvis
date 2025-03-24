/**
 * Admin Dashboard JavaScript
 * Handles authentication, data loading, charts, and user interactions
 */

class AdminDashboard {
    constructor() {
        // API base URL
        this.apiBaseUrl = '/app/api/admin';

        // Pagination settings
        this.currentPage = 1;
        this.perPage = 10;
        this.totalPages = 1;

        // Filter settings
        this.filters = {
            platform: '',
            version: '',
            start_date: '',
            end_date: '',
            status: ''
        };

        // Initialize the dashboard
        this.init();
    }

    /**
     * Initialize the dashboard
     */
    init() {
        // Check authentication
        this.checkAuth();

        // Set up event listeners
        this.setupEventListeners();

        // Show dashboard section by default
        this.showSection('dashboard');

        // Load dashboard data
        this.loadDashboardData();
    }

    /**
     * Check if user is authenticated and is admin
     */
    checkAuth() {
        const token = localStorage.getItem('token');
        if (!token) {
            window.location.href = '/login.html?redirect=admin';
            return;
        }

        // Verify token and admin status
        fetch('/app/api/auth/verify', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.user.subscription_status !== 'admin') {
                window.location.href = '/index.html';
            } else {
                // Set user info
                document.getElementById('admin-name').textContent = data.user.username || 'Admin';
            }
        })
        .catch(error => {
            console.error('Auth check failed:', error);
            window.location.href = '/login.html?redirect=admin';
        });
    }

    /**
     * Set up event listeners for UI interactions
     */
    setupEventListeners() {
        // Navigation links
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = e.currentTarget.getAttribute('data-target');
                this.showSection(target);

                // Load section data if needed
                if (target === 'dashboard') {
                    this.loadDashboardData();
                } else if (target === 'downloads') {
                    this.loadDownloadsData();
                }

                // Update active state
                document.querySelectorAll('.sidebar-nav a').forEach(link => link.classList.remove('active'));
                e.currentTarget.classList.add('active');
            });
        });

        // Logout button
        document.getElementById('logout-btn').addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem('token');
            window.location.href = '/login.html';
        });

        // Refresh data button
        document.getElementById('refresh-data').addEventListener('click', () => {
            const activeSection = document.querySelector('.section:not(.hidden)').id;
            if (activeSection === 'dashboard-section') {
                this.loadDashboardData();
            } else if (activeSection === 'downloads-section') {
                this.loadDownloadsData();
            }
        });

        // Filter toggle
        document.getElementById('filter-toggle').addEventListener('click', () => {
            const filterContent = document.getElementById('filter-content');
            filterContent.classList.toggle('hidden');
        });

        // Apply filters
        document.getElementById('apply-filters').addEventListener('click', () => {
            this.filters.platform = document.getElementById('filter-platform').value;
            this.filters.version = document.getElementById('filter-version').value;
            this.filters.start_date = document.getElementById('filter-start-date').value;
            this.filters.end_date = document.getElementById('filter-end-date').value;
            this.filters.status = document.getElementById('filter-status').value;

            this.currentPage = 1;
            this.loadDownloadsData();
        });

        // Reset filters
        document.getElementById('reset-filters').addEventListener('click', () => {
            document.getElementById('filter-platform').value = '';
            document.getElementById('filter-version').value = '';
            document.getElementById('filter-start-date').value = '';
            document.getElementById('filter-end-date').value = '';
            document.getElementById('filter-status').value = '';

            this.filters = {
                platform: '',
                version: '',
                start_date: '',
                end_date: '',
                status: ''
            };

            this.currentPage = 1;
            this.loadDownloadsData();
        });

        // Pagination controls
        document.getElementById('prev-page').addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadDownloadsData();
            }
        });

        document.getElementById('next-page').addEventListener('click', () => {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadDownloadsData();
            }
        });
    }

    /**
     * Show only the selected section and hide others
     */
    showSection(section) {
        // Hide all sections first
        document.querySelectorAll('.section').forEach(el => el.classList.add('hidden'));

        // Show the requested section
        const sectionEl = document.getElementById(`${section}-section`);
        if (sectionEl) {
            sectionEl.classList.remove('hidden');
        }
    }

    /**
     * Load data for the dashboard summary
     */
    loadDashboardData() {
        const token = localStorage.getItem('token');

        // Show loading state
        document.getElementById('dashboard-loader').classList.remove('hidden');
        document.getElementById('dashboard-content').classList.add('hidden');

        fetch(`${this.apiBaseUrl}/analytics.php?action=summary`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateDashboardUI(data.data);
            } else {
                alert('Failed to load dashboard data: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Dashboard data load failed:', error);
            alert('Failed to load dashboard data. Please try again.');
        })
        .finally(() => {
            // Hide loading state
            document.getElementById('dashboard-loader').classList.add('hidden');
            document.getElementById('dashboard-content').classList.remove('hidden');
        });
    }

    /**
     * Load download records with pagination and filters
     */
    loadDownloadsData() {
        const token = localStorage.getItem('token');

        // Show loading state
        document.getElementById('downloads-loader').classList.remove('hidden');
        document.getElementById('downloads-content').classList.add('hidden');

        // Prepare query parameters
        const queryParams = new URLSearchParams({
            action: 'records',
            page: this.currentPage,
            per_page: this.perPage
        });

        // Add filters if they're set
        Object.keys(this.filters).forEach(key => {
            if (this.filters[key]) {
                queryParams.append(key, this.filters[key]);
            }
        });

        fetch(`${this.apiBaseUrl}/analytics.php?${queryParams.toString()}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateDownloadsUI(data.data);
            } else {
                alert('Failed to load downloads data: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Downloads data load failed:', error);
            alert('Failed to load downloads data. Please try again.');
        })
        .finally(() => {
            // Hide loading state
            document.getElementById('downloads-loader').classList.add('hidden');
            document.getElementById('downloads-content').classList.remove('hidden');
        });
    }

    /**
     * Update the dashboard UI with fetched data
     */
    updateDashboardUI(data) {
        // Update total downloads
        document.getElementById('total-downloads-value').textContent = data.total_downloads;

        // Update successful downloads
        document.getElementById('successful-downloads-value').textContent = data.successful_downloads;

        // Update platform specific downloads
        const platformData = data.by_platform;
        const platforms = {
            'windows': 'windows-downloads-value',
            'win': 'windows-downloads-value',
            'mac': 'mac-downloads-value',
            'macos': 'mac-downloads-value',
            'linux': 'linux-downloads-value'
        };

        // Reset all platform values to 0
        Object.values(platforms).forEach(id => {
            document.getElementById(id).textContent = '0';
        });

        // Update with actual data
        platformData.forEach(platform => {
            const key = platform.platform.toLowerCase();
            const elementId = platforms[key];
            if (elementId) {
                document.getElementById(elementId).textContent = platform.count;
            }
        });

        // Display total download size if available
        if (data.total_download_size) {
            const formattedSize = this.formatFileSize(data.total_download_size);
            document.getElementById('total-download-size').textContent = formattedSize;
        }

        // Create charts
        this.createPlatformChart(data.by_platform);
        this.createTimelineChart(data.timeline);

        // Create browser chart if data is available
        if (data.browser_stats && data.browser_stats.length > 0) {
            this.createBrowserChart(data.browser_stats);
        }
    }

    /**
     * Update the downloads UI with fetched data
     */
    updateDownloadsUI(data) {
        const tbody = document.getElementById('downloads-table-body');
        tbody.innerHTML = '';

        // Update pagination info
        this.totalPages = data.total_pages;
        document.getElementById('current-page').textContent = data.page;
        document.getElementById('total-pages').textContent = data.total_pages;
        document.getElementById('total-records').textContent = data.total;

        // Update pagination button states
        document.getElementById('prev-page').disabled = data.page <= 1;
        document.getElementById('next-page').disabled = data.page >= data.total_pages;

        // No records message
        if (data.records.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="7" class="text-center">No download records found</td>`;
            tbody.appendChild(tr);
            return;
        }

        // Add records to table
        data.records.forEach(record => {
            const tr = document.createElement('tr');

            const date = new Date(record.created_at);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();

            // Determine status class
            let statusClass = 'status-normal';
            if (record.download_status === 'failed') {
                statusClass = 'status-error';
            } else if (record.download_status === 'in_progress') {
                statusClass = 'status-warning';
            }

            tr.innerHTML = `
                <td>${record.id}</td>
                <td>${record.platform || 'N/A'}</td>
                <td>${record.version || 'N/A'}</td>
                <td>${record.user_email || 'Anonymous'}</td>
                <td>${formattedDate}</td>
                <td>${record.ip_address || 'N/A'}</td>
                <td><span class="${statusClass}">${record.download_status || 'completed'}</span></td>
            `;

            tbody.appendChild(tr);
        });
    }

    /**
     * Create platform distribution chart
     */
    createPlatformChart(platformData) {
        const ctx = document.getElementById('platform-chart').getContext('2d');

        // Clear any existing chart
        if (this.platformChart) {
            this.platformChart.destroy();
        }

        // Prepare data for chart
        const labels = [];
        const counts = [];
        const colors = [
            'rgba(98, 0, 234, 0.8)',
            'rgba(3, 218, 198, 0.8)',
            'rgba(255, 214, 0, 0.8)',
            'rgba(207, 102, 121, 0.8)'
        ];

        platformData.forEach((item, index) => {
            labels.push(item.platform);
            counts.push(item.count);
        });

        // Create chart
        this.platformChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors.slice(0, platformData.length),
                    borderColor: 'rgba(30, 30, 30, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#e0e0e0'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Downloads by Platform',
                        color: '#e0e0e0',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    }

    /**
     * Create browser distribution chart
     */
    createBrowserChart(browserData) {
        const ctx = document.getElementById('browser-chart').getContext('2d');

        // Clear any existing chart
        if (this.browserChart) {
            this.browserChart.destroy();
        }

        // Prepare data for chart
        const labels = [];
        const counts = [];
        const colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)'
        ];

        browserData.forEach((item, index) => {
            labels.push(item.browser);
            counts.push(item.count);
        });

        // Create chart
        this.browserChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors.slice(0, browserData.length),
                    borderColor: 'rgba(30, 30, 30, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#e0e0e0'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Downloads by Browser',
                        color: '#e0e0e0',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    }

    /**
     * Create timeline chart for downloads
     */
    createTimelineChart(timelineData) {
        const ctx = document.getElementById('timeline-chart').getContext('2d');

        // Clear any existing chart
        if (this.timelineChart) {
            this.timelineChart.destroy();
        }

        // Prepare data for chart
        const labels = [];
        const counts = [];

        timelineData.forEach(item => {
            labels.push(item.date);
            counts.push(item.count);
        });

        // Create chart
        this.timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Downloads',
                    data: counts,
                    backgroundColor: 'rgba(98, 0, 234, 0.2)',
                    borderColor: 'rgba(98, 0, 234, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Download Trend (Last 30 Days)',
                        color: '#e0e0e0',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return 'Date: ' + tooltipItems[0].label;
                            },
                            label: function(context) {
                                return 'Downloads: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0a0'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#a0a0a0',
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes === 0 || !bytes) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Initialize the admin dashboard when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.adminDashboard = new AdminDashboard();
});
