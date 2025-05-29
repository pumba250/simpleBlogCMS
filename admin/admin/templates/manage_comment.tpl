<h2>Управление комментариями</h2>
<?php if (empty($pendingComments)): ?>
    <p>Нет комментариев на модерацию.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Сообщение</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendingComments as $comment): ?>
        <tr>
            <td><?= $comment['id'] ?></td>
            <td><?= htmlspecialchars($comment['user_name']) ?></td>
            <td><?= htmlspecialchars($comment['user_text']) ?></td>
            <td>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                    <button type="submit" name="approve" class="btn btn-success">Одобрить</button>
                    <button type="submit" name="reject" class="btn btn-danger">Удалить</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>