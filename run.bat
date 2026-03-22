@echo off
echo Starting PHP WebSocket Server on ws://localhost:8080...
start php server.php

echo Starting PHP Web Server for Chat UI on http://localhost:8000...
echo Please open http://localhost:8000 in your browser.
php -d extension_dir="C:\Program Files\php-8.5.4\ext" -d extension=pdo_mysql -S localhost:8000
