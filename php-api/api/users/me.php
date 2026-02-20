<?php

$payload = requireAuth();
$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, name, email, phone, role, is_active, created_at FROM users WHERE id = ?');
$stmt->execute([$payload->sub]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    jsonError('User not found', 404);
}
$user['id'] = (int) $user['id'];
jsonSuccess($user);
