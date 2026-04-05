<?php
$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = '4000';
$dbname = 'test';
$username = '2ss5xha7cGNrmKW.root';
$password = 'yf5BJZ5I2yZVwxm7';

try {
    // Point directly to the secure Linux Server SSL Certificate path inside the Docker container
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => file_exists('/etc/ssl/certs/ca-certificates.crt') ? '/etc/ssl/certs/ca-certificates.crt' : __DIR__ . '/cacert.pem',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, $options);
    
    // Auto-migrate database (Runs successfully on Render's Linux PHP environment)
    try { @$pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE messages ADD COLUMN is_edited TINYINT(1) DEFAULT 0"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE chatbot ADD COLUMN email VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE chatbot ADD COLUMN full_name VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE chatbot ADD COLUMN gender VARCHAR(50) DEFAULT NULL"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE chatbot ADD COLUMN profile_image VARCHAR(255) DEFAULT 'default.png'"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE chatbot MODIFY COLUMN profile_image LONGTEXT"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE chatbot ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL"); } catch(PDOException $e) {}
    try { @$pdo->exec("ALTER TABLE chatbot ADD COLUMN reset_token_expires DATETIME DEFAULT NULL"); } catch(PDOException $e) {}
    
} catch(PDOException $e) {
    die("ERROR: Could not connect to database. " . $e->getMessage() . "<br>Did you run setup.php or start XAMPP MySQL?");
}
?>
