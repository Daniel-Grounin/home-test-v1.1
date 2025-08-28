<?php
$dotenv = parse_ini_file(__DIR__ . '/../.env');

// Allow direct hits from the React dev server
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'http://localhost:3000' || $origin === 'http://127.0.0.1:3000') {
  header('Access-Control-Allow-Origin: ' . $origin);
  header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

header('Content-Type: application/json');

// ---- load your framework ----
define('a328763fe27bba', true);
require_once __DIR__ . '/../config.php';

// ---- simple helpers (don’t rely on unknown modules) ----
function db(): mysqli {
  static $m;
  if ($m) return $m;
  $m = @new mysqli(MYSQL_DEFAULT_SERVERNAME, MYSQL_DEFAULT_USERNAME, MYSQL_DEFAULT_DB_PASSWORD, MYSQL_DEFAULT_DB_NAME);
  if ($m->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
  }
  $m->set_charset('utf8mb4');
  return $m;
}
function now(): DateTime { return new DateTime('now', new DateTimeZone(date_default_timezone_get())); }

// ---- configurable policy ----
$TTL_MIN        = 10;  // code lifetime
$RESEND_SEC     = 30;  // minimum seconds between emails
$MAX_PER_HOUR   = 4;
$MAX_PER_DAY    = 10;

// ---- Brevo config ----
// Prefer values from your config table if you have get_a_config_value() module
$BREVO_API_KEY       = function_exists('get_a_config_value') ? (get_a_config_value('brevo_api_key') ?: null) : null;
$BREVO_TEMPLATE_ID   = function_exists('get_a_config_value') ? intval(get_a_config_value('brevo_template_id') ?: 0) : 0;
$BREVO_SENDER_EMAIL  = function_exists('get_a_config_value') ? (get_a_config_value('brevo_sender_email') ?: null) : null;
$BREVO_SENDER_NAME   = function_exists('get_a_config_value') ? (get_a_config_value('brevo_sender_name') ?: 'AssafMedia') : 'AssafMedia';

// Fallbacks (edit if you prefer hardcoding here)
if (!$BREVO_API_KEY)      $BREVO_API_KEY = $dotenv['BREVO_API_KEY'];
if (!$BREVO_TEMPLATE_ID)  $BREVO_TEMPLATE_ID = 2; // the template id you created
if (!$BREVO_SENDER_EMAIL) $BREVO_SENDER_EMAIL = 'dani.grunin@gmail.com';
if (!$BREVO_SENDER_NAME)  $BREVO_SENDER_NAME = 'Assaf Media';

// ---- read body ----
$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');
$hp    = trim($_POST['hp']    ?? ''); // honeypot

if ($hp !== '') { http_response_code(400); echo json_encode(['error' => 'Bad request']); exit; }
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400); echo json_encode(['error' => 'Invalid name or email.']); exit;
}

// ---- fetch user ----
$mysqli = db();
$stmt = $mysqli->prepare('SELECT id, otp_last_sent_at, otp_hourly_count, otp_hourly_reset_at, otp_daily_count, otp_daily_reset_at FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $name);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) { http_response_code(404); echo json_encode(['error' => 'User not found.']); exit; }

$uid = (int)$u['id'];
$now = now();

// ---- rate limits ----
$lastSent = $u['otp_last_sent_at'] ? new DateTime($u['otp_last_sent_at']) : null;
if ($lastSent && ($now->getTimestamp() - $lastSent->getTimestamp()) < $RESEND_SEC) {
  http_response_code(429); echo json_encode(['error' => 'Please wait before requesting again.']); exit;
}

$hourCount = (int)$u['otp_hourly_count'];
$dayCount  = (int)$u['otp_daily_count'];
$hourReset = $u['otp_hourly_reset_at'] ? new DateTime($u['otp_hourly_reset_at']) : null;
$dayReset  = $u['otp_daily_reset_at']  ? new DateTime($u['otp_daily_reset_at'])  : null;

if (!$hourReset || $now > $hourReset) { $hourReset = (clone $now)->modify('+1 hour'); $hourCount = 0; }
if (!$dayReset  || $now > $dayReset ) { $dayReset  = (clone $now)->setTime(23,59,59); $dayCount  = 0; }

if ($hourCount >= $MAX_PER_HOUR) { http_response_code(429); echo json_encode(['error' => 'Too many requests this hour.']); exit; }
if ($dayCount  >= $MAX_PER_DAY ) { http_response_code(429); echo json_encode(['error' => 'Daily limit reached.']); exit; }

// ---- generate, hash & save OTP ----
$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$hash = password_hash($code, PASSWORD_DEFAULT);
$expiresAt   = (clone $now)->modify("+{$TTL_MIN} minutes")->format('Y-m-d H:i:s');
$lastSentStr = $now->format('Y-m-d H:i:s');
$hourResetStr = $hourReset->format('Y-m-d H:i:s');
$dayResetStr  = $dayReset->format('Y-m-d H:i:s');

$hourCount++; $dayCount++;

$upd = $mysqli->prepare('UPDATE users SET otp_hash=?, otp_expires_at=?, otp_last_sent_at=?, otp_hourly_count=?, otp_hourly_reset_at=?, otp_daily_count=?, otp_daily_reset_at=?, otp_attempts=0 WHERE id=?');
$upd->bind_param('sssisssi', $hash, $expiresAt, $lastSentStr, $hourCount, $hourResetStr, $dayCount, $dayResetStr, $uid);
if (!$upd->execute()) {
  http_response_code(500); echo json_encode(['error' => 'Could not save OTP']); exit;
}
$upd->close();

// ---- send with Brevo transactional template ----
// Template must include a variable like {{params.otp}} somewhere in the content
$payload = [
  'to'        => [[ 'email' => $email, 'name' => $name ]],
  'templateId'=> $BREVO_TEMPLATE_ID,
  'sender'    => [ 'email' => $BREVO_SENDER_EMAIL, 'name' => $BREVO_SENDER_NAME ],
  'params'    => [ 'otp' => $code, 'username' => $name, 'ttlMinutes' => $TTL_MIN ]
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => [
    'Content-Type: application/json',
    'api-key: ' . $BREVO_API_KEY,
  ],
  CURLOPT_POSTFIELDS     => json_encode($payload),
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false || $http >= 400) {
  // Optional: revert counts when email fails
  $mysqli->query("UPDATE users SET otp_hourly_count=GREATEST(otp_hourly_count-1,0), otp_daily_count=GREATEST(otp_daily_count-1,0) WHERE id={$uid}");
  http_response_code(502);
  echo json_encode(['error' => 'Email provider error', 'details' => $err ?: $resp]);
  exit;
}

echo json_encode(['ok' => true, 'message' => 'OTP sent: ' . $code]);