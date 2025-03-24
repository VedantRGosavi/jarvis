/**
 * FridayAI Gaming Companion
 * Main application initialization
 */

import analyticsManager from './analytics.js';

export class FridayAIApp {
  constructor() {
    this.version = '1.0.0';
    this.gameModules = {};
    this.activeGame = null;

    this.initializeApp();
  }

  initializeApp() {
    console.log(`Initializing FridayAI Gaming Companion v${this.version}`);

    // Load theme preference
    this.loadThemePreference();

    // Initialize event listeners
    this.initEventListeners();

    // Register available game modules
    this.detectGameModules();

    // Handle navigation
    this.handleNavigation();

    // Initialize auth button
    this.initializeAuthButton();

    // Initialize analytics
    this.initializeAnalytics();
  }

  initializeAnalytics() {
    // Make sure analytics is initialized
    if (!analyticsManager.initialized) {
      analyticsManager.initialize();
    }

    // Track page view if not already tracked in main.js
    if (window.location.pathname !== analyticsManager.lastTrackedPath) {
      analyticsManager.trackPageView(window.location.pathname);
    }
  }

  loadThemePreference() {
    const darkMode = localStorage.getItem('fridayai_dark_mode') === 'true';
    if (darkMode) {
      document.documentElement.classList.add('dark');
    }
  }

  toggleDarkMode(isDark) {
    const isDarkMode = document.documentElement.classList.toggle('dark');
    localStorage.setItem('fridayai_dark_mode', isDarkMode);

    // Track theme preference
    analyticsManager.trackEvent('toggle_theme', {
      theme: isDarkMode ? 'dark' : 'light'
    });
  }

  initEventListeners() {
    // Dark mode toggle (if present on the page)
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
      darkModeToggle.addEventListener('click', () => this.toggleDarkMode(true));
    }

    // Download button
    const downloadBtn = document.querySelector('a[href="#download"]');
    if (downloadBtn) {
      downloadBtn.addEventListener('click', (e) => {
        if (e.currentTarget.getAttribute('href') === '#download') {
          e.preventDefault();
          const downloadSection = document.getElementById('download');
          if (downloadSection) {
            downloadSection.scrollIntoView({ behavior: 'smooth' });
          }
        }
      });
    }

    // Features section visibility tracking
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

    // Pricing section visibility tracking
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

    // Track subscribe button clicks
    const subscribeButtons = document.querySelectorAll('.subscribe-button');
    subscribeButtons.forEach(button => {
      button.addEventListener('click', () => {
        const tier = button.getAttribute('data-tier') || 'unknown';
        analyticsManager.trackFunnelStep('conversion', 'click_subscribe', { tier });
      });
    });

    // Track downloads
    const downloadButtons = document.querySelectorAll('.download-button');
    downloadButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        const platform = button.getAttribute('data-platform') || 'unknown';
        const version = button.getAttribute('data-version') || this.version;

        analyticsManager.trackConversion('download_initiated', {
          platform,
          version
        });

        analyticsManager.trackFunnelStep('conversion', 'download_app', {
          platform,
          version
        });

