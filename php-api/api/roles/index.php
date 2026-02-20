<?php

// Public list of roles (for signup/admin UIs)
jsonSuccess([
    'items' => array_map(function ($r) {
        return ['id' => $r, 'name' => $r, 'label' => ucfirst(str_replace('_', ' ', $r))];
    }, ROLES),
]);
