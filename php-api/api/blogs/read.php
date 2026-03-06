<?php

require_once __DIR__ . '/data.php';

$slugOrId = $_GET['_id'] ?? null;
if ($slugOrId === null || $slugOrId === '') {
    jsonError('Blog identifier required', 400);
}

$post = blogFetchOne((string) $slugOrId);
if ($post === null) {
    // 404 with a JSON body containing a message field as requested.
    jsonError('Blog not found', 404);
}

jsonSuccess($post);

