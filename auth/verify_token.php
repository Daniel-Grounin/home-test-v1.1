<?php
function require_auth_or_redirect() {
  $token = $_COOKIE['auth_token'] ?? '';
  if ($token === '') {
    header('Location: http://localhost:3000'); // your React login or PHP page
    exit;
  }

  // Validate token exists & not expired
  $mysqli = new mysqli(MYSQL_DEFAULT_SERVERNAME, MYSQL_DEFAULT_USERNAME, MYSQL_DEFAULT_DB_PASSWORD, MYSQL_DEFAULT_DB_NAME);
  if ($mysqli->connect_errno) { http_response_code(500); die('DB error'); }
  $stmt = $mysqli->prepare('SELECT username FROM users WHERE api_token=? AND api_token_expires_at > NOW() LIMIT 1');
  $stmt->bind_param('s', $token);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$user) {
    // optionally clear the cookie
    setcookie('auth_token', '', ['expires'=>time()-3600,'path'=>'/HOME-TEST','httponly'=>true, 'samesite'=>'Lax']);
    header('Location: http://localhost:3000');
    exit;
  }

  // make username available
  $GLOBALS['current_user'] = $user['username'];
}