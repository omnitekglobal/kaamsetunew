<?php
/**
 * Pagination bar. Set these before including:
 *   $paginationTotal   - total number of items
 *   $paginationPage    - current page (1-based)
 *   $paginationPerPage - items per page
 *   $paginationQueryParams - array of query params to preserve (e.g. ['page' => 'users', 'search' => 'x'])
 */
if (!isset($paginationTotal) || $paginationTotal <= 0) return;
$total = (int) $paginationTotal;
$current = max(1, (int) ($paginationPage ?? 1));
$perPage = max(1, (int) ($paginationPerPage ?? 20));
$params = $paginationQueryParams ?? [];
$totalPages = (int) ceil($total / $perPage);
if ($totalPages <= 1) return;

$baseUrl = DASHBOARD_BASE . '/index.php?';
$link = function ($p) use ($baseUrl, $params) {
    $q = array_merge($params, ['p' => $p]);
    return $baseUrl . http_build_query($q);
};
$from = ($current - 1) * $perPage + 1;
$to = min($current * $perPage, $total);
?>
<div class="pagination-wrap">
    <span class="pagination-info">Showing <?= $from ?>–<?= $to ?> of <?= $total ?></span>
    <nav class="pagination" aria-label="Pagination">
        <?php if ($current > 1): ?>
            <a href="<?= htmlspecialchars($link($current - 1)) ?>" class="pagination-btn" aria-label="Previous">← Prev</a>
        <?php else: ?>
            <span class="pagination-btn disabled" aria-disabled="true">← Prev</span>
        <?php endif; ?>
        <span class="pagination-pages">
            <?php
            $range = 2;
            $start = max(1, $current - $range);
            $end = min($totalPages, $current + $range);
            if ($start > 1): ?>
                <a href="<?= htmlspecialchars($link(1)) ?>" class="pagination-num">1</a>
                <?php if ($start > 2): ?><span class="pagination-ellipsis">…</span><?php endif;
            endif;
            for ($i = $start; $i <= $end; $i++):
                if ($i === $current): ?>
                    <span class="pagination-num current" aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($link($i)) ?>" class="pagination-num"><?= $i ?></a>
                <?php endif;
            endfor;
            if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                <a href="<?= htmlspecialchars($link($totalPages)) ?>" class="pagination-num"><?= $totalPages ?></a>
            <?php endif; ?>
        </span>
        <?php if ($current < $totalPages): ?>
            <a href="<?= htmlspecialchars($link($current + 1)) ?>" class="pagination-btn" aria-label="Next">Next →</a>
        <?php else: ?>
            <span class="pagination-btn disabled" aria-disabled="true">Next →</span>
        <?php endif; ?>
    </nav>
</div>
