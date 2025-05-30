<h2>Обратная связь</h2>
<?php if (empty($feedback)): ?>
    <p>Нет сообщений обратной связи.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Сообщение</th>
            <th>Дата</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($feedback as $item): ?>
        <tr>
            <td><?= $item['id'] ?></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= htmlspecialchars($item['email']) ?></td>
            <td><?= htmlspecialchars(substr($item['message'], 0, 50)) ?>...</td>
            <td><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></td>
            <td>
                <form action="delete_message.php" method="POST" onsubmit="return confirm('Удалить сообщение?')">
                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>