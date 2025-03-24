<?php

namespace App\Utils;

class SitemapGenerator
{
    private $baseUrl;
    private $pages = [];
    private $outputPath;

    public function __construct($baseUrl, $outputPath = 'public/sitemap.xml')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputPath = $outputPath;

        // Add default pages
        $this->addPage('/', '1.0', 'daily');
        $this->addPage('/login', '0.8', 'weekly');
        $this->addPage('/register', '0.8', 'weekly');
        $this->addPage('/about', '0.9', 'weekly');
        $this->addPage('/contact', '0.8', 'weekly');
    }

    public function addPage($url, $priority = '0.5', $changefreq = 'monthly')
    {
        $this->pages[] = [
            'url' => $this->baseUrl . $url,
            'priority' => $priority,
            'changefreq' => $changefreq,
            'lastmod' => date('Y-m-d')
        ];
    }

    public function generate()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($this->pages as $page) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $page['lastmod'] . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        file_put_contents($this->outputPath, $xml);
        return true;
    }
}
