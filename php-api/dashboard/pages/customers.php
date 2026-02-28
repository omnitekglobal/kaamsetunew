<?php

requireRole('super_admin', 'team_leader', 'staff', 'professional');
$pdo = getDb();

$message = '';
$error = '';

$isProfessional = ($user['role'] ?? '') === 'professional';
$isStaff = ($user['role'] ?? '') === 'staff';
$isTeamLeader = ($user['role'] ?? '') === 'team_leader';
$canAddCustomer = ($user['role'] ?? '') === 'staff';

$customersTableExists = false;
$customerCols = [];
$hasUserCreatedByCol = false;
$staffReferralCode = '';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
    $customersTableExists = $stmt && $stmt->rowCount() > 0;
    if ($customersTableExists) {
        $stmt = $pdo->query("SHOW COLUMNS FROM customers");
        $customerCols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
    $hasUserCreatedByCol = $stmt && $stmt->rowCount() > 0;
    if ($isStaff) {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
        if ($stmt && $stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('SELECT referral_code FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $staffReferralCode = trim($row['referral_code'] ?? '');
        }
    }
} catch (Throwable $e) {}

$hasCreatedByCol = in_array('created_by', $customerCols, true);
$hasCustomerReferralCode = in_array('referral_code', $customerCols, true);

// Add Customer: insert into customers table only. Bookings can be assigned to this customer later.
if ($canAddCustomer && $customersTableExists && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $language = trim($_POST['language'] ?? '');
    $referralCode = $isStaff && $staffReferralCode !== '' ? $staffReferralCode : trim($_POST['referral_code'] ?? '');
    if ($name === '' || $phone === '') {
        $error = 'Name and phone are required.';
    } else {
        $cols = ['name', 'phone', 'email', 'city', 'state', 'pincode', 'language', 'referral_code', 'created_at'];
        $placeholders = '?, ?, ?, ?, ?, ?, ?, ?, NOW()';
        $params = [$name, $phone, $email !== '' ? $email : null, $city !== '' ? $city : null, $state !== '' ? $state : null, $pincode !== '' ? $pincode : null, $language !== '' ? $language : null, $referralCode !== '' ? $referralCode : null];
        if ($hasCreatedByCol) {
            $cols[] = 'created_by';
            $placeholders .= ', ?';
            $params[] = (int) $user['id'];
        }
        try {
            $pdo->prepare('INSERT INTO customers (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')')->execute($params);
            $message = 'Customer added successfully. Bookings can be assigned to this customer in future.';
            header('Location: ' . DASHBOARD_BASE . '/index.php?page=customers&msg=' . urlencode($message));
            exit;
        } catch (Throwable $e) {
            $error = 'Failed to add customer: ' . $e->getMessage();
        }
    }
}

$customers = [];
$totalCustomers = 0;
$search = trim($_GET['search'] ?? '');
$perPage = 20;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($currentPage - 1) * $perPage;

// Check if bookings has customer_id for "Bookings" column
$bookingsHasCustomerId = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'customer_id'");
    $bookingsHasCustomerId = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

if ($customersTableExists) {
    $where = '1=1';
    $params = [];
    if ($isProfessional) {
        // Professionals don't create customers; show none or all depending on requirement. Show empty for professional.
        $where = '1=0';
    } elseif ($isTeamLeader && ($hasUserCreatedByCol || $hasCustomerReferralCode)) {
        $allowedUserIds = [(int) $user['id']];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'staff' AND created_by = ?");
        $stmt->execute([$user['id']]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $allowedUserIds[] = (int) $row['id'];
        }
        $placeholders = implode(',', array_fill(0, count($allowedUserIds), '?'));
        $where = "created_by IN ($placeholders)";
        $params = $allowedUserIds;
        if ($hasCustomerReferralCode && !empty($allowedUserIds)) {
            $stmt = $pdo->prepare("SELECT TRIM(referral_code) AS rc FROM users WHERE id IN ($placeholders) AND referral_code IS NOT NULL AND TRIM(referral_code) != ''");
            $stmt->execute($allowedUserIds);
            $refCodes = array_values(array_unique(array_filter(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'rc'))));
            if (!empty($refCodes)) {
                $refPh = implode(',', array_fill(0, count($refCodes), '?'));
                $where .= " OR referral_code IN ($refPh)";
                $params = array_merge($params, $refCodes);
            }
        }
        $where = "($where)";
    } elseif ($isStaff) {
        $parts = [];
        $params = [];
        if ($hasCreatedByCol) {
            $parts[] = 'created_by = ?';
            $params[] = (int) $user['id'];
        }
        if ($hasCustomerReferralCode && $staffReferralCode !== '') {
            $parts[] = 'referral_code = ?';
            $params[] = $staffReferralCode;
        }
        if (!empty($parts)) {
            $where = '(' . implode(' OR ', $parts) . ')';
        }
    }

    if ($search !== '') {
        $searchLike = "%$search%";
        $where .= ' AND (name LIKE ? OR COALESCE(email,\'\') LIKE ? OR phone LIKE ? OR COALESCE(city,\'\') LIKE ? OR COALESCE(state,\'\') LIKE ? OR COALESCE(pincode,\'\') LIKE ? OR COALESCE(language,\'\') LIKE ? OR COALESCE(referral_code,\'\') LIKE ?)';
        $params = array_merge($params, [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike]);
    }

    try {
        $countSql = "SELECT COUNT(*) FROM customers WHERE $where";
        $stmt = $params ? $pdo->prepare($countSql) : $pdo->query($countSql);
        if ($params) $stmt->execute($params);
        $totalCustomers = (int) $stmt->fetchColumn();

        $listSql = "SELECT * FROM customers WHERE $where ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset";
        $stmt = $params ? $pdo->prepare($listSql) : $pdo->query($listSql);
        if ($params) $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($bookingsHasCustomerId && !empty($customers)) {
            $ids = array_column($customers, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT customer_id, COUNT(*) AS cnt FROM bookings WHERE customer_id IN ($placeholders) GROUP BY customer_id");
            $stmt->execute($ids);
            $bookingCounts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $bookingCounts[(int)$row['customer_id']] = (int) $row['cnt'];
            }
            foreach ($customers as &$c) {
                $c['_booking_count'] = $bookingCounts[(int)($c['id'] ?? 0)] ?? 0;
            }
            unset($c);
        }

        $userNames = [];
        if (!empty($customers) && in_array('created_by', $customerCols, true)) {
            $ids = [];
            foreach ($customers as $c) {
                if (!empty($c['created_by'])) $ids[(int)$c['created_by']] = true;
            }
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id IN ($placeholders)");
                $stmt->execute(array_keys($ids));
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $userNames[(int)$row['id']] = $row['name'];
                }
            }
        }
    } catch (Throwable $e) {
        $customers = [];
        $userNames = [];
    }
} else {
    $userNames = [];
}

