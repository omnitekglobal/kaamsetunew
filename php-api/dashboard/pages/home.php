<?php

$pdo = getDb();

$isStaff = ($user['role'] ?? '') === 'staff';
$isProfessional = ($user['role'] ?? '') === 'professional';
$isTeamLeader = ($user['role'] ?? '') === 'team_leader';
$isSuperAdmin = ($user['role'] ?? '') === 'super_admin';

// Column checks for scoping
$bookingCols = [];
$hasUserCreatedByCol = false;
$hasBookingCreatedBy = false;
$hasBookingAssignedBy = false;
$hasProReferredBy = false;
try {
    if (table_exists($pdo, 'bookings')) {
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings");
        $bookingCols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $hasBookingCreatedBy = in_array('created_by', $bookingCols, true);
        $hasBookingAssignedBy = in_array('assigned_by', $bookingCols, true);
        $hasBookingReferralCode = in_array('referral_code', $bookingCols, true);
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
    $hasUserCreatedByCol = $stmt && $stmt->rowCount() > 0;
    if (table_exists($pdo, 'professionals')) {
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referred_by_user_id'");
        $hasProReferredBy = $stmt && $stmt->rowCount() > 0;
    }
} catch (Throwable $e) {}
$hasBookingReferralCode = $hasBookingReferralCode ?? false;

// TL's assigned staff ids (for scoping TL to their team only)
$tlStaffIds = [];
if ($isTeamLeader && $hasUserCreatedByCol) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'staff' AND created_by = ?");
    $stmt->execute([$user['id']]);
    $tlStaffIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
}

$stats = [];
// Users: super_admin = all; team_leader = only their staff count
if (canAccessUsers($user['role'])) {
    if ($isSuperAdmin) {
        $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    } else {
        // team_leader: count only staff assigned to them
        if ($hasUserCreatedByCol) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'staff' AND created_by = ?");
            $stmt->execute([$user['id']]);
            $stats['users'] = (int) $stmt->fetchColumn();
        } else {
            $stats['users'] = 0;
        }
    }
}
$stats['categories'] = 0;
$stats['services'] = 0;
$stats['bookings'] = 0;
$stats['professionals'] = 0;
$stats['my_bookings'] = 0;

if (table_exists($pdo, 'categories')) {
    $stats['categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
}
if (table_exists($pdo, 'services')) {
    $stats['services'] = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
}
if (table_exists($pdo, 'bookings')) {
    if ($isProfessional) {
        // Professional: bookings assigned to them (handled below in my_bookings; bookings card can show same or 0)
        $hasAssigned = in_array('assigned_to', $bookingCols, true);
        if ($hasAssigned) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE assigned_to = ?');
            $stmt->execute([$user['id']]);
            $stats['bookings'] = (int) $stmt->fetchColumn();
        } else {
            $stats['bookings'] = 0;
        }
    } elseif ($isTeamLeader && ($hasBookingCreatedBy || $hasBookingAssignedBy || $hasBookingReferralCode)) {
        // TL: bookings created/assigned by TL or their staff, or with team referral code
        $allowedUserIds = array_merge([(int) $user['id']], array_map('intval', $tlStaffIds));
        $placeholders = implode(',', array_fill(0, count($allowedUserIds), '?'));
        $where = "(created_by IN ($placeholders) OR assigned_by IN ($placeholders)";
        $params = array_merge($allowedUserIds, $allowedUserIds);
        if ($hasBookingReferralCode && !empty($allowedUserIds)) {
            $stmt = $pdo->prepare("SELECT TRIM(referral_code) AS rc FROM users WHERE id IN ($placeholders) AND referral_code IS NOT NULL AND TRIM(referral_code) != ''");
            $stmt->execute($allowedUserIds);
            $refCodes = array_values(array_unique(array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'rc'))));
            if (!empty($refCodes)) {
                $refPh = implode(',', array_fill(0, count($refCodes), '?'));
                $where .= " OR referral_code IN ($refPh)";
                $params = array_merge($params, $refCodes);
            }
        }
        $where .= ')';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE $where");
        $stmt->execute($params);
        $stats['bookings'] = (int) $stmt->fetchColumn();
    } elseif ($isStaff) {
        $parts = [];
        $params = [];
        if ($hasBookingCreatedBy) {
            $parts[] = 'created_by = ?';
            $params[] = $user['id'];
        }
        if ($hasBookingAssignedBy) {
            $parts[] = 'assigned_by = ?';
            $params[] = $user['id'];
        }
        if ($hasBookingReferralCode) {
            $stmt = $pdo->prepare('SELECT TRIM(referral_code) FROM users WHERE id = ? AND referral_code IS NOT NULL AND TRIM(referral_code) != ""');
            $stmt->execute([$user['id']]);
            $staffCode = $stmt->fetchColumn();
            if ($staffCode !== false && $staffCode !== '') {
                $parts[] = 'referral_code = ?';
                $params[] = $staffCode;
            }
        }
        if (!empty($parts)) {
            $where = '(' . implode(' OR ', $parts) . ')';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE $where");
            $stmt->execute($params);
            $stats['bookings'] = (int) $stmt->fetchColumn();
        } else {
            $stats['bookings'] = 0;
        }
    } else {
        // super_admin: all bookings
        $stats['bookings'] = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    }
}
if (table_exists($pdo, 'professionals')) {
    if ($isSuperAdmin) {
        $stats['professionals'] = (int) $pdo->query('SELECT COUNT(*) FROM professionals')->fetchColumn();
    } elseif ($isTeamLeader && $hasProReferredBy) {
        // TL: only professionals referred by their staff
        if (!empty($tlStaffIds)) {
            $placeholders = implode(',', array_fill(0, count($tlStaffIds), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM professionals WHERE referred_by_user_id IN ($placeholders)");
            $stmt->execute($tlStaffIds);
            $stats['professionals'] = (int) $stmt->fetchColumn();
        } else {
            $stats['professionals'] = 0;
        }
    } elseif ($isStaff && $hasProReferredBy) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM professionals WHERE referred_by_user_id = ?');
        $stmt->execute([$user['id']]);
        $stats['professionals'] = (int) $stmt->fetchColumn();
    } elseif ($isTeamLeader) {
        $stats['professionals'] = 0; // TL without staff or without referred_by column
    } else {
        $stats['professionals'] = (int) $pdo->query('SELECT COUNT(*) FROM professionals')->fetchColumn();
    }
}
if ($isProfessional && table_exists($pdo, 'bookings')) {
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
    <?php if (canAccessUsers($user['role']) && isset($stats['users'])): ?>
        <div class="stat-card">
            <span class="stat-value"><?= $stats['users'] ?></span>
            <span class="stat-label"><?= $isTeamLeader ? 'My Staff' : 'Users' ?></span>
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
