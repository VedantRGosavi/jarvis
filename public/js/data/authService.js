/**
 * Authentication Service
 * Handles user authentication, registration, and session management
 */

class AuthService {
  constructor() {
    this.baseUrl = '/api/auth';
    this.tokenKey = 'fridayai_auth_token';
    this.userKey = 'fridayai_user';
    this.token = localStorage.getItem(this.tokenKey);
    this.user = JSON.parse(localStorage.getItem(this.userKey) || 'null');
    
    // Check if token exists and validate it
    if (this.token) {
      this.validateToken();
    }
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
   * Validate the current token
   * @returns {Promise<boolean>} Whether token is valid
   */
  async validateToken() {
    try {
      const response = await fetch(`${this.baseUrl}/validate`, {
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
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email, password })
      });
      
      const data = await response.json();
      
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
      const response = await fetch(`${this.baseUrl}/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ name, email, password })
      });
      
      const data = await response.json();
      
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

// Initialize authentication service
document.addEventListener('DOMContentLoaded', () => {
  const authService = new AuthService();
  
  // Expose to global scope for other scripts
  window.authService = authService;
}); 