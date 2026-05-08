<?php
$host = "127.0.0.1";
$port = 3306;
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Dropping database 'project'...\n";
    $pdo->exec("DROP DATABASE IF EXISTS project");

    echo "Creating database 'project'...\n";
    $pdo->exec("CREATE DATABASE project");

    echo "Using database 'project'...\n";
    $pdo->exec("USE project");

    echo "Reading SQL file...\n";
    $sql = file_get_contents(__DIR__ . "/db_dump.sql");

    echo "Importing SQL statements...\n";
    $pdo->exec($sql);

    echo "Import completed successfully!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
