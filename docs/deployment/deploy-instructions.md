# Production Deployment Instructions

## Setting Environment Variables in Production

When deploying to production, you need to ensure that your environment variables are properly set. Here's how to do it:

### 1. Setting APP_ENV to Production

First, ensure that `APP_ENV=production` is set in your production environment. This is crucial for the dynamic URL resolution to work correctly.

### 2. Heroku Deployment Instructions

1. **Set Environment Variables**
```bash
# Set all environment variables
heroku config:set APP_ENV=production
heroku config:set GOOGLE_CLIENT_ID=your_client_id
heroku config:set GOOGLE_CLIENT_SECRET=your_client_secret
heroku config:set GITHUB_CLIENT_ID=your_github_client_id
heroku config:set GITHUB_CLIENT_SECRET=your_github_client_secret
heroku config:set STRIPE_PUBLIC_KEY=your_stripe_public_key
heroku config:set STRIPE_SECRET_KEY=your_stripe_secret_key
heroku config:set STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret
# ...and so on for all variables
```

2. **Domain Configuration**
- Add your custom domain: `heroku domains:add your-domain.com`
- Enable automatic SSL: `heroku certs:auto:enable`
- Update your DNS settings with the provided Heroku DNS target

3. **Deployment**
```bash
# Deploy your code
git push heroku main
```

4. **Verify Deployment**
```bash
# Check app status
heroku ps
# View logs
heroku logs --tail
```

### 3. Using a Configuration Management Tool

For complex deployments, consider using a configuration management tool like:
- AWS Parameter Store
- HashiCorp Vault
- Google Secret Manager

These tools help securely manage environment variables across different environments.

### 4. Verifying the Environment

After deployment, verify that your application is working correctly:
1. Test the application at your custom domain
2. Verify SSL certificate is active
3. Check all OAuth logins are working
4. Test Stripe integration
5. Monitor application logs

## Important Security Notes

1. **Never commit .env files to version control**
2. **Rotate secrets regularly**
3. **Use secret management services for production**
4. **Restrict access to your environment variables to only necessary personnel**
