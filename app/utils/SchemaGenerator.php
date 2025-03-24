<?php

namespace App\Utils;

class SchemaGenerator
{
    /**
     * Generate FAQ Schema markup
     *
     * @param array $faqItems Array of FAQ items, each containing 'question' and 'answer'
     * @return string JSON-encoded schema
     */
    public static function generateFAQSchema($faqItems)
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => []
        ];

        foreach ($faqItems as $item) {
            $schema['mainEntity'][] = [
                "@type" => "Question",
                "name" => $item['question'],
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $item['answer']
                ]
            ];
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate BreadcrumbList Schema markup
     *
     * @param array $items Array of breadcrumb items, each containing 'name' and 'url'
     * @return string JSON-encoded schema
     */
    public static function generateBreadcrumbSchema($items)
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => []
        ];

        $position = 1;
        foreach ($items as $item) {
            $schema['itemListElement'][] = [
                "@type" => "ListItem",
                "position" => $position,
                "name" => $item['name'],
                "item" => $item['url']
            ];
            $position++;
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate Article Schema markup
     *
     * @param array $article Article details
     * @return string JSON-encoded schema
     */
    public static function generateArticleSchema($article)
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $article['title'],
            "description" => $article['description'],
            "image" => $article['image'],
            "datePublished" => $article['datePublished'],
            "dateModified" => $article['dateModified'] ?? $article['datePublished'],
            "author" => [
                "@type" => "Person",
                "name" => $article['author']['name']
            ]
        ];

        if (isset($article['publisher'])) {
            $schema['publisher'] = [
                "@type" => "Organization",
                "name" => $article['publisher']['name'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $article['publisher']['logo']
                ]
            ];
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate Organization Schema with social media profiles
     *
     * @param array $organization Organization details
     * @return string JSON-encoded schema
     */
    public static function generateOrganizationSchema($organization)
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => $organization['name'],
            "url" => $organization['url'],
            "logo" => $organization['logo']
        ];

        if (isset($organization['description'])) {
            $schema['description'] = $organization['description'];
        }

        if (isset($organization['socialProfiles']) && is_array($organization['socialProfiles'])) {
            $schema['sameAs'] = $organization['socialProfiles'];
        }

        if (isset($organization['contactPoint'])) {
            $schema['contactPoint'] = [
                "@type" => "ContactPoint",
                "telephone" => $organization['contactPoint']['telephone'],
                "contactType" => $organization['contactPoint']['contactType']
            ];
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
