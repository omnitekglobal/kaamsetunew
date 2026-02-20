<?php

$bookingId = $_GET['_id'] ?? null;
if ($bookingId === null || $bookingId === '') {
    jsonError('Booking ID required', 400);
}

try {
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE bookingId = ?');
    $stmt->execute([$bookingId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    jsonError('Server error', 500);
}

if (!$row) {
    jsonError('Booking not found', 404);
}

jsonSuccess($row);
