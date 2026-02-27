<?php

/**
 * POST /api/professionals/request
 * Capture a professional request/inquiry: mobile number (required) and optional referral code.
 * Public endpoint – no auth required.
 */
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$phone = trim($input['phone'] ?? '');
$referralCode = isset($input['referral_code']) ? trim((string) $input['referral_code']) : null;

if ($phone === '') {
    jsonError('Mobile number is required', 400);
}

// Basic phone validation: allow digits, spaces, +, -; require at least 10 digits
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) < 10) {
    jsonError('Please provide a valid mobile number (at least 10 digits)', 400);
}

try {
    $pdo = getDb();

    $stmt = $pdo->query("SHOW TABLES LIKE 'professional_requests'");
    if (!$stmt || $stmt->rowCount() === 0) {
        jsonError('Professional requests are not configured. Run migration 011_professional_requests.sql', 503);
    }

    $sql = 'INSERT INTO professional_requests (phone, referral_code) VALUES (?, ?)';
    $pdo->prepare($sql)->execute([$phone, $referralCode !== '' ? $referralCode : null]);

    $id = (int) $pdo->lastInsertId();
    jsonSuccess(
        ['id' => $id, 'phone' => $phone, 'referral_code' => $referralCode],
        'Request received',
        201
    );
} catch (PDOException $e) {
    jsonError('Could not save request. Please try again.', 500);
}
