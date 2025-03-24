/**
 * Game Detection Module
 * Detects running games and handles overlay positioning
 */

class GameDetection {
  constructor() {
    this.currentGame = null;
    this.isDetecting = false;
    this.detectionInterval = null;
    this.supportedGames = {
      ELDEN_RING: {
        id: 'elden_ring',
        processName: 'eldenring.exe',
        windowTitle: 'ELDEN RING™',
        defaultPosition: 'top-right',
        defaultSize: { width: 320, height: 480 }
      },
      BALDURS_GATE_3: {
        id: 'baldurs_gate3',
        processName: 'bg3.exe',
        windowTitle: "Baldur's Gate 3",
        defaultPosition: 'top-right',
        defaultSize: { width: 320, height: 480 }
      }
    };

    // Position presets
    this.positionPresets = {
      'top-right': { x: window.innerWidth - 330, y: 10 },
      'top-left': { x: 10, y: 10 },
      'bottom-right': { x: window.innerWidth - 330, y: window.innerHeight - 490 },
      'bottom-left': { x: 10, y: window.innerHeight - 490 },
      'center-right': { x: window.innerWidth - 330, y: (window.innerHeight - 480) / 2 },
      'center-left': { x: 10, y: (window.innerHeight - 480) / 2 }
    };

    // User preferences for game-specific positions
    this.userPositionPreferences = this.loadPositionPreferences();

    // Game detection preferences
    this.detectionSettings = this.loadDetectionSettings();

    // Initialize
    this.initialize();
  }

  /**
   * Initialize the game detection
   */
  initialize() {
    console.log('Initializing game detection');

    // Listen for window resize to update position presets
    window.addEventListener('resize', () => {
      this.updatePositionPresets();
    });

    // Automatic detection if enabled
    if (this.detectionSettings.autoDetectGames) {
      this.startDetection();
    } else {
      // Try to load the last game from storage
      this.loadLastGameFromStorage();
    }

    // Notify that game detection is ready
    document.dispatchEvent(new CustomEvent('gameDetectionReady'));
  }

  /**
   * Update position presets based on current window size
   */
  updatePositionPresets() {
    this.positionPresets = {
      'top-right': { x: window.innerWidth - 330, y: 10 },
      'top-left': { x: 10, y: 10 },
      'bottom-right': { x: window.innerWidth - 330, y: window.innerHeight - 490 },
      'bottom-left': { x: 10, y: window.innerHeight - 490 },
      'center-right': { x: window.innerWidth - 330, y: (window.innerHeight - 480) / 2 },
      'center-left': { x: 10, y: (window.innerHeight - 480) / 2 }
    };
  }

  /**
   * Start automatic game detection
   */
  startDetection() {
    if (this.isDetecting) return;

    this.isDetecting = true;

    // Perform initial detection
    this.detectRunningGame();

    // Set up periodic detection
    this.detectionInterval = setInterval(() => {
      this.detectRunningGame();
    }, this.detectionSettings.detectionIntervalMs);

    console.log(`Game detection started (interval: ${this.detectionSettings.detectionIntervalMs}ms)`);
  }

  /**
   * Stop automatic game detection
   */
  stopDetection() {
    if (!this.isDetecting) return;

    this.isDetecting = false;

    if (this.detectionInterval) {
      clearInterval(this.detectionInterval);
      this.detectionInterval = null;
    }

    console.log('Game detection stopped');
  }

  /**
   * Toggle automatic game detection
   * @returns {boolean} New detection state
   */
  toggleDetection() {
    if (this.isDetecting) {
      this.stopDetection();
    } else {
      this.startDetection();
    }

    // Update settings
    this.detectionSettings.autoDetectGames = this.isDetecting;
    this.saveDetectionSettings();

    return this.isDetecting;
  }

  /**
   * Load the last played game from storage
   */
  loadLastGameFromStorage() {
    const lastGame = localStorage.getItem('friday_current_game');

    if (lastGame) {
      const gameInfo = Object.values(this.supportedGames).find(game => game.id === lastGame);

      if (gameInfo) {
        this.currentGame = gameInfo;
        this.handleGameChange(gameInfo);
        return true;
      }
    }

    return false;
  }

  /**
   * Detect which game is currently running
   */
  detectRunningGame() {
    // In a browser environment, we can't directly detect running processes
    // In a real implementation, this would use the native API or a backend endpoint
    // For demo purposes, we'll use a mock implementation that looks at the URL

    const url = window.location.href;
    let detectedGame = null;

    // Mock detection based on URL parameters
    if (url.includes('game=eldenring') || url.includes('game=elden_ring')) {
      detectedGame = this.supportedGames.ELDEN_RING;
    } else if (url.includes('game=baldursgate3') || url.includes('game=baldurs_gate3')) {
      detectedGame = this.supportedGames.BALDURS_GATE_3;
    }

    // If no game detected in URL, try localStorage
    if (!detectedGame && !this.currentGame) {
      const lastGame = localStorage.getItem('friday_current_game');
      if (lastGame === 'elden_ring') {
        detectedGame = this.supportedGames.ELDEN_RING;
      } else if (lastGame === 'baldurs_gate3') {
        detectedGame = this.supportedGames.BALDURS_GATE_3;
      }
    }

    // If the detected game has changed
    if (detectedGame && (!this.currentGame || this.currentGame.id !== detectedGame.id)) {
      this.currentGame = detectedGame;
      this.handleGameChange(detectedGame);

      // Save to localStorage
      localStorage.setItem('friday_current_game', detectedGame.id);

      return detectedGame;
    }

    return this.currentGame;
  }