$paginationQueryParams = ['page' => 'customers'];
if ($search !== '') $paginationQueryParams['search'] = $search;
$paginationTotal = $totalCustomers;
$paginationPage = $currentPage;
$paginationPerPage = $perPage;
if (isset($_GET['msg'])) $message = $_GET['msg'];
?>
<div class="page-header">
    <h1>Customers</h1>
    <?php if ($canAddCustomer && $customersTableExists): ?>
        <button type="button" class="btn btn-primary" id="open-add-customer">Add Customer</button>
    <?php endif; ?>
    <p class="text-muted small">Create customers here. Bookings can be assigned to a customer when created or assigned in future.</p>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!$customersTableExists): ?>
<div class="alert alert-warning">Customers table does not exist. Run migration: <code>database/migrations/014_customers_table.sql</code></div>
<?php else: ?>
<?php if ($canAddCustomer): ?>
<?php
$addCustomerProLink = (defined('FRONTEND_URL') && FRONTEND_URL !== '') ? FRONTEND_URL . '/professional/register?ref=' . urlencode($staffReferralCode) : '';
$addCustomerBookLink = (defined('FRONTEND_URL') && FRONTEND_URL !== '') ? FRONTEND_URL . '/book-service?ref=' . urlencode($staffReferralCode) : '';
?>
<div class="modal" id="add-customer-modal" aria-hidden="true">
    <div class="modal-content">
        <h2>Add Customer</h2>
        <p class="text-muted small">Creates a customer record only. Bookings can be assigned to this customer later. Your referral is auto-filled when you add a customer.</p>
        <form method="post" class="form-grid">
            <input type="hidden" name="add_customer" value="1">
            <?php if ($staffReferralCode !== ''): ?>
            <input type="hidden" name="referral_code" value="<?= htmlspecialchars($staffReferralCode) ?>">
            <div class="form-group">
                <label>Professional referral link</label>
                <?php if ($addCustomerProLink !== ''): ?>
                <div class="flex gap-2" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="add-customer-pro-link" readonly value="<?= htmlspecialchars($addCustomerProLink) ?>" style="flex: 1; min-width: 0;" onclick="this.select();">
                    <button type="button" class="btn btn-secondary" id="copy-add-customer-pro-link">Copy</button>
                </div>
                <small class="text-muted">Share so others can register as professionals.</small>
                <?php else: ?>
                <p class="text-muted small">Set <code>FRONTEND_URL</code> in .env to show your referral link.</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Customer referral link</label>
                <?php if ($addCustomerBookLink !== ''): ?>
                <div class="flex gap-2" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="add-customer-referral-link" readonly value="<?= htmlspecialchars($addCustomerBookLink) ?>" style="flex: 1; min-width: 0;" onclick="this.select();">
                    <button type="button" class="btn btn-secondary" id="copy-add-customer-link">Copy</button>
                </div>
                <small class="text-muted">Share so customers can book a service with your referral.</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="cust_name">Name *</label>
                <input type="text" name="name" id="cust_name" required>
            </div>
            <div class="form-group">
                <label for="cust_phone">Phone *</label>
                <input type="text" name="phone" id="cust_phone" required>
            </div>
            <div class="form-group">
                <label for="cust_email">Email</label>
                <input type="email" name="email" id="cust_email">
            </div>
            <div class="form-group">
                <label for="cust_city">City</label>
                <input type="text" name="city" id="cust_city" placeholder="e.g. Mumbai">
            </div>
            <div class="form-group">
                <label for="cust_state">State</label>
                <input type="text" name="state" id="cust_state" placeholder="e.g. Maharashtra">
            </div>
            <div class="form-group">
                <label for="cust_pincode">Pincode</label>
                <input type="text" name="pincode" id="cust_pincode">
            </div>
            <div class="form-group">
                <label for="cust_language">Language</label>
                <input type="text" name="language" id="cust_language" placeholder="e.g. Hindi, English">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Customer</button>
                <button type="button" class="btn btn-secondary" id="close-add-customer">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var openBtn = document.getElementById('open-add-customer');
    var closeBtn = document.getElementById('close-add-customer');
    var modal = document.getElementById('add-customer-modal');
    if (!openBtn || !modal) return;
    function openModal() { modal.classList.add('open'); }
    function closeModal() { modal.classList.remove('open'); }
    openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    var copyBtn = document.getElementById('copy-add-customer-link');
    var linkInput = document.getElementById('add-customer-referral-link');
    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', function () {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(linkInput.value);
            } else {
                linkInput.select();
                document.execCommand('copy');
            }
            copyBtn.textContent = 'Copied!';
            setTimeout(function () { copyBtn.textContent = 'Copy'; }, 2000);
        });
    }
    var copyProBtn = document.getElementById('copy-add-customer-pro-link');
    var linkProInput = document.getElementById('add-customer-pro-link');
    if (copyProBtn && linkProInput) {
        copyProBtn.addEventListener('click', function () {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(linkProInput.value);
            } else {
                linkProInput.select();
                document.execCommand('copy');
            }
            copyProBtn.textContent = 'Copied!';
            setTimeout(function () { copyProBtn.textContent = 'Copy'; }, 2000);
        });
    }
});
</script>
<?php endif; ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="customers">
    <input type="text" name="search" placeholder="Search by name, email, phone, city, state, referral code…" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>

