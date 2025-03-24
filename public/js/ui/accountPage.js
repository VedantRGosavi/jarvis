// Account Page Component
class AccountPage {
  constructor() {
    this.container = null;
  }

  render(container) {
    this.container = container;
    const user = window.authService.getCurrentUser();
    const authMethod = localStorage.getItem('fridayai_auth_method');

    this.container.innerHTML = `
      <div class="min-h-screen bg-gaming-primary text-gaming-light py-12">
        <div class="container mx-auto px-6">
          <!-- Profile Header -->
          <div class="bg-gaming-gray-800/90 backdrop-blur-sm rounded-lg p-8 mb-8 shadow-xl">
            <div class="flex items-center space-x-4">
              <div class="bg-gaming-gray-700 rounded-full p-4">
                <i class="fas fa-user text-3xl"></i>
              </div>
              <div>
                <h1 class="text-2xl font-bold">${user?.name || 'User'}</h1>
                <p class="text-gaming-gray-400">${user?.email || ''}</p>
              </div>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <!-- Subscription Status -->
            <div class="bg-gaming-gray-800/90 backdrop-blur-sm rounded-lg p-8 shadow-xl">
              <h2 class="text-xl font-semibold mb-6 flex items-center">
                <i class="fas fa-crown text-yellow-500 mr-3"></i>
                Subscription Status
              </h2>
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span>Current Plan</span>
                  <span class="text-gaming-gray-400" id="current-plan">Loading...</span>
                </div>
                <div class="flex justify-between items-center">
                  <span>Billing Period</span>
                  <span class="text-gaming-gray-400" id="billing-period">Loading...</span>
                </div>
                <div class="flex justify-between items-center">
                  <span>Next Payment</span>
                  <span class="text-gaming-gray-400" id="next-payment">Loading...</span>
                </div>
                <button class="w-full bg-gaming-gray-700 hover:bg-gaming-gray-600 text-white font-medium py-2 px-4 rounded-md transition mt-4">
                  Manage Subscription
                </button>
              </div>
            </div>

            <!-- Game Packs -->
            <div class="bg-gaming-gray-800/90 backdrop-blur-sm rounded-lg p-8 shadow-xl">
              <h2 class="text-xl font-semibold mb-6 flex items-center">
                <i class="fas fa-gamepad text-purple-500 mr-3"></i>
                Game Packs
              </h2>
              <div class="space-y-4" id="game-packs-list">
                <div class="flex items-center justify-between p-4 bg-gaming-gray-700 rounded-lg">
                  <div class="flex items-center">
                    <img src="assets/images/elden-ring.jpg" alt="Elden Ring" class="w-12 h-12 rounded-lg object-cover">
                    <div class="ml-4">
                      <h3 class="font-medium">Elden Ring</h3>
                      <p class="text-sm text-gaming-gray-400">Full Access</p>
                    </div>
                  </div>
                  <span class="text-green-500">Active</span>
                </div>
                <div class="flex items-center justify-between p-4 bg-gaming-gray-700 rounded-lg">
                  <div class="flex items-center">
                    <img src="assets/images/baldurs-gate-3.jpg" alt="Baldur's Gate 3" class="w-12 h-12 rounded-lg object-cover">
                    <div class="ml-4">
                      <h3 class="font-medium">Baldur's Gate 3</h3>
                      <p class="text-sm text-gaming-gray-400">Full Access</p>
                    </div>
                  </div>
                  <span class="text-green-500">Active</span>
                </div>
              </div>
            </div>

            <!-- Login Methods -->
            <div class="bg-gaming-gray-800/90 backdrop-blur-sm rounded-lg p-8 shadow-xl">
              <h2 class="text-xl font-semibold mb-6 flex items-center">
                <i class="fas fa-shield-alt text-blue-500 mr-3"></i>
                Login Method
              </h2>
              <div class="flex items-center space-x-4 p-4 bg-gaming-gray-700 rounded-lg">
                ${this.getAuthMethodIcon(authMethod)}
                <div>
                  <h3 class="font-medium">${this.getAuthMethodName(authMethod)}</h3>
                  <p class="text-sm text-gaming-gray-400">Connected Account</p>
                </div>
              </div>
            </div>

            <!-- Account Settings -->
            <div class="bg-gaming-gray-800/90 backdrop-blur-sm rounded-lg p-8 shadow-xl">
              <h2 class="text-xl font-semibold mb-6 flex items-center">
                <i class="fas fa-cog text-gray-500 mr-3"></i>
                Account Settings
              </h2>
              <div class="space-y-4">
                <button class="w-full bg-gaming-gray-700 hover:bg-gaming-gray-600 text-white font-medium py-2 px-4 rounded-md transition">
                  Change Password
                </button>
                <button class="w-full bg-gaming-gray-700 hover:bg-gaming-gray-600 text-white font-medium py-2 px-4 rounded-md transition">
                  Email Preferences
                </button>
                <button class="w-full bg-red-600/20 hover:bg-red-600/30 text-red-500 font-medium py-2 px-4 rounded-md transition">
                  Delete Account
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    this.initializeSubscriptionInfo();
    this.attachEventListeners();
  }

  getAuthMethodIcon(method) {
    const icons = {
      'google': window.authIcons?.GoogleIcon() || '<i class="fab fa-google"></i>',
      'github': window.authIcons?.GithubIcon() || '<i class="fab fa-github"></i>',
      'playstation': window.authIcons?.PlayStationIcon() || '<i class="fab fa-playstation"></i>',
      'steam': window.authIcons?.SteamIcon() || '<i class="fab fa-steam"></i>',
      'default': '<i class="fas fa-user"></i>'
    };
    return icons[method] || icons.default;
  }

  getAuthMethodName(method) {
    const names = {
      'google': 'Google Account',
      'github': 'GitHub Account',
      'playstation': 'PlayStation Network',
      'steam': 'Steam Account',
      'default': 'Email & Password'
    };
    return names[method] || names.default;
  }

  initializeSubscriptionInfo() {
    // Here we would normally fetch subscription info from the server
    // For now, using placeholder data
    document.getElementById('current-plan').textContent = 'Premium';
    document.getElementById('billing-period').textContent = 'Monthly';
    document.getElementById('next-payment').textContent = 'June 1, 2024';
  }

  attachEventListeners() {
    // Add event listeners for buttons and interactions
    const subscriptionButton = this.container.querySelector('button:contains("Manage Subscription")');
    if (subscriptionButton) {
      subscriptionButton.addEventListener('click', () => {
        // Handle subscription management
        console.log('Managing subscription...');
      });
    }
  }

  show() {
    const pageContainer = document.createElement('div');
    pageContainer.id = 'account-page';
    document.body.appendChild(pageContainer);
    this.render(pageContainer);
  }

  hide() {
    if (this.container) {
      this.container.remove();
    }
  }
}

// Export the AccountPage class
window.accountPage = new AccountPage();
