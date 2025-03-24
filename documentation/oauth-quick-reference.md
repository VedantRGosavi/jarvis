# OAuth Implementation Quick Reference

## Overview
FridayAI supports multiple OAuth providers to give users flexible authentication options:

- Google OAuth 2.0
- GitHub OAuth
- PlayStation Network OAuth
- Steam Authentication

## Implementation Structure

```
app/utils/
├── OAuthProvider.php           # Base abstract class for all providers
├── GoogleOAuth.php             # Google implementation 
├── GithubOAuth.php             # GitHub implementation
├── PlayStationOAuth.php        # PlayStation Network implementation
├── SteamOAuth.php              # Steam implementation (OpenID + API)
```

## Configuration

All OAuth providers are configured through environment variables in the `.env` file:

```
# Required for all OAuth providers
FRONTEND_URL=http://localhost:3000

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=${FRONTEND_URL}/api/auth/google/callback

# GitHub OAuth
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_REDIRECT_URI=${FRONTEND_URL}/api/auth/github/callback

# PlayStation Network OAuth
PLAYSTATION_CLIENT_ID=your_playstation_client_id
PLAYSTATION_CLIENT_SECRET=your_playstation_client_secret
PLAYSTATION_REDIRECT_URI=${FRONTEND_URL}/api/auth/playstation/callback

# Steam Authentication (uses OpenID + API Key)
STEAM_API_KEY=your_steam_api_key
STEAM_REDIRECT_URI=${FRONTEND_URL}/api/auth/steam/callback
```

## Common OAuth Flow

1. **Authorization Request**:
   ```php
   $authUrl = $provider->getAuthorizationUrl();
   // Redirect user to $authUrl
   ```

2. **Callback Processing**:
   ```php
   $userInfo = $provider->handleCallback($_GET);
   // User is now authenticated, $userInfo contains user details
   ```

## Testing Tools

1. **Command Line Check**:
   ```bash
   ./scripts/test-oauth.sh check
   ```

2. **Interactive Test Tool**:
   ```bash
   ./scripts/test-oauth.sh start
   ```
   Then visit: `http://localhost:8000/app/tools/oauth-test.php`

## Provider-Specific Notes

### Google OAuth
- Requires OAuth consent screen setup
- Provides email, profile name, and profile picture

### GitHub OAuth
- Requires registering an OAuth application
- Provides username, email, and profile URL

### PlayStation Network OAuth
- Requires PlayStation Partner Center access
- Provides PSN ID and basic profile info

### Steam Authentication
- Uses OpenID for authentication + Steam API for user info
- Provides Steam ID, username, and avatar

## Security Features

- **CSRF Protection**: All OAuth implementations use a state parameter to prevent CSRF attacks
- **JWT Tokens**: After successful authentication, a JWT token is generated for the user
- **Validation**: All user information is validated before storing or returning

For detailed setup instructions, see [OAuth Provider Setup Guide](./oauth-setup.md). 