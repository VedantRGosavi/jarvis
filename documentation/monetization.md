# FridayAI Gaming Assistant - Monetization Strategy

## Current Implementation (MVP)

### Core Products

1. **Individual Game Data Packs** ($5.99 each, one-time purchase)
   - Elden Ring Data Pack (prod_Rzrfd6mQejXRe9)
   - Baldur's Gate 3 Data Pack (prod_Rzrf1wcie1AQVY)
   - Kingdom Come: Deliverance Data Pack (prod_RzrfcbcODmPqJl)

   Each pack includes:
   - Complete quest information
   - Item locations
   - Game mechanics
   - Basic overlay functionality

2. **Premium Add-ons**
   - Priority Support ($3.99, one-time) - prod_Rzrf0SzbiPBvvb
     * 24-hour response guarantee
     * Direct email support
   - Overlay Themes ($2.49 each, one-time) - prod_RzrhhjtllZF7Rq
     * Custom interface themes
     * Personalized overlay appearance
   - Premium Features ($3.99 per game, one-time) - prod_RzrhqzSsxy1lX8
     * Detailed maps
     * Voice narration
     * Advanced game mechanics

3. **Trial Access**
   - 7-day trial ($0.99, non-refundable) - prod_Rzrfix2z3JKFtR
   - Access to all core features
   - Converts to individual purchase options after trial

4. **Additional Game Data Packs**
   - Template product for future games (prod_RzrgDzKd2Ou4Tc)
   - $5.99 each, one-time purchase
   - Same feature set as core game packs

### Integration Details

Each product includes metadata for:
- Product category (core, addon, trial)
- Feature flags
- Game relationships (for game-specific add-ons)
- Integration identifiers

### Future Considerations (Not in MVP)

1. **Subscription Model**
   - Monthly and annual subscription options (to be implemented)
   - Bundle discounts for multiple games
   - Automatic access to new game packs

2. **Community Marketplace**
   - User-submitted game data packs
   - Revenue sharing model (70/30 split)
   - Creator access program

### Value Proposition

1. **Transparent Pricing**
   - Clear one-time purchase structure
   - No hidden fees
   - Trial option for testing

2. **Flexible Purchase Options**
   - Individual game packs
   - Optional premium features
   - Customization options

3. **Quality Assurance**
   - Regular updates
   - Accurate game information
   - Professional support options

### Technical Implementation

All products are implemented in Stripe with:
- Unique product IDs
- Clear descriptions
- One-time payment prices
- Integration metadata
- Feature flags

For integration code examples and product IDs, refer to the API documentation.

Stripe Setup :
Your setup
Funds flow
Buyers will purchase from you
Sellers will be paid out individually


Account creation
Onboarding hosted by Stripe

Account management
You'll redirect sellers to the Express Dashboard to manage their Stripe account

Liability for refunds and chargebacks
You are responsible for refunds and chargebacks to buyers. Learn more.
