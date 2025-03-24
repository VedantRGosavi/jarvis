# SEO Implementation Guide for FridayAI

This guide explains how to maintain and extend SEO features in the FridayAI application.

## Table of Contents
1. [Overview](#overview)
2. [Meta Tags](#meta-tags)
3. [Schema.org Markup](#schemaorg-markup)
4. [Advanced Schema Implementation](#advanced-schema-implementation)
5. [Sitemap Generation](#sitemap-generation)
6. [Specialized Sitemaps](#specialized-sitemaps)
7. [Adding SEO to New Pages](#adding-seo-to-new-pages)
8. [Deployment Process](#deployment-process)
9. [Best Practices](#best-practices)

## Overview

The SEO implementation for FridayAI includes:
- Dynamic meta tags for title, description, and keywords
- Open Graph meta tags for social media sharing
- Twitter Card meta tags
- Schema.org markup with multiple entity types
- Automated sitemap generation with specialized sections
- Human-readable XML sitemaps with XSL styling
- Breadcrumb navigation markup
- Organization and social media profile markup
- robots.txt configuration
- News sitemaps (if applicable)

## Meta Tags

All meta tags are implemented in the base layout file (`app/views/layout.php`). The layout supports dynamic meta information through PHP variables:

- `$pageTitle`: The title of the page
- `$pageDescription`: The description of the page (recommended 150-160 characters)
- `$pageKeywords`: Keywords for the page (comma-separated)
- `$pageImage`: Path to the image for social media sharing
- `$pageOgType`: Open Graph type (e.g., website, article, product)
- `$canonicalUrl`: Custom canonical URL (if needed)

## Schema.org Markup

Schema.org markup is implemented using JSON-LD format in the `app/views/layout.php` file. The default implementation uses the `SoftwareApplication` type and an `Organization` type for social profiles.

## Advanced Schema Implementation

The `SchemaGenerator` utility class (`app/utils/SchemaGenerator.php`) provides methods to generate various schema.org markup types:

1. **FAQ Schema**:
```php
use App\Utils\SchemaGenerator;

$faqItems = [
    ['question' => 'What is FridayAI?', 'answer' => 'FridayAI is an intelligent assistant...'],
    ['question' => 'How does it work?', 'answer' => 'It uses advanced AI technologies...']
];

$schemaMarkup = SchemaGenerator::generateFAQSchema($faqItems);
```

2. **Breadcrumb Schema**:
```php
$breadcrumbs = [
    ['name' => 'Home', 'url' => 'https://fridayai.com/'],
    ['name' => 'Games', 'url' => 'https://fridayai.com/games'],
    ['name' => 'Game Title', 'url' => 'https://fridayai.com/games/game-title']
];

$breadcrumbSchema = SchemaGenerator::generateBreadcrumbSchema($breadcrumbs);
```

3. **Article Schema**:
```php
$article = [
    'title' => 'How AI is Changing Gaming',
    'description' => 'Learn how artificial intelligence is revolutionizing gaming experiences...',
    'image' => 'https://fridayai.com/images/ai-gaming.jpg',
    'datePublished' => '2023-01-15',
    'dateModified' => '2023-02-01',
    'author' => ['name' => 'John Doe'],
    'publisher' => [
        'name' => 'FridayAI',
        'logo' => 'https://fridayai.com/images/logo.png'
    ]
];

$articleSchema = SchemaGenerator::generateArticleSchema($article);
```

4. **Organization Schema**:
```php
$organization = [
    'name' => 'FridayAI',
    'url' => 'https://fridayai.com',
    'logo' => 'https://fridayai.com/images/logo.png',
    'description' => 'FridayAI is your intelligent AI assistant...',
    'socialProfiles' => [
        'https://twitter.com/fridayai',
        'https://www.facebook.com/fridayai',
        'https://www.linkedin.com/company/fridayai'
    ],
    'contactPoint' => [
        'telephone' => '+1-800-555-1234',
        'contactType' => 'customer service'
    ]
];

$organizationSchema = SchemaGenerator::generateOrganizationSchema($organization);
```

## Sitemap Generation

The sitemap is automatically generated using the `SitemapGenerator` class in `app/utils/SitemapGenerator.php`. The generation script is located at `scripts/generate-sitemap.php`.

### Adding New Pages to the Sitemap

To add new pages to the sitemap:

1. Open `scripts/generate-sitemap.php` or `scripts/generate-specialized-sitemaps.php`
2. Add your new page using the `$generator->addPage()` method:

```php
$generator->addPage('/your-new-page', '0.8', 'weekly');
```

Parameters:
- URL path
- Priority (0.0 to 1.0)
- Change frequency (always, hourly, daily, weekly, monthly, yearly, never)

## Specialized Sitemaps

The site uses a sitemap index system that organizes content into different sitemap files:

- `sitemap-main.xml`: Main pages of the site
- `sitemap-games.xml`: Game-related pages
- `sitemap-payment.xml`: Payment-related pages
- `news-sitemap.xml`: News articles (if applicable)

The sitemap index is located at `sitemap-index.xml`, and `sitemap.xml` redirects to this index file.

### News Sitemaps

If your site publishes news content, you can use the `NewsSitemapGenerator` class to create Google News compatible sitemaps:

```php
$newsSitemap = new NewsSitemapGenerator($baseUrl);
$newsSitemap->addNews(
    '/news/article-slug',
    'Article Title',
    '2023-03-15',
    ['keyword1', 'keyword2']
);
$newsSitemap->generate();
```

## Adding SEO to New Pages

To add SEO to a new page:

1. Set the meta variables at the top of your page file:

```php
$pageTitle = 'Your Page Title';
$pageDescription = 'Your page description here.';
$pageKeywords = 'keyword1, keyword2, keyword3';
$pageImage = '/path/to/og-image.jpg';
```

2. Add schema.org markup if needed:

```php
$breadcrumbs = [
    ['name' => 'Home', 'url' => 'https://fridayai.com/'],
    ['name' => 'Current Page', 'url' => 'https://fridayai.com/current-page']
];
$schemaMarkup = SchemaGenerator::generateBreadcrumbSchema($breadcrumbs);
```

3. Capture your page content using output buffering:

```php
ob_start();
// Your HTML content here
$content = ob_get_clean();
```

4. Set any additional head content or scripts:

```php
$additionalHead = '<link rel="stylesheet" href="/css/your-page.css">';
$additionalScripts = '<script src="/js/your-page.js"></script>';
```

5. Include the layout template:

```php
require_once BASE_PATH . '/app/views/layout.php';
```

## Deployment Process

The sitemaps should be generated regularly to ensure they stay up-to-date. This is automated through a cron job:

### Cron Job Setup

1. The script at `scripts/cron-sitemap.sh` is designed to be run as a cron job
2. Make sure the script is executable: `chmod +x scripts/cron-sitemap.sh`
3. Add to crontab to run daily:

```bash
# Run sitemap generation daily at 2:00 AM
0 2 * * * /path/to/fridayai/scripts/cron-sitemap.sh
```

### Manual Generation

To manually generate the sitemaps:

```bash
php scripts/generate-sitemap.php
php scripts/generate-specialized-sitemaps.php
```

## Best Practices

1. **Keep meta descriptions unique** - Every page should have a unique, descriptive meta description between 120-160 characters.

2. **Use semantic HTML** - Proper heading structure (H1, H2, H3) and semantic elements (nav, main, section, article) improve accessibility and SEO.

3. **Image optimization** - Always include alt text for images and consider using responsive images with srcset.

4. **Use breadcrumbs** - Implement breadcrumb navigation for improved user experience and SEO.

5. **Monitor search performance** - Regularly check Google Search Console to identify crawl errors and performance issues.

6. **Update sitemaps** - Ensure your sitemap generation process runs regularly and includes all important pages.

7. **Check for crawl errors** - Regularly check for 404 errors and fix broken links.

8. **Optimize page speed** - Use tools like Google PageSpeed Insights to identify and fix performance issues.

---

For any questions or issues with the SEO implementation, please contact the development team.
