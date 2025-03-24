/**
 * Payment UI Module
 * Handles payment form UI using Stripe Elements
 */

class PaymentUI {
  constructor() {
    this.stripe = null;
    this.elements = null;
    this.card = null;
    this.paymentContainer = null;
    this.isInitialized = false;

    // Templates
    this.paymentFormTemplate = `
      <div class="payment-form bg-gaming-gray-800 rounded-lg p-6 max-w-md mx-auto">
        <h2 class="text-2xl font-bold mb-6 text-center payment-title">Payment</h2>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Card Information</label>
          <div id="card-element" class="bg-gaming-gray-700 border border-gaming-gray-600 rounded p-3 h-10"></div>
          <div id="card-errors" class="text-red-500 text-xs mt-1 hidden"></div>
        </div>

        <div class="payment-details mb-6">
          <div class="flex justify-between text-sm mb-2">
            <span>Subtotal</span>
            <span class="payment-subtotal">$0.00</span>
          </div>
          <div class="flex justify-between text-sm mb-2 payment-tax-container">
            <span>Tax</span>
            <span class="payment-tax">$0.00</span>
          </div>
          <div class="flex justify-between font-bold border-t border-gaming-gray-600 pt-2 mt-2">
            <span>Total</span>
            <span class="payment-total">$0.00</span>
          </div>
        </div>

        <button type="button" id="payment-submit" class="w-full bg-gaming-gray-600 hover:bg-gaming-gray-500 text-white font-bold py-2 px-4 rounded transition">
          Pay Now
        </button>

        <div id="payment-processing" class="hidden mt-4 text-center">
          <div class="inline-block animate-spin h-4 w-4 border-2 border-gaming-gray-500 border-t-gaming-gray-300 rounded-full mr-2"></div>
          <span>Processing payment...</span>
        </div>

        <div id="payment-success" class="hidden mt-4 text-center text-green-500">
          <svg class="inline-block h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          <span>Payment successful!</span>
        </div>

        <div id="payment-error" class="hidden mt-4 text-center text-red-500">
          <svg class="inline-block h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          <span class="payment-error-message">Payment failed. Please try again.</span>
        </div>
      </div>
    `;
  }

  /**
   * Initialize Stripe Elements
   */
  async initialize() {
    if (this.isInitialized) return true;

    if (!window.Stripe) {
      console.error('Stripe.js not loaded. Please include Stripe.js in your page.');
      return false;
    }

    if (!window.paymentService) {
      console.error('Payment service not loaded. Please include paymentService.js in your page.');
      return false;
    }

    try {
      // Get Stripe from payment service
      this.stripe = await window.paymentService.getStripe();
      this.isInitialized = true;
      return true;
    } catch (error) {
      console.error('Failed to initialize Stripe:', error);
      return false;
    }
  }

  /**
   * Create Stripe Elements instance
   * @param {HTMLElement} container - Element where to mount the card element
   * @returns {Object} Stripe Elements instance
   */
  createElements(container) {
    if (!this.stripe) return null;

    this.elements = this.stripe.elements();

    const style = {
      base: {
        color: '#f8f8f8',
        fontFamily: 'Inter, sans-serif',
        fontSize: '16px',
        '::placeholder': {
          color: '#a3a3a3'
        },
        iconColor: '#f8f8f8'
      },
      invalid: {
        color: '#e74c3c',
        iconColor: '#e74c3c'
      }
    };

    this.card = this.elements.create('card', { style });
    this.card.mount(container);

    // Add event listeners
    this.card.on('change', (event) => {
      const errorElement = document.getElementById('card-errors');
      if (errorElement) {
        if (event.error) {
          errorElement.textContent = event.error.message;
          errorElement.classList.remove('hidden');
        } else {
          errorElement.textContent = '';
          errorElement.classList.add('hidden');
        }
      }
    });

    return this.card;
  }

