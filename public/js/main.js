// Import modules
import { PricingSection } from './ui/pricing.js';
import { PRICING_TIERS } from './data/pricing.js';
import analyticsManager from './analytics.js';
import { DownloadManager } from './ui/downloadUI.js';
import fridayAIApp from './app.js';

// Document ready initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('Main.js initialization started');

    // Initialize analytics if not already initialized
    if (!analyticsManager.initialized) {
        analyticsManager.initialize();

        // Track page view
        analyticsManager.trackPageView(window.location.pathname);

        // Track initial funnel step
        if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
            analyticsManager.trackFunnelStep('acquisition', 'visit_home');
        }
    }

    // Initialize download manager if on download page
    if (window.location.pathname.includes('download.html')) {
        window.downloadManager = new DownloadManager();
    }

    // Initialize tabs
    initializeTabs();

    // Initialize FAQ accordions
    initializeFaqAccordions();

    // Initialize pricing section
    initializePricingSection();

    // Ensure content visibility as a final check
    if (window.emergencyForceDisplay) {
        window.emergencyForceDisplay();
    } else if (window.forceDisplayContent) {
        window.forceDisplayContent();
    } else if (fridayAIApp && fridayAIApp.forceDisplayAllContent) {
        fridayAIApp.forceDisplayAllContent();
    } else {
        // Final fallback
        ensureContentVisibility();
    }
});

// Documentation Tab initialization
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    // Set initial state - show only getting started
    tabContents.forEach(content => {
        if (content.id === 'getting-started') {
            content.classList.remove('hidden');
            requestAnimationFrame(() => {
                content.classList.add('active');
            });
        } else {
            content.classList.add('hidden');
            content.classList.remove('active');
        }
    });

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = button.getAttribute('data-tab');

            // Remove active class from all buttons
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('text-gaming-gray-400');
                btn.classList.remove('text-gaming-light');
            });

            // Add active class to clicked button
            button.classList.add('active');
            button.classList.remove('text-gaming-gray-400');
            button.classList.add('text-gaming-light');

            // Handle content switching with proper transitions
            tabContents.forEach(content => {
                if (content.id === tabId) {
                    // Show new content
                    content.classList.remove('hidden');
                    requestAnimationFrame(() => {
                        content.classList.add('active');
                    });
                } else {
                    // Hide other content
                    content.classList.remove('active');
                    const transitionDuration = 400; // Match CSS transition duration
                    setTimeout(() => {
                        if (!content.classList.contains('active')) {
                            content.classList.add('hidden');
                        }
                    }, transitionDuration);
                }
            });
        });
    });
}

// FAQ Accordion initialization
function initializeFaqAccordions() {
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const button = item.querySelector('button');
        const answer = item.querySelector('.faq-answer');
        const arrow = item.querySelector('svg');

        button.addEventListener('click', () => {
            const isOpen = answer.classList.contains('active');

            // Close all FAQ items
            faqItems.forEach(otherItem => {
                const otherAnswer = otherItem.querySelector('.faq-answer');
                const otherArrow = otherItem.querySelector('svg');
                otherAnswer.classList.remove('active');
                otherArrow.style.transform = 'rotate(0deg)';
            });

            // Toggle clicked item
            if (!isOpen) {
                answer.classList.add('active');
                arrow.style.transform = 'rotate(180deg)';
            }
        });
    });
}

// Pricing section initialization
function initializePricingSection() {
    if (document.getElementById('pricing-section')) {
        // Initialize pricing section with the pricing tiers data
        new PricingSection('pricing-section', {
            title: "Choose Your Gaming Pack",
            subtitle: "Enhance your gaming experience with our specialized tools",
            tiers: PRICING_TIERS
        });
    }
}

// Fallback content visibility function
function ensureContentVisibility() {
    // Force display all major sections
    ['features', 'games', 'pricing', 'docs'].forEach(function(id) {
        var section = document.getElementById(id);
        if (section) {
            section.style.display = 'block';
            section.style.visibility = 'visible';
            section.style.opacity = '1';
        }
    });

    // Check all sections for visibility
    var sections = document.querySelectorAll('section');
    sections.forEach(function(section) {
        if (window.getComputedStyle(section).display === 'none') {
            section.style.display = 'block';
            section.style.visibility = 'visible';
            section.style.opacity = '1';
        }
    });

    // Make sure footer is visible
    var footer = document.querySelector('footer');
    if (footer) {
        footer.style.display = 'block';
        footer.style.visibility = 'visible';
        footer.style.opacity = '1';
    }

    console.log('Content visibility ensured by main.js');
}
