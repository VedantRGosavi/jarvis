// Pricing tiers for the FridayAI gaming companion
const PRICING_TIERS = [
    {
        name: "Game Pack",
        price: {
            monthly: 5.99,
            yearly: null, // One-time purchase
        },
        description: "Complete game data pack with essential features",
        features: [
            "Complete quest information",
            "Item locations",
            "Game mechanics",
            "Basic overlay functionality",
            "Regular updates"
        ],
        cta: "Get Pack",
        icon: "gamepad",
        type: "one-time"
    },
    {
        name: "Premium Features",
        price: {
            monthly: 3.99,
            yearly: null, // One-time purchase
        },
        description: "Enhanced features for a single game",
        features: [
            "Detailed maps",
            "Voice narration",
            "Advanced game mechanics",
            "Priority email support",
            "Custom interface themes"
        ],
        cta: "Upgrade Now",
        popular: true,
        icon: "star",
        type: "one-time"
    },
    {
        name: "Trial Access",
        price: {
            monthly: 0.99,
            yearly: null, // One-time purchase
        },
        description: "7-day trial access to all features",
        features: [
            "Access to all core features",
            "Try before you buy",
            "All game packs included",
            "Premium features enabled",
            "7-day duration",
            "Non-refundable"
        ],
        cta: "Start Trial",
        highlighted: true,
        icon: "clock",
        type: "one-time"
    }
];

// Export for use in main.js
export { PRICING_TIERS }; 