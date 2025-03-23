/**
 * UI Components
 * Reusable UI components for the gaming companion overlay
 */

class UIComponents {
  constructor() {
    this.components = {};
  }
  
  /**
   * Creates a collapsible section
   * @param {string} title - Section title
   * @param {string} content - HTML content
   * @param {boolean} isCollapsed - Initial collapsed state
   * @returns {HTMLElement} The created section element
   */
  createCollapsibleSection(title, content, isCollapsed = false) {
    const section = document.createElement('div');
    section.className = 'mb-3 bg-gaming-gray-800 rounded border border-gaming-gray-700 overflow-hidden';
    
    const header = document.createElement('div');
    header.className = 'flex justify-between items-center p-2 cursor-pointer hover:bg-gaming-gray-700 transition';
    header.innerHTML = `
      <h3 class="text-sm font-semibold">${title}</h3>
      <span class="collapsible-arrow">${isCollapsed ? '▶' : '▼'}</span>
    `;
    
    const body = document.createElement('div');
    body.className = 'p-2 border-t border-gaming-gray-700';
    body.innerHTML = content;
    
    if (isCollapsed) {
      body.style.display = 'none';
    }
    
    header.addEventListener('click', () => {
      const isHidden = body.style.display === 'none';
      body.style.display = isHidden ? 'block' : 'none';
      header.querySelector('.collapsible-arrow').textContent = isHidden ? '▼' : '▶';
    });
    
    section.appendChild(header);
    section.appendChild(body);
    
    return section;
  }
  
  /**
   * Creates a tab interface
   * @param {Array} tabs - Array of tab objects {id, label, content}
   * @returns {HTMLElement} The created tabs container
   */
  createTabs(tabs) {
    const container = document.createElement('div');
    container.className = 'tabs-container';
    
    const tabsNav = document.createElement('div');
    tabsNav.className = 'flex border-b border-gaming-gray-700 mb-2';
    
    const tabsContent = document.createElement('div');
    tabsContent.className = 'tabs-content';
    
    tabs.forEach((tab, index) => {
      // Create tab button
      const tabBtn = document.createElement('button');
      tabBtn.className = `py-1 px-3 text-sm ${index === 0 ? 'bg-gaming-gray-700 border-b-2 border-gaming-gray-500' : 'hover:bg-gaming-gray-800 transition'}`;
      tabBtn.textContent = tab.label;
      tabBtn.dataset.tabId = tab.id;
      
      // Create tab content
      const tabContent = document.createElement('div');
      tabContent.className = 'tab-pane';
      tabContent.innerHTML = tab.content;
      tabContent.dataset.tabId = tab.id;
      tabContent.style.display = index === 0 ? 'block' : 'none';
      
      // Add click handler
      tabBtn.addEventListener('click', () => {
        // Update active tab button
        tabsNav.querySelectorAll('button').forEach(btn => {
          btn.className = 'py-1 px-3 text-sm hover:bg-gaming-gray-800 transition';
        });
        tabBtn.className = 'py-1 px-3 text-sm bg-gaming-gray-700 border-b-2 border-gaming-gray-500';
        
        // Show selected tab content, hide others
        tabsContent.querySelectorAll('.tab-pane').forEach(pane => {
          pane.style.display = 'none';
        });
        tabContent.style.display = 'block';
      });
      
      tabsNav.appendChild(tabBtn);
      tabsContent.appendChild(tabContent);
    });
    
    container.appendChild(tabsNav);
    container.appendChild(tabsContent);
    
    return container;
  }
  
  /**
   * Creates a search result item
   * @param {Object} result - Search result object
   * @returns {HTMLElement} The created result element
   */
  createSearchResultItem(result) {
    const item = document.createElement('div');
    item.className = 'p-2 border-b border-gaming-gray-700 hover:bg-gaming-gray-700 cursor-pointer transition';
    
    item.innerHTML = `
      <div class="flex justify-between">
        <span class="font-medium">${result.title}</span>
        <span class="text-xs text-gaming-gray-400">${result.type}</span>
      </div>
      <p class="text-xs text-gaming-gray-300 mt-1">${result.description}</p>
    `;
    
    item.addEventListener('click', () => {
      if (result.onClick && typeof result.onClick === 'function') {
        result.onClick(result);
      }
    });
    
    return item;
  }
  
  /**
   * Creates a tooltip
   * @param {HTMLElement} element - Element to attach tooltip to
   * @param {string} text - Tooltip text
   */
  createTooltip(element, text) {
    element.dataset.tooltip = text;
    element.className += ' relative';
    
    element.addEventListener('mouseenter', (e) => {
      const tooltip = document.createElement('div');
      tooltip.className = 'absolute bg-gaming-primary text-white p-1 text-xs rounded z-50 tooltip';
      tooltip.textContent = text;
      tooltip.style.bottom = '100%';
      tooltip.style.left = '50%';
      tooltip.style.transform = 'translateX(-50%)';
      tooltip.style.marginBottom = '5px';
      tooltip.style.whiteSpace = 'nowrap';
      
      element.appendChild(tooltip);
    });
    
    element.addEventListener('mouseleave', () => {
      const tooltip = element.querySelector('.tooltip');
      if (tooltip) {
        tooltip.remove();
      }
    });
  }
}

// Initialize UI components
document.addEventListener('DOMContentLoaded', () => {
  const uiComponents = new UIComponents();
  
  // Expose to global scope for other scripts
  window.uiComponents = uiComponents;
}); 