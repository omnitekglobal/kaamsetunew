<?php

// Public: create booking (customer submits form). Status = pending; optional created_by if auth.
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$service = trim($input['service'] ?? '');
$pincode = trim($input['pincode'] ?? '');
$language = trim($input['language'] ?? '');
$referralCode = isset($input['referral_code']) ? trim((string) $input['referral_code']) : null;

if (!$name || !$phone || !$service || !$pincode || !$language) {
    jsonError('Name, phone, service, pincode and language are required', 400);
}

$bookingId = 'KS' . time() . bin2hex(random_bytes(4));
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
    $hasReferralCodeCol = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'referral_code'")->rowCount() > 0;
    if ($hasReferralCodeCol && $referralCode !== null && $referralCode !== '') {
        $cols .= ', referral_code';
        $placeholders .= ', ?';
        $params[] = $referralCode;
    }
    $pdo->prepare("INSERT INTO bookings ($cols) VALUES ($placeholders)")->execute($params);

    $logTableExists = $pdo->query("SHOW TABLES LIKE 'booking_log'")->rowCount() > 0;
    if ($logTableExists) {
        $details = json_encode(['service' => $service, 'created_by' => $createdBy]);
        $pdo->prepare('INSERT INTO booking_log (booking_id, action, by_user_id, details) VALUES (?, ?, ?, ?)')
            ->execute([$bookingId, 'created', $createdBy, $details]);
    }
} catch (PDOException $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'exist') !== false) {
        jsonError('Bookings table does not exist. Run migration 000_bookings_base.sql', 500);
    }
    if (strpos($msg, 'Unknown column') !== false || strpos($msg, 'status') !== false) {
        try {
            $pdo->prepare(
                'INSERT INTO bookings (bookingId, name, email, phone, service, pincode, language) VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$bookingId, $name, $email ?: null, $phone, $service, $pincode, $language]);
            $logTableExists = $pdo->query("SHOW TABLES LIKE 'booking_log'")->rowCount() > 0;
            if ($logTableExists) {
                $details = json_encode(['service' => $service, 'created_by' => $createdBy]);
                @$pdo->prepare('INSERT INTO booking_log (booking_id, action, by_user_id, details) VALUES (?, ?, ?, ?)')->execute([$bookingId, 'created', $createdBy, $details]);
            }
        } catch (PDOException $e2) {
            jsonError('Database error: ' . $e2->getMessage(), 500);
        }
    } else {
        jsonError('Database error: ' . $msg, 500);
    }
}

// Verify row exists (catches wrong DB, no commit, or misconfigured connection)
$check = $pdo->prepare('SELECT 1 FROM bookings WHERE bookingId = ? LIMIT 1');
$check->execute([$bookingId]);
if ($check->fetch() === false) {
    jsonError('Booking insert did not persist. Check database: ' . ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'unknown'), 500);
}

// Create or find customer and link to booking (separate from dashboard "Add Customer")
try {
    $customersTableExists = $pdo->query("SHOW TABLES LIKE 'customers'")->rowCount() > 0;
    if ($customersTableExists && $name !== '' && $phone !== '') {
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        $customerId = $existing ? (int) $existing['id'] : null;
        if (!$customerId) {
            $custCols = 'name, phone, email, city, state, pincode, language, referral_code, created_at';
            $custPh = '?, ?, ?, ?, ?, ?, ?, ?, NOW()';
            $city = isset($input['city']) ? trim((string) $input['city']) : null;
            $state = isset($input['state']) ? trim((string) $input['state']) : null;
            $custParams = [$name, $phone, $email ?: null, $city ?: null, $state ?: null, $pincode ?: null, $language ?: null, $referralCode];
            $pdo->prepare("INSERT INTO customers ($custCols) VALUES ($custPh)")->execute($custParams);
            $customerId = (int) $pdo->lastInsertId();
        }
        if ($customerId > 0) {
            $hasBookingCustomerId = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'customer_id'")->rowCount() > 0;
            if ($hasBookingCustomerId) {
                $pdo->prepare('UPDATE bookings SET customer_id = ? WHERE bookingId = ?')->execute([$customerId, $bookingId]);
            }
        }
    }
} catch (PDOException $e) {
    // Don't fail the booking; customer link is optional
}

jsonSuccess(['bookingId' => $bookingId], 'Booking created', 201);
