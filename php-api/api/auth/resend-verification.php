<?php
/**
 * POST /api/auth/resend-verification
 * Body: { "phone": "9190XXXXXXXX" }
 * Re-generates a verification token and sends a fresh WhatsApp link.
 */

require_once __DIR__ . '/../../includes/whatsapp.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$phoneRaw = trim((string)($input['phone'] ?? ''));
$phone    = preg_replace('/\D/', '', $phoneRaw);

if (!$phone) {
    jsonError('Phone number is required');
}

$pdo  = getDb();
$stmt = $pdo->prepare(
    'SELECT id, is_verified, phone
     FROM users
     WHERE REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,\'\'), \' \', \'\'), \'-\', \'\'), \'+\', \'\'), CHAR(10), \'\') = ?
     LIMIT 1'
);
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Generic message to avoid user enumeration
    jsonSuccess(null, 'If an account with that number exists, a verification link has been sent.');
}

if ((int) $user['is_verified'] === 1) {
    jsonError('This account is already verified. Please log in.', 400);
}

// Generate a fresh token
$newToken   = bin2hex(random_bytes(32));
$expiresAt  = date('Y-m-d H:i:s', time() + 86400);

$pdo->prepare(
    'UPDATE users
     SET verification_token = ?,
         verification_token_expires_at = ?
     WHERE id = ?'
)->execute([$newToken, $expiresAt, (int) $user['id']]);

$whatsappPhone = $user['phone'] ?? $phone;
$sent = sendWhatsAppVerification($whatsappPhone, $newToken);

jsonSuccess(
    ['whatsapp_sent' => $sent],
    $sent
        ? 'Verification link sent to your WhatsApp. Please check and click the link.'
        : 'Could not send WhatsApp message right now. Please try again later.'
);
