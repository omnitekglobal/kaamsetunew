<?php

require_once __DIR__ . '/../../includes/whatsapp.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name     = trim($input['name'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$phone    = trim($input['phone'] ?? '');
$role     = trim($input['role'] ?? 'end_user');

if (!$name || !$email || !$password) {
    jsonError('Name, email and password are required');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Invalid email address');
}

if (strlen($password) < 8) {
    jsonError('Password must be at least 8 characters');
}

if (!in_array($role, ROLES, true)) {
    jsonError('Invalid role. Allowed: ' . implode(', ', ROLES));
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError('Email already registered', 409);
}

// Generate verification token
$verificationToken   = bin2hex(random_bytes(32)); // 64-char hex
$tokenExpiresAt      = date('Y-m-d H:i:s', time() + 86400); // 24 hours
$isVerified          = 0; // new users start unverified

// If no phone provided skip WhatsApp and auto-verify (e.g. admin-created accounts)
if (!$phone) {
    $isVerified        = 1;
    $verificationToken = null;
    $tokenExpiresAt    = null;
}

$hash  = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, phone, role, is_verified, verification_token, verification_token_expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $hash, $phone ?: null, $role, $isVerified, $verificationToken, $tokenExpiresAt]);
} catch (\PDOException $e) {
    // Fallback: columns may not exist yet (migration not applied)
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $hash, $phone ?: null, $role]);
    $isVerified        = 1;
    $verificationToken = null;
}

$userId = (int) $pdo->lastInsertId();

// Send WhatsApp verification message (non-fatal if it fails)
$whatsappSent = false;
if ($phone && $verificationToken) {
    $whatsappSent = sendWhatsAppVerification($phone, $verificationToken);
}

$authToken = createToken($userId, $email, $role);
jsonSuccess([
    'user' => [
        'id'          => $userId,
        'name'        => $name,
        'email'       => $email,
        'phone'       => $phone,
        'role'        => $role,
        'is_verified' => (bool) $isVerified,
    ],
    'token'            => $authToken,
    'expires_in'       => 86400,
    'is_verified'      => (bool) $isVerified,
    'whatsapp_sent'    => $whatsappSent,
    'message'          => $phone
        ? ($whatsappSent
            ? 'Registered successfully. A verification link has been sent to your WhatsApp.'
            : 'Registered successfully. WhatsApp message could not be delivered — please use resend.')
        : 'Registered successfully.',
], 'Registered successfully', 201);
