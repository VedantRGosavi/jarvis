# FridayAI Developer Tools

This directory contains tools to assist in development and testing of FridayAI.

## OAuth Test Tool

The OAuth test tool (`oauth-test.php`) helps you verify that your OAuth provider configurations are working correctly.

### Features

- Status check for all configured OAuth providers
- Test authentication flow for each provider
- Detailed display of user information and tokens after successful authentication
- Error reporting for failed authentication attempts

### How to Use

1. Ensure your environment variables are configured in `.env` with the appropriate OAuth credentials
2. Access the tool at:
   ```
   http://localhost:8000/app/tools/oauth-test.php
   ```
   (replace with your actual base URL)

3. The tool interface shows:
   - Status indicators for each provider (green if configured, red if missing credentials)
   - "Test" buttons to initiate the OAuth flow for each provider
   - Detailed results for callback processing

### Testing OAuth Flow

1. Click the "Test" button for the provider you want to test
2. You'll be redirected to the provider's authorization page
3. After authorizing, you'll be redirected back to the tool with detailed results
4. The results include:
   - Access token (partially masked for security)
   - User information retrieved from the provider
   - Raw response data for debugging

### Important Notes

- This tool only works in development mode (checks for `APP_ENV=development`)
- You should register specific test callback URLs in each provider's developer console:
  ```
  http://localhost:8000/app/tools/oauth-test.php?action=callback&provider=[provider_name]
  ```
- When testing is complete, remember to remove test redirect URIs from production applications

For complete OAuth setup instructions, see the main documentation at `documentation/oauth-setup.md`.
