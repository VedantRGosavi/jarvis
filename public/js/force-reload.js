/**
 * Force Reload Utility
 * This file is used to force browsers to reload all JavaScript files
 * by changing the cache busting parameter in the URL
 */

// Generate a timestamp to use as a cache buster
const forceCacheBuster = Date.now();

// Force reload all sections
document.addEventListener('DOMContentLoaded', function() {
    console.log("Force reload utility activated");

    // Apply to the body immediately
    document.body.classList.add('force-display');

    // Force display all major sections - IMMEDIATE ACTION
    const criticalSections = ['features', 'games', 'pricing', 'docs'];
    criticalSections.forEach(function(id) {
        const section = document.getElementById(id);
        if (section) {
            // Force display with !important inline styles
            section.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important');
            console.log(`Force displayed: ${id}`);
        }
    });

    // Force display ALL sections
    const allSections = document.querySelectorAll('section');
    allSections.forEach(function(section) {
        section.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important');
    });

    // Force display the footer
    const footer = document.querySelector('footer');
    if (footer) {
        footer.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important');
        console.log("Force displayed: footer");
    }

    // Apply class to body for CSS selector approach as well
    document.body.classList.add('app-initialized');

    console.log("All sections have been force displayed");
});

// Export the timestamp so other modules can use it
export const FORCE_RELOAD_TIMESTAMP = forceCacheBuster;
