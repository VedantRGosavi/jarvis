/**
 * DIRECT EMERGENCY FIX
 * No PHP dependencies, just pure JavaScript
 */

// Execute immediately
(function() {
    console.log('DIRECT EMERGENCY FIX LOADED');

    // Force display function that runs immediately and after load
    function forceDisplay() {
        console.log('FORCING DISPLAY OF ALL CONTENT');

        // Add classes to body
        document.body.classList.add('app-initialized');
        document.body.classList.add('force-display');

        // Force all sections to display
        const sectionIds = ['features', 'games', 'pricing', 'docs'];
        sectionIds.forEach(function(id) {
            const section = document.getElementById(id);
            if (section) {
                section.style.display = 'block';
                section.style.visibility = 'visible';
                section.style.opacity = '1';
                console.log('Fixed section:', id);
            }
        });

        // Force all section elements
        const allSections = document.querySelectorAll('section');
        allSections.forEach(function(section) {
            section.style.display = 'block';
            section.style.visibility = 'visible';
            section.style.opacity = '1';
        });

        // Force footer display
        const footer = document.querySelector('footer');
        if (footer) {
            footer.style.display = 'block';
            footer.style.visibility = 'visible';
            footer.style.opacity = '1';
            console.log('Fixed footer');
        }
    }

    // Run immediately and add event listeners
    forceDisplay();
    document.addEventListener('DOMContentLoaded', forceDisplay);
    window.addEventListener('load', forceDisplay);

    // Also run delayed to catch any late rendering issues
    setTimeout(forceDisplay, 500);
    setTimeout(forceDisplay, 1000);
    setTimeout(forceDisplay, 2000);
})();
