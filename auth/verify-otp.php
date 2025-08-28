<?php
// CORS for local dev
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:3000' || $origin === 'http://127.0.0.1:3000') {
  header('Access-Control-Allow-Origin: ' . $origin);
  header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

header('Content-Type: application/json');

define('a328763fe27bba', true);
require_once __DIR__ . '/../config.php';

// ---- read body (JSON OR form) ----
$body = [];
$ct = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
if (strpos($ct, 'application/json') !== false) {
  $raw = file_get_contents('php://input');
  if ($raw !== false && $raw !== '') $body = json_decode($raw, true) ?: [];
}
$name  = trim($body['name'] ?? $_POST['name'] ?? '');
$otp   = trim($body['otp']  ?? $_POST['otp']  ?? $body['code'] ?? $_POST['code'] ?? ''); // be forgiving

if ($name === '' || !preg_match('/^\d{6}$/', $otp)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid input']);
  exit;
}

// ---- DB ----
$mysqli = new mysqli(MYSQL_DEFAULT_SERVERNAME, MYSQL_DEFAULT_USERNAME, MYSQL_DEFAULT_DB_PASSWORD, MYSQL_DEFAULT_DB_NAME);
if ($mysqli->connect_errno) { http_response_code(500); echo json_encode(['error'=>'DB connection failed']); exit; }
$mysqli->set_charset('utf8mb4');

$stmt = $mysqli->prepare('SELECT id, otp_hash, otp_expires_at, otp_attempts FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $name);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) { http_response_code(404); echo json_encode(['error'=>'User not found']); exit; }

// ---- validation & rate limiting ----
$now = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
$maxAttempts = 10;

if (!empty($u['otp_attempts']) && (int)$u['otp_attempts'] >= $maxAttempts) {
  http_response_code(429);
  echo json_encode(['error'=>'Too many attempts. Try again later.']);
  exit;
}

if (empty($u['otp_hash']) || empty($u['otp_expires_at'])) {
  http_response_code(400);
  echo json_encode(['error'=>'No pending code. Request a new one.']);
  exit;
}

$expires = new DateTime($u['otp_expires_at']);
if ($now > $expires) {
  $mysqli->query("UPDATE users SET otp_hash=NULL, otp_expires_at=NULL WHERE id=".(int)$u['id']);
  http_response_code(400);
  echo json_encode(['error'=>'Code expired.']);
  exit;
}

// ---- verify code ----
$ok = password_verify($otp, $u['otp_hash']);
if (!$ok) {
  $mysqli->query("UPDATE users SET otp_attempts = COALESCE(otp_attempts,0)+1 WHERE id=".(int)$u['id']);
  http_response_code(400);
  echo json_encode(['error'=>'Wrong code']);
  exit;
}

// ---- success: issue session/API token and clear OTP ----
$token  = bin2hex(random_bytes(24));
$expStr = $now->modify('+7 days')->format('Y-m-d H:i:s');

$upd = $mysqli->prepare('UPDATE users
  SET api_token=?, api_token_expires_at=?, last_login_at=NOW(),
      otp_hash=NULL, otp_expires_at=NULL, otp_attempts=0
  WHERE id=?');
$upd->bind_param('ssi', $token, $expStr, $u['id']);
$upd->execute();
$upd->close();

// CORS for dev (must be specific origin when using credentials)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:3000' || $origin === 'http://127.0.0.1:3000') {
  header('Access-Control-Allow-Origin: ' . $origin);
  header('Vary: Origin');
  header('Access-Control-Allow-Credentials: true'); // <-- add this
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// ... after you compute $token and $expStr (Y-m-d H:i:s) ...
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'); // true on HTTPS
setcookie(
  'auth_token',
  $token,
  [
    'expires'  => strtotime($expStr),
    'path'     => '/HOME-TEST',   // or '/' if you want the whole host
    'secure'   => $secure,        // must be true if you later use SameSite=None
    'httponly' => true,           // JS cannot read it (good)
    'samesite' => 'Lax',          // cookie sent on top-level navigations
  ]
);

echo json_encode([
  'ok' => true,
  'username' => $name,
  'token' => $token,
  'expiresAt' => $expStr
]);