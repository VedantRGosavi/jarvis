/**
 * DIRECT EMERGENCY FIX
 * No PHP dependencies, just pure JavaScript
 * This script is loaded FIRST before anything else to guarantee content visibility
 */

// Execute immediately
(function() {
    console.log('🚨 DIRECT EMERGENCY FIX LOADED - PRIORITY 1');

    // Force display function that works consistently
    function forceDisplay() {
        console.log('🚨 FORCING DISPLAY OF ALL CONTENT');

        // Add classes to body
        document.body.classList.add('app-initialized');
        document.body.classList.add('force-display');

        // Force all sections and important elements to display
        const selectors = [
            'section', 'footer', '#features', '#games', '#pricing', '#docs',
            '.features-section', '.games-section', '.pricing-section', '.docs-section',
            '.footer-section', '.page-container'
        ];

        selectors.forEach(function(selector) {
            const elements = document.querySelectorAll(selector);
            elements.forEach(function(el) {
                if (el) {
                    el.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; position: relative !important;';
                    el.classList.add('force-visible');
                }
            });
        });

        // If the window fridayAIApp is available, use its method too
        if (window.fridayAIApp && typeof window.fridayAIApp.forceDisplayAllContent === 'function') {
            window.fridayAIApp.forceDisplayAllContent();
        }

        // If the global function is available, use it too
        if (window.forceDisplayContent && typeof window.forceDisplayContent === 'function') {
            window.forceDisplayContent();
        }
    }

    // Run immediately and add event listeners
    forceDisplay();
    document.addEventListener('DOMContentLoaded', forceDisplay);
    window.addEventListener('load', forceDisplay);

    // Also run delayed to catch any late rendering issues
    setTimeout(forceDisplay, 500);
    setTimeout(forceDisplay, 1000);

    // Global helper function that can be called from anywhere
    window.emergencyForceDisplay = forceDisplay;

    // Add direct CSS rules for extra protection
    const emergencyStyle = document.createElement('style');
    emergencyStyle.textContent = `
        body section, body footer, #features, #games, #pricing, #docs,
        .features-section, .games-section, .pricing-section, .docs-section, .footer-section,
        .page-container, .force-visible {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
        }
    `;
    document.head.appendChild(emergencyStyle);
})();
