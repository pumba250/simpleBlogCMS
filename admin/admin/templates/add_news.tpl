<h2>Добавить новую запись</h2>
<form method="POST" action="process_add_news.php">
    <div class="form-group">
        <label for="title">Заголовок</label>
        <input type="text" id="title" name="title" required>
    </div>
    <div class="form-group">
        <label for="content">Содержание</label>
        <textarea id="content" name="content" rows="10" required></textarea>
    </div>
    <button type="submit" class="btn">Добавить запись</button>
</form>