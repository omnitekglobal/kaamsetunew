<?php

// Only super and team_leader can list users; staff cannot
$payload = requireRole('super_admin', 'team_leader');

$pdo = getDb();
$callerRole = $payload->role ?? '';
$scopeRole = $callerRole === 'super_admin' ? 'team_leader' : ($callerRole === 'team_leader' ? 'staff' : null);
$role = $_GET['role'] ?? null;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

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

$sql = 'SELECT id, name, email, phone, role, is_active, created_at'
    . ($hasPincodeCol ? ', pincode' : '')
    . ($hasLastLoginDateCol ? ', last_login_date' : '')
    . ($hasLastLoginTimeCol ? ', last_login_time' : '')
    . ' FROM users WHERE 1=1';
$params = [];
$sql .= ' AND role = ?';
$params[] = $scopeRole;
if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$countSql = preg_replace('/SELECT .+ FROM/', 'SELECT COUNT(*) FROM', $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$sql .= ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as &$u) {
    $u['id'] = (int) $u['id'];
}

jsonSuccess([
    'items' => $users,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
]);
