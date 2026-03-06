<?php

requireRole('super_admin', 'team_leader');
$pdo = getDb();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$error = '';
$blog = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT id, title, slug, excerpt, body, cover_image_url, published_at, is_published FROM blogs WHERE id = ?');
    $stmt->execute([$id]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$blog) {
        $error = 'Blog not found.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $cover_image_url = trim($_POST['cover_image_url'] ?? '');
    $published_at = trim($_POST['published_at'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '') {
        $error = 'Title is required.';
    } else {
        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title)) ?: 'blog';
        }

        if ($published_at === '') {
            $published_at = date('Y-m-d H:i:s');
        } else {
            $published_at = str_replace('T', ' ', $published_at);
        }

        if ($id) {
            $stmt = $pdo->prepare('SELECT id FROM blogs WHERE id = ?');
            $stmt->execute([$id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                $error = 'Blog not found.';
            } else {
                $stmt = $pdo->prepare('SELECT id FROM blogs WHERE slug = ? AND id != ?');
                $stmt->execute([$slug, $id]);
                if ($stmt->fetch()) {
                    $error = 'Slug already in use.';
                } else {
                    $pdo->prepare('UPDATE blogs SET title = ?, slug = ?, excerpt = ?, body = ?, cover_image_url = ?, published_at = ?, is_published = ? WHERE id = ?')
                        ->execute([
                            $title,
                            $slug,
                            $excerpt !== '' ? $excerpt : null,
                            $body !== '' ? $body : null,
                            $cover_image_url !== '' ? $cover_image_url : null,
                            $published_at,
                            $is_published,
                            $id,
                        ]);
                    $message = 'Blog updated.';
                }
            }
        } else {
            $stmt = $pdo->prepare('SELECT id FROM blogs WHERE slug = ?');
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $error = 'Slug already in use.';
            } else {
                $pdo->prepare('INSERT INTO blogs (title, slug, excerpt, body, cover_image_url, published_at, is_published) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([
                        $title,
                        $slug,
                        $excerpt !== '' ? $excerpt : null,
                        $body !== '' ? $body : null,
                        $cover_image_url !== '' ? $cover_image_url : null,
                        $published_at,
                        $is_published,
                    ]);
                $newId = (int) $pdo->lastInsertId();
                $message = 'Blog created.';
                $blog = [
                    'id' => $newId,
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $excerpt,
                    'body' => $body,
                    'cover_image_url' => $cover_image_url,
                    'published_at' => $published_at,
                    'is_published' => $is_published,
                ];
            }
        }
    }

    // If there was an error, repopulate $blog from submitted values
    $blog = [
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $excerpt,
        'body' => $body,
        'cover_image_url' => $cover_image_url,
        'published_at' => $published_at,
        'is_published' => $is_published,
    ];
}

// Prepare value for datetime-local input
$publishedValue = '';
if (!empty($blog['published_at'])) {
    $ts = strtotime($blog['published_at']);
    if ($ts) {
        $publishedValue = date('Y-m-d\TH:i', $ts);
    }
}

?>

<div class="page-header">
    <h1><?= $blog && !empty($blog['id']) ? 'Edit Blog' : 'Add Blog' ?></h1>
    <a href="<?= DASHBOARD_BASE ?>/index.php?page=blogs" class="btn btn-outline">Back to list</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card">
    <form method="post">
        <input type="hidden" name="id" value="<?= isset($blog['id']) ? (int) $blog['id'] : 0 ?>">
        <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($blog['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="<?= htmlspecialchars($blog['slug'] ?? '') ?>">
            <small class="text-muted">Used in URL, e.g. /blogs/your-slug-here. Leave blank to auto-generate.</small>
        </div>
        <div class="form-group">
            <label>Excerpt</label>
            <textarea name="excerpt" rows="2"><?= htmlspecialchars($blog['excerpt'] ?? '') ?></textarea>
            <small class="text-muted">Short summary shown in blog list.</small>
        </div>
        <div class="form-group">
            <label>Cover image URL</label>
            <input type="text" name="cover_image_url" value="<?= htmlspecialchars($blog['cover_image_url'] ?? '') ?>">
            <small class="text-muted">Absolute or relative URL to blog image.</small>
        </div>
        <div class="form-group">
            <label>Published at</label>
            <input type="datetime-local" name="published_at" value="<?= htmlspecialchars($publishedValue) ?>">
        </div>
        <div class="form-group">
            <label>Body (HTML)</label>
            <textarea name="body" id="blog-body" rows="12"><?= htmlspecialchars($blog['body'] ?? '') ?></textarea>
            <small class="text-muted">Use the editor to format content. Saved as HTML.</small>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_published" value="1" <?= ($blog['is_published'] ?? 1) ? 'checked' : '' ?>> Published</label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $blog && !empty($blog['id']) ? 'Update' : 'Create' ?></button>
            <a href="<?= DASHBOARD_BASE ?>/index.php?page=blogs" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
(function () {
    var el = document.getElementById('blog-body');
    if (!el || typeof ClassicEditor === 'undefined') return;
    ClassicEditor
        .create(el)
        .catch(function (error) {
            console.error(error);
        });
})();
</script>

