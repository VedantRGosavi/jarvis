/**
 * Game Overlay View Manager
 * Manages different views and content displays in the game companion overlay
 */

class ViewManager {
  constructor() {
    this.currentGame = null;
    this.currentView = 'home';
    this.currentContentType = null;
    this.contentArea = document.getElementById('content-area');
    this.searchInput = document.querySelector('#search-input');
    
    // Initialize event listeners
    this.initEventListeners();
  }
  
  initEventListeners() {
    // Initialize search functionality
    if (this.searchInput) {
      this.searchInput.addEventListener('input', debounce((e) => {
        this.handleSearch(e.target.value);
      }, 300));
    }
  }
  
  /**
   * Set the current game
   * @param {string} gameId - Game identifier 
   */
  setGame(gameId) {
    this.currentGame = gameId;
    
    // Update UI elements for the selected game
    const gameElements = document.querySelectorAll('.game-selector');
    gameElements.forEach(el => {
      if (el.dataset.game === gameId) {
        el.classList.add('border-l-2', 'border-gaming-gray-500', 'bg-gaming-gray-700');
      } else {
        el.classList.remove('border-l-2', 'border-gaming-gray-500', 'bg-gaming-gray-700');
      }
    });
    
    // Reset view to home for the new game
    this.showHomeView();
  }
  
  /**
   * Show the home view with navigation options
   */
  showHomeView() {
    this.currentView = 'home';
    this.currentContentType = null;
    
    if (!this.contentArea) return;
    
    this.contentArea.innerHTML = `
      <div class="bg-gaming-gray-800 p-2 rounded border border-gaming-gray-700">
        <h3 class="text-sm font-semibold mb-1">Quick Navigation</h3>
        <div class="grid grid-cols-2 gap-1 text-xs">
          <button data-content-type="quests" class="nav-button bg-gaming-gray-700 hover:bg-gaming-gray-600 p-1 rounded transition">Quests</button>
          <button data-content-type="locations" class="nav-button bg-gaming-gray-700 hover:bg-gaming-gray-600 p-1 rounded transition">Locations</button>
          <button data-content-type="items" class="nav-button bg-gaming-gray-700 hover:bg-gaming-gray-600 p-1 rounded transition">Items</button>
          <button data-content-type="npcs" class="nav-button bg-gaming-gray-700 hover:bg-gaming-gray-600 p-1 rounded transition">NPCs</button>
        </div>
      </div>
      
      <div class="mt-3 text-xs text-gaming-gray-400">
        <p>Press <span class="bg-gaming-gray-700 px-1 rounded">Ctrl+J</span> to toggle overlay visibility</p>
      </div>
    `;
    
    // Add event listeners to navigation buttons
    const navButtons = this.contentArea.querySelectorAll('.nav-button');
    navButtons.forEach(button => {
      button.addEventListener('click', () => {
        const contentType = button.dataset.contentType;
        this.showContentListView(contentType);
      });
    });
  }
  
