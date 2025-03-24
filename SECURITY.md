# Security Policy

## Reporting Security Issues

If you discover a security issue in FridayAI, please report it by sending an email to security@fridayai.me. This will allow us to assess the risk and make a fix available before we add a bug report to the GitHub repository.

Thanks for helping make FridayAI safe for everyone.

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