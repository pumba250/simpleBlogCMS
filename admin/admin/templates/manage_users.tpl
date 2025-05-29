<h2>Управление пользователями</h2>

<?php if (empty($users)): ?>
    <p>Нет зарегистрированных пользователей.</p>
<?php else: ?>
    <table class="w3-table-all">
        <thead>
            <tr class="w3-light-grey">
                <th>ID</th>
                <th>Логин</th>
                <th>Email</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <?php if ($user['is_verified'] == 1): ?>
                        <span class="w3-tag w3-green">Подтверждён</span>
                    <?php else: ?>
                        <span class="w3-tag w3-red">Не подтверждён</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="delete_user.php?id=<?= $user['id'] ?>" 
                       class="w3-button w3-red w3-small"
                       onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">
                        Удалить
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>