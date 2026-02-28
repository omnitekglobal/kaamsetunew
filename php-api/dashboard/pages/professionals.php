<?php

requireRole('super_admin', 'team_leader', 'staff');
$pdo = getDb();

$message = '';
$error = '';
$hasStatusColumn = false;
$hasReferralCodeColumn = false;
$hasReferredByUserIdColumn = false;
$staffReferralCode = '';
$professionals = [];
$tableExists = false;

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'professionals'");
    $tableExists = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

$professionalsPerPage = 20;
$professionalsPage = max(1, (int) ($_GET['p'] ?? 1));
$totalProfessionals = 0;

$servicesList = [];
try {
    if ($pdo->query("SHOW TABLES LIKE 'services'")->rowCount() > 0) {
        $orderCol = 'name';
        if ($pdo->query("SHOW COLUMNS FROM services LIKE 'sort_order'")->rowCount() > 0) $orderCol = 'sort_order';
        $stmt = $pdo->query("SHOW COLUMNS FROM services LIKE 'is_active'");
        $whereActive = $stmt && $stmt->rowCount() > 0 ? ' WHERE is_active = 1' : '';
        $stmt = $pdo->query("SELECT id, name FROM services" . $whereActive . " ORDER BY " . $orderCol . ", name");
        $servicesList = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
} catch (Throwable $e) {}

if ($tableExists) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'status'");
        $hasStatusColumn = $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {}
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referral_code'");
        $hasReferralCodeColumn = $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {}
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referred_by_user_id'");
        $hasReferredByUserIdColumn = $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {}
    // When staff adds professional, pre-fill their referral code in the form
    if (isset($user) && ($user['role'] ?? '') === 'staff') {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
        if ($stmt && $stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('SELECT referral_code FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $staffReferralCode = trim($row['referral_code'] ?? '');
        }
    }
}

// Create professional directly from dashboard — only staff can add.
if ($tableExists && ($user['role'] ?? '') === 'staff' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_professional'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $language = trim($_POST['language'] ?? '');
    $servicesRaw = $_POST['services'] ?? '';
    $services = is_array($servicesRaw) ? implode(', ', array_map('trim', $servicesRaw)) : trim($servicesRaw);

    if ($name === '' || $phone === '' || $city === '' || $state === '' || $pincode === '' || $language === '' || $services === '') {
        $error = 'All fields except email are required.';
    } else {
        $professionalId = 'PR' . time();

        // Base columns expected on professionals table created by the app.
        $columns = ['professionalId', 'name', 'phone', 'email', 'city', 'state', 'pincode', 'language', 'services'];
        $values = [$professionalId, $name, $phone, $email !== '' ? $email : null, $city, $state, $pincode, $language, $services];

        // New records created from dashboard start as pending when status column exists.
        if ($hasStatusColumn) {
            $columns[] = 'status';
            $values[] = 'pending';
        }

        // Referral tracking: staff's code is sent hidden; resolve to referred_by_user_id.
        $referredByUserId = null;
        $formReferralCode = trim($_POST['referral_code'] ?? '');
        if ($hasReferredByUserIdColumn && $formReferralCode !== '') {
            // Resolve referral code: staff (users.referral_code) or professional (professionals.referral_code -> user_id)
            $stmt = $pdo->prepare('SELECT id FROM users WHERE referral_code = ?');
            $stmt->execute([$formReferralCode]);
            $refUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($refUser) {
                $referredByUserId = (int) $refUser['id'];
            } else {
                $stmt = $pdo->prepare('SELECT user_id FROM professionals WHERE referral_code = ? AND user_id IS NOT NULL');
                $stmt->execute([$formReferralCode]);
                $proRef = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($proRef && !empty($proRef['user_id'])) {
                    $referredByUserId = (int) $proRef['user_id'];
                } else {
                    $error = 'Invalid referral code.';
                }
            }
        }
        if ($error === '' && $hasReferredByUserIdColumn && $referredByUserId === null && isset($user) && ($user['role'] ?? '') === 'staff') {
            // No code in form but staff: still attribute to this staff
            $referredByUserId = (int) ($user['id'] ?? 0) ?: null;
        }
        if ($hasReferralCodeColumn) {
            $columns[] = 'referral_code';
            $values[] = null; // New professional gets own code on approval
        }
        if ($hasReferredByUserIdColumn) {
            $columns[] = 'referred_by_user_id';
            $values[] = $referredByUserId;
        }

        if ($error === '') {
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $sql = 'INSERT INTO professionals (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                $message = 'Professional added successfully.';
                header('Location: ' . DASHBOARD_BASE . '/index.php?page=professionals&msg=' . urlencode($message));
                exit;
            } catch (Throwable $e) {
                $error = 'Failed to add professional: ' . $e->getMessage();
            }
        }
    }
}

