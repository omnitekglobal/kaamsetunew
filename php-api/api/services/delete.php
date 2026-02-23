<?php

requireAdmin();

$id = $_GET['_id'] ?? null;
if ($id === null) {
    jsonError('Service ID required', 400);
}
$id = (int) $id;

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM services WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    jsonError('Service not found', 404);
}
$pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
jsonSuccess(null, 'Service deleted');
