<?php

requireRole('super_admin', 'admin', 'staff');
$pdo = getDb();

$message = '';
$error = '';
$hasStatusColumn = false;
$professionals = [];
$tableExists = false;

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'professionals'");
    $tableExists = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

if ($tableExists) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM professionals LIKE 'status'");
        $hasStatusColumn = $stmt && $stmt->rowCount() > 0;
        $stmt = $pdo->query('SELECT * FROM professionals ORDER BY 1 DESC LIMIT 500');
        $professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $professionals = [];
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
                if ($email === '') {
                    $error = 'Professional has no email; cannot create user.';
                } else {
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
if ($search !== '' && $tableExists && !empty($professionals)) {
    $cols = array_keys($professionals[0]);
    $orderCol = in_array('created_at', $cols) ? 'created_at' : $cols[0];
    $stmt = $pdo->prepare("SELECT * FROM professionals WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ? OR professionalId LIKE ? ORDER BY $orderCol DESC LIMIT 500");
    $q = "%$search%";
    $stmt->execute([$q, $q, $q, $q, $q]);
    $professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$displayCols = empty($professionals) ? ['professionalId','name','phone','email','city','state','pincode','language','services','status'] : array_keys($professionals[0]);
$showStatus = $hasStatusColumn && in_array('status', $displayCols, true);
$showActions = $hasStatusColumn && canApproveRejectProfessional($user['role']);
?>
<div class="page-header">
    <h1>Professionals</h1>
</div>
<?php if (!$tableExists): ?>
    <div class="alert alert-warning">Professionals table does not exist. Create it from your Next.js app or run the migrations.</div>
<?php elseif (!$hasStatusColumn): ?>
    <div class="alert alert-warning">Run migration to enable approve/reject: <code>database/migrations/001_professionals_status_user_id.sql</code></div>
<?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="professionals">
    <input type="text" name="search" placeholder="Search by name, email, phone, city, ID" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>
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
</div>
