<?php
require __DIR__.'/../includes/bootstrap.php';
$user = current_user($pdo);
$q = $pdo->prepare("SELECT t.id, t.title, t.game, t.mode, t.description, t.entry_coins, t.prize_coins, t.max_teams, t.starts_at, t.status,
    (SELECT COUNT(*) FROM tournament_entries e WHERE e.tournament_id = t.id AND e.status IN ('registered','checked_in')) AS entries,
    CASE WHEN ? IS NOT NULL THEN EXISTS (
        SELECT 1 FROM tournament_entries e WHERE e.tournament_id = t.id AND e.user_id = ? AND e.status IN ('registered','checked_in')
    ) ELSE 0 END AS joined
    FROM tournaments t
    WHERE t.status IN ('draft','upcoming','live','completed')
    ORDER BY t.starts_at ASC
    LIMIT 100");
$q->execute([$user['id'] ?? null, $user['id'] ?? null]);
$rows = $q->fetchAll();
json_out(['tournaments' => $rows]);
