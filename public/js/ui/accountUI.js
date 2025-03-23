/**
 * Account UI Module
 * Displays user account information, subscription, and purchase history
 */

class AccountUI {
  constructor() {
    this.accountContainer = null;
    this.isVisible = false;
    
    // Templates
    this.accountTemplate = `
      <div class="account-section bg-gaming-gray-800 rounded-lg p-6 max-w-md mx-auto">
        <h2 class="text-2xl font-bold mb-6 text-center">My Account</h2>
        
        <div class="user-info bg-gaming-gray-700 p-4 rounded mb-6">
          <p><span class="font-medium">Name:</span> <span id="account-name">Loading...</span></p>
          <p><span class="font-medium">Email:</span> <span id="account-email">Loading...</span></p>
        </div>
        
        <div class="subscription-info mb-6">
          <h3 class="text-lg font-semibold mb-2">Subscription</h3>
          <div id="subscription-details" class="bg-gaming-gray-700 p-4 rounded">
            <p class="text-center text-sm text-gaming-gray-400">Loading subscription details...</p>
          </div>
        </div>
        
        <div class="purchase-history">
          <h3 class="text-lg font-semibold mb-2">Purchase History</h3>
          <div id="purchase-list" class="bg-gaming-gray-700 p-4 rounded">
            <p class="text-center text-sm text-gaming-gray-400">Loading purchase history...</p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-between">
          <button id="account-close" class="border border-gaming-gray-600 hover:border-gaming-gray-500 text-white font-bold py-2 px-4 rounded transition">
            Close
          </button>
          <button id="account-logout" class="bg-gaming-gray-600 hover:bg-gaming-gray-500 text-white font-bold py-2 px-4 rounded transition">
            Log Out
          </button>
        </div>
      </div>
    `;
  }
  
  /**
   * Show account UI
   */
  async show() {
    // Check authentication
    if (!window.authService || !window.authService.isAuthenticated()) {
      if (window.notifications) {
        window.notifications.warning('Please log in to view your account');
      }
      return;
    }
    
    // Create account container if it doesn't exist
    if (!this.accountContainer) {
      this.accountContainer = document.createElement('div');
      this.accountContainer.className = 'account-container fixed inset-0 bg-gaming-gray-900 bg-opacity-90 flex items-center justify-center z-50 hidden';
      document.body.appendChild(this.accountContainer);
    }
    
    // Render account UI
    this.accountContainer.innerHTML = `
      <div class="account-modal relative max-w-lg w-full rounded-lg overflow-auto max-h-90vh">
        ${this.accountTemplate}
      </div>
    `;
    
    // Show container
    this.accountContainer.classList.remove('hidden');
    this.isVisible = true;
    
    // Add event listeners
    const closeButton = this.accountContainer.querySelector('#account-close');
    const logoutButton = this.accountContainer.querySelector('#account-logout');
    
    if (closeButton) {
      closeButton.addEventListener('click', () => {
        this.hide();
      });
    }
    
    if (logoutButton) {
      logoutButton.addEventListener('click', () => {
        if (window.authService) {
          window.authService.logout();
        }
        this.hide();
      });
    }
    
    // Load account data
    this.loadAccountData();
  }
  
  /**
   * Hide account UI
   */
  hide() {
    if (this.accountContainer) {
      this.accountContainer.classList.add('hidden');
    }
    this.isVisible = false;
  }
  
  /**
   * Toggle account UI visibility
   */
  toggle() {
    if (this.isVisible) {
      this.hide();
    } else {
      this.show();
    }
  }
  
  /**
   * Load account data
   */
  async loadAccountData() {
    // Update user info
    const user = window.authService.getCurrentUser();
    const nameEl = this.accountContainer.querySelector('#account-name');
    const emailEl = this.accountContainer.querySelector('#account-email');
    
    if (nameEl && user) nameEl.textContent = user.username || user.name || 'User';
    if (emailEl && user) emailEl.textContent = user.email || '';
    
    // Load subscription data
    await this.loadSubscriptionDetails();
    
    // Load purchase history
    await this.loadPurchaseHistory();
  }
  
