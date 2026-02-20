<?php

requireStaff(); // staff, admin, super_admin

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$slug = trim($input['slug'] ?? '');
$description = trim($input['description'] ?? '');
$sortOrder = (int) ($input['sort_order'] ?? 0);
$isActive = isset($input['is_active']) ? (int) (bool) $input['is_active'] : 1;

if (!$name) {
    jsonError('Name is required');
}
if ($slug === '') {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $slug = trim($slug, '-') ?: 'category';
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
$stmt->execute([$slug]);
if ($stmt->fetch()) {
    jsonError('Category with this slug already exists', 409);
}

$stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $slug, $description ?: null, $sortOrder, $isActive]);
$id = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT id, name, slug, description, sort_order, is_active, created_at FROM categories WHERE id = ?');
$stmt->execute([$id]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);
$cat['id'] = (int) $cat['id'];
$cat['sort_order'] = (int) $cat['sort_order'];
$cat['is_active'] = (bool) $cat['is_active'];
jsonSuccess($cat, 'Category created', 201);