  /**
   * Show payment form for a game purchase
   * @param {Object} gameInfo - Game information
   * @param {string} gameInfo.id - Game ID
   * @param {string} gameInfo.name - Game name
   * @param {number} gameInfo.price - Game price in cents
   * @param {Function} onSuccess - Callback on successful payment
   * @param {Function} onCancel - Callback on cancelled payment
   */
  async showGamePurchaseForm(gameInfo, onSuccess, onCancel) {
    // Initialize if needed
    if (!this.isInitialized && !(await this.initialize())) {
      if (window.notifications) {
        window.notifications.error('Failed to initialize payment system');
      }
      return;
    }

    // Create payment container if it doesn't exist
    if (!this.paymentContainer) {
      this.paymentContainer = document.createElement('div');
      this.paymentContainer.className = 'payment-container fixed inset-0 bg-gaming-gray-900 bg-opacity-90 flex items-center justify-center z-50 hidden';
      document.body.appendChild(this.paymentContainer);
    }

    // Render payment form
    this.paymentContainer.innerHTML = `
      <div class="payment-modal relative max-w-lg w-full rounded-lg overflow-hidden">
        <button type="button" class="payment-close absolute top-3 right-3 text-gaming-gray-400 hover:text-gaming-gray-200">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        ${this.paymentFormTemplate}
      </div>
    `;

    // Update payment form details
    const formTitle = this.paymentContainer.querySelector('.payment-title');
    const subtotalEl = this.paymentContainer.querySelector('.payment-subtotal');
    const taxEl = this.paymentContainer.querySelector('.payment-tax');
    const totalEl = this.paymentContainer.querySelector('.payment-total');

    if (formTitle) formTitle.textContent = `Purchase ${gameInfo.name}`;

    // Format prices
    const subtotal = (gameInfo.price / 100).toFixed(2);
    const tax = 0; // We'll assume no tax for simplicity
    const total = (gameInfo.price / 100).toFixed(2);

    if (subtotalEl) subtotalEl.textContent = `$${subtotal}`;
    if (taxEl) taxEl.textContent = `$${tax.toFixed(2)}`;
    if (totalEl) totalEl.textContent = `$${total}`;

    // Show container
    this.paymentContainer.classList.remove('hidden');

    // Initialize Stripe Elements
    const cardElement = this.paymentContainer.querySelector('#card-element');
    if (cardElement) {
      this.createElements(cardElement);
    }

    // Add event listeners
    const closeButton = this.paymentContainer.querySelector('.payment-close');
    const submitButton = this.paymentContainer.querySelector('#payment-submit');

    if (closeButton) {
      closeButton.addEventListener('click', () => {
        this.closePaymentForm();
        if (onCancel) onCancel();
      });
    }

    if (submitButton) {
      submitButton.addEventListener('click', async () => {
        await this.processGamePayment(gameInfo, onSuccess);
      });
    }
  }

  /**
   * Process game payment
   * @param {Object} gameInfo - Game information
   * @param {Function} onSuccess - Callback on successful payment
   */
  async processGamePayment(gameInfo, onSuccess) {
    const submitButton = this.paymentContainer.querySelector('#payment-submit');
    const processingEl = this.paymentContainer.querySelector('#payment-processing');
    const successEl = this.paymentContainer.querySelector('#payment-success');
    const errorEl = this.paymentContainer.querySelector('#payment-error');
    const errorMessageEl = this.paymentContainer.querySelector('.payment-error-message');

    // Show processing state
    if (submitButton) submitButton.disabled = true;
    if (processingEl) processingEl.classList.remove('hidden');
    if (successEl) successEl.classList.add('hidden');
    if (errorEl) errorEl.classList.add('hidden');

    try {
      // Create payment method
      const { error, paymentMethod } = await this.stripe.createPaymentMethod({
        type: 'card',
        card: this.card
      });

      if (error) {
        throw new Error(error.message);
      }

      // Use payment service to purchase the game
      // In a real implementation, you would pass the payment method to the server
      // For demo purposes, we'll just use the paymentService directly
      const result = await window.paymentService.purchaseGame(gameInfo.id);

      if (!result.success) {
        throw new Error(result.message || 'Payment failed');
      }

      // Show success state
      if (processingEl) processingEl.classList.add('hidden');
      if (successEl) successEl.classList.remove('hidden');

      // Close form after a delay
      setTimeout(() => {
        this.closePaymentForm();
        if (onSuccess) onSuccess(result);
      }, 2000);

      // Show success notification
      if (window.notifications) {
        window.notifications.success(`Successfully purchased ${gameInfo.name}`);
      }
    } catch (error) {
      console.error('Payment error:', error);

      // Show error state
      if (processingEl) processingEl.classList.add('hidden');
      if (errorEl) errorEl.classList.remove('hidden');
      if (errorMessageEl) errorMessageEl.textContent = error.message || 'Payment failed. Please try again.';

      // Re-enable submit button
      if (submitButton) submitButton.disabled = false;

      // Show error notification
      if (window.notifications) {
        window.notifications.error(`Payment failed: ${error.message}`);
      }
    }
  }

