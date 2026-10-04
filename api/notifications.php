<?php
require __DIR__.'/../includes/bootstrap.php';
$user = current_user($pdo);
if (!$user) json_out(['error' => 'Sign in required'], 401);
$q = $pdo->prepare('SELECT id, title, message, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20');
$q->execute([$user['id']]);
json_out(['notifications' => $q->fetchAll()]);
