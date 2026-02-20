<?php

// Public list; optional filter by category_id
$pdo = getDb();
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';

$sql = 'SELECT s.id, s.category_id, s.name, s.slug, s.description, s.is_active, s.sort_order, s.created_at, c.name AS category_name
        FROM services s
        LEFT JOIN categories c ON c.id = s.category_id
        WHERE 1=1';
$params = [];
if ($categoryId) {
    $sql .= ' AND s.category_id = ?';
    $params[] = $categoryId;
}
if ($activeOnly) {
    $sql .= ' AND s.is_active = 1';
}
$sql .= ' ORDER BY s.sort_order ASC, s.id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($items as &$row) {
    $row['id'] = (int) $row['id'];
    $row['category_id'] = (int) $row['category_id'];
    $row['sort_order'] = (int) $row['sort_order'];
    $row['is_active'] = (bool) $row['is_active'];
}
jsonSuccess(['items' => $items]);
