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
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=users" class="nav-item <?= ($page ?? '') === 'users' ? 'active' : '' ?>">Users</a>
        <?php endif; ?>
        <?php if (canAccessCategoriesServices($user['role'])): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=categories" class="nav-item <?= ($page ?? '') === 'categories' ? 'active' : '' ?>">Categories</a>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=services" class="nav-item <?= ($page ?? '') === 'services' ? 'active' : '' ?>">Services</a>
        <?php endif; ?>
        <?php if (canAccessBookings($user['role'])): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=bookings" class="nav-item <?= ($page ?? '') === 'bookings' ? 'active' : '' ?>">Bookings</a>
        <?php endif; ?>
        <?php if (canAccessProfessionals($user['role'])): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=professionals" class="nav-item <?= ($page ?? '') === 'professionals' ? 'active' : '' ?>">Professionals</a>
        <?php endif; ?>
        <?php if (in_array($user['role'], ['end_user', 'professional', 'staff'], true)): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=profile" class="nav-item <?= ($page ?? '') === 'profile' ? 'active' : '' ?>">My Profile</a>
        <?php endif; ?>
    </nav>
</aside>
