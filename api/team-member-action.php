<?php
require __DIR__.'/../includes/bootstrap.php';
require_post();
verify_csrf();
$user = current_user($pdo);
if (!$user) json_out(['error' => 'Sign in required'], 401);
$teamId = (int)($_POST['team_id'] ?? 0);
$membershipId = (int)($_POST['membership_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
if ($teamId < 1 || !in_array($action, ['leave', 'disband', 'remove_member'], true)) {
  json_out(['error' => 'Invalid team action.'], 422);
}
try {
  $pdo->beginTransaction();
  $team = $pdo->prepare('SELECT id, owner_id FROM teams WHERE id=? FOR UPDATE');
  $team->execute([$teamId]);
  $teamRow = $team->fetch();
  if (!$teamRow) throw new RuntimeException('Team not found.');

  if ($action === 'leave') {
    $membership = $pdo->prepare('SELECT id, member_role, status FROM team_members WHERE team_id=? AND user_id=? FOR UPDATE');
    $membership->execute([$teamId, $user['id']]);
    $memberRow = $membership->fetch();
    if (!$memberRow) throw new RuntimeException('You are not a member of this team.');
    if ((int)$teamRow['owner_id'] === (int)$user['id']) {
      throw new RuntimeException('The captain must disband the team instead of leaving.');
    }
    if ($memberRow['status'] !== 'active') {
      throw new RuntimeException('You are not currently active on this team.');
    }
    $update = $pdo->prepare('UPDATE team_members SET status="left" WHERE id=?');
    $update->execute([$memberRow['id']]);
    $pdo->commit();
    json_out(['ok' => true, 'message' => 'You left the team.']);
  }

  if ($action === 'remove_member') {
    if ($membershipId < 1) throw new RuntimeException('Select a roster member to remove.');
    $captainCheck = $pdo->prepare('SELECT id FROM team_members WHERE team_id=? AND user_id=? AND status="active" AND member_role="captain" LIMIT 1 FOR UPDATE');
    $captainCheck->execute([$teamId, $user['id']]);
    if (!$captainCheck->fetch()) throw new RuntimeException('Only the captain can remove members.');
    $targetMembership = $pdo->prepare('SELECT id, user_id, status, member_role FROM team_members WHERE id=? AND team_id=? FOR UPDATE');
    $targetMembership->execute([$membershipId, $teamId]);
    $targetRow = $targetMembership->fetch();
    if (!$targetRow) throw new RuntimeException('Roster member not found.');
    if ((int)$targetRow['user_id'] === (int)$user['id']) throw new RuntimeException('You cannot remove yourself from the team here. Use Leave instead.');
    if ($targetRow['status'] === 'removed') throw new RuntimeException('This member is already removed.');
    $update = $pdo->prepare('UPDATE team_members SET status="removed", member_role="member" WHERE id=?');
    $update->execute([$membershipId]);
    $pdo->commit();
    json_out(['ok' => true, 'message' => 'Member removed from the team.']);
  }

  $membership = $pdo->prepare('SELECT id, member_role, status FROM team_members WHERE team_id=? AND user_id=? FOR UPDATE');
  $membership->execute([$teamId, $user['id']]);
  $memberRow = $membership->fetch();
  if (!$memberRow) throw new RuntimeException('You are not a member of this team.');
  if ((int)$teamRow['owner_id'] !== (int)$user['id']) {
    throw new RuntimeException('Only the captain can disband the team.');
  }
  if ($memberRow['status'] !== 'active') {
    throw new RuntimeException('You must be active on the team to disband it.');
  }
  $remove = $pdo->prepare('UPDATE team_members SET status="removed" WHERE team_id=?');
  $remove->execute([$teamId]);
  $updateTeam = $pdo->prepare('UPDATE teams SET status="disbanded" WHERE id=?');
  $updateTeam->execute([$teamId]);
  $pdo->commit();
  json_out(['ok' => true, 'message' => 'Team disbanded successfully.']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error' => $e->getMessage() ?: 'Unable to update the team.'], 422);
}
