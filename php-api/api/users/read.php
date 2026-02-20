<?php

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('User ID required', 400);
}

$payload = requireAuth();
$currentId = (int) $payload->sub;
$currentRole = $payload->role ?? 'end_user';

// End users and professionals can only read their own profile; staff+ can read any
if (in_array($currentRole, ['end_user', 'professional'], true) && (int)$id !== $currentId) {
    jsonError('Forbidden', 403);
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, name, email, phone, role, is_active, created_at, updated_at FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    jsonError('User not found', 404);
}
$user['id'] = (int) $user['id'];
jsonSuccess($user);
