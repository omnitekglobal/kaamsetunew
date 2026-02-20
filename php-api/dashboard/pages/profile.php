<?php

// End user: edit own profile only
$pdo = getDb();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}

$stmt = $pdo->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="page-header">
    <h1>My Profile</h1>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width: 480px;">
    <form method="post">
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