  /**
   * Show subscription payment form
   * @param {Object} planInfo - Subscription plan information
   * @param {string} planInfo.name - Plan name
   * @param {number} planInfo.price - Plan price in cents
   * @param {string} planInfo.interval - Billing interval (month, year)
   * @param {Function} onSuccess - Callback on successful payment
   * @param {Function} onCancel - Callback on cancelled payment
   */
  async showSubscriptionForm(planInfo, onSuccess, onCancel) {
    // Initialize if needed
    if (!this.isInitialized && !(await this.initialize())) {
      if (window.notifications) {
        window.notifications.error('Failed to initialize payment system');
      }
      return;
    }

    // Create payment container if it doesn't exist
    if (!this.paymentContainer) {
      this.paymentContainer = document.createElement('div');
      this.paymentContainer.className = 'payment-container fixed inset-0 bg-gaming-gray-900 bg-opacity-90 flex items-center justify-center z-50 hidden';
      document.body.appendChild(this.paymentContainer);
    }

    // Render payment form
    this.paymentContainer.innerHTML = `
      <div class="payment-modal relative max-w-lg w-full rounded-lg overflow-hidden">
        <button type="button" class="payment-close absolute top-3 right-3 text-gaming-gray-400 hover:text-gaming-gray-200">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        ${this.paymentFormTemplate}
      </div>
    `;

    // Update payment form details
    const formTitle = this.paymentContainer.querySelector('.payment-title');
    const subtotalEl = this.paymentContainer.querySelector('.payment-subtotal');
    const taxEl = this.paymentContainer.querySelector('.payment-tax');
    const totalEl = this.paymentContainer.querySelector('.payment-total');
    const submitButton = this.paymentContainer.querySelector('#payment-submit');

    if (formTitle) formTitle.textContent = `Subscribe to ${planInfo.name}`;

    // Format prices
    const subtotal = (planInfo.price / 100).toFixed(2);
    const tax = 0; // We'll assume no tax for simplicity
    const total = (planInfo.price / 100).toFixed(2);

    if (subtotalEl) subtotalEl.textContent = `$${subtotal}/${planInfo.interval}`;
    if (taxEl) taxEl.textContent = `$${tax.toFixed(2)}`;
    if (totalEl) totalEl.textContent = `$${total}/${planInfo.interval}`;
    if (submitButton) submitButton.textContent = `Subscribe Now`;

    // Show container
    this.paymentContainer.classList.remove('hidden');

    // Initialize Stripe Elements
    const cardElement = this.paymentContainer.querySelector('#card-element');
    if (cardElement) {
      this.createElements(cardElement);
    }

    // Add event listeners
    const closeButton = this.paymentContainer.querySelector('.payment-close');

    if (closeButton) {
      closeButton.addEventListener('click', () => {
        this.closePaymentForm();
        if (onCancel) onCancel();
      });
    }

    if (submitButton) {
      submitButton.addEventListener('click', async () => {
        await this.processSubscription(planInfo, onSuccess);
      });
    }
  }

  /**
   * Process subscription payment
   * @param {Object} planInfo - Subscription plan information
   * @param {Function} onSuccess - Callback on successful payment
   */
  async processSubscription(planInfo, onSuccess) {
    const submitButton = this.paymentContainer.querySelector('#payment-submit');
    const processingEl = this.paymentContainer.querySelector('#payment-processing');
    const successEl = this.paymentContainer.querySelector('#payment-success');
    const errorEl = this.paymentContainer.querySelector('#payment-error');
    const errorMessageEl = this.paymentContainer.querySelector('.payment-error-message');

    // Show processing state
    if (submitButton) submitButton.disabled = true;
    if (processingEl) processingEl.classList.remove('hidden');
    if (successEl) successEl.classList.add('hidden');
    if (errorEl) errorEl.classList.add('hidden');

    try {
      // Create payment method
      const { error, paymentMethod } = await this.stripe.createPaymentMethod({
        type: 'card',
        card: this.card
      });

      if (error) {
        throw new Error(error.message);
      }

      // Use payment service to create subscription
      // In a real implementation, you would pass the payment method to the server
      // For demo purposes, we'll just use the paymentService directly
      const result = await window.paymentService.createSubscription();

      if (!result.success) {
        throw new Error(result.message || 'Subscription failed');
      }

      // Show success state
      if (processingEl) processingEl.classList.add('hidden');
      if (successEl) successEl.classList.remove('hidden');

      // Close form after a delay
      setTimeout(() => {
        this.closePaymentForm();
        if (onSuccess) onSuccess(result);
      }, 2000);

      // Show success notification
      if (window.notifications) {
        window.notifications.success(`Successfully subscribed to ${planInfo.name}`);
      }
    } catch (error) {
      console.error('Subscription error:', error);

      // Show error state
      if (processingEl) processingEl.classList.add('hidden');
      if (errorEl) errorEl.classList.remove('hidden');
      if (errorMessageEl) errorMessageEl.textContent = error.message || 'Subscription failed. Please try again.';

      // Re-enable submit button
      if (submitButton) submitButton.disabled = false;

      // Show error notification
      if (window.notifications) {
        window.notifications.error(`Subscription failed: ${error.message}`);
      }
    }
  }

  /**
   * Close payment form
   */
  closePaymentForm() {
    if (this.paymentContainer) {
      this.paymentContainer.classList.add('hidden');
    }

    // Clean up Stripe Elements
    if (this.card) {
      this.card.destroy();
      this.card = null;
    }
  }

  /**
   * Get card element for external use
   * @returns {Object} Stripe card element
   */
  getCardElement() {
    return this.card;
  }
}

// Initialize payment UI
document.addEventListener('DOMContentLoaded', () => {
  const paymentUI = new PaymentUI();

  // Expose to global scope
  window.paymentUI = paymentUI;

  // Initialize when Stripe.js is loaded
  if (window.Stripe) {
    paymentUI.initialize();
  } else {
    // Add event listener for Stripe.js loaded
    document.addEventListener('stripe-loaded', () => {
      paymentUI.initialize();
    });
  }
});