  /**
   * Handle game change
   * @param {Object} gameInfo - Information about detected game
   */
  handleGameChange(gameInfo) {
    console.log(`Game detected: ${gameInfo.id}`);

    // Update UI
    if (window.overlayController) {
      window.overlayController.setCurrentGame(gameInfo.id);
    }

    // Position overlay
    this.positionOverlayForGame(gameInfo);

    // Dispatch event
    const event = new CustomEvent('gameDetected', { detail: gameInfo });
    document.dispatchEvent(event);
  }

  /**
   * Manually set the current game
   * @param {string} gameId - Game identifier
   * @returns {boolean} Success
   */
  setCurrentGame(gameId) {
    const gameInfo = Object.values(this.supportedGames).find(game => game.id === gameId);

    if (gameInfo) {
      this.currentGame = gameInfo;
      this.handleGameChange(gameInfo);

      // Save to localStorage
      localStorage.setItem('friday_current_game', gameId);

      return true;
    }

    return false;
  }

  /**
   * Position overlay for the detected game
   * @param {Object} gameInfo - Information about detected game
   */
  positionOverlayForGame(gameInfo) {
    if (!window.gameOverlay) return;

    // Get preferred position for this game
    const positionPreference = this.userPositionPreferences[gameInfo.id] || gameInfo.defaultPosition;

    // Get coordinates for the position
    const position = this.getPositionCoordinates(positionPreference);

    // Update overlay position
    window.gameOverlay.updatePosition(position);
  }

  /**
   * Get coordinates for a named position
   * @param {string} positionName - Position name (e.g., 'top-right')
   * @returns {Object} Position coordinates {x, y}
   */
  getPositionCoordinates(positionName) {
    return this.positionPresets[positionName] || this.positionPresets['top-right'];
  }

  /**
   * Set user preference for game overlay position
   * @param {string} gameId - Game identifier
   * @param {string} positionName - Position name
   */
  setGamePositionPreference(gameId, positionName) {
    // Update preferences
    this.userPositionPreferences[gameId] = positionName;

    // Save to localStorage
    localStorage.setItem('friday_position_preferences', JSON.stringify(this.userPositionPreferences));

    // Apply immediately if this is the current game
    if (this.currentGame && this.currentGame.id === gameId) {
      const position = this.getPositionCoordinates(positionName);
      if (window.gameOverlay) {
        window.gameOverlay.updatePosition(position);
      }
    }
  }

  /**
   * Load position preferences from localStorage
   * @returns {Object} Position preferences by game
   */
  loadPositionPreferences() {
    try {
      const preferences = localStorage.getItem('friday_position_preferences');
      return preferences ? JSON.parse(preferences) : {};
    } catch (e) {
      console.error('Error loading position preferences:', e);
      return {};
    }
  }

  /**
   * Load detection settings from localStorage
   * @returns {Object} Detection settings
   */
  loadDetectionSettings() {
    try {
      const settings = localStorage.getItem('friday_detection_settings');
      const parsedSettings = settings ? JSON.parse(settings) : {};

      // Default settings
      return {
        autoDetectGames: parsedSettings.autoDetectGames !== undefined ? parsedSettings.autoDetectGames : true,
        detectionIntervalMs: parsedSettings.detectionIntervalMs || 10000
      };
    } catch (e) {
      console.error('Error loading detection settings:', e);
      return {
        autoDetectGames: true,
        detectionIntervalMs: 10000
      };
    }
  }

  /**
   * Save detection settings to localStorage
   */
  saveDetectionSettings() {
    try {
      localStorage.setItem('friday_detection_settings', JSON.stringify(this.detectionSettings));
    } catch (e) {
      console.error('Error saving detection settings:', e);
    }
  }

  /**
   * Update detection settings
   * @param {Object} settings - New settings
   */
  updateDetectionSettings(settings) {
    // Update settings
    if (settings.autoDetectGames !== undefined) {
      this.detectionSettings.autoDetectGames = settings.autoDetectGames;
    }

    if (settings.detectionIntervalMs !== undefined) {
      this.detectionSettings.detectionIntervalMs = settings.detectionIntervalMs;
    }

    // Apply changes
    if (this.detectionSettings.autoDetectGames) {
      // Restart detection with new interval
      this.stopDetection();
      this.startDetection();
    } else {
      this.stopDetection();
    }

    // Save settings
    this.saveDetectionSettings();
  }

  /**
   * Get all available position presets
   * @returns {Object} Position presets
   */
  getPositionPresets() {
    return Object.keys(this.positionPresets);
  }

  /**
   * Get recommended overlay position based on game and screen resolution
   * @param {string} gameId - Game identifier
   * @returns {string} Recommended position
   */
  getRecommendedPosition(gameId) {
    // Logic to determine the best position based on game UI and resolution
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;

    // For widescreen monitors, prefer right side positions
    if (screenWidth / screenHeight > 1.8) {
      return 'center-right';
    }

    // Default recommendations by game
    if (gameId === 'elden_ring') {
      return 'top-right'; // Elden Ring has less UI in top-right
    } else if (gameId === 'baldurs_gate3') {
      return 'bottom-left'; // BG3 has party portraits bottom-left, quest log top-right
    }

    // Default fallback
    return 'top-right';
  }
}

// Initialize game detection
document.addEventListener('DOMContentLoaded', () => {
  const gameDetection = new GameDetection();

  // Expose to global scope
  window.gameDetection = gameDetection;
});
