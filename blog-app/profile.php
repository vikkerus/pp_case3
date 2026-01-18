<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);

// Получение постов пользователя
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение подписчиков и подписок
$stmt = $conn->prepare("SELECT u.id, u.username FROM users u 
                        JOIN subscriptions s ON u.id = s.subscriber_id 
                        WHERE s.subscribed_to_id = ?");
$stmt->execute([$user_id]);
$followers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT u.id, u.username FROM users u 
                        JOIN subscriptions s ON u.id = s.subscribed_to_id 
                        WHERE s.subscriber_id = ?");
$stmt->execute([$user_id]);
$following = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">БлогПлатформа</a>
            <div class="nav-links">
                <span>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="index.php">Главная</a>
                <a href="post.php">Написать пост</a>
                <a href="logout.php">Выйти</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h1>Профиль пользователя: <?php echo htmlspecialchars($user['username']); ?></h1>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                <p>Зарегистрирован: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <div class="profile-stats">
                <div class="stat-card">
                    <h3>Посты</h3>
                    <p class="stat-number"><?php echo count($user_posts); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Подписчики</h3>
                    <p class="stat-number"><?php echo count($followers); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Подписки</h3>
                    <p class="stat-number"><?php echo count($following); ?></p>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="profile-section">
                    <h2>Мои посты</h2>
                    
                    <?php if (count($user_posts) > 0): ?>
                        <div class="user-posts">
                            <?php foreach ($user_posts as $post): ?>
                                <div class="user-post">
                                    <h3><a href="view_post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
                                    <div class="post-info">
                                        <span>Создан: <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></span>
                                        <span>Видимость: <?php 
                                            if ($post['visibility'] == 'public') echo 'Публичный';
                                            elseif ($post['visibility'] == 'private') echo 'Приватный';
                                            else echo 'По запросу';
                                        ?></span>
                                    </div>
                                    <div class="post-actions">
                                        <a href="post.php?edit=<?php echo $post['id']; ?>" class="btn btn-small">Редактировать</a>
                                        <a href="view_post.php?id=<?php echo $post['id']; ?>" class="btn btn-small">Просмотреть</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>У вас пока нет постов. <a href="post.php">Создайте первый!</a></p>
                    <?php endif; ?>
                </div>
                
                <div class="profile-section">
                    <h2>Мои подписки</h2>
                    
                    <?php if (count($following) > 0): ?>
                        <div class="following-list">
                            <?php foreach ($following as $follow): ?>
                                <div class="follow-item">
                                    <span><?php echo htmlspecialchars($follow['username']); ?></span>
                                    <a href="index.php?user=<?php echo $follow['id']; ?>" class="btn btn-small">Посмотреть посты</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Вы еще ни на кого не подписаны.</p>
                    <?php endif; ?>
                </div>
                
                <div class="profile-section">
                    <h2>Мои подписчики</h2>
                    
                    <?php if (count($followers) > 0): ?>
                        <div class="followers-list">
                            <?php foreach ($followers as $follower): ?>
                                <div class="follower-item">
                                    <span><?php echo htmlspecialchars($follower['username']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>У вас пока нет подписчиков.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>