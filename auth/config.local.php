<?php 
$dotenv = parse_ini_file(__DIR__ . '/../.env');
// ==== EDIT THESE ====
$BREVO_API_KEY = $dotenv['BREVO_API_KEY'];
$BREVO_FROM_EMAIL = "dani.grunin@gmail.com"; // must be a validated Brevo sender
$BREVO_FROM_NAME  = "Assaf Media";
$BREVO_TEMPLATE_ID = 2; // Transactional template ID. Body should include {{ params.code }}.

// OTP policy
$OTP_TTL_SECONDS = 10 * 60; // 10 minutes
$OTP_DIGITS = 6;

// Rate limits
$OTP_MIN_INTERVAL_SECONDS = 30;   // 30s between sends
$OTP_MAX_PER_HOUR = 4;
$OTP_MAX_PER_DAY  = 10;

// Storage
$AUTH_LOG_DIR = __DIR__ . "/logs";          // make sure it's writable
$STATE_FILE   = $AUTH_LOG_DIR . "/otp-state.json";

// CORS (since React dev runs on :3000 and you’re calling absolute URLs)
$CORS_ORIGIN = "http://localhost:3000";

// ====== bootstrap ======
if (!is_dir($AUTH_LOG_DIR)) { @mkdir($AUTH_LOG_DIR, 0777, true); }
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $AUTH_LOG_DIR . '/php_errors.log');

function respond_json($code, $data) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function cors_headers_if_needed($origin) {
  if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
  }
}

function load_state($file) {
  if (!file_exists($file)) return [];
  $raw = file_get_contents($file);
  if (!$raw) return [];
  $json = json_decode($raw, true);
  return is_array($json) ? $json : [];
}

function save_state($file, $arr) {
  file_put_contents($file, json_encode($arr, JSON_PRETTY_PRINT));
}