<?php
$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = '4000';
$dbname = 'test';
$username = '2ss5xha7cGNrmKW.root';
$password = 'yf5BJZ5I2yZVwxm7';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/cacert.pem'
];
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, $options);
    $pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0");
    echo "Successfully updated the TiDB messages table with is_read column.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column is_read already exists! Safe to proceed.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
