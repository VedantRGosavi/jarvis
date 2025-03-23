/**
 * Payment Service
 * Handles payment processing using Stripe
 */

class PaymentService {
  constructor() {
    this.baseUrl = '/api/payments';
    this.stripePromise = null;
    this.stripePublicKey = 'pk_test_51XYZABCDEStripeTestKeyKeepThisSecret0123'; // Replace with real key in production
    
    // Initialize Stripe if the script is loaded
    this.initStripe();
  }
  
  /**
   * Initialize Stripe
   */
  initStripe() {
    if (window.Stripe) {
      this.stripePromise = window.Stripe(this.stripePublicKey);
    } else {
      console.warn('Stripe.js not loaded. Payment functionality will be limited.');
    }
  }
  
  /**
   * Get Stripe instance
   * @returns {Promise<Stripe>} Stripe instance
   */
  async getStripe() {
    if (!this.stripePromise) {
      this.initStripe();
    }
    
    if (!this.stripePromise) {
      throw new Error('Stripe not initialized');
    }
    
    return this.stripePromise;
  }
  
  /**
   * Process a game purchase
   * @param {string} gameId - Game identifier
   * @returns {Promise<Object>} Payment result
   */
  async purchaseGame(gameId) {
    try {
      if (!window.authService || !window.authService.isAuthenticated()) {
        throw new Error('Authentication required for purchases');
      }
      
      // Request payment intent from server
      const response = await fetch(`${this.baseUrl}/purchase-game`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${window.authService.getToken()}`
        },
        body: JSON.stringify({ game_id: gameId })
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'Failed to initiate payment');
      }
      
      // Get Stripe instance
      const stripe = await this.getStripe();
      
      // Show payment form
      const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret, {
        payment_method: {
          card: this.getCardElement(),
          billing_details: {
            name: window.authService.getCurrentUser()?.name || '',
            email: window.authService.getCurrentUser()?.email || ''
          }
        }
      });
      
      if (error) {
        throw new Error(error.message);
      }
      
      // Payment successful
      return {
        success: true,
        paymentIntent
      };
    } catch (error) {
      console.error('Payment error:', error);
      return {
        success: false,
        message: error.message
      };
    }
  }
  
  /**
   * Create a subscription
   * @returns {Promise<Object>} Subscription result
   */
  async createSubscription() {
    try {
      if (!window.authService || !window.authService.isAuthenticated()) {
        throw new Error('Authentication required for subscriptions');
      }
      
      // Request subscription from server
      const response = await fetch(`${this.baseUrl}/create-subscription`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${window.authService.getToken()}`
        }
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'Failed to create subscription');
      }
      
      // Get Stripe instance
      const stripe = await this.getStripe();
      
      // Show payment form for subscription
      const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret, {
        payment_method: {
          card: this.getCardElement(),
          billing_details: {
            name: window.authService.getCurrentUser()?.name || '',
            email: window.authService.getCurrentUser()?.email || ''
          }
        }
      });
      
      if (error) {
        throw new Error(error.message);
      }
      
      // Subscription created successfully
      return {
        success: true,
        subscriptionId: data.subscription_id,
        status: data.status
      };
    } catch (error) {
      console.error('Subscription error:', error);
      return {
        success: false,
        message: error.message
      };
    }
  }
  
  /**
   * Get user's current subscription
   * @returns {Promise<Object>} Subscription info
   */
  async getSubscription() {
    try {
      if (!window.authService || !window.authService.isAuthenticated()) {
        throw new Error('Authentication required');
      }
      
      const response = await fetch(`${this.baseUrl}/subscription`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${window.authService.getToken()}`
        }
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'Failed to get subscription');
      }
      
      return data.subscription;
    } catch (error) {
      console.error('Error getting subscription:', error);
      return null;
    }
  }
  
  /**
   * Get user's purchase history
   * @returns {Promise<Array>} Purchase history
   */
  async getPurchases() {
    try {
      if (!window.authService || !window.authService.isAuthenticated()) {
        throw new Error('Authentication required');
      }
      
      const response = await fetch(`${this.baseUrl}/purchases`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${window.authService.getToken()}`
        }
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'Failed to get purchases');
      }
      
      return data.purchases;
    } catch (error) {
      console.error('Error getting purchases:', error);
      return [];
    }
  }
  
  /**
   * Create a card element for Stripe (placeholder - would need an actual UI component)
   * @returns {Object} Card element
   */
  getCardElement() {
    // In a real implementation, this would use Stripe Elements
    throw new Error('Payment UI not implemented. Please use the PaymentUI component.');
  }
}

// Initialize payment service
document.addEventListener('DOMContentLoaded', () => {
  const paymentService = new PaymentService();
  
  // Expose to global scope for other scripts
  window.paymentService = paymentService;
}); 