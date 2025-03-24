/**
 * Authentication Service
 * Handles user authentication, registration, and session management
 */

export class AuthService {
  constructor() {
    // Determine if we're in production based on the hostname
    const hostname = window.location.hostname;
    const isProduction = hostname === 'fridayai-gold.vercel.app' || hostname === 'fridayai.me';

    // Set base URL accordingly
    if (hostname === 'fridayai.me') {
      this.baseUrl = 'https://fridayai.me/api/auth';
    } else if (hostname === 'fridayai-gold.vercel.app') {
      this.baseUrl = 'https://fridayai-gold.vercel.app/api/auth';
    } else {
      this.baseUrl = '/api/auth';
    }

    this.tokenKey = 'fridayai_auth_token';
    this.userKey = 'fridayai_user';
    this.token = localStorage.getItem(this.tokenKey);
    this.user = JSON.parse(localStorage.getItem(this.userKey) || 'null');

    // Check if token exists and validate it
    if (this.token) {
      this.validateToken();
    }

    // Check for OAuth callback token
    this.handleOAuthCallback();
  }

  /**
   * Check if user is authenticated
   * @returns {boolean} Authentication status
   */
  isAuthenticated() {
    return !!this.token;
  }

  /**
   * Get current user data
   * @returns {Object|null} User data or null if not authenticated
   */
  getCurrentUser() {
    return this.user;
  }

  /**
   * Get authentication token
   * @returns {string|null} JWT token or null if not authenticated
   */
  getToken() {
    return this.token;
  }

  /**
   * Handle OAuth callback if present in URL
   */
  handleOAuthCallback() {
    // Check if we have a token in the URL (either from OAuth callback or from development mock)
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    if (token) {
      console.log("OAuth token detected:", token.substring(0, 10) + '...');

      // Store token
      this.token = token;
      localStorage.setItem(this.tokenKey, token);

      // Create mock user data for development if token starts with 'mock_'
      if (token.startsWith('mock_')) {
        console.log("Using mock user data for development");
        this.user = {
          id: "mock-user-" + Math.floor(Math.random() * 1000),
          username: "MockUser",
          name: "Mock User",
          email: "mockuser@example.com",
          avatar: "https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y"
        };
        localStorage.setItem(this.userKey, JSON.stringify(this.user));

        // Dispatch login event
        document.dispatchEvent(new CustomEvent('userLogin', { detail: this.user }));

        // Clean URL
        const newUrl = window.location.origin;
        window.history.replaceState({}, document.title, newUrl);

        // Redirect to home if needed
        setTimeout(() => {
          window.location.href = '/index.html';
        }, 500);
      } else {
        // For real tokens, fetch user info
        this.fetchUserInfo(token);
      }
    } else if (urlParams.get('error')) {
      console.error("OAuth callback error:", urlParams.get('error'));

      // Potentially show an error message to the user here
      alert("Authentication failed: " + urlParams.get('error'));

      // Redirect to login page
      window.location.href = '/index.html';
    }
  }

