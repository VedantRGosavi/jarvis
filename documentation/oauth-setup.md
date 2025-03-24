# OAuth Provider Setup Guide

This guide provides step-by-step instructions for setting up each OAuth provider for FridayAI.

## Contents
1. [General Setup](#general-setup)
2. [Google OAuth Setup](#google-oauth-setup)
3. [GitHub OAuth Setup](#github-oauth-setup)
4. [PlayStation Network OAuth Setup](#playstation-network-oauth-setup)
5. [Steam OAuth Setup](#steam-oauth-setup)
6. [Testing OAuth Providers](#testing-oauth-providers)
7. [Troubleshooting](#troubleshooting)

## General Setup

1. Make a copy of the `.env.example` file to `.env` if you haven't already:
   ```
   cp .env.example .env
   ```

2. Set the correct frontend URL in your `.env` file:
   ```
   # For local development
   FRONTEND_URL=http://localhost:8000

   # For production
   # FRONTEND_URL=https://fridayai.me
   ```

3. Make sure your `JWT_SECRET` is set to a secure random string:
   ```
   JWT_SECRET=your_secure_random_string
   ```

## Google OAuth Setup

1. Go to the [Google Cloud Console](https://console.cloud.google.com/).

2. Create a new project (or select an existing one).

3. Navigate to "APIs & Services" > "Credentials".

4. Click "Create Credentials" and select "OAuth client ID".

5. If this is your first time creating OAuth credentials, you'll need to configure the OAuth consent screen:
   - Choose "External" (available to any user)
   - Enter your app name, user support email, and developer contact information
   - Add the scopes: `.../auth/userinfo.email`, `.../auth/userinfo.profile`, and `openid`
   - Add your domain to the authorized domains list

6. Return to the "Create OAuth client ID" form:
   - Application type: Web application
   - Name: FridayAI
   - Authorized JavaScript origins: Add your frontend URL (e.g., `http://localhost:8000` for development or `https://fridayai.me` for production)
   - Authorized redirect URIs: Add `${FRONTEND_URL}/api/auth/callback/google`

7. Click "Create" and note the generated Client ID and Client Secret.

8. Update your `.env` file with these credentials:
   ```
   GOOGLE_CLIENT_ID=your_client_id
   GOOGLE_CLIENT_SECRET=your_client_secret
   GOOGLE_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/google
   ```

## GitHub OAuth Setup

1. Go to your [GitHub Developer Settings](https://github.com/settings/developers).

2. Click "New OAuth App".

3. Fill in the form:
   - Application name: FridayAI
   - Homepage URL: Your frontend URL (e.g., `http://localhost:8000` for development or `https://fridayai.me` for production)
   - Application description (optional): Gaming Companion App
   - Authorization callback URL: `${FRONTEND_URL}/api/auth/callback/github`

4. Click "Register application".

5. On the next screen, note your Client ID and generate a new Client Secret.

6. Update your `.env` file with these credentials:
   ```
   GITHUB_CLIENT_ID=your_client_id
   GITHUB_CLIENT_SECRET=your_client_secret
   GITHUB_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/github
   ```

## PlayStation Network OAuth Setup

1. Go to the [PlayStation Network Partner Center](https://partners.api.playstation.com/).

2. Create a new account if you don't have one and log in.

3. Create a new application:
   - Select "Production" as the environment
   - Set the application name to FridayAI
   - Enter your company information
   - Select appropriate API access for your needs (Public API access)

4. Once approved, set up the OAuth configuration:
   - Description: FridayAI Gaming Companion
   - Redirect URI: `${FRONTEND_URL}/api/auth/callback/playstation`
   - Select the scopes: `psn:s2s`, `openid`, `user:account.profile.read`, `user:account.settings.privacy.read`

5. Submit the application for approval.

6. Once approved, note the Client ID and Client Secret from your application details.

7. Update your `.env` file with these credentials:
   ```
   PLAYSTATION_CLIENT_ID=your_client_id
   PLAYSTATION_CLIENT_SECRET=your_client_secret
   PLAYSTATION_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/playstation
   ```

## Steam OAuth Setup

1. Go to the [Steam API Key Registration](https://steamcommunity.com/dev/apikey) page.

2. Sign in with your Steam account.

3. Enter your domain name (e.g., `localhost` for development or `fridayai.me` for production).

4. Agree to the Steam API Terms of Use and click "Register".

5. Note the generated API Key.

6. Update your `.env` file:
   ```
   STEAM_CLIENT_ID=steam   # This is just a placeholder value for Steam
   STEAM_API_KEY=your_api_key
   STEAM_REDIRECT_URI=${FRONTEND_URL}/api/auth/callback/steam
   ```

## Testing OAuth Providers

FridayAI includes a testing tool for OAuth providers. You can access it at:

```
${FRONTEND_URL}/app/tools/oauth-test.php
```

This tool will:
1. Show the configuration status of each provider
2. Allow you to test authentication flows
3. Display detailed information about successful authentications

For testing purposes, you should set up these testing redirect URIs in each provider's developer console:

- Google: `${FRONTEND_URL}/app/tools/oauth-test.php?action=callback&provider=google`
- GitHub: `${FRONTEND_URL}/app/tools/oauth-test.php?action=callback&provider=github`
- PlayStation: `${FRONTEND_URL}/app/tools/oauth-test.php?action=callback&provider=playstation`
- Steam: `${FRONTEND_URL}/app/tools/oauth-test.php?action=callback&provider=steam`

## Troubleshooting

### Common Issues:

1. **Invalid redirect URI error**:
   - Double-check that the exact URI in your `.env` file matches what you've registered in the provider's developer console.
   - For local development, ensure you're using the exact redirect URI with the correct port.

2. **CSRF state validation error**:
   - This happens when the state parameter doesn't match between the request and callback.
   - Ensure cookies are enabled in your browser.
   - Try clearing browser cookies and session data.

3. **Authentication works in testing but not in the main app**:
   - Verify you've added both testing and production redirect URIs to the provider's allowed redirects.
   - Check PHP error logs for detailed error messages.

4. **Missing user profile information**:
   - Ensure you've requested the correct scopes for each provider.
   - Check the provider's documentation for any API changes.

For further assistance, check the PHP error logs and contact support.
