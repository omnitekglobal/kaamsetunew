<header class="dashboard-header">
    <div class="header-brand">
        <a href="<?= DASHBOARD_BASE ?>/index.php">KaamSetu</a>
    </div>
    <div class="header-user">
        <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
        <span class="user-role"><?= htmlspecialchars(roleLabel($user['role'])) ?></span>
        <a href="<?= DASHBOARD_BASE ?>/logout.php" class="btn btn-sm btn-outline">Logout</a>
    </div>
</header>
<aside class="sidebar">
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
        <?php if (in_array($user['role'], ['end_user', 'professional'], true)): ?>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=profile" class="nav-item <?= ($page ?? '') === 'profile' ? 'active' : '' ?>">My Profile</a>
        <?php endif; ?>
    </nav>
</aside>
