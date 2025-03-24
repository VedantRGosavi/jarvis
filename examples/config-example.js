// Example configuration file that loads the correct environment variables

require('dotenv').config(); // Load .env file

// Helper function to evaluate the environment-specific variables
const resolveEnvVar = (varName) => {
  // For variables that are directly set using the ternary in the .env file
  // This isn't strictly necessary as they're already resolved in the .env file,
  // but it's a good fallback in case the syntax in .env changes
  if (process.env[varName] && !process.env[varName].includes('?')) {
    return process.env[varName];
  }

  // Handle the case where the variable isn't properly resolved in .env
  const isProd = process.env.APP_ENV === 'production';
  const prodVarName = `${varName}_PROD`;
  const devVarName = `${varName}_DEV`;

  return isProd ? process.env[prodVarName] : process.env[devVarName];
};

// Export configuration object
module.exports = {
  // Application
  appName: process.env.APP_NAME || 'FridayAI',
  environment: process.env.APP_ENV || 'development',
  isProduction: process.env.APP_ENV === 'production',
  debug: process.env.APP_DEBUG === 'true',

  // Frontend URLs
  frontendUrl: resolveEnvVar('FRONTEND_URL') || (process.env.APP_ENV === 'production'
    ? process.env.PROD_FRONTEND_URL
    : process.env.DEV_FRONTEND_URL),

  // OAuth Configuration
  oauth: {
    google: {
      clientId: process.env.GOOGLE_CLIENT_ID,
      clientSecret: process.env.GOOGLE_CLIENT_SECRET,
      redirectUri: resolveEnvVar('GOOGLE_REDIRECT_URI')
    },
    github: {
      clientId: process.env.GITHUB_CLIENT_ID,
      clientSecret: process.env.GITHUB_CLIENT_SECRET,
      redirectUri: resolveEnvVar('GITHUB_REDIRECT_URI')
    },
    playstation: {
      clientId: process.env.PLAYSTATION_CLIENT_ID,
      clientSecret: process.env.PLAYSTATION_CLIENT_SECRET,
      redirectUri: resolveEnvVar('PLAYSTATION_REDIRECT_URI')
    },
    steam: {
      clientId: process.env.STEAM_CLIENT_ID,
      apiKey: process.env.STEAM_API_KEY,
      redirectUri: resolveEnvVar('STEAM_REDIRECT_URI')
    }
  },

  // Webhook URL
  webhookUrl: resolveEnvVar('WEBHOOK_URL'),

  // Google Cloud Platform
  gcp: {
    projectId: process.env.GCP_PROJECT_ID,
    serviceAccountPath: process.env.GCP_SERVICE_ACCOUNT_PATH
  },

  // Stripe
  stripe: {
    publicKey: process.env.STRIPE_PUBLIC_KEY,
    secretKey: process.env.STRIPE_SECRET_KEY,
    connectAccountId: process.env.STRIPE_CONNECT_ACCOUNT_ID,
    webhookSecret: process.env.STRIPE_WEBHOOK_SECRET
  }
};
