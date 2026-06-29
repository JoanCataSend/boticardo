<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Preguntas frecuentes | Boticardo';
$pageDescription = 'Resuelve las dudas más habituales sobre pedidos, envíos, pagos, cuenta, productos y devoluciones en Boticardo.';
$canonicalUrl = $siteUrl . '/faq.php';

$faqGroups = [
    [
        'id' => 'pedidos',
        'title' => 'Pedidos',
        'intro' => 'Dudas rápidas sobre cómo comprar y revisar tu pedido.',
        'items' => [
            [
                'question' => '¿Cómo hago un pedido en Boticardo?',
                'answer' => 'Añade los productos que necesites al carrito, revisa las cantidades y continúa al checkout. Para finalizar la compra tendrás que iniciar sesión o crear una cuenta.',
            ],
            [
                'question' => '¿Puedo modificar un pedido después de confirmarlo?',
                'answer' => 'Si acabas de realizar el pedido, contacta con nosotros lo antes posible por teléfono o desde la página de contacto. Revisaremos si todavía se puede modificar antes de prepararlo.',
            ],
            [
                'question' => '¿Dónde puedo ver mis pedidos?',
                'answer' => 'Cuando hayas iniciado sesión, podrás revisar la información de tu cuenta y tus pedidos desde el apartado correspondiente de la web.',
            ],
        ],
    ],
    [
        'id' => 'envios',
        'title' => 'Envíos y recogida',
        'intro' => 'Información general sobre entregas y recogida en farmacia.',
        'items' => [
            [
                'question' => '¿Hacéis envíos a domicilio?',
                'answer' => 'Sí, la web está preparada para gestionar pedidos online. Las condiciones concretas de envío, plazos e importe se mostrarán durante el proceso de compra cuando estén configuradas.',
            ],
            [
                'question' => '¿Puedo recoger mi pedido en la farmacia?',
                'answer' => 'Si prefieres recogerlo directamente, contacta con Boticardo para confirmar disponibilidad y preparar tu pedido en la farmacia.',
            ],
            [
                'question' => '¿Cuánto tarda en llegar un pedido?',
                'answer' => 'El plazo puede variar según disponibilidad, destino y método de entrega. Si tienes prisa, escríbenos antes de comprar y te orientamos.',
            ],
        ],
    ],
    [
        'id' => 'pagos',
        'title' => 'Pagos',
        'intro' => 'Formas de pago y confirmación de compra.',
        'items' => [
            [
                'question' => '¿Qué métodos de pago aceptáis?',
                'answer' => 'La web está preparada para pagos online con tarjeta y Bizum mediante pasarela segura. Los métodos disponibles aparecerán al finalizar el pedido.',
            ],
            [
                'question' => '¿El pago online es seguro?',
                'answer' => 'Sí. El pago se realiza mediante una pasarela externa segura, por lo que Boticardo no guarda los datos completos de tu tarjeta.',
            ],
            [
                'question' => '¿Qué pasa si el pago falla?',
                'answer' => 'Si el pago no se completa, el pedido no quedará confirmado correctamente. Puedes volver a intentarlo o contactar con nosotros para revisar el caso.',
            ],
        ],
    ],
    [
        'id' => 'productos',
        'title' => 'Productos y consejo farmacéutico',
        'intro' => 'Ayuda para elegir productos y resolver dudas antes de comprar.',
        'items' => [
            [
                'question' => '¿Puedo pediros consejo antes de comprar?',
                'answer' => 'Sí. Puedes llamarnos, escribirnos por email o usar el formulario de contacto. Te ayudaremos a elegir el producto más adecuado según tu consulta.',
            ],
            [
                'question' => '¿Todos los productos tienen disponibilidad inmediata?',
                'answer' => 'La disponibilidad puede variar. Si un producto es importante para ti o necesitas varias unidades, contacta con nosotros y lo comprobamos.',
            ],
            [
                'question' => '¿Puedo comprar medicamentos con receta desde la web?',
                'answer' => 'Los medicamentos sujetos a receta requieren la validación correspondiente. Para cualquier duda sobre receta electrónica o dispensación, consulta directamente con la farmacia.',
            ],
        ],
    ],
    [
        'id' => 'devoluciones',
        'title' => 'Cambios y devoluciones',
        'intro' => 'Qué hacer si hay una incidencia con tu compra.',
        'items' => [
            [
                'question' => '¿Puedo devolver un producto?',
                'answer' => 'Depende del tipo de producto, su estado y la normativa aplicable. Contacta con nosotros indicando tu pedido y revisaremos la mejor solución.',
            ],
            [
                'question' => '¿Qué hago si el pedido llega dañado o incorrecto?',
                'answer' => 'Escríbenos lo antes posible con el número de pedido y, si puedes, adjunta una foto. Así podremos revisar la incidencia y darte una respuesta rápida.',
            ],
            [
                'question' => '¿Cuánto tarda una devolución o reembolso?',
                'answer' => 'Cuando la devolución esté aceptada, el plazo dependerá del método de pago y de la entidad bancaria. Te informaremos del estado en cada caso.',
            ],
        ],
    ],
    [
        'id' => 'cuenta',
        'title' => 'Cuenta y favoritos',
        'intro' => 'Uso de la cuenta, carrito y lista de favoritos.',
        'items' => [
            [
                'question' => '¿Necesito cuenta para comprar?',
                'answer' => 'Puedes navegar por el catálogo y añadir productos al carrito, pero para finalizar la compra tendrás que iniciar sesión o crear una cuenta.',
            ],
            [
                'question' => '¿Para qué sirven los favoritos?',
                'answer' => 'Los favoritos te permiten guardar productos que quieres revisar más tarde. Puedes acceder a ellos desde el icono del corazón en la cabecera.',
            ],
            [
                'question' => 'He olvidado mis datos de acceso, ¿qué hago?',
                'answer' => 'Si no puedes entrar en tu cuenta, contacta con Boticardo indicando el email con el que te registraste y te ayudaremos a revisarlo.',
            ],
        ],
    ],
];

