<?php

requireAdmin();

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('Category ID required', 400);
}
$id = (int) $id;

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    jsonError('Category not found', 404);
}
$pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
jsonSuccess(null, 'Category deleted');
