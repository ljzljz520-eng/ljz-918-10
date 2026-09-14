<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/audit.php';
if (is_post()) {
    csrf_check();
    logout();
}
header('Location: login.php');
exit;
