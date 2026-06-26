<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Madrid');

// --- DATOS DEL NEGOCIO Y SEO ---
$siteName = 'Boticardo';
$legalName = 'Farmacia Mª Carmen Rozalén Escriche';
$siteUrl = 'https://boticardo.es';
$canonicalUrl = $siteUrl . '/';

$pageTitle = 'Farmacia online en Manzanera, Teruel | Boticardo';
$pageDescription = 'Farmacia y parafarmacia online en Manzanera, Teruel. Vitaminas, dermocosmética e higiene con asesoramiento farmacéutico en Boticardo.';

$phoneDisplay = '978 781 980';
$phoneE164 = '+34978781980';
$email = 'hola@boticardo.es';

$streetAddress = 'C. Tomás María Ariño, 118';
$postalCode = '44420';
$locality = 'Manzanera';
$region = 'Teruel';
$countryCode = 'ES';

$horarioVerano = '10:00h a 14:00h';
$horarioVeranoT = '17:30h a 20:00h';
$horarioVeranoV = '10:00h a 14:00h';

$horarioInvierno = '10:00h a 13:30h';
$horarioInviernoT = '17:30h a 19:30h';
$horarioInviernoV = '10:00h a 13:00h';

$mapsQuery = rawurlencode($streetAddress . ', ' . $postalCode . ' ' . $locality . ', ' . $region . ', España');
$mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $mapsQuery;
$mapsEmbedUrl = 'https://www.google.com/maps?q=' . $mapsQuery . '&output=embed';

// --- IMÁGENES ---
$heroImagePath = 'img/landing/persona.png';
$heroImageExists = is_file(__DIR__ . '/../' . $heroImagePath);
$socialImagePath = 'img/seo/boticardo-social-1200x630.jpg';
$logo = 'img/identidad/logo.jpeg';
$socialImageExists = is_file(__DIR__ . '/../' . $socialImagePath);
$socialImageUrl = $siteUrl . '/' . $socialImagePath;

// --- CREDENCIALES DE BASE DE DATOS ---
//$host = getenv('DB_HOST') ?: 'localhost:3306';
//$usuario = getenv('DB_USER') ?: 'boticardo';
//$contrasena = getenv('DB_PASSWORD') ?: '2vJVif8iJa$_5nhp';
//$baseDatos = getenv('DB_NAME') ?: 'boticardo_bd';


define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'boticardo_bd');