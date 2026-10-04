<?php
require __DIR__.'/../includes/bootstrap.php';
$user = current_user($pdo);
if (!$user) json_out(['error' => 'Sign in required'], 401);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $q = $pdo->prepare('SELECT id, subject, message, status, created_at FROM support_tickets WHERE user_id=? ORDER BY created_at DESC LIMIT 20');
  $q->execute([$user['id']]);
  json_out(['tickets' => $q->fetchAll()]);
}
require_post();
verify_csrf();
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
if (strlen($subject) < 4 || strlen($subject) > 180 || strlen($message) < 10 || strlen($message) > 5000) {
  json_out(['error' => 'Subject (4-180) and message (10-5000 chars) required.'], 422);
}
$q = $pdo->prepare('INSERT INTO support_tickets(user_id,subject,message) VALUES(?,?,?)');
$q->execute([$user['id'], $subject, $message]);
json_out(['ok' => true, 'ticket_id' => (int)$pdo->lastInsertId()]);
