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
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, $options);
} catch(PDOException $e) {
    die("ERROR: Could not connect to database. " . $e->getMessage() . "<br>Did you run setup.php or start XAMPP MySQL?");
}
?>
