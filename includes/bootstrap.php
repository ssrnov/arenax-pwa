<?php
declare(strict_types=1);
$config = require __DIR__ . '/../config.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['db']['host'], $config['db']['name'], $config['db']['charset']);
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']), 'samesite'=>'Lax']);
  session_start();
}
function json_out(array $data, int $status=200): never {
  http_response_code($status); header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store'); echo json_encode($data, JSON_UNESCAPED_SLASHES); exit;
}
function require_post(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error'=>'POST required'],405); }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void {
  $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
  if (!is_string($provided) || !hash_equals($_SESSION['csrf'] ?? '', $provided)) json_out(['error'=>'Invalid security token. Refresh and retry.'],419);
}
function app_setting(PDO $pdo, string $key, string $default = ''): string {
  try {
    $q = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
    $q->execute([$key]);
    $value = $q->fetchColumn();
    return $value !== false && $value !== null ? (string)$value : $default;
  } catch (Throwable $e) {
    return $default;
  }
}

function app_settings(PDO $pdo): array {
  $defaults = [
    'app_name' => 'ArenaX',
    'support_email' => '',
    'upi_id' => '',
    'coins_per_rupee' => '10',
    'registration_enabled' => '1',
    'maintenance_mode' => '0',
    'home_notice' => '',
    'referral_coins' => '0',
    'min_topup_rupees' => '50',
  ];
  try {
    $rows = $pdo->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll();
    foreach ($rows as $row) {
      $defaults[(string)$row['setting_key']] = (string)$row['setting_value'];
    }
  } catch (Throwable $e) {
  }
  return $defaults;
}

function current_user(PDO $pdo): ?array {
  if (empty($_SESSION['user_id'])) return null;
  $q=$pdo->prepare('SELECT id,username,email,coins,role,status FROM users WHERE id=? AND status="active"');
  $q->execute([$_SESSION['user_id']]);
  return $q->fetch() ?: null;
}
