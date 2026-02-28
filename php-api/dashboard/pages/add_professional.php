<?php

requireRole('staff');
$pdo = getDb();

$error = '';
$message = '';
$tableExists = false;
$hasStatusColumn = false;
$hasReferralCodeColumn = false;
$hasReferredByUserIdColumn = false;
$staffReferralCode = '';
$servicesList = [];

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'professionals'");
    $tableExists = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

if ($tableExists) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'status'");
        $hasStatusColumn = $stmt && $stmt->rowCount() > 0;
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referral_code'");
        $hasReferralCodeColumn = $stmt && $stmt->rowCount() > 0;
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'referred_by_user_id'");
        $hasReferredByUserIdColumn = $stmt && $stmt->rowCount() > 0;
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
        if ($stmt && $stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('SELECT referral_code FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $staffReferralCode = trim($row['referral_code'] ?? '');
        }
    } catch (Throwable $e) {}
}

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

// Create professional — POST from this page
if ($tableExists && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_professional'])) {
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
        $columns = ['professionalId', 'name', 'phone', 'email', 'city', 'state', 'pincode', 'language', 'services'];
        $values = [$professionalId, $name, $phone, $email !== '' ? $email : null, $city, $state, $pincode, $language, $services];
        if ($hasStatusColumn) {
            $columns[] = 'status';
            $values[] = 'pending';
        }
        $referredByUserId = null;
        $formReferralCode = trim($_POST['referral_code'] ?? '');
        if ($hasReferredByUserIdColumn && $formReferralCode !== '') {
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
        if ($error === '' && $hasReferredByUserIdColumn && $referredByUserId === null) {
            $referredByUserId = (int) ($user['id'] ?? 0) ?: null;
        }
        if ($hasReferralCodeColumn) {
            $columns[] = 'referral_code';
            $values[] = null;
        }
        if ($hasReferredByUserIdColumn) {
            $columns[] = 'referred_by_user_id';
            $values[] = $referredByUserId;
        }
        if ($error === '') {
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $sql = 'INSERT INTO professionals (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
            try {
                $pdo->prepare($sql)->execute($values);
                header('Location: ' . DASHBOARD_BASE . '/index.php?page=professionals&msg=' . urlencode('Professional added successfully.'));
                exit;
            } catch (Throwable $e) {
                $error = 'Failed to add professional: ' . $e->getMessage();
            }
        }
    }
}

$professionalReferralLink = (defined('FRONTEND_URL') && FRONTEND_URL !== '') ? FRONTEND_URL . '/professional/register?ref=' . urlencode($staffReferralCode) : '';
?>
<div class="add-professional-page add-professional-form-cols">
    <p class="mb-2"><a href="<?= DASHBOARD_BASE ?>/index.php?page=professionals" class="btn btn-secondary">&rarr; <?= htmlspecialchars('Back to Professionals') ?></a></p>

    <?php if ($staffReferralCode !== ''): ?>
    <div class="form-group mb-3">
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

    <h1 class="page-header" style="margin-bottom: 1rem;">Add Professional</h1>
    <p class="text-muted small mb-4">Your referral is auto-filled when you add a professional. Fill the form and select services.</p>

    <?php if (!$tableExists): ?>
    <div class="alert alert-warning">Professionals table does not exist. Run migrations first.</div>
    <?php else: ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="create_professional" value="1">
        <?php if ($staffReferralCode !== ''): ?>
        <input type="hidden" name="referral_code" value="<?= htmlspecialchars($staffReferralCode) ?>">
        <?php endif; ?>
        <div class="form-grid-inner">
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
        <div class="form-group form-group-full">
            <label>Services *</label>
            <?php if (!empty($servicesList)): ?>
            <div class="services-checkbox-list" style="border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 0.75rem; max-height: 220px; overflow-y: auto;">
                <?php foreach ($servicesList as $svc): ?>
                <label class="checkbox-row" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="services[]" value="<?= htmlspecialchars($svc['name']) ?>">
                    <span><?= htmlspecialchars($svc['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <small class="text-muted">Select one or more services.</small>
            <?php else: ?>
            <input type="text" name="services" placeholder="e.g. Plumbing, Electrical" required>
            <small class="text-muted">Add services in Categories &amp; Services to use checkboxes here.</small>
            <?php endif; ?>
        </div>
        <div class="form-actions form-group-full">
            <button type="submit" class="btn btn-primary">Save Professional</button>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=professionals" class="btn btn-secondary">Cancel</a>
        </div>
        </div>
    </form>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var copyBtn = document.getElementById('copy-add-pro-link');
    var linkInput = document.getElementById('add-pro-referral-link');
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
    var form = document.querySelector('.add-professional-page form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var checkboxes = form.querySelectorAll('input[name="services[]"]:checked');
            if (checkboxes.length === 0 && form.querySelector('input[name="services[]"]')) {
                e.preventDefault();
                alert('Please select at least one service.');
                return false;
            }
        });
    }
});
</script>
