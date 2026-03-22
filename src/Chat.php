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

            // We will send to target AFTER we save to DB, so we get the accurate ID!
            // Save payload to Database robustly in the background
            $msgId = null;
            if ($target) {
                try {
                    $stmt = $this->pdo->prepare("INSERT INTO messages (sender, receiver, message, is_image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$sender, $target, $message, $isImage]);
                    $msgId = $this->pdo->lastInsertId();
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
                    $msgId = $this->pdo->lastInsertId();
                }

                // Send to Target
                if (isset($this->userConnections[$target])) {
                    $targetConn = $this->userConnections[$target];
                    $payload = json_encode([
                        'type'     => 'chat',
                        'id'       => $msgId,
                        'sender'   => $sender,
                        'message'  => $message,
                        'isImage'  => $isImage === 1,
                        'unix_time'=> time()
                    ]);
                    $targetConn->send($payload);
                    echo "Private message from {$sender} to {$target} (ID: {$msgId})\n";
                }
                
                // Send Ack to Sender
                $from->send(json_encode([
                    'type'     => 'ack_message',
                    'tempId'   => $data['tempId'] ?? null,
                    'id'       => $msgId
                ]));
            }
            return;
        }

        // Handle Editing Message
        if (isset($data['type']) && $data['type'] === 'edit') {
            $msgId = $data['id'] ?? 0;
            $newMessage = $data['message'] ?? '';
            $sender = $from->username ?? '';
            $target = $data['target'] ?? '';

            if ($msgId && $newMessage && $sender) {
                try {
                    $stmt = $this->pdo->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ? AND sender = ?");
                    $stmt->execute([$newMessage, $msgId, $sender]);
                } catch (\PDOException $e) {
                    $this->pdo = new PDO("mysql:host=gateway01.ap-southeast-1.prod.aws.tidbcloud.com;port=4000;dbname=test", "2ss5xha7cGNrmKW.root", "yf5BJZ5I2yZVwxm7", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_SSL_CA => dirname(__DIR__) . '/cacert.pem', PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true]);
                    $stmt = $this->pdo->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ? AND sender = ?");
                    $stmt->execute([$newMessage, $msgId, $sender]);
                }

                $editPayload = json_encode([
                    'type' => 'edit',
                    'id' => $msgId,
                    'message' => $newMessage,
                    'target' => $target
                ]);

                // Broadcast to Target
                if ($target && isset($this->userConnections[$target])) {
                    $this->userConnections[$target]->send($editPayload);
                }
                // Broadcast confirmation to Sender
                $from->send($editPayload);
            }
            return;
        }

        // Handle Deleting Message
        if (isset($data['type']) && $data['type'] === 'delete') {
            $msgId = $data['id'] ?? 0;
            $sender = $from->username ?? '';
            $target = $data['target'] ?? '';

            if ($msgId && $sender) {
                try {
                    $stmt = $this->pdo->prepare("DELETE FROM messages WHERE id = ? AND sender = ?");
                    $stmt->execute([$msgId, $sender]);
                } catch (\PDOException $e) {
                    $this->pdo = new PDO("mysql:host=gateway01.ap-southeast-1.prod.aws.tidbcloud.com;port=4000;dbname=test", "2ss5xha7cGNrmKW.root", "yf5BJZ5I2yZVwxm7", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_SSL_CA => dirname(__DIR__) . '/cacert.pem', PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true]);
                    $stmt = $this->pdo->prepare("DELETE FROM messages WHERE id = ? AND sender = ?");
                    $stmt->execute([$msgId, $sender]);
                }

                $deletePayload = json_encode([
                    'type' => 'delete',
                    'id' => $msgId,
                    'target' => $target
                ]);

                // Broadcast to Target
                if ($target && isset($this->userConnections[$target])) {
                    $this->userConnections[$target]->send($deletePayload);
                }
                // Broadcast confirmation to Sender
                $from->send($deletePayload);
            }
            return;
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
