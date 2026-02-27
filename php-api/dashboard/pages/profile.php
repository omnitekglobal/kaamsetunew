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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$name) {
            $error = 'Name is required.';
        } else {
            $sql = 'UPDATE users SET name = ?, phone = ?';
            $params = [$name, $phone ?: null];
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
                $services = trim($_POST['pro_services'] ?? '');

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

$sql = 'SELECT id, name, email, phone, role, created_at'
    . ($hasUserReferralCode ? ', referral_code' : '')
    . ' FROM users WHERE id = ?';
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
<div class="card mt-2" style="max-width: 480px;">
    <h2 class="h5 mb-2">Refer New Professionals</h2>
    <p class="text-muted small mb-2">
        Share this referral code with professionals you invite. When they sign up using this code,
        they will be linked to your account.
    </p>
    <div class="form-group">
        <label>Your referral code</label>
        <input type="text" readonly value="<?= htmlspecialchars($referralCode) ?>" onclick="this.select();" />
        <small class="text-muted">Tap to select and copy.</small>
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
            <label>Services (comma separated) *</label>
            <input type="text" name="pro_services" placeholder="e.g. Plumbing, Electrical" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Professional</button>
        </div>
    </form>
</div>
<?php endif; ?>
