<?php

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('User ID required', 400);
}
$id = (int) $id;

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$payload = requireAuth();
$currentId = (int) $payload->sub;
$currentRole = $payload->role ?? 'end_user';

$pdo = getDb();
$hasPincodeCol = false;
try {
    $stmtCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'pincode'");
    $hasPincodeCol = $stmtCol && $stmtCol->rowCount() > 0;
} catch (Throwable $e) {}
$stmt = $pdo->prepare('SELECT id, name, email, phone, role, is_active FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    jsonError('User not found', 404);
}

// End user and professional can only update own profile (limited fields). Super/team_leader can update users in their scope.
$isSelf = ($id === $currentId);
$canChangeRole = in_array($currentRole, ['super_admin', 'team_leader'], true);
$targetRole = $user['role'] ?? '';
$allowedScope = $currentRole === 'super_admin' ? 'team_leader' : ($currentRole === 'team_leader' ? 'staff' : null);

if (in_array($currentRole, ['end_user', 'professional'], true) && !$isSelf) {
    jsonError('Forbidden', 403);
}
if ($canChangeRole && !$isSelf && $allowedScope !== null && $targetRole !== $allowedScope) {
    jsonError('You can only update ' . $allowedScope . ' users', 403);
}

$updates = [];
$params = [];

$allowedSelf = ['name', 'phone', 'password'];
$allowedAdmin = ['name', 'email', 'phone', 'role', 'is_active', 'password'];
if ($hasPincodeCol) {
    $allowedAdmin[] = 'pincode';
}

foreach ($allowedAdmin as $field) {
    if (!array_key_exists($field, $input)) continue;
    if (in_array($currentRole, ['end_user', 'professional'], true) && $isSelf && !in_array($field, $allowedSelf, true)) continue;
    if ($field === 'role' && !$canChangeRole) continue;
    if ($field === 'is_active' && !$canChangeRole) continue;
    if ($field === 'email' && $isSelf) continue; // prevent self email change without verification

    if ($field === 'password') {
        $val = $input['password'];
        if (strlen($val) < 8) {
            jsonError('Password must be at least 8 characters');
        }
        $updates[] = 'password = ?';
        $params[] = password_hash($val, PASSWORD_DEFAULT);
    } elseif ($field === 'role') {
        $newRole = trim($input['role'] ?? '');
        if (!in_array($newRole, ROLES, true)) jsonError('Invalid role');
        if ($canChangeRole && $allowedScope !== null && $newRole !== $allowedScope) {
            jsonError('You can only set role to ' . $allowedScope);
        }
        $updates[] = 'role = ?';
        $params[] = $newRole;
    } elseif ($field === 'is_active') {
        $updates[] = 'is_active = ?';
        $params[] = (int) (bool) $input['is_active'];
    } else {
        $updates[] = "`$field` = ?";
        $params[] = ($field === 'email')
            ? trim($input['email'])
            : (trim((string) $input[$field]) ?: null);
    }
}

if (empty($updates)) {
    jsonError('No valid fields to update');
}

$params[] = $id;
$sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
$pdo->prepare($sql)->execute($params);

$stmt = $pdo->prepare(
    'SELECT id, name, email, phone, role, is_active, updated_at'
    . ($hasPincodeCol ? ', pincode' : '')
    . ' FROM users WHERE id = ?'
);
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user['id'] = (int) $user['id'];
jsonSuccess($user, 'User updated');
