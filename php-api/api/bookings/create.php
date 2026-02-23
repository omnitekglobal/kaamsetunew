<?php

// Public: create booking (customer submits form). Status = pending; optional created_by if auth.
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
$createdBy = null;
if (function_exists('currentUserId')) {
    require_once __DIR__ . '/../../includes/auth.php';
    $createdBy = currentUserId();
}

try {
    $pdo = getDb();
    $cols = 'bookingId, name, email, phone, service, pincode, language, status, created_at';
    $placeholders = '?, ?, ?, ?, ?, ?, ?, ?, NOW()';
    $params = [$bookingId, $name, $email ?: null, $phone, $service, $pincode, $language, 'pending'];
    if ($createdBy !== null) {
        $cols .= ', created_by';
        $placeholders .= ', ?';
        $params[] = $createdBy;
    }
    $pdo->prepare("INSERT INTO bookings ($cols) VALUES ($placeholders)")->execute($params);

    $logTableExists = $pdo->query("SHOW TABLES LIKE 'booking_log'")->rowCount() > 0;
    if ($logTableExists) {
        $details = json_encode(['service' => $service, 'created_by' => $createdBy]);
        $pdo->prepare('INSERT INTO booking_log (booking_id, action, by_user_id, details) VALUES (?, ?, ?, ?)')
            ->execute([$bookingId, 'created', $createdBy, $details]);
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'exist') !== false) {
        jsonError('Bookings table does not exist', 500);
    }
    if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'status') !== false) {
        $pdo->prepare(
            'INSERT INTO bookings (bookingId, name, email, phone, service, pincode, language) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$bookingId, $name, $email ?: null, $phone, $service, $pincode, $language]);
        $logTableExists = $pdo->query("SHOW TABLES LIKE 'booking_log'")->rowCount() > 0;
        if ($logTableExists) {
            $details = json_encode(['service' => $service, 'created_by' => $createdBy]);
            @$pdo->prepare('INSERT INTO booking_log (booking_id, action, by_user_id, details) VALUES (?, ?, ?, ?)')->execute([$bookingId, 'created', $createdBy, $details]);
        }
    } else {
        throw $e;
    }
}

jsonSuccess(['bookingId' => $bookingId], 'Booking created', 201);
