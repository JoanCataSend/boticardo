<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
/**
 * Declaración para el IDE: Estas variables provienen de config.php
 * * @var string $siteUrl
 * @var string $siteName
 * @var string $legalName
 * @var string $pageDescription
 * @var string $canonicalUrl
 * @var string $phoneE164
 * @var string $email
 * @var string $mapsUrl
 * @var string $streetAddress
 * @var string $postalCode
 * @var string $locality
 * @var string $region
 * @var string $countryCode
 * @var bool   $socialImageExists
 * @var string $socialImageUrl
 * @var string $pageTitle
 */
$pharmacySchema = [
    '@type' => 'Pharmacy',
    '@id' => $siteUrl . '/#pharmacy',
    'name' => $siteName,
    'legalName' => $legalName,
    'description' => $pageDescription,
    'url' => $canonicalUrl,
    'telephone' => $phoneE164,
    'email' => $email,
    'hasMap' => $mapsUrl,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $streetAddress,
        'postalCode' => $postalCode,
        'addressLocality' => $locality,
        'addressRegion' => $region,
        'addressCountry' => $countryCode,
    ],
    'openingHoursSpecification' => [
        [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens' => '09:00',
            'closes' => '21:00',
        ],
        [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => 'Saturday',
            'opens' => '09:30',
            'closes' => '14:00',
        ],
    ],
];

if ($socialImageExists) {
    $pharmacySchema['image'] = $socialImageUrl;
}

$websiteSchema = [
    '@type' => 'WebSite',
    '@id' => $siteUrl . '/#website',
    'url' => $canonicalUrl,
    'name' => $siteName,
    'alternateName' => $legalName,
    'inLanguage' => 'es-ES',
    'publisher' => ['@id' => $siteUrl . '/#pharmacy'],
];

$webPageSchema = [
    '@type' => 'WebPage',
    '@id' => $canonicalUrl . '#webpage',
    'url' => $canonicalUrl,
    'name' => $pageTitle,
    'description' => $pageDescription,
    'inLanguage' => 'es-ES',
    'isPartOf' => ['@id' => $siteUrl . '/#website'],
    'about' => ['@id' => $siteUrl . '/#pharmacy'],
];

$structuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [$pharmacySchema, $websiteSchema, $webPageSchema],
];