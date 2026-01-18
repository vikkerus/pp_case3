<?php
require_once 'functions.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$post_id = intval($_GET['id']);
$post = getPost($post_id);

if (!$post) {
    header('Location: index.php');
    exit();
}

// Проверка видимости поста
$can_view = false;
if ($post['visibility'] == 'public') {
    $can_view = true;
} elseif (isLoggedIn()) {
    if ($post['visibility'] == 'private' && $post['user_id'] == $_SESSION['user_id']) {
        $can_view = true;
    } elseif ($post['visibility'] == 'request') {
        // Для постов "по запросу" доступ есть у автора и подписчиков
        if ($post['user_id'] == $_SESSION['user_id'] || isSubscribed($_SESSION['user_id'], $post['user_id'])) {
            $can_view = true;
        }
    }
}

if (!$can_view) {
    header('Location: index.php');
    exit();
}

// Получение тегов
$tags = getPostTags($post_id);

// Получение комментариев
$comments = getComments($post_id);

// Обработка добавления комментария
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment']) && isLoggedIn()) {
    $comment_content = trim($_POST['comment_content']);
    
    if (!empty($comment_content)) {
        $conn = getConnection();
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $comment_content]);
        
        // Обновляем список комментариев
        $comments = getComments($post_id);
    }
}

// Проверка подписки
$is_subscribed = false;
if (isLoggedIn() && $post['user_id'] != $_SESSION['user_id']) {
    $is_subscribed = isSubscribed($_SESSION['user_id'], $post['user_id']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Блог</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">БлогПлатформа</a>
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <span>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="index.php">Главная</a>
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
        <div class="post-view">
            <div class="post-header">
                <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-meta">
                    <span class="post-author">Автор: <?php echo htmlspecialchars($post['username']); ?></span>
                    <span class="post-date">Опубликовано: <?php echo date('d.m.Y H:i', strtotime($post['created_at'])); ?></span>
                    
                    <?php if ($post['visibility'] == 'request'): ?>
                        <span class="visibility-label">Пост доступен по запросу</span>
                    <?php elseif ($post['visibility'] == 'private'): ?>
                        <span class="visibility-label">Приватный пост</span>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn() && $post['user_id'] != $_SESSION['user_id']): ?>
                        <button id="subscribeBtn" class="btn btn-subscribe" 
                                data-user-id="<?php echo $post['user_id']; ?>"
                                data-subscribed="<?php echo $is_subscribed ? 'true' : 'false'; ?>">
                            <?php echo $is_subscribed ? 'Отписаться' : 'Подписаться'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            
            <?php if (!empty($tags)): ?>
                <div class="post-tags">
                    <strong>Теги:</strong>
                    <?php foreach ($tags as $tag): ?>
                        <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="post-actions">
                <?php if (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']): ?>
                    <a href="post.php?edit=<?php echo $post['id']; ?>" class="btn btn-edit">Редактировать</a>
                <?php endif; ?>
                <a href="index.php" class="btn">Назад к списку</a>
            </div>
            
            <div class="comments-section">
                <h2>Комментарии (<?php echo count($comments); ?>)</h2>
                
                <?php if (isLoggedIn()): ?>
                    <div class="add-comment">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="comment_content">Ваш комментарий:</label>
                                <textarea id="comment_content" name="comment_content" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary">Добавить комментарий</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p><a href="login.php">Войдите</a>, чтобы оставить комментарий.</p>
                <?php endif; ?>
                
                <div class="comments-list">
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                    <span class="comment-date"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-comments">Комментариев пока нет. Будьте первым!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/scripts.js"></script>
</body>
</html>