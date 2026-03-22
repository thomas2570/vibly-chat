<?php
// Force maximum error reporting so Render Logs capture crashes
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ChatApp\Chat;

try {
    // Explicitly bind to 127.0.0.1 to avoid Apache IPv6 [::1] mismatches inside Docker
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Chat()
            )
        ),
        8080,
        '127.0.0.1' 
    );

    echo "✅ SUCCESS: Vibly WebSocket Server booted normally and bound securely to 127.0.0.1:8080\n";
    $server->run();
} catch (\Exception $e) {
    echo "🚨 FATAL EXCEPTION: " . $e->getMessage() . "\n";
} catch (\Throwable $t) {
    echo "🚨 FATAL SYSTEM ERROR: " . $t->getMessage() . "\n";
}
?>
