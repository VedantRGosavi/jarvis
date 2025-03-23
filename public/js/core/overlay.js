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
    this.overlayElement.style.transition = 'opacity 0.3s ease';
  }
  
  initializeDraggable() {
    if (!this.overlayElement) return;
    
    const header = this.overlayElement.querySelector('header');
    let isDragging = false;
    let offsetX, offsetY;
    
    header.addEventListener('mousedown', (e) => {
      isDragging = true;
      offsetX = e.clientX - this.overlayElement.getBoundingClientRect().left;
      offsetY = e.clientY - this.overlayElement.getBoundingClientRect().top;
      
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
        
        // Save position to local storage
        this.saveSettings();
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
        this.toggleSettings();
      });
    }
  }
  
  updatePosition({ x, y }) {
    if (!this.overlayElement) return;
    
    this.position = { x, y };
    this.overlayElement.style.left = `${x}px`;
    this.overlayElement.style.top = `${y}px`;
  }
  
  setVisibility(visible) {
    if (!this.overlayElement) return;
    
    this.visible = visible;
    
    if (visible) {
      this.overlayElement.style.display = 'block';
      // Fade in
      setTimeout(() => {
        this.overlayElement.style.opacity = '1';
      }, 10);
    } else {
      // Fade out
      this.overlayElement.style.opacity = '0';
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
  
  toggleSettings() {
    // Implementation for settings panel
    console.log('Settings button clicked');
    // Future implementation
  }
  
  setOpacity(opacity) {
    if (!this.overlayElement) return;
    
    this.opacity = opacity;
    this.overlayElement.style.backgroundColor = `rgba(18, 18, 18, ${this.opacity})`;
    
    this.saveSettings();
  }
  
  saveSettings() {
    const settings = {
      position: this.position,
      visible: this.visible,
      opacity: this.opacity,
      minimized: this.overlayElement.querySelector('main').style.display === 'none'
    };
    
    localStorage.setItem('fridayai_overlay_settings', JSON.stringify(settings));
  }
  
  loadSettings() {
    try {
      const settings = JSON.parse(localStorage.getItem('fridayai_overlay_settings'));
      
      if (settings) {
        if (settings.position) this.updatePosition(settings.position);
        if (settings.opacity) this.setOpacity(settings.opacity);
        if (settings.visible !== undefined) this.setVisibility(settings.visible);
        
        if (settings.minimized) {
          const main = this.overlayElement.querySelector('main');
          const footer = this.overlayElement.querySelector('footer');
          
          if (main) main.style.display = 'none';
          if (footer) footer.style.display = 'none';
        }
      }
    } catch (error) {
      console.error('Error loading settings:', error);
    }
  }
}

// Initialize overlay when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  const overlay = new GameOverlay({
    visible: true,
    position: { x: window.innerWidth - 330, y: 10 }
  });
  
  // Expose to global scope for other scripts
  window.gameOverlay = overlay;
}); 