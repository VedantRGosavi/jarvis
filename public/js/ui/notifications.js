/**
 * Notifications Module
 * Handles in-app notifications and alerts
 */

class NotificationSystem {
  constructor() {
    this.container = null;
    this.notifications = [];
    this.maxNotifications = 3;
    this.defaultDuration = 5000; // 5 seconds
    this.enabled = this.loadSettings().enabled;

    // Create notification container
    this.initialize();
  }

  /**
   * Initialize the notification system
   */
  initialize() {
    // Create container if it doesn't exist
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'notification-container';
      this.container.className = 'fixed bottom-4 right-4 w-64 space-y-2 z-50';
      document.body.appendChild(this.container);
    }
  }

  /**
   * Show a notification
   * @param {string} message - Notification message
   * @param {string} type - Notification type (info, success, warning, error)
   * @param {number} duration - Duration in ms (0 for permanent)
   * @returns {string} Notification ID
   */
  show(message, type = 'info', duration = this.defaultDuration) {
    if (!this.enabled) return null;

    // Generate unique ID
    const id = 'notification-' + Date.now();

    // Create notification element
    const notification = document.createElement('div');
    notification.id = id;
    notification.className = `notification bg-gaming-gray-800 border-l-4 rounded p-3 shadow-lg transform transition-all duration-300 translate-x-full`;

    // Add appropriate border color based on type
    switch (type) {
      case 'success':
        notification.classList.add('border-green-500');
        break;
      case 'warning':
        notification.classList.add('border-yellow-500');
        break;
      case 'error':
        notification.classList.add('border-red-500');
        break;
      default:
        notification.classList.add('border-gaming-gray-500');
    }

    // Add content
    notification.innerHTML = `
      <div class="flex justify-between items-start">
        <div class="flex-1 pr-2">
          <p class="text-xs text-gaming-light">${message}</p>
        </div>
        <button class="text-gaming-gray-400 hover:text-gaming-gray-200 text-xs notification-close" data-id="${id}">×</button>
      </div>
    `;

    // Add to container
    this.container.appendChild(notification);

    // Add to notifications array
    this.notifications.push({
      id,
      element: notification,
      timer: duration > 0 ? setTimeout(() => this.dismiss(id), duration) : null
    });

    // Remove oldest notifications if we exceed the maximum
    if (this.notifications.length > this.maxNotifications) {
      const oldest = this.notifications.shift();
      this.dismiss(oldest.id);
    }

    // Add close button event listener
    const closeBtn = notification.querySelector('.notification-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        this.dismiss(id);
      });
    }

    // Animate in
    setTimeout(() => {
      notification.classList.remove('translate-x-full');
    }, 10);

    return id;
  }

  /**
   * Show a success notification
   * @param {string} message - Notification message
   * @param {number} duration - Duration in ms
   * @returns {string} Notification ID
   */
  success(message, duration = this.defaultDuration) {
    return this.show(message, 'success', duration);
  }

  /**
   * Show a warning notification
   * @param {string} message - Notification message
   * @param {number} duration - Duration in ms
   * @returns {string} Notification ID
   */
  warning(message, duration = this.defaultDuration) {
    return this.show(message, 'warning', duration);
  }

  /**
   * Show an error notification
   * @param {string} message - Notification message
   * @param {number} duration - Duration in ms
   * @returns {string} Notification ID
   */
  error(message, duration = this.defaultDuration) {
    return this.show(message, 'error', duration);
  }

  /**
   * Dismiss a notification
   * @param {string} id - Notification ID
   */
  dismiss(id) {
    const index = this.notifications.findIndex(n => n.id === id);
    if (index === -1) return;

    const notification = this.notifications[index];

    // Clear timer if exists
    if (notification.timer) {
      clearTimeout(notification.timer);
    }

    // Animate out
    notification.element.classList.add('translate-x-full');

    // Remove after animation
    setTimeout(() => {
      if (notification.element.parentNode) {
        notification.element.parentNode.removeChild(notification.element);
      }
    }, 300);

    // Remove from array
    this.notifications.splice(index, 1);
  }

  /**
   * Dismiss all notifications
   */
  dismissAll() {
    [...this.notifications].forEach(notification => {
      this.dismiss(notification.id);
    });
  }

  /**
   * Enable notifications
   */
  enable() {
    this.enabled = true;
    this.saveSettings();
  }

  /**
   * Disable notifications
   */
  disable() {
    this.enabled = false;
    this.dismissAll();
    this.saveSettings();
  }

  /**
   * Toggle notifications
   * @returns {boolean} New enabled state
   */
  toggle() {
    this.enabled = !this.enabled;
    this.saveSettings();

    if (!this.enabled) {
      this.dismissAll();
    }

    return this.enabled;
  }

  /**
   * Load notification settings
   * @returns {Object} Settings
   */
  loadSettings() {
    try {
      const settings = localStorage.getItem('fridayai_notification_settings');
      return settings ? JSON.parse(settings) : { enabled: true };
    } catch (e) {
      console.error('Error loading notification settings:', e);
      return { enabled: true };
    }
  }

  /**
   * Save notification settings
   */
  saveSettings() {
    try {
      localStorage.setItem('fridayai_notification_settings', JSON.stringify({
        enabled: this.enabled
      }));
    } catch (e) {
      console.error('Error saving notification settings:', e);
    }
  }
}

// Initialize notification system
document.addEventListener('DOMContentLoaded', () => {
  const notifications = new NotificationSystem();

  // Expose to global scope
  window.notifications = notifications;

  // Link to settings panel
  if (window.settingsPanel) {
    document.addEventListener('settingsPanelReady', () => {
      const notificationToggle = document.getElementById('show-notifications');
      if (notificationToggle) {
        notificationToggle.checked = notifications.enabled;

        notificationToggle.addEventListener('change', (e) => {
          if (e.target.checked) {
            notifications.enable();
          } else {
            notifications.disable();
          }
        });
      }
    });
  }

  // Test notifications (for development only)
  if (window.location.search.includes('test-notifications')) {
    setTimeout(() => {
      notifications.show('This is an info notification');
      setTimeout(() => {
        notifications.success('Operation completed successfully');
        setTimeout(() => {
          notifications.warning('Low disk space');
          setTimeout(() => {
            notifications.error('Connection failed');
          }, 1000);
        }, 1000);
      }, 1000);
    }, 1000);
  }
});
