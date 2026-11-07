<?php
require_once __DIR__ . '/security.php';

function be_require_login(): void
{
    be_start_session();
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    // Idle timeout: 30 minutes
    $idleLimit = 1800;
    if (!empty($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > $idleLimit) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_active'] = time();
}
