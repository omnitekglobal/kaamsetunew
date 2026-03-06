<?php

require_once __DIR__ . '/data.php';

// Public list of blogs, newest first.
// Frontend expects either:
// - res.data.items (wrapped by jsonSuccess), or
// - a plain array. We use the existing jsonSuccess helper.

$items = blogFetchAll();

jsonSuccess(['items' => $items]);

