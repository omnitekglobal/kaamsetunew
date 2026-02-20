<?php

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonError('Email and password are required');
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, name, email, password, phone, role, is_active FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonError('Invalid email or password', 401);
}

if (!$user['is_active']) {
    jsonError('Account is deactivated', 403);
}

if (!password_verify($password, $user['password'])) {
    jsonError('Invalid email or password', 401);
}

$token = createToken((int) $user['id'], $user['email'], $user['role']);
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
