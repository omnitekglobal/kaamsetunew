<?php

requireAdmin();

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('Category ID required', 400);
}
$id = (int) $id;

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    jsonError('Category not found', 404);
}

$updates = [];
$params = [];
$allowed = ['name', 'slug', 'description', 'sort_order', 'is_active'];
foreach ($allowed as $field) {
    if (!array_key_exists($field, $input)) continue;
    if ($field === 'sort_order') {
        $updates[] = 'sort_order = ?';
        $params[] = (int) $input['sort_order'];
    } elseif ($field === 'is_active') {
        $updates[] = 'is_active = ?';
        $params[] = (int) (bool) $input['is_active'];
    } else {
        $updates[] = "`$field` = ?";
        $params[] = trim((string) $input[$field]) ?: null;
    }
}

if (empty($updates)) {
    jsonError('No valid fields to update');
}

if (isset($input['slug'])) {
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
    $stmt->execute([trim($input['slug']), $id]);
    if ($stmt->fetch()) {
        jsonError('Category with this slug already exists', 409);
    }
}

$params[] = $id;
$pdo->prepare('UPDATE categories SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);

$stmt = $pdo->prepare('SELECT id, name, slug, description, sort_order, is_active, updated_at FROM categories WHERE id = ?');
$stmt->execute([$id]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);
$cat['id'] = (int) $cat['id'];
$cat['sort_order'] = (int) $cat['sort_order'];
$cat['is_active'] = (bool) $cat['is_active'];
jsonSuccess($cat, 'Category updated');
