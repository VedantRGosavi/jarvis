/**
 * Settings Panel
 * Manages user settings for the overlay
 */

class SettingsPanel {
  constructor() {
    // Create settings panel element
    this.panel = document.createElement('div');
    this.panel.id = 'settings-panel';
    this.panel.className = 'settings-panel fixed right-0 top-0 h-full w-64 bg-gaming-gray-800 border-l border-gaming-gray-700 p-4 shadow-lg transform translate-x-full transition-transform duration-300 z-40';

    this.isVisible = false;

    // Create settings content
    this.renderPanel();

    // Add to document
    document.body.appendChild(this.panel);

    // Initialize event listeners
    this.initEventListeners();
  }

  /**
   * Render the settings panel
   */
  renderPanel() {
    // Settings header
    const header = document.createElement('div');
    header.className = 'flex justify-between items-center mb-4';
    header.innerHTML = `
      <h2 class="text-lg font-bold">Settings</h2>
      <button id="close-settings" class="text-gaming-gray-400 hover:text-gaming-gray-200">×</button>
    `;

    // Settings sections
    const content = document.createElement('div');
    content.className = 'settings-content space-y-6 overflow-y-auto';

    // Game detection settings
    const gameDetectionSettings = this.createGameDetectionSettings();

    // Display settings
    const displaySettings = this.createDisplaySettings();

    // Position settings
    const positionSettings = this.createPositionSettings();

    // Add all sections
    content.appendChild(gameDetectionSettings);
    content.appendChild(displaySettings);
    content.appendChild(positionSettings);

    // Add to panel
    this.panel.appendChild(header);
    this.panel.appendChild(content);
  }

  /**
   * Create game detection settings section
   * @returns {HTMLElement} Settings section
   */
  createGameDetectionSettings() {
    const section = document.createElement('div');
    section.className = 'settings-section';

    // Get current settings if available
    const detectionEnabled = window.gameDetection && window.gameDetection.detectionSettings.autoDetectGames;
    const detectionInterval = window.gameDetection ? window.gameDetection.detectionSettings.detectionIntervalMs / 1000 : 10;

    section.innerHTML = `
      <h3 class="text-sm font-semibold mb-2 text-gaming-gray-300">Game Detection</h3>
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <label for="auto-detect" class="text-sm">Auto-detect games</label>
          <label class="switch">
            <input type="checkbox" id="auto-detect" ${detectionEnabled ? 'checked' : ''}>
            <span class="slider round"></span>
          </label>
        </div>

        <div class="interval-setting">
          <label for="detection-interval" class="text-sm mb-1 block">Check interval (seconds)</label>
          <div class="flex items-center space-x-2">
            <input type="range" id="detection-interval" min="5" max="60" step="5" value="${detectionInterval}"
              class="w-full h-2 bg-gaming-gray-700 rounded-lg appearance-none cursor-pointer">
            <span id="interval-value" class="text-sm">${detectionInterval}s</span>
          </div>
        </div>

        <div class="detection-status text-xs bg-gaming-gray-700 p-2 rounded">
          <p>Status: <span id="detection-status-text">${detectionEnabled ? 'Active' : 'Inactive'}</span></p>
          <p>Current game: <span id="current-game-text">None</span></p>
        </div>
      </div>
    `;

    return section;
  }

  /**
   * Create display settings section
   * @returns {HTMLElement} Settings section
   */
  createDisplaySettings() {
    const section = document.createElement('div');
    section.className = 'settings-section';

    // Get current settings if available
    const opacity = window.gameOverlay ? window.gameOverlay.opacity * 100 : 85;

    section.innerHTML = `
      <h3 class="text-sm font-semibold mb-2 text-gaming-gray-300">Display</h3>
      <div class="space-y-3">
        <div>
          <label for="overlay-opacity" class="text-sm mb-1 block">Overlay opacity</label>
          <div class="flex items-center space-x-2">
            <input type="range" id="overlay-opacity" min="25" max="100" step="5" value="${opacity}"
              class="w-full h-2 bg-gaming-gray-700 rounded-lg appearance-none cursor-pointer">
            <span id="opacity-value" class="text-sm">${opacity}%</span>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <label for="show-notifications" class="text-sm">Show notifications</label>
          <label class="switch">
            <input type="checkbox" id="show-notifications" checked>
            <span class="slider round"></span>
          </label>
        </div>
      </div>
    `;

    return section;
  }

