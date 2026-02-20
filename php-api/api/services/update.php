<?php

requireStaff();

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('Service ID required', 400);
}
$id = (int) $id;

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, category_id FROM services WHERE id = ?');
$stmt->execute([$id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$existing) {
    jsonError('Service not found', 404);
}

$updates = [];
$params = [];
$allowed = ['category_id', 'name', 'slug', 'description', 'sort_order', 'is_active'];
foreach ($allowed as $field) {
    if (!array_key_exists($field, $input)) continue;
    if ($field === 'category_id') {
        $updates[] = 'category_id = ?';
        $params[] = (int) $input['category_id'];
    } elseif ($field === 'sort_order') {
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

if (isset($input['category_id'])) {
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
    $stmt->execute([(int) $input['category_id']]);
    if (!$stmt->fetch()) {
        jsonError('Category not found', 404);
    }
}

if (isset($input['slug'])) {
    $catId = isset($input['category_id']) ? (int) $input['category_id'] : (int) $existing['category_id'];
    $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? AND category_id = ? AND id != ?');
    $stmt->execute([trim($input['slug']), $catId, $id]);
    if ($stmt->fetch()) {
        jsonError('Service with this slug already exists in this category', 409);
    }
}

$params[] = $id;
$pdo->prepare('UPDATE services SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);

$stmt = $pdo->prepare('SELECT id, category_id, name, slug, description, sort_order, is_active, updated_at FROM services WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$row['id'] = (int) $row['id'];
$row['category_id'] = (int) $row['category_id'];
$row['sort_order'] = (int) $row['sort_order'];
$row['is_active'] = (bool) $row['is_active'];
jsonSuccess($row, 'Service updated');
