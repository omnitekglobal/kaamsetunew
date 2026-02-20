<?php

// Public: register as professional (service provider) - pending until admin approves
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$pincode = trim($input['pincode'] ?? '');
$language = trim($input['language'] ?? '');
$services = $input['services'] ?? '';

if (!$name || !$phone || !$city || !$state || !$pincode || !$language || $services === '') {
    jsonError('Name, phone, city, state, pincode, language and services are required', 400);
}

$professionalId = 'PR' . time();

try {
    $pdo = getDb();
    $servicesStr = is_array($services) ? implode(', ', $services) : (string) $services;
    $stmt = $pdo->prepare("SHOW COLUMNS FROM professionals LIKE 'status'");
    $stmt->execute();
    $hasStatus = $stmt->rowCount() > 0;
    if ($hasStatus) {
        $pdo->prepare(
            'INSERT INTO professionals (professionalId, name, phone, email, city, state, pincode, language, services, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$professionalId, $name, $phone, $email ?: null, $city, $state, $pincode, $language, $servicesStr, 'pending']);
    } else {
        $pdo->prepare(
            'INSERT INTO professionals (professionalId, name, phone, email, city, state, pincode, language, services) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$professionalId, $name, $phone, $email ?: null, $city, $state, $pincode, $language, $servicesStr]);
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'exist') !== false) {
        jsonError('Professionals table does not exist', 500);
    }
    throw $e;
}

jsonSuccess(['professionalId' => $professionalId], 'Registration submitted', 201);
