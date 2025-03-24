/**
 * Baldur's Gate 3 Module
 * Game-specific functionality for Baldur's Gate 3
 */

class BaldursGate3Module {
  constructor() {
    this.gameId = 'baldurs_gate3';
    this.gameName = "Baldur's Gate 3";
    this.regions = [
      'Act 1 - The Wilderness',
      'Act 1 - The Underdark',
      'Act 2 - Shadow-Cursed Lands',
      'Act 3 - Baldur\'s Gate'
    ];
    this.contentTypes = {
      quests: 'Quests',
      npcs: 'NPCs',
      items: 'Items',
      locations: 'Locations',
      classes: 'Classes',
      spells: 'Spells'
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
      'illithid_tadpole': {
        id: 'illithid_tadpole',
        name: "The Illithid Tadpole",
        description: "Find a way to remove the Mind Flayer parasite from your brain before it transforms you into a Mind Flayer.",
        progression: [
          {
            step: 1,
            instruction: "Escape the Nautiloid",
            area: "Nautiloid Ship",
            npc: "None",
            rewards: []
          },
          {
            step: 2,
            instruction: "Find other survivors on the beach",
            area: "Ravaged Beach",
            npc: "None",
            rewards: []
          },
          {
            step: 3,
            instruction: "Find someone who can help with the parasite",
            area: "Act 1",
            npc: "Various",
            rewards: []
          }
        ]
      },
      'find_halsin': {
        id: 'find_halsin',
        name: "Find the Missing Archdruid",
        description: "Locate Halsin, the missing leader of the Emerald Grove druids.",
        progression: [
          {
            step: 1,
            instruction: "Talk to Rath about Halsin",
            area: "Druid Grove",
            npc: "Rath",
            rewards: []
          },
          {
            step: 2,
            instruction: "Infiltrate the Goblin Camp",
            area: "Goblin Camp",
            npc: "None",
            rewards: []
          },
          {
            step: 3,
            instruction: "Find and free Halsin",
            area: "Goblin Camp - Prison",
            npc: "Halsin",
            rewards: ["Halsin as a Camp Companion"]
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

  // Additional methods for Baldur's Gate 3-specific functionality
  searchContent(query, type = null) {
    // Search implementation
    // For demo purposes, just return mock results
    const results = [
      {
        id: 'illithid_tadpole',
        title: "The Illithid Tadpole",
        type: 'quest',
        description: "Find a way to remove the Mind Flayer parasite from your brain."
      },
      {
        id: 'find_halsin',
        title: "Find the Missing Archdruid",
        type: 'quest',
        description: "Locate Halsin, the missing leader of the Emerald Grove druids."
      },
      {
        id: 'shadowheart',
        title: "Shadowheart",
        type: 'npc',
        description: "A reserved cleric of Shar with a forgotten past and a mysterious artifact."
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

// Initialize Baldur's Gate 3 module
document.addEventListener('DOMContentLoaded', () => {
  const baldursGate3Module = new BaldursGate3Module();

  // Expose to global scope for other scripts
  window.baldursGate3Module = baldursGate3Module;
});