require_once __DIR__ . '/includes/schema.php';

$faqSchemaItems = [];
foreach ($faqGroups as $group) {
    foreach ($group['items'] as $item) {
        $faqSchemaItems[] = [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['answer'],
            ],
        ];
    }
}

$structuredData['@graph'][] = [
    '@type' => 'FAQPage',
    '@id' => $canonicalUrl . '#faq',
    'url' => $canonicalUrl,
    'name' => $pageTitle,
    'inLanguage' => 'es-ES',
    'mainEntity' => $faqSchemaItems,
];

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="faq-page" aria-labelledby="faq-title">
        <div class="container">
            <div class="faq-hero">
                <span class="section-header__eyebrow">Ayuda</span>
                <h1 id="faq-title">Preguntas frecuentes</h1>
                <p>
                    Hemos reunido las dudas más habituales sobre pedidos, envíos, pagos, productos y cuenta.
                    Si no encuentras lo que buscas, puedes contactar con nosotros directamente.
                </p>
            </div>

            <div class="faq-layout">
                <aside class="faq-sidebar" aria-label="Categorías de preguntas frecuentes">
                    <div class="faq-sidebar__card">
                        <p class="faq-sidebar__title">Ir a una sección</p>
                        <nav class="faq-nav" aria-label="Secciones de ayuda">
                            <?php foreach ($faqGroups as $group): ?>
                                <a href="#<?= e($group['id']) ?>" class="faq-nav__link"><?= e($group['title']) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    </div>

                    <div class="faq-help-card">
                        <span class="faq-help-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M9 9h6"/><path d="M9 13h4"/></svg>
                        </span>
                        <h2>¿No encuentras tu duda?</h2>
                        <p>Llámanos o mándanos un mensaje y te ayudamos personalmente.</p>
                        <div class="faq-help-card__actions">
                            <a href="contacto.php" class="btn btn--primary">Contactar</a>
                            <a href="tel:<?= e($phoneE164) ?>" class="btn btn--ghost"><?= e($phoneDisplay) ?></a>
                        </div>
                    </div>
                </aside>

                <div class="faq-content">
                    <?php foreach ($faqGroups as $group): ?>
                        <section class="faq-section" id="<?= e($group['id']) ?>" aria-labelledby="faq-section-<?= e($group['id']) ?>">
                            <div class="faq-section__header">
                                <span class="faq-section__badge"><?= count($group['items']) ?> preguntas</span>
                                <h2 id="faq-section-<?= e($group['id']) ?>"><?= e($group['title']) ?></h2>
                                <p><?= e($group['intro']) ?></p>
                            </div>

                            <div class="faq-list">
                                <?php foreach ($group['items'] as $item): ?>
                                    <details class="faq-item">
                                        <summary class="faq-item__question">
                                            <span><?= e($item['question']) ?></span>
                                            <span class="faq-item__icon" aria-hidden="true"></span>
                                        </summary>
                                        <div class="faq-item__answer">
                                            <p><?= e($item['answer']) ?></p>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
