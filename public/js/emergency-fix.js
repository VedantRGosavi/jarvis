/**
 * DIRECT EMERGENCY FIX
 * No PHP dependencies, just pure JavaScript
 * This script is loaded FIRST before anything else to guarantee content visibility
 */

// Execute immediately
(function() {
    console.log('🚨 DIRECT EMERGENCY FIX LOADED - PRIORITY 1');

    // Force display function that runs immediately and after load
    function forceDisplay() {
        console.log('🚨 FORCING DISPLAY OF ALL CONTENT');

        // Add classes to body
        document.body.classList.add('app-initialized');
        document.body.classList.add('force-display');

        // Force all sections to display with maximum priority
        const sectionIds = ['features', 'games', 'pricing', 'docs'];
        sectionIds.forEach(function(id) {
            const section = document.getElementById(id);
            if (section) {
                section.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; position: relative !important;';
                console.log('🚨 Fixed section:', id);
            }
        });

        // Force all section elements with !important
        const allSections = document.querySelectorAll('section');
        allSections.forEach(function(section) {
            section.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; position: relative !important;';
            section.classList.add('force-visible');
        });

        // Force footer display with !important
        const footer = document.querySelector('footer');
        if (footer) {
            footer.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important; position: relative !important;';
            footer.classList.add('force-visible');
            console.log('🚨 Fixed footer');
        }

        // Force the page container to be visible
        const container = document.querySelector('.page-container');
        if (container) {
            container.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
        }

        // Create a MutationObserver to catch any dynamic hiding
        if (window.MutationObserver && !window._fridayaiObserver) {
            const observer = new MutationObserver(function(mutations) {
                let needsForceDisplay = false;

                mutations.forEach(function(mutation) {
                    if (mutation.target.nodeName === 'SECTION' ||
                        mutation.target.id === 'features' ||
                        mutation.target.id === 'games' ||
                        mutation.target.id === 'pricing' ||
                        mutation.target.id === 'docs' ||
                        mutation.target.nodeName === 'FOOTER') {
                        needsForceDisplay = true;
                    }
                });

                if (needsForceDisplay) {
                    forceDisplay();
                }
            });

            // Observe the whole document for style/attribute changes
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: true,
                subtree: true
            });

            window._fridayaiObserver = observer;
            console.log('🚨 Content visibility observer active');
        }
    }

    // Run immediately and add event listeners
    forceDisplay();
    document.addEventListener('DOMContentLoaded', forceDisplay);
    window.addEventListener('load', forceDisplay);

    // Also run delayed to catch any late rendering issues
    setTimeout(forceDisplay, 100);
    setTimeout(forceDisplay, 500);
    setTimeout(forceDisplay, 1000);
    setTimeout(forceDisplay, 2000);
    setTimeout(forceDisplay, 5000);

    // Global debug helper
    window.fridayAIForceDisplay = forceDisplay;

    // Extra safeguard - add direct CSS rule
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
