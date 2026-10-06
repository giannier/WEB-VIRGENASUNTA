<?php
/**
 * Shared <head> + site header/nav, included by every page.
 * Callers should set, before requiring this file:
 *   $page_title        (string, required)
 *   $page_description  (string, required)
 *   $canonical_path     (string, required, e.g. "/nosotros.html")
 *   $body_class         (string, optional)
 *   $json_ld            (string, optional, raw <script type="application/ld+json"> body)
 *   $extra_head          (string, optional, raw extra <head> markup)
 */

require_once __DIR__ . '/bootstrap.php';

$page_title = $page_title ?? 'Virgen Asunta - Centro de Conciliación y Arbitraje';
$page_description = $page_description ?? 'Centro de Conciliación y Arbitraje Virgen Asunta en Lima, Perú. Diálogo que construye soluciones.';
$canonical_path = $canonical_path ?? '/';
$body_class = $body_class ?? '';
$canonical_url = rtrim(va_site_url(), '/') . $canonical_path;
$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

function va_nav_active(string $path, string $current): string {
    if ($path === '/' ) {
        return $current === '/' || $current === '/index.html' ? 'page' : 'false';
    }
    return (strpos($current, rtrim($path, '/')) === 0) ? 'page' : 'false';
}
?><!DOCTYPE html>
<html lang="es-PE">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= va_e($page_title) ?></title>
<meta name="description" content="<?= va_e($page_description) ?>">
<link rel="canonical" href="<?= va_e($canonical_url) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= va_e($page_title) ?>">
<meta property="og:description" content="<?= va_e($page_description) ?>">
<meta property="og:url" content="<?= va_e($canonical_url) ?>">
<meta property="og:locale" content="es_PE">
<meta property="og:image" content="<?= va_e(rtrim(va_site_url(), '/') . '/assets/img/hero-justice-statue.png') ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#132a4c">
<link rel="icon" href="/assets/img/favicon.ico" sizes="any">
<link rel="icon" href="/assets/img/favicon-32.png" type="image/png" sizes="32x32">
<link rel="icon" href="/assets/img/favicon-192.png" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="/assets/img/favicon-180.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/base.css">
<link rel="stylesheet" href="/assets/css/components.css">
<?php if (!empty($json_ld)): ?>
<script type="application/ld+json"><?= $json_ld ?></script>
<?php endif; ?>
<?= $extra_head ?? '' ?>
</head>
<body class="<?= va_e($body_class) ?>">
<?php if ($canonical_path === '/'): ?>
<!-- Premium loading screen: full logo lockup. Only on the home page — the
     client doesn't want it firing on every internal link click (this is a
     traditional multi-page site, so every navigation is a full reload, and
     a splash on every single page would feel repetitive instead of
     premium). Faded out once the page is ready (see assets/js/main.js).
     Respects prefers-reduced-motion — that path hides it immediately. -->
<div class="page-loader" id="pageLoader" aria-hidden="true">
  <picture class="page-loader__watermark">
    <!-- Lighter crop for mobile (webp ~190KB vs ~618KB) — source order
         matters, the first matching media wins, so the narrower query
         goes first. -->
    <source media="(max-width: 639px)" srcset="/assets/img/loader-virgen-watermark-mobile.webp" type="image/webp">
    <source media="(max-width: 639px)" srcset="/assets/img/loader-virgen-watermark-mobile.png">
    <source srcset="/assets/img/loader-virgen-watermark.webp" type="image/webp">
    <img src="/assets/img/loader-virgen-watermark.png" alt="" width="1200" height="1600">
  </picture>
  <picture>
    <source srcset="/assets/img/logo-full-reversed.webp" type="image/webp">
    <img class="page-loader__logo" src="/assets/img/logo-full-reversed.png" alt="" width="1661" height="1000">
  </picture>
</div>
<?php endif; ?>
<a class="skip-link" href="#main">Saltar al contenido principal</a>

