<?php

namespace App\Utils;

class NewsSitemapGenerator
{
    private $baseUrl;
    private $news = [];
    private $outputPath;
    private $publication;

    public function __construct($baseUrl, $publication = ['name' => 'FridayAI', 'language' => 'en'], $outputPath = 'public/news-sitemap.xml')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->outputPath = $outputPath;
        $this->publication = $publication;
    }

    /**
     * Add a news article to the sitemap
     *
     * @param string $url The URL of the news article
     * @param string $title The title of the news article
     * @param string $publicationDate The publication date (format: YYYY-MM-DD)
     * @param array $keywords Array of keywords for the article
     */
    public function addNews($url, $title, $publicationDate, $keywords = [])
    {
        $this->news[] = [
            'url' => $this->baseUrl . $url,
            'title' => $title,
            'publicationDate' => $publicationDate,
            'keywords' => $keywords
        ];
    }

    /**
     * Generate the news sitemap
     *
     * @return bool True on success
     */
    public function generate()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . PHP_EOL;

        foreach ($this->news as $news) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($news['url']) . '</loc>' . PHP_EOL;
            $xml .= '    <news:news>' . PHP_EOL;
            $xml .= '      <news:publication>' . PHP_EOL;
            $xml .= '        <news:name>' . htmlspecialchars($this->publication['name']) . '</news:name>' . PHP_EOL;
            $xml .= '        <news:language>' . htmlspecialchars($this->publication['language']) . '</news:language>' . PHP_EOL;
            $xml .= '      </news:publication>' . PHP_EOL;
            $xml .= '      <news:publication_date>' . $news['publicationDate'] . '</news:publication_date>' . PHP_EOL;
            $xml .= '      <news:title>' . htmlspecialchars($news['title']) . '</news:title>' . PHP_EOL;
            if (!empty($news['keywords'])) {
                $xml .= '      <news:keywords>' . htmlspecialchars(implode(', ', $news['keywords'])) . '</news:keywords>' . PHP_EOL;
            }
            $xml .= '    </news:news>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        file_put_contents($this->outputPath, $xml);
        return true;
    }
}
