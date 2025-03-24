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
    if (window.location.pathname.includes('/auth/callback')) {
      const urlParams = new URLSearchParams(window.location.search);
      const token = urlParams.get('token');

      if (token) {
        // Store token
        this.token = token;
        localStorage.setItem(this.tokenKey, token);

        // Get user info
        this.fetchUserInfo(token);

        // Remove callback parameters from URL
        const newUrl = window.location.origin;
        window.history.replaceState({}, document.title, newUrl);
      }
    }
  }

  /**
   * Fetch user info using token
   * @param {string} token - JWT token
   */
  async fetchUserInfo(token) {
    try {
      const response = await fetch(`${this.baseUrl}/verify`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (!response.ok) {
        throw new Error('Failed to get user info');
      }

      const data = await response.json();
      this.user = data.user;
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
      const response = await fetch(`${this.baseUrl}/verify`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${this.token}`
        }
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
      console.log(`Fetching OAuth URL for ${provider} from ${this.baseUrl}/oauth/${provider}`);

      const response = await fetch(`${this.baseUrl}/oauth/${provider}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        },
        credentials: 'include'
      });

      // Check response type
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        console.error(`OAuth URL response has invalid content type: ${contentType} for ${provider}`);
        return {
          success: false,
          message: `Authentication service unavailable for ${provider}`
        };
      }

      // Safely parse JSON
      let data;
      try {
        data = await response.json();
      } catch (e) {
        console.error(`Failed to parse OAuth URL response as JSON for ${provider}:`, e);
        return {
          success: false,
          message: `Authentication service for ${provider} returned an invalid response`
        };
      }

      if (!response.ok) {
        throw new Error(data.message || `Failed to get ${provider} authentication URL`);
      }

      if (!data.auth_url) {
        console.error(`OAuth URL response is missing auth_url for ${provider}`, data);
        return {
          success: false,
          message: `Authentication service for ${provider} returned an invalid response`
        };
      }

      return {
        success: true,
        auth_url: data.auth_url
      };
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
      const response = await fetch(`${this.baseUrl}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ email, password })
      });

      // Check response type
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        console.error(`Login response has invalid content type: ${contentType}`);
        throw new Error('Login service is temporarily unavailable.');
      }

      // Safely parse JSON
      let data;
      try {
        data = await response.json();
      } catch (e) {
        console.error('Failed to parse login response as JSON:', e);
        throw new Error('Invalid response from login service.');
      }

      if (!response.ok) {
        throw new Error(data.message || 'Login failed');
      }

      // Store authentication data
      this.token = data.token;
      this.user = data.user;

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
      console.log(`Sending registration request to ${this.baseUrl}/register`);

      const response = await fetch(`${this.baseUrl}/register`, {
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

      // Store authentication data
      this.token = data.token;
      this.user = data.user;

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
}

// Initialize authentication service and expose to window
const authService = new AuthService();
window.authService = authService;
export default authService;
