# Stripe Payment Integration Tests

This directory contains test scripts for verifying Stripe integration with our payment system. These tests use live Stripe keys but with test data to verify the entire payment flow from customer creation to subscription management and webhook events.

## Important Notes

- These tests use **live Stripe keys** from your `.env` file but utilize test card numbers to avoid actual charges
- The tests create real test data in your Stripe account that you may want to clean up afterwards
- You must have a valid Stripe account with API keys configured in your `.env` file

## Requirements

- PHP 8.0+ with curl and json extensions
- A `.env` file with the following variables:
  - `STRIPE_SECRET_KEY`: Your Stripe secret key
  - `STRIPE_PUBLISHABLE_KEY`: Your Stripe publishable key
  - `STRIPE_WEBHOOK_SECRET`: Your Stripe webhook secret key
  - `STRIPE_SUBSCRIPTION_PRICE_ID` (Optional): ID of a subscription price to use for testing

## Running the Tests

### All Tests at Once

To run all tests in sequence with detailed reporting:

```
./scripts/run-payment-tests.sh
```

### Individual Tests

#### Basic Stripe Integration Test

Tests customer creation, subscription with trial, one-time purchase, and basic webhook event handling:

```
php scripts/payment-test.php
```

> Note: This test will create a test product and price if no active subscription price is found in your Stripe account.

#### Trial to Paid Subscription Test

Tests the user journey from trial to paid subscription:

```
php scripts/trial-to-paid-test.php
```

#### Webhook Event Handling Test

Tests critical webhook events are properly processed:

```
php scripts/webhook-test.php
```

## Test Data

The tests create the following data in your Stripe account:

- Test customers with email addresses containing timestamps (e.g., `test-1647890123@example.com`)
- Test products and prices (if needed)
- Test subscriptions with trial periods
- Test payment intents for one-time purchases

All test data is marked with a `test: true` metadata field for easy identification.

## Cleanup

By default, the tests do not delete the test data to allow for manual inspection. To clean up:

1. Log in to your Stripe Dashboard
2. Navigate to Customers section
3. Filter by the email addresses generated during the tests (they contain timestamps)
4. Delete the test customers (this will also delete associated subscriptions and payment methods)

## Troubleshooting

### Database Issues

All tests now use direct database insertion to create test users, which should work regardless of your application's user model. The tests will:

1. Check your database structure
2. Dynamically build SQL statements based on available columns
3. Create test users with appropriate data

If you encounter database issues, check:
- Database connection settings in `app/utils/Database.php`
- Table structure in your database

### Stripe API Issues

If you encounter Stripe API issues:
- Verify your Stripe API keys are correct and active
- Ensure your Stripe account is properly configured
- Check the Stripe Dashboard for potential account restrictions

## Webhook Testing in Development

For local webhook testing, use the Stripe CLI to forward events:

```bash
stripe listen --forward-to http://localhost:8000/api/webhook
```

Then run the webhook test to simulate events:
