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
if (canAccessBookings($user['role'])) $allowedPages[] = 'bookings';
if (canAccessProfessionals($user['role'])) $allowedPages[] = 'professionals';
if (in_array($user['role'], ['end_user', 'professional'], true)) $allowedPages[] = 'profile';

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
