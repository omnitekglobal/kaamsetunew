<?php

requireRole('super_admin', 'admin', 'staff');
$pdo = getDb();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'create';
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $id = (int) ($_POST['id'] ?? 0);

    if (!$name) {
        $error = 'Name is required.';
    } else {
        if ($slug === '') $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?: 'category';
        if ($action === 'update' && $id) {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) $error = 'Category not found.';
            else {
                $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
                $stmt->execute([$slug, $id]);
                if ($stmt->fetch()) $error = 'Slug already in use.';
                else {
                    $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?')
                        ->execute([$name, $slug, $description ?: null, $sort_order, $is_active, $id]);
                    $message = 'Category updated.';
                }
            }
        } else {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
            $stmt->execute([$slug]);
            if ($stmt->fetch()) $error = 'Slug already in use.';
            else {
                $pdo->prepare('INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$name, $slug, $description ?: null, $sort_order, $is_active]);
                $message = 'Category created.';
            }
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    $message = 'Category deleted.';
}

$stmt = $pdo->query('SELECT id, name, slug, description, sort_order, is_active, created_at FROM categories ORDER BY sort_order ASC, id ASC');
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editCat = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT id, name, slug, description, sort_order, is_active FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCat = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="page-header">
    <h1>Categories</h1>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('catModal').classList.add('open')">Add Category</button>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card overflow-x">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Order</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= (int) $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['slug']) ?></td>
                    <td><?= (int) $c['sort_order'] ?></td>
                    <td><?= $c['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td><?= htmlspecialchars($c['created_at']) ?></td>
                    <td>
                        <a href="?page=categories&edit=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                        <a href="?page=categories&delete=<?= (int) $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="catModal" class="modal <?= $editCat ? 'open' : '' ?>">
    <div class="modal-content">
        <h2><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
        <form method="post">
            <input type="hidden" name="_action" value="<?= $editCat ? 'update' : 'create' ?>">
            <?php if ($editCat): ?><input type="hidden" name="id" value="<?= (int) $editCat['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editCat['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($editCat['slug'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2"><?= htmlspecialchars($editCat['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Sort order</label>
                <input type="number" name="sort_order" value="<?= (int) ($editCat['sort_order'] ?? 0) ?>">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_active" value="1" <?= ($editCat['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editCat ? 'Update' : 'Create' ?></button>
                <a href="?page=categories" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
