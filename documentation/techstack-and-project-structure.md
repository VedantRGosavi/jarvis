# Gaming Companion Overlay Tool
## Technical Stack and Project Structure

This document outlines the technical architecture, stack selections, and project structure for the Gaming Companion Overlay Tool, following Pieter Levels' minimalist approach to solo development.

### 1. Technical Stack Overview

The stack has been carefully selected to prioritize:
- Development speed and simplicity
- Solo maintainability
- Low operational complexity
- Proven reliability
- Cost-effectiveness

#### 1.1 Frontend Technologies

| Technology | Purpose | Justification |
|------------|---------|---------------|
| Vanilla JavaScript | Core overlay functionality and DOM manipulation | Eliminates framework overhead, reduces bundle size, and simplifies deployment |
| Tailwind CSS | UI styling and responsive design | Utility-first approach accelerates development with minimal CSS overhead |
| HTML5 | Base structure for overlay interface | Lightweight, standard-compliant markup for maximum compatibility |

#### 1.2 Backend Technologies

| Technology | Purpose | Justification |
|------------|---------|---------------|
| Pure PHP | Server-side logic and API endpoints | Mature, widely-supported language with simple deployment requirements |
| SQLite | Data storage for user accounts and game data | Zero-configuration database with simple file-based storage |
| MySQL (optional) | Scalable database option if needed | Easy migration path from SQLite if user base grows significantly |

#### 1.3 Infrastructure

| Technology | Purpose | Justification |
|------------|---------|---------------|
| DigitalOcean | VPS hosting | Affordable, reliable hosting with simple scaling options |
| NGINX | Web server | Lightweight, high-performance server for static files and PHP processing |
| Cloudflare | CDN, SSL, and security | Minimal setup for production-grade security and performance |

#### 1.4 Third-Party Services

| Service | Purpose | Justification |
|---------|---------|---------------|
| Stripe | Payment processing | Industry-standard payment solution with well-documented PHP integration |
| OpenAI API | Optional AI-generated content | Simple REST API integration for dynamic hint generation |
| Plausible Analytics | Usage tracking | Lightweight, privacy-focused analytics without complex implementation |

### 2. Project Structure - Planning

The project follows a monolithic architecture with a clear separation of concerns:

```
gaming-companion-overlay/
├── public/               # Publicly accessible files
│   ├── index.html        # Landing page and application entry point
│   ├── overlay.html      # Main overlay interface
│   ├── css/              # Compiled CSS files
│   │   └── tailwind.css  # Compiled Tailwind styles
│   ├── js/               # JavaScript files
│   │   ├── overlay.js    # Core overlay functionality
│   │   ├── interaction.js # User interaction handlers
│   │   └── data.js       # Data retrieval and management
│   └── assets/           # Images and static resources
│       ├── icons/        # UI icons
│       └── images/       # Marketing and UI images
├── app/                  # Server-side application code
│   ├── api/              # API endpoints
│   │   ├── auth.php      # Authentication endpoints
│   │   ├── data.php      # Game data retrieval
│   │   ├── payment.php   # Payment processing
│   │   └── user.php      # User management
│   ├── models/           # Data models
│   │   ├── User.php      # User account management
│   │   ├── GameData.php  # Game data handling
│   │   └── Subscription.php # Subscription management
│   ├── utils/            # Utility functions
│   │   ├── Database.php  # Database connection and queries
│   │   ├── Auth.php      # Authentication utilities
│   │   └── Config.php    # Configuration management
│   └── config/           # Configuration files
│       ├── database.php  # Database configuration
│       ├── stripe.php    # Stripe API configuration
│       └── openai.php    # OpenAI API configuration (optional)
├── data/                 # Data storage
│   ├── system.sqlite     # Main SQLite database for user accounts and settings
│   └── game_data/        # Game-specific databases and resources
│       ├── elden_ring.sqlite  # Elden Ring game database
│       └── baldurs_gate3.sqlite # Baldur's Gate 3 game database
├── scripts/              # Utility scripts
│   ├── deploy.sh         # Deployment script
│   ├── build.sh          # Build process for Tailwind
│   └── import.php        # Data import utilities
└── docs/                 # Documentation
    ├── architecture.md   # Architecture documentation
    ├── api.md            # API documentation
    └── setup.md          # Setup instructions
```

### 3. Component Breakdown

#### 3.1 Frontend Components

##### 3.1.1 Overlay System (`public/js/overlay.js`)
- **Purpose**: Manages the overlay interface within the game window
- **Key Functions**:
  - Window creation and management
  - Transparency and positioning control
  - Hotkey detection and handling
  - Interface rendering and updates

##### 3.1.2 User Interaction (`public/js/interaction.js`)
- **Purpose**: Handles user input and interface interactions
- **Key Functions**:
  - Click and drag operations
  - UI element interactions
  - Search functionality
  - Settings management

##### 3.1.3 Data Management (`public/js/data.js`)
- **Purpose**: Retrieves and manages game data for display
- **Key Functions**:
  - API communication with backend
  - Local storage of frequently used data
  - Data formatting for display
  - Search indexing and filtering

##### 3.1.4 UI Components (HTML/Tailwind CSS)
- **Purpose**: Define the visual structure and styling of the overlay
- **Key Elements**:
  - Responsive layout with Tailwind utility classes
  - Dark/light mode support
  - Minimal, clean interface with appropriate contrast
  - Game-specific styling elements

#### 3.2 Backend Components

##### 3.2.1 Authentication System (`app/api/auth.php`, `app/models/User.php`)
- **Purpose**: Manages user registration, login, and session management
- **Key Functions**:
  - User registration and login
  - Session creation and validation
  - Password hashing and security
  - Account management

