<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\SitemapGenerator;
use App\Utils\SitemapIndexGenerator;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Get the base URL from environment variable or use a default
$baseUrl = getenv('APP_URL') ?: 'https://fridayai.com';

// Generate main pages sitemap
$mainSitemap = new SitemapGenerator($baseUrl, 'public/sitemap-main.xml');
$mainSitemap->addPage('/', '1.0', 'daily');
$mainSitemap->addPage('/about', '0.9', 'weekly');
$mainSitemap->addPage('/contact', '0.8', 'weekly');
$mainSitemap->addPage('/login', '0.8', 'weekly');
$mainSitemap->addPage('/register', '0.8', 'weekly');
$mainSitemap->addPage('/terms', '0.7', 'monthly');
$mainSitemap->addPage('/privacy', '0.7', 'monthly');
$mainSitemap->generate();

// Generate games sitemap (assuming you have games)
$gamesSitemap = new SitemapGenerator($baseUrl, 'public/sitemap-games.xml');
$gamesSitemap->addPage('/games', '0.9', 'daily');
// TODO: Add dynamic game pages here
// Example: foreach ($games as $game) {
//    $gamesSitemap->addPage("/games/{$game->slug}", '0.8', 'weekly');
// }
$gamesSitemap->generate();

// Generate payment pages sitemap
$paymentSitemap = new SitemapGenerator($baseUrl, 'public/sitemap-payment.xml');
$paymentSitemap->addPage('/payment/success', '0.6', 'monthly');
$paymentSitemap->addPage('/payment/cancel', '0.6', 'monthly');
$paymentSitemap->generate();

// Generate sitemap index that includes all sitemaps
$sitemapIndex = new SitemapIndexGenerator($baseUrl);
$sitemapIndex->addSitemap('sitemap-main.xml');
$sitemapIndex->addSitemap('sitemap-games.xml');
$sitemapIndex->addSitemap('sitemap-payment.xml');
$sitemapIndex->generate();

// Update the main sitemap.xml to redirect to the index
$indexRedirect = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$indexRedirect .= '<!-- This is a redirect to the sitemap index -->' . PHP_EOL;
$indexRedirect .= '<?xml-stylesheet type="text/xsl" href="sitemap.xsl"?>' . PHP_EOL;
$indexRedirect .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
$indexRedirect .= '  <sitemap>' . PHP_EOL;
$indexRedirect .= '    <loc>' . htmlspecialchars($baseUrl . '/sitemap-index.xml') . '</loc>' . PHP_EOL;
$indexRedirect .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
$indexRedirect .= '  </sitemap>' . PHP_EOL;
$indexRedirect .= '</sitemapindex>';

file_put_contents('public/sitemap.xml', $indexRedirect);

echo "Specialized sitemaps and sitemap index generated successfully!\n";
