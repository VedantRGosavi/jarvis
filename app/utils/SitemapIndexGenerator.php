<?php

namespace App\Utils;

class SitemapIndexGenerator
{
    private $baseUrl;
    private $sitemaps = [];
    private $outputPath;

    public function __construct($baseUrl, $outputPath = 'public/sitemap-index.xml')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputPath = $outputPath;
    }

    /**
     * Add a sitemap to the index
     *
     * @param string $location The relative path to the sitemap file
     * @param string $lastmod The last modification date (YYYY-MM-DD)
     */
    public function addSitemap($location, $lastmod = null)
    {
        $this->sitemaps[] = [
            'loc' => $this->baseUrl . '/' . ltrim($location, '/'),
            'lastmod' => $lastmod ?: date('Y-m-d')
        ];
    }

    /**
     * Generate the sitemap index file
     *
     * @return bool True on success
     */
    public function generate()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($this->sitemaps as $sitemap) {
            $xml .= '  <sitemap>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($sitemap['loc']) . '</loc>' . PHP_EOL;
            if (isset($sitemap['lastmod'])) {
                $xml .= '    <lastmod>' . $sitemap['lastmod'] . '</lastmod>' . PHP_EOL;
            }
            $xml .= '  </sitemap>' . PHP_EOL;
        }

        $xml .= '</sitemapindex>';

        file_put_contents($this->outputPath, $xml);
        return true;
    }
}
