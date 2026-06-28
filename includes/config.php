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

// --- ADMINISTRACIÓN Y AVISOS DE PEDIDOS ---
// Cambia ADMIN_ORDER_EMAIL en producción por el correo real donde quieres recibir los pedidos.
define('ADMIN_ORDER_EMAIL', getenv('ADMIN_ORDER_EMAIL') ?: $email);
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: $email);
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: $siteName);

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
$logo = 'img/identidad/logo2.jpeg';
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
// --- AUTENTICACIÓN Y PROVEEDORES SOCIALES ---
$detectedScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$detectedHost = $_SERVER['HTTP_HOST'] ?? '';
$detectedBasePath = isset($_SERVER['SCRIPT_NAME']) ? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') : '';
// Si config.php se carga desde /api, /checkout, /webhooks o /admin, la URL base real sigue siendo la raíz del proyecto.
$detectedBasePath = preg_replace('#/(api|checkout|webhooks|admin)$#', '', $detectedBasePath) ?: '';
$detectedBaseUrl = $detectedHost !== '' ? $detectedScheme . '://' . $detectedHost . ($detectedBasePath === '/' ? '' : $detectedBasePath) : $siteUrl;

define('APP_BASE_URL', rtrim(getenv('APP_BASE_URL') ?: $detectedBaseUrl, '/'));

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: APP_BASE_URL . '/auth_callback.php?provider=google');

define('APPLE_CLIENT_ID', getenv('APPLE_CLIENT_ID') ?: '');
define('APPLE_TEAM_ID', getenv('APPLE_TEAM_ID') ?: '');
define('APPLE_KEY_ID', getenv('APPLE_KEY_ID') ?: '');
define('APPLE_PRIVATE_KEY', getenv('APPLE_PRIVATE_KEY') ?: '');
define('APPLE_REDIRECT_URI', getenv('APPLE_REDIRECT_URI') ?: APP_BASE_URL . '/auth_callback.php?provider=apple');


// --- PAGOS EXTERNOS: STRIPE CHECKOUT ---
// Pon estas claves como variables de entorno en producción. No subas claves reales a Git.
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');
define('STRIPE_CURRENCY', strtolower(getenv('STRIPE_CURRENCY') ?: 'eur'));
define('STRIPE_PAYMENT_METHODS', ['card', 'bizum']);
