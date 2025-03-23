// Improved Pricing component for FridayAI
class PricingSection {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            title: options.title || "Choose Your Gaming Pack",
            subtitle: options.subtitle || "Enhance your gaming experience with our specialized tools",
            tiers: options.tiers || [],
            theme: options.theme || {
                primary: "gaming-light",
                secondary: "gaming-gray-300",
                background: "gaming-gray-900",
                cardBackground: "gaming-gray-800",
                buttonText: "gaming-gray-900",
                borderColor: "gaming-gray-700",
            },
            animation: options.animation || {
                enabled: true,
                duration: 300,
                delay: 100,
            },
            onPurchase: options.onPurchase || null
        };
        
        if (!this.container) {
            console.error(`Container with ID "${containerId}" not found.`);
            return;
        }
        
        this.render();
        this.setupListeners();
    }

    render() {
        // Create section structure
        const sectionContent = `
            <div class="relative overflow-hidden bg-${this.options.theme.background} py-24">
                <div class="absolute top-0 left-0 w-full h-full opacity-10">
                    <div class="absolute -top-24 -left-24 w-96 h-96 bg-purple-500 rounded-full filter blur-3xl"></div>
                    <div class="absolute top-1/2 right-0 w-80 h-80 bg-blue-500 rounded-full filter blur-3xl"></div>
                </div>
            
                <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-4xl font-bold tracking-tight text-${this.options.theme.primary} sm:text-5xl fade-in-element">
                            ${this.options.title}
                        </h2>
                        <p class="mt-6 text-xl text-${this.options.theme.secondary} max-w-2xl mx-auto fade-in-element">
                            ${this.options.subtitle}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-10 md:grid-cols-3 fade-in-element">
                        ${this.renderPricingCards()}
                    </div>
                </div>
            </div>
        `;

        this.container.innerHTML = sectionContent;
        
        // Trigger animation on load
        if (this.options.animation.enabled) {
            const elements = this.container.querySelectorAll('.fade-in-element');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.add('visible');
                }, this.options.animation.delay * (index + 1));
            });
        }
    }

    renderPricingCards() {
        return this.options.tiers.map(tier => {
            const price = tier.price.monthly;
            const isHighlighted = tier.highlighted;
            const isPopular = tier.popular;
            const iconClass = this.getIconClass(tier.icon);

            // Determine card-specific classes based on tier properties
            const cardClasses = [
                "pricing-card",
                "relative",
                "flex",
                "flex-col",
                "h-full",
                "overflow-hidden",
                "rounded-2xl",
                "border",
                `border-${this.options.theme.borderColor}`,
                `bg-${this.options.theme.cardBackground}/50`,
                "backdrop-blur-sm",
                "transition-all",
                "duration-300",
                "hover:transform",
                "hover:scale-[1.02]",
                "hover:shadow-2xl",
            ];
            
            // Add a glow effect for highlighted cards
            if (isHighlighted) {
                cardClasses.push(`ring-2`);
                cardClasses.push(`ring-${this.options.theme.primary}`);
                cardClasses.push(`ring-opacity-60`);
            }

            return `
                <div class="${cardClasses.join(' ')}">
                    ${isHighlighted ? `<div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-blue-500/10 rounded-2xl"></div>` : ''}
                    <div class="relative p-8">
                        <div class="flex items-start justify-between mb-8">
                            <div>
                                <h3 class="text-2xl font-bold text-${this.options.theme.primary}">${tier.name}</h3>
                                <p class="mt-2 text-sm text-${this.options.theme.secondary}">${tier.description}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-${this.options.theme.cardBackground}/80">
                                <i class="${iconClass} text-${this.options.theme.primary} text-xl"></i>
                            </div>
                        </div>

                        <div class="my-8 pb-8 border-b border-${this.options.theme.borderColor}/30">
                            <div class="flex items-baseline gap-2">
                                <span class="text-5xl font-bold text-${this.options.theme.primary}">$${typeof price === 'number' ? price.toFixed(2) : price}</span>
                                <span class="text-sm text-${this.options.theme.secondary}">${tier.type === 'one-time' ? 'one-time' : '/mo'}</span>
                            </div>
                        </div>

                        <div class="flex-grow mb-8">
                            <h4 class="text-sm font-semibold uppercase tracking-wider text-${this.options.theme.primary} mb-4">What's included</h4>
                            <ul class="space-y-4">
                                ${tier.features.map(feature => `
                                    <li class="flex items-start gap-3">
                                        <span class="flex-shrink-0 rounded-full p-1 bg-${this.options.theme.primary}/20">
                                            <svg class="h-4 w-4 text-${this.options.theme.primary}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                        <span class="text-${this.options.theme.secondary}">${feature}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>

                        <div class="mt-8">
                            <button class="w-full px-6 py-3 rounded-lg bg-${this.options.theme.primary} text-${this.options.theme.buttonText} font-medium transition-all duration-200 hover:bg-opacity-90 hover:transform hover:translate-y-[-1px] focus:outline-none focus:ring-2 focus:ring-${this.options.theme.primary} focus:ring-opacity-50 border-2 border-white">
                                ${tier.cta}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    getIconClass(icon) {
        const iconMap = {
            'gamepad': 'fas fa-gamepad',
            'star': 'fas fa-star',
            'clock': 'fas fa-clock',
            'trophy': 'fas fa-trophy',
            'bolt': 'fas fa-bolt',
            'crown': 'fas fa-crown',
            'default': 'fas fa-gamepad'
        };
        return iconMap[icon] || iconMap.default;
    }

    setupListeners() {
        if (!this.container) return;

        // Purchase button clicks
        this.container.querySelectorAll('.pricing-card button').forEach((button, index) => {
            button.addEventListener('click', () => {
                const tier = this.options.tiers[index];
                this.handlePurchase(tier);
            });
        });

        // Card hover effect enhancement
        this.container.querySelectorAll('.pricing-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.classList.add('is-hovered');
            });
            
            card.addEventListener('mouseleave', () => {
                card.classList.remove('is-hovered');
            });
        });
    }

    handlePurchase(tier) {
        // Here you would typically integrate with a payment processor
        console.log(`User selected the ${tier.name} pack`);
        
        // Implement custom purchase handling logic
        if (typeof this.options.onPurchase === 'function') {
            this.options.onPurchase(tier);
        } else {
            // For demonstration purposes
            alert(`Thank you for choosing the ${tier.name}!\nThis would typically redirect to a payment processor.`);
        }
    }
}

// Export for use in main.js
export { PricingSection };