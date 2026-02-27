<?php

$inputFile  = "staff.csv";
$outputFile = "staff_users_insert.sql";

if (!file_exists($inputFile)) {
    die("CSV file not found.");
}

$file = fopen($inputFile, "r");
if (!$file) {
    die("Unable to open CSV file.");
}

$output = fopen($outputFile, "w");

fwrite($output, "INSERT INTO `users` 
(`name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`)
VALUES\n");

$rowNumber = 0;
$values = [];

// Skip header (PHP 8.4 compatible)
fgetcsv($file, 0, ",", '"', "\\");

while (($row = fgetcsv($file, 0, ",", '"', "\\")) !== false) {

    $rowNumber++;

    $name  = addslashes(trim($row[0] ?? ''));
    $phone = addslashes(trim($row[1] ?? ''));

    $email = "staff{$rowNumber}@example.com";
    $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    $values[] = "('$name', '$email', '$password', '$phone', 'staff', 1, NOW(), NOW())";
}

fwrite($output, implode(",\n", $values));
fwrite($output, ";\n");

fclose($file);
fclose($output);

echo "✅ SQL file generated successfully: $outputFile";