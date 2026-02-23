<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/** Roles: super_admin (adds team_leader), team_leader (adds staff), staff (bookings/professionals/profile only), professional, end_user */
const ROLES = ['super_admin', 'team_leader', 'staff', 'professional', 'end_user'];

function getJwtSecret(): string {
    $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
    if (empty($secret)) {
        $secret = $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: 'kaamsetu-default-secret-change-in-env';
    }
    return $secret;
}

/**
 * Get Bearer token from Authorization header
 */
function getBearerToken(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/', $header, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * Decode JWT and return payload or null
 */
function decodeJwt(?string $token): ?object {
    if (empty($token)) return null;
    try {
        $secret = getJwtSecret();
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Require valid JWT; return decoded payload or send 401 and exit
 */
function requireAuth(): object {
    $token = getBearerToken();
    $payload = decodeJwt($token);
    if (!$payload || empty($payload->sub)) {
        jsonError('Unauthorized', 401);
    }
    return $payload;
}

/**
 * Require one of the given roles (e.g. requireRole('super_admin', 'team_leader'))
 */
function requireRole(string ...$allowedRoles): object {
    $payload = requireAuth();
    $userRole = $payload->role ?? 'end_user';
    if (!in_array($userRole, $allowedRoles, true)) {
        jsonError('Forbidden', 403);
    }
    return $payload;
}

/**
 * Only super_admin
 */
function requireSuperAdmin(): object {
    return requireRole('super_admin');
}

/**
 * Super or team leader only (user management, categories, services)
 */
function requireAdmin(): object {
    return requireRole('super_admin', 'team_leader');
}

/**
 * Staff, team leader, or super (bookings, professionals, profile)
 */
function requireStaff(): object {
    return requireRole('super_admin', 'team_leader', 'staff');
}

/**
 * Create JWT for user (call after login)
 */
function createToken(int $userId, string $email, string $role, int $expireSeconds = 86400): string {
    $secret = getJwtSecret();
    $now = time();
    $payload = [
        'sub' => (string) $userId,
        'email' => $email,
        'role' => $role,
        'iat' => $now,
        'exp' => $now + $expireSeconds,
    ];
    return JWT::encode($payload, $secret, 'HS256');
}

/**
 * Get current user ID from JWT (no exit)
 */
function currentUserId(): ?int {
    $token = getBearerToken();
    $payload = decodeJwt($token);
    return $payload && isset($payload->sub) ? (int) $payload->sub : null;
}
