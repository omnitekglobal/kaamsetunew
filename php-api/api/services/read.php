<?php

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('Service ID required', 400);
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT s.id, s.category_id, s.name, s.slug, s.description, s.is_active, s.sort_order, s.created_at, s.updated_at, c.name AS category_name FROM services s LEFT JOIN categories c ON c.id = s.category_id WHERE s.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    jsonError('Service not found', 404);
}
$row['id'] = (int) $row['id'];
$row['category_id'] = (int) $row['category_id'];
$row['sort_order'] = (int) $row['sort_order'];
$row['is_active'] = (bool) $row['is_active'];
jsonSuccess($row);
