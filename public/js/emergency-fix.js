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
        if (document.body) {
            document.body.classList.add('app-initialized');
            document.body.classList.add('force-display');
        }

        // Force all sections and important elements to display
        const selectors = [
            'section', 'footer', '#features', '#games', '#pricing', '#docs',
            '.features-section', '.games-section', '.pricing-section', '.docs-section',
            '.footer-section', '.page-container'
        ];

        selectors.forEach(function(selector) {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(function(el) {
                    if (el) {
                        el.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; position: relative !important;';
                        el.classList.add('force-visible');
                    }
                });
            } catch (e) {
                console.error('Error selecting elements:', e);
            }
        });
    }

    // Run immediately, but wrapped in try-catch for safety
    try {
        forceDisplay();
    } catch (e) {
        console.error('Error in emergency fix initial run:', e);
    }

    // Add event listeners safely
    try {
        document.addEventListener('DOMContentLoaded', forceDisplay);
        window.addEventListener('load', forceDisplay);
    } catch (e) {
        console.error('Error setting up emergency fix listeners:', e);
    }

    // Also run delayed to catch any late rendering issues
    setTimeout(forceDisplay, 500);
    setTimeout(forceDisplay, 1000);

    // Global helper function that can be called from anywhere
    window.emergencyForceDisplay = forceDisplay;

    // Add direct CSS rules for extra protection
    try {
        const emergencyStyle = document.createElement('style');
        emergencyStyle.textContent = `
            body section, body footer, #features, #games, #pricing, #docs,
            .features-section, .games-section, .pricing-section, .docs-section, .footer-section,
            .page-container, .force-visible {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                height: auto !important;
                position: relative !important;
            }
        `;
        document.head.appendChild(emergencyStyle);
    } catch (e) {
        console.error('Error adding emergency styles:', e);
    }
})();
