<?php

requireRole('super_admin', 'admin');
$pdo = getDb();

$message = '';
$error = '';

// Delete
if (canCreateDeleteUsers($user['role']) && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id !== $user['id']) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $message = 'User deleted.';
    } else {
        $error = 'You cannot delete your own account.';
    }
}

// Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'create';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'end_user';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $id = (int) ($_POST['id'] ?? 0);

    if (!in_array($role, DASHBOARD_ROLES, true)) $role = 'end_user';
    if (!$name || !$email) {
        $error = 'Name and email are required.';
    } elseif ($action === 'create' && strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        if ($action === 'update' && $id) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $error = 'User not found.';
            } else {
                $sql = 'UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ?';
                $params = [$name, $email, $phone ?: null, $role, $is_active];
                if ($password !== '') {
                    $sql .= ', password = ?';
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
                $params[] = $id;
                $pdo->prepare($sql . ' WHERE id = ?')->execute($params);
                $message = 'User updated.';
            }
        } elseif ($action === 'create' && canCreateDeleteUsers($user['role'])) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered.';
            } else {
                $pdo->prepare('INSERT INTO users (name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null, $role, 1]);
                $message = 'User created.';
            }
        }
    }
}

$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$sql = 'SELECT id, name, email, phone, role, is_active, created_at FROM users WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($roleFilter !== '') {
    $sql .= ' AND role = ?';
    $params[] = $roleFilter;
}
$sql .= ' ORDER BY id DESC';
$stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="page-header">
    <h1>Users</h1>
    <?php if (canCreateDeleteUsers($user['role'])): ?>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('userModal').classList.add('open')">Add User</button>
    <?php endif; ?>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="users">
    <input type="text" name="search" placeholder="Search name or email" value="<?= htmlspecialchars($search) ?>">
    <select name="role">
        <option value="">All roles</option>
        <?php foreach (DASHBOARD_ROLES as $r): ?>
            <option value="<?= htmlspecialchars($r) ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= htmlspecialchars(roleLabel($r)) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
</form>
<div class="card overflow-x">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created</th>
                <?php if (canCreateDeleteUsers($user['role'])): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <td><span class="badge"><?= htmlspecialchars(roleLabel($u['role'])) ?></span></td>
                    <td><?= $u['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                    <?php if (canCreateDeleteUsers($user['role'])): ?>
                        <td>
                            <a href="?page=users&edit=<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <?php if ((int) $u['id'] !== $user['id']): ?>
                                <a href="?page=users&delete=<?= (int) $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editUser = null;
if ($editId && canCreateDeleteUsers($user['role'])) {
    $stmt = $pdo->prepare('SELECT id, name, email, phone, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
$modalOpen = !empty($editUser);
?>
<div id="userModal" class="modal <?= $modalOpen ? 'open' : '' ?>">
    <div class="modal-content">
        <h2><?= $editUser ? 'Edit User' : 'Add User' ?></h2>
        <form method="post">
            <input type="hidden" name="_action" value="<?= $editUser ? 'update' : 'create' ?>">
            <?php if ($editUser): ?><input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editUser['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" <?= $editUser ? '' : '' ?>>
            </div>
            <div class="form-group">
                <label>Password <?= $editUser ? '(leave blank to keep)' : '*' ?></label>
                <input type="password" name="password" <?= $editUser ? '' : 'required' ?> minlength="8">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <?php foreach (DASHBOARD_ROLES as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= ($editUser['role'] ?? 'end_user') === $r ? 'selected' : '' ?>><?= htmlspecialchars(roleLabel($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($editUser): ?>
            <div class="form-group">
                <label><input type="checkbox" name="is_active" value="1" <?= ($editUser['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
            </div>
            <?php endif; ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editUser ? 'Update' : 'Create' ?></button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('userModal').classList.remove('open'); window.location.href='?page=users'">Cancel</button>
            </div>
        </form>
    </div>
</div>
