<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=project;charset=utf8mb4', 'root', '');
// Check FK
$stmt = $pdo->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA='project' AND TABLE_NAME='reset_password_request' AND REFERENCED_TABLE_NAME IS NOT NULL");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo implode(' | ', $row) . PHP_EOL;
}
echo "---\n";
// Check indexes
$stmt2 = $pdo->query("SHOW INDEX FROM reset_password_request");
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Key_name'] . ' | ' . $row['Column_name'] . PHP_EOL;
}
