#!/usr/bin/env php
<?php

/**
 * Seed mock categories and services. Each service is linked to its category.
 * Run from php-api dir: php seed.php
 */

$baseDir = __DIR__;
require_once $baseDir . '/config/database.php';

// Load .env
$envPath = $baseDir . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val, " \t\"'");
        putenv(trim($key) . '=' . trim($val, " \t\"'"));
    }
}

$pdo = getDb();

$categories = [
    ['name' => 'Home Services', 'slug' => 'home-services', 'description' => 'Cleaning, plumbing, electrical, and general home maintenance.', 'sort_order' => 10],
    ['name' => 'Beauty & Wellness', 'slug' => 'beauty-wellness', 'description' => 'Salon, spa, and personal care services.', 'sort_order' => 20],
    ['name' => 'Repairs & Maintenance', 'slug' => 'repairs-maintenance', 'description' => 'Appliance repair, AC, and technical fixes.', 'sort_order' => 30],
    ['name' => 'Events & Catering', 'slug' => 'events-catering', 'description' => 'Event planning, catering, and photography.', 'sort_order' => 40],
    ['name' => 'Education & Tuition', 'slug' => 'education-tuition', 'description' => 'Tutoring and coaching for students and professionals.', 'sort_order' => 50],
];

$servicesByCategorySlug = [
    'home-services' => [
        ['name' => 'House Cleaning', 'slug' => 'house-cleaning', 'description' => 'Full house deep cleaning and regular upkeep.'],
        ['name' => 'Plumbing', 'slug' => 'plumbing', 'description' => 'Pipe repairs, taps, and drainage solutions.'],
        ['name' => 'Electrical', 'slug' => 'electrical', 'description' => 'Wiring, switches, and electrical fixes.'],
        ['name' => 'Carpentry', 'slug' => 'carpentry', 'description' => 'Furniture repair, shelves, and woodwork.'],
        ['name' => 'Pest Control', 'slug' => 'pest-control', 'description' => 'Rodent and insect treatment for homes.'],
    ],
    'beauty-wellness' => [
        ['name' => 'Haircut & Styling', 'slug' => 'haircut-styling', 'description' => 'Men and women haircuts and styling.'],
        ['name' => 'Massage', 'slug' => 'massage', 'description' => 'Relaxation and therapeutic massage.'],
        ['name' => 'Facial', 'slug' => 'facial', 'description' => 'Cleansing, exfoliation, and skin care.'],
        ['name' => 'Manicure & Pedicure', 'slug' => 'manicure-pedicure', 'description' => 'Nail care and grooming.'],
        ['name' => 'Bridal Makeup', 'slug' => 'bridal-makeup', 'description' => 'Bridal makeup and hairstyling.'],
    ],
    'repairs-maintenance' => [
        ['name' => 'AC Repair', 'slug' => 'ac-repair', 'description' => 'AC servicing and repair.'],
        ['name' => 'Washing Machine Repair', 'slug' => 'washing-machine-repair', 'description' => 'Washing machine and dryer repair.'],
        ['name' => 'Refrigerator Repair', 'slug' => 'refrigerator-repair', 'description' => 'Fridge and freezer repair.'],
        ['name' => 'TV & Set-top Box', 'slug' => 'tv-set-top-box', 'description' => 'TV and set-top box installation and repair.'],
        ['name' => 'Laptop Repair', 'slug' => 'laptop-repair', 'description' => 'Laptop hardware and software repair.'],
    ],
    'events-catering' => [
        ['name' => 'Event Decoration', 'slug' => 'event-decoration', 'description' => 'Birthday, wedding, and party decoration.'],
        ['name' => 'Catering', 'slug' => 'catering', 'description' => 'Food and beverage for events.'],
        ['name' => 'Photography', 'slug' => 'photography', 'description' => 'Event and portrait photography.'],
        ['name' => 'Videography', 'slug' => 'videography', 'description' => 'Event video and editing.'],
        ['name' => 'DJ & Music', 'slug' => 'dj-music', 'description' => 'DJ and live music for events.'],
    ],
    'education-tuition' => [
        ['name' => 'School Tuition', 'slug' => 'school-tuition', 'description' => 'Math, science, and language tuition for school students.'],
        ['name' => 'Competitive Exam Coaching', 'slug' => 'competitive-exam-coaching', 'description' => 'JEE, NEET, UPSC, and other exam preparation.'],
        ['name' => 'Music Lessons', 'slug' => 'music-lessons', 'description' => 'Piano, guitar, and vocal lessons.'],
        ['name' => 'Spoken English', 'slug' => 'spoken-english', 'description' => 'Spoken English and communication skills.'],
        ['name' => 'Art & Craft', 'slug' => 'art-craft', 'description' => 'Drawing, painting, and craft classes.'],
    ],
];

echo "Seeding categories and services...\n";

$categoryIds = [];
$insCat = $pdo->prepare('INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)');
$insSvc = $pdo->prepare('INSERT INTO services (category_id, name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)');

foreach ($categories as $i => $c) {
    $insCat->execute([$c['name'], $c['slug'], $c['description'], $c['sort_order']]);
    $categoryIds[$c['slug']] = (int) $pdo->lastInsertId();
    echo "  Category: {$c['name']} (id {$categoryIds[$c['slug']]})\n";
}

foreach ($servicesByCategorySlug as $catSlug => $services) {
    $categoryId = $categoryIds[$catSlug] ?? null;
    if ($categoryId === null) {
        echo "  Skip services for unknown category: $catSlug\n";
        continue;
    }
    foreach ($services as $j => $s) {
        $insSvc->execute([$categoryId, $s['name'], $s['slug'], $s['description'], $j]);
        $sid = $pdo->lastInsertId();
        echo "  Service: {$s['name']} → {$catSlug} (id $sid)\n";
    }
}

echo "Done. Categories: " . count($categories) . ", Services: " . array_sum(array_map('count', $servicesByCategorySlug)) . "\n";
