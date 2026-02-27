<?php

requireRole('super_admin', 'team_leader', 'staff', 'professional');
$pdo = getDb();

$isProfessional = ($user['role'] ?? '') === 'professional';
$isStaff = ($user['role'] ?? '') === 'staff';
$isTeamLeader = ($user['role'] ?? '') === 'team_leader';

$tableExists = false;
$bookingCols = [];
$hasUserCreatedByCol = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'bookings'");
    $tableExists = $stmt && $stmt->rowCount() > 0;
    if ($tableExists) {
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings");
        $bookingCols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
    $hasUserCreatedByCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

$hasAssignedColumn = in_array('assigned_to', $bookingCols, true);
$customers = [];
$totalCustomers = 0;
$search = trim($_GET['search'] ?? '');
$perPage = 20;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($currentPage - 1) * $perPage;

if ($tableExists) {
    $where = '1=1';
    $params = [];
    if ($isProfessional && $hasAssignedColumn) {
        $where = 'assigned_to = ?';
        $params[] = $user['id'];
    } elseif ($isTeamLeader && (in_array('created_by', $bookingCols, true) || in_array('assigned_by', $bookingCols, true))) {
        // TL sees customers from bookings created/assigned by themselves or their assigned staff.
        $allowedUserIds = [(int) $user['id']];
        if ($hasUserCreatedByCol) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'staff' AND created_by = ?");
            $stmt->execute([$user['id']]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $allowedUserIds[] = (int) $row['id'];
            }
        }
        $placeholders = implode(',', array_fill(0, count($allowedUserIds), '?'));
        $where = "(created_by IN ($placeholders) OR assigned_by IN ($placeholders))";
        $params = array_merge($allowedUserIds, $allowedUserIds);
    } elseif ($isStaff) {
        $parts = [];
        if (in_array('created_by', $bookingCols, true)) {
            $parts[] = 'created_by = ?';
            $params[] = $user['id'];
        }
        if (in_array('assigned_by', $bookingCols, true)) {
            $parts[] = 'assigned_by = ?';
            $params[] = $user['id'];
        }
        if (!empty($parts)) {
            $where = '(' . implode(' OR ', $parts) . ')';
        }
    }
    if ($search !== '') {
        $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    try {
        // Count distinct customers (same name+email+phone = one customer)
        $countSql = "SELECT COUNT(*) FROM (
            SELECT 1 FROM bookings WHERE $where
            GROUP BY TRIM(COALESCE(name,'')), TRIM(COALESCE(email,'')), TRIM(COALESCE(phone,''))
        ) t";
        $stmt = $params ? $pdo->prepare($countSql) : $pdo->query($countSql);
        if ($params) $stmt->execute($params);
        $totalCustomers = (int) $stmt->fetchColumn();

        // List customers: user details only (name, email, phone, booking count, last booking)
        $listSql = "SELECT name, email, phone,
                    COUNT(*) AS booking_count,
                    MAX(COALESCE(created_at, '1970-01-01')) AS last_booking
                    FROM bookings
                    WHERE $where
                    GROUP BY TRIM(COALESCE(name,'')), TRIM(COALESCE(email,'')), TRIM(COALESCE(phone,''))
                    ORDER BY last_booking DESC
                    LIMIT $perPage OFFSET $offset";
        $stmt = $params ? $pdo->prepare($listSql) : $pdo->query($listSql);
        if ($params) $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $customers = [];
    }
}

$paginationQueryParams = ['page' => 'customers'];
if ($search !== '') $paginationQueryParams['search'] = $search;
$paginationTotal = $totalCustomers;
$paginationPage = $currentPage;
$paginationPerPage = $perPage;
?>
<div class="page-header">
    <h1>Customers</h1>
    <p class="text-muted small">Customers from bookings — user details only. <?= $isStaff ? 'Showing customers from your created or assigned bookings.' : ($isTeamLeader ? 'Showing customers from bookings created or assigned by you or your staff.' : ($isProfessional ? 'Showing customers from bookings assigned to you.' : '')) ?></p>
</div>

<?php if (!$tableExists): ?>
<div class="alert alert-warning">Bookings table does not exist. Customers are derived from bookings.</div>
<?php else: ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="customers">
    <input type="text" name="search" placeholder="Search by name, email or phone" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>

<div class="card overflow-x mt-2">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Bookings</th>
                <th>Last booking</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                <td><?= (int) ($c['booking_count'] ?? 0) ?></td>
                <td><?= (isset($c['last_booking']) && $c['last_booking'] !== '1970-01-01 00:00:00') ? htmlspecialchars(date('M j, Y', strtotime($c['last_booking']))) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?>
            <tr><td colspan="5" class="text-muted">No customers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
if ($totalCustomers > 0) {
    require __DIR__ . '/../includes/pagination.php';
}
?>
<?php endif; ?>
