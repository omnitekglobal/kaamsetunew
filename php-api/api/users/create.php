<?php

// Only admin and super_admin can create users (and set role)
requireAdmin();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$phone = trim($input['phone'] ?? '');
$role = trim($input['role'] ?? 'end_user');

if (!$name || !$email || !$password) {
    jsonError('Name, email and password are required');
}

if (!in_array($role, ROLES, true)) {
    jsonError('Invalid role. Allowed: ' . implode(', ', ROLES));
}

if (strlen($password) < 8) {
    jsonError('Password must be at least 8 characters');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Invalid email address');
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError('Email already registered', 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $email, $hash, $phone ?: null, $role]);
$userId = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT id, name, email, phone, role, is_active, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user['id'] = (int) $user['id'];

jsonSuccess($user, 'User created', 201);