  /**
   * Show a list of content items by type
   * @param {string} contentType - Content type (quests, locations, items, npcs)
   */
  async showContentListView(contentType) {
    if (!this.currentGame) {
      this.showMessage('Please select a game first');
      return;
    }
    
    this.currentView = 'list';
    this.currentContentType = contentType;
    
    if (!this.contentArea) return;
    
    // Show loading indicator
    this.contentArea.innerHTML = `
      <div class="text-center py-4">
        <div class="inline-block animate-spin rounded-full h-5 w-5 border-t-2 border-b-2 border-gaming-gray-400"></div>
        <p class="mt-2 text-sm text-gaming-gray-400">Loading ${contentType}...</p>
      </div>
    `;
    
    try {
      // Get data based on content type
      let data = [];
      
      switch (contentType) {
        case 'quests':
          data = await window.gameDataService.getQuests(this.currentGame);
          break;
        case 'locations':
          data = await window.gameDataService.getLocations(this.currentGame);
          break;
        case 'items':
          data = await window.gameDataService.getItems(this.currentGame);
          break;
        case 'npcs':
          data = await window.gameDataService.getNpcs(this.currentGame);
          break;
      }
      
      // Format the display based on content type
      let listHtml = '';
      
      if (data.length === 0) {
        listHtml = `<p class="text-sm text-gaming-gray-400 text-center py-4">No ${contentType} found</p>`;
      } else {
        listHtml = `
          <div class="space-y-1">
            ${data.map(item => this.renderListItem(item, contentType)).join('')}
          </div>
        `;
      }
      
      this.contentArea.innerHTML = `
        <div class="mb-3 flex items-center">
          <button id="back-btn" class="bg-gaming-gray-700 hover:bg-gaming-gray-600 rounded-full w-6 h-6 flex items-center justify-center mr-2">
            <span class="text-xs">←</span>
          </button>
          <h3 class="text-sm font-semibold">${this.capitalizeFirstLetter(contentType)}</h3>
        </div>
        ${listHtml}
      `;
      
      // Add event listener to back button
      const backBtn = document.getElementById('back-btn');
      if (backBtn) {
        backBtn.addEventListener('click', () => {
          this.showHomeView();
        });
      }
      
      // Add event listeners to list items
      const listItems = this.contentArea.querySelectorAll('.game-list-item');
      listItems.forEach(item => {
        item.addEventListener('click', () => {
          const itemId = item.dataset.id;
          this.showDetailView(contentType, itemId);
        });
      });
      
    } catch (error) {
      console.error(`Error loading ${contentType}:`, error);
      this.contentArea.innerHTML = `
        <div class="text-center py-4">
          <p class="text-sm text-gaming-red-500">Failed to load ${contentType}</p>
          <button id="retry-btn" class="mt-2 bg-gaming-gray-700 hover:bg-gaming-gray-600 px-3 py-1 rounded text-xs">Retry</button>
        </div>
      `;
      
      const retryBtn = document.getElementById('retry-btn');
      if (retryBtn) {
        retryBtn.addEventListener('click', () => {
          this.showContentListView(contentType);
        });
      }
    }
  }
  
