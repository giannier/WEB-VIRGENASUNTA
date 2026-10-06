<?php
/**
 * Configuration template for Virgen Asunta - Centro de Conciliación y Arbitraje.
 *
 * Copy this file to `config.php` in this same directory (kept OUT of version
 * control / never uploaded publicly readable) and fill in the real values
 * below. `config.php` is loaded automatically by partials/bootstrap.php;
 * until it exists, the site falls back to THIS example file so pages still
 * render for local preview and QA, with placeholder text visible on the
 * Libro de Reclamaciones page and no working Turnstile/email delivery.
 */

return [
    // Legal identity of the provider, required on the Libro de Reclamaciones
    // page by Peruvian consumer-protection regulation (Indecopi). Provided
    // later by the client — DO NOT invent a legal name or RUC.
    'company_legal_name' => '{{RAZON_SOCIAL}}',
    'company_ruc'        => '{{RUC_EMPRESA}}',

    // Dedicated inbox that receives Libro de Reclamaciones submissions.
    'complaints_email'   => '{{EMAIL_RECLAMOS}}',

    // General contact inbox for the "Contacto" request-info form.
    'contact_email'      => '{{EMAIL_CONTACTO}}',

    // Inbox that receives "Trabaja con Nosotros" staff applications.
    'staff_email'         => '{{EMAIL_STAFF}}',

    // Cloudflare Turnstile keys — https://dash.cloudflare.com/?to=/:account/turnstile
    'turnstile_site_key'   => '{{TURNSTILE_SITE_KEY}}',   // public, safe to render in HTML
    'turnstile_secret_key' => '{{TURNSTILE_SECRET_KEY}}', // SECRET — server-side only, never expose

    // IP-based sliding-window rate limits for api/complaint-submit.php
    'rate_limit_per_hour' => 5,
    'rate_limit_per_day'  => 15,

    // Salt used to hash IP addresses before they are written to the rate
    // limit store / logs, so raw IPs are never persisted. Generate a random
    // 32+ character string for production, e.g. `bin2hex(random_bytes(32))`.
    'ip_hash_salt' => '{{IP_HASH_SALT}}',
];
