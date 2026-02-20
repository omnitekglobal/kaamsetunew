<?php

requireRole('super_admin', 'admin', 'staff');
$pdo = getDb();

$bookings = [];
$tableExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'bookings'");
    $tableExists = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
if ($tableExists) {
    try {
        $stmt = $pdo->query('SELECT * FROM bookings ORDER BY 1 DESC LIMIT 500');
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $bookings = [];
    }
}

$search = trim($_GET['search'] ?? '');
if ($search !== '' && $tableExists && !empty($bookings)) {
    $cols = array_keys($bookings[0]);
    $orderCol = in_array('created_at', $cols) ? 'created_at' : $cols[0];
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR service LIKE ? OR bookingId LIKE ? ORDER BY $orderCol DESC LIMIT 500");
    $q = "%$search%";
    $stmt->execute([$q, $q, $q, $q, $q]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="page-header">
    <h1>Bookings</h1>
</div>
<?php if (!$tableExists): ?>
    <div class="alert alert-warning">Bookings table does not exist. Create it from your Next.js app or run the migrations.</div>
<?php else: ?>
    <form method="get" class="toolbar">
        <input type="hidden" name="page" value="bookings">
        <input type="text" name="search" placeholder="Search by name, email, phone, service, ID" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
    <div class="card overflow-x">
        <table class="table">
            <thead>
                <tr>
                    <?php
                    $cols = empty($bookings) ? ['bookingId','name','email','phone','service','pincode','language'] : array_keys($bookings[0]);
                    foreach ($cols as $c): ?>
                        <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $c))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <?php foreach ($cols as $c): ?>
                            <td><?= $c === 'bookingId' ? '<code>' . htmlspecialchars($b[$c] ?? '') . '</code>' : htmlspecialchars($b[$c] ?? '-') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($bookings)): ?>
            <p class="p-3 text-muted">No bookings found.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