##### 3.2.2 Data API (`app/api/data.php`, `app/models/GameData.php`)
- **Purpose**: Provides access to game data packs
- **Key Functions**:
  - Data retrieval by game, category, and query
  - User-specific data access control
  - Content formatting and delivery
  - Data update management

##### 3.2.3 Payment System (`app/api/payment.php`, `app/models/Subscription.php`)
- **Purpose**: Handles payment processing and subscription management
- **Key Functions**:
  - Stripe API integration
  - One-time purchase processing
  - Subscription creation and management
  - Trial handling and conversion

##### 3.2.4 Database Management (`app/utils/Database.php`)
- **Purpose**: Provides database connection and query functionality
- **Key Functions**:
  - SQLite connection management
  - Query execution and result handling
  - Data migration capabilities (if scaling to MySQL)
  - Basic ORM-like functionality

#### 3.3 Data Storage

##### 3.3.1 SQLite Databases
- **System Database** (`data/system.sqlite`):
  - `users`: User account information
  - `subscriptions`: Subscription status and details
  - `purchases`: One-time purchase records
  - `user_settings`: User preferences and settings
  - `user_game_progress`: Tracks user progress in games
  - `user_bookmarks`: Stores user-saved content
  - `usage_logs`: Activity tracking for analytics

##### 3.3.2 Game-Specific Databases (`data/game_data/`)
- **Purpose**: Store game-specific content in dedicated SQLite databases
- **Databases**:
  - `elden_ring.sqlite`: Elden Ring game data
  - `baldurs_gate3.sqlite`: Baldur's Gate 3 game data
- **Structure**:
  - Each game database has identical table schemas:
    - `quests`: Quest/mission information
    - `quest_steps`: Detailed quest progression steps
    - `locations`: Game areas and points of interest
    - `npcs`: Non-player characters
    - `items`: Game items and artifacts
    - `npc_locations`: Tracks NPC locations at different stages
    - `quest_prerequisites`: Tracks quest requirements
    - `quest_consequences`: Tracks effects of quest decisions
    - `search_index`: Optimized FTS5 table for text search

### 4. Integration Points

#### 4.1 Stripe Integration

- **Files**: `app/api/payment.php`, `app/config/stripe.php`
- **Purpose**: Process payments and manage subscriptions
- **Implementation**:
  - Direct PHP SDK integration for Stripe API
  - Webhook handling for subscription events
  - Secure customer portal for subscription management

#### 4.2 OpenAI Integration (Optional)

- **Files**: `app/api/data.php`, `app/config/openai.php`
- **Purpose**: Generate dynamic hints or additional content
- **Implementation**:
  - Simple REST API calls to OpenAI endpoints
  - Context management for relevant responses
  - Caching to reduce API costs

#### 4.3 Analytics Integration

- **Files**: `public/index.html`, `public/overlay.html`
- **Purpose**: Track usage patterns and feature engagement
- **Implementation**:
  - Lightweight Plausible Analytics script
  - Custom event tracking for key actions
  - Basic reporting via Plausible dashboard

### 5. Development Workflow

#### 5.1 Local Development

1. Local PHP development server (`php -S localhost:8000`)
2. SQLite database for development
3. Manual Tailwind compilation using `npx tailwindcss` command
4. Local testing via web browser and in-game overlays

#### 5.2 Version Control

- GitHub private repository
- Simple branch structure:
  - `main`: Production-ready code
  - `dev`: Current development work
  - Feature branches as needed

#### 5.3 Deployment

- **Script**: `scripts/deploy.sh`
- **Process**:
  1. Build Tailwind CSS for production
  2. SSH into DigitalOcean VPS
  3. Pull latest changes from GitHub
  4. Update file permissions as needed
  5. Restart NGINX if necessary

### 6. Performance Considerations

#### 6.1 Overlay Performance

- Minimal DOM operations to prevent layout thrashing
- Optimized JavaScript with no heavy dependencies
- Efficient CSS via Tailwind's utility-first approach
- Asynchronous data loading to prevent UI blocking

#### 6.2 Backend Performance

- Simple PHP execution model for predictable performance
- SQLite's file-based approach for low resource consumption
- Cloudflare caching for static resources
- Minimal third-party dependencies

### 7. Security Considerations

#### 7.1 User Authentication

- Secure password hashing using PHP's password_hash
- HTTPS-only communication via Cloudflare SSL
- Rate limiting for login attempts
- Secure session management

#### 7.2 Payment Security

- No storage of sensitive payment information
- Reliance on Stripe's secure infrastructure
- PCI compliance through Stripe Elements

#### 7.3 Data Protection

- Minimal personal data collection
- Encrypted database connections
- Regular security updates
- WAF protection via Cloudflare

### 8. Scaling Considerations

The initial architecture prioritizes simplicity and development speed. However, the design allows for incremental scaling:

1. **Database Scaling**: Migration path from SQLite to MySQL if user volume grows
2. **Server Scaling**: Easy vertical scaling on DigitalOcean with droplet resizing
3. **Content Delivery**: Cloudflare already provides global distribution
4. **Modular Design**: Components can be separated into microservices if needed

### 9. Conclusion

This technical stack and project structure is designed to enable rapid development and deployment by a solo developer, following Pieter Levels' minimalist approach. By focusing on proven, simple technologies and a clear project structure, we can achieve:

- Fast development cycles (4-6 weeks to launch)
- Low operational complexity
- Sustainable solo maintenance
- Cost-effective infrastructure
- Iterative improvement capabilities

The architecture prioritizes getting to market quickly with a functional product that can be enhanced and expanded based on user feedback and market validation.
