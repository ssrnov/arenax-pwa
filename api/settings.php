<?php
require __DIR__.'/../includes/bootstrap.php';
json_out(['settings' => app_settings($pdo), 'csrf' => csrf_token()]);
