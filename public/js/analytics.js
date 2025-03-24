/**
 * FridayAI Analytics Module
 *
 * This module handles all analytics tracking and event management
 * for the FridayAI application.
 */

export class AnalyticsManager {
  constructor() {
    this.initialized = false;
    this.lastTrackedPath = null;
    this.funnels = {
      // Define key user journeys here
      acquisition: ['visit_home', 'view_features', 'view_pricing', 'create_account'],
      activation: ['create_account', 'confirm_email', 'subscribe'],
      engagement: ['login', 'visit_dashboard', 'download_app', 'launch_app'],
      conversion: ['view_pricing', 'click_subscribe', 'complete_payment', 'download_app']
    };
  }

  /**
   * Initialize Google Analytics
   */
  initialize() {
    if (this.initialized) return;

    try {
      // Add Google Analytics tag
      const googleAnalyticsId = this.getAnalyticsId();
      if (googleAnalyticsId) {
        // Load Google Analytics (GA4)
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${googleAnalyticsId}`;
        document.head.appendChild(script);

        window.dataLayer = window.dataLayer || [];
        window.gtag = function() { dataLayer.push(arguments); };
        gtag('js', new Date());
        gtag('config', googleAnalyticsId, {
          'send_page_view': true,
          'cookie_domain': 'fridayai.me',
          'cookie_flags': 'SameSite=None;Secure'
        });

        console.log('Analytics initialized');
        this.initialized = true;

        // Track initial page view
        this.trackPageView(window.location.pathname);
      }
    } catch (error) {
      console.error('Error initializing analytics:', error);
    }
  }

  /**
   * Get the Google Analytics ID from meta tag
   */
  getAnalyticsId() {
    // Get GA ID from meta tag (set in the HTML)
    const metaTag = document.querySelector('meta[name="google-analytics-id"]');
    return metaTag ? metaTag.getAttribute('content') : null;
  }

  /**
   * Track a page view
   * @param {string} path - The page path
   * @param {string} title - The page title
   */
  trackPageView(path, title = document.title) {
    if (!this.initialized) return;

    try {
      gtag('event', 'page_view', {
        page_path: path,
        page_title: title,
        page_location: window.location.href
      });
      this.lastTrackedPath = path;
    } catch (error) {
      console.error('Error tracking page view:', error);
    }
  }

  /**
   * Track a custom event
   * @param {string} eventName - The name of the event
   * @param {Object} eventParams - Additional event parameters
   */
  trackEvent(eventName, eventParams = {}) {
    if (!this.initialized) return;

    try {
      gtag('event', eventName, eventParams);
    } catch (error) {
      console.error('Error tracking event:', error);
    }
  }

  /**
   * Track a conversion (e.g., subscription, download)
   * @param {string} conversionType - Type of conversion
   * @param {Object} params - Conversion parameters
   */
  trackConversion(conversionType, params = {}) {
    if (!this.initialized) return;

    try {
      // Standard event tracking
      this.trackEvent(conversionType, params);

      // For ecommerce purchases
      if (conversionType === 'purchase') {
        gtag('event', 'purchase', {
          transaction_id: params.transactionId,
          value: params.value,
          currency: params.currency || 'USD',
          items: params.items || []
        });
      }

      // For app downloads
      if (conversionType === 'download_complete') {
        gtag('event', 'download_complete', {
          app_name: 'FridayAI',
          app_version: params.version || '1.0.0',
          os_platform: params.platform || 'unknown'
        });
      }
    } catch (error) {
      console.error('Error tracking conversion:', error);
    }
  }

  /**
   * Track a step in a user journey funnel
   * @param {string} funnelName - The name of the funnel
   * @param {string} stepName - The name of the step
   * @param {Object} params - Additional step parameters
   */
  trackFunnelStep(funnelName, stepName, params = {}) {
    if (!this.initialized || !this.funnels[funnelName]) return;

    try {
      // Get step position in funnel
      const stepIndex = this.funnels[funnelName].indexOf(stepName);
      if (stepIndex === -1) return;

      // Track the funnel step
      this.trackEvent(stepName, {
        funnel: funnelName,
        step_index: stepIndex,
        ...params
      });
    } catch (error) {
      console.error('Error tracking funnel step:', error);
    }
  }

  /**
   * Set user ID for cross-platform tracking
   * @param {string} userId - The user ID
   */
  setUserId(userId) {
    if (!this.initialized || !userId) return;

    try {
      gtag('config', this.getAnalyticsId(), {
        'user_id': userId
      });
    } catch (error) {
      console.error('Error setting user ID:', error);
    }
  }
}

// Create a single instance for the application
const analyticsManager = new AnalyticsManager();
export default analyticsManager;
