<header class="dashboard-header">
    <div class="header-brand">
        <button type="button" class="menu-toggle" id="sidebar-toggle" aria-label="Open menu">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a href="<?= DASHBOARD_BASE ?>/index.php">KaamSetu</a>
    </div>
    <div class="header-user">
        <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
        <span class="user-role"><?= htmlspecialchars(roleLabel($user['role'])) ?></span>
        <a href="<?= DASHBOARD_BASE ?>/logout.php" class="btn btn-sm btn-outline">Logout</a>
    </div>
</header>
<div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>
<aside class="sidebar" id="sidebar">
    <button type="button" class="sidebar-close" id="sidebar-close" aria-label="Close menu">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <nav class="sidebar-nav">
        <a href="<?= DASHBOARD_BASE ?>/index.php?page=home" class="nav-item <?= ($page ?? '') === 'home' ? 'active' : '' ?>">Dashboard</a>
        <?php if (canAccessUsers($user['role'])): ?>
            <?php
            $userRoles = userListRoleFilters($user['role']);
            $usersPageActive = ($page ?? '') === 'users';
            $currentRoleFilter = $_GET['role'] ?? '';
            ?>
            <div class="nav-group <?= $usersPageActive ? 'open' : '' ?>" id="nav-group-users">
                <button type="button" class="nav-item nav-group-label" aria-expanded="<?= $usersPageActive ? 'true' : 'false' ?>" aria-controls="nav-group-users-sub" id="nav-group-users-btn">
                    <span>Users</span>
                    <svg class="nav-group-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="nav-group-sub" id="nav-group-users-sub" role="region" aria-label="User types">
                    <?php foreach ($userRoles as $r): ?>
                        <a href="<?= DASHBOARD_BASE ?>/index.php?page=users&role=<?= urlencode($r) ?>" class="nav-item nav-sub <?= $usersPageActive && $currentRoleFilter === $r ? 'active' : '' ?>"><?= htmlspecialchars(roleLabel($r)) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (canAccessCategoriesServices($user['role'])): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=categories" class="nav-item <?= ($page ?? '') === 'categories' ? 'active' : '' ?>">Categories</a>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=services" class="nav-item <?= ($page ?? '') === 'services' ? 'active' : '' ?>">Services</a>
        <?php endif; ?>
        <?php if (canAccessBookings($user['role'])): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=bookings" class="nav-item <?= ($page ?? '') === 'bookings' ? 'active' : '' ?>">Bookings</a>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=customers" class="nav-item <?= ($page ?? '') === 'customers' ? 'active' : '' ?>">Customers</a>
        <?php endif; ?>
        <?php if (canAccessProfessionals($user['role'])): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=professionals" class="nav-item <?= ($page ?? '') === 'professionals' ? 'active' : '' ?>">Professionals</a>
        <?php endif; ?>
        <?php if (in_array($user['role'], ['super_admin', 'team_leader'], true)): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=referrals" class="nav-item <?= ($page ?? '') === 'referrals' ? 'active' : '' ?>">Referrals</a>
        <?php endif; ?>
        <?php if (in_array($user['role'], ['super_admin', 'end_user', 'professional', 'staff'], true)): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=profile" class="nav-item <?= ($page ?? '') === 'profile' ? 'active' : '' ?>">My Profile</a>
        <?php endif; ?>
        <?php if (in_array($user['role'], ['super_admin', 'professional', 'staff', 'team_leader'], true)): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=wallet" class="nav-item <?= ($page ?? '') === 'wallet' ? 'active' : '' ?>">Wallet</a>
        <?php endif; ?>
    </nav>
</aside>
