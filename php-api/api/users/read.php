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
$hasLastLoginDateCol = false;
$hasLastLoginTimeCol = false;
$hasPincodeCol = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login_date'");
    $hasLastLoginDateCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login_time'");
    $hasLastLoginTimeCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pincode'");
    $hasPincodeCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

$stmt = $pdo->prepare(
    'SELECT id, name, email, phone, role, is_active, created_at, updated_at'
    . ($hasPincodeCol ? ', pincode' : '')
    . ($hasLastLoginDateCol ? ', last_login_date' : '')
    . ($hasLastLoginTimeCol ? ', last_login_time' : '')
    . ' FROM users WHERE id = ?'
);
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    jsonError('User not found', 404);
}
$user['id'] = (int) $user['id'];
jsonSuccess($user);
