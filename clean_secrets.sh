#!/bin/bash

# Script to clean sensitive information from repository

# Create a new branch for cleaning
git checkout -b clean-repo

# List of files to check and clean
FILES_TO_CLEAN=(
  ".env.example"
  "config/.env.example"
  "scripts/payment-test.php"
  "scripts/trial-to-paid-test.php"
  "scripts/webhook-test.php"
  "scripts/test-oauth.sh"
)

# Patterns to replace with placeholders
declare -A PATTERNS
PATTERNS=(
  ["your_stripe_secret_key"]="your_stripe_secret_key"
  ["your_stripe_publishable_key"]="your_stripe_publishable_key"
  ["your_stripe_webhook_secret"]="your_stripe_webhook_secret"
  ["your_google_client_id"]="your_google_client_id"
  ["your_google_client_secret"]="your_google_client_secret"
  ["your_playstation_npsso_token"]="your_playstation_npsso_token"
  ["your_steam_api_key"]="your_steam_api_key"
  ["your_openai_api_key"]="your_openai_api_key"
  ["your_openai_api_key"]="your_openai_api_key"
  ["your_github_client_id"]="your_github_client_id"
  ["your_github_client_secret"]="your_github_client_secret"
)

echo "Cleaning sensitive information from files..."

for file in "${FILES_TO_CLEAN[@]}"; do
  if [ -f "$file" ]; then
    echo "Processing $file..."
    
    # Create a backup
    cp "$file" "${file}.bak"
    
    # Replace patterns
    for pattern in "${!PATTERNS[@]}"; do
      replacement="${PATTERNS[$pattern]}"
      sed -i '' "s/$pattern/$replacement/g" "$file"
    done
    
    # Check if changes were made
    if cmp -s "$file" "${file}.bak"; then
      echo "No changes needed in $file"
      rm "${file}.bak"
    else
      echo "Cleaned $file"
      rm "${file}.bak"
      git add "$file"
    fi
  else
    echo "File $file does not exist, skipping..."
  fi
done

# Commit changes
git commit -m "Remove sensitive information from repository files"

echo "Done! Now run 'git push origin clean-repo' and create a PR to merge these changes."
echo "After merging, you'll need to unblock the secrets on GitHub or use 'git filter-branch' to completely remove them from history." 