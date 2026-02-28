<?php

// End user: edit own profile only
$pdo = getDb();
$message = '';
$error = '';

// Optional referral code support for current user (staff/professional)
$hasUserReferralCode = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
    $hasUserReferralCode = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

// Services list for multi-select in Add Professional form
$profileServicesList = [];
try {
    if ($pdo->query("SHOW TABLES LIKE 'services'")->rowCount() > 0) {
        $orderCol = 'name';
        if ($pdo->query("SHOW COLUMNS FROM services LIKE 'sort_order'")->rowCount() > 0) $orderCol = 'sort_order';
        $stmt = $pdo->query("SHOW COLUMNS FROM services LIKE 'is_active'");
        $whereActive = $stmt && $stmt->rowCount() > 0 ? ' WHERE is_active = 1' : '';
        $stmt = $pdo->query("SELECT id, name FROM services" . $whereActive . " ORDER BY " . $orderCol . ", name");
        $profileServicesList = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $language = trim($_POST['language'] ?? '');
        $village = trim($_POST['village'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '');
        $aadhaar = trim($_POST['aadhaar_no'] ?? '');
        if (!$name) {
            $error = 'Name is required.';
        } else {
            $sql = 'UPDATE users SET name = ?, phone = ?';
            $params = [$name, $phone ?: null];
            if ($hasUserReferralCode) {
                // referral_code is immutable here
            }
            try {
                $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'language'");
                $hasLangCol = $colsStmt && $colsStmt->rowCount() > 0;
            } catch (Throwable $e) { $hasLangCol = false; }
            try {
                $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'village'");
                $hasVillageCol = $colsStmt && $colsStmt->rowCount() > 0;
            } catch (Throwable $e) { $hasVillageCol = false; }
            try {
                $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'state'");
                $hasStateCol = $colsStmt && $colsStmt->rowCount() > 0;
            } catch (Throwable $e) { $hasStateCol = false; }
            try {
                $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'landmark'");
                $hasLandmarkCol = $colsStmt && $colsStmt->rowCount() > 0;
            } catch (Throwable $e) { $hasLandmarkCol = false; }
            try {
                $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'aadhaar_no'");
                $hasAadhaarCol = $colsStmt && $colsStmt->rowCount() > 0;
            } catch (Throwable $e) { $hasAadhaarCol = false; }

            if ($hasLangCol) {
                $sql .= ', language = ?';
                $params[] = $language !== '' ? $language : null;
            }
            if ($hasVillageCol) {
                $sql .= ', village = ?';
                $params[] = $village !== '' ? $village : null;
            }
            if ($hasStateCol) {
                $sql .= ', state = ?';
                $params[] = $state !== '' ? $state : null;
            }
            if ($hasLandmarkCol) {
                $sql .= ', landmark = ?';
                $params[] = $landmark !== '' ? $landmark : null;
            }
            if ($hasAadhaarCol) {
                $sql .= ', aadhaar_no = ?';
                $params[] = $aadhaar !== '' ? $aadhaar : null;
            }
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $error = 'Password must be at least 8 characters.';
                } else {
                    $sql .= ', password = ?';
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
            }
            if (!$error) {
                $params[] = $user['id'];
                $pdo->prepare($sql . ' WHERE id = ?')->execute($params);
                $_SESSION['dashboard_user']['name'] = $name;
                $message = 'Profile updated.';
            }
        }
    } elseif ($action === 'create_referred_professional') {
        // Allow staff and professionals to create a new professional linked to their account.
        if (!in_array($user['role'] ?? '', ['staff', 'professional'], true)) {
            $error = 'You are not allowed to refer professionals.';
        } else {
            try {
                $hasProfessionalsTable = $pdo->query("SHOW TABLES LIKE 'professionals'")->rowCount() > 0;
            } catch (Throwable $e) {
                $hasProfessionalsTable = false;
            }

            if (!$hasProfessionalsTable) {
                $error = 'Professionals table does not exist.';
            } else {
                $name = trim($_POST['pro_name'] ?? '');
                $phone = trim($_POST['pro_phone'] ?? '');
                $email = trim($_POST['pro_email'] ?? '');
                $city = trim($_POST['pro_city'] ?? '');
                $state = trim($_POST['pro_state'] ?? '');
                $pincode = trim($_POST['pro_pincode'] ?? '');
                $language = trim($_POST['pro_language'] ?? '');
                $servicesRaw = $_POST['pro_services'] ?? '';
                $services = is_array($servicesRaw) ? implode(', ', array_map('trim', $servicesRaw)) : trim($servicesRaw);

                if (!$name || !$phone || !$city || !$state || !$pincode || !$language || $services === '') {
                    $error = 'All fields except email are required for new professional.';
                } else {
                    $professionalId = 'PR' . time();

                    // Detect optional columns on professionals table.
                    try {
                        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'status'");
                        $hasStatus = $stmt && $stmt->rowCount() > 0;
                    } catch (Throwable $e) {
                        $hasStatus = false;
                    }
                    try {
                        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referred_by_user_id'");
                        $hasReferredByUserId = $stmt && $stmt->rowCount() > 0;
                    } catch (Throwable $e) {
                        $hasReferredByUserId = false;
                    }
                    try {
                        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referral_code'");
                        $hasProReferralCode = $stmt && $stmt->rowCount() > 0;
                    } catch (Throwable $e) {
                        $hasProReferralCode = false;
                    }

                    $columns = ['professionalId', 'name', 'phone', 'email', 'city', 'state', 'pincode', 'language', 'services'];
                    $values = [$professionalId, $name, $phone, $email !== '' ? $email : null, $city, $state, $pincode, $language, $services];

                    if ($hasStatus) {
                        $columns[] = 'status';
                        $values[] = 'pending';
                    }
                    if ($hasReferredByUserId) {
                        $columns[] = 'referred_by_user_id';
                        $values[] = (int) $user['id'];
                    }
                    if ($hasProReferralCode) {
                        $columns[] = 'referral_code';
                        $values[] = null; // will be assigned on approval
                    }

                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    $sql = 'INSERT INTO professionals (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
                    try {
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($values);
                        $message = 'Professional referral submitted successfully.';
                    } catch (Throwable $e) {
                        $error = 'Failed to create professional: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Build SELECT dynamically so it works even if extra columns are not yet migrated.
$hasLangCol = false;
$hasVillageCol = false;
$hasStateCol = false;
$hasLandmarkCol = false;
$hasAadhaarCol = false;
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'language'");
    $hasLangCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'village'");
    $hasVillageCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'state'");
    $hasStateCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'landmark'");
    $hasLandmarkCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'aadhaar_no'");
    $hasAadhaarCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}

$extraCols = [];
if ($hasUserReferralCode) $extraCols[] = 'referral_code';
if ($hasLangCol) $extraCols[] = 'language';
if ($hasVillageCol) $extraCols[] = 'village';
if ($hasStateCol) $extraCols[] = 'state';
if ($hasLandmarkCol) $extraCols[] = 'landmark';
if ($hasAadhaarCol) $extraCols[] = 'aadhaar_no';

$sql = 'SELECT id, name, email, phone, role, created_at';
if (!empty($extraCols)) {
    $sql .= ', ' . implode(', ', $extraCols);
}
$sql .= ' FROM users WHERE id = ?';

$stmt = $pdo->prepare($sql);
$stmt->execute([$user['id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

$professionalRecord = null;
$canReferProfessionals = false;
$referralCode = $hasUserReferralCode ? ($me['referral_code'] ?? null) : null;
$totalReferredProfessionals = null;

if ($pdo->query("SHOW TABLES LIKE 'professionals'")->rowCount() > 0) {
    if (($me['role'] ?? '') === 'professional') {
        $stmt = $pdo->prepare('SELECT professionalId, name, phone, email, city, state, pincode, language, services, status, referral_code FROM professionals WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $professionalRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($professionalRecord && !empty($professionalRecord['referral_code'])) {
            $referralCode = $professionalRecord['referral_code'];
        }
    }

    // Both staff and professionals can refer new professionals when we can track referrals.
    if (in_array($me['role'] ?? '', ['staff', 'professional'], true)) {
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referred_by_user_id'");
            if ($colStmt && $colStmt->rowCount() > 0) {
                $canReferProfessionals = true;
                if ($hasUserReferralCode || ($professionalRecord && !empty($professionalRecord['referral_code']))) {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM professionals WHERE referred_by_user_id = ?');
                    $stmt->execute([$user['id']]);
                    $totalReferredProfessionals = (int) $stmt->fetchColumn();
                }
            }
        } catch (Throwable $e) {}
    }
}
?>
<div class="page-header">
    <h1>My Profile</h1>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width: 480px;">
    <form method="post">
        <input type="hidden" name="_action" value="update_profile">
        <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($me['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" disabled>
            <small class="text-muted">Email cannot be changed here.</small>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($me['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Language</label>
            <input type="text" name="language" value="<?= htmlspecialchars($me['language'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Village</label>
            <input type="text" name="village" value="<?= htmlspecialchars($me['village'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>State</label>
            <input type="text" name="state" value="<?= htmlspecialchars($me['state'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Landmark</label>
            <input type="text" name="landmark" value="<?= htmlspecialchars($me['landmark'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Aadhaar No.</label>
            <input type="text" name="aadhaar_no" value="<?= htmlspecialchars($me['aadhaar_no'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>New password (leave blank to keep current)</label>
            <input type="password" name="password" minlength="8">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </form>
</div>
<?php if ($professionalRecord): ?>
<div class="card mt-2" style="max-width: 480px;">
    <h2 class="h5 mb-2">Service provider details</h2>
    <p class="text-muted small mb-2">Read-only; managed from Professionals when you were approved.</p>
    <dl class="profile-dl">
        <dt>Services</dt>
        <dd><?= htmlspecialchars($professionalRecord['services'] ?? '-') ?></dd>
        <dt>City</dt>
        <dd><?= htmlspecialchars($professionalRecord['city'] ?? '-') ?></dd>
        <dt>State</dt>
        <dd><?= htmlspecialchars($professionalRecord['state'] ?? '-') ?></dd>
        <dt>Pincode</dt>
        <dd><?= htmlspecialchars($professionalRecord['pincode'] ?? '-') ?></dd>
        <dt>Language</dt>
        <dd><?= htmlspecialchars($professionalRecord['language'] ?? '-') ?></dd>
        <dt>Status</dt>
        <dd><?= htmlspecialchars($professionalRecord['status'] ?? '-') ?></dd>
    </dl>
</div>
<?php endif; ?>

<?php if ($canReferProfessionals && $referralCode): ?>
<?php
$referralLink = (FRONTEND_URL !== '' ? FRONTEND_URL : '') . '/professional/register?ref=' . urlencode($referralCode);
$customerRefLink = (FRONTEND_URL !== '' ? FRONTEND_URL : '') . '/book-service?ref=' . urlencode($referralCode);
?>
<div class="card mt-2" style="max-width: 480px;">
    <h2 class="h5 mb-2">Referral links</h2>
    <p class="text-muted small mb-2">
        Share your links. Professional link: they register with your code pre-filled. Customer link: they book a service and the booking is linked to your referral.
    </p>
    <div class="form-group">
        <label>Professional referral link</label>
        <?php if (FRONTEND_URL !== ''): ?>
        <div class="flex gap-2" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <input type="text" id="referral-link-input" readonly value="<?= htmlspecialchars($referralLink) ?>" style="flex: 1; min-width: 0;" onclick="this.select();" />
            <button type="button" class="btn btn-primary" id="copy-referral-link-btn">Copy</button>
        </div>
        <small class="text-muted">Share so others can register as professionals (code pre-filled).</small>
        <?php else: ?>
        <p class="text-muted small">Set <code>FRONTEND_URL</code> in your .env (e.g. <code>https://yourapp.com</code>) to show the referral link.</p>
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label>Customer referral link</label>
        <?php if (FRONTEND_URL !== ''): ?>
        <div class="flex gap-2" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <input type="text" id="customer-referral-link-input" readonly value="<?= htmlspecialchars($customerRefLink) ?>" style="flex: 1; min-width: 0;" onclick="this.select();" />
            <button type="button" class="btn btn-secondary" id="copy-customer-referral-link-btn">Copy</button>
        </div>
        <small class="text-muted">Share so customers can book a service; the booking will be linked to your referral.</small>
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label>Your referral code</label>
        <input type="text" readonly value="<?= htmlspecialchars($referralCode) ?>" onclick="this.select();" />
        <small class="text-muted">They can also enter this code manually on the register page.</small>
    </div>
    <?php if ($totalReferredProfessionals !== null): ?>
        <p class="text-muted small">Total professionals referred: <strong><?= (int) $totalReferredProfessionals ?></strong></p>
    <?php endif; ?>

    <hr>
    <h2 class="h5 mb-2">Add New Professional (via your referral)</h2>
    <p class="text-muted small mb-2">
        Use this form to add a new professional. They will be automatically linked to your account.
    </p>
    <form method="post">
        <input type="hidden" name="_action" value="create_referred_professional">
        <div class="form-group">
            <label>Name *</label>
            <input type="text" name="pro_name" required>
        </div>
        <div class="form-group">
            <label>Phone *</label>
            <input type="text" name="pro_phone" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="pro_email">
        </div>
        <div class="form-group">
            <label>City *</label>
            <input type="text" name="pro_city" required>
        </div>
        <div class="form-group">
            <label>State *</label>
            <input type="text" name="pro_state" required>
        </div>
        <div class="form-group">
            <label>Pincode *</label>
            <input type="text" name="pro_pincode" required>
        </div>
        <div class="form-group">
            <label>Language *</label>
            <input type="text" name="pro_language" required>
        </div>
        <div class="form-group">
            <label>Services *</label>
            <?php if (!empty($profileServicesList)): ?>
            <select name="pro_services[]" class="js-services-multi" multiple required data-placeholder="Select services…">
                <?php foreach ($profileServicesList as $svc): ?>
                <option value="<?= htmlspecialchars($svc['name']) ?>"><?= htmlspecialchars($svc['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Selected services appear as tags; click × to remove.</small>
            <?php else: ?>
            <input type="text" name="pro_services" placeholder="e.g. Plumbing, Electrical" required>
            <small class="text-muted">Add services in Categories &amp; Services to use multi-select here.</small>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Professional</button>
        </div>
    </form>
    <script>
    (function() {
        var btn = document.getElementById('copy-referral-link-btn');
        var input = document.getElementById('referral-link-input');
        if (btn && input) {
            btn.addEventListener('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value);
                } else {
                    input.select();
                    document.execCommand('copy');
                }
                btn.textContent = 'Copied!';
                setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
            });
        }
        var customerBtn = document.getElementById('copy-customer-referral-link-btn');
        var customerInput = document.getElementById('customer-referral-link-input');
        if (customerBtn && customerInput) {
            customerBtn.addEventListener('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(customerInput.value);
                } else {
                    customerInput.select();
                    document.execCommand('copy');
                }
                customerBtn.textContent = 'Copied!';
                setTimeout(function() { customerBtn.textContent = 'Copy'; }, 2000);
            });
        }
    })();
    </script>
</div>
<?php endif; ?>
