<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$post = null;
$tags = '';

// Режим редактирования
if (isset($_GET['edit'])) {
    $post_id = intval($_GET['edit']);
    $post = getPost($post_id);
    
    if (!$post || $post['user_id'] != $_SESSION['user_id']) {
        header('Location: index.php');
        exit();
    }
    
    $existing_tags = getPostTags($post_id);
    $tags = implode(', ', $existing_tags);
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $visibility = $_POST['visibility'];
    $tags_input = trim($_POST['tags']);
    
    if (empty($title) || empty($content)) {
        $error = 'Заголовок и содержание поста обязательны';
    } else {
        $conn = getConnection();
        
        if (isset($_POST['update']) && isset($post)) {
            // Обновление поста
            $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, visibility = ? WHERE id = ?");
            $stmt->execute([$title, $content, $visibility, $post['id']]);
            $post_id = $post['id'];
            $success = 'Пост успешно обновлен!';
        } else {
            // Создание нового поста
            $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, visibility) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $content, $visibility]);
            $post_id = $conn->lastInsertId();
            $success = 'Пост успешно создан!';
        }
        
        // Обработка тегов
        if (!empty($tags_input) && $post_id) {
            // Удаляем старые теги при редактировании
            if (isset($post)) {
                $stmt = $conn->prepare("DELETE FROM post_tags WHERE post_id = ?");
                $stmt->execute([$post_id]);
            }
            
            $tag_names = array_map('trim', explode(',', $tags_input));
            $tag_names = array_unique($tag_names);
            
            foreach ($tag_names as $tag_name) {
                if (empty($tag_name)) continue;
                
                // Проверяем существование тега
                $stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
                $stmt->execute([$tag_name]);
                
                if ($stmt->rowCount() > 0) {
                    $tag_id = $stmt->fetch(PDO::FETCH_COLUMN);
                } else {
                    // Создаем новый тег
                    $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
                    $stmt->execute([$tag_name]);
                    $tag_id = $conn->lastInsertId();
                }
                
                // Связываем тег с постом
                $stmt = $conn->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$post_id, $tag_id]);
            }
        }
        
        if (isset($_POST['update'])) {
            // Обновляем данные поста для отображения
            $post = getPost($post_id);
            $existing_tags = getPostTags($post_id);
            $tags = implode(', ', $existing_tags);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($post) ? 'Редактирование поста' : 'Создание поста'; ?> - Блог</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">БлогПлатформа</a>
            <div class="nav-links">
                <span>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="index.php">Главная</a>
                <a href="profile.php">Профиль</a>
                <a href="logout.php">Выйти</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="post-form-container">
            <h1><?php echo isset($post) ? 'Редактирование поста' : 'Создание нового поста'; ?></h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Заголовок:</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo isset($post) ? htmlspecialchars($post['title']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Содержание:</label>
                    <textarea id="content" name="content" rows="10" required><?php 
                        echo isset($post) ? htmlspecialchars($post['content']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="visibility">Видимость:</label>
                    <select id="visibility" name="visibility">
                        <option value="public" <?php echo (isset($post) && $post['visibility'] == 'public') ? 'selected' : ''; ?>>Публичный</option>
                        <option value="private" <?php echo (isset($post) && $post['visibility'] == 'private') ? 'selected' : ''; ?>>Приватный (только я)</option>
                        <option value="request" <?php echo (isset($post) && $post['visibility'] == 'request') ? 'selected' : ''; ?>>По запросу</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tags">Теги (через запятую):</label>
                    <input type="text" id="tags" name="tags" 
                           value="<?php echo htmlspecialchars($tags); ?>" 
                           placeholder="технологии, программирование, php">
                </div>
                
                <div class="form-actions">
                    <?php if (isset($post)): ?>
                        <button type="submit" name="update" class="btn btn-primary">Обновить пост</button>
                        <a href="view_post.php?id=<?php echo $post['id']; ?>" class="btn">Просмотреть</a>
                        <a href="index.php" class="btn btn-secondary">Отмена</a>
                    <?php else: ?>
                        <button type="submit" name="create" class="btn btn-primary">Опубликовать пост</button>
                        <a href="index.php" class="btn btn-secondary">Отмена</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php if (isset($post)): ?>
            <div class="delete-section">
                <h3>Удаление поста</h3>
                <p>Внимание: удаление поста нельзя отменить. Все комментарии также будут удалены.</p>
                <button id="deletePostBtn" class="btn btn-danger">Удалить пост</button>
                
                <form id="deleteForm" method="POST" action="delete_post.php" style="display: none;">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteBtn = document.getElementById('deletePostBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    if (confirm('Вы уверены, что хотите удалить этот пост? Это действие нельзя отменить.')) {
                        document.getElementById('deleteForm').submit();
                    }
                });
            }
        });
    </script>
</body>
</html>