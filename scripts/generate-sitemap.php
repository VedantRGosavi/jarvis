<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\SitemapGenerator;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get the base URL from environment variable or use a default
$baseUrl = getenv('APP_URL') ?: 'https://fridayai.com';

// Initialize sitemap generator
$generator = new SitemapGenerator($baseUrl);

// Add dynamic pages if needed
// Example: Add blog posts or other dynamic content
// foreach ($blogPosts as $post) {
//     $generator->addPage("/blog/{$post->slug}", '0.7', 'weekly');
// }

// Generate the sitemap
$generator->generate();

echo "Sitemap generated successfully!\n";
