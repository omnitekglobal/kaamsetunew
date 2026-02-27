<?php

requireRole('super_admin', 'team_leader', 'staff', 'professional');
$pdo = getDb();

$canAssign = in_array($user['role'], ['super_admin', 'team_leader', 'staff'], true);
$isProfessional = ($user['role'] ?? '') === 'professional';
$isStaff = ($user['role'] ?? '') === 'staff';
$isTeamLeader = ($user['role'] ?? '') === 'team_leader';

$message = '';
$error = '';

$tableExists = false;
$hasStatusColumn = false;
$hasAssignedColumn = false;
$hasUserCreatedByCol = false;
$logExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'bookings'");
    $tableExists = $stmt && $stmt->rowCount() > 0;
    if ($tableExists) {
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings");
        $bookingCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $hasStatusColumn = in_array('status', $bookingCols, true);
        $hasAssignedColumn = in_array('assigned_to', $bookingCols, true);
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
    $hasUserCreatedByCol = $stmt && $stmt->rowCount() > 0;
    $logExists = $pdo->query("SHOW TABLES LIKE 'booking_log'")->rowCount() > 0;
} catch (Throwable $e) {}

// Assign booking to professional (staff / team_leader / super)
if ($canAssign && $tableExists && $hasAssignedColumn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'assign') {
    $bookingId = trim($_POST['booking_id'] ?? '');
    $assignedTo = (int) ($_POST['assigned_to'] ?? 0);
    if (!$bookingId || !$assignedTo) {
        $error = 'Booking and professional are required.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ? AND role = ? AND is_active = 1');
        $stmt->execute([$assignedTo, 'professional']);
        $pro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pro) {
            $error = 'Invalid professional.';
        } else {
            $stmt = $pdo->prepare('SELECT bookingId, assigned_to FROM bookings WHERE bookingId = ?');
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$booking) {
                $error = 'Booking not found.';
            } else {
                $oldAssigned = $booking['assigned_to'] ?? null;
                $assignOk = false;
                try {
                    $pdo->prepare('UPDATE bookings SET status = ?, assigned_to = ?, assigned_by = ?, assigned_at = NOW() WHERE bookingId = ?')
                        ->execute(['assigned', $assignedTo, $user['id'], $bookingId]);
                    $assignOk = true;
                    $message = 'Booking assigned to ' . htmlspecialchars($pro['name']) . '.';
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    if (strpos($msg, "column 'status'") !== false || strpos($msg, 'Data truncated') !== false) {
                        $pdo->prepare('UPDATE bookings SET assigned_to = ?, assigned_by = ?, assigned_at = NOW() WHERE bookingId = ?')
                            ->execute([$assignedTo, $user['id'], $bookingId]);
                        $assignOk = true;
                        $message = 'Booking assigned to ' . htmlspecialchars($pro['name']) . '. Run migration 004_fix_bookings_status_varchar.sql to show status correctly.';
                    } else {
                        throw $e;
                    }
                }
                if ($assignOk) {
                    $logExists = $pdo->query("SHOW TABLES LIKE 'booking_log'")->rowCount() > 0;
                    if ($logExists) {
                        $details = json_encode([
                            'assigned_to' => $assignedTo,
                            'assigned_to_name' => $pro['name'],
                            'assigned_by' => $user['id'],
                            'assigned_by_name' => $user['name'],
                            'old_assigned_to' => $oldAssigned,
                        ]);
                        $pdo->prepare('INSERT INTO booking_log (booking_id, action, by_user_id, details) VALUES (?, ?, ?, ?)')
                            ->execute([$bookingId, 'assigned', $user['id'], $details]);
                    }
                }
            }
        }
    }
}

$bookings = [];
$professionalsForAssign = [];
$totalBookings = 0;
$bookingsPage = max(1, (int) ($_GET['p'] ?? 1));
$bookingsPerPage = 20;
$search = trim($_GET['search'] ?? '');

