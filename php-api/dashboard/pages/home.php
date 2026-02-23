<?php

$pdo = getDb();

$stats = [];
$stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stats['categories'] = 0;
$stats['services'] = 0;
$stats['bookings'] = 0;
$stats['professionals'] = 0;
$stats['my_bookings'] = 0; // for professional role

if (table_exists($pdo, 'categories')) {
    $stats['categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
}
if (table_exists($pdo, 'services')) {
    $stats['services'] = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
}
if (table_exists($pdo, 'bookings')) {
    $stats['bookings'] = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
}
if (table_exists($pdo, 'professionals')) {
    $stats['professionals'] = (int) $pdo->query('SELECT COUNT(*) FROM professionals')->fetchColumn();
}
if ($user['role'] === 'professional' && table_exists($pdo, 'bookings')) {
    $hasAssigned = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'assigned_to'");
        $hasAssigned = $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {}
    if ($hasAssigned) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE assigned_to = ?');
        $stmt->execute([$user['id']]);
        $stats['my_bookings'] = (int) $stmt->fetchColumn();
    } elseif (table_exists($pdo, 'professionals')) {
        $stmt = $pdo->prepare('SELECT services FROM professionals WHERE user_id = ? OR email = ? LIMIT 1');
        $stmt->execute([$user['id'], $user['email'] ?? '']);
        $pro = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pro && !empty($pro['services'])) {
            $myServices = array_map('trim', explode(',', (string) $pro['services']));
            $myServices = array_filter($myServices);
            if (!empty($myServices)) {
                $placeholders = implode(',', array_fill(0, count($myServices), '?'));
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE TRIM(service) IN ($placeholders)");
                $stmt->execute($myServices);
                $stats['my_bookings'] = (int) $stmt->fetchColumn();
            }
        }
    }
}

function table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
    return $stmt && $stmt->rowCount() > 0;
}
?>
<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($user['name']) ?>.</p>
</div>
<?php if (isset($_GET['forbidden'])): ?>
    <div class="alert alert-warning">You do not have permission to access that page.</div>
<?php endif; ?>
<div class="stats-grid">
    <?php if (canAccessUsers($user['role'])): ?>
        <div class="stat-card">
            <span class="stat-value"><?= $stats['users'] ?></span>
            <span class="stat-label">Users</span>
        </div>
    <?php endif; ?>
    <?php if (canAccessCategoriesServices($user['role'])): ?>
        <div class="stat-card">
            <span class="stat-value"><?= $stats['categories'] ?></span>
            <span class="stat-label">Categories</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $stats['services'] ?></span>
            <span class="stat-label">Services</span>
        </div>
    <?php endif; ?>
    <?php if (canAccessBookings($user['role'])): ?>
        <div class="stat-card">
            <span class="stat-value"><?= $stats['bookings'] ?></span>
            <span class="stat-label">Bookings</span>
        </div>
    <?php endif; ?>
    <?php if (canAccessProfessionals($user['role'])): ?>
        <div class="stat-card">
            <span class="stat-value"><?= $stats['professionals'] ?></span>
            <span class="stat-label">Professionals</span>
        </div>
    <?php endif; ?>
    <?php if ($user['role'] === 'professional'): ?>
        <div class="stat-card">
            <span class="stat-value"><?= $stats['my_bookings'] ?></span>
            <span class="stat-label">My Bookings</span>
        </div>
    <?php endif; ?>
</div>
<?php if ($user['role'] === 'end_user'): ?>
    <div class="card mt-2">
        <p>You are logged in as a <strong>Customer</strong> (end user). Use <strong>My Profile</strong> to update your details.</p>
    </div>
<?php endif; ?>
<?php if ($user['role'] === 'professional'): ?>
    <div class="card mt-2">
        <p>You are logged in as a <strong>Service Provider</strong> (professional). View <strong>Bookings</strong> to see customer requests for your services; use <strong>My Profile</strong> to update your details.</p>
    </div>
<?php endif; ?>
