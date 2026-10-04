<?php
// Run once from CLI: php setup-admin.php admin@example.com 'Strong-Unique-Password' 'Admin Name'
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if($argc<4){fwrite(STDERR,"Usage: php setup-admin.php email password username\n");exit(1);}
require __DIR__.'/includes/bootstrap.php';$email=filter_var($argv[1],FILTER_VALIDATE_EMAIL);$password=$argv[2];$username=substr(trim($argv[3]),0,40);if(!$email||strlen($password)<14||!$username){fwrite(STDERR,"Valid email, username and password (14+ chars) required.\n");exit(1);}$q=$pdo->prepare("INSERT INTO users(username,email,password_hash,role) VALUES(?,?,?,'super_admin')");$q->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);fwrite(STDOUT,"Super admin created. Delete setup-admin.php now.\n");
