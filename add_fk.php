<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=project;charset=utf8mb4', 'root', '');

// Check if FK already exists
$stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA='project' AND TABLE_NAME='reset_password_request'
    AND CONSTRAINT_TYPE='FOREIGN KEY'");
$existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (in_array('FK_7CE748AA76ED395', $existing)) {
    echo "FK already exists.\n";
} else {
    $pdo->exec("ALTER TABLE reset_password_request
        ADD CONSTRAINT FK_7CE748AA76ED395
        FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE");
    echo "FK added successfully.\n";
}

// Make selector nullable if it isn't already
$pdo->exec("ALTER TABLE reset_password_request MODIFY selector VARCHAR(20) NULL");
echo "selector column set to nullable.\n";
