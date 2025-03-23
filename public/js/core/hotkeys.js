/**
 * Hotkeys Handler
 * Manages keyboard shortcuts for controlling the overlay
 */

class HotkeysHandler {
  constructor() {
    this.shortcuts = {
      toggleOverlay: { key: 'j', ctrl: true, shift: false, alt: false },
      increaseOpacity: { key: 'ArrowUp', ctrl: true, shift: true, alt: false },
      decreaseOpacity: { key: 'ArrowDown', ctrl: true, shift: true, alt: false }
    };
    
    this.initialize();
  }
  
  initialize() {
    document.addEventListener('keydown', (event) => {
      this.handleKeyPress(event);
    });
    
    console.log('Hotkeys handler initialized');
  }
  
  handleKeyPress(event) {
    // Toggle overlay visibility with Ctrl+J
    if (this.matchesShortcut(event, this.shortcuts.toggleOverlay)) {
      if (window.gameOverlay) {
        window.gameOverlay.toggleVisibility();
      }
      event.preventDefault();
    }
    
    // Increase overlay opacity with Ctrl+Shift+Up
    if (this.matchesShortcut(event, this.shortcuts.increaseOpacity)) {
      if (window.gameOverlay) {
        const newOpacity = Math.min(1, window.gameOverlay.opacity + 0.1);
        window.gameOverlay.setOpacity(newOpacity);
      }
      event.preventDefault();
    }
    
    // Decrease overlay opacity with Ctrl+Shift+Down
    if (this.matchesShortcut(event, this.shortcuts.decreaseOpacity)) {
      if (window.gameOverlay) {
        const newOpacity = Math.max(0.1, window.gameOverlay.opacity - 0.1);
        window.gameOverlay.setOpacity(newOpacity);
      }
      event.preventDefault();
    }
  }
  
  matchesShortcut(event, shortcut) {
    return event.key.toLowerCase() === shortcut.key.toLowerCase() &&
           event.ctrlKey === shortcut.ctrl &&
           event.shiftKey === shortcut.shift &&
           event.altKey === shortcut.alt;
  }
  
  updateShortcut(name, newShortcut) {
    if (this.shortcuts[name]) {
      this.shortcuts[name] = {...newShortcut};
      this.saveShortcuts();
      return true;
    }
    return false;
  }
  
  saveShortcuts() {
    localStorage.setItem('jarvis_hotkeys', JSON.stringify(this.shortcuts));
  }
  
  loadShortcuts() {
    try {
      const savedShortcuts = JSON.parse(localStorage.getItem('jarvis_hotkeys'));
      if (savedShortcuts) {
        this.shortcuts = {...this.shortcuts, ...savedShortcuts};
      }
    } catch (error) {
      console.error('Error loading shortcuts:', error);
    }
  }
}

// Initialize hotkeys when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  const hotkeysHandler = new HotkeysHandler();
  
  // Expose to global scope for other scripts
  window.hotkeysHandler = hotkeysHandler;
}); 