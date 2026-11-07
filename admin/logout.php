<?php
require_once __DIR__ . '/includes/security.php';
be_start_session();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
