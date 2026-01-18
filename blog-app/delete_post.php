<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = intval($_POST['post_id']);
    
    // Проверяем, что пост принадлежит пользователю
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post && $post['user_id'] == $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
    }
}

header('Location: index.php');
exit();
?>