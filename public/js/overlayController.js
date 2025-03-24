/**
 * Game Companion Overlay Controller
 * Main controller that initializes and coordinates all overlay components
 */

class OverlayController {
  constructor() {
    this.currentGame = localStorage.getItem('friday_current_game') || 'elden_ring';
    this.isInitialized = false;
    
    // Initialize components when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
      this.initialize();
    });
  }
  
  /**
   * Initialize the overlay controller and all components
   */
  initialize() {
    if (this.isInitialized) return;
    
    // Wait for other components to be available
    if (!window.gameOverlay || !window.gameDataService || !window.viewManager) {
      console.log('Waiting for components to load...');
      setTimeout(() => this.initialize(), 100);
      return;
    }
    
    console.log('Initializing Overlay Controller');
    
    // Initialize UI elements
    this.initializeUI();
    
    // Initialize event listeners
    this.initializeEventListeners();
    
    // Set current game
    this.setCurrentGame(this.currentGame);
    
    // Mark as initialized
    this.isInitialized = true;
    
    // Dispatch initialization event
    const event = new CustomEvent('overlayControllerReady');
    document.dispatchEvent(event);
  }
  
  /**
   * Initialize UI elements
   */
  initializeUI() {
    // Initialize the game selector
    const gameSelector = document.querySelector('.games-selector');
    if (gameSelector) {
      // Clear existing content
      gameSelector.innerHTML = '';
      
      // Add game options
      gameSelector.innerHTML = `
        <div class="space-y-1">
          <div class="game-selector flex items-center bg-gaming-gray-700 rounded p-1.5 cursor-pointer hover:bg-gaming-gray-600 transition" data-game="elden_ring">
            <span class="text-sm">Elden Ring</span>
          </div>
          <div class="game-selector flex items-center bg-gaming-gray-800 rounded p-1.5 cursor-pointer hover:bg-gaming-gray-700 transition" data-game="baldurs_gate3">
            <span class="text-sm">Baldur's Gate 3</span>
          </div>
        </div>
      `;
    }
    
    // Ensure search input has an ID for the view manager
    const searchInput = document.querySelector('input[type="text"][placeholder="Search..."]');
    if (searchInput && !searchInput.id) {
      searchInput.id = 'search-input';
    }
  }
  
  /**
   * Initialize event listeners
   */
  initializeEventListeners() {
    // Game selector clicks
    const gameSelectors = document.querySelectorAll('.game-selector');
    gameSelectors.forEach(selector => {
      selector.addEventListener('click', () => {
        const gameId = selector.dataset.game;
        this.setCurrentGame(gameId);
      });
    });
    
    // Menu navigation buttons
    document.querySelectorAll('.nav-button').forEach(button => {
      button.addEventListener('click', () => {
        const contentType = button.dataset.contentType;
        if (contentType && window.viewManager) {
          window.viewManager.showContentListView(contentType);
        }
      });
    });
    
    // Initialize hotkeys if available
    if (window.gameHotkeys) {
      // Register platform-specific hotkey
      const isMac = navigator.platform.includes('Mac');
      if (isMac) {
        window.gameHotkeys.registerHotkey('Meta+Shift+J', () => {
          if (window.gameOverlay) {
            window.gameOverlay.toggleVisibility();
          }
        });
      } else {
        window.gameHotkeys.registerHotkey('Ctrl+Shift+J', () => {
          if (window.gameOverlay) {
            window.gameOverlay.toggleVisibility();
          }
        });
      }
      
      window.gameHotkeys.registerHotkey('Escape', () => {
        if (window.viewManager && window.viewManager.currentView !== 'home') {
          window.viewManager.showHomeView();
        }
      });
    }
  }
  
  /**
   * Set the current game
   * @param {string} gameId - Game identifier
   */
  setCurrentGame(gameId) {
    this.currentGame = gameId;
    
    // Update game selectors
    const gameSelectors = document.querySelectorAll('.game-selector');
    gameSelectors.forEach(selector => {
      if (selector.dataset.game === gameId) {
        selector.classList.add('border-l-2', 'border-gaming-gray-500', 'bg-gaming-gray-700');
        selector.classList.remove('bg-gaming-gray-800');
      } else {
        selector.classList.remove('border-l-2', 'border-gaming-gray-500', 'bg-gaming-gray-700');
        selector.classList.add('bg-gaming-gray-800');
      }
    });
    
    // Update view manager
    if (window.viewManager) {
      window.viewManager.setGame(gameId);
    }
    
    // Update theme classes on body
    document.body.classList.remove('elden-ring-theme', 'baldurs-gate-theme');
    if (gameId === 'elden_ring') {
      document.body.classList.add('elden-ring-theme');
    } else if (gameId === 'baldurs_gate3') {
      document.body.classList.add('baldurs-gate-theme');
    }
    
    // Save current game to localStorage
    localStorage.setItem('friday_current_game', gameId);
  }
}

// Initialize the overlay controller
const overlayController = new OverlayController(); 