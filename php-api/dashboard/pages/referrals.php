<?php

requireRole('super_admin', 'team_leader');
$pdo = getDb();

$message = '';
$error = '';

// Ensure required columns exist
$hasUserReferralCode = false;
$hasProReferredByUserId = false;
$hasProReferralCode = false;

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
    $hasUserReferralCode = $stmt && $stmt->rowCount() > 0;
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

$staffRows = [];
$proReferrers = [];

if ($error === '') {
    // Staff referral summary
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.referral_code,
                   COUNT(p.professionalId) AS total_referred
            FROM users u
            LEFT JOIN professionals p ON p.referred_by_user_id = u.id
            WHERE u.role = 'staff'
            GROUP BY u.id, u.name, u.email, u.phone, u.referral_code
            ORDER BY total_referred DESC, u.id DESC";
    try {
        $stmt = $pdo->query($sql);
        $staffRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $error = 'Failed to load staff referrals: ' . $e->getMessage();
    }

    // Professional referral summary (professionals who have user_id and a referral_code)
    if ($error === '' && $hasProReferralCode) {
        $sql = "SELECT p.professionalId, p.name, p.phone, p.email, p.referral_code,
                       u.id AS user_id,
                       COUNT(r.professionalId) AS total_referred
                FROM professionals p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN professionals r ON r.referred_by_user_id = u.id
                GROUP BY p.professionalId, p.name, p.phone, p.email, p.referral_code, u.id
                HAVING p.referral_code IS NOT NULL
                ORDER BY total_referred DESC, p.professionalId DESC";
        try {
            $stmt = $pdo->query($sql);
            $proReferrers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $error = 'Failed to load professional referrals: ' . $e->getMessage();
        }
    }
}
?>
<div class="page-header">
    <h1>Referral Management</h1>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<?php if ($error === ''): ?>
<div class="card mt-2">
    <h2 class="h5 mb-2">Staff Referral Codes</h2>
    <p class="text-muted small">Each staff has a unique code. Use this to track professionals they onboarded.</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Referral Code</th>
                    <th>Total Professionals Referred</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffRows as $row): ?>
                    <tr>
                        <td><?= (int) $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                        <td><code><?= htmlspecialchars($row['referral_code'] ?? '-') ?></code></td>
                        <td><?= (int) $row['total_referred'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($staffRows)): ?>
                    <tr><td colspan="6" class="text-muted">No staff referrals found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($hasProReferralCode): ?>
<div class="card mt-2">
    <h2 class="h5 mb-2">Professional Referral Codes</h2>
    <p class="text-muted small">Approved professionals with referral codes and how many professionals they referred.</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Professional ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Referral Code</th>
                    <th>User ID</th>
                    <th>Total Professionals Referred</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proReferrers as $row): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($row['professionalId']) ?></code></td>
                        <td><?= htmlspecialchars($row['name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['email'] ?? '-') ?></td>
                        <td><code><?= htmlspecialchars($row['referral_code'] ?? '-') ?></code></td>
                        <td><?= (int) $row['user_id'] ?></td>
                        <td><?= (int) $row['total_referred'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($proReferrers)): ?>
                    <tr><td colspan="7" class="text-muted">No professional referrals found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