  /**
   * Create position settings section
   * @returns {HTMLElement} Settings section
   */
  createPositionSettings() {
    const section = document.createElement('div');
    section.className = 'settings-section';

    // Get position presets
    const positionPresets = window.gameDetection ? window.gameDetection.getPositionPresets() : ['top-right', 'top-left', 'bottom-right', 'bottom-left'];

    // Get current game
    const currentGame = window.gameDetection && window.gameDetection.currentGame ? window.gameDetection.currentGame.id : null;

    // Get current position preference
    let currentPosition = 'top-right';
    if (currentGame && window.gameDetection && window.gameDetection.userPositionPreferences[currentGame]) {
      currentPosition = window.gameDetection.userPositionPreferences[currentGame];
    }

    // Create position options
    const positionOptions = positionPresets.map(position => {
      return `<option value="${position}" ${position === currentPosition ? 'selected' : ''}>${this.formatPositionName(position)}</option>`;
    }).join('');

    section.innerHTML = `
      <h3 class="text-sm font-semibold mb-2 text-gaming-gray-300">Position</h3>
      <div class="space-y-3">
        <div>
          <label for="overlay-position" class="text-sm mb-1 block">Default position</label>
          <select id="overlay-position" class="w-full px-2 py-1 bg-gaming-gray-700 border border-gaming-gray-600 rounded text-sm text-gaming-light">
            ${positionOptions}
          </select>
        </div>

        <div class="text-xs text-gaming-gray-400">
          <p>You can also drag the overlay to position it anywhere on screen.</p>
        </div>
      </div>
    `;

    return section;
  }

