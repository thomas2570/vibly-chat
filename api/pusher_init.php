<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Use environment variables for security on Vercel
$app_id = getenv('PUSHER_APP_ID');
$app_key = getenv('PUSHER_APP_KEY');
$app_secret = getenv('PUSHER_APP_SECRET');
$app_cluster = getenv('PUSHER_APP_CLUSTER');

if (!$app_id || !$app_key || !$app_secret || !$app_cluster) {
    // If not set, use placeholders for local development so it doesn't crash
    $app_id = '2137230';
    $app_key = '9ac7b2ea2db44e457c4e';
    $app_secret = '5730eb5e4f5fd9648346';
    $app_cluster = 'ap2';
}

$pusher = new Pusher\Pusher(
    $app_key,
    $app_secret,
    $app_id,
    [
        'cluster' => $app_cluster,
        'useTLS' => true
    ]
);
?>
