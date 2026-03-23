<?php

requireRole('super_admin');
$pdo = getDb();

function reportTableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function reportHasColumn(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "` LIKE " . $pdo->quote($column));
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function normalizePincodeValue($value): string {
    $v = trim((string) ($value ?? ''));
    return $v === '' ? 'Unknown' : $v;
}

// --- Table/column checks ---
$usersTableExists = reportTableExists($pdo, 'users');
$customersTableExists = reportTableExists($pdo, 'customers');
$professionalsTableExists = reportTableExists($pdo, 'professionals');

$usersHasPincode = $usersTableExists && reportHasColumn($pdo, 'users', 'pincode');
$usersHasLastLoginDate = $usersTableExists && reportHasColumn($pdo, 'users', 'last_login_date');
$usersHasLastLoginTime = $usersTableExists && reportHasColumn($pdo, 'users', 'last_login_time');
$usersHasIsActive = $usersTableExists && reportHasColumn($pdo, 'users', 'is_active');

$customersHasPincode = $customersTableExists && reportHasColumn($pdo, 'customers', 'pincode');
$professionalsHasPincode = $professionalsTableExists && reportHasColumn($pdo, 'professionals', 'pincode');
$professionalsHasServices = $professionalsTableExists && reportHasColumn($pdo, 'professionals', 'services');

// --- Report 1: Pincode-wise (staff/professional/customer) ---
$staffByPincode = [];
$professionalsByPincode = [];
$customersByPincode = [];
$allPincodes = [];

if ($usersHasPincode) {
    $stmt = $pdo->query("SELECT pincode, COUNT(*) AS cnt FROM users WHERE role = 'staff' GROUP BY pincode");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as $r) {
        $pin = normalizePincodeValue($r['pincode'] ?? '');
        $staffByPincode[$pin] = (int) ($r['cnt'] ?? 0);
        $allPincodes[$pin] = true;
    }
}

if ($professionalsHasPincode) {
    $stmt = $pdo->query('SELECT pincode, COUNT(*) AS cnt FROM professionals GROUP BY pincode');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as $r) {
        $pin = normalizePincodeValue($r['pincode'] ?? '');
        $professionalsByPincode[$pin] = (int) ($r['cnt'] ?? 0);
        $allPincodes[$pin] = true;
    }
}

if ($customersHasPincode) {
    $stmt = $pdo->query('SELECT pincode, COUNT(*) AS cnt FROM customers GROUP BY pincode');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as $r) {
        $pin = normalizePincodeValue($r['pincode'] ?? '');
        $customersByPincode[$pin] = (int) ($r['cnt'] ?? 0);
        $allPincodes[$pin] = true;
    }
}

$pincodeReport = [];
foreach (array_keys($allPincodes) as $pin) {
    $pincodeReport[] = [
        'pincode' => $pin,
        'staff_count' => (int) ($staffByPincode[$pin] ?? 0),
        'professional_count' => (int) ($professionalsByPincode[$pin] ?? 0),
        'customer_count' => (int) ($customersByPincode[$pin] ?? 0),
    ];
}
usort($pincodeReport, static function (array $a, array $b): int {
    if ($a['pincode'] === 'Unknown' && $b['pincode'] !== 'Unknown') return 1;
    if ($a['pincode'] !== 'Unknown' && $b['pincode'] === 'Unknown') return -1;
    return strcmp((string) $a['pincode'], (string) $b['pincode']);
});

// --- Report 2: Service-wise professional counts ---
$serviceWiseProfessionals = [];
if ($professionalsHasServices) {
    $stmt = $pdo->query("SELECT services FROM professionals WHERE services IS NOT NULL AND TRIM(services) != ''");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($rows as $r) {
        $rawServices = (string) ($r['services'] ?? '');
        $parts = array_map('trim', explode(',', $rawServices));
        $parts = array_values(array_filter($parts, static fn($s) => $s !== ''));
        $uniquePerProfessional = array_values(array_unique($parts));
        foreach ($uniquePerProfessional as $svc) {
            if (!isset($serviceWiseProfessionals[$svc])) {
                $serviceWiseProfessionals[$svc] = 0;
            }
            $serviceWiseProfessionals[$svc]++;
        }
    }
    arsort($serviceWiseProfessionals);
}

