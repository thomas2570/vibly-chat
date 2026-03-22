<?php
// Standalone DB Schematics Injector
$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = '4000';
$dbname = 'test';
$username = '2ss5xha7cGNrmKW.root';
$password = 'yf5BJZ5I2yZVwxm7';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/cacert.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false 
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password, $options);
    
    $sqlMessages = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender VARCHAR(50) NOT NULL,
        receiver VARCHAR(50) NOT NULL,
        message LONGTEXT,
        is_image BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlMessages);
    echo "INJECTION SUCCESSFUL: messages table permanently written to TiDB.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
