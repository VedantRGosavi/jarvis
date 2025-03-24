#!/bin/bash

# Navigate to the project root directory
# Replace with the actual path to your project if needed
PROJECT_DIR=$(dirname "$(dirname "$(readlink -f "$0")")")
cd "$PROJECT_DIR"

# Run the sitemap generator scripts
php scripts/generate-sitemap.php
php scripts/generate-specialized-sitemaps.php

# Submit sitemaps to search engines
curl "https://www.google.com/ping?sitemap=https://fridayai.com/sitemap.xml"
curl "https://www.bing.com/ping?sitemap=https://fridayai.com/sitemap.xml"

# Log the execution
echo "Sitemaps generated at $(date)" >> logs/sitemap-generation.log
