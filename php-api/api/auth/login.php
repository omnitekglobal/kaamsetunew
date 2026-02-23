<?php

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$phoneRaw = trim((string) ($input['phone'] ?? ''));
$phone = preg_replace('/\D/', '', $phoneRaw);
$password = $input['password'] ?? '';

if (!$phone || !$password) {
    jsonError('Mobile number and password are required');
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, name, email, password, phone, role, is_active FROM users WHERE REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,\'\'), \' \', \'\'), \'-\', \'\'), \'+\', \'\'), CHAR(10), \'\') = ?');
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonError('Invalid mobile number or password', 401);
}

if (!$user['is_active']) {
    jsonError('Account is deactivated', 403);
}

if (!password_verify($password, $user['password'])) {
    jsonError('Invalid mobile number or password', 401);
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
