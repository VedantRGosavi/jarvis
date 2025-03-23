/**
 * Elden Ring Module
 * Game-specific functionality for Elden Ring
 */

class EldenRingModule {
  constructor() {
    this.gameId = 'elden-ring';
    this.gameName = 'Elden Ring';
    this.regions = [
      'Limgrave', 'Weeping Peninsula', 'Liurnia', 
      'Caelid', 'Altus Plateau', 'Mt. Gelmir', 
      'Mountaintops of the Giants', 'Consecrated Snowfield'
    ];
    this.contentTypes = {
      quests: 'Quests',
      bosses: 'Bosses',
      items: 'Items',
      locations: 'Locations',
      weapons: 'Weapons',
      armor: 'Armor'
    };
    
    this.initialize();
  }
  
  initialize() {
    // Initialize the module
    console.log(`Initializing ${this.gameName} module`);
    
    // Register with overlay (if it exists)
    if (window.gameOverlay) {
      this.registerWithOverlay();
    } else {
      // Wait for overlay to load
      document.addEventListener('overlayReady', () => {
        this.registerWithOverlay();
      });
    }
  }
  
  registerWithOverlay() {
    // Add game-specific elements to the overlay
    console.log(`Registering ${this.gameName} with overlay`);
  }
  
  async loadQuestData(questId) {
    try {
      // In a real implementation, this would fetch from an API
      // For demo purposes, return mock data
      return this.getMockQuestData(questId);
    } catch (error) {
      console.error('Error loading quest data:', error);
      return null;
    }
  }
  
  getMockQuestData(questId) {
    // Mock data for demonstration
    const mockQuests = {
      'ranni': {
        id: 'ranni',
        name: "Ranni's Questline",
        description: "A complex questline involving the mysterious witch Ranni.",
        progression: [
          {
            step: 1,
            instruction: "Meet Ranni at Ranni's Rise",
            area: "Three Sisters, Liurnia",
            npc: "Ranni the Witch",
            rewards: ["Ranni's Dark Moon"]
          },
          {
            step: 2,
            instruction: "Speak to Blaidd in Siofra River",
            area: "Siofra River",
            npc: "Blaidd",
            rewards: []
          },
          {
            step: 3,
            instruction: "Defeat Starscourge Radahn",
            area: "Caelid",
            npc: "None",
            rewards: ["Remembrance of the Starscourge"]
          }
        ]
      },
      'millicent': {
        id: 'millicent',
        name: "Millicent's Questline",
        description: "Help Millicent on her journey through the Lands Between.",
        progression: [
          {
            step: 1,
            instruction: "Find Millicent in Caelid",
            area: "Caelid",
            npc: "Millicent",
            rewards: []
          },
          {
            step: 2,
            instruction: "Get the Golden Needle from Commander O'Neil",
            area: "Swamp of Aeonia, Caelid",
            npc: "Commander O'Neil",
            rewards: ["Golden Needle"]
          }
        ]
      }
    };
    
    return mockQuests[questId] || null;
  }
  
  formatQuestData(data) {
    // Format quest data for display
    return {
      title: data.name,
      description: data.description,
      steps: data.progression.map(step => ({
        instruction: step.instruction,
        location: step.area,
        npc: step.npc || 'None',
        rewards: step.rewards || []
      }))
    };
  }
  
  renderQuestDetails(questData) {
    if (!questData) return '<p class="text-gaming-gray-400">Quest not found</p>';
    
    const formattedData = this.formatQuestData(questData);
    
    return `
      <div class="quest-details">
        <h3 class="text-lg font-semibold mb-2">${formattedData.title}</h3>
        <p class="text-sm text-gaming-gray-300 mb-4">${formattedData.description}</p>
        
        <h4 class="text-sm font-semibold mb-2">Quest Steps:</h4>
        <div class="space-y-3">
          ${formattedData.steps.map((step, index) => `
            <div class="bg-gaming-gray-700 p-2 rounded">
              <div class="flex justify-between">
                <span class="font-medium">Step ${index + 1}</span>
                <span class="text-xs text-gaming-gray-400">${step.location}</span>
              </div>
              <p class="text-sm mt-1">${step.instruction}</p>
              ${step.npc !== 'None' ? `<p class="text-xs text-gaming-gray-400 mt-1">NPC: ${step.npc}</p>` : ''}
              ${step.rewards.length > 0 ? `
                <div class="mt-2">
                  <span class="text-xs text-gaming-gray-400">Rewards:</span>
                  <ul class="text-xs pl-4">
                    ${step.rewards.map(reward => `<li>${reward}</li>`).join('')}
                  </ul>
                </div>
              ` : ''}
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }
  
  // Additional methods for Elden Ring-specific functionality
  searchContent(query, type = null) {
    // Search implementation
    // For demo purposes, just return mock results
    const results = [
      {
        id: 'ranni',
        title: "Ranni's Questline",
        type: 'quest',
        description: "A complex questline involving the mysterious witch Ranni."
      },
      {
        id: 'millicent',
        title: "Millicent's Questline",
        type: 'quest',
        description: "Help Millicent on her journey through the Lands Between."
      },
      {
        id: 'malenia',
        title: "Malenia, Blade of Miquella",
        type: 'boss',
        description: "Optional endgame boss located in the Haligtree."
      }
    ];
    
    // Filter by type if specified
    const filtered = type ? results.filter(item => item.type === type) : results;
    
    // Filter by query text
    if (query && query.trim() !== '') {
      const lowerQuery = query.toLowerCase();
      return filtered.filter(item => 
        item.title.toLowerCase().includes(lowerQuery) || 
        item.description.toLowerCase().includes(lowerQuery)
      );
    }
    
    return filtered;
  }
}

// Initialize Elden Ring module
document.addEventListener('DOMContentLoaded', () => {
  const eldenRingModule = new EldenRingModule();
  
  // Expose to global scope for other scripts
  window.eldenRingModule = eldenRingModule;
}); 