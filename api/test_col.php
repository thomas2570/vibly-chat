<?php
require 'db.php';
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("ALTER TABLE chatbot ADD COLUMN email VARCHAR(255) DEFAULT NULL, ADD COLUMN full_name VARCHAR(255) DEFAULT NULL, ADD COLUMN gender VARCHAR(50) DEFAULT NULL, ADD COLUMN profile_image VARCHAR(255) DEFAULT 'default.png', ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL, ADD COLUMN reset_token_expires DATETIME DEFAULT NULL");
    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    echo "Error adding columns: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->query('DESCRIBE chatbot');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in chatbot:\n";
    foreach($columns as $col) {
        echo "- " . $col['Field'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error describing: " . $e->getMessage() . "\n";
}
?>
