<?php
require __DIR__.'/../includes/bootstrap.php';
$user = current_user($pdo);
if (!$user) {
  json_out(['user' => null, 'csrf' => csrf_token(), 'ledger' => [], 'topups' => []]);
}
$ledger = $pdo->prepare('SELECT id, delta, kind, reference, note, created_at FROM coin_ledger WHERE user_id=? ORDER BY created_at DESC LIMIT 20');
$ledger->execute([$user['id']]);
$topups = $pdo->prepare('SELECT id, amount_paise, coins, utr, status, admin_note, created_at, reviewed_at FROM topup_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 20');
$topups->execute([$user['id']]);
json_out(['user' => $user, 'csrf' => csrf_token(), 'ledger' => $ledger->fetchAll(), 'topups' => $topups->fetchAll()]);
