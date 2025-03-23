/**
 * Game Companion Overlay
 * Manages the overlay window positioning, visibility, and interaction
 */

class GameOverlay {
  constructor(config = {}) {
    this.visible = config.visible || false;
    this.opacity = config.opacity || 0.85;
    this.position = config.position || { x: 10, y: 10 };
    this.size = config.size || { width: 320, height: 480 };
    this.currentGameId = null;
    this.overlayElement = document.getElementById('fridayai-overlay');
    
    this.initializeOverlay();
    this.initializeDraggable();
    this.initializeButtons();
    this.loadSettings();
  }
  
  initializeOverlay() {
    if (!this.overlayElement) {
      console.error('Overlay element not found');
      return;
    }
    
    // Set initial position
    this.updatePosition(this.position);
    
    // Set initial visibility
    this.setVisibility(this.visible);
    
    // Apply styles
    this.overlayElement.style.position = 'fixed';
    this.overlayElement.style.zIndex = '9999';
    this.overlayElement.style.backgroundColor = `rgba(18, 18, 18, ${this.opacity})`;
    this.overlayElement.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    this.overlayElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    
    // Add class for animations
    this.overlayElement.classList.add('overlay-transition');
  }
  
  initializeDraggable() {
    if (!this.overlayElement) return;
    
    const header = this.overlayElement.querySelector('header');
    let isDragging = false;
    let offsetX, offsetY;
    let startPos = { x: 0, y: 0 };
    
    header.addEventListener('mousedown', (e) => {
      isDragging = true;
      offsetX = e.clientX - this.overlayElement.getBoundingClientRect().left;
      offsetY = e.clientY - this.overlayElement.getBoundingClientRect().top;
      startPos = { ...this.position };
      
      // Add a dragging class
      this.overlayElement.classList.add('dragging');
    });
    
    document.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      
      const x = e.clientX - offsetX;
      const y = e.clientY - offsetY;
      
      this.updatePosition({ x, y });
    });
    
    document.addEventListener('mouseup', () => {
      if (isDragging) {
        isDragging = false;
        this.overlayElement.classList.remove('dragging');
        
        // Check if position has changed significantly
        if (Math.abs(startPos.x - this.position.x) > 5 || Math.abs(startPos.y - this.position.y) > 5) {
          // Update position preset based on current position
          this.updatePositionPresetFromCoordinates();
          
          // Save position to local storage
          this.saveSettings();
        }
      }
    });
  }
  
  initializeButtons() {
    const minimizeBtn = document.getElementById('minimize-btn');
    const closeBtn = document.getElementById('close-btn');
    const settingsBtn = document.getElementById('settings-btn');
    
    if (minimizeBtn) {
      minimizeBtn.addEventListener('click', () => {
        this.minimize();
      });
    }
    
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        this.setVisibility(false);
      });
    }
    
    if (settingsBtn) {
      settingsBtn.addEventListener('click', () => {
        if (window.settingsPanel) {
          window.settingsPanel.togglePanel();
        }
      });
    }
  }
  
  updatePosition({ x, y }) {
    if (!this.overlayElement) return;
    
    // Clamp position to ensure overlay stays within screen bounds
    const maxX = window.innerWidth - this.overlayElement.offsetWidth;
    const maxY = window.innerHeight - this.overlayElement.offsetHeight;
    
    const clampedX = Math.max(0, Math.min(x, maxX));
    const clampedY = Math.max(0, Math.min(y, maxY));
    
    this.position = { x: clampedX, y: clampedY };
    this.overlayElement.style.left = `${clampedX}px`;
    this.overlayElement.style.top = `${clampedY}px`;
  }
  
  /**
   * Update position preset based on current coordinates
   */
  updatePositionPresetFromCoordinates() {
    if (!this.currentGameId || !window.gameDetection) return;
    
    // Calculate which preset position is closest to the current position
    const presets = window.gameDetection.positionPresets;
    let closestPreset = 'top-right';
    let closestDistance = Infinity;
    
    for (const [presetName, presetPos] of Object.entries(presets)) {
      const distance = Math.sqrt(
        Math.pow(this.position.x - presetPos.x, 2) + 
        Math.pow(this.position.y - presetPos.y, 2)
      );
      
      if (distance < closestDistance) {
        closestDistance = distance;
        closestPreset = presetName;
      }
    }
    
    // Only update if distance is reasonably close to a preset
    if (closestDistance < 100) {
      // Update game detection with new position preference
      window.gameDetection.setGamePositionPreference(this.currentGameId, closestPreset);
      
      // Dispatch position changed event
      const event = new CustomEvent('overlayPositionChanged', {
        detail: { gameId: this.currentGameId, position: closestPreset }
      });
      document.dispatchEvent(event);
    }
  }
  
  setVisibility(visible) {
    if (!this.overlayElement) return;
    
    this.visible = visible;
    
    if (visible) {
      this.overlayElement.style.display = 'block';
      // Fade in
      setTimeout(() => {
        this.overlayElement.style.opacity = '1';
        this.overlayElement.style.transform = 'translateY(0)';
      }, 10);
    } else {
      // Fade out
      this.overlayElement.style.opacity = '0';
      this.overlayElement.style.transform = 'translateY(10px)';
      setTimeout(() => {
        this.overlayElement.style.display = 'none';
      }, 300);
    }
    
    // Save to local storage
    this.saveSettings();
  }
  
  toggleVisibility() {
    this.setVisibility(!this.visible);
  }
  
  minimize() {
    // Implementation for minimizing to a small bar
    const main = this.overlayElement.querySelector('main');
    const footer = this.overlayElement.querySelector('footer');
    
    if (main.style.display === 'none') {
      main.style.display = 'block';
      if (footer) footer.style.display = 'block';
    } else {
      main.style.display = 'none';
      if (footer) footer.style.display = 'none';
    }
    
    this.saveSettings();
  }
  
  /**
   * Set the opacity of the overlay
   * @param {number} opacity - Opacity value (0-1)
   */
  setOpacity(opacity) {
    if (opacity < 0 || opacity > 1) {
      console.error('Opacity must be between 0 and 1');
      return;
    }
    
    this.opacity = opacity;
    
    if (this.overlayElement) {
      this.overlayElement.style.backgroundColor = `rgba(18, 18, 18, ${this.opacity})`;
    }
    
    // Save settings
    this.saveSettings();
  }
  
  /**
   * Set the current game
   * @param {string} gameId - Game identifier
   */
  setCurrentGame(gameId) {
    this.currentGameId = gameId;
  }
  
  /**
   * Save current overlay settings
   */
  saveSettings() {
    const settings = {
      opacity: this.opacity,
      position: this.position,
      visible: this.visible
    };
    
    localStorage.setItem('fridayai_overlay_settings', JSON.stringify(settings));
  }
  
  /**
   * Load saved overlay settings
   */
  loadSettings() {
    try {
      const settings = localStorage.getItem('fridayai_overlay_settings');
      if (settings) {
        const parsedSettings = JSON.parse(settings);
        
        if (parsedSettings.opacity !== undefined) {
          this.opacity = parsedSettings.opacity;
        }
        
        if (parsedSettings.position) {
          this.position = parsedSettings.position;
        }
        
        if (parsedSettings.visible !== undefined) {
          this.visible = parsedSettings.visible;
        }
      }
    } catch (e) {
      console.error('Error loading overlay settings:', e);
    }
  }
  
  /**
   * Get position name for coordinates
   * @param {Object} position - Position coordinates
   * @returns {string} Position name
   */
  getPositionName(position) {
    if (!window.gameDetection) return 'custom';
    
    // Get all position presets
    const presets = window.gameDetection.positionPresets;
    
    // Find closest preset
    let closestPreset = 'custom';
    let closestDistance = Infinity;
    
    for (const [presetName, presetPos] of Object.entries(presets)) {
      const distance = Math.sqrt(
        Math.pow(position.x - presetPos.x, 2) + 
        Math.pow(position.y - presetPos.y, 2)
      );
      
      if (distance < closestDistance) {
        closestDistance = distance;
        closestPreset = presetName;
      }
    }
    
    // Only use preset name if distance is reasonably close
    return closestDistance < 50 ? closestPreset : 'custom';
  }
}

// Initialize overlay when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  const overlay = new GameOverlay({
    visible: true,
    position: { x: window.innerWidth - 330, y: 10 }
  });
  
  // Listen for game detection events
  document.addEventListener('gameDetected', (e) => {
    const gameInfo = e.detail;
    if (gameInfo && gameInfo.id) {
      // Set current game for the overlay
      overlay.setCurrentGame(gameInfo.id);
    }
  });
  
  // Expose to global scope for other scripts
  window.gameOverlay = overlay;
}); 