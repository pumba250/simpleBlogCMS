<h2>Управление шаблонами</h2>
<?php if (!empty($message)): ?>
    <div class="alert alert-<?= strpos($message, 'Ошибка') !== false ? 'danger' : 'success' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="current-template">
    <h3>Текущий шаблон: <?= htmlspecialchars($currentTemplate) ?></h3>
</div>

<div class="templates-grid">
    <?php foreach ($templates as $templateName): ?>
    <div class="template-card <?= $templateName === $currentTemplate ? 'active' : '' ?>">
        <h4><?= htmlspecialchars(ucfirst($templateName)) ?></h4>
        <?php if ($templateName === $currentTemplate): ?>
            <span class="badge">Активен</span>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="change_template">
                <input type="hidden" name="template" value="<?= htmlspecialchars($templateName) ?>">
                <button type="submit" class="btn">Активировать</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>