// Approve: create user with default password, link professional, set status approved
$approveId = trim($_GET['approve'] ?? '');
if ($approveId !== '' && canApproveRejectProfessional($user['role'])) {
    if (!$hasStatusColumn) {
        $error = 'Run migration database/migrations/001_professionals_status_user_id.sql to enable approve.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM professionals WHERE professionalId = ?');
        $stmt->execute([$approveId]);
        $pro = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pro) {
            $status = $pro['status'] ?? 'pending';
            if ($status === 'pending') {
                $email = trim($pro['email'] ?? '');
                $name = trim($pro['name'] ?? '');
                $phone = trim($pro['phone'] ?? '');
                $defaultPassword = $_ENV['DEFAULT_PROFESSIONAL_PASSWORD'] ?? 'Welcome@123';

                // We primarily use mobile number for login. If email is missing but phone exists,
                // generate a synthetic email so we can satisfy DB constraints and keep login by phone.
                if ($email === '') {
                    $normalizedPhone = preg_replace('/\D/', '', $phone);
                    if ($normalizedPhone === '') {
                        $error = 'Professional has no phone number; cannot create user.';
                    } else {
                        $email = 'pro_' . $normalizedPhone . '@auto.kaamsetu';
                    }
                }

                if ($error === '') {
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                    $stmt->execute([$email]);
                    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existingUser) {
                        $userId = (int) $existingUser['id'];
                    } else {
                        $pdo->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)')
                            ->execute([$name, $email, password_hash($defaultPassword, PASSWORD_DEFAULT), $phone ?: null, 'professional']);
                        $userId = (int) $pdo->lastInsertId();
                    }

                    if ($hasStatusColumn) {
                        $stmt = $pdo->prepare('UPDATE professionals SET status = ?, user_id = ? WHERE professionalId = ?');
                        $stmt->execute(['approved', $userId, $approveId]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE professionals SET user_id = ? WHERE professionalId = ?');
                        $stmt->execute([$userId, $approveId]);
                    }

                    // Assign a unique referral code to this professional (once), if the column exists.
                    if ($hasReferralCodeColumn && empty($pro['referral_code'] ?? null)) {
                        try {
                            $proReferralCode = 'PRO' . strtoupper(bin2hex(random_bytes(3)));
                        } catch (Throwable $e) {
                            $proReferralCode = 'PRO' . strtoupper(substr(md5(uniqid((string) $approveId, true)), 0, 6));
                        }
                        $stmt = $pdo->prepare('UPDATE professionals SET referral_code = ? WHERE professionalId = ? AND (referral_code IS NULL OR referral_code = \'\')');
                        $stmt->execute([$proReferralCode, $approveId]);
                    }

                    $message = 'Professional approved. User account created with default password.';
                }
            }
        }
        if ($message) {
            header('Location: ' . DASHBOARD_BASE . '/index.php?page=professionals&msg=' . urlencode($message));
            exit;
        }
    }
}

