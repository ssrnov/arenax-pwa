<?php
require __DIR__.'/../includes/bootstrap.php';
require_post();
verify_csrf();
$user = current_user($pdo);
if (!$user) json_out(['error' => 'Sign in required'], 401);
$teamId = (int)($_POST['team_id'] ?? 0);
$target = trim((string)($_POST['user_identifier'] ?? ''));
if ($teamId < 1 || $target === '') json_out(['error' => 'Invalid team or player identifier.'], 422);
try {
  $pdo->beginTransaction();
  $teamQuery = $pdo->prepare('SELECT id, owner_id FROM teams WHERE id=? FOR UPDATE');
  $teamQuery->execute([$teamId]);
  $team = $teamQuery->fetch();
  if (!$team) throw new RuntimeException('Team not found.');
  $ownership = $pdo->prepare('SELECT id FROM team_members WHERE team_id=? AND user_id=? AND status="active" AND member_role IN ("captain","owner")');
  $ownership->execute([$teamId, $user['id']]);
  if (!$ownership->fetch()) throw new RuntimeException('Only team captains can send invites.');
  $targetUser = $pdo->prepare('SELECT id, username, email FROM users WHERE username=? OR email=? LIMIT 1');
  $targetUser->execute([$target, strtolower($target)]);
  $targetRow = $targetUser->fetch();
  if (!$targetRow) throw new RuntimeException('Player not found.');
  if ((int)$targetRow['id'] === (int)$user['id']) throw new RuntimeException('You are already on this team.');
  $existing = $pdo->prepare('SELECT id, status FROM team_members WHERE team_id=? AND user_id=? LIMIT 1 FOR UPDATE');
  $existing->execute([$teamId, $targetRow['id']]);
  $existingRow = $existing->fetch();
  if ($existingRow) {
    if ($existingRow['status'] === 'active') throw new RuntimeException('This player is already on the team.');
    if ($existingRow['status'] === 'invited') throw new RuntimeException('This player already has a pending invite for the team.');
    if ($existingRow['status'] === 'left' || $existingRow['status'] === 'removed') {
      $reinvite = $pdo->prepare('UPDATE team_members SET status="invited", member_role="member", joined_at=CURRENT_TIMESTAMP WHERE id=?');
      $reinvite->execute([$existingRow['id']]);
      $pdo->commit();
      json_out(['ok' => true, 'message' => 'Previous membership was re-opened as a new invite.']);
    }
  }
  $invite = $pdo->prepare('INSERT INTO team_members(team_id, user_id, member_role, status) VALUES(?,?,?,?)');
  $invite->execute([$teamId, $targetRow['id'], 'member', 'invited']);
  $pdo->commit();
  json_out(['ok' => true, 'message' => 'Invite sent successfully.']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error' => $e->getMessage() ?: 'Unable to send the invite.'], 422);
}
