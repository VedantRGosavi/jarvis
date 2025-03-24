<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Dynamic SEO Meta Tags -->
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - FridayAI' : 'FridayAI - Your AI Assistant'; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'FridayAI is your intelligent AI assistant that helps you accomplish tasks efficiently and effectively.'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'AI assistant, artificial intelligence, productivity, automation, task management'; ?>">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - FridayAI' : 'FridayAI - Your AI Assistant'; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'FridayAI is your intelligent AI assistant that helps you accomplish tasks efficiently and effectively.'; ?>">
    <meta property="og:type" content="<?php echo isset($pageOgType) ? $pageOgType : 'website'; ?>">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="<?php echo isset($pageImage) ? $pageImage : '/images/fridayai-og.png'; ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - FridayAI' : 'FridayAI - Your AI Assistant'; ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'FridayAI is your intelligent AI assistant that helps you accomplish tasks efficiently and effectively.'; ?>">
    <meta name="twitter:image" content="<?php echo isset($pageImage) ? $pageImage : '/images/fridayai-og.png'; ?>">

    <!-- Canonical URL -->
    <?php if (isset($canonicalUrl)): ?>
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>">
    <?php else: ?>
    <link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Schema.org Markup -->
    <script type="application/ld+json">
    <?php
    if (isset($schemaMarkup)) {
        echo $schemaMarkup;
    } else {
        // Default schema markup
        $defaultSchema = [
            "@context" => "https://schema.org",
            "@type" => "SoftwareApplication",
            "name" => "FridayAI",
            "applicationCategory" => "AIApplication",
            "operatingSystem" => "Web",
            "description" => isset($pageDescription) ? $pageDescription : 'FridayAI is your intelligent AI assistant that helps you accomplish tasks efficiently and effectively.',
            "offers" => [
                "@type" => "Offer",
                "price" => "0",
                "priceCurrency" => "USD"
            ]
        ];
        echo json_encode($defaultSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    ?>
    </script>

    <!-- Organization Schema for Social Media Profiles -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "FridayAI",
        "url": "https://fridayai.com",
        "logo": "https://fridayai.com/images/logo.png",
        "description": "FridayAI is your intelligent AI assistant that helps you accomplish tasks efficiently and effectively.",
        "sameAs": [
            "https://twitter.com/fridayai",
            "https://www.facebook.com/fridayai",
            "https://www.linkedin.com/company/fridayai",
            "https://github.com/fridayai"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+1-800-555-1234",
            "contactType": "customer service"
        }
    }
    </script>

    <!-- Your existing CSS and JS includes -->
    <?php if (isset($additionalHead)) echo $additionalHead; ?>
</head>
<body>
    <?php if (isset($content)) echo $content; ?>

    <!-- Your existing scripts -->
    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html>
