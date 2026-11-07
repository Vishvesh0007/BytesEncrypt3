<?php
require_once __DIR__ . '/includes/auth.php';
be_require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
    $pdo = be_db();
    $stmt = $pdo->prepare('SELECT featured_image FROM posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $img = $stmt->fetchColumn();

    $del = $pdo->prepare('DELETE FROM posts WHERE id = :id');
    $del->execute(['id' => $id]);

    if ($img) {
        $path = __DIR__ . '/' . $img;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
header('Location: dashboard.php');
exit;