  /**
   * Show detailed view of a content item
   * @param {string} contentType - Content type (quests, locations, items, npcs)
   * @param {string} itemId - Item identifier
   */
  async showDetailView(contentType, itemId) {
    if (!this.currentGame || !this.contentArea) return;
    
    this.currentView = 'detail';
    
    // Show loading indicator
    this.contentArea.innerHTML = `
      <div class="text-center py-4">
        <div class="inline-block animate-spin rounded-full h-5 w-5 border-t-2 border-b-2 border-gaming-gray-400"></div>
        <p class="mt-2 text-sm text-gaming-gray-400">Loading details...</p>
      </div>
    `;
    
    try {
      let detailData = null;
      
      // Fetch data based on content type
      switch (contentType) {
        case 'quests':
          detailData = await window.gameDataService.getQuestDetails(this.currentGame, itemId);
          break;
        case 'locations':
          // Fetch location details including points of interest
          detailData = await window.gameDataService.getGameData(this.currentGame, 'locations', { id: itemId, include: 'points_of_interest' });
          detailData = detailData[0] || null;
          break;
        case 'items':
          // Fetch item details
          detailData = await window.gameDataService.getGameData(this.currentGame, 'items', { id: itemId });
          detailData = detailData[0] || null;
          break;
        case 'npcs':
          // Fetch NPC details including dialogue
          detailData = await window.gameDataService.getGameData(this.currentGame, 'npcs', { id: itemId, include: 'dialogue,locations' });
          detailData = detailData[0] || null;
          break;
      }
      
      if (!detailData) {
        this.contentArea.innerHTML = `
          <div class="mb-3 flex items-center">
            <button id="back-btn" class="bg-gaming-gray-700 hover:bg-gaming-gray-600 rounded-full w-6 h-6 flex items-center justify-center mr-2">
              <span class="text-xs">←</span>
            </button>
            <h3 class="text-sm font-semibold">${this.capitalizeFirstLetter(contentType)} Detail</h3>
          </div>
          <p class="text-sm text-gaming-gray-400 text-center py-4">No details found</p>
        `;
      } else {
        // Render details based on content type
        const detailHtml = this.renderDetailView(detailData, contentType);
        
        this.contentArea.innerHTML = `
          <div class="mb-3 flex items-center">
            <button id="back-btn" class="bg-gaming-gray-700 hover:bg-gaming-gray-600 rounded-full w-6 h-6 flex items-center justify-center mr-2">
              <span class="text-xs">←</span>
            </button>
            <h3 class="text-sm font-semibold">${detailData.name}</h3>
          </div>
          ${detailHtml}
        `;
      }
      
      // Add event listener to back button
      const backBtn = document.getElementById('back-btn');
      if (backBtn) {
        backBtn.addEventListener('click', () => {
          this.showContentListView(this.currentContentType);
        });
      }
      
    } catch (error) {
      console.error(`Error loading details:`, error);
      this.contentArea.innerHTML = `
        <div class="mb-3 flex items-center">
          <button id="back-btn" class="bg-gaming-gray-700 hover:bg-gaming-gray-600 rounded-full w-6 h-6 flex items-center justify-center mr-2">
            <span class="text-xs">←</span>
          </button>
          <h3 class="text-sm font-semibold">Error</h3>
        </div>
        <p class="text-sm text-gaming-red-500 text-center py-4">Failed to load details</p>
      `;
      
      const backBtn = document.getElementById('back-btn');
      if (backBtn) {
        backBtn.addEventListener('click', () => {
          this.showContentListView(this.currentContentType);
        });
      }
    }
  }
  
  /**
   * Handle search input
   * @param {string} query - Search query
   */
  async handleSearch(query) {
    if (!query || query.trim().length < 2 || !this.currentGame) return;
    
    this.currentView = 'search';
    
    if (!this.contentArea) return;
    
    // Show searching indicator
    this.contentArea.innerHTML = `
      <div class="mb-3">
        <h3 class="text-sm font-semibold">Search Results</h3>
        <p class="text-xs text-gaming-gray-400">Searching for "${query}"...</p>
      </div>
      <div class="text-center py-2">
        <div class="inline-block animate-spin rounded-full h-4 w-4 border-t-2 border-b-2 border-gaming-gray-400"></div>
      </div>
    `;
    
    try {
      const results = await window.gameDataService.search(this.currentGame, query);
      
      if (results.length === 0) {
        this.contentArea.innerHTML = `
          <div class="mb-3">
            <h3 class="text-sm font-semibold">Search Results</h3>
            <p class="text-xs text-gaming-gray-400">No results found for "${query}"</p>
          </div>
        `;
      } else {
        this.contentArea.innerHTML = `
          <div class="mb-3">
            <h3 class="text-sm font-semibold">Search Results</h3>
            <p class="text-xs text-gaming-gray-400">${results.length} results for "${query}"</p>
          </div>
          <div class="space-y-1">
            ${results.map(item => this.renderSearchResult(item)).join('')}
          </div>
        `;
        
        // Add event listeners to search results
        const resultItems = this.contentArea.querySelectorAll('.search-result');
        resultItems.forEach(item => {
          item.addEventListener('click', () => {
            const itemId = item.dataset.id;
            const contentType = item.dataset.type;
            this.showDetailView(contentType, itemId);
          });
        });
      }
    } catch (error) {
      console.error('Error during search:', error);
      this.contentArea.innerHTML = `
        <div class="mb-3">
          <h3 class="text-sm font-semibold">Search Results</h3>
          <p class="text-xs text-gaming-red-500">Error searching for "${query}"</p>
        </div>
      `;
    }
  }
  
