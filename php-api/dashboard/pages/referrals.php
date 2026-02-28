<?php

$user = requireRole('super_admin', 'team_leader');
$pdo = getDb();

$message = '';
$error = '';

// Ensure required columns exist
$hasUserReferralCode = false;
$hasUserCreatedByCol = false;
$hasProReferredByUserId = false;
$hasProReferralCode = false;

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
    $hasUserReferralCode = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
    $hasUserCreatedByCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referred_by_user_id'");
    $hasProReferredByUserId = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referral_code'");
    $hasProReferralCode = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

if (!$hasUserReferralCode || !$hasProReferredByUserId) {
    $error = 'Referral tracking columns are missing. Ensure migrations 005_professionals_referral_code.sql and 006_users_referral_code.sql have been run.';
}

// Team leader sees only their assigned staff (and professionals referred by those staff)
$scopeStaffOnly = ($user['role'] === 'team_leader' && $hasUserCreatedByCol);

$staffRows = [];
$proReferrers = [];

if ($error === '') {
    // Staff referral summary (super_admin: all staff; team_leader: only staff where created_by = TL id)
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.referral_code,
                   COUNT(p.professionalId) AS total_referred
            FROM users u
            LEFT JOIN professionals p ON p.referred_by_user_id = u.id
            WHERE u.role = 'staff'";
    $staffParams = [];
    if ($scopeStaffOnly) {
        $sql .= " AND u.created_by = ?";
        $staffParams[] = (int) $user['id'];
    }
    $sql .= " GROUP BY u.id, u.name, u.email, u.phone, u.referral_code
            ORDER BY total_referred DESC, u.id DESC";
    try {
        $stmt = $staffParams ? $pdo->prepare($sql) : $pdo->query($sql);
        if ($staffParams) $stmt->execute($staffParams);
        $staffRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $error = 'Failed to load staff referrals: ' . $e->getMessage();
    }

    // Professional referral summary: approved professionals with referral codes and how many they referred.
    // Team leader: only professionals that were referred by their staff (referred_by_user_id in TL's staff).
    if ($error === '' && $hasProReferralCode) {
        $sql = "SELECT p.professionalId, p.name, p.phone, p.email, p.referral_code,
                       u.id AS user_id,
                       COUNT(r.professionalId) AS total_referred
                FROM professionals p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN professionals r ON r.referred_by_user_id = u.id
                WHERE p.referral_code IS NOT NULL";
        $proParams = [];
        if ($scopeStaffOnly) {
            $sql .= " AND p.referred_by_user_id IN (SELECT id FROM users WHERE role = 'staff' AND created_by = ?)";
            $proParams[] = (int) $user['id'];
        }
        $sql .= " GROUP BY p.professionalId, p.name, p.phone, p.email, p.referral_code, u.id
                ORDER BY total_referred DESC, p.professionalId DESC";
        try {
            $stmt = $proParams ? $pdo->prepare($sql) : $pdo->query($sql);
            if ($proParams) $stmt->execute($proParams);
            $proReferrers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $error = 'Failed to load professional referrals: ' . $e->getMessage();
        }
    }
}
?>
<div class="page-header">
    <h1>Referral Management</h1>
    <?php if ($scopeStaffOnly): ?>
    <p class="text-muted small">Showing only staff assigned to you and professionals they referred.</p>
    <?php endif; ?>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<?php if ($error === ''): ?>
<div class="card mt-2">
    <h2 class="h5 mb-2">Staff Referral Codes</h2>
    <p class="text-muted small">Each staff has a unique code and referral link. Anyone who clicks the link lands on professional/register with the code pre-filled.</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Referral Code</th>
                    <th>Referral Link</th>
                    <th>Total Professionals Referred</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffRows as $row): ?>
                    <?php $code = $row['referral_code'] ?? ''; $staffLink = (FRONTEND_URL !== '' && $code !== '') ? FRONTEND_URL . '/professional/register?ref=' . urlencode($code) : ''; ?>
                    <tr>
                        <td><?= (int) $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                        <td><code><?= htmlspecialchars($code ?: '-') ?></code></td>
                        <td><?php if ($staffLink): ?><a href="<?= htmlspecialchars($staffLink) ?>" target="_blank" rel="noopener">Open link</a><br><small class="text-muted" style="word-break: break-all;"><?= htmlspecialchars($staffLink) ?></small><?php else: ?>—<?php endif; ?></td>
                        <td><?= (int) $row['total_referred'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($staffRows)): ?>
                    <tr><td colspan="7" class="text-muted">No staff referrals found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($hasProReferralCode): ?>
<div class="card mt-2">
    <h2 class="h5 mb-2">Professional Referral Codes</h2>
    <p class="text-muted small">Approved professionals with referral codes and link. Anyone who clicks the link lands on professional/register with the code pre-filled.</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Professional ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Referral Code</th>
                    <th>Referral Link</th>
                    <th>User ID</th>
                    <th>Total Professionals Referred</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proReferrers as $row): ?>
                    <?php $pCode = $row['referral_code'] ?? ''; $proLink = (FRONTEND_URL !== '' && $pCode !== '') ? FRONTEND_URL . '/professional/register?ref=' . urlencode($pCode) : ''; ?>
                    <tr>
                        <td><code><?= htmlspecialchars($row['professionalId']) ?></code></td>
                        <td><?= htmlspecialchars($row['name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                        <td><code><?= htmlspecialchars($pCode ?: '-') ?></code></td>
                        <td><?php if ($proLink): ?><a href="<?= htmlspecialchars($proLink) ?>" target="_blank" rel="noopener">Open link</a><br><small class="text-muted" style="word-break: break-all;"><?= htmlspecialchars($proLink) ?></small><?php else: ?>—<?php endif; ?></td>
                        <td><?= (int) $row['user_id'] ?></td>
                        <td><?= (int) $row['total_referred'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($proReferrers)): ?>
                    <tr><td colspan="8" class="text-muted">No professional referrals found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