  /**
   * Fetch user info using token
   * @param {string} token - JWT token
   */
  async fetchUserInfo(token) {
    try {
      // Let's directly use the full API endpoint to bypass any routing issues
      const hostname = window.location.hostname;
      let apiUrl;

      if (hostname === 'fridayai.me') {
        apiUrl = 'https://fridayai.me/api/auth.php?action=verify';
      } else if (hostname === 'fridayai-gold.vercel.app') {
        apiUrl = 'https://fridayai-gold.vercel.app/api/auth.php?action=verify';
      } else {
        apiUrl = `${this.baseUrl}/verify`;
      }

      console.log(`Fetching user info from ${apiUrl}`);

      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error('Failed to get user info');
      }

      const textResponse = await response.text();
      console.log(`User info raw response: ${textResponse.substring(0, 200)}`);

      if (!textResponse.trim()) {
        throw new Error('Empty response received');
      }

      const data = JSON.parse(textResponse);

      // Extract user data based on response format
      let userData;
      if (data.success && data.data && data.data.user) {
        // Direct PHP endpoint format
        userData = data.data.user;
      } else if (data.user) {
        // Original format
        userData = data.user;
      } else {
        throw new Error('Invalid user data format in response');
      }

      this.user = userData;
      localStorage.setItem(this.userKey, JSON.stringify(this.user));

      // Dispatch login event
      document.dispatchEvent(new CustomEvent('userLogin', { detail: this.user }));

      // Redirect to home if needed
      setTimeout(() => {
        window.location.href = '/index.html';
      }, 500);
    } catch (error) {
      console.error('Error fetching user info:', error);
      this.logout();
    }
  }

  /**
   * Validate the current token
   * @returns {Promise<boolean>} Whether token is valid
   */
  async validateToken() {
    try {
      // Let's directly use the full API endpoint to bypass any routing issues
      const hostname = window.location.hostname;
      let apiUrl;

      if (hostname === 'fridayai.me') {
        apiUrl = 'https://fridayai.me/api/auth.php?action=verify';
      } else if (hostname === 'fridayai-gold.vercel.app') {
        apiUrl = 'https://fridayai-gold.vercel.app/api/auth.php?action=verify';
      } else {
        apiUrl = `${this.baseUrl}/verify`;
      }

      console.log(`Validating token at ${apiUrl}`);

      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Accept': 'application/json'
        },
        credentials: 'include'
      });

      if (!response.ok) {
        this.logout();
        return false;
      }

      return true;
    } catch (error) {
      console.error('Error validating token:', error);
      this.logout();
      return false;
    }
  }

  /**
   * Get OAuth authorization URL
   * @param {string} provider - OAuth provider (google, github, etc.)
   * @returns {Promise<Object>} Authorization URL
   */
  async getOAuthUrl(provider) {
    try {
      console.log(`Getting OAuth URL for ${provider}`);

      // For production environments, use real OAuth endpoints
      const hostname = window.location.hostname;
      let apiUrl;

      if (hostname === 'fridayai.me') {
        apiUrl = `https://fridayai.me/api/auth/oauth?provider=${provider}`;
      } else if (hostname === 'fridayai-gold.vercel.app') {
        apiUrl = `https://fridayai-gold.vercel.app/api/auth/oauth?provider=${provider}`;
      } else {
        apiUrl = `/api/auth/oauth?provider=${provider}`;
      }

      console.log(`Fetching OAuth URL for ${provider} from ${apiUrl}`);

      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        },
        credentials: 'include'
      });

      // Check response status
      console.log(`OAuth response status for ${provider}: ${response.status}`);

      // Get the raw text response
      const textResponse = await response.text();

      // Log a small preview of the response
      console.log(`OAuth raw response for ${provider} (first 100 chars): ${textResponse.substring(0, 100)}`);

      // If the response is HTML (contains opening tags), it's an error page
      if (textResponse.includes('<!DOCTYPE') || textResponse.includes('<html') || textResponse.includes('<br />')) {
        console.error(`Received HTML error page instead of JSON for ${provider}`);
        return {
          success: false,
          message: `Authentication service for ${provider} returned an error. Please try again later.`
        };
      }

      // Parse JSON response
      try {
        if (!textResponse.trim()) {
          throw new Error('Empty response received');
        }

        const data = JSON.parse(textResponse);
        return this.parseOAuthResponse(data);
      } catch (e) {
        console.error(`Failed to parse OAuth URL response as JSON for ${provider}:`, e);
        return {
          success: false,
          message: `Authentication service for ${provider} returned an invalid response. Please try again later.`
        };
      }
    } catch (error) {
      console.error(`OAuth URL error for ${provider}:`, error);
      return {
        success: false,
        message: `Authentication service for ${provider} is currently unavailable.`
      };
    }
  }

  /**
   * Login with email and password
   * @param {string} email - User email
   * @param {string} password - User password
   * @returns {Promise<Object>} Login result
   */
  async login(email, password) {
    try {
      // Let's directly use the full API endpoint to bypass any routing issues
      const hostname = window.location.hostname;
      let apiUrl;

      if (hostname === 'fridayai.me') {
        apiUrl = 'https://fridayai.me/api/auth/login';
      } else if (hostname === 'fridayai-gold.vercel.app') {
        apiUrl = 'https://fridayai-gold.vercel.app/api/auth/login';
      } else {
        apiUrl = `${this.baseUrl}/login`;
      }

      console.log(`Sending login request to ${apiUrl}`);

      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ email, password })
      });

      console.log(`Login response status: ${response.status}`);

      // Check response type
      const contentType = response.headers.get('content-type');
      console.log(`Login response content-type: ${contentType}`);

      if (!contentType || !contentType.includes('application/json')) {
        console.error(`Login response has invalid content type: ${contentType}`);
        throw new Error('Login service is temporarily unavailable.');
      }

      // Safely parse JSON
      let data;
      try {
        const textResponse = await response.text();
        console.log(`Login raw response: ${textResponse.substring(0, 200)}`);

        if (!textResponse.trim()) {
          throw new Error('Empty response received');
        }

        data = JSON.parse(textResponse);
      } catch (e) {
        console.error('Failed to parse login response as JSON:', e);
        throw new Error('Invalid response from login service.');
      }

      if (!response.ok) {
        throw new Error(data.message || 'Login failed');
      }

      // Check if we're using the PHP direct path
      if (apiUrl.includes('auth.php')) {
        // In the direct PHP endpoint case, the token is in data.data.token
        if (data.success && data.data) {
          this.token = data.data.token;
          this.user = data.data.user;
        } else {
          throw new Error('Invalid response format from login service');
        }
      } else {
        // In the original case, the token is directly in data.token
        this.token = data.token;
        this.user = data.user;
      }

      localStorage.setItem(this.tokenKey, this.token);
      localStorage.setItem(this.userKey, JSON.stringify(this.user));

      return {
        success: true,
        user: this.user
      };
    } catch (error) {
      console.error('Login error:', error);
      return {
        success: false,
        message: error.message
      };
    }
  }

  /**
   * Register a new user
   * @param {string} name - User name
   * @param {string} email - User email
   * @param {string} password - User password
   * @returns {Promise<Object>} Registration result
   */
  async register(name, email, password) {
    try {
      // Let's directly use the full API endpoint to bypass any routing issues
      const hostname = window.location.hostname;
      let apiUrl;

      if (hostname === 'fridayai.me') {
        apiUrl = 'https://fridayai.me/api/auth/register';
      } else if (hostname === 'fridayai-gold.vercel.app') {
        apiUrl = 'https://fridayai-gold.vercel.app/api/auth/register';
      } else {
        apiUrl = `${this.baseUrl}/register`;
      }

      console.log(`Sending registration request to ${apiUrl}`);

      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ name, email, password })
      });

      console.log(`Registration response status: ${response.status}`);

      // Check response type
      const contentType = response.headers.get('content-type');
      console.log(`Registration response content-type: ${contentType}`);

      if (!contentType || !contentType.includes('application/json')) {
        console.error(`Registration response has invalid content type: ${contentType}`);
        throw new Error('Registration service is temporarily unavailable.');
      }

      // Safely parse JSON
      let data;
      try {
        const textResponse = await response.text();
        console.log(`Registration raw response: ${textResponse.substring(0, 200)}`);

        if (!textResponse.trim()) {
          throw new Error('Empty response received');
        }

        data = JSON.parse(textResponse);
      } catch (e) {
        console.error('Failed to parse registration response as JSON:', e);
        throw new Error('Invalid response from registration service.');
      }

      if (!response.ok) {
        throw new Error(data.message || 'Registration failed');
      }

      // Check if we're using the PHP direct path
      if (apiUrl.includes('auth.php')) {
        // In the direct PHP endpoint case, the token is in data.data.token
        if (data.success && data.data) {
          this.token = data.data.token;
          this.user = data.data.user;
        } else {
          throw new Error('Invalid response format from registration service');
        }
      } else {
        // In the original case, the token is directly in data.token
        this.token = data.token;
        this.user = data.user;
      }

      localStorage.setItem(this.tokenKey, this.token);
      localStorage.setItem(this.userKey, JSON.stringify(this.user));

      return {
        success: true,
        user: this.user
      };
    } catch (error) {
      console.error('Registration error:', error);
      return {
        success: false,
        message: error.message
      };
    }
  }

  /**
   * Logout the current user
   */
  logout() {
    this.token = null;
    this.user = null;
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);

    // Redirect to login page if needed
    if (window.location.pathname.includes('/overlay.html')) {
      window.location.href = '/index.html';
    }

    // Dispatch logout event
    document.dispatchEvent(new CustomEvent('userLogout'));
  }

  /**
   * Parse the OAuth response into a consistent format
   * @param {Object} data - Raw response data
   * @returns {Object} Normalized response with success and auth_url
   */
  parseOAuthResponse(data) {
    // Case 1: Direct auth_url property
    if (data.auth_url) {
      return {
        success: true,
        auth_url: data.auth_url
      };
    }

    // Case 2: Success with data.auth_url structure
    if (data.success && data.data && data.data.auth_url) {
      return {
        success: true,
        auth_url: data.data.auth_url
      };
    }

    // Case 3: Success with data.url structure (used in some endpoints)
    if (data.success && data.url) {
      return {
        success: true,
        auth_url: data.url
      };
    }

    // Failed to find a valid auth URL
    console.error('Failed to parse OAuth response:', data);
    return {
      success: false,
      message: 'Invalid response format from authentication service'
    };
  }
}

// Initialize authentication service and expose to window
const authService = new AuthService();
window.authService = authService;
export default authService;
