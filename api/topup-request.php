<?php
require __DIR__.'/../includes/bootstrap.php'; require_post(); verify_csrf();
$user=current_user($pdo); if(!$user) json_out(['error'=>'Please sign in first.'],401);
$amount=(int)($_POST['amount_paise']??0); $coins=(int)($_POST['coins']??0); $utr=trim((string)($_POST['utr']??''));
// Demo packages: keep server-side pricing authoritative; never trust client-provided coin amounts.
$packages=[10000=>500,20000=>1100,5000=>200];
if(!isset($packages[$amount]) || $coins!==$packages[$amount] || !preg_match('/^[A-Za-z0-9-]{8,100}$/',$utr)) json_out(['error'=>'Invalid package or UTR/reference number.'],422);
if(empty($_FILES['screenshot']) || $_FILES['screenshot']['error']!==UPLOAD_ERR_OK) json_out(['error'=>'Payment screenshot is required.'],422);
$f=$_FILES['screenshot']; if($f['size']>5*1024*1024) json_out(['error'=>'Screenshot must be 5 MB or smaller.'],422);
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null; if(!$ext) json_out(['error'=>'Upload a JPG, PNG, or WebP image.'],422);
$dir=__DIR__.'/../private_uploads'; if(!is_dir($dir)) mkdir($dir,0750,true); $filename=bin2hex(random_bytes(20)).'.'.$ext; if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$filename)) json_out(['error'=>'Could not store screenshot.'],500);
try { $q=$pdo->prepare('INSERT INTO topup_requests(user_id,amount_paise,coins,utr,screenshot_path) VALUES(?,?,?,?,?)'); $q->execute([$user['id'],$amount,$coins,$utr,$filename]); json_out(['ok'=>true,'message'=>'Request submitted. Coins will be credited only after admin verification.']); }
catch(PDOException $e) { @unlink($dir.'/'.$filename); if($e->getCode()==='23000') json_out(['error'=>'This UTR has already been submitted.'],409); throw $e; }