  /**
   * Render a list item
   * @param {Object} item - Item data
   * @param {string} type - Content type
   * @returns {string} HTML for list item
   */
  renderListItem(item, type) {
    const itemId = item.quest_id || item.location_id || item.item_id || item.npc_id;
    const subtitle = this.getSubtitleForType(item, type);
    
    return `
      <div class="game-list-item flex items-center bg-gaming-gray-700 rounded p-2 cursor-pointer hover:bg-gaming-gray-600 transition" data-id="${itemId}">
        <div class="flex-1">
          <div class="font-medium text-sm">${item.name}</div>
          <div class="text-xs text-gaming-gray-400">${subtitle}</div>
        </div>
        <div class="text-gaming-gray-400">
          <span class="text-xs">→</span>
        </div>
      </div>
    `;
  }
  
  /**
   * Get appropriate subtitle based on content type
   * @param {Object} item - Item data
   * @param {string} type - Content type
   * @returns {string} Subtitle text
   */
  getSubtitleForType(item, type) {
    switch (type) {
      case 'quests':
        return item.is_main_story === 1 ? 'Main Quest' : 'Side Quest';
      case 'locations':
        return item.region || 'Unknown Region';
      case 'items':
        return `${this.capitalizeFirstLetter(item.type || '')} - ${this.capitalizeFirstLetter(item.rarity || '')}`;
      case 'npcs':
        return item.role || (item.is_hostile === 1 ? 'Hostile' : 'Friendly');
      default:
        return '';
    }
  }
  
  /**
   * Render search result item
   * @param {Object} result - Search result item
   * @returns {string} HTML for search result
   */
  renderSearchResult(result) {
    const itemId = result.content_id;
    const contentType = result.content_type;
    
    return `
      <div class="search-result flex items-center bg-gaming-gray-700 rounded p-2 cursor-pointer hover:bg-gaming-gray-600 transition" data-id="${itemId}" data-type="${contentType}">
        <div class="flex-1">
          <div class="flex justify-between">
            <span class="font-medium text-sm">${result.name}</span>
            <span class="text-xs text-gaming-gray-400">${this.capitalizeFirstLetter(contentType)}</span>
          </div>
          <p class="text-xs text-gaming-gray-300 mt-1 line-clamp-1">${result.description}</p>
        </div>
      </div>
    `;
  }
  
  /**
   * Render detail view for item
   * @param {Object} data - Item data
   * @param {string} contentType - Content type
   * @returns {string} HTML for detail view
   */
  renderDetailView(data, contentType) {
    switch (contentType) {
      case 'quests':
        return this.renderQuestDetail(data);
      case 'locations':
        return this.renderLocationDetail(data);
      case 'items':
        return this.renderItemDetail(data);
      case 'npcs':
        return this.renderNpcDetail(data);
      default:
        return `<p class="text-sm text-gaming-gray-400">No details available</p>`;
    }
  }
  
  /**
   * Render quest detail view
   * @param {Object} quest - Quest data
   * @returns {string} HTML for quest detail
   */
  renderQuestDetail(quest) {
    const steps = quest.steps || [];
    const questTypeClass = quest.is_main_story === 1 ? 'text-gaming-gold-500' : 'text-gaming-blue-500';
    
    return `
      <div class="quest-detail">
        <div class="mb-2 flex justify-between items-center">
          <span class="${questTypeClass} text-xs">${quest.is_main_story === 1 ? 'Main Quest' : 'Side Quest'}</span>
          <span class="text-xs text-gaming-gray-400">Difficulty: ${this.capitalizeFirstLetter(quest.difficulty || 'Normal')}</span>
        </div>
        
        <p class="text-sm mb-3">${quest.description}</p>
        
        <h4 class="text-sm font-semibold mb-2">Quest Steps</h4>
        <div class="space-y-3">
          ${steps.map(step => `
            <div class="bg-gaming-gray-700 p-2 rounded">
              <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-semibold">${step.title}</span>
                <span class="text-xs text-gaming-gray-400">Step ${step.step_number}</span>
              </div>
              <p class="text-xs">${step.description}</p>
              ${step.objective ? `<p class="text-xs mt-1"><span class="text-gaming-gray-400">Objective:</span> ${step.objective}</p>` : ''}
              ${step.hints ? `<p class="text-xs mt-1 text-gaming-gray-400 italic">Hint: ${step.hints}</p>` : ''}
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }
  
