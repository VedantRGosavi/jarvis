/**
 * Simplified AuthService for testing OAuth URLs
 */
class TestAuthService {
  constructor() {
    console.log("Test Auth Service initialized");
  }

  /**
   * Get OAuth authorization URL
   * @param {string} provider - OAuth provider (google, github, etc.)
   * @returns {Promise<Object>} Authorization URL
   */
  async getOAuthUrl(provider) {
    try {
      console.log(`Creating test OAuth URL for ${provider}`);

      const mockAuthUrls = {
        'google': `https://accounts.google.com/o/oauth2/auth?client_id=mock-client-id&redirect_uri=http://localhost:8000/auth-callback.html&response_type=code&scope=email%20profile&state=${provider}`,
        'github': `https://github.com/login/oauth/authorize?client_id=mock-client-id&redirect_uri=http://localhost:8000/auth-callback.html&scope=user:email&state=${provider}`,
        'playstation': `https://auth.api.sonyentertainmentnetwork.com/2.0/oauth/authorize?client_id=mock-client-id&redirect_uri=http://localhost:8000/auth-callback.html&response_type=code&scope=psn:s2s&state=${provider}`,
        'steam': `https://steamcommunity.com/openid/login?openid.ns=http://specs.openid.net/auth/2.0&openid.mode=checkid_setup&openid.return_to=http://localhost:8000/auth-callback.html&openid.realm=http://localhost:8000&state=${provider}`
      };

      // For testing, we're using a direct mockable URL to avoid actual OAuth provider connections
      const testMockUrls = {
        'google': `http://localhost:8000/auth-callback.html?code=mock_code_google&state=${provider}`,
        'github': `http://localhost:8000/auth-callback.html?code=mock_code_github&state=${provider}`,
        'playstation': `http://localhost:8000/auth-callback.html?code=mock_code_playstation&state=${provider}`,
        'steam': `http://localhost:8000/auth-callback.html?openid.identity=mock_identity&state=${provider}`
      };

      // Return test mock URL for easy testing
      return {
        success: true,
        auth_url: testMockUrls[provider] || mockAuthUrls[provider] || `https://example.com/oauth/${provider}?mock=true&redirect_uri=http://localhost:8000/auth-callback.html&state=${provider}`
      };
    } catch (error) {
      console.error(`OAuth URL error for ${provider}:`, error);
      return {
        success: false,
        message: `Authentication service for ${provider} is currently unavailable: ${error.message}`
      };
    }
  }
}

// Initialize test authentication service and expose to window
window.authService = new TestAuthService();
