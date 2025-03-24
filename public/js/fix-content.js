/**
 * EMERGENCY FIX - Force content visibility
 * This is a direct script that will force all page content to be visible
 * regardless of any other JavaScript.
 */

// Force display on document ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('EMERGENCY FIX: Force displaying all content');
    forceDisplayContent();
});

// Force display again after window load
window.addEventListener('load', function() {
    console.log('EMERGENCY FIX: Force displaying all content after load');
    setTimeout(forceDisplayContent, 100);
    setTimeout(forceDisplayContent, 500);
    setTimeout(forceDisplayContent, 1000);
});

function forceDisplayContent() {
    // Set body classes
    document.body.classList.add('app-initialized');
    document.body.classList.add('force-display');

    // Get all sections by ID
    ['features', 'games', 'pricing', 'docs'].forEach(function(id) {
        const element = document.getElementById(id);
        if (element) {
            applyDisplayStyles(element);
        }
    });

    // Get all section elements
    const sections = document.querySelectorAll('section');
    sections.forEach(function(section) {
        applyDisplayStyles(section);
    });

    // Force display footer
    const footer = document.querySelector('footer');
    if (footer) {
        applyDisplayStyles(footer);
    }

    // Force container display
    const container = document.querySelector('.page-container');
    if (container) {
        applyDisplayStyles(container);
    }

    console.log('All content should now be visible');
}

function applyDisplayStyles(element) {
    // Apply direct style attributes with !important
    element.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;');

    // Add backup class
    element.classList.add('force-visible');
}
