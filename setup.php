<?php
$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = '4000';
$dbname = 'test';
$username = '2ss5xha7cGNrmKW.root';
$password = 'yf5BJZ5I2yZVwxm7';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        1012 => __DIR__ . '/cacert.pem', // PDO::MYSQL_ATTR_SSL_CA
        1014 => false // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT (ignore mismatch)
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password, $options);
    
    echo "Connected to TiDB Serverless Cloud Database successfully.\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS chatbot (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'chatbot' created successfully in cloud!\n";
    echo "Setup complete! You can now Register or Login on Vibly.\n";

} catch(PDOException $e) {
    die("ERROR: Could not complete setup. " . $e->getMessage() . "\nPlease ensure your MySQL credentials are correct.");
}
?>
