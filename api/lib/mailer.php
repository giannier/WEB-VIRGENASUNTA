<?php
/**
 * Minimal safe-by-default mail helper built on PHP's built-in mail(), which
 * works out of the box on virtually every cPanel host without extra setup.
 *
 * Header injection guard: every header VALUE is stripped of \r and \n
 * before use (the classic PHP mail() header-injection vector), and all
 * user-supplied content is placed ONLY in the message body, HTML-escaped.
 *
 * ---------------------------------------------------------------------
 * Swapping in PHPMailer/SMTP later (recommended once the client needs
 * reliable delivery / DKIM / a transactional provider):
 *
 *   1. `composer require phpmailer/phpmailer`
 *   2. Replace the body of va_send_mail() with:
 *        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
 *        $mail->isSMTP();
 *        $mail->Host = 'smtp.example.com';
 *        $mail->SMTPAuth = true;
 *        $mail->Username = '...';
 *        $mail->Password = '...';
 *        $mail->setFrom($from, $fromName);
 *        $mail->addAddress($to);
 *        $mail->Subject = $subject;
 *        $mail->isHTML(true);
 *        $mail->Body = $htmlBody;
 *        return $mail->send();
 *   3. Keep the same function signature so callers don't change.
 * ---------------------------------------------------------------------
 */

function va_clean_header_value(string $value): string {
    return trim(preg_replace('/[\r\n]+/', ' ', $value));
}

function va_send_mail(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName, ?string $replyTo = null): bool {
    $to = va_clean_header_value($to);
    $subject = va_clean_header_value($subject);
    $fromEmail = va_clean_header_value($fromEmail);
    $fromName = va_clean_header_value($fromName);

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = sprintf('From: %s <%s>', $encodedFromName, $fromEmail);
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . va_clean_header_value($replyTo);
    }
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    return @mail($to, $encodedSubject, $htmlBody, implode("\r\n", $headers));
}