        // Track download completion after a delay
        // This is a simplification - in a real app you'd track after the download is confirmed
        setTimeout(() => {
          analyticsManager.trackConversion('download_complete', {
            platform,
            version
          });
        }, 5000); // Simulate 5-second download
      });
    });

    // Search form tracking
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
      searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const searchInput = searchForm.querySelector('input[type="text"]');
        if (searchInput && searchInput.value.trim() !== '') {
          const query = searchInput.value;
          this.performSearch(query);

          // Track search event
          analyticsManager.trackEvent('search', { query });
        }
      });
    }
  }

  detectGameModules() {
    // Check for Elden Ring module
    if (window.eldenRingModule) {
      this.registerGameModule(window.eldenRingModule);
    }

    // Check for Baldur's Gate 3 module
    if (window.baldursGateModule) {
      this.registerGameModule(window.baldursGateModule);
    }

    // Set default active game if available
    const gameIds = Object.keys(this.gameModules);
    if (gameIds.length > 0) {
      // Try to restore the last active game
      const lastActiveGame = localStorage.getItem('fridayai_active_game');
      if (lastActiveGame && this.gameModules[lastActiveGame]) {
        this.setActiveGame(lastActiveGame);
      } else {
        // Default to first available game
        this.setActiveGame(gameIds[0]);
      }
    }
  }

  registerGameModule(gameModule) {
    if (gameModule && gameModule.gameId) {
      this.gameModules[gameModule.gameId] = gameModule;
      console.log(`Registered game module: ${gameModule.gameName}`);
    }
  }

  setActiveGame(gameId) {
    if (this.gameModules[gameId]) {
      this.activeGame = gameId;
      localStorage.setItem('fridayai_active_game', gameId);
      console.log(`Active game set to: ${this.gameModules[gameId].gameName}`);

      // Update UI to reflect active game
      this.updateGameUI(gameId);

      // Track game selection
      analyticsManager.trackEvent('select_game', {
        game_id: gameId,
        game_name: this.gameModules[gameId].gameName
      });

      return true;
    }
    return false;
  }

  updateGameUI(gameId) {
    // Update game selector UI (if on overlay page)
    const gameSelectors = document.querySelectorAll('.game-selector');
    if (gameSelectors.length > 0) {
      gameSelectors.forEach(selector => {
        const gameItems = selector.querySelectorAll('[data-game-id]');
        gameItems.forEach(item => {
          if (item.dataset.gameId === gameId) {
            item.classList.add('active', 'border-l-2', 'border-gaming-gray-500', 'bg-gaming-gray-700');
            item.classList.remove('bg-gaming-gray-800');
          } else {
            item.classList.remove('active', 'border-l-2', 'border-gaming-gray-500', 'bg-gaming-gray-700');
            item.classList.add('bg-gaming-gray-800');
          }
        });
      });
    }
  }

  performSearch(query) {
    if (!query || !this.activeGame) return [];

    const gameModule = this.gameModules[this.activeGame];
    if (gameModule && typeof gameModule.searchContent === 'function') {
      const results = gameModule.searchContent(query);
      this.displaySearchResults(results, query);
      return results;
    }

    return [];
  }

  displaySearchResults(results, query) {
    const searchResultsContainer = document.getElementById('search-results');
    if (!searchResultsContainer) return;

    if (results.length === 0) {
      searchResultsContainer.innerHTML = `
        <div class="p-4 text-center text-gaming-gray-400">
          <p>No results found for "${query}"</p>
        </div>
      `;
      return;
    }

    searchResultsContainer.innerHTML = `
      <div class="p-2 border-b border-gaming-gray-700">
        <h3 class="font-medium">Results for "${query}"</h3>
        <p class="text-xs text-gaming-gray-400">${results.length} items found</p>
      </div>
      <div id="results-list"></div>
    `;

    const resultsList = document.getElementById('results-list');
    if (resultsList && window.uiComponents) {
      results.forEach(result => {
        const resultItem = window.uiComponents.createSearchResultItem({
          ...result,
          onClick: (item) => {
            this.handleResultClick(item);
          }
        });
        resultsList.appendChild(resultItem);
      });
    }
  }

  handleResultClick(result) {
    console.log('Result clicked:', result);
    // Implementation depends on result type and content

    // Track result click
    analyticsManager.trackEvent('search_result_click', {
      result_id: result.id,
      result_type: result.type,
      search_term: result.searchTerm
    });
  }

  handleNavigation() {
    // Smooth scroll for navigation links
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
      if (link.getAttribute('href').startsWith('#')) {
        link.addEventListener('click', (e) => {
          const targetId = link.getAttribute('href');
          if (targetId !== '#' && document.querySelector(targetId)) {
            e.preventDefault();
            document.querySelector(targetId).scrollIntoView({
              behavior: 'smooth'
            });
          }
        });
      }
    });
  }

  initializeAuthButton() {
    const authButton = document.getElementById('auth-button');
    if (!authButton) return;

    // Update button state based on auth status
    const updateAuthButton = () => {
      if (window.authService && window.authService.isAuthenticated()) {
        const user = window.authService.getCurrentUser();
        authButton.textContent = user?.name || 'Account';
        authButton.classList.add('logged-in');

        // If user is logged in, set user ID for analytics
        const userId = localStorage.getItem('fridayai_user_id');
        if (userId) {
          analyticsManager.setUserId(userId);
        }
      } else {
        authButton.textContent = 'Create an Account';
        authButton.classList.remove('logged-in');
      }
    };

    // Handle auth button click
    authButton.addEventListener('click', () => {
      if (window.authService && window.authService.isAuthenticated()) {
        // Show account page if logged in
        if (window.accountPage) {
          window.accountPage.show();
        }
      } else {
        // Show registration form if not logged in
        if (this.authForms) {
          const container = document.createElement('div');
          container.className = 'fixed inset-0 bg-gaming-gray-900 bg-opacity-90 flex items-center justify-center z-50';
          document.body.appendChild(container);
          this.authForms.renderRegisterForm(container);
        }
      }
    });

    // Listen for auth state changes
    document.addEventListener('userLogin', () => {
      updateAuthButton();
    });

    document.addEventListener('userLogout', () => {
      updateAuthButton();
      // Hide account page if it's open
      if (window.accountPage) {
        window.accountPage.hide();
      }
    });

    // Initial state
    updateAuthButton();
  }
}

// Initialize the app and expose to window
export const fridayAIApp = new FridayAIApp();

// SAFEGUARD: Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  // Ensure the app is available globally
  window.fridayAIApp = fridayAIApp;

  // Check if all UI elements are visible by adding a class to the body
  document.body.classList.add('app-initialized');

  // If sections aren't visible, try force re-initialization
  const forceReinitialize = () => {
    // Make sure core UI sections are visible
    const sections = ['features', 'games', 'pricing', 'docs'];

    sections.forEach(id => {
      const section = document.getElementById(id);
      if (section && window.getComputedStyle(section).display === 'none') {
        console.log(`Re-showing section: ${id}`);
        section.style.display = 'block';
      }
    });
  };

  // Try to force re-initialize after a short delay
  setTimeout(forceReinitialize, 500);
});