// --- Report 3: Inactive staff (1 month) ---
$inactiveStaffRows = [];
$canBuildInactiveStaff = $usersTableExists && $usersHasLastLoginDate && $usersHasLastLoginTime;
if ($canBuildInactiveStaff) {
    $statusSelect = $usersHasIsActive ? ', is_active' : '';
    $inactiveSql = "
        SELECT id, name, email, phone, role, last_login_date, last_login_time{$statusSelect}
        FROM users
        WHERE role = 'staff'
          AND (
              last_login_date IS NULL
              OR TIMESTAMP(last_login_date, COALESCE(last_login_time, '00:00:00')) < (NOW() - INTERVAL 1 MONTH)
          )
        ORDER BY
          CASE WHEN last_login_date IS NULL THEN 0 ELSE 1 END,
          last_login_date ASC,
          last_login_time ASC
    ";
    $stmt = $pdo->query($inactiveSql);
    $inactiveStaffRows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}
?>

<div class="page-header">
    <h1>Reports</h1>
    <p>Staff, professionals, and customers summary reports for super admin.</p>
</div>

<?php if (!$usersHasPincode): ?>
    <div class="alert alert-warning">
        Staff pincode-wise report is limited because <code>users.pincode</code> column was not found.
        Add this column if you want exact staff pincode counts.
    </div>
<?php endif; ?>

<div class="card overflow-x">
    <div class="p-3">
        <h3 class="h5">Pincode-wise report (Staff / Professional / Customer)</h3>
        <p class="text-muted small">Grouped by pincode. Empty values are shown as <strong>Unknown</strong>.</p>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Pincode</th>
                <th>Staff</th>
                <th>Professionals</th>
                <th>Customers</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pincodeReport as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['pincode']) ?></td>
                    <td><?= (int) $row['staff_count'] ?></td>
                    <td><?= (int) $row['professional_count'] ?></td>
                    <td><?= (int) $row['customer_count'] ?></td>
                    <td><?= (int) ($row['staff_count'] + $row['professional_count'] + $row['customer_count']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pincodeReport)): ?>
                <tr>
                    <td colspan="5" class="text-muted">No pincode data found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card overflow-x mt-2">
    <div class="p-3">
        <h3 class="h5">Service-wise professionals report</h3>
        <p class="text-muted small">Counts unique professionals per service name.</p>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Professionals</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($serviceWiseProfessionals as $serviceName => $count): ?>
                <tr>
                    <td><?= htmlspecialchars($serviceName) ?></td>
                    <td><?= (int) $count ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($serviceWiseProfessionals)): ?>
                <tr>
                    <td colspan="2" class="text-muted">No service mapping data found in professionals.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card overflow-x mt-2">
    <div class="p-3">
        <h3 class="h5">Inactive staff report (last login older than 1 month)</h3>
        <p class="text-muted small">Includes staff who never logged in.</p>
    </div>
    <?php if (!$canBuildInactiveStaff): ?>
        <div class="p-3 text-muted">
            Last-login columns not available. Run migration:
            <code>database/migrations/016_users_last_login_fields.sql</code>
        </div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <?php if ($usersHasIsActive): ?><th>Status</th><?php endif; ?>
                <th>Last Login Date</th>
                <th>Last Login Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inactiveStaffRows as $row): ?>
                <tr>
                    <td><?= (int) ($row['id'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string) ($row['name'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['phone'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['email'] ?? '-')) ?></td>
                    <?php if ($usersHasIsActive): ?>
                        <td><?= !empty($row['is_active']) ? 'Active' : 'Inactive' ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars((string) ($row['last_login_date'] ?? '-')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['last_login_time'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($inactiveStaffRows)): ?>
                <tr>
                    <td colspan="<?= $usersHasIsActive ? '7' : '6' ?>" class="text-muted">No inactive staff found for the last 1 month.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
