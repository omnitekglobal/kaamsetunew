<?php
/**
 * GET /api/auth/verify?token=XXXX
 * Click this link from WhatsApp to verify the account.
 */

$token = trim($_GET['token'] ?? '');

if (!$token || strlen($token) !== 64) {
    jsonError('Invalid or missing verification token', 400);
}

$pdo  = getDb();
$stmt = $pdo->prepare(
    'SELECT id, is_verified, verification_token_expires_at
     FROM users
     WHERE verification_token = ?
     LIMIT 1'
);
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonError('Verification link is invalid or has already been used', 400);
}

if ((int) $user['is_verified'] === 1) {
    jsonSuccess(null, 'Your account is already verified. You can log in now.');
}

// Check expiry
if (!empty($user['verification_token_expires_at'])) {
    $expires = strtotime($user['verification_token_expires_at']);
    if ($expires && $expires < time()) {
        jsonError('Verification link has expired. Please request a new one.', 400);
    }
}

// Mark as verified and clear token
$pdo->prepare(
    'UPDATE users
     SET is_verified = 1,
         verification_token = NULL,
         verification_token_expires_at = NULL
     WHERE id = ?'
)->execute([(int) $user['id']]);

jsonSuccess(null, 'Account verified successfully! You can now log in.');
