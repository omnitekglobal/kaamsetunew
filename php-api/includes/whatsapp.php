<?php

/**
 * Send a WhatsApp verification link message via media.sendmsg.in
 *
 * @param string $phone   Recipient phone in international format, digits only (e.g. "919081239239")
 * @param string $token   The verification token (64-char hex)
 * @param string|null $templateName Optional template name to override the .env default
 * @return bool           true on success, false on any failure
 */
function sendWhatsAppVerification(string $phone, string $token, ?string $templateName = null): bool
{
    // Sanitise phone – digits only
    $phone = preg_replace('/\D/', '', $phone);

    // Prepend country code if bare 10-digit Indian number
    if (strlen($phone) === 10) {
        $phone = '91' . $phone;
    }

    // Build the verification URL that the user will click
    $frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? getenv('FRONTEND_URL') ?? 'http://localhost:3000', '/');
    $verifyUrl   = $frontendUrl . '/verify-account?token=' . $token;

    // WhatsApp API credentials from .env
    $user     = $_ENV['WHATSAPP_USER']     ?? getenv('WHATSAPP_USER')     ?? 'pinkysreya';
    $pass     = $_ENV['WHATSAPP_PASS']     ?? getenv('WHATSAPP_PASS')     ?? 'Show#442';
    $from     = $_ENV['WHATSAPP_FROM']     ?? getenv('WHATSAPP_FROM')     ?? '919167130160';
    $template = $templateName ?? $_ENV['WHATSAPP_TEMPLATE'] ?? getenv('WHATSAPP_TEMPLATE') ?? 'verify2';

    $payload = [
        'user' => $user,
        'pass' => $pass,
        'whatsapptosend' => [
            [
                'from'         => $from,
                'to'           => $phone,
                'templateid'   => $template,
                'url'          => '',
                'filename'     => '',
                'smsgid'       => 'verify_' . substr($token, 0, 12),
                'placeholders' => [
                    (object)['0' => $verifyUrl]
                ],
                'buttons' => [
                    ['placeholder' => $verifyUrl]
                ],
            ]
        ]
    ];

    $ch = curl_init('https://media.sendmsg.in/mediasend');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    //var_dump($response);exit;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode < 200 || $httpCode >= 300) {
        // Log but don't crash – registration should still succeed
        error_log("WhatsApp send failed (HTTP $httpCode): $curlErr | Response: $response");
        return false;
    }

    return true;
}
