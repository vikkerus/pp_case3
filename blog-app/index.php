<?php
require_once 'functions.php';

$posts = [];
if (isLoggedIn()) {
    $posts = getPosts(50, 0, $_SESSION['user_id']);
} else {
    $posts = getPosts(50, 0);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Блог - Главная</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">БлогПлатформа</a>
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <span>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="post.php">Написать пост</a>
                    <a href="profile.php">Профиль</a>
                    <a href="logout.php">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="login.php?register=1">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content">
            <div class="main-content">
                <h1>Последние посты</h1>
                
                <?php if (isLoggedIn()): ?>
                <div class="subscription-panel">
                    <h3>Мои подписки</h3>
                    <?php
                    $conn = getConnection();
                    $stmt = $conn->prepare("SELECT u.id, u.username FROM users u 
                                            JOIN subscriptions s ON u.id = s.subscribed_to_id 
                                            WHERE s.subscriber_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($subscriptions) > 0): ?>
                        <div class="subscription-list">
                            <?php foreach ($subscriptions as $sub): ?>
                                <span class="subscription-tag"><?php echo htmlspecialchars($sub['username']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Вы еще ни на кого не подписаны. Найдите интересных авторов!</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="posts">
                    <?php if (count($posts) > 0): ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <span class="post-author"><?php echo htmlspecialchars($post['username']); ?></span>
                                    <span class="post-date"><?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></span>
                                    <?php if ($post['visibility'] == 'request'): ?>
                                        <span class="visibility-label">По запросу</span>
                                    <?php elseif ($post['visibility'] == 'private'): ?>
                                        <span class="visibility-label">Приватный</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <div class="post-content">
                                    <?php 
                                    $content = $post['content'];
                                    if (strlen($content) > 300) {
                                        echo htmlspecialchars(substr($content, 0, 300)) . '...';
                                    } else {
                                        echo htmlspecialchars($content);
                                    }
                                    ?>
                                </div>
                                
                                <?php 
                                $tags = getPostTags($post['id']);
                                if (!empty($tags)): ?>
                                    <div class="post-tags">
                                        <?php foreach ($tags as $tag): ?>
                                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-actions">
                                    <a href="view_post.php?id=<?php echo $post['id']; ?>" class="btn">Читать далее</a>
                                    <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                                        <a href="post.php?edit=<?php echo $post['id']; ?>" class="btn btn-edit">Редактировать</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-posts">
                            <h3>Постов пока нет</h3>
                            <p>Будьте первым, кто напишет интересный пост!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sidebar">
                <div class="sidebar-widget">
                    <h3>Популярные теги</h3>
                    <div class="tags-cloud">
                        <?php
                        $conn = getConnection();
                        $stmt = $conn->query("SELECT t.name, COUNT(pt.post_id) as count FROM tags t 
                                              JOIN post_tags pt ON t.id = pt.tag_id 
                                              GROUP BY t.id ORDER BY count DESC LIMIT 15");
                        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($tags as $tag):
                            $size = min(20 + ($tag['count'] * 3), 32);
                        ?>
                            <a href="index.php?tag=<?php echo urlencode($tag['name']); ?>" 
                               style="font-size: <?php echo $size; ?>px" 
                               class="tag-cloud"><?php echo htmlspecialchars($tag['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                <div class="sidebar-widget">
                    <h3>Рекомендации</h3>
                    <div class="recommended-users">
                        <?php
                        $conn = getConnection();
                        $stmt = $conn->prepare("SELECT u.id, u.username FROM users u 
                                                WHERE u.id != ? AND u.id NOT IN (
                                                    SELECT subscribed_to_id FROM subscriptions 
                                                    WHERE subscriber_id = ?
                                                ) LIMIT 5");
                        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                        $recommended = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($recommended as $user): ?>
                            <div class="recommended-user">
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                                <button class="btn btn-subscribe" data-user-id="<?php echo $user['id']; ?>">Подписаться</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>БлогПлатформа &copy; 2023</p>
        </div>
    </footer>
    
    <script src="js/scripts.js"></script>
</body>
</html>