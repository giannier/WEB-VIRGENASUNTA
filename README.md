# Virgen Asunta — Centro de Conciliación y Arbitraje

Sitio institucional (HTML/CSS/JS + includes PHP) para Virgen Asunta.

## Estructura

- `partials/header.php` / `partials/footer.php` — cabecera y pie
  compartidos, incluidos por cada página `.html` (servidas como PHP vía
  `AddType application/x-httpd-php .html .htm` en `.htaccess`).
- `partials/bootstrap.php` — sesión, carga de `config/config.php`, helpers
  de CSRF.
- `assets/css/` — `tokens.css` (variables de marca), `base.css` (reset y
  tipografía), `components.css` (nav, botones, tarjetas, formularios).
- `assets/js/main.js` — menú móvil, scroll reveals, helper genérico de
  formularios (`vaBindForm`).
- `api/complaint-submit.php` — endpoint del Libro de Reclamaciones (CSRF,
  honeypot, Turnstile server-side, rate limiting, envío de correo seguro).
- `config/config.example.php` — plantilla de configuración. Copiar a
  `config/config.php` y completar (ver `DEPLOY.md`).

## Badge del Libro de Reclamaciones

`partials/footer.php` muestra en cada página un badge "Libro de
Reclamaciones" enlazando a `/libro-reclamaciones.html`. Por defecto renderiza
un ícono SVG + texto en la paleta navy/gold del sitio, ya que no existe un
logo oficial de Indecopi provisto por el cliente.

Si en el futuro el cliente entrega un badge oficial, basta con colocar el
archivo en `assets/img/libro-reclamaciones-badge.png` — `footer.php` detecta
automáticamente su existencia (`file_exists`) y lo usa en lugar del SVG, sin
tocar código.

## Imágenes

Todas las imágenes fuente están en `contenido/`. Se generaron versiones
optimizadas en `assets/img/` usando Pillow (Python) — ImageMagick/cwebp no
estaban disponibles en el entorno de build:

- `logo-transparent.png/.webp` — logo con fondo recortado (flood-fill sobre
  el fondo casi blanco original).
- `hero-justice-statue.png/.webp` — estatua dorada, fondo recortado. Ya no se
  usa en el hero de `index.html` (reemplazado por fondo degradado + foto
  pendiente en `assets/img/hero-background.jpg`, ver `.hero` en
  `components.css`); queda como `og:image` en `partials/header.php`.
- `justice-statue-silver-a.png/.webp` — variante en plata. Ya no se usa en
  `centro-arbitraje/index.html` (el cliente pidió quitar la imagen del hero;
  ver `.page-hero` de esa página, ahora a una sola columna). `justice-statue-silver-b.png/.webp`
  es una segunda variante en plata, procesada y disponible en `assets/img/`
  para uso futuro (no está enlazada en ninguna página actualmente).

La foto de dos personas en `contenido/imagen de informacion.jpeg` **no** se
usó como foto institucional: es un flyer compuesto con logos y texto
superpuestos que no se puede recortar limpiamente sin cortar las cabezas o
dejar un logo parcial visible. `nosotros.html` tiene un placeholder marcado
con `<!-- TODO: reemplazar con foto institucional del equipo -->` a la
espera de fotografía real del equipo.

## Configuración y despliegue

Ver `DEPLOY.md` para Cloudflare, Turnstile, `config/config.php` y subida a
cPanel.