if ($tableExists) {
    try {
        $orderCol = 'created_at';
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'created_at'");
        if (!$stmt || $stmt->rowCount() === 0) $orderCol = 'bookingId';
        $where = '1=1';
        $params = [];
        if ($isProfessional && $hasAssignedColumn) {
            $where = 'assigned_to = ?';
            $params[] = $user['id'];
        } elseif ($isTeamLeader && (in_array('created_by', $bookingCols ?? [], true) || in_array('assigned_by', $bookingCols ?? [], true))) {
            // TL sees bookings created or assigned by themselves or their assigned staff.
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
            // Staff should see only bookings they created or assigned.
            $parts = [];
            if (in_array('created_by', $bookingCols ?? [], true)) {
                $parts[] = 'created_by = ?';
                $params[] = $user['id'];
            }
            if (in_array('assigned_by', $bookingCols ?? [], true)) {
                $parts[] = 'assigned_by = ?';
                $params[] = $user['id'];
            }
            if (!empty($parts)) {
                $where = '(' . implode(' OR ', $parts) . ')';
            }
        }
        if ($search !== '') {
            $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR service LIKE ? OR bookingId LIKE ?)';
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        $countSql = "SELECT COUNT(*) FROM bookings WHERE $where";
        $stmt = $params ? $pdo->prepare($countSql) : $pdo->query($countSql);
        $stmt->execute($params);
        $totalBookings = (int) $stmt->fetchColumn();

        $offset = ($bookingsPage - 1) * $bookingsPerPage;
        $listSql = "SELECT * FROM bookings WHERE $where ORDER BY COALESCE(assigned_at, created_at, 1) DESC, bookingId DESC LIMIT $bookingsPerPage OFFSET $offset";
        $stmt = $params ? $pdo->prepare($listSql) : $pdo->query($listSql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $bookings = [];
    }
}
if ($canAssign) {
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'professional' AND is_active = 1 ORDER BY name");
    $professionalsForAssign = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$userNames = [];
if (!empty($bookings)) {
    $ids = [];
    if ($isProfessional) $ids[(int)$user['id']] = true;
    foreach ($bookings as $b) {
        if (!empty($b['assigned_to'])) $ids[(int)$b['assigned_to']] = true;
        if (!empty($b['assigned_by'])) $ids[(int)$b['assigned_by']] = true;
        if (!empty($b['created_by'])) $ids[(int)$b['created_by']] = true;
    }
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($ids));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $userNames[(int)$row['id']] = $row['name'];
        }
    }
    if ($isProfessional) {
        $userNames[(int)$user['id']] = $user['name'];
    }
}
?>
<div class="page-header">
    <h1>Bookings</h1>
    <?php if ($isProfessional): ?>
        <p class="text-muted">Showing bookings assigned to you.</p>
    <?php elseif ($isTeamLeader): ?>
        <p class="text-muted">Showing bookings created or assigned by you or your staff.</p>
    <?php elseif ($isStaff): ?>
        <p class="text-muted">Showing bookings you created or assigned.</p>
    <?php endif; ?>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!$tableExists): ?>
    <div class="alert alert-warning">Bookings table does not exist. Create it from your Next.js app or run the migrations.</div>
