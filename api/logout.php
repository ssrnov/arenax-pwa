<?php
require __DIR__.'/../includes/bootstrap.php'; require_post(); verify_csrf(); $_SESSION=[]; session_destroy(); json_out(['ok'=>true]);