<?php
$rawCols = empty($customers) ? (count($customerCols) > 0 ? $customerCols : ['id','name','email','phone','city','state','pincode','language','referral_code','created_by','created_at']) : array_keys($customers[0]);
$displayCols = array_values(array_filter($rawCols, function ($k) { return $k !== '_booking_count'; }));
$userIdCols = ['created_by' => true];
?>
<div class="card overflow-x mt-2">
    <table class="table">
        <thead>
            <tr>
                <?php foreach ($displayCols as $col): ?>
                <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $col))) ?></th>
                <?php endforeach; ?>
                <?php if ($bookingsHasCustomerId): ?><th>Bookings</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <?php foreach ($displayCols as $col): ?>
                <td>
                    <?php
                    if (isset($userIdCols[$col]) && isset($c[$col]) && $c[$col] !== '' && $c[$col] !== null) {
                        echo htmlspecialchars($userNames[(int)$c[$col]] ?? (int)$c[$col]);
                    } elseif ($col === 'id') {
                        echo '<code>' . htmlspecialchars($c[$col] ?? '') . '</code>';
                    } elseif (in_array($col, ['created_at'], true) && !empty($c[$col])) {
                        echo htmlspecialchars($c[$col]);
                    } else {
                        echo htmlspecialchars($c[$col] ?? '-');
                    }
                    ?>
                </td>
                <?php endforeach; ?>
                <?php if ($bookingsHasCustomerId): ?><td><?= (int)($c['_booking_count'] ?? 0) ?></td><?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?>
            <tr><td colspan="<?= count($displayCols) + ($bookingsHasCustomerId ? 1 : 0) ?>" class="text-muted">No customers found.</td></tr>
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
