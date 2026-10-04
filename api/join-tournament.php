<?php
require __DIR__.'/../includes/bootstrap.php';
require_post();
verify_csrf();
$u = current_user($pdo);
if (!$u) json_out(['error' => 'Sign in required'], 401);
$tid = (int)($_POST['tournament_id'] ?? 0);
$teamId = (int)($_POST['team_id'] ?? 0);
$team = trim((string)($_POST['team_name'] ?? ''));
if ($tid < 1 || ($team !== '' && (strlen($team) < 2 || strlen($team) > 100))) {
  json_out(['error' => 'Invalid tournament or team name'], 422);
}
try {
  $pdo->beginTransaction();
  $selectedTeamName = null;
  if ($teamId > 0) {
    $teamQuery = $pdo->prepare('SELECT t.id, t.name FROM teams t JOIN team_members tm ON tm.team_id = t.id WHERE t.id=? AND tm.user_id=? AND tm.status="active" LIMIT 1 FOR UPDATE');
    $teamQuery->execute([$teamId, $u['id']]);
    $selectedTeam = $teamQuery->fetch();
    if (!$selectedTeam) throw new RuntimeException('You are not an active member of that team.');
    $selectedTeamName = $selectedTeam['name'];
    if ($team !== '' && strtolower(trim($team)) !== strtolower($selectedTeamName)) {
      throw new RuntimeException('Selected team name does not match the chosen team.');
    }
  }
  $q = $pdo->prepare('SELECT * FROM tournaments WHERE id=? FOR UPDATE');
  $q->execute([$tid]);
  $t = $q->fetch();
  if (!$t || $t['status'] !== 'upcoming' || strtotime($t['starts_at']) <= time()) {
    throw new RuntimeException('Registration is not open.');
  }
  $q = $pdo->prepare("SELECT id FROM tournament_entries WHERE tournament_id=? AND user_id=? AND status IN ('registered','checked_in') LIMIT 1 FOR UPDATE");
  $q->execute([$tid, $u['id']]);
  if ($q->fetch()) {
    throw new RuntimeException('You are already registered for this tournament.');
  }
  $q = $pdo->prepare("SELECT COUNT(*) FROM tournament_entries WHERE tournament_id=? AND status IN ('registered','checked_in')");
  $q->execute([$tid]);
  if ((int)$q->fetchColumn() >= (int)$t['max_teams']) {
    throw new RuntimeException('Tournament is full.');
  }
  $q = $pdo->prepare('SELECT coins,status FROM users WHERE id=? FOR UPDATE');
  $q->execute([$u['id']]);
  $player = $q->fetch();
  if (!$player || $player['status'] !== 'active') {
    throw new RuntimeException('Account unavailable.');
  }
  if ((int)$player['coins'] < (int)$t['entry_coins']) {
    throw new RuntimeException('Insufficient coins.');
  }
  $ref = 'entry-'.$tid.'-user-'.$u['id'];
  $entryTeamName = $selectedTeamName ?? ($team !== '' ? $team : null);
  $q = $pdo->prepare('INSERT INTO tournament_entries(tournament_id,user_id,team_name) VALUES(?,?,?)');
  $q->execute([$tid, $u['id'], $entryTeamName]);
  if ((int)$t['entry_coins'] > 0) {
    $q = $pdo->prepare('INSERT INTO coin_ledger(user_id,delta,kind,reference,note) VALUES(?,?,?, ?,?)');
    $q->execute([$u['id'], -(int)$t['entry_coins'], 'entry', $ref, 'Tournament entry']);
    $q = $pdo->prepare('UPDATE users SET coins=coins-? WHERE id=?');
    $q->execute([$t['entry_coins'], $u['id']]);
  }
  $pdo->commit();
  json_out(['ok' => true, 'message' => 'Tournament joined.']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['error' => $e instanceof PDOException ? 'Already registered or request failed.' : $e->getMessage()], 422);
}

