# Production Deployment Instructions

## Setting Environment Variables in Production

When deploying to production, you need to ensure that your environment variables are properly set. Here's how to do it on different platforms:

### 1. Setting APP_ENV to Production

First, ensure that `APP_ENV=production` is set in your production environment. This is crucial for the dynamic URL resolution to work correctly.

### 2. Cloud Provider Instructions

#### Heroku

```bash
# Set all environment variables
heroku config:set APP_ENV=production
heroku config:set GOOGLE_CLIENT_ID=your_client_id
heroku config:set GOOGLE_CLIENT_SECRET=your_client_secret
# ...and so on for all variables
```

#### AWS Elastic Beanstalk

- Go to AWS Console > Elastic Beanstalk > Your Environment
- Click on "Configuration" > "Software"
- Scroll to "Environment properties" and add all your variables
- Ensure APP_ENV is set to "production"

#### Google Cloud Run

```bash
gcloud run services update your-service-name --set-env-vars APP_ENV=production,GOOGLE_CLIENT_ID=your_client_id,GOOGLE_CLIENT_SECRET=your_client_secret
```

#### Docker

```bash
docker run -e APP_ENV=production -e GOOGLE_CLIENT_ID=your_client_id -e GOOGLE_CLIENT_SECRET=your_client_secret ...your-image-name
```

### 3. Using a Configuration Management Tool

For complex deployments, consider using a configuration management tool like:
- AWS Parameter Store
- HashiCorp Vault
- Google Secret Manager

These tools help securely manage environment variables across different environments.

### 4. Verifying the Environment

After deployment, verify that the environment variables are correctly set:

```javascript
// Add this to a route that only you can access
app.get('/debug-env', (req, res) => {
  if (process.env.APP_ENV !== 'production') {
    res.json({
      environment: process.env.APP_ENV,
      googleRedirectUri: process.env.GOOGLE_REDIRECT_URI,
      // Include other non-sensitive variables to verify
    });
  } else {
    res.status(403).send('Not available in production');
  }
});
```

## Important Security Notes

1. **Never commit .env files to version control**
2. **Rotate secrets regularly**
3. **Use secret management services for production**
4. **Restrict access to your environment variables to only necessary personnel** 