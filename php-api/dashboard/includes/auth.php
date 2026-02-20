<?php

declare(strict_types=1);

function currentUser(): ?array {
    return $_SESSION['dashboard_user'] ?? null;
}

function requireLogin(): array {
    $user = currentUser();
    if (!$user) {
        header('Location: ' . DASHBOARD_BASE . '/login.php');
        exit;
    }
    if (empty($user['is_active'])) {
        session_destroy();
        header('Location: ' . DASHBOARD_BASE . '/login.php?inactive=1');
        exit;
    }
    return $user;
}

function requireRole(string ...$allowed): array {
    $user = requireLogin();
    if (!in_array($user['role'], $allowed, true)) {
        header('Location: ' . DASHBOARD_BASE . '/index.php?forbidden=1');
        exit;
    }
    return $user;
}

function canAccessUsers(string $role): bool {
    return in_array($role, ['super_admin', 'admin'], true);
}

function canCreateDeleteUsers(string $role): bool {
    return in_array($role, ['super_admin', 'admin'], true);
}

function canAccessCategoriesServices(string $role): bool {
    return in_array($role, ['super_admin', 'admin', 'staff'], true);
}

function canAccessBookings(string $role): bool {
    return in_array($role, ['super_admin', 'admin', 'staff'], true);
}

function canAccessProfessionals(string $role): bool {
    return in_array($role, ['super_admin', 'admin', 'staff'], true);
}

/** Only admin and super_admin can approve/reject professionals and create user accounts */
function canApproveRejectProfessional(string $role): bool {
    return in_array($role, ['super_admin', 'admin'], true);
}

function roleLabel(string $role): string {
    return ucfirst(str_replace('_', ' ', $role));
}
