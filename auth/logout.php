<?php
define('a328763fe27bba', true);

require_once __DIR__ . '/../config.php';

// Allow cookie credentials (use the same CORS as your verify-otp)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:3000' || $origin === 'http://127.0.0.1:3000') {
  header('Access-Control-Allow-Origin', $origin);
  header('Vary: Origin');
  header('Access-Control-Allow-Credentials', 'true');
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$token = $_COOKIE['auth_token'] ?? '';

// Best-effort: remove token from DB
if ($token !== '') {
  $mysqli = @new mysqli(MYSQL_DEFAULT_SERVERNAME, MYSQL_DEFAULT_USERNAME, MYSQL_DEFAULT_DB_PASSWORD, MYSQL_DEFAULT_DB_NAME);
  if (!$mysqli->connect_errno) {
    $stmt = $mysqli->prepare('UPDATE users SET api_token=NULL, api_token_expires_at=NULL WHERE api_token=?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->close();
  }
}

// Expire the cookie (adjust path to your app root)
setcookie('auth_token', '', [
  'expires'  => time() - 3600,
  'path'     => '/HOME-TEST-v1.1',
  'httponly' => true,
  'samesite' => 'Lax',
  'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);

// If it’s an XHR, return JSON; if it’s a navigation, redirect.
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (str_contains($accept, 'application/json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
  header('Content-Type: application/json');
  echo json_encode(['ok' => true]);
} else {
  header('Location: /HOME-TEST-v1.1/index.php'); // or your React login
}
exit;