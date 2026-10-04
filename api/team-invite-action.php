<?php
require __DIR__.'/../includes/bootstrap.php';
require_post();
verify_csrf();
$user = current_user($pdo);
if (!$user) json_out(['error' => 'Sign in required'], 401);
$membershipId = (int)($_POST['membership_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
if ($membershipId < 1 || !in_array($action, ['accept', 'reject'], true)) {
  json_out(['error' => 'Invalid invite response.'], 422);
}
try {
  $pdo->beginTransaction();
  $q = $pdo->prepare('SELECT team_id, user_id, status FROM team_members WHERE id=? FOR UPDATE');
  $q->execute([$membershipId]);
  $row = $q->fetch();
  if (!$row || (int)$row['user_id'] !== (int)$user['id']) throw new RuntimeException('Invite not found.');
  if ($row['status'] !== 'invited') throw new RuntimeException('This invite is no longer active.');
  if ($action === 'accept') {
    $update = $pdo->prepare('UPDATE team_members SET status="active", member_role="member" WHERE id=?');
    $update->execute([$membershipId]);
    $pdo->commit();
    json_out(['ok' => true, 'message' => 'Invite accepted.']);
  }
  $update = $pdo->prepare('UPDATE team_members SET status="left" WHERE id=?');
  $update->execute([$membershipId]);
  $pdo->commit();
  json_out(['ok' => true, 'message' => 'Invite rejected.']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error' => $e->getMessage() ?: 'Unable to process invite response.'], 422);
}
