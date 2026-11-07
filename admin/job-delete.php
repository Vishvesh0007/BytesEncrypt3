<?php
require_once __DIR__ . '/includes/auth.php';
be_require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
    $pdo = be_db();
    $stmt = $pdo->prepare('DELETE FROM jobs WHERE id = :id');
    $stmt->execute(['id' => $id]);
}
header('Location: jobs.php');
exit;
