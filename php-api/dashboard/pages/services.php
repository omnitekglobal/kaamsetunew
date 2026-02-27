<?php

requireRole('super_admin', 'team_leader');
$pdo = getDb();

$message = '';
$error = '';

$uploadDir = dirname(dirname(__DIR__)) . '/uploads/services';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}
$placeholderUrl = DASHBOARD_BASE . '/assets/service-placeholder.svg';
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'create';
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $remove_icon = isset($_POST['remove_icon']) && $_POST['remove_icon'] === '1';
    $id = (int) ($_POST['id'] ?? 0);

    if (!$name) $error = 'Name is required.';
    elseif (!$category_id) $error = 'Category is required.';
    else {
        if ($slug === '') $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?: 'service';
        if ($action === 'update' && $id) {
            $stmt = $pdo->prepare('SELECT id, icon FROM services WHERE id = ?');
            $stmt->execute([$id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) $error = 'Service not found.';
            else {
                $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? AND category_id = ? AND id != ?');
                $stmt->execute([$slug, $category_id, $id]);
                if ($stmt->fetch()) $error = 'Slug already in use in this category.';
                else {
                    $iconPath = $existing['icon'];
                    if ($remove_icon && $iconPath) {
                        $fullPath = dirname(dirname(__DIR__)) . '/' . $iconPath;
                        if (is_file($fullPath)) @unlink($fullPath);
                        $iconPath = null;
                    }
                    $file = $_FILES['icon'] ?? null;
                    if ($file && ($file['error'] ?? 0) === UPLOAD_ERR_OK && in_array($file['type'] ?? '', $allowedImageTypes, true)) {
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
                        if (!in_array($ext, $allowedExts, true)) $ext = 'jpg';
                        if ($iconPath) {
                            $oldFull = dirname(dirname(__DIR__)) . '/' . $iconPath;
                            if (is_file($oldFull)) @unlink($oldFull);
                        }
                        $iconPath = 'uploads/services/' . $id . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], dirname(dirname(__DIR__)) . '/' . $iconPath)) {
                            // ok
                        } else {
                            $iconPath = $existing['icon'];
                        }
                    } elseif (!$remove_icon) {
                        $iconPath = $existing['icon'];
                    }
                    $pdo->prepare('UPDATE services SET category_id = ?, name = ?, slug = ?, description = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?')
                        ->execute([$category_id, $name, $slug, $description ?: null, $iconPath, $sort_order, $is_active, $id]);
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
                $newId = (int) $pdo->lastInsertId();
                $iconPath = null;
                $file = $_FILES['icon'] ?? null;
                if ($file && ($file['error'] ?? 0) === UPLOAD_ERR_OK && in_array($file['type'] ?? '', $allowedImageTypes, true)) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
                    if (!in_array($ext, $allowedExts, true)) $ext = 'jpg';
                    $iconPath = 'uploads/services/' . $newId . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], dirname(dirname(__DIR__)) . '/' . $iconPath)) {
                        $pdo->prepare('UPDATE services SET icon = ? WHERE id = ?')->execute([$iconPath, $newId]);
                    }
                }
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
$servicesPerPage = 20;
$servicesPage = max(1, (int) ($_GET['p'] ?? 1));

$countSql = 'SELECT COUNT(*) FROM services s WHERE 1=1';
$countParams = [];
if ($categoryFilter) {
    $countSql .= ' AND s.category_id = ?';
    $countParams[] = $categoryFilter;
}
$stmt = $countParams ? $pdo->prepare($countSql) : $pdo->query($countSql);
$stmt->execute($countParams);
$totalServices = (int) $stmt->fetchColumn();

$offset = ($servicesPage - 1) * $servicesPerPage;
$sql = 'SELECT s.id, s.category_id, s.name, s.slug, s.icon, s.is_active, s.sort_order, s.created_at, c.name AS category_name FROM services s LEFT JOIN categories c ON c.id = s.category_id WHERE 1=1';
$params = [];
if ($categoryFilter) {
    $sql .= ' AND s.category_id = ?';
    $params[] = $categoryFilter;
}
$sql .= ' ORDER BY s.sort_order ASC, s.id ASC LIMIT ' . $servicesPerPage . ' OFFSET ' . $offset;
$stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editSvc = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT id, category_id, name, slug, description, icon, sort_order, is_active FROM services WHERE id = ?');
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
<?php
$paginationQueryParams = ['page' => 'services'];
if ($categoryFilter) $paginationQueryParams['category_id'] = $categoryFilter;
?>
<div class="card overflow-x">
    <table class="table">
        <thead>
            <tr>
                <th>Icon</th>
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
                    <td>
                        <?php if (!empty($s['icon'])): ?>
                            <img src="/<?= htmlspecialchars($s['icon']) ?>" alt="" class="service-thumb" width="40" height="40" style="object-fit:cover;border-radius:6px;">
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($placeholderUrl) ?>" alt="" width="40" height="40" style="object-fit:cover;border-radius:6px;opacity:0.7;">
                        <?php endif; ?>
                    </td>
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
    <?php
    $paginationTotal = $totalServices;
    $paginationPage = $servicesPage;
    $paginationPerPage = $servicesPerPage;
    require __DIR__ . '/../includes/pagination.php';
    ?>
</div>

<div id="svcModal" class="modal <?= $editSvc ? 'open' : '' ?>">
    <div class="modal-content">
        <h2><?= $editSvc ? 'Edit Service' : 'Add Service' ?></h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_action" value="<?= $editSvc ? 'update' : 'create' ?>">
            <?php if ($editSvc): ?><input type="hidden" name="id" value="<?= (int) $editSvc['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>Icon / Image</label>
                <div class="service-icon-preview">
                    <?php if (!empty($editSvc['icon'])): ?>
                        <img id="svcIconPreview" src="/<?= htmlspecialchars($editSvc['icon']) ?>" alt="" width="80" height="80" style="object-fit:cover;border-radius:8px;border:1px solid #ddd;">
                    <?php else: ?>
                        <img id="svcIconPreview" src="<?= htmlspecialchars($placeholderUrl) ?>" alt="" width="80" height="80" style="object-fit:cover;border-radius:8px;border:1px solid #ddd;opacity:0.8;">
                    <?php endif; ?>
                </div>
                <input type="file" name="icon" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
                <small class="text-muted">Optional. JPG, PNG, GIF or WebP. Placeholder shown if none uploaded.</small>
                <?php if ($editSvc && !empty($editSvc['icon'])): ?>
                    <label class="checkbox-inline"><input type="checkbox" name="remove_icon" value="1"> Remove current image</label>
                <?php endif; ?>
            </div>
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