// Reject
$rejectId = trim($_GET['reject'] ?? '');
if ($rejectId !== '' && canApproveRejectProfessional($user['role']) && $hasStatusColumn) {
    $pdo->prepare('UPDATE professionals SET status = ? WHERE professionalId = ?')->execute(['rejected', $rejectId]);
    header('Location: ' . DASHBOARD_BASE . '/index.php?page=professionals&msg=' . urlencode('Professional rejected.'));
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

$search = trim($_GET['search'] ?? '');
if ($tableExists) {
    try {
        $orderCol = 'professionalId';
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'created_at'");
        if ($stmt && $stmt->rowCount() > 0) $orderCol = 'created_at';
        $where = '1=1';
        $params = [];

        // Staff should see only professionals they brought in.
        if (($user['role'] ?? '') === 'staff' && $hasReferredByUserIdColumn) {
            $where = 'referred_by_user_id = ?';
            $params[] = (int) $user['id'];
        }

        if ($search !== '') {
            $cols = $pdo->query("SHOW COLUMNS FROM professionals")->fetchAll(PDO::FETCH_ASSOC);
            $searchableTypes = ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'enum'];
            $searchCols = [];
            foreach ($cols as $col) {
                $type = strtolower($col['Type'] ?? '');
                $isSearchable = false;
                foreach ($searchableTypes as $t) {
                    if (strpos($type, $t) === 0) {
                        $isSearchable = true;
                        break;
                    }
                }
                if ($isSearchable) {
                    $searchCols[] = '`' . str_replace('`', '``', $col['Field']) . '` LIKE ?';
                }
            }
            if (!empty($searchCols)) {
                $where = '(' . implode(' OR ', $searchCols) . ')';
                $params = array_fill(0, count($searchCols), "%$search%");
            }
        }
        $countSql = "SELECT COUNT(*) FROM professionals WHERE $where";
        $stmt = $params ? $pdo->prepare($countSql) : $pdo->query($countSql);
        $stmt->execute($params);
        $totalProfessionals = (int) $stmt->fetchColumn();
        $offset = ($professionalsPage - 1) * $professionalsPerPage;
        $listSql = "SELECT * FROM professionals WHERE $where ORDER BY $orderCol DESC LIMIT $professionalsPerPage OFFSET $offset";
        $stmt = $params ? $pdo->prepare($listSql) : $pdo->query($listSql);
        $stmt->execute($params);
        $professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $professionals = [];
    }
}

$displayCols = empty($professionals)
    ? ['professionalId','name','phone','email','city','state','pincode','language','services','status','referral_code','referred_by_user_id']
    : array_keys($professionals[0]);
$showStatus = $hasStatusColumn && in_array('status', $displayCols, true);
$showActions = $hasStatusColumn && canApproveRejectProfessional($user['role']);
?>
<div class="page-header">
    <h1>Professionals</h1>
    <?php if ($tableExists && ($user['role'] ?? '') === 'staff'): ?>
        <button type="button" class="btn btn-primary" id="open-add-professional">Add Professional</button>
    <?php endif; ?>
</div>
<?php if (!$tableExists): ?>
    <div class="alert alert-warning">Professionals table does not exist. Create it from your Next.js app or run the migrations.</div>
<?php elseif (!$hasStatusColumn): ?>
    <div class="alert alert-warning">Run migration to enable approve/reject: <code>database/migrations/001_professionals_status_user_id.sql</code></div>
<?php endif; ?>
<?php if ($tableExists && !$hasReferralCodeColumn): ?>
    <div class="alert alert-warning">To enable referral tracking for staff-created professionals, run migration: <code>database/migrations/005_professionals_referral_code.sql</code></div>
<?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="professionals">
    <input type="text" name="search" placeholder="Search any column (name, phone, email, city, state, pincode, language, services, status, ID…)" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>
