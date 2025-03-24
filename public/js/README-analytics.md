# FridayAI Analytics Implementation

This document describes how the analytics tracking system is implemented in FridayAI.

## Overview

The analytics system uses Google Analytics 4 (GA4) to track user behavior, conversion events, and customer journeys. It includes:

- Page view tracking
- Event tracking for user interactions
- Conversion tracking for downloads and subscriptions
- User journey funnel analysis
- Cross-device user tracking

## Files

- `analytics.js` - The core analytics manager module
- `analytics-init.js` - Initializes analytics and adds DOM event listeners
- Environment variables in `.env` for configuration

## Configuration

Analytics IDs are stored in the `.env` file:

```
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
GOOGLE_TAG_MANAGER_ID=GTM-XXXXXXX
```

Replace these with the actual IDs from your Google Analytics account.

## User Journey Funnels

We've defined several funnels for analyzing user journeys:

1. **Acquisition Funnel**
   - `visit_home` → `view_features` → `view_pricing` → `create_account`

2. **Activation Funnel**
   - `create_account` → `confirm_email` → `subscribe`

3. **Engagement Funnel**
   - `login` → `visit_dashboard` → `download_app` → `launch_app`

4. **Conversion Funnel**
   - `view_pricing` → `click_subscribe` → `complete_payment` → `download_app`

## Tracked Events

### Page Views
- All page views are automatically tracked

### User Interaction Events
- `toggle_theme` - User switches between light/dark mode
- `search` - User performs a search
- `search_result_click` - User clicks on a search result
- `select_game` - User selects a game

### Conversion Events
- `download_initiated` - User starts downloading the application
- `download_complete` - Download is completed
- `click_subscribe_button` - User clicks on a subscription button
- `purchase` - User completes a purchase (subscription or one-time)
- `subscription_cancelled` - User cancels during the subscription process

## Implementation Details

### HTML Changes

The analytics system is initialized by adding two components to HTML pages:

1. A meta tag in the `<head>` section:
```html
<meta name="google-analytics-id" content="G-XXXXXXXXXX">
```

2. A script tag at the end of the `<body>` section:
```html
<script src="js/analytics-init.js" type="module"></script>
```

### Tracking Custom Events

To track custom events in your code, import the analytics manager and use its methods:

```javascript
import analyticsManager from './analytics.js';

// Track a simple event
analyticsManager.trackEvent('button_click', { button_id: 'my-button' });

// Track a conversion
analyticsManager.trackConversion('purchase', {
  transactionId: 'order123',
  value: 49.99,
  currency: 'USD'
});

// Track a funnel step
analyticsManager.trackFunnelStep('acquisition', 'view_features');
```

### User Identification

For cross-device tracking, we set user IDs when users log in:

```javascript
// Set user ID for cross-device tracking
analyticsManager.setUserId('user-123');
```

## Viewing Analytics Data

1. Log in to the [Google Analytics Console](https://analytics.google.com/)
2. Navigate to the FridayAI property
3. View reports in the "Reports" section
4. For funnel analysis, look under "Exploration" > "Funnel Analysis"

## Adding New Metrics

1. Update the `analytics.js` file to include new tracking methods
2. Add event listeners or manual tracking calls where needed
3. Update this documentation to include new metrics

## Best Practices

1. Don't add tracking that could compromise user privacy
2. Use descriptive, consistent event names
3. Include relevant parameters with events
4. Group related events into funnels
5. Test tracking in development before deploying
