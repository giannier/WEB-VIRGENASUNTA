<?php
/**
 * Libro de Reclamaciones submission handler.
 *
 * Security layers, in order:
 *   1. Method / content-type check
 *   2. CSRF token check (session-bound)
 *   3. Honeypot check (silent reject, HTTP 200, no email)
 *   4. Server-side Cloudflare Turnstile verification (mandatory — the
 *      client-side widget alone is not sufficient)
 *   5. IP-based sliding-window rate limiting (SQLite, JSON fallback)
 *   6. Full server-side validation/sanitization of every field
 *   7. Header-injection-safe email construction, never trusting client input
 */

require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/lib/rate_limiter.php';
require_once __DIR__ . '/lib/turnstile.php';
require_once __DIR__ . '/lib/mailer.php';

header('Content-Type: application/json; charset=UTF-8');

function va_json_response(int $httpCode, bool $success, string $message, array $extra = []): void {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// 1. Method + content-type check
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    va_json_response(405, false, 'Método no permitido.');
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/x-www-form-urlencoded') !== 0 && stripos($contentType, 'multipart/form-data') !== 0) {
    va_json_response(415, false, 'Tipo de contenido no soportado.');
}

// 2. CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!va_csrf_verify($csrfToken)) {
    va_json_response(403, false, 'Sesión inválida o expirada. Por favor recarga la página e intenta nuevamente.');
}

// 3. Honeypot check — silently accept-and-drop, never reveal the trap to bots
$honeypot = $_POST['website_url'] ?? '';
if (trim($honeypot) !== '') {
    va_json_response(200, true, 'Tu reclamo fue registrado correctamente.');
}

$config = va_config();

$clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// 4. Turnstile server-side verification (mandatory)
$turnstileToken = $_POST['cf-turnstile-response'] ?? '';
$turnstileOk = va_verify_turnstile($turnstileToken, (string) ($config['turnstile_secret_key'] ?? ''), $clientIp);
if (!$turnstileOk) {
    va_json_response(400, false, 'No se pudo verificar que la solicitud proviene de una persona. Por favor intenta nuevamente.');
}

// 5. Rate limiting
$salt = (string) ($config['ip_hash_salt'] ?? 'fallback-salt-change-me');
$limiter = new VaRateLimiter(
    $clientIp,
    $salt,
    (int) ($config['rate_limit_per_hour'] ?? 5),
    (int) ($config['rate_limit_per_day'] ?? 15),
    __DIR__ . '/data'
);
$rateResult = $limiter->check();
if (!$rateResult['allowed']) {
    error_log(sprintf(
        '[virgenasunta] rate limit exceeded ip_hash=%s path=%s time=%s',
        $limiter->ipHash(),
        $_SERVER['REQUEST_URI'] ?? '',
        date('c')
    ));
    header('Retry-After: ' . ($rateResult['retry_after'] ?? 3600));
    va_json_response(429, false, 'Has alcanzado el límite de solicitudes permitidas. Por favor intenta nuevamente más tarde.');
}

// 6. Validate + sanitize every field
function va_clean_text(string $value, int $maxLen): string {
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value); // strip control chars, keep \n\t
    $value = trim($value);
    return mb_substr($value, 0, $maxLen);
}

$errors = [];

$tipo = ($_POST['tipo'] ?? '') === 'queja' ? 'queja' : 'reclamo';

$name = va_clean_text($_POST['consumer_name'] ?? '', 150);
if ($name === '') $errors[] = 'Nombre completo es obligatorio.';

$docType = in_array($_POST['consumer_doc_type'] ?? '', ['DNI', 'CE'], true) ? $_POST['consumer_doc_type'] : 'DNI';
$docNumber = va_clean_text($_POST['consumer_doc_number'] ?? '', 20);
if ($docNumber === '') $errors[] = 'Número de documento es obligatorio.';

$address = va_clean_text($_POST['consumer_address'] ?? '', 200);
if ($address === '') $errors[] = 'Domicilio es obligatorio.';

$phone = va_clean_text($_POST['consumer_phone'] ?? '', 20);
if ($phone === '' || !preg_match('/^[0-9+()\-\s]{6,20}$/', $phone)) $errors[] = 'Teléfono inválido.';

$email = va_clean_text($_POST['consumer_email'] ?? '', 150);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo electrónico inválido.';

$isMinor = !empty($_POST['is_minor']);
$guardianName = $isMinor ? va_clean_text($_POST['guardian_name'] ?? '', 150) : '';
$guardianDoc = $isMinor ? va_clean_text($_POST['guardian_doc_number'] ?? '', 20) : '';
if ($isMinor && ($guardianName === '' || $guardianDoc === '')) {
    $errors[] = 'Datos del apoderado/tutor son obligatorios cuando el consumidor es menor de edad.';
}

$bienTipo = in_array($_POST['bien_tipo'] ?? '', ['producto', 'servicio'], true) ? $_POST['bien_tipo'] : 'servicio';
$bienDescripcion = va_clean_text($_POST['bien_descripcion'] ?? '', 300);
if ($bienDescripcion === '') $errors[] = 'Descripción del bien contratado es obligatoria.';