  /**
   * Load subscription details
   */
  async loadSubscriptionDetails() {
    if (!window.paymentService) return;
    
    const subscriptionContainer = this.accountContainer.querySelector('#subscription-details');
    if (!subscriptionContainer) return;
    
    try {
      const subscription = await window.paymentService.getSubscription();
      
      if (subscription) {
        // Format date
        const endDate = new Date(subscription.current_period_end);
        const formattedDate = endDate.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        
        // Display active subscription
        subscriptionContainer.innerHTML = `
          <div class="text-center">
            <div class="mb-2 font-semibold">Premium Plan</div>
            <div class="mb-1 text-sm"><span class="text-gaming-gray-300">Status:</span> ${subscription.status}</div>
            <div class="mb-1 text-sm"><span class="text-gaming-gray-300">Renews:</span> ${formattedDate}</div>
            <button id="manage-subscription" class="mt-2 text-xs bg-gaming-gray-600 hover:bg-gaming-gray-500 px-2 py-1 rounded">
              Manage Subscription
            </button>
          </div>
        `;
        
        // Add event listener to manage subscription button
        const manageButton = subscriptionContainer.querySelector('#manage-subscription');
        if (manageButton) {
          manageButton.addEventListener('click', () => {
            // Open subscription management
            console.log('Manage subscription clicked');
            // In a real implementation, this would open a customer portal or similar
            if (window.notifications) {
              window.notifications.info('Subscription management is not implemented in the demo');
            }
          });
        }
      } else {
        // No active subscription
        subscriptionContainer.innerHTML = `
          <div class="text-center">
            <p class="mb-2 text-sm">You don't have an active subscription.</p>
            <button id="subscribe-button" class="bg-gaming-gray-600 hover:bg-gaming-gray-500 text-white text-sm py-1 px-3 rounded transition">
              Subscribe Now
            </button>
          </div>
        `;
        
        // Add event listener to subscribe button
        const subscribeButton = subscriptionContainer.querySelector('#subscribe-button');
        if (subscribeButton) {
          subscribeButton.addEventListener('click', () => {
            this.hide();
            
            // Scroll to pricing section
            const pricingSection = document.getElementById('pricing');
            if (pricingSection) {
              pricingSection.scrollIntoView({ behavior: 'smooth' });
            }
          });
        }
      }
    } catch (error) {
      console.error('Error loading subscription:', error);
      subscriptionContainer.innerHTML = `
        <p class="text-center text-sm text-red-500">Failed to load subscription details</p>
      `;
    }
  }
  
  /**
   * Load purchase history
   */
  async loadPurchaseHistory() {
    if (!window.paymentService) return;
    
    const purchaseListContainer = this.accountContainer.querySelector('#purchase-list');
    if (!purchaseListContainer) return;
    
    try {
      const purchases = await window.paymentService.getPurchases();
      
      if (purchases && purchases.length > 0) {
        // Display purchase history
        const purchaseItems = purchases.map(purchase => {
          // Format date
          const purchaseDate = new Date(purchase.created_at);
          const formattedDate = purchaseDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
          });
          
          // Format price
          const price = (purchase.amount / 100).toFixed(2);
          
          return `
            <div class="purchase-item flex justify-between py-2 border-b border-gaming-gray-600 last:border-0">
              <div>
                <div class="font-medium text-sm">${purchase.game_name || 'Game Purchase'}</div>
                <div class="text-xs text-gaming-gray-300">${formattedDate}</div>
              </div>
              <div class="text-sm">$${price}</div>
            </div>
          `;
        }).join('');
        
        purchaseListContainer.innerHTML = `
          <div class="purchases-list space-y-1">
            ${purchaseItems}
          </div>
        `;
      } else {
        // No purchases
        purchaseListContainer.innerHTML = `
          <p class="text-center text-sm text-gaming-gray-400">No purchases yet</p>
        `;
      }
    } catch (error) {
      console.error('Error loading purchases:', error);
      purchaseListContainer.innerHTML = `
        <p class="text-center text-sm text-red-500">Failed to load purchase history</p>
      `;
    }
  }
}

// Initialize account UI
document.addEventListener('DOMContentLoaded', () => {
  const accountUI = new AccountUI();
  
  // Expose to global scope
  window.accountUI = accountUI;
  
  // Add listener to user button in overlay
  const userButton = document.getElementById('user-btn');
  if (userButton) {
    userButton.addEventListener('click', () => {
      accountUI.toggle();
    });
  }
  
  // Add listener to account links
  document.querySelectorAll('.account-link').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      accountUI.show();
    });
  });
}); 