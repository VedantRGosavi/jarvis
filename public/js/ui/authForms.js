/**
 * Authentication Forms Module
 * Handles login and registration UI forms
 */

class AuthForms {
  constructor() {
    this.loginFormTemplate = `
      <div class="auth-form bg-gaming-gray-800 rounded-lg p-6 max-w-md mx-auto">
        <h2 class="text-2xl font-bold mb-6 text-center">Log In</h2>
        <form id="loginForm" class="space-y-4">
          <div class="form-group">
            <label for="loginEmail" class="block text-sm font-medium mb-1">Email</label>
            <input type="email" id="loginEmail" class="w-full px-3 py-2 bg-gaming-gray-700 border border-gaming-gray-600 rounded text-gaming-light" required>
          </div>
          <div class="form-group">
            <label for="loginPassword" class="block text-sm font-medium mb-1">Password</label>
            <input type="password" id="loginPassword" class="w-full px-3 py-2 bg-gaming-gray-700 border border-gaming-gray-600 rounded text-gaming-light" required>
          </div>
          <div id="loginError" class="text-red-500 text-sm hidden"></div>
          <div class="flex items-center justify-between">
            <button type="submit" class="bg-gaming-gray-600 hover:bg-gaming-gray-500 text-white font-bold py-2 px-4 rounded transition">
              Log In
            </button>
            <a href="#" id="showRegistration" class="text-sm text-gaming-gray-400 hover:text-gaming-gray-300">Need an account?</a>
          </div>
        </form>
      </div>
    `;
    
    this.registrationFormTemplate = `
      <div class="auth-form bg-gaming-gray-800 rounded-lg p-6 max-w-md mx-auto">
        <h2 class="text-2xl font-bold mb-6 text-center">Create Account</h2>
        <form id="registrationForm" class="space-y-4">
          <div class="form-group">
            <label for="registerName" class="block text-sm font-medium mb-1">Name</label>
            <input type="text" id="registerName" class="w-full px-3 py-2 bg-gaming-gray-700 border border-gaming-gray-600 rounded text-gaming-light" required>
          </div>
          <div class="form-group">
            <label for="registerEmail" class="block text-sm font-medium mb-1">Email</label>
            <input type="email" id="registerEmail" class="w-full px-3 py-2 bg-gaming-gray-700 border border-gaming-gray-600 rounded text-gaming-light" required>
          </div>
          <div class="form-group">
            <label for="registerPassword" class="block text-sm font-medium mb-1">Password</label>
            <input type="password" id="registerPassword" class="w-full px-3 py-2 bg-gaming-gray-700 border border-gaming-gray-600 rounded text-gaming-light" required minlength="8">
            <p class="text-xs text-gaming-gray-400 mt-1">Minimum 8 characters</p>
          </div>
          <div id="registrationError" class="text-red-500 text-sm hidden"></div>
          <div class="flex items-center justify-between">
            <button type="submit" class="bg-gaming-gray-600 hover:bg-gaming-gray-500 text-white font-bold py-2 px-4 rounded transition">
              Register
            </button>
            <a href="#" id="showLogin" class="text-sm text-gaming-gray-400 hover:text-gaming-gray-300">Already have an account?</a>
          </div>
        </form>
      </div>
    `;
    
    this.userProfileTemplate = `
      <div class="user-profile bg-gaming-gray-800 rounded-lg p-6 max-w-md mx-auto">
        <h2 class="text-2xl font-bold mb-6 text-center">My Account</h2>
        <div class="space-y-4">
          <div class="user-info bg-gaming-gray-700 p-4 rounded">
            <p><span class="font-medium">Name:</span> <span id="profileName"></span></p>
            <p><span class="font-medium">Email:</span> <span id="profileEmail"></span></p>
          </div>
          <div class="flex justify-between">
            <button id="launchAppBtn" class="bg-gaming-gray-600 hover:bg-gaming-gray-500 text-white font-bold py-2 px-4 rounded transition">
              Launch App
            </button>
            <button id="logoutBtn" class="border border-gaming-gray-600 hover:border-gaming-gray-500 text-white font-bold py-2 px-4 rounded transition">
              Log Out
            </button>
          </div>
        </div>
      </div>
    `;
  }
  
  /**
   * Render authentication UI based on authentication state
   * @param {HTMLElement} container - Container element to render in
   */
  renderAuthUI(container) {
    if (!container) return;
    
    if (window.authService && window.authService.isAuthenticated()) {
      this.renderUserProfile(container);
    } else {
      this.renderLoginForm(container);
    }
  }
  
