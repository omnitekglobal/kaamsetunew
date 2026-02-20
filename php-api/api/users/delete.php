<?php

requireAdmin(); // only admin / super_admin can delete users

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('User ID required', 400);
}
$id = (int) $id;

$payload = requireAuth();
$currentId = (int) $payload->sub;
if ($id === $currentId) {
    jsonError('You cannot delete your own account', 400);
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    jsonError('User not found', 404);
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);
jsonSuccess(null, 'User deleted', 200);