  /**
   * Render location detail view
   * @param {Object} location - Location data
   * @returns {string} HTML for location detail
   */
  renderLocationDetail(location) {
    return `
      <div class="location-detail">
        <div class="mb-2">
          <span class="text-xs text-gaming-gray-400">${location.region || 'Unknown Region'}</span>
        </div>
        
        <p class="text-sm mb-3">${location.description}</p>
        
        ${location.parent_location_id ? `
          <p class="text-xs mb-2">
            <span class="text-gaming-gray-400">Part of:</span> ${location.parent_location_name || location.parent_location_id}
          </p>
        ` : ''}
        
        ${location.connected_locations ? `
          <h4 class="text-xs font-semibold mb-1">Connected Locations</h4>
          <p class="text-xs mb-2">${location.connected_locations}</p>
        ` : ''}
        
        ${location.notable_npcs ? `
          <h4 class="text-xs font-semibold mb-1">Notable NPCs</h4>
          <p class="text-xs mb-2">${location.notable_npcs}</p>
        ` : ''}
        
        ${location.notable_items ? `
          <h4 class="text-xs font-semibold mb-1">Notable Items</h4>
          <p class="text-xs mb-2">${location.notable_items}</p>
        ` : ''}
        
        ${location.points_of_interest ? `
          <h4 class="text-xs font-semibold mb-1">Points of Interest</h4>
          <p class="text-xs">${location.points_of_interest}</p>
        ` : ''}
      </div>
    `;
  }
  
  /**
   * Render item detail view
   * @param {Object} item - Item data
   * @returns {string} HTML for item detail
   */
  renderItemDetail(item) {
    // Parse JSON data if necessary
    let stats = item.stats;
    let requirements = item.requirements;
    
    try {
      if (typeof item.stats === 'string') {
        stats = JSON.parse(item.stats);
      }
      if (typeof item.requirements === 'string') {
        requirements = JSON.parse(item.requirements);
      }
    } catch (e) {
      console.error('Error parsing item data:', e);
    }
    
    // Determine rarity color
    let rarityColorClass = 'text-gaming-gray-400';
    switch (item.rarity) {
      case 'uncommon':
        rarityColorClass = 'text-gaming-green-500';
        break;
      case 'rare':
        rarityColorClass = 'text-gaming-blue-500';
        break;
      case 'epic':
        rarityColorClass = 'text-gaming-purple-500';
        break;
      case 'legendary':
        rarityColorClass = 'text-gaming-gold-500';
        break;
    }
    
    return `
      <div class="item-detail">
        <div class="mb-2 flex justify-between items-center">
          <span class="text-xs">${this.capitalizeFirstLetter(item.type || 'Item')}${item.subtype ? ` - ${item.subtype}` : ''}</span>
          <span class="text-xs ${rarityColorClass}">${this.capitalizeFirstLetter(item.rarity || 'Common')}</span>
        </div>
        
        <p class="text-sm mb-3">${item.description}</p>
        
        ${stats ? `
          <h4 class="text-xs font-semibold mb-1">Stats</h4>
          <div class="bg-gaming-gray-700 p-2 rounded mb-2 text-xs">
            ${this.renderObjectProperties(stats)}
          </div>
        ` : ''}
        
        ${requirements ? `
          <h4 class="text-xs font-semibold mb-1">Requirements</h4>
          <div class="bg-gaming-gray-700 p-2 rounded mb-2 text-xs">
            ${this.renderObjectProperties(requirements)}
          </div>
        ` : ''}
        
        ${item.effects ? `
          <h4 class="text-xs font-semibold mb-1">Effects</h4>
          <p class="text-xs mb-2">${item.effects}</p>
        ` : ''}
        
        ${item.locations_found ? `
          <h4 class="text-xs font-semibold mb-1">Where to Find</h4>
          <p class="text-xs">${item.locations_found}</p>
        ` : ''}
      </div>
    `;
  }
  
