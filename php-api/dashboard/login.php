<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    header('Location: ' . DASHBOARD_BASE . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $error = 'Invalid email or password.';
        } else {
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();
            if (!password_verify($password, $hash)) {
                $error = 'Invalid email or password.';
            } elseif (!$user['is_active']) {
                $error = 'Account is deactivated.';
            } else {
                $_SESSION['dashboard_user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'is_active' => (bool) $user['is_active'],
                ];
                header('Location: ' . DASHBOARD_BASE . '/index.php');
                exit;
            }
        }
    }
}

$inactive = isset($_GET['inactive']);
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - KaamSetu Dashboard</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(DASHBOARD_BASE) ?>/assets/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1>KaamSetu Dashboard</h1>
        <p class="login-subtitle">Sign in to manage your account</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($inactive): ?>
            <div class="alert alert-error">Your account has been deactivated.</div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
    </div>
</body>
</html>
