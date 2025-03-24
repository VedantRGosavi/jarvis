/*
 * IMPORTANT: If any changes are made to this frontend architecture,
 * please ensure that corresponding updates are applied throughout
 * the entire codebase to maintain consistency and prevent integration issues.
 */

# FridayAI Gaming Companion Overlay Tool - Frontend Architecture

## Design Philosophy

The frontend implements a minimalist black and white color scheme with a modern, clean aesthetic. This approach ensures:

1. Optimal contrast and readability during gameplay
2. Reduced visual distraction from the game environment
3. Consistent styling across all supported games
4. Minimal resource consumption for overlay rendering

## Technology Stack

The frontend uses a lightweight, framework-free approach:

- **Vanilla JavaScript**: Core functionality without framework overhead
- **Tailwind CSS**: Utility-first styling for rapid development
- **HTML5**: Semantic markup for the application structure

## Color Palette

```css
:root {
  /* Primary Colors */
  --color-black: #121212;
  --color-white: #f8f8f8;

  /* Gray Scale */
  --color-gray-100: #e5e5e5;
  --color-gray-200: #c7c7c7;
  --color-gray-300: #a3a3a3;
  --color-gray-400: #737373;
  --color-gray-500: #525252;
  --color-gray-600: #404040;
  --color-gray-700: #282828;
  --color-gray-800: #1c1c1c;

  /* Accent Colors (Used sparingly) */
  --color-accent: #404040;
  --color-success: #1f8c3b;
  --color-warning: #bb7d00;
  --color-error: #c11c1c;
}
```

## File Structure

```
public/
├── index.html                  # Landing page
├── app.html                    # Application container
├── overlay.html               # Game overlay interface
├── css/
│   ├── tailwind.css           # Compiled Tailwind styles
│   └── custom.css             # Custom styles beyond Tailwind
├── js/
│   ├── core/
│   │   ├── overlay.js         # Overlay positioning and management
│   │   ├── hotkeys.js         # Hotkey detection and handling
│   │   └── windows.js         # Window capture and management
│   ├── ui/
│   │   ├── components.js      # Reusable UI components
│   │   ├── navigation.js      # Navigation and menu management
│   │   └── search.js          # Search functionality
│   ├── data/
│   │   ├── api.js             # API communication
│   │   ├── cache.js           # Local data caching
│   │   └── gameData.js        # Game-specific data handling
│   ├── games/
│   │   ├── eldenRing.js       # Elden Ring specific functionality
│   │   └── baldursGate.js     # Baldur's Gate 3 specific functionality
│   └── app.js                 # Main application initialization
└── assets/
    ├── icons/                 # UI icons (SVG for performance)
    └── images/                # Marketing and UI images
```

## Core Components

### 1. Overlay System

The overlay system provides a transparent interface that can be activated during gameplay.

#### Key Features:
- Draggable and resizable interface
- Configurable opacity levels (25%-100%)
- Always-on-top functionality
- Platform-specific hotkey activation:
  - Windows: Ctrl+Shift+J
  - macOS: Cmd+Shift+J
- Position memory between sessions

#### Implementation:
```javascript
// overlay.js example implementation
export class GameOverlay {
  constructor(config = {}) {
    this.visible = false;
    this.opacity = config.opacity || 0.85;
    this.position = config.position || { x: 10, y: 10 };
    this.size = config.size || { width: 320, height: 480 };
    this.initializeOverlay();
  }

  initializeOverlay() {
    // Create overlay container
    this.container = document.createElement('div');
    this.container.id = 'game-companion-overlay';
    this.container.style.position = 'fixed';
    this.container.style.zIndex = '9999';
    this.container.style.backgroundColor = `rgba(18, 18, 18, ${this.opacity})`;
    this.container.style.color = 'var(--color-white)';
    this.container.style.fontFamily = 'Inter, sans-serif';
    this.container.style.borderRadius = '4px';
    this.container.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    this.container.style.overflow = 'hidden';

    // Position and size
    this.updatePosition(this.position);
    this.updateSize(this.size);

    // Initial visibility
    this.setVisibility(this.visible);

    // Add to document
    document.body.appendChild(this.container);

    // Initialize draggable behavior
    this.initializeDraggable();
  }

  // Additional methods for overlay manipulation
  // ...
}
```

### 2. User Interface

The UI follows these design principles:

- Minimal, flat design with high contrast
- Clear typography hierarchy
- Icon-based navigation for space efficiency
- Collapsible sections for information density
- Semi-transparent backgrounds to preserve game visibility

#### Typography:
```css
/* Typography system */
body {
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  line-height: 1.5;
  color: var(--color-white);
}

h1 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
```

### 3. Game-Specific Modules

Game-specific modules contain tailored functionality for each supported game.

