<?php

requireRole('super_admin', 'team_leader', 'staff');
$pdo = getDb();

$isStaff = ($user['role'] ?? '') === 'staff';
$isTeamLeader = ($user['role'] ?? '') === 'team_leader';

$tableExists = false;
$staffReferralCode = '';
$hasUserReferralCode = false;
$tlStaffIds = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'professional_requests'");
    $tableExists = $stmt && $stmt->rowCount() > 0;
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
    $hasUserReferralCode = $stmt && $stmt->rowCount() > 0;
    if ($isStaff && $hasUserReferralCode) {
        $stmt = $pdo->prepare('SELECT TRIM(referral_code) FROM users WHERE id = ? AND referral_code IS NOT NULL AND TRIM(referral_code) != ""');
        $stmt->execute([$user['id']]);
        $staffReferralCode = (string) $stmt->fetchColumn();
    }
    if ($isTeamLeader && $hasUserReferralCode) {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
        if ($stmt && $stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'staff' AND created_by = ?");
            $stmt->execute([$user['id']]);
            $tlStaffIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
        }
    }
} catch (Throwable $e) {}

$requests = [];
$total = 0;
$search = trim($_GET['search'] ?? '');
$perPage = 20;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($currentPage - 1) * $perPage;

if ($tableExists) {
    $where = '1=1';
    $params = [];
    if ($isStaff) {
        if ($staffReferralCode !== '') {
            $where = 'referral_code = ?';
            $params[] = $staffReferralCode;
        } else {
            $where = '1=0';
        }
    } elseif ($isTeamLeader && $hasUserReferralCode) {
        $allowedUserIds = array_merge([(int) $user['id']], array_map('intval', $tlStaffIds));
        if (!empty($allowedUserIds)) {
            $ph = implode(',', array_fill(0, count($allowedUserIds), '?'));
            $stmt = $pdo->prepare("SELECT TRIM(referral_code) AS rc FROM users WHERE id IN ($ph) AND referral_code IS NOT NULL AND TRIM(referral_code) != ''");
            $stmt->execute($allowedUserIds);
            $refCodes = array_values(array_unique(array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'rc'))));
            if (!empty($refCodes)) {
                $refPh = implode(',', array_fill(0, count($refCodes), '?'));
                $where = "referral_code IN ($refPh)";
                $params = $refCodes;
            }
        }
    }
    if ($search !== '') {
        $where .= (strpos($where, 'AND') !== false ? ' AND ' : ' AND ') . '(phone LIKE ? OR referral_code LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    try {
        $countSql = "SELECT COUNT(*) FROM professional_requests WHERE $where";
        $stmt = $params ? $pdo->prepare($countSql) : $pdo->query($countSql);
        if ($params) $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $listSql = "SELECT id, phone, referral_code, created_at FROM professional_requests WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
        $stmt = $params ? $pdo->prepare($listSql) : $pdo->query($listSql);
        if ($params) $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $requests = [];
    }
}

$paginationQueryParams = ['page' => 'professional_requests'];
if ($search !== '') $paginationQueryParams['search'] = $search;
$paginationTotal = $total;
$paginationPage = $currentPage;
$paginationPerPage = $perPage;
?>
<div class="page-header">
    <h1>Professional Requests</h1>
    <p class="text-muted small">Leads from the “request to join” form (mobile + optional referral code). Follow up and invite them to register as professionals.</p>
</div>

<?php if (!$tableExists): ?>
<div class="alert alert-warning">Table <code>professional_requests</code> is missing. Run migration: <code>database/migrations/011_professional_requests.sql</code></div>
<?php else: ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="professional_requests">
    <input type="text" name="search" placeholder="Search by phone or referral code" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>

<div class="card overflow-x mt-2">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Phone</th>
                <th>Referral code</th>
                <th>Submitted at</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td><?= (int) $r['id'] ?></td>
                <td><?= htmlspecialchars($r['phone'] ?? '-') ?></td>
                <td><code><?= htmlspecialchars($r['referral_code'] ?? '-') ?></code></td>
                <td><?= isset($r['created_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($r['created_at']))) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
            <tr><td colspan="4" class="text-muted">No professional requests yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
if ($total > 0) {
    require __DIR__ . '/../includes/pagination.php';
}
?>
<?php endif; ?>