  /**
   * Render login form
   * @param {HTMLElement} container - Container element to render in
   */
  renderLoginForm(container) {
    container.innerHTML = this.loginFormTemplate;
    
    // Set up form event handlers
    const loginForm = document.getElementById('loginForm');
    const showRegistrationLink = document.getElementById('showRegistration');
    
    if (loginForm) {
      loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleLogin();
      });
    }
    
    if (showRegistrationLink) {
      showRegistrationLink.addEventListener('click', (e) => {
        e.preventDefault();
        this.renderRegistrationForm(container);
      });
    }
  }
  
  /**
   * Render registration form
   * @param {HTMLElement} container - Container element to render in
   */
  renderRegistrationForm(container) {
    container.innerHTML = this.registrationFormTemplate;
    
    // Set up form event handlers
    const registrationForm = document.getElementById('registrationForm');
    const showLoginLink = document.getElementById('showLogin');
    
    if (registrationForm) {
      registrationForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleRegistration();
      });
    }
    
    if (showLoginLink) {
      showLoginLink.addEventListener('click', (e) => {
        e.preventDefault();
        this.renderLoginForm(container);
      });
    }
  }
  
  /**
   * Render user profile
   * @param {HTMLElement} container - Container element to render in
   */
  renderUserProfile(container) {
    container.innerHTML = this.userProfileTemplate;
    
    // Get current user
    const user = window.authService.getCurrentUser();
    
    // Populate user data
    const profileName = document.getElementById('profileName');
    const profileEmail = document.getElementById('profileEmail');
    
    if (profileName && user) {
      profileName.textContent = user.username || user.name || 'User';
    }
    
    if (profileEmail && user) {
      profileEmail.textContent = user.email || '';
    }
    
    // Set up event handlers
    const launchAppBtn = document.getElementById('launchAppBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    
    if (launchAppBtn) {
      launchAppBtn.addEventListener('click', () => {
        window.location.href = '/overlay.html';
      });
    }
    
    if (logoutBtn) {
      logoutBtn.addEventListener('click', () => {
        window.authService.logout();
        this.renderLoginForm(container);
      });
    }
  }
  
  /**
   * Handle login form submission
   */
  async handleLogin() {
    if (!window.authService) return;
    
    const emailInput = document.getElementById('loginEmail');
    const passwordInput = document.getElementById('loginPassword');
    const errorDisplay = document.getElementById('loginError');
    
    if (!emailInput || !passwordInput) return;
    
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    
    if (!email || !password) {
      this.showFormError(errorDisplay, 'Please fill in all fields');
      return;
    }
    
    // Clear any previous errors
    this.hideFormError(errorDisplay);
    
    // Show loading state
    const submitButton = document.querySelector('#loginForm button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Logging in...';
    }
    
    // Attempt login
    try {
      const result = await window.authService.login(email, password);
      
      if (result.success) {
        // Render user profile on success
        const container = document.querySelector('#loginForm').closest('.auth-form').parentElement;
        this.renderUserProfile(container);
      } else {
        this.showFormError(errorDisplay, result.message || 'Login failed. Please check your credentials.');
      }
    } catch (error) {
      this.showFormError(errorDisplay, 'An error occurred during login. Please try again.');
    } finally {
      // Reset button state
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Log In';
      }
    }
  }
  
  /**
   * Handle registration form submission
   */
  async handleRegistration() {
    if (!window.authService) return;
    
    const nameInput = document.getElementById('registerName');
    const emailInput = document.getElementById('registerEmail');
    const passwordInput = document.getElementById('registerPassword');
    const errorDisplay = document.getElementById('registrationError');
    
    if (!nameInput || !emailInput || !passwordInput) return;
    
    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    
    if (!name || !email || !password) {
      this.showFormError(errorDisplay, 'Please fill in all fields');
      return;
    }
    
    if (password.length < 8) {
      this.showFormError(errorDisplay, 'Password must be at least 8 characters');
      return;
    }
    
    // Clear any previous errors
    this.hideFormError(errorDisplay);
    
    // Show loading state
    const submitButton = document.querySelector('#registrationForm button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Creating account...';
    }
    
    // Attempt registration
    try {
      const result = await window.authService.register(name, email, password);
      
      if (result.success) {
        // Render user profile on success
        const container = document.querySelector('#registrationForm').closest('.auth-form').parentElement;
        this.renderUserProfile(container);
      } else {
        this.showFormError(errorDisplay, result.message || 'Registration failed. Please try again.');
      }
    } catch (error) {
      this.showFormError(errorDisplay, 'An error occurred during registration. Please try again.');
    } finally {
      // Reset button state
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Register';
      }
    }
  }
  
  /**
   * Show error message in form
   * @param {HTMLElement} errorElement - Error display element
   * @param {string} message - Error message to show
   */
  showFormError(errorElement, message) {
    if (!errorElement) return;
    
    errorElement.textContent = message;
    errorElement.classList.remove('hidden');
  }
  
  /**
   * Hide error message in form
   * @param {HTMLElement} errorElement - Error display element
   */
  hideFormError(errorElement) {
    if (!errorElement) return;
    
    errorElement.textContent = '';
    errorElement.classList.add('hidden');
  }
}

// Initialize auth forms
document.addEventListener('DOMContentLoaded', () => {
  const authForms = new AuthForms();
  
  // Expose to global scope
  window.authForms = authForms;
  
  // Render auth UI if container exists
  const authContainer = document.getElementById('auth-container');
  if (authContainer) {
    authForms.renderAuthUI(authContainer);
  }
}); 