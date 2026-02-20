<?php

// Public: create booking (customer submits form)
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$service = trim($input['service'] ?? '');
$pincode = trim($input['pincode'] ?? '');
$language = trim($input['language'] ?? '');

if (!$name || !$phone || !$service || !$pincode || !$language) {
    jsonError('Name, phone, service, pincode and language are required', 400);
}

$bookingId = 'KS' . time();

try {
    $pdo = getDb();
    $pdo->prepare(
        'INSERT INTO bookings (bookingId, name, email, phone, service, pincode, language) VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$bookingId, $name, $email ?: null, $phone, $service, $pincode, $language]);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'exist') !== false) {
        jsonError('Bookings table does not exist', 500);
    }
    throw $e;
}

jsonSuccess(['bookingId' => $bookingId], 'Booking created', 201);
