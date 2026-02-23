<?php

// Super can delete team_leader; team_leader can delete staff
$payload = requireRole('super_admin', 'team_leader');
$currentId = (int) $payload->sub;
$callerRole = $payload->role ?? '';

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('User ID required', 400);
}
$id = (int) $id;

if ($id === $currentId) {
    jsonError('You cannot delete your own account', 400);
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
$stmt->execute([$id]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$target) {
    jsonError('User not found', 404);
}

$scopeRole = $callerRole === 'super_admin' ? 'team_leader' : 'staff';
if ($target['role'] !== $scopeRole) {
    jsonError('You can only delete ' . $scopeRole . ' users', 403);
}

$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
jsonSuccess(null, 'User deleted', 200);
