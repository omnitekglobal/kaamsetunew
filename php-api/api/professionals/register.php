<?php

// Public: register as professional (service provider) - pending until admin approves.
// Optionally accepts a referral_code which can belong to a staff user (users.referral_code)
// or an existing professional (professionals.referral_code). In both cases we store the
// referrer's user_id in professionals.referred_by_user_id when that column exists.
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$pincode = trim($input['pincode'] ?? '');
$language = trim($input['language'] ?? ''); // now optional
$village = trim($input['village'] ?? '');
$landmark = trim($input['landmark'] ?? '');
$aadhaar = trim($input['aadhaar_no'] ?? '');
$services = $input['services'] ?? '';
$referralCodeInput = trim($input['referral_code'] ?? '');

if (!$name || !$phone || !$city || !$state || !$pincode || $services === '') {
    jsonError('Name, phone, city, state, pincode and services are required', 400);
}

$professionalId = 'PR' . time();

try {
    $pdo = getDb();
    $servicesStr = is_array($services) ? implode(', ', $services) : (string) $services;

    // Detect optional columns on professionals table.
    $stmt = $pdo->prepare("SHOW COLUMNS FROM professionals LIKE 'status'");
    $stmt->execute();
    $hasStatus = $stmt->rowCount() > 0;
    $stmt = $pdo->prepare("SHOW COLUMNS FROM professionals LIKE 'referred_by_user_id'");
    $stmt->execute();
    $hasReferredByUserId = $stmt->rowCount() > 0;
    $stmt = $pdo->prepare("SHOW COLUMNS FROM professionals LIKE 'referral_code'");
    $stmt->execute();
    $hasProfessionalReferralCode = $stmt->rowCount() > 0;
    $stmt = $pdo->prepare("SHOW COLUMNS FROM professionals LIKE 'village'");
    $stmt->execute();
    $hasVillageCol = $stmt->rowCount() > 0;
    $stmt = $pdo->prepare("SHOW COLUMNS FROM professionals LIKE 'landmark'");
    $stmt->execute();
    $hasLandmarkCol = $stmt->rowCount() > 0;
    $stmt = $pdo->prepare("SHOW COLUMNS FROM professionals LIKE 'aadhaar_no'");
    $stmt->execute();
    $hasAadhaarCol = $stmt->rowCount() > 0;

    // Resolve referral (staff or professional) to a user_id if provided.
    $referredByUserId = null;
    if ($hasReferredByUserId && $referralCodeInput !== '') {
        $code = $referralCodeInput;

        // 1) Staff / other users with referral_code
        $stmt = $pdo->prepare('SELECT id FROM users WHERE referral_code = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $referredByUserId = (int) $row['id'];
        } else {
            // 2) Existing professional by referral_code → use their user_id if available
            $stmt = $pdo->prepare('SELECT user_id FROM professionals WHERE referral_code = ? AND user_id IS NOT NULL');
            $stmt->execute([$code]);
            $proRef = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($proRef && !empty($proRef['user_id'])) {
                $referredByUserId = (int) $proRef['user_id'];
            } else {
                jsonError('Invalid referral code', 400);
            }
        }
    }

    // Build insert dynamically based on which columns exist.
    $columns = ['professionalId', 'name', 'phone', 'email', 'city', 'state', 'pincode', 'language', 'services'];
    $values = [$professionalId, $name, $phone, $email ?: null, $city, $state, $pincode, $language !== '' ? $language : null, $servicesStr];

    if ($hasVillageCol) {
        $columns[] = 'village';
        $values[] = $village !== '' ? $village : null;
    }
    if ($hasLandmarkCol) {
        $columns[] = 'landmark';
        $values[] = $landmark !== '' ? $landmark : null;
    }
    if ($hasAadhaarCol) {
        $columns[] = 'aadhaar_no';
        $values[] = $aadhaar !== '' ? $aadhaar : null;
    }

    if ($hasStatus) {
        $columns[] = 'status';
        $values[] = 'pending';
    }
    if ($hasReferredByUserId) {
        $columns[] = 'referred_by_user_id';
        $values[] = $referredByUserId;
    }
    if ($hasProfessionalReferralCode) {
        // Each professional can have their own referral code later (e.g. on approval),
        // but at creation time we leave it NULL here; dashboard may populate differently.
        $columns[] = 'referral_code';
        $values[] = null;
    }

    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO professionals (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'exist') !== false) {
        jsonError('Professionals table does not exist', 500);
    }
    throw $e;
}

jsonSuccess(['professionalId' => $professionalId], 'Registration submitted', 201);