<header class="site-header">
  <div class="container nav">
    <a href="/" class="nav__brand" aria-label="Virgen Asunta - Centro de Conciliación y Arbitraje, ir al inicio">
      <!-- Two logo exports, CSS-toggled by .site-header.is-scrolled (set via
           a scroll listener in main.js): the navy-ink version reads on the
           light default header, the white-ink reversed version reads once
           the header inverts to a dark background on scroll. -->
      <picture class="nav__logo nav__logo--default">
        <source srcset="/assets/img/logo-wordmark-transparent.webp" type="image/webp">
        <img class="nav__logo-img" src="/assets/img/logo-wordmark-transparent.png" alt="Virgen Asunta - Centro de Conciliación y Arbitraje" width="1340" height="700">
      </picture>
      <picture class="nav__logo nav__logo--scrolled">
        <source srcset="/assets/img/logo-wordmark-reversed.webp" type="image/webp">
        <img class="nav__logo-img" src="/assets/img/logo-wordmark-reversed.png" alt="Virgen Asunta - Centro de Conciliación y Arbitraje" width="1340" height="700">
      </picture>
    </a>

    <nav class="nav__list" aria-label="Navegación principal">
      <ul style="display:flex;align-items:center;gap:var(--space-md);">
        <li><a class="nav__link" href="/" aria-current="<?= va_nav_active('/', $current_path) ?>">Inicio</a></li>
        <li><a class="nav__link" href="/nosotros.html" aria-current="<?= va_nav_active('/nosotros.html', $current_path) ?>">Nosotros</a></li>
        <li class="nav__item--has-submenu">
          <a class="nav__link" href="/conciliacion-extrajudicial/" aria-current="<?= va_nav_active('/conciliacion-extrajudicial/', $current_path) ?>">Conciliación</a>
          <div class="nav__submenu">
            <div class="nav__submenu-panel">
              <a class="nav__link" href="/conciliacion-extrajudicial/familiar.html">Conciliación Familiar</a>
              <a class="nav__link" href="/conciliacion-extrajudicial/ambiental.html">Conciliación Ambiental</a>
              <a class="nav__link" href="/conciliacion-extrajudicial/laboral.html">Conciliación Laboral</a>
              <a class="nav__link" href="/conciliacion-extrajudicial/comercial.html">Conciliación Comercial</a>
              <a class="nav__link" href="/conciliacion-extrajudicial/contrataciones-estado.html">Conciliación en Contrataciones del Estado</a>
            </div>
          </div>
        </li>
        <li class="nav__item--has-submenu">
          <a class="nav__link" href="/centro-arbitraje/" aria-current="<?= va_nav_active('/centro-arbitraje/', $current_path) ?>">Arbitraje</a>
          <div class="nav__submenu">
            <div class="nav__submenu-panel">
              <a class="nav__link" href="/centro-arbitraje/independencia.html">Independencia</a>
              <a class="nav__link" href="/centro-arbitraje/confidencialidad.html">Confidencialidad</a>
              <a class="nav__link" href="/centro-arbitraje/arbitros-especializados.html">Árbitros especializados</a>
              <a class="nav__link" href="/centro-arbitraje/resolucion-controversias.html">Resolución de controversias</a>
            </div>
          </div>
        </li>
        <li><a class="nav__link" href="/trabaja-con-nosotros.html" aria-current="<?= va_nav_active('/trabaja-con-nosotros.html', $current_path) ?>">Trabaja con Nosotros</a></li>
        <li><a class="nav__link" href="/contacto.html" aria-current="<?= va_nav_active('/contacto.html', $current_path) ?>">Contacto</a></li>
      </ul>
    </nav>

    <a class="btn btn--primary nav__cta" href="/contacto.html">Agendar consulta</a>

    <button class="nav__toggle" id="navToggle" aria-expanded="false" aria-controls="navMobile" aria-label="Abrir menú de navegación">
      <span></span><span></span><span></span>
    </button>
  </div>

  <nav class="nav__mobile container" id="navMobile" aria-label="Navegación móvil">
    <a class="nav__link" href="/">Inicio</a>
    <a class="nav__link" href="/nosotros.html">Nosotros</a>
    <a class="nav__link" href="/conciliacion-extrajudicial/">Conciliación</a>
    <div class="nav__submenu">
      <a class="nav__link" href="/conciliacion-extrajudicial/familiar.html"><img class="nav__submenu-icon" src="/assets/img/icon-handshake-navy.png" alt="" width="400" height="255" loading="lazy">Familiar</a>
      <a class="nav__link" href="/conciliacion-extrajudicial/ambiental.html"><img class="nav__submenu-icon" src="/assets/img/icon-handshake-navy.png" alt="" width="400" height="255" loading="lazy">Ambiental</a>
      <a class="nav__link" href="/conciliacion-extrajudicial/laboral.html"><img class="nav__submenu-icon" src="/assets/img/icon-handshake-navy.png" alt="" width="400" height="255" loading="lazy">Laboral</a>
      <a class="nav__link" href="/conciliacion-extrajudicial/comercial.html"><img class="nav__submenu-icon" src="/assets/img/icon-handshake-navy.png" alt="" width="400" height="255" loading="lazy">Comercial</a>
      <a class="nav__link" href="/conciliacion-extrajudicial/contrataciones-estado.html"><img class="nav__submenu-icon" src="/assets/img/icon-handshake-navy.png" alt="" width="400" height="255" loading="lazy">Contrataciones del Estado</a>
    </div>
    <a class="nav__link" href="/centro-arbitraje/">Arbitraje</a>
    <div class="nav__submenu">
      <a class="nav__link" href="/centro-arbitraje/independencia.html"><img class="nav__submenu-icon" src="/assets/img/icon-gavel-navy.png" alt="" width="400" height="308" loading="lazy">Independencia</a>
      <a class="nav__link" href="/centro-arbitraje/confidencialidad.html"><img class="nav__submenu-icon" src="/assets/img/icon-gavel-navy.png" alt="" width="400" height="308" loading="lazy">Confidencialidad</a>
      <a class="nav__link" href="/centro-arbitraje/arbitros-especializados.html"><img class="nav__submenu-icon" src="/assets/img/icon-gavel-navy.png" alt="" width="400" height="308" loading="lazy">Árbitros especializados</a>
      <a class="nav__link" href="/centro-arbitraje/resolucion-controversias.html"><img class="nav__submenu-icon" src="/assets/img/icon-gavel-navy.png" alt="" width="400" height="308" loading="lazy">Resolución de controversias</a>
    </div>
    <a class="nav__link" href="/trabaja-con-nosotros.html">Trabaja con Nosotros</a>
    <a class="nav__link" href="/contacto.html">Contacto</a>
    <a class="nav__link" href="/libro-reclamaciones.html">Libro de Reclamaciones</a>
    <a class="btn btn--primary btn--block" href="/contacto.html">Agendar consulta</a>
  </nav>
</header>

<main id="main">
