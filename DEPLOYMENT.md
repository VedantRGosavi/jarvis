# FridayAI Deployment Guide

## Static Website Deployment (Vercel)

The static portion of FridayAI is deployed on Vercel. Follow these steps to update the deployment:

1. Build the Tailwind CSS:
   ```
   npm run build:css
   ```

2. Deploy to Vercel:
   ```
   vercel --prod
   ```

## Custom Domain Setup

FridayAI uses the domain `fridayai.me`. To configure the domain:

1. Add the domain to Vercel:
   ```
   vercel domains add fridayai.me --scope fruitcademy
   vercel domains add www.fridayai.me --scope fruitcademy
   ```

2. Configure DNS at your domain registrar:
   - Add an A record for `fridayai.me` pointing to `76.76.21.21`
   - Add an A record for `www.fridayai.me` pointing to `76.76.21.21`

## Environment Variables

To set up environment variables for the project:

1. Run the environment variable helper script:
   ```
   node scripts/update-env.js
   ```

2. Follow the instructions to add each environment variable to Vercel.

## Backend API Setup Options

Since Vercel doesn't natively support PHP, consider one of these options for the API:

### Option 1: Shared Hosting for PHP Backend
1. Upload the PHP files to a shared hosting provider that supports PHP 7.4+
2. Configure a subdomain like `api.fridayai.me` pointing to the shared hosting
3. Update the frontend to reference the API at `https://api.fridayai.me/api/...`

### Option 2: Convert to Serverless Functions
1. Rewrite PHP endpoints as JavaScript/TypeScript serverless functions
2. Add these under `/api` directory in the Vercel project
3. Update vercel.json to include API routes

### Option 3: Separate API Service
1. Deploy the PHP backend to a service like DigitalOcean, Linode, or Heroku
2. Configure CORS to allow requests from `fridayai.me`
3. Update the frontend to reference the API at the new location

## Database Configuration

If using SQLite databases:
1. For Vercel: Convert to a serverless database like Supabase or PlanetScale
2. For separate hosting: Ensure the database directory is writable