  /**
   * Initialize event listeners
   */
  initEventListeners() {
    // Close button
    const closeBtn = this.panel.querySelector('#close-settings');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        this.togglePanel();
      });
    }

    // Auto-detect toggle
    const autoDetectToggle = this.panel.querySelector('#auto-detect');
    if (autoDetectToggle && window.gameDetection) {
      autoDetectToggle.addEventListener('change', (e) => {
        const enabled = e.target.checked;
        window.gameDetection.updateDetectionSettings({ autoDetectGames: enabled });
        this.updateDetectionStatus();
      });
    }

    // Detection interval slider
    const intervalSlider = this.panel.querySelector('#detection-interval');
    const intervalValue = this.panel.querySelector('#interval-value');
    if (intervalSlider && intervalValue && window.gameDetection) {
      intervalSlider.addEventListener('input', (e) => {
        const seconds = parseInt(e.target.value, 10);
        intervalValue.textContent = `${seconds}s`;
      });

      intervalSlider.addEventListener('change', (e) => {
        const seconds = parseInt(e.target.value, 10);
        window.gameDetection.updateDetectionSettings({ detectionIntervalMs: seconds * 1000 });
      });
    }

    // Opacity slider
    const opacitySlider = this.panel.querySelector('#overlay-opacity');
    const opacityValue = this.panel.querySelector('#opacity-value');
    if (opacitySlider && opacityValue && window.gameOverlay) {
      opacitySlider.addEventListener('input', (e) => {
        const percent = parseInt(e.target.value, 10);
        opacityValue.textContent = `${percent}%`;
        window.gameOverlay.setOpacity(percent / 100);
      });
    }

    // Position selector
    const positionSelector = this.panel.querySelector('#overlay-position');
    if (positionSelector && window.gameDetection) {
      positionSelector.addEventListener('change', (e) => {
        const position = e.target.value;
        const currentGame = window.gameDetection.currentGame;

        if (currentGame) {
          window.gameDetection.setGamePositionPreference(currentGame.id, position);
        }
      });
    }

    // Update game detection info when game changes
    document.addEventListener('gameDetected', () => {
      this.updateGameInfo();
      this.updatePositionSelector();
    });
  }

  /**
   * Format position name for display
   * @param {string} position - Position identifier
   * @returns {string} Formatted position name
   */
  formatPositionName(position) {
    return position
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  }

  /**
   * Update detection status display
   */
  updateDetectionStatus() {
    if (!window.gameDetection) return;

    const statusText = this.panel.querySelector('#detection-status-text');
    if (statusText) {
      statusText.textContent = window.gameDetection.isDetecting ? 'Active' : 'Inactive';
    }
  }

  /**
   * Update game info display
   */
  updateGameInfo() {
    if (!window.gameDetection) return;

    const gameText = this.panel.querySelector('#current-game-text');
    if (gameText) {
      const currentGame = window.gameDetection.currentGame;

      if (currentGame) {
        const gameName = currentGame.id === 'elden_ring' ? 'Elden Ring' :
                         currentGame.id === 'baldurs_gate3' ? 'Baldur\'s Gate 3' :
                         'Unknown';

        gameText.textContent = gameName;
      } else {
        gameText.textContent = 'None';
      }
    }
  }

  /**
   * Update position selector based on current game
   */
  updatePositionSelector() {
    if (!window.gameDetection) return;

    const positionSelector = this.panel.querySelector('#overlay-position');
    const currentGame = window.gameDetection.currentGame;

    if (positionSelector && currentGame) {
      const currentPosition = window.gameDetection.userPositionPreferences[currentGame.id] || 'top-right';
      positionSelector.value = currentPosition;
    }
  }

  /**
   * Show the settings panel
   */
  showPanel() {
    this.panel.classList.remove('translate-x-full');
    this.isVisible = true;
    this.updateDetectionStatus();
    this.updateGameInfo();
  }

  /**
   * Hide the settings panel
   */
  hidePanel() {
    this.panel.classList.add('translate-x-full');
    this.isVisible = false;
  }

  /**
   * Toggle panel visibility
   */
  togglePanel() {
    if (this.isVisible) {
      this.hidePanel();
    } else {
      this.showPanel();
    }
  }
}

// Initialize settings panel
document.addEventListener('DOMContentLoaded', () => {
  const settingsPanel = new SettingsPanel();

  // Expose to global scope
  window.settingsPanel = settingsPanel;

  // Add button event listener
  const settingsBtn = document.getElementById('settings-btn');
  if (settingsBtn) {
    settingsBtn.addEventListener('click', () => {
      settingsPanel.togglePanel();
    });
  }

  // Add settings styles
  const style = document.createElement('style');
  style.textContent = `
    /* Switch styles */
    .switch {
      position: relative;
      display: inline-block;
      width: 36px;
      height: 20px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #525252;
      transition: .4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 14px;
      width: 14px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
    }

    input:checked + .slider {
      background-color: #737373;
    }

    input:checked + .slider:before {
      transform: translateX(16px);
    }

    .slider.round {
      border-radius: 34px;
    }

    .slider.round:before {
      border-radius: 50%;
    }

    /* Range input styles */
    input[type=range] {
      -webkit-appearance: none;
      margin: 10px 0;
      background: transparent;
    }

    input[type=range]::-webkit-slider-thumb {
      -webkit-appearance: none;
      height: 16px;
      width: 16px;
      border-radius: 50%;
      background: #f8f8f8;
      cursor: pointer;
      margin-top: -6px;
    }

    input[type=range]::-moz-range-thumb {
      height: 16px;
      width: 16px;
      border-radius: 50%;
      background: #f8f8f8;
      cursor: pointer;
    }

    input[type=range]::-webkit-slider-runnable-track {
      width: 100%;
      height: 4px;
      cursor: pointer;
      background: #525252;
      border-radius: 25px;
    }

    input[type=range]::-moz-range-track {
      width: 100%;
      height: 4px;
      cursor: pointer;
      background: #525252;
      border-radius: 25px;
    }
  `;
  document.head.appendChild(style);
});
