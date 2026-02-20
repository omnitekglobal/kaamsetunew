<?php

// Public: list categories (optional auth for future)
$pdo = getDb();
$activeOnly = !isset($_GET['all']) || $_GET['all'] !== '1';
$sql = 'SELECT id, name, slug, description, sort_order, is_active, created_at FROM categories';
if ($activeOnly) {
    $sql .= ' WHERE is_active = 1';
}
$sql .= ' ORDER BY sort_order ASC, id ASC';
$stmt = $pdo->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($items as &$row) {
    $row['id'] = (int) $row['id'];
    $row['sort_order'] = (int) $row['sort_order'];
    $row['is_active'] = (bool) $row['is_active'];
}
jsonSuccess(['items' => $items]);
