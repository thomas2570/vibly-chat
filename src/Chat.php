<?php
namespace ChatApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $userConnections; // Store mappings of username => ConnectionInterface
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        
        // Native PDO initialization to bypass Composer scoped global closures
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_CA => file_exists('/etc/ssl/certs/ca-certificates.crt') ? '/etc/ssl/certs/ca-certificates.crt' : dirname(__DIR__) . '/cacert.pem',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
        ];
        $this->pdo = new PDO("mysql:host=gateway01.ap-southeast-1.prod.aws.tidbcloud.com;port=4000;dbname=test", "2ss5xha7cGNrmKW.root", "yf5BJZ5I2yZVwxm7", $options);
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data) return;

        // Handle initial authentication message
        if (isset($data['type']) && $data['type'] === 'auth') {
            $username = $data['username'] ?? '';
            if ($username) {
                // Store connection by username
                $this->userConnections[$username] = $from;
                $from->username = $username;
                echo "User {$username} registered on connection {$from->resourceId}\n";
                $this->broadcastPresence();
            }
            return;
        }

        // Handle read receipts
        if (isset($data['type']) && $data['type'] === 'mark_read') {
            $senderUser = $data['target'] ?? ''; // the user whose messages were just read
            $receiverUser = $from->username ?? '';

            if ($senderUser && $receiverUser) {
                try {
                    $stmt = $this->pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender = ? AND receiver = ? AND is_read = 0");
                    $stmt->execute([$senderUser, $receiverUser]);
                } catch (\PDOException $e) {
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_SSL_CA => file_exists('/etc/ssl/certs/ca-certificates.crt') ? '/etc/ssl/certs/ca-certificates.crt' : dirname(__DIR__) . '/cacert.pem',
                        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
                    ];
                    $this->pdo = new PDO("mysql:host=gateway01.ap-southeast-1.prod.aws.tidbcloud.com;port=4000;dbname=test", "2ss5xha7cGNrmKW.root", "yf5BJZ5I2yZVwxm7", $options);
                    $stmt = $this->pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender = ? AND receiver = ? AND is_read = 0");
                    $stmt->execute([$senderUser, $receiverUser]);
                }

                if (isset($this->userConnections[$senderUser])) {
                    $senderConn = $this->userConnections[$senderUser];
                    $senderConn->send(json_encode([
                        'type' => 'read_receipt',
                        'target' => $receiverUser
                    ]));
                }
            }
            return;
        }

        // Handle private chat messages
        if (isset($data['type']) && $data['type'] === 'chat') {
            $target = $data['target'] ?? '';
            $message = $data['message'] ?? '';
            $sender = $from->username ?? 'Unknown';
            $isImage = isset($data['isImage']) && $data['isImage'] ? 1 : 0;

            // Save payload to Database robustly (Catching "Server has gone away" timeouts if left idle for 8+ hours)
            if ($target) {
                try {
                    $stmt = $this->pdo->prepare("INSERT INTO messages (sender, receiver, message, is_image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$sender, $target, $message, $isImage]);
                } catch (\PDOException $e) {
                    // Critical connection drop fallback
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_SSL_CA => file_exists('/etc/ssl/certs/ca-certificates.crt') ? '/etc/ssl/certs/ca-certificates.crt' : dirname(__DIR__) . '/cacert.pem',
                        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
                    ];
                    $this->pdo = new PDO("mysql:host=gateway01.ap-southeast-1.prod.aws.tidbcloud.com;port=4000;dbname=test", "2ss5xha7cGNrmKW.root", "yf5BJZ5I2yZVwxm7", $options);
                    $stmt = $this->pdo->prepare("INSERT INTO messages (sender, receiver, message, is_image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$sender, $target, $message, $isImage]);
                }
            }

            // Send to target if online
            if ($target && isset($this->userConnections[$target])) {
                $targetConn = $this->userConnections[$target];
                $payload = json_encode([
                    'type'     => 'chat',
                    'sender'   => $sender,
                    'message'  => $message,
                    'isImage'  => $isImage === 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_read'  => 0
                ]);
                $targetConn->send($payload);
                echo "Private message from {$sender} to {$target}\n";
            } else {
                echo "Target {$target} is offline or not found\n";
                // Optionally handle offline messages (save to database) here
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        if (isset($conn->username)) {
            unset($this->userConnections[$conn->username]);
            echo "User {$conn->username} disconnected\n";
            $this->broadcastPresence();
        } else {
            echo "Connection {$conn->resourceId} disconnected\n";
        }
    }

    private function broadcastPresence() {
        $onlineUsers = array_keys($this->userConnections);
        $payload = json_encode([
            'type' => 'presence',
            'users' => $onlineUsers
        ]);
        foreach ($this->clients as $client) {
            $client->send($payload);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
