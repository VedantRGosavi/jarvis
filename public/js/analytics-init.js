/**
 * FridayAI Analytics Initialization
 *
 * This script initializes analytics tracking and adds event listeners
 * for various user interactions throughout the application.
 */

import analyticsManager from './analytics.js';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize analytics
    analyticsManager.initialize();

    // Track page view
    analyticsManager.trackPageView(window.location.pathname);

    // =============================================
    // Track user acquisition funnel
    // =============================================

    // Home page visit
    if (window.location.pathname === '/' || window.location.pathname.includes('index.html')) {
        analyticsManager.trackFunnelStep('acquisition', 'visit_home');

        // Track when Features section becomes visible
        const featuresSection = document.getElementById('features');
        if (featuresSection) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        analyticsManager.trackFunnelStep('acquisition', 'view_features');
                        observer.unobserve(featuresSection);
                    }
                });
            }, { threshold: 0.5 });
            observer.observe(featuresSection);
        }

        // Track when Pricing section becomes visible
        const pricingSection = document.getElementById('pricing');
        if (pricingSection) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        analyticsManager.trackFunnelStep('acquisition', 'view_pricing');
                        analyticsManager.trackFunnelStep('conversion', 'view_pricing');
                        observer.unobserve(pricingSection);
                    }
                });
            }, { threshold: 0.5 });
            observer.observe(pricingSection);
        }
    }

    // =============================================
    // Track download funnel
    // =============================================

    // Download page visit
    if (window.location.pathname.includes('download.html')) {
        analyticsManager.trackFunnelStep('conversion', 'view_download_page');

        // Track download button clicks
        const downloadButtons = document.querySelectorAll('a[data-platform]');
        downloadButtons.forEach(button => {
            button.addEventListener('click', function() {
                const platform = this.getAttribute('data-platform') || 'unknown';
                const version = this.getAttribute('data-version') || '1.0.0';

                analyticsManager.trackConversion('download_initiated', {
                    platform,
                    version
                });

                analyticsManager.trackFunnelStep('conversion', 'download_app', {
                    platform,
                    version
                });

                // Track download completion after a delay
                setTimeout(() => {
                    analyticsManager.trackConversion('download_complete', {
                        platform,
                        version
                    });
                }, 5000); // Simulate 5-second download
            });
        });
    }

    // =============================================
    // Track subscription conversion funnel
    // =============================================

    // Track subscribe button clicks (add class to these buttons in the existing code)
    document.addEventListener('click', function(e) {
        // Check if the clicked element is a subscription button or inside one
        const button = e.target.closest('.pricing-card button');
        if (button) {
            // Find which card it belongs to
            const card = button.closest('.pricing-card');
            if (!card) return;

            // Get tier information (estimate based on DOM position)
            const allCards = Array.from(document.querySelectorAll('.pricing-card'));
            const index = allCards.indexOf(card);
            const tierName = card.querySelector('h3')?.textContent || `Tier ${index + 1}`;
            const priceElement = card.querySelector('.text-5xl');
            let price = 0;
            if (priceElement) {
                const priceText = priceElement.textContent;
                price = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;
            }

            // Track click event
            analyticsManager.trackEvent('click_subscribe_button', {
                tier_name: tierName,
                tier_price: price
            });

            // Track conversion funnel step
            analyticsManager.trackFunnelStep('conversion', 'click_subscribe', {
                tier_name: tierName,
                tier_price: price
            });
        }
    });

    // Attempt to intercept payment success events
    // This is a simplified approach - ideally this would be handled in the payment callbacks
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        const promise = originalFetch.apply(this, arguments);

        // Check if this is a payment-related request
        if (typeof url === 'string' &&
            (url.includes('/api/payment') || url.includes('/api/subscription'))) {
            promise.then(response => {
                // Clone the response so we can read it and still return the original
                const clone = response.clone();
                clone.json().then(data => {
                    // Check if it's a successful payment
                    if (data && data.success === true) {
                        analyticsManager.trackConversion('purchase', {
                            transactionId: data.id || `purchase_${Date.now()}`,
                            value: data.amount ? data.amount / 100 : 0, // Convert from cents
                            currency: data.currency || 'USD'
                        });

                        // Track conversion funnel completion
                        analyticsManager.trackFunnelStep('conversion', 'complete_payment', {
                            payment_method: data.payment_method || 'unknown',
                            payment_type: data.type || 'unknown'
                        });
                    }
                }).catch(() => {
                    // Ignore JSON parsing errors
                });
            });
        }

        return promise;
    };

    // =============================================
    // Track user ID for cross-device tracking
    // =============================================

    // Set user ID for analytics if available
    const userId = localStorage.getItem('fridayai_user_id');
    if (userId) {
        analyticsManager.setUserId(userId);
    }

    // Listen for login events
    window.addEventListener('fridayai:login', function(e) {
        const userData = e.detail || {};
        if (userData.id) {
            analyticsManager.setUserId(userData.id);
            analyticsManager.trackFunnelStep('activation', 'login');
        }
    });

    // Listen for signup events
    window.addEventListener('fridayai:signup', function(e) {
        const userData = e.detail || {};
        if (userData.id) {
            analyticsManager.setUserId(userData.id);
            analyticsManager.trackFunnelStep('acquisition', 'create_account');
            analyticsManager.trackFunnelStep('activation', 'create_account');
        }
    });
});
