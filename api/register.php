<?php
require __DIR__.'/../includes/bootstrap.php'; require_post(); verify_csrf();
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
  $data = $_POST;
}
$username = trim((string)($data['username'] ?? ''));
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = trim((string)($data['password'] ?? ''));
if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
  json_out(['error' => 'Use a 3-40 character username, valid email, and password of at least 10 characters.'], 422);
}
try {
  $q = $pdo->prepare('INSERT INTO users(username,email,password_hash) VALUES(?,?,?)');
  $q->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
  session_regenerate_id(true);
  $_SESSION['user_id'] = (int)$pdo->lastInsertId();
  json_out(['ok' => true, 'csrf' => csrf_token()]);
} catch (PDOException $e) {
  if ($e->getCode() === '23000') json_out(['error' => 'Email is already registered.'], 409);
  throw $e;
}

