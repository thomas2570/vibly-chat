<?php
/**
 * Cookie-based authentication for Vercel serverless PHP.
 * PHP sessions are file-based and DO NOT persist across serverless function
 * invocations (each request may get a different container/filesystem).
 * This module uses HMAC-signed cookies instead — fully stateless and reliable.
 */

define('AUTH_SECRET', 'vibly_secret_hmac_key_2026_xT9pQ');
define('AUTH_COOKIE', 'vibly_auth');
define('AUTH_TTL', 60 * 60 * 24 * 7); // 7 days

/**
 * Log in a user: set a signed cookie with the username.
 */
function auth_login(string $username): void {
    $expires = time() + AUTH_TTL;
    $payload = base64_encode(json_encode(['u' => $username, 'exp' => $expires]));
    $sig = hash_hmac('sha256', $payload, AUTH_SECRET);
    $token = $payload . '.' . $sig;
    setcookie(AUTH_COOKIE, $token, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Get the currently logged-in username, or null if not authenticated.
 */
function auth_user(): ?string {
    if (empty($_COOKIE[AUTH_COOKIE])) return null;
    $parts = explode('.', $_COOKIE[AUTH_COOKIE], 2);
    if (count($parts) !== 2) return null;
    [$payload, $sig] = $parts;
    $expected = hash_hmac('sha256', $payload, AUTH_SECRET);
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode(base64_decode($payload), true);
    if (!$data || !isset($data['u'], $data['exp'])) return null;
    if ($data['exp'] < time()) return null;
    return $data['u'];
}

/**
 * Require the user to be logged in. Redirect to /login if not.
 */
function auth_require(): string {
    $user = auth_user();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    return $user;
}

/**
 * Log out: clear the cookie.
 */
function auth_logout(): void {
    setcookie(AUTH_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
?>
