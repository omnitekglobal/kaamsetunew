<?php

requireRole('super_admin', 'team_leader');
$pdo = getDb();

$message = '';
$error = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $message = 'Blog created.';
    } elseif ($_GET['msg'] === 'updated') {
        $message = 'Blog updated.';
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM blogs WHERE id = ?')->execute([$id]);
    $message = 'Blog deleted.';
}

$blogs = $pdo->query('SELECT id, title, slug, is_published, published_at FROM blogs ORDER BY published_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1>Blogs</h1>
    <a href="<?= DASHBOARD_BASE ?>/index.php?page=blog_edit" class="btn btn-primary">
        Add Blog
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card overflow-x">
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Slug</th>
            <th>Published</th>
            <th>Published At</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($blogs as $b): ?>
            <tr>
                <td><?= (int) $b['id'] ?></td>
                <td><?= htmlspecialchars($b['title']) ?></td>
                <td><?= htmlspecialchars($b['slug']) ?></td>
                <td><?= ((int) ($b['is_published'] ?? 0)) === 1 ? 'Yes' : 'No' ?></td>
                <td><?= htmlspecialchars($b['published_at']) ?></td>
                <td>
                    <a href="<?= DASHBOARD_BASE ?>/index.php?page=blog_edit&id=<?= (int) $b['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <a href="?page=blogs&delete=<?= (int) $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this blog?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

