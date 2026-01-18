document.addEventListener('DOMContentLoaded', function() {
    // Обработка подписки/отписки
    const subscribeButtons = document.querySelectorAll('.btn-subscribe');
    
    subscribeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const isSubscribed = this.getAttribute('data-subscribed') === 'true';
            
            // Отправка AJAX запроса
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/subscribe.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Обновляем текст кнопки и состояние
                        if (response.subscribed) {
                            button.textContent = 'Отписаться';
                            button.setAttribute('data-subscribed', 'true');
                        } else {
                            button.textContent = 'Подписаться';
                            button.setAttribute('data-subscribed', 'false');
                        }
                        
                        // Показываем уведомление
                        showNotification(response.message, 'success');
                    } else {
                        showNotification(response.message, 'error');
                    }
                }
            };
            
            xhr.send('user_id=' + userId + '&action=' + (isSubscribed ? 'unsubscribe' : 'subscribe'));
        });
    });
    
    // Функция показа уведомления
    function showNotification(message, type) {
        // Создаем элемент уведомления
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Стили для уведомления
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.padding = '15px 20px';
        notification.style.borderRadius = '4px';
        notification.style.color = 'white';
        notification.style.zIndex = '1000';
        notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        
        if (type === 'success') {
            notification.style.backgroundColor = '#28a745';
        } else {
            notification.style.backgroundColor = '#dc3545';
        }
        
        // Добавляем на страницу
        document.body.appendChild(notification);
        
        // Удаляем через 3 секунды
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 500);
        }, 3000);
    }
    
    // Фильтрация по тегам (если есть параметр тега в URL)
    const urlParams = new URLSearchParams(window.location.search);
    const tagParam = urlParams.get('tag');
    
    if (tagParam) {
        // Выделяем активный тег
        const tagElements = document.querySelectorAll('.tag-cloud');
        tagElements.forEach(tag => {
            if (tag.textContent === tagParam) {
                tag.style.fontWeight = 'bold';
                tag.style.color = '#0056b3';
            }
        });
        
        // Можно добавить заголовок "Посты с тегом: X"
        const postsTitle = document.querySelector('.posts h1');
        if (postsTitle) {
            postsTitle.textContent = `Посты с тегом: ${tagParam}`;
        }
    }
    
    // Динамическая загрузка постов (пагинация)
    let page = 1;
    const loadMoreBtn = document.getElementById('loadMore');
    
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            page++;
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `ajax/load_posts.php?page=${page}`, true);
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.posts && response.posts.length > 0) {
                        // Добавляем новые посты
                        const postsContainer = document.querySelector('.posts');
                        
                        response.posts.forEach(post => {
                            const postElement = createPostElement(post);
                            postsContainer.appendChild(postElement);
                        });
                        
                        // Если больше нет постов, скрываем кнопку
                        if (!response.hasMore) {
                            loadMoreBtn.style.display = 'none';
                        }
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            };
            
            xhr.send();
        });
    }
    
    // Создание HTML элемента поста (для динамической загрузки)
    function createPostElement(post) {
        const div = document.createElement('div');
        div.className = 'post-card';
        
        // Форматируем дату
        const date = new Date(post.created_at);
        const formattedDate = `${date.getDate().toString().padStart(2, '0')}.${(date.getMonth() + 1).toString().padStart(2, '0')}.${date.getFullYear()} ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
        
        // Обрезаем контент для превью
        let contentPreview = post.content;
        if (contentPreview.length > 300) {
            contentPreview = contentPreview.substring(0, 300) + '...';
        }
        
        div.innerHTML = `
            <div class="post-header">
                <span class="post-author">${post.username}</span>
                <span class="post-date">${formattedDate}</span>
            </div>
            <h3 class="post-title">${escapeHtml(post.title)}</h3>
            <div class="post-content">${escapeHtml(contentPreview)}</div>
            <div class="post-actions">
                <a href="view_post.php?id=${post.id}" class="btn">Читать далее</a>
            </div>
        `;
        
        return div;
    }
    
    // Экранирование HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Обработка сортировки постов
    const sortSelect = document.getElementById('sortPosts');
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortBy = this.value;
            window.location.href = `index.php?sort=${sortBy}`;
        });
    }
});