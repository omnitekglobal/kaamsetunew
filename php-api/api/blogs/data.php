<?php

/**
 * Blog repository helpers backed by MySQL.
 *
 * Expected `blogs` table structure (MySQL):
 *
 * CREATE TABLE blogs (
 *   id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   slug VARCHAR(191) NOT NULL UNIQUE,
 *   title VARCHAR(255) NOT NULL,
 *   excerpt TEXT NULL,
 *   body MEDIUMTEXT NOT NULL,
 *   cover_image_url VARCHAR(512) NULL,
 *   published_at DATETIME NOT NULL,
 *   created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
 *   updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */

/**
 * Fetch all published blogs ordered by newest first.
 *
 * @return array<int, array<string, mixed>>
 */
function blogFetchAll(): array {
    $pdo = getDb();
    $sql = 'SELECT id, slug, title, excerpt, body, cover_image_url, published_at
            FROM blogs
            WHERE is_published = 1
            ORDER BY published_at DESC';
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        if (isset($row['published_at']) && $row['published_at'] !== null) {
            $row['published_at'] = date(DATE_ATOM, strtotime((string) $row['published_at']));
        }
    }

    return $rows;
}

/**
 * Fetch a single blog by numeric id or slug.
 *
 * @param string $slugOrId
 * @return array<string, mixed>|null
 */
function blogFetchOne(string $slugOrId): ?array {
    $pdo = getDb();

    if (ctype_digit($slugOrId)) {
        $stmt = $pdo->prepare('SELECT id, slug, title, excerpt, body, cover_image_url, published_at FROM blogs WHERE id = ?');
        $stmt->execute([(int) $slugOrId]);
    } else {
        $stmt = $pdo->prepare('SELECT id, slug, title, excerpt, body, cover_image_url, published_at FROM blogs WHERE slug = ?');
        $stmt->execute([$slugOrId]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $row['id'] = (int) $row['id'];
    if (isset($row['published_at']) && $row['published_at'] !== null) {
        $row['published_at'] = date(DATE_ATOM, strtotime((string) $row['published_at']));
    }

    return $row;
}

