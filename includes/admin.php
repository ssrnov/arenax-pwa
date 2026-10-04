<?php
require_once __DIR__.'/bootstrap.php';
function require_admin(array $roles=[]): array { global $pdo; if(empty($_SESSION['user_id'])) { header('Location: login.php'); exit; } $s=$pdo->prepare('SELECT id,username,email,role,status FROM users WHERE id=?');$s->execute([$_SESSION['user_id']]);$a=$s->fetch(); if(!$a || $a['status']!=='active' || !in_array($a['role'],['super_admin','finance','tournament','support','moderator','analyst'],true) || ($roles && !in_array($a['role'],$roles,true) && $a['role']!=='super_admin')) { http_response_code(403);exit('Access denied'); } return $a; }
function h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function audit(PDO $pdo,int $admin,string $action,string $type,?int $id,array $details=[]):void{$s=$pdo->prepare('INSERT INTO audit_logs(admin_id,action,entity_type,entity_id,details,ip_address) VALUES(?,?,?,?,?,?)');$s->execute([$admin,$action,$type,$id,json_encode($details),$_SERVER['REMOTE_ADDR']??null]);}
