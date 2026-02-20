<?php

requireStaff();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$categoryId = (int) ($input['category_id'] ?? 0);
$name = trim($input['name'] ?? '');
$slug = trim($input['slug'] ?? '');
$description = trim($input['description'] ?? '');
$sortOrder = (int) ($input['sort_order'] ?? 0);
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if (!$name) {
    jsonError('Name is required');
}
if (!$categoryId) {
    jsonError('category_id is required');
}
if ($slug === '') {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $slug = trim($slug, '-') ?: 'service';
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$stmt->execute([$categoryId]);
if (!$stmt->fetch()) {
    jsonError('Category not found', 404);
}

$stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? AND category_id = ?');
$stmt->execute([$slug, $categoryId]);
if ($stmt->fetch()) {
    jsonError('Service with this slug already exists in this category', 409);
}

$stmt = $pdo->prepare('INSERT INTO services (category_id, name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$categoryId, $name, $slug, $description ?: null, $sortOrder, $isActive]);
$id = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT id, category_id, name, slug, description, sort_order, is_active, created_at FROM services WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$row['id'] = (int) $row['id'];
$row['category_id'] = (int) $row['category_id'];
$row['sort_order'] = (int) $row['sort_order'];
$row['is_active'] = (bool) $row['is_active'];
jsonSuccess($row, 'Service created', 201);
