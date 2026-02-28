<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$user = requireLogin();

$page = $_GET['page'] ?? 'home';
$allowedPages = ['home'];
if (canAccessUsers($user['role'])) $allowedPages[] = 'users';
if (canAccessCategoriesServices($user['role'])) {
    $allowedPages[] = 'categories';
    $allowedPages[] = 'services';
}
if (canAccessBookings($user['role'])) {
    $allowedPages[] = 'bookings';
    $allowedPages[] = 'customers';
}
if (canAccessProfessionals($user['role'])) $allowedPages[] = 'professionals';
if (in_array($user['role'], ['super_admin', 'team_leader'], true)) {
    $allowedPages[] = 'referrals';
}
if (in_array($user['role'], ['super_admin', 'team_leader', 'staff'], true)) {
    $allowedPages[] = 'professional_requests';
}
if (in_array($user['role'], ['super_admin', 'end_user', 'professional', 'staff'], true)) $allowedPages[] = 'profile';
if (in_array($user['role'], ['super_admin', 'professional', 'staff', 'team_leader'], true)) $allowedPages[] = 'wallet';

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!is_file($pageFile)) {
    $pageFile = __DIR__ . '/pages/home.php';
}

$pageTitle = ucfirst(str_replace('_', ' ', $page));
require __DIR__ . '/layout/header.php';
require __DIR__ . '/layout/sidebar.php';
?>
<main class="main-content">
    <?php require $pageFile; ?>
</main>
<?php require __DIR__ . '/layout/footer.php';
