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

    // First, ensure content is visible immediately (no waiting for JS)
    if (fridayAIApp) {
        fridayAIApp.forceDisplayAllContent();
    } else {
        // If app isn't available yet, use the fallback
        ensureContentVisibility();
    }

    // Initialize mobile menu toggle
    initializeMobileMenu();

    // Then proceed with normal initialization
    initializeTabs();
    initializeFaqAccordions();
    initializePricingSection();

    // Enable animations only after ensuring content is visible
    setTimeout(() => {
        document.documentElement.classList.add('js-animation-ready');
        activateFadeInElements();
    }, 100);
});

// Mobile menu toggle
function initializeMobileMenu() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileAuthButton = document.getElementById('mobile-auth-button');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });

        // Close menu when clicking a link
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenu.classList.add('hidden');
            });
        });

        // Close menu when auth button is clicked
        if (mobileAuthButton) {
            mobileAuthButton.addEventListener('click', function() {
                mobileMenu.classList.add('hidden');
            });
        }
    }
}

// Documentation Tab initialization
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    if (!tabButtons.length || !tabContents.length) {
        console.log('Tabs not found, skipping tab initialization');
        return;
    }

    console.log('Initializing tab functionality with', tabButtons.length, 'buttons and', tabContents.length, 'content areas');

    // Set initial state - show only getting started
    tabContents.forEach(content => {
        if (content.id === 'getting-started') {
            content.classList.remove('hidden');
            content.classList.add('active');
        } else {
            content.classList.add('hidden');
            content.classList.remove('active');
        }
    });

    // Ensure the first tab button is active
    if (tabButtons.length > 0) {
        tabButtons[0].classList.add('active');
        tabButtons[0].classList.add('text-gaming-light');
        tabButtons[0].classList.remove('text-gaming-gray-400');
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = button.getAttribute('data-tab');
            console.log('Tab clicked:', tabId);

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
                    setTimeout(() => {
                        content.classList.add('active');
                    }, 10);
                } else {
                    // Hide other content
                    content.classList.remove('active');
                    setTimeout(() => {
                        if (!content.classList.contains('active')) {
                            content.classList.add('hidden');
                        }
                    }, 300); // Shorter transition for better UX
                }
            });
        });
    });
}

// FAQ Accordion initialization
function initializeFaqAccordions() {
    const faqItems = document.querySelectorAll('.faq-item');

    if (!faqItems.length) {
        console.log('No FAQ items found, skipping accordion initialization');
        return;
    }

    console.log('Initializing FAQ accordions for', faqItems.length, 'items');

    faqItems.forEach(item => {
        const button = item.querySelector('button');
        const answer = item.querySelector('.faq-answer');
        const arrow = item.querySelector('svg');

        if (!button || !answer || !arrow) {
            console.warn('FAQ item missing required elements');
            return;
        }

        // Initially hide all answers except the first one
        if (item !== faqItems[0]) {
            answer.style.maxHeight = '0';
            answer.style.overflow = 'hidden';
            answer.style.opacity = '0';
            answer.style.transition = 'max-height 0.3s ease, opacity 0.3s ease';
        } else {
            // First item starts open
            answer.style.maxHeight = answer.scrollHeight + 'px';
            answer.style.overflow = 'visible';
            answer.style.opacity = '1';
            answer.style.transition = 'max-height 0.3s ease, opacity 0.3s ease';
            answer.classList.add('active');
            arrow.style.transform = 'rotate(180deg)';
        }

        button.addEventListener('click', () => {
            const isOpen = answer.classList.contains('active');
            console.log('FAQ item clicked, current state:', isOpen ? 'open' : 'closed');

            // Close all FAQ items
            faqItems.forEach(otherItem => {
                const otherAnswer = otherItem.querySelector('.faq-answer');
                const otherArrow = otherItem.querySelector('svg');

                if (otherAnswer && otherItem !== item) {
                    otherAnswer.classList.remove('active');
                    otherAnswer.style.maxHeight = '0';
                    otherAnswer.style.overflow = 'hidden';
                    otherAnswer.style.opacity = '0';
                }

                if (otherArrow && otherItem !== item) {
                    otherArrow.style.transform = 'rotate(0deg)';
                }
            });

            // Toggle clicked item
            if (isOpen) {
                // Close the current item
                answer.classList.remove('active');
                answer.style.maxHeight = '0';
                answer.style.overflow = 'hidden';
                answer.style.opacity = '0';
                arrow.style.transform = 'rotate(0deg)';
            } else {
                // Open the current item
                answer.classList.add('active');
                answer.style.maxHeight = answer.scrollHeight + 'px';
                answer.style.overflow = 'visible';
                answer.style.opacity = '1';
                arrow.style.transform = 'rotate(180deg)';
            }
        });
    });
}

// Pricing section initialization
function initializePricingSection() {
    try {
        const pricingSection = document.getElementById('pricing-section');
        if (pricingSection) {
            console.log('Initializing pricing section from main.js');

            // Verify that PRICING_TIERS is available
            if (!PRICING_TIERS || !Array.isArray(PRICING_TIERS) || PRICING_TIERS.length === 0) {
                console.error('PRICING_TIERS is not properly defined:', PRICING_TIERS);
                return;
            }

            // Check if PricingSection class is available
            if (typeof PricingSection !== 'function') {
                console.error('PricingSection class is not properly imported');
                return;
            }

            // Initialize pricing section with the pricing tiers data
            const pricingComponent = new PricingSection('pricing-section', {
                title: "Choose Your Gaming Pack",
                subtitle: "Enhance your gaming experience with our specialized tools",
                tiers: PRICING_TIERS
            });

            console.log('Pricing section initialized successfully, tiers:', PRICING_TIERS.length);
        } else {
            console.warn('Pricing section element not found in the document');
        }
    } catch (error) {
        console.error('Error initializing pricing section:', error);
    }
}

// Function to activate fade-in elements as they become visible
function activateFadeInElements() {
    const fadeElements = document.querySelectorAll('.fade-in-element');

    if (!fadeElements.length) return;

    // Create an intersection observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Stop observing once it's visible
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null, // viewport
        threshold: 0.1, // trigger when 10% is visible
        rootMargin: '0px 0px -50px 0px' // slightly before coming into view
    });

    // Observe each element
    fadeElements.forEach(element => {
        observer.observe(element);
    });

    // Fallback for browsers that don't support IntersectionObserver
    if (!('IntersectionObserver' in window)) {
        fadeElements.forEach(element => {
            element.classList.add('visible');
        });
    }
}

// Fallback content visibility function (simplified and reliable)
function ensureContentVisibility() {
    // Force display all sections
    document.querySelectorAll('section, footer, .page-container').forEach(function(element) {
        if (element) {
            element.style.display = 'block';
            element.style.visibility = 'visible';
            element.style.opacity = '1';

            // If it's a fade-in element, make it visible
            if (element.classList.contains('fade-in-element')) {
                element.classList.add('visible');
            }
        }
    });

    // Apply visible class to fade-in elements
    document.querySelectorAll('.fade-in-element').forEach(function(element) {
        element.classList.add('visible');
    });

    console.log('Content visibility ensured by main.js');
}
