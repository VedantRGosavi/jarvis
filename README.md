# FridayAI

FridayAI is an interactive gaming assistant that provides real-time information, quest tracking, and game guides for popular games.

## Features

- Real-time overlay with game information
- Quest tracking and progress monitoring
- Interactive maps and location guides
- Item database with search functionality
- User account and subscription management
- Multi-platform support (Windows, macOS, Linux)

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js and npm
- SQLite3

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/fridayai.git
   cd fridayai
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies:
   ```bash
   npm install
   ```

4. Copy the environment configuration:
   ```bash
   cp .env.example .env
   ```

5. Edit the `.env` file with your configuration settings.

6. Setup the databases:
   ```bash
   cd database
   bash ./setup_databases.sh
   ```

7. Build the frontend assets:
   ```bash
   npm run build
   ```

8. Start the development server:
   ```bash
   php -S localhost:8000 -t public
   ```

## Production Deployment

### Heroku Deployment

We use Heroku for hosting the application with the following setup:

1. Automatic deployment from the main branch
2. PostgreSQL for the production database
3. S3 for storing application assets and files

To deploy to production, use the deployment script:

```bash
./deploy.sh
```

This script will:
- Verify database structures
- Install production dependencies
- Create/configure Heroku app if needed
- Configure environment variables
- Deploy the application

### Manual Deployment

If you need to manually deploy:

1. Create a Heroku app:
   ```bash
   heroku create fridayai-prod
   ```

2. Add PostgreSQL:
   ```bash
   heroku addons:create heroku-postgresql:hobby-dev
   ```

3. Configure environment variables:
   ```bash
   heroku config:set APP_ENV=production
   heroku config:set APP_DEBUG=false
   heroku config:set S3_BUCKET=fridayai-downloads-2025
   heroku config:set S3_REGION=us-east-1
   ```

   Add all necessary secrets and API keys.

4. Deploy the application:
   ```bash
   git push heroku main
   ```

## Monitoring

Monitor the application using Heroku logs:
```bash
heroku logs --tail
```

## Database

The application uses SQLite for development and PostgreSQL for production.

- `system.sqlite`: Main application database
- Game-specific databases in `data/game_data/`

## License

This project is proprietary software. All rights reserved.