<?php else: ?>
    <form method="get" class="toolbar">
        <input type="hidden" name="page" value="bookings">
        <input type="text" name="search" placeholder="Search by name, email, phone, service, ID" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
    <?php
    $paginationQueryParams = ['page' => 'bookings'];
    if ($search !== '') $paginationQueryParams['search'] = $search;
    $hasLanguageColumn = !empty($bookings) && array_key_exists('language', $bookings[0]);
    ?>
    <div class="card overflow-x">
        <table class="table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th><?= $isProfessional ? 'Customer' : 'Name' ?></th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Service</th>
                    <th>Pincode</th>
                    <?php if ($hasLanguageColumn): ?><th>Language</th><?php endif; ?>
                    <?php if ($hasStatusColumn): ?><th>Status</th><?php endif; ?>
                    <?php if ($hasAssignedColumn && !$isProfessional): ?><th>Assigned to</th><?php endif; ?>
                    <?php if ($hasAssignedColumn): ?>
                        <th>Assigned by</th>
                        <th>Assigned at</th>
                    <?php endif; ?>
                    <?php if (isset($bookings[0]['created_at'])): ?><th>Created at</th><?php endif; ?>
                    <?php if (isset($bookings[0]['created_by']) && !$isProfessional): ?><th>Created by</th><?php endif; ?>
                    <?php if ($canAssign && $hasAssignedColumn): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($b['bookingId'] ?? '') ?></code></td>
                        <td><?= htmlspecialchars($b['name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($b['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($b['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($b['service'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($b['pincode'] ?? '-') ?></td>
                        <?php if ($hasLanguageColumn): ?><td><?= htmlspecialchars($b['language'] ?? '-') ?></td><?php endif; ?>
                        <?php if ($hasStatusColumn): ?>
                            <td><span class="badge <?= ($b['status'] ?? '') === 'assigned' ? 'badge-success' : 'badge-warning' ?>"><?= htmlspecialchars($b['status'] ?? 'pending') ?></span></td>
                        <?php endif; ?>
                        <?php if ($hasAssignedColumn && !$isProfessional): ?>
                            <td><?= htmlspecialchars($userNames[(int)($b['assigned_to'] ?? 0)] ?? '-') ?></td>
                        <?php endif; ?>
                        <?php if ($hasAssignedColumn): ?>
                            <td><?= htmlspecialchars($userNames[(int)($b['assigned_by'] ?? 0)] ?? '-') ?></td>
                            <td><?= !empty($b['assigned_at']) ? htmlspecialchars($b['assigned_at']) : '-' ?></td>
                        <?php endif; ?>
                        <?php if (isset($bookings[0]['created_at'])): ?><td><?= htmlspecialchars($b['created_at'] ?? '-') ?></td><?php endif; ?>
                        <?php if (isset($bookings[0]['created_by']) && !$isProfessional): ?><td><?= htmlspecialchars($userNames[(int)($b['created_by'] ?? 0)] ?? '-') ?></td><?php endif; ?>
                        <?php if ($canAssign && $hasAssignedColumn): ?>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Assign this booking to the selected professional?');">
                                    <input type="hidden" name="_action" value="assign">
                                    <input type="hidden" name="page" value="bookings">
                                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($b['bookingId'] ?? '') ?>">
                                    <select name="assigned_to" required style="min-width:140px;">
                                        <option value="">Select professional</option>
                                        <?php foreach ($professionalsForAssign as $pro): ?>
                                            <option value="<?= (int)$pro['id'] ?>" <?= ((int)($b['assigned_to'] ?? 0)) === (int)$pro['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pro['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Assign</button>
                                </form>
                                <?php if ($logExists): ?>
                                    <a href="?page=bookings&log=<?= urlencode($b['bookingId'] ?? '') ?>" class="btn btn-sm btn-outline">Log</a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($bookings)): ?>
            <p class="p-3 text-muted">No bookings found.</p>
        <?php endif; ?>
        <?php
        $paginationTotal = $totalBookings;
        $paginationPage = $bookingsPage;
        $paginationPerPage = $bookingsPerPage;
        require __DIR__ . '/../includes/pagination.php';
        ?>
    </div>
    <?php if ($tableExists && $canAssign): ?>
        <?php
        $logExists = $pdo->query("SHOW TABLES LIKE 'booking_log'")->rowCount() > 0;
        $logs = [];
        if ($logExists && !empty($_GET['log']) && preg_match('/^[A-Za-z0-9_-]+$/', $_GET['log'])) {
            $stmt = $pdo->prepare('SELECT id, booking_id, action, by_user_id, created_at, details FROM booking_log WHERE booking_id = ? ORDER BY created_at DESC');
            $stmt->execute([$_GET['log']]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $logUserIds = array_unique(array_filter(array_column($logs, 'by_user_id')));
            foreach ($logUserIds as $uid) {
                if (!isset($userNames[(int)$uid])) {
                    $u = $pdo->prepare('SELECT id, name FROM users WHERE id = ?');
                    $u->execute([$uid]);
                    $r = $u->fetch(PDO::FETCH_ASSOC);
                    $userNames[(int)$uid] = $r ? $r['name'] : 'User #' . $uid;
                }
            }
        }
        ?>
        <?php if (!empty($logs)): ?>
            <div class="card mt-2">
                <h3>Log for booking <?= htmlspecialchars($_GET['log']) ?></h3>
                <p><a href="?page=bookings" class="btn btn-outline btn-sm">Back to list</a></p>
                <table class="table">
                    <thead><tr><th>Time</th><th>Action</th><th>By</th><th>Details</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($userNames[(int)($log['by_user_id'] ?? 0)] ?? (int)($log['by_user_id'] ?? '-')) ?></td>
                                <td><pre style="margin:0;font-size:0.85em;"><?= htmlspecialchars($log['details'] ?? '') ?></pre></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
