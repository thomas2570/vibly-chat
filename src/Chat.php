<?php
namespace ChatApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

// Bootstrap DB configuration so $pdo becomes available structurally
require dirname(__DIR__) . '/db.php';

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $userConnections; // Store mappings of username => ConnectionInterface
    protected $pdo;

    public function __construct() {
        global $pdo;
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->pdo = $pdo;
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
                    require dirname(__DIR__) . '/db.php';
                    global $pdo;
                    $this->pdo = $pdo;
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
                    'isImage'  => $isImage === 1
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
