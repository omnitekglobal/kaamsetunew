<?php

require_once __DIR__ . '/../../includes/whatsapp.php';

// Super can create super_admin, team_leader, or staff; team_leader can create staff
$payload = requireAuth();
$callerRole = $payload->role ?? 'end_user';
$allowedRoles = [];
if ($callerRole === 'super_admin') $allowedRoles = ['super_admin', 'team_leader', 'staff'];
elseif ($callerRole === 'team_leader') $allowedRoles = ['staff'];
else jsonError('Forbidden', 403);

// Optional: referral_code support for staff users (migration 006_users_referral_code.sql)
$hasUserReferralCode = false;
try {
    $stmt = getDb()->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
    $hasUserReferralCode = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$phone = trim($input['phone'] ?? '');
$role = trim($input['role'] ?? $allowedRoles[0]);
$language = trim($input['language'] ?? '');
$village = trim($input['village'] ?? '');
$state = trim($input['state'] ?? '');
$landmark = trim($input['landmark'] ?? '');
$aadhaar = trim($input['aadhaar_no'] ?? '');
$pincode = trim($input['pincode'] ?? '');

if (!$name || !$phone || !$password) {
    jsonError('Name, phone and password are required');
}

if (!in_array($role, $allowedRoles, true)) {
    jsonError('Invalid role. You may only create: ' . implode(', ', $allowedRoles));
}

if (strlen($password) < 8) {
    jsonError('Password must be at least 8 characters');
}

// Email is optional in UI, but users.email is NOT NULL + UNIQUE in schema.
// If email is empty, synthesize one from the phone so constraints are satisfied.
if ($email === '') {
    $digits = preg_replace('/\D/', '', $phone);
    if ($digits === '') {
        jsonError('Phone number is required to generate an email', 400);
    }
    $email = 'user_' . $role . '_' . $digits . '@auto.kaamsetu';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Invalid email address');
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError('Email already registered', 409);
}

// Optional extra profile fields
$hasLanguageCol = false;
$hasVillageCol = false;
$hasStateCol = false;
$hasLandmarkCol = false;
$hasAadhaarCol = false;
$hasCreatedByCol = false;
$hasPincodeCol = false;
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'language'");
    $hasLanguageCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'village'");
    $hasVillageCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'state'");
    $hasStateCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'landmark'");
    $hasLandmarkCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'aadhaar_no'");
    $hasAadhaarCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
    $hasCreatedByCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pincode'");
    $hasPincodeCol = $colsStmt && $colsStmt->rowCount() > 0;
} catch (Throwable $e) {}

$hash = password_hash($password, PASSWORD_DEFAULT);

$hash = password_hash($password, PASSWORD_DEFAULT);

// Generate verification token
$verificationToken   = bin2hex(random_bytes(32)); // 64-char hex
$tokenExpiresAt      = date('Y-m-d H:i:s', time() + 86400); // 24 hours
$isVerified          = 0; // new users start unverified

// Build dynamic insert based on available columns
$columns = ['name', 'email', 'password', 'phone', 'role', 'is_verified', 'verification_token', 'verification_token_expires_at'];
$values = [$name, $email, $hash, $phone ?: null, $role, $isVerified, $verificationToken, $tokenExpiresAt];

if ($hasUserReferralCode) {
    $referralCode = null;
    if ($role === 'staff') {
        try {
            $referralCode = 'STF' . strtoupper(bin2hex(random_bytes(3)));
        } catch (Throwable $e) {
            $referralCode = 'STF' . strtoupper(substr(md5(uniqid((string) $email, true)), 0, 6));
        }
    }
    $columns[] = 'referral_code';
    $values[] = $referralCode;
}

if ($hasLanguageCol) {
    $columns[] = 'language';
    $values[] = $language !== '' ? $language : null;
}
if ($hasVillageCol) {
    $columns[] = 'village';
    $values[] = $village !== '' ? $village : null;
}
if ($hasStateCol) {
    $columns[] = 'state';
    $values[] = $state !== '' ? $state : null;
}
if ($hasLandmarkCol) {
    $columns[] = 'landmark';
    $values[] = $landmark !== '' ? $landmark : null;
}
if ($hasAadhaarCol) {
    $columns[] = 'aadhaar_no';
    $values[] = $aadhaar !== '' ? $aadhaar : null;
}
if ($hasPincodeCol) {
    $columns[] = 'pincode';
    $values[] = $pincode !== '' ? $pincode : null;
}
if ($hasCreatedByCol) {
    $columns[] = 'created_by';
    $teamLeaderId = (int) ($input['team_leader_id'] ?? 0);
    if ($role === 'staff' && $callerRole === 'super_admin' && $teamLeaderId > 0) {
        $values[] = $teamLeaderId;
    } else {
        $values[] = (int) ($payload->sub ?? 0) ?: null;
    }
}

$placeholders = implode(',', array_fill(0, count($columns), '?'));
$sql = 'INSERT INTO users (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
$stmt = $pdo->prepare($sql);
$stmt->execute($values);
$userId = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare(
    'SELECT id, name, email, phone, role, is_active, created_at'
    . ($hasPincodeCol ? ', pincode' : '')
    . ' FROM users WHERE id = ?'
);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user['id'] = (int) $user['id'];
$user['is_verified'] = false;

$whatsappSent = false;
if ($phone && $verificationToken) {
    try {
        $whatsappSent = sendWhatsAppVerification($phone, $verificationToken);
    } catch (Throwable $e) {}
}

$user['whatsapp_sent'] = $whatsappSent;

jsonSuccess($user, 'User created', 201);