#### Elden Ring Module:
```javascript
// eldenRing.js
import { GameModule } from '../core/gameModule.js';

export class EldenRingModule extends GameModule {
  constructor() {
    super('elden-ring');
    this.regions = [
      'Limgrave', 'Weeping Peninsula', 'Liurnia',
      'Caelid', 'Altus Plateau', 'Mt. Gelmir',
      'Mountaintops of the Giants', 'Consecrated Snowfield'
    ];
  }

  async loadQuestData(questId) {
    const data = await this.fetchData(`quests/${questId}`);
    return this.formatQuestData(data);
  }

  formatQuestData(data) {
    // Elden Ring-specific formatting
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

  // Additional Elden Ring-specific methods
  // ...
}
```

#### Baldur's Gate 3 Module:
```javascript
// baldursGate.js
import { GameModule } from '../core/gameModule.js';

export class BaldursGate3Module extends GameModule {
  constructor() {
    super('baldurs-gate-3');
    this.acts = ['Act 1', 'Act 2', 'Act 3'];
    this.companions = [
      'Shadowheart', 'Astarion', 'Gale',
      'Lae\'zel', 'Wyll', 'Karlach'
    ];
  }

  async loadQuestData(questId) {
    const data = await this.fetchData(`quests/${questId}`);
    return this.formatQuestData(data);
  }

  formatQuestData(data) {
    // Baldur's Gate 3-specific formatting
    return {
      title: data.name,
      act: data.act,
      description: data.description,
      steps: data.progression.map(step => ({
        objective: step.objective,
        location: step.area,
        dialogueOptions: step.dialogues || [],
        consequences: step.consequences || [],
        rewards: step.rewards || []
      }))
    };
  }

  // Additional Baldur's Gate 3-specific methods
  // ...
}
```

## Data Handling

### 1. API Communication

```javascript
// api.js
export class ApiClient {
  constructor(baseUrl) {
    this.baseUrl = baseUrl || '/api';
    this.authToken = localStorage.getItem('auth_token');
  }

  async get(endpoint) {
    try {
      const response = await fetch(`${this.baseUrl}/${endpoint}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': this.authToken ? `Bearer ${this.authToken}` : ''
        }
      });

      if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  // Additional methods (post, put, etc.)
  // ...
}
```

### 2. Local Caching

```javascript
// cache.js
export class DataCache {
  constructor(ttl = 3600000) { // Default TTL: 1 hour
    this.cache = {};
    this.ttl = ttl;
  }

  set(key, data) {
    this.cache[key] = {
      data,
      timestamp: Date.now()
    };

    // Also persist to localStorage for session persistence
    try {
      localStorage.setItem(`cache_${key}`, JSON.stringify({
        data,
        timestamp: Date.now()
      }));
    } catch (e) {
      console.warn('localStorage caching failed:', e);
    }
  }

  get(key) {
    // Check memory cache first
    const item = this.cache[key];

    if (item && Date.now() - item.timestamp < this.ttl) {
      return item.data;
    }

    // Try localStorage as fallback
    try {
      const storedItem = JSON.parse(localStorage.getItem(`cache_${key}`));

      if (storedItem && Date.now() - storedItem.timestamp < this.ttl) {
        // Refresh memory cache
        this.cache[key] = storedItem;
        return storedItem.data;
      }
    } catch (e) {
      console.warn('localStorage retrieval failed:', e);
    }

    return null;
  }

  // Additional cache methods
  // ...
}
```

## Responsive Design

The UI adapts to different window sizes and game resolutions:

- Default compact mode for gameplay
- Expanded mode for detailed information
- Collapsible panels to minimize screen space
- Draggable positioning to avoid covering critical game elements

## Performance Considerations

1. **Minimal DOM Operations**: Updates are batched to prevent layout thrashing
2. **SVG Icons**: Lightweight vector graphics for UI elements
3. **Lazy Loading**: Game data is loaded on demand
4. **Event Delegation**: Efficient event handling for UI interactivity
5. **Throttled Resize/Drag**: Performance-optimized window manipulation

## Implementation Priorities

1. **Core Overlay System**: Window capture and positioning
2. **Basic UI Components**: Navigation, search, content display
3. **Elden Ring Module**: Initial game integration
4. **Baldur's Gate 3 Module**: Second game integration
5. **Settings and Customization**: User preference management
6. **Search and Filtering**: Advanced content discovery

## Testing Requirements

The frontend requires testing across:

1. Different game window modes (windowed, borderless fullscreen)
2. Various screen resolutions
3. High DPI displays
4. Different performance-tier systems
5. Integration with each supported game

## Accessibility Considerations

1. **Keyboard Navigation**: Full functionality without mouse dependency
2. **High Contrast Mode**: Enhanced readability option
3. **Scalable UI**: Text size adjustment
4. **Screen Reader Support**: Semantic HTML and ARIA attributes
