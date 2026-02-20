<?php

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('Category ID required', 400);
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, name, slug, description, sort_order, is_active, created_at, updated_at FROM categories WHERE id = ?');
$stmt->execute([$id]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cat) {
    jsonError('Category not found', 404);
}
$cat['id'] = (int) $cat['id'];
$cat['sort_order'] = (int) $cat['sort_order'];
$cat['is_active'] = (bool) $cat['is_active'];
jsonSuccess($cat);
