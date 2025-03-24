/**
 * Game Hotkeys Module
 * Manages keyboard shortcuts for the game companion overlay
 */

class GameHotkeys {
  constructor() {
    this.hotkeys = {};
    this.isEnabled = true;

    // Initialize keyboard event listeners
    document.addEventListener('keydown', (e) => {
      this.handleKeyDown(e);
    });
  }

  /**
   * Register a new hotkey
   * @param {string} keys - Keyboard shortcut (e.g., 'Ctrl+S', 'Alt+F')
   * @param {Function} callback - Function to execute when hotkey is pressed
   * @param {Object} options - Additional options
   */
  registerHotkey(keys, callback, options = {}) {
    const hotkeyConfig = {
      keys: this.parseKeys(keys),
      callback,
      options: {
        preventDefault: options.preventDefault !== undefined ? options.preventDefault : true,
        onlyWhenOverlayVisible: options.onlyWhenOverlayVisible !== undefined ? options.onlyWhenOverlayVisible : false,
        ...options
      }
    };

    this.hotkeys[keys] = hotkeyConfig;

    console.log(`Registered hotkey: ${keys}`);
  }

  /**
   * Unregister a hotkey
   * @param {string} keys - Keyboard shortcut to unregister
   */
  unregisterHotkey(keys) {
    if (this.hotkeys[keys]) {
      delete this.hotkeys[keys];
      console.log(`Unregistered hotkey: ${keys}`);
    }
  }

  /**
   * Enable or disable all hotkeys
   * @param {boolean} enabled - Whether hotkeys should be enabled
   */
  setEnabled(enabled) {
    this.isEnabled = enabled;
  }

  /**
   * Handle keydown event
   * @param {KeyboardEvent} event - Keyboard event
   */
  handleKeyDown(event) {
    if (!this.isEnabled) return;

    // Get active keys
    const activeKeys = {
      ctrl: event.ctrlKey,
      alt: event.altKey,
      shift: event.shiftKey,
      meta: event.metaKey,
      key: event.key.toLowerCase()
    };

    // Check each registered hotkey
    for (const hotkeyStr in this.hotkeys) {
      const hotkey = this.hotkeys[hotkeyStr];

      // Check if overlay visibility condition is met
      if (hotkey.options.onlyWhenOverlayVisible && window.gameOverlay && !window.gameOverlay.visible) {
        continue;
      }

      // Check if the keys match
      if (this.keysMatch(activeKeys, hotkey.keys)) {
        if (hotkey.options.preventDefault) {
          event.preventDefault();
        }

        hotkey.callback(event);
        return;
      }
    }
  }

  /**
   * Parse a hotkey string into components
   * @param {string} keyStr - Hotkey string (e.g., 'Ctrl+S')
   * @returns {Object} Parsed keys
   */
  parseKeys(keyStr) {
    const parts = keyStr.split('+');
    const result = {
      ctrl: false,
      alt: false,
      shift: false,
      meta: false,
      key: null
    };

    parts.forEach(part => {
      const lowered = part.toLowerCase().trim();

      if (lowered === 'ctrl' || lowered === 'control') {
        result.ctrl = true;
      } else if (lowered === 'alt') {
        result.alt = true;
      } else if (lowered === 'shift') {
        result.shift = true;
      } else if (lowered === 'meta' || lowered === 'cmd' || lowered === 'command') {
        result.meta = true;
      } else {
        // Handle special key names
        switch (lowered) {
          case 'esc':
          case 'escape':
            result.key = 'escape';
            break;
          case 'enter':
          case 'return':
            result.key = 'enter';
            break;
          case 'space':
          case 'spacebar':
            result.key = ' ';
            break;
          default:
            result.key = lowered;
        }
      }
    });

    return result;
  }

  /**
   * Check if the pressed keys match a hotkey configuration
   * @param {Object} activeKeys - Currently active keys
   * @param {Object} configKeys - Hotkey configuration
   * @returns {boolean} Whether the keys match
   */
  keysMatch(activeKeys, configKeys) {
    return (
      activeKeys.ctrl === configKeys.ctrl &&
      activeKeys.alt === configKeys.alt &&
      activeKeys.shift === configKeys.shift &&
      activeKeys.meta === configKeys.meta &&
      activeKeys.key === configKeys.key
    );
  }
}

// Initialize hotkeys
document.addEventListener('DOMContentLoaded', () => {
  const gameHotkeys = new GameHotkeys();

  // Register default hotkeys
  gameHotkeys.registerHotkey('Ctrl+Shift+J', () => {
    if (window.gameOverlay) {
      window.gameOverlay.toggleVisibility();
    }
  });

  // Register macOS-specific hotkey
  if (navigator.platform.includes('Mac')) {
    gameHotkeys.registerHotkey('Meta+Shift+J', () => {
      if (window.gameOverlay) {
        window.gameOverlay.toggleVisibility();
      }
    });
  }

  gameHotkeys.registerHotkey('Escape', () => {
    // Only handle Escape when overlay is visible
    if (window.gameOverlay && window.gameOverlay.visible) {
      if (window.viewManager && window.viewManager.currentView !== 'home') {
        window.viewManager.showHomeView();
      } else {
        window.gameOverlay.setVisibility(false);
      }
    }
  }, { onlyWhenOverlayVisible: true });

  // Expose to global scope for other scripts
  window.gameHotkeys = gameHotkeys;
});
