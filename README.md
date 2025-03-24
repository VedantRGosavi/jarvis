# FridayAI Platform

FridayAI is a powerful AI gaming platform that helps gamers optimize their performance and track their progress.

## Download Functionality

The platform provides download options for multiple operating systems and versions:

- **Windows** (.zip)
- **macOS** (.dmg)
- **Linux** (.tar.gz)

Each platform has both latest and beta versions available.

### Download Access Control

Downloads are protected by authentication and subscription status:
- Users must have a valid authentication token to access downloads
- Downloads are available to users with the following subscription statuses:
  - Trial
  - Active (premium)
  - Admin

### Download Analytics

The platform tracks download statistics for analysis, including:
- User ID
- Platform
- Version
- Download date/time
- IP address

## Admin Dashboard

An admin dashboard is available for administrators to track download statistics:

### Features

- **Download Analytics**
  - Total downloads counter
  - Platform-specific download counts (Windows, macOS, Linux)
  - Platform distribution chart
  - Download trend timeline chart
  - Detailed download records with filtering and pagination

### Access Control

- The admin dashboard is only accessible to users with admin subscription status
- Authentication checks are performed for all admin API endpoints
- Admin-specific navigation links are only visible to admin users

## API Endpoints

### Download API

- `GET /api/download/[version]?platform=[platform]`
  - Requires authentication
  - Checks subscription status
  - Logs download details
  - Serves the appropriate download file

### Admin Analytics API

- `GET /api/admin/analytics.php?action=summary`
  - Returns summary statistics for the dashboard

- `GET /api/admin/analytics.php?action=records`
  - Returns detailed download records with pagination
  - Supports filtering by platform, version, and date range

## Development Setup

1. Clone the repository
2. Ensure the `downloads` directory contains the necessary files
3. Start the PHP server with: `php -S localhost:8000`
4. Access the application at http://localhost:8000

## Technology Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Charts: Chart.js
- UI Framework: Custom CSS with responsive design
