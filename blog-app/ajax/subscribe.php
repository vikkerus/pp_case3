<?php
require_once '../functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо войти в систему']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $subscriber_id = $_SESSION['user_id'];
    $subscribed_to_id = intval($_POST['user_id']);
    
    if ($subscriber_id == $subscribed_to_id) {
        echo json_encode(['success' => false, 'message' => 'Нельзя подписаться на самого себя']);
        exit();
    }
    
    $conn = getConnection();
    
    if ($_POST['action'] == 'subscribe') {
        // Подписка
        $stmt = $conn->prepare("INSERT INTO subscriptions (subscriber_id, subscribed_to_id) VALUES (?, ?)");
        $stmt->execute([$subscriber_id, $subscribed_to_id]);
        
        echo json_encode([
            'success' => true, 
            'subscribed' => true,
            'message' => 'Вы успешно подписались на пользователя'
        ]);
    } else {
        // Отписка
        $stmt = $conn->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND subscribed_to_id = ?");
        $stmt->execute([$subscriber_id, $subscribed_to_id]);
        
        echo json_encode([
            'success' => true, 
            'subscribed' => false,
            'message' => 'Вы отписались от пользователя'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный запрос']);
}
?>