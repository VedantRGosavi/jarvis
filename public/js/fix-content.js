/**
 * SECONDARY EMERGENCY FIX - Force content visibility
 * This file uses the primary emergency fix function when available,
 * or provides a simplified version for redundancy.
 */

// Force display on document ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('SECONDARY FIX: Ensure all content is visible');

    // Try to use the primary emergency fix if available
    if (window.emergencyForceDisplay && typeof window.emergencyForceDisplay === 'function') {
        window.emergencyForceDisplay();
    } else {
        // Fallback if primary fix isn't available
        simpleForceDisplay();
    }
});

// Also force display on load
window.addEventListener('load', function() {
    // Delayed fixes to catch any late-rendering elements
    setTimeout(() => {
        if (window.emergencyForceDisplay && typeof window.emergencyForceDisplay === 'function') {
            window.emergencyForceDisplay();
        } else {
            simpleForceDisplay();
        }
    }, 500);
});

// Simple force display function as backup
function simpleForceDisplay() {
    // Add classes to body
    document.body.classList.add('app-initialized');
    document.body.classList.add('force-display');

    // Force display on all sections and footer
    document.querySelectorAll('section, footer, #features, #games, #pricing, #docs').forEach(el => {
        el.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
    });
}
