<?php

requireRole('super_admin', 'admin', 'staff');
$pdo = getDb();

$message = '';
$error = '';

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'create';
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $id = (int) ($_POST['id'] ?? 0);

    if (!$name) $error = 'Name is required.';
    elseif (!$category_id) $error = 'Category is required.';
    else {
        if ($slug === '') $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?: 'service';
        if ($action === 'update' && $id) {
            $stmt = $pdo->prepare('SELECT id FROM services WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) $error = 'Service not found.';
            else {
                $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? AND category_id = ? AND id != ?');
                $stmt->execute([$slug, $category_id, $id]);
                if ($stmt->fetch()) $error = 'Slug already in use in this category.';
                else {
                    $pdo->prepare('UPDATE services SET category_id = ?, name = ?, slug = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?')
                        ->execute([$category_id, $name, $slug, $description ?: null, $sort_order, $is_active, $id]);
                    $message = 'Service updated.';
                }
            }
        } else {
            $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? AND category_id = ?');
            $stmt->execute([$slug, $category_id]);
            if ($stmt->fetch()) $error = 'Slug already in use in this category.';
            else {
                $pdo->prepare('INSERT INTO services (category_id, name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([$category_id, $name, $slug, $description ?: null, $sort_order, $is_active]);
                $message = 'Service created.';
            }
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
    $message = 'Service deleted.';
}

$categoryFilter = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$sql = 'SELECT s.id, s.category_id, s.name, s.slug, s.is_active, s.sort_order, s.created_at, c.name AS category_name FROM services s LEFT JOIN categories c ON c.id = s.category_id WHERE 1=1';
$params = [];
if ($categoryFilter) {
    $sql .= ' AND s.category_id = ?';
    $params[] = $categoryFilter;
}
$sql .= ' ORDER BY s.sort_order ASC, s.id ASC';
$stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editSvc = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT id, category_id, name, slug, description, sort_order, is_active FROM services WHERE id = ?');
    $stmt->execute([$editId]);
    $editSvc = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="page-header">
    <h1>Services</h1>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('svcModal').classList.add('open')">Add Service</button>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="services">
    <select name="category_id">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
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
                <th>Category</th>
                <th>Slug</th>
                <th>Order</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $s): ?>
                <tr>
                    <td><?= (int) $s['id'] ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['category_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['slug']) ?></td>
                    <td><?= (int) $s['sort_order'] ?></td>
                    <td><?= $s['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td><?= htmlspecialchars($s['created_at']) ?></td>
                    <td>
                        <a href="?page=services&edit=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                        <a href="?page=services&delete=<?= (int) $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this service?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="svcModal" class="modal <?= $editSvc ? 'open' : '' ?>">
    <div class="modal-content">
        <h2><?= $editSvc ? 'Edit Service' : 'Add Service' ?></h2>
        <form method="post">
            <input type="hidden" name="_action" value="<?= $editSvc ? 'update' : 'create' ?>">
            <?php if ($editSvc): ?><input type="hidden" name="id" value="<?= (int) $editSvc['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= ($editSvc['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editSvc['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($editSvc['slug'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2"><?= htmlspecialchars($editSvc['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Sort order</label>
                <input type="number" name="sort_order" value="<?= (int) ($editSvc['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_active" value="1" <?= ($editSvc['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editSvc ? 'Update' : 'Create' ?></button>
                <a href="?page=services" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
