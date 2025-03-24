# Security Policy

## Reporting Security Issues

If you discover a security vulnerability in this project, please report it by sending an email to security@fridayai.me. Please provide a detailed description of the issue, steps to reproduce, and if possible, a potential solution.

## Handling Credentials and API Keys

### API Keys and Secrets

Never commit real API keys, tokens, or other sensitive information to this repository. Always use placeholder values in example files.

### Environment Variables
- Use environment variables to store sensitive configurations
- Copy `.env.example` to `.env` and add your real keys to the `.env` file
- `.env` files are already ignored by git (check `.gitignore`) 
- `.env.example` files should only contain placeholder values like `your_api_key_here`

### Pre-commit Hooks

Consider using pre-commit hooks to prevent accidental commits of sensitive data:

1. Install the pre-commit framework: `pip install pre-commit`
2. Create a `.pre-commit-config.yaml` file with rules for detecting secrets
3. Review code changes carefully before committing to ensure no secrets are included

## Accessing Resources

- For team members who need access to the real API keys and credentials, contact the project administrator
- Each developer should maintain their own `.env` file locally
- Production credentials are managed separately and deployed securely to hosting environments

## Best Practices

1. **Rotate credentials regularly**: Change API keys and secrets periodically
2. **Use secret management services**: For production environments, use services like AWS Secrets Manager or HashiCorp Vault
3. **Limit access scope**: Give APIs the minimum permissions necessary
4. **Monitor usage**: Regularly check API usage for suspicious activities

## Sensitive Information

### API Keys and Secrets

Never commit real API keys, tokens, or other sensitive information to this repository. Always use placeholder values in example files.

### Environment Files

- `.env` files are listed in `.gitignore` and should never be committed
- `.env.example` files should only contain placeholder values like `your_api_key_here`

## Development Best Practices

1. Use environment variables for all sensitive information
2. Never hardcode credentials in any file that will be committed
3. Review code changes carefully before committing to ensure no secrets are included
4. If you accidentally commit sensitive information, contact the repository administrators immediately

## Dependency Security

We regularly update dependencies to ensure we're not using packages with known vulnerabilities. If you notice outdated dependencies with security issues, please open a pull request with the necessary updates. 