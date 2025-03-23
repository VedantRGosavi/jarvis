/**
 * Game Data Service
 * Handles fetching game data from the API endpoints
 */

class GameDataService {
  constructor() {
    this.baseUrl = '/api';
    this.games = {
      ELDEN_RING: 'elden_ring',
      BALDURS_GATE_3: 'baldurs_gate3'
    };
    
    // Cache storage for frequently accessed data
    this.cache = {
      locations: {},
      items: {},
      npcs: {},
      quests: {}
    };
  }
  
  /**
   * Get game data by type
   * @param {string} game - Game identifier
   * @param {string} type - Data type (locations, items, npcs, quests)
   * @param {Object} params - Query parameters
   * @returns {Promise<Array>} Array of game data items
   */
  async getGameData(game, type, params = {}) {
    try {
      const queryParams = new URLSearchParams(params).toString();
      const queryString = queryParams ? `?${queryParams}` : '';
      
      // Check cache first
      const cacheKey = `${game}_${type}_${queryString}`;
      if (this.cache[type][cacheKey]) {
        return this.cache[type][cacheKey];
      }
      
      const response = await fetch(`${this.baseUrl}/games/${game}/${type}${queryString}`);
      
      if (!response.ok) {
        throw new Error(`Failed to fetch ${type} for ${game}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      // Cache the result
      this.cache[type][cacheKey] = data;
      
      return data;
    } catch (error) {
      console.error(`Error fetching ${type} for ${game}:`, error);
      return [];
    }
  }
  
  /**
   * Get locations for a game
   * @param {string} game - Game identifier
   * @param {Object} params - Query parameters
   * @returns {Promise<Array>} Array of locations
   */
  async getLocations(game, params = {}) {
    return this.getGameData(game, 'locations', params);
  }
  
  /**
   * Get items for a game
   * @param {string} game - Game identifier
   * @param {Object} params - Query parameters
   * @returns {Promise<Array>} Array of items
   */
  async getItems(game, params = {}) {
    return this.getGameData(game, 'items', params);
  }
  
  /**
   * Get NPCs for a game
   * @param {string} game - Game identifier
   * @param {Object} params - Query parameters
   * @returns {Promise<Array>} Array of NPCs
   */
  async getNpcs(game, params = {}) {
    return this.getGameData(game, 'npcs', params);
  }
  
  /**
   * Get quests for a game
   * @param {string} game - Game identifier
   * @param {Object} params - Query parameters
   * @returns {Promise<Array>} Array of quests
   */
  async getQuests(game, params = {}) {
    return this.getGameData(game, 'quests', params);
  }
  
  /**
   * Get detailed quest data including steps
   * @param {string} game - Game identifier
   * @param {string} questId - Quest identifier
   * @returns {Promise<Object>} Detailed quest data
   */
  async getQuestDetails(game, questId) {
    try {
      const cacheKey = `${game}_quest_detail_${questId}`;
      if (this.cache.quests[cacheKey]) {
        return this.cache.quests[cacheKey];
      }
      
      const response = await fetch(`${this.baseUrl}/games/${game}/quests/${questId}`);
      
      if (!response.ok) {
        throw new Error(`Failed to fetch quest details: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      // Cache the result
      this.cache.quests[cacheKey] = data;
      
      return data;
    } catch (error) {
      console.error('Error fetching quest details:', error);
      return null;
    }
  }
  
  /**
   * Search across all game data
   * @param {string} game - Game identifier
   * @param {string} query - Search query
   * @param {string} contentType - Optional content type filter
   * @returns {Promise<Array>} Array of search results
   */
  async search(game, query, contentType = null) {
    try {
      const params = new URLSearchParams({ q: query });
      
      if (contentType) {
        params.append('type', contentType);
      }
      
      const response = await fetch(`${this.baseUrl}/games/${game}/search?${params.toString()}`);
      
      if (!response.ok) {
        throw new Error(`Search failed: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('Error during search:', error);
      return [];
    }
  }
  
  /**
   * Clear cache for a specific type
   * @param {string} type - Data type to clear cache for
   */
  clearCache(type) {
    if (type === 'all') {
      this.cache = {
        locations: {},
        items: {},
        npcs: {},
        quests: {}
      };
    } else if (this.cache[type]) {
      this.cache[type] = {};
    }
  }
}

// Initialize data service
document.addEventListener('DOMContentLoaded', () => {
  const gameDataService = new GameDataService();
  
  // Expose to global scope for other scripts
  window.gameDataService = gameDataService;
}); 