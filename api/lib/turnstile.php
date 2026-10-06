<?php
/**
 * Server-side Cloudflare Turnstile verification. The client-side widget
 * alone is NOT sufficient security — it is trivially bypassable by calling
 * this endpoint directly, so every submission is re-verified here against
 * https://challenges.cloudflare.com/turnstile/v0/siteverify using the
 * SECRET key (never exposed to the browser).
 */

function va_verify_turnstile(string $token, string $secretKey, string $remoteIp): bool {
    if ($token === '' || $secretKey === '' || strpos($secretKey, '{{') === 0) {
        // Secret key not yet configured (placeholder from config.example.php) —
        // fail closed so no submission bypasses verification silently in production.
        error_log('[virgenasunta] turnstile secret key not configured');
        return false;
    }

    $payload = http_build_query([
        'secret'   => $secretKey,
        'response' => $token,
        'remoteip' => $remoteIp,
    ]);

    $result = null;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response !== false) {
            $result = $response;
        } else {
            error_log('[virgenasunta] turnstile curl error: ' . $error);
        }
    }

    if ($result === null) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);
        $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    }

    if ($result === false || $result === null) {
        error_log('[virgenasunta] turnstile verification request failed');
        return false;
    }

    $decoded = json_decode($result, true);
    return is_array($decoded) && !empty($decoded['success']);
}