<?php if ($tableExists && ($user['role'] ?? '') === 'staff'): ?>
<?php
$professionalReferralLink = (defined('FRONTEND_URL') && FRONTEND_URL !== '') ? FRONTEND_URL . '/professional/register?ref=' . urlencode($staffReferralCode) : '';
?>
<div class="modal" id="add-professional-modal" aria-hidden="true">
    <div class="modal-content">
        <h2>Add Professional</h2>
        <p class="text-muted small">Your referral is auto-filled when you add a professional (hidden). Share your professional link so others can register with your referral.</p>
        <form method="post" class="form-grid">
            <input type="hidden" name="create_professional" value="1">
            <?php if ($staffReferralCode !== ''): ?>
            <input type="hidden" name="referral_code" value="<?= htmlspecialchars($staffReferralCode) ?>">
            <div class="form-group">
                <label>Professional referral link</label>
                <?php if ($professionalReferralLink !== ''): ?>
                <div class="flex gap-2" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="add-pro-referral-link" readonly value="<?= htmlspecialchars($professionalReferralLink) ?>" style="flex: 1; min-width: 0;" onclick="this.select();">
                    <button type="button" class="btn btn-secondary" id="copy-add-pro-link">Copy</button>
                </div>
                <small class="text-muted">Share so others can register as professionals.</small>
                <?php else: ?>
                <p class="text-muted small">Set <code>FRONTEND_URL</code> in .env to show your referral link.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone *</label>
                <input type="text" name="phone" id="phone" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email">
            </div>
            <div class="form-group">
                <label for="city">City *</label>
                <input type="text" name="city" id="city" required>
            </div>
            <div class="form-group">
                <label for="state">State *</label>
                <input type="text" name="state" id="state" required>
            </div>
            <div class="form-group">
                <label for="pincode">Pincode *</label>
                <input type="text" name="pincode" id="pincode" required>
            </div>
            <div class="form-group">
                <label for="language">Language *</label>
                <input type="text" name="language" id="language" required>
            </div>
            <div class="form-group">
                <label for="services">Services *</label>
                <?php if (!empty($servicesList)): ?>
                <select name="services[]" id="services" class="js-services-multi" multiple required data-placeholder="Select services…">
                    <?php foreach ($servicesList as $svc): ?>
                    <option value="<?= htmlspecialchars($svc['name']) ?>"><?= htmlspecialchars($svc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Selected services appear as tags; click × to remove.</small>
                <?php else: ?>
                <input type="text" name="services" id="services" placeholder="e.g. Plumbing, Electrical" required>
                <small class="text-muted">Add services in Categories &amp; Services to use multi-select here.</small>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Professional</button>
                <button type="button" class="btn btn-secondary" id="close-add-professional">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var openBtn = document.getElementById('open-add-professional');
    var closeBtn = document.getElementById('close-add-professional');
    var modal = document.getElementById('add-professional-modal');

    if (!openBtn || !modal) return;

    function openModal() {
        modal.classList.add('open');
    }
    function closeModal() {
        modal.classList.remove('open');
    }

    openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    var copyProBtn = document.getElementById('copy-add-pro-link');
    var linkProInput = document.getElementById('add-pro-referral-link');
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
<?php
$paginationQueryParams = ['page' => 'professionals'];
if ($search !== '') $paginationQueryParams['search'] = $search;
?>
<div class="card overflow-x">
    <table class="table">
        <thead>
            <tr>
                <?php foreach ($displayCols as $c): ?>
                    <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $c))) ?></th>
                <?php endforeach; ?>
                <?php if ($showActions): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($professionals as $p): ?>
                <tr>
                    <?php foreach ($displayCols as $c): ?>
                        <td>
                            <?php if ($c === 'professionalId'): ?>
                                <code><?= htmlspecialchars($p[$c] ?? '') ?></code>
                            <?php elseif ($c === 'status' && $showStatus): ?>
                                <?php
                                $st = $p['status'] ?? 'pending';
                                $class = $st === 'approved' ? 'badge-success' : ($st === 'rejected' ? 'badge-danger' : 'badge-warning');
                                ?>
                                <span class="badge <?= $class ?>"><?= htmlspecialchars(ucfirst($st)) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($p[$c] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <?php if ($showActions): ?>
                        <td>
                            <?php $st = $p['status'] ?? 'pending'; ?>
                            <?php if ($st === 'pending'): ?>
                                <a href="?page=professionals&approve=<?= urlencode($p['professionalId'] ?? '') ?>" class="btn btn-sm btn-primary" onclick="return confirm('Approve this professional and create user account with default password?')">Approve</a>
                                <a href="?page=professionals&reject=<?= urlencode($p['professionalId'] ?? '') ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">Reject</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($professionals)): ?>
        <p class="p-3 text-muted">No professionals found.</p>
    <?php endif; ?>
    <?php
    $paginationTotal = $totalProfessionals;
    $paginationPage = $professionalsPage;
    $paginationPerPage = $professionalsPerPage;
    require __DIR__ . '/../includes/pagination.php';
    ?>
</div>
