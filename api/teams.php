<?php
require __DIR__.'/../includes/bootstrap.php';
$user = current_user($pdo);
if (!$user) json_out(['error' => 'Sign in required'], 401);
$teamsQuery = $pdo->prepare('SELECT t.id, t.name, t.game, t.description, t.invite_code, t.owner_id, (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id AND tm.status = "active") AS members FROM teams t JOIN team_members tm ON tm.team_id = t.id WHERE tm.user_id = ? AND tm.status = "active" GROUP BY t.id ORDER BY t.created_at DESC');
$teamsQuery->execute([$user['id']]);
$teams = $teamsQuery->fetchAll(PDO::FETCH_ASSOC);
$teamIds = array_map('intval', array_column($teams, 'id'));
$roster = [];
if ($teamIds) {
  $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
  $rosterQuery = $pdo->prepare("SELECT tm.id AS membership_id, tm.team_id, tm.user_id, u.username, tm.member_role, tm.status FROM team_members tm JOIN users u ON u.id = tm.user_id WHERE tm.team_id IN ($placeholders) AND tm.status IN ('active', 'invited') ORDER BY tm.team_id, FIELD(tm.status, 'active', 'invited'), FIELD(tm.member_role, 'captain', 'owner', 'member'), u.username ASC");
  $rosterQuery->execute($teamIds);
  while ($row = $rosterQuery->fetch(PDO::FETCH_ASSOC)) {
    $roster[(int)$row['team_id']][] = $row;
  }
  foreach ($teams as &$team) {
    $team['roster'] = $roster[(int)$team['id']] ?? [];
  }
  unset($team);
}
$invites = $pdo->prepare('SELECT tm.id AS membership_id, t.id AS team_id, t.name, t.game, tm.status, tm.created_at FROM team_members tm JOIN teams t ON t.id = tm.team_id WHERE tm.user_id = ? AND tm.status = "invited" ORDER BY tm.created_at DESC');
$invites->execute([$user['id']]);
$teamIds = array_map('intval', array_column($teams, 'id'));
if (!$teamIds) {
  json_out(['teams' => [], 'invites' => $invites->fetchAll(), 'roster' => []]);
}
$teamSummary = $pdo->prepare('SELECT team_id, COUNT(*) AS member_count FROM team_members WHERE team_id IN (' . implode(',', array_fill(0, count($teamIds), '?')) . ') AND status = "active" GROUP BY team_id');
$teamSummary->execute($teamIds);
$memberCounts = [];
while ($row = $teamSummary->fetch(PDO::FETCH_ASSOC)) {
  $memberCounts[(int)$row['team_id']] = (int)$row['member_count'];
}
foreach ($teams as &$team) {
  $team['members'] = $memberCounts[(int)$team['id']] ?? 0;
}
unset($team);
json_out(['teams' => $teams, 'invites' => $invites->fetchAll(), 'roster' => $roster]);
