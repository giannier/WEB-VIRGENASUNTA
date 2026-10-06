# Guía de despliegue — Virgen Asunta

Sitio estático (HTML/CSS/JS) con includes PHP para header/footer y un
endpoint PHP para el Libro de Reclamaciones. Pensado para hosting
compartido tipo cPanel.

## 1. Cloudflare (obligatorio, paso de infraestructura)

El rate-limiting de `api/complaint-submit.php` es una protección a nivel de
aplicación, **no** sustituye protección a nivel de borde. Antes de publicar:

1. Agrega el dominio a Cloudflare (plan gratuito es suficiente para empezar).
2. Actualiza los nameservers del dominio a los que indique Cloudflare.
3. En el panel de DNS, asegúrate que el registro A/CNAME del dominio tenga
   la nube **naranja activada** (proxied), no gris (DNS only). Esto habilita
   protección DDoS y bot management de Cloudflare en el borde.
4. En SSL/TLS, selecciona modo **Full (strict)** una vez el hosting tenga
   certificado SSL activo (ver paso 4).
5. Activa "Always Use HTTPS" en Reglas > SSL/TLS.

## 2. Cloudflare Turnstile (CAPTCHA del Libro de Reclamaciones)

1. En el dashboard de Cloudflare, ve a **Turnstile** y crea un nuevo Site.
2. Agrega el dominio de producción.
3. Copia el **Site Key** (público) y el **Secret Key** (privado).
4. Pégalos en `config/config.php` (ver paso 3) en `turnstile_site_key` y
   `turnstile_secret_key`. El Secret Key nunca debe exponerse en el HTML ni
   subirse a ningún repositorio público.

## 3. Configurar `config/config.php`

1. Copia `config/config.example.php` a `config/config.php` (mismo directorio).
2. Completa cada valor:
   - `company_legal_name`, `company_ruc`: razón social y RUC reales del
     proveedor (lo entrega el cliente).
   - `complaints_email`: bandeja dedicada para el Libro de Reclamaciones.
   - `contact_email`: bandeja para el formulario de "Contacto".
   - `turnstile_site_key`, `turnstile_secret_key`: del paso 2.
   - `ip_hash_salt`: genera un valor aleatorio largo, por ejemplo ejecutando
     en PHP: `php -r "echo bin2hex(random_bytes(32));"`.
3. **No subas `config/config.php` a ningún repositorio ni lo dejes con
   permisos de lectura pública fuera del servidor.** El `.htaccess` raíz ya
   bloquea el acceso HTTP directo a este archivo como defensa adicional.

## 4. Subida a cPanel

1. Selecciona **PHP 8.1 o superior** en "MultiPHP Manager" para el dominio.
2. En "Select PHP Extensions", verifica que **PDO** y **pdo_sqlite** estén
   habilitados (el rate limiter los usa; si no están disponibles, el sistema
   cae automáticamente a un contador en archivo JSON, pero SQLite es
   preferible).
3. Sube todos los archivos del proyecto a `public_html/` (o el subdominio
   correspondiente) vía **File Manager** o **FTP/SFTP**, manteniendo la
   estructura de carpetas intacta (`assets/`, `api/`, `partials/`, `config/`,
   etc.). **No subas la carpeta `contenido/`** — son los archivos fuente de
   marca (logos e imágenes originales) usados solo durante la construcción
   del sitio; las versiones optimizadas que sí usa el sitio ya están en
   `assets/img/`.
4. Verifica que `api/data/` se haya creado con permisos de escritura para el
   usuario de PHP (755 en la carpeta suele bastar; el script la crea
   automáticamente si no existe).
5. Confirma que `.htaccess` (raíz y `api/data/.htaccess`) se subieron — en
   FTP suelen estar ocultos por empezar con punto.

## 5. Verificación post-despliegue

- Visita el sitio por HTTPS y confirma que HTTP redirige automáticamente.
- Llena el formulario de `libro-reclamaciones.html` con datos de prueba y
  confirma que llega el correo a `complaints_email`.
- Intenta enviar el formulario más de 5 veces en una hora desde la misma
  conexión para confirmar que el límite de tasa responde con HTTP 429.
- Revisa en el navegador (DevTools > Network) que `config/config.php`,
  `api/data/ratelimit.sqlite` y `api/data/ratelimit.json` devuelvan 403/404
  si se accede directamente por URL.

## 6. Pendiente antes de salir a producción

Ver la lista en el reporte de entrega: RUC, razón social, correo de
reclamos, llaves de Turnstile y el paso de Cloudflare de este documento son
obligatorios antes del lanzamiento.