  /**
   * Render NPC detail view
   * @param {Object} npc - NPC data
   * @returns {string} HTML for NPC detail
   */
  renderNpcDetail(npc) {
    return `
      <div class="npc-detail">
        <div class="mb-2 flex justify-between items-center">
          <span class="text-xs">${npc.role || 'Character'}</span>
          <span class="text-xs ${npc.is_hostile === 1 ? 'text-gaming-red-500' : 'text-gaming-green-500'}">${npc.is_hostile === 1 ? 'Hostile' : 'Friendly'}</span>
        </div>
        
        <p class="text-sm mb-3">${npc.description}</p>
        
        ${npc.faction ? `
          <p class="text-xs mb-2">
            <span class="text-gaming-gray-400">Faction:</span> ${npc.faction}
          </p>
        ` : ''}
        
        ${npc.default_location_id ? `
          <p class="text-xs mb-2">
            <span class="text-gaming-gray-400">Location:</span> ${npc.default_location_name || npc.default_location_id}
          </p>
        ` : ''}
        
        ${npc.dialogue_summary ? `
          <h4 class="text-xs font-semibold mb-1">Dialogue</h4>
          <p class="text-xs italic bg-gaming-gray-700 p-2 rounded mb-2">"${npc.dialogue_summary}"</p>
        ` : ''}
        
        ${npc.services && npc.is_merchant === 1 ? `
          <h4 class="text-xs font-semibold mb-1">Services</h4>
          <p class="text-xs mb-2">${npc.services}</p>
        ` : ''}
        
        ${npc.gives_quests ? `
          <h4 class="text-xs font-semibold mb-1">Related Quests</h4>
          <p class="text-xs">${npc.gives_quests}</p>
        ` : ''}
      </div>
    `;
  }
  
  /**
   * Render object properties as a list
   * @param {Object} obj - Object to render
   * @returns {string} HTML for object properties
   */
  renderObjectProperties(obj) {
    if (!obj) return '';
    
    return Object.entries(obj)
      .map(([key, value]) => {
        const formattedKey = key.replace(/_/g, ' ');
        return `<div class="flex justify-between">
          <span class="text-gaming-gray-400">${this.capitalizeFirstLetter(formattedKey)}</span>
          <span>${value}</span>
        </div>`;
      })
      .join('');
  }
  
  /**
   * Show a message in the content area
   * @param {string} message - Message to display
   */
  showMessage(message) {
    if (!this.contentArea) return;
    
    this.contentArea.innerHTML = `
      <div class="text-center py-4">
        <p class="text-sm">${message}</p>
      </div>
    `;
  }
  
  /**
   * Capitalize first letter of a string
   * @param {string} str - String to capitalize
   * @returns {string} Capitalized string
   */
  capitalizeFirstLetter(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
  }
}

/**
 * Debounce function to limit how often a function can be called
 * @param {Function} func - Function to debounce
 * @param {number} wait - Milliseconds to wait
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
  let timeout;
  return function(...args) {
    const context = this;
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(context, args), wait);
  };
}

// Initialize view manager
document.addEventListener('DOMContentLoaded', () => {
  const viewManager = new ViewManager();
  
  // Expose to global scope for other scripts
  window.viewManager = viewManager;
}); 