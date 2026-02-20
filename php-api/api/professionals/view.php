<?php

$professionalId = $_GET['_id'] ?? null;
if ($professionalId === null || $professionalId === '') {
    jsonError('Professional ID required', 400);
}

try {
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM professionals WHERE professionalId = ?');
    $stmt->execute([$professionalId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    jsonError('Server error', 500);
}

if (!$row) {
    jsonError('Professional not found', 404);
}

jsonSuccess($row);