$montoReclamado = va_clean_text($_POST['monto_reclamado'] ?? '', 30);
$fechaOcurrencia = va_clean_text($_POST['fecha_ocurrencia'] ?? '', 20);
if ($fechaOcurrencia !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaOcurrencia)) {
    $errors[] = 'Fecha inválida.';
}

$detalle = va_clean_text($_POST['detalle'] ?? '', 3000);
if ($detalle === '') $errors[] = 'El detalle del reclamo/queja es obligatorio.';

$pedido = va_clean_text($_POST['pedido'] ?? '', 2000);
if ($pedido === '') $errors[] = 'El pedido del consumidor es obligatorio.';

if (!empty($errors)) {
    va_json_response(422, false, 'Revisa los datos ingresados: ' . implode(' ', $errors));
}

// 7. Build + send emails safely
$complaintsEmail = (string) ($config['complaints_email'] ?? '');
$isPlaceholderEmail = ($complaintsEmail === '' || strpos($complaintsEmail, '{{') === 0);

$tipoLabel = $tipo === 'queja' ? 'Queja (disconformidad con la atención)' : 'Reclamo (disconformidad con el producto o servicio)';

$rows = [
    'Tipo' => $tipoLabel,
    'Nombre completo' => $name,
    'Documento' => $docType . ' ' . $docNumber,
    'Domicilio' => $address,
    'Teléfono' => $phone,
    'Correo electrónico' => $email,
];
if ($isMinor) {
    $rows['Apoderado / tutor'] = $guardianName . ' (' . $guardianDoc . ')';
}
$rows['Bien contratado'] = ucfirst($bienTipo) . ': ' . $bienDescripcion;
if ($montoReclamado !== '') $rows['Monto reclamado'] = $montoReclamado;
if ($fechaOcurrencia !== '') $rows['Fecha del hecho'] = $fechaOcurrencia;

$bodyHtml = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#132A4C;">';
$bodyHtml .= '<h2 style="color:#132A4C;">Nueva hoja de reclamación — Virgen Asunta</h2>';
$bodyHtml .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
foreach ($rows as $label => $value) {
    $bodyHtml .= '<tr><td style="font-weight:bold;vertical-align:top;width:220px;border-bottom:1px solid #eee;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>';
    $bodyHtml .= '<td style="border-bottom:1px solid #eee;">' . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) . '</td></tr>';
}
$bodyHtml .= '</table>';
$bodyHtml .= '<h3 style="color:#132A4C;margin-top:20px;">Detalle del ' . ($tipo === 'queja' ? 'queja' : 'reclamo') . '</h3>';
$bodyHtml .= '<p>' . nl2br(htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8')) . '</p>';
$bodyHtml .= '<h3 style="color:#132A4C;">Pedido del consumidor</h3>';
$bodyHtml .= '<p>' . nl2br(htmlspecialchars($pedido, ENT_QUOTES, 'UTF-8')) . '</p>';
$bodyHtml .= '<p style="color:#8A93A6;font-size:12px;margin-top:24px;">Enviado el ' . date('d/m/Y H:i') . ' (hora del servidor).</p>';
$bodyHtml .= '</div>';

$mailSent = true;
if (!$isPlaceholderEmail) {
    $mailSent = va_send_mail(
        $complaintsEmail,
        'Nueva hoja de reclamación - Virgen Asunta',
        $bodyHtml,
        'no-reply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'virgenasunta.pe'),
        'Sitio web Virgen Asunta',
        $email
    );

    // Best-effort confirmation copy to the consumer; failure here must not
    // fail the overall submission.
    $confirmationHtml = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#132A4C;">'
        . '<p>Hemos recibido tu ' . ($tipo === 'queja' ? 'queja' : 'reclamo') . ' registrado en el Libro de Reclamaciones de Virgen Asunta - Centro de Conciliación y Arbitraje.</p>'
        . '<p>Será atendido en el plazo que establece la normativa vigente. Recibirás la respuesta en este mismo correo.</p>'
        . '<hr>' . $bodyHtml . '</div>';
    @va_send_mail($email, 'Copia de tu registro - Libro de Reclamaciones Virgen Asunta', $confirmationHtml, 'no-reply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'virgenasunta.pe'), 'Virgen Asunta');
} else {
    error_log('[virgenasunta] complaints_email not configured, submission logged but not emailed. Data: ' . json_encode($rows));
}

if (!$mailSent) {
    va_json_response(502, false, 'Tu solicitud fue recibida pero ocurrió un problema al enviarla por correo. Nos pondremos en contacto contigo a la brevedad.');
}

va_json_response(200, true, 'Tu ' . ($tipo === 'queja' ? 'queja' : 'reclamo') . ' fue registrado correctamente. Recibirás una copia en tu correo y será atendido en el plazo que establece la normativa vigente.');
