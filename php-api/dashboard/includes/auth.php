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

/** Super can add team leader; team leader can add staff. Staff cannot access users. */
function canAccessUsers(string $role): bool {
    return in_array($role, ['super_admin', 'team_leader'], true);
}

/** Who can create/delete users: super can manage all (except other supers); team_leader → staff only. */
function canCreateDeleteUsers(string $role): bool {
    return in_array($role, ['super_admin', 'team_leader'], true);
}

/** Super can edit/delete any user except self and other super_admins; team_leader only their scope. */
function canManageUser(string $managerRole, array $targetUser, int $managerId): bool {
    if ((int) ($targetUser['id'] ?? 0) === $managerId) return false;
    $scope = managedRoleScope($managerRole);
    if ($scope === null) return ($targetUser['role'] ?? '') !== 'super_admin';
    return ($targetUser['role'] ?? '') === $scope;
}

/** Roles the current user is allowed to create. Super can create super_admin, team_leader, or staff; team_leader can create staff. */
function allowedRolesToCreate(string $role): array {
    if ($role === 'super_admin') return ['super_admin', 'team_leader', 'staff'];
    if ($role === 'team_leader') return ['staff'];
    return [];
}

/** Roles the current user can assign when editing a user. Super can set any role including super_admin. */
function rolesEditableBy(string $role): array {
    if ($role === 'super_admin') return ['super_admin', 'team_leader', 'staff', 'professional', 'end_user'];
    if ($role === 'team_leader') return ['staff'];
    return [];
}

/** Roles the current user is allowed to manage (list/edit). Super sees all; team_leader sees staff. */
function managedRoleScope(string $role): ?string {
    if ($role === 'super_admin') return null; // see all users
    if ($role === 'team_leader') return 'staff';
    return null;
}

/** Categories & services: only super and team leader. Staff cannot. */
function canAccessCategoriesServices(string $role): bool {
    return in_array($role, ['super_admin', 'team_leader'], true);
}

/** Bookings: super, team leader, staff, and professional (professional sees only own/service-matching bookings). */
function canAccessBookings(string $role): bool {
    return in_array($role, ['super_admin', 'team_leader', 'staff', 'professional'], true);
}

/** Professionals: super, team leader, staff (view and edit). */
function canAccessProfessionals(string $role): bool {
    return in_array($role, ['super_admin', 'team_leader', 'staff'], true);
}

/** Approve/reject professional registrations: only super and team leader. */
function canApproveRejectProfessional(string $role): bool {
    return in_array($role, ['super_admin', 'team_leader'], true);
}

/** Roles that appear as user sub-menus (and are valid for ?role=). Super sees all types; team_leader sees Staff only. */
function userListRoleFilters(string $role): array {
    if ($role === 'super_admin') return ['super_admin', 'team_leader', 'staff', 'professional', 'end_user'];
    if ($role === 'team_leader') return ['staff'];
    return [];
}

function roleLabel(string $role): string {
    $labels = [
        'super_admin' => 'Super Admin',
        'team_leader' => 'Team Leader',
        'staff' => 'Staff',
        'professional' => 'Professional',
        'end_user' => 'Customers',
    ];
    return $labels[$role] ?? ucfirst(str_replace('_', ' ', $role));
}
