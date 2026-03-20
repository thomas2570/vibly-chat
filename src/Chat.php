<?php
namespace ChatApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $userConnections; // Store mappings of username => ConnectionInterface

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
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
            }
            return;
        }

        // Handle private chat messages
        if (isset($data['type']) && $data['type'] === 'chat') {
            $target = $data['target'] ?? '';
            $message = $data['message'] ?? '';
            $sender = $from->username ?? 'Unknown';

            // Send to target if online
            if ($target && isset($this->userConnections[$target])) {
                $targetConn = $this->userConnections[$target];
                $payload = json_encode([
                    'type'     => 'chat',
                    'sender'   => $sender,
                    'message'  => $message
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
        } else {
            echo "Connection {$conn->resourceId} disconnected\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
