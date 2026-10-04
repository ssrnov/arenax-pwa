<?php
require __DIR__.'/../includes/bootstrap.php'; require_post(); verify_csrf();
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
  $data = $_POST;
}
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = trim((string)($data['password'] ?? ''));
$q = $pdo->prepare('SELECT id,password_hash,status FROM users WHERE email=?');
$q->execute([$email]);
$u = $q->fetch();
if (!$u || $u['status'] !== 'active' || !password_verify($password, $u['password_hash'])) {
  json_out(['error' => 'Invalid login details.'], 401);
}
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$u['id'];
json_out(['ok' => true, 'csrf' => csrf_token()]);

