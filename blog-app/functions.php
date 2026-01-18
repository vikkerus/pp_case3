<?php
session_start();
require_once 'config/database.php';

function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Ошибка подключения: " . $e->getMessage());
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPost($id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPosts($limit = 50, $offset = 0, $user_id = null) {
    $conn = getConnection();
    
    if ($user_id) {
        // Посты пользователей, на которых подписан
        $sql = "SELECT DISTINCT p.*, u.username FROM posts p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN subscriptions s ON p.user_id = s.subscribed_to_id 
                WHERE (s.subscriber_id = ? OR p.visibility = 'public') 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $limit, $offset]);
    } else {
        // Только публичные посты
        $sql = "SELECT p.*, u.username FROM posts p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.visibility = 'public' 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$limit, $offset]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPostTags($post_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT t.name FROM tags t 
                            JOIN post_tags pt ON t.id = pt.tag_id 
                            WHERE pt.post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

function getComments($post_id) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT c.*, u.username FROM comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.post_id = ? 
                            ORDER BY c.created_at");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isSubscribed($subscriber_id, $subscribed_to_id) {
    if ($subscriber_id == $subscribed_to_id) return false;
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM subscriptions 
                            WHERE subscriber_id = ? AND subscribed_to_id = ?");
    $stmt->execute([$subscriber_id, $subscribed_to_id]);
    return $stmt->rowCount() > 0;
}
?>