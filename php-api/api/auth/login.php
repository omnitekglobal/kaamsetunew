<?php

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$phoneRaw = trim((string) ($input['phone'] ?? ''));
$phone = preg_replace('/\D/', '', $phoneRaw);
$password = $input['password'] ?? '';

if (!$phone || !$password) {
    jsonError('Mobile number and password are required');
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, name, email, password, phone, role, is_active, is_verified FROM users WHERE REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,\'\'), \' \', \'\'), \'-\', \'\'), \'+\', \'\'), CHAR(10), \'\') = ?');
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonError('Invalid mobile number or password', 401);
}

if (!$user['is_active']) {
    jsonError('Account is deactivated', 403);
}

// Block unverified accounts (graceful fallback if column missing)
if (array_key_exists('is_verified', $user) && !(int)$user['is_verified']) {
    jsonError('Your account is not verified. Please click the verification link sent to your WhatsApp number and try again.', 403);
}

if (!password_verify($password, $user['password'])) {
    jsonError('Invalid mobile number or password', 401);
}

try {
    $pdo->prepare('UPDATE users SET last_login_date = CURDATE(), last_login_time = CURTIME() WHERE id = ?')
        ->execute([(int) $user['id']]);
} catch (Throwable $e) {
    // Keep login working even if DB migration is not applied yet.
}

$token = createToken((int) $user['id'], $user['phone'] ?? $user['email'] ?? (string) $user['id'], $user['role']);
unset($user['password']);
jsonSuccess([
    'user' => [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => $user['role'],
    ],
    'token' => $token,
    'expires_in' => 86400,
], 'Login successful');
