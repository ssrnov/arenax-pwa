<?php
require __DIR__.'/../includes/bootstrap.php';
require_post();
verify_csrf();
$user = current_user($pdo);
if (!$user) json_out(['error' => 'Sign in required'], 401);
$name = trim((string)($_POST['team_name'] ?? ''));
$game = trim((string)($_POST['game'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
if (strlen($name) < 3 || strlen($name) > 60 || strlen($game) < 2 || strlen($game) > 60) {
  json_out(['error' => 'Team name (3-60 chars) and game (2-60 chars) are required.'], 422);
}
if (strlen($description) > 500) json_out(['error' => 'Team description must be 500 chars or shorter.'], 422);
try {
  $pdo->beginTransaction();
  $existingMembership = $pdo->prepare('SELECT team_id FROM team_members WHERE user_id=? AND status="active" LIMIT 1 FOR UPDATE');
  $existingMembership->execute([$user['id']]);
  if ($existingMembership->fetch()) {
    throw new RuntimeException('You are already active on another team. Leave or remove the current team first.');
  }
  $duplicate = $pdo->prepare('SELECT id FROM teams WHERE owner_id=? AND LOWER(name)=LOWER(?) LIMIT 1 FOR UPDATE');
  $duplicate->execute([$user['id'], $name]);
  if ($duplicate->fetch()) {
    throw new RuntimeException('You already have a team with that name.');
  }
  $code = strtoupper(bin2hex(random_bytes(6)));
  $q = $pdo->prepare('INSERT INTO teams(owner_id, name, game, invite_code, description) VALUES(?,?,?,?,?)');
  $q->execute([$user['id'], $name, $game, $code, $description ?: null]);
  $teamId = (int)$pdo->lastInsertId();
  $m = $pdo->prepare('INSERT INTO team_members(team_id, user_id, member_role, status) VALUES(?,?,?,?)');
  $m->execute([$teamId, $user['id'], 'captain', 'active']);
  $pdo->commit();
  json_out(['ok' => true, 'message' => 'Team created successfully.', 'team_id' => $teamId, 'invite_code' => $code]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error' => $e->getMessage() ?: 'Unable to create team right now.'], 422);
}
