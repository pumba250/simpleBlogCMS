<!DOCTYPE html>
<html>
<head>
    <title>Ошибка приложения</title>
    <style>
        .error-container { max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #e0e0e0; }
        .error-title { color: #d32f2f; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; }
        .trace-item { margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .admin-only { background-color: #fff8e1; padding: 10px; margin: 10px 0; border-left: 3px solid #ffc107; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">Произошла ошибка</h1>
        <h2><?= htmlspecialchars($errorDetails['message']) ?></h2>
        <p><strong>Файл:</strong> <?= htmlspecialchars($errorDetails['file']) ?> (строка <?= $errorDetails['line'] ?>)</p>
        <p><strong>Тип:</strong> <?= htmlspecialchars($errorDetails['type']) ?></p>
        
        <h3>Трассировка (первые 10 вызовов):</h3>
        <?php foreach ($errorDetails['trace'] as $i => $trace): ?>
            <div class="trace-item">
                <strong>#<?= $i ?>:</strong> 
                <?= htmlspecialchars($trace['file'] ?? '') ?> (<?= $trace['line'] ?? '' ?>)
                <br>
                <?= htmlspecialchars($trace['class'] ?? '') ?><?= htmlspecialchars($trace['type'] ?? '') ?><?= htmlspecialchars($trace['function'] ?? '') ?>()
            </div>
        <?php endforeach; ?>
        
        <?php if (isset($errorDetails['full_trace'])): ?>
            <div class="admin-only">
                <h3>Полная трассировка (только для администраторов):</h3>
                <pre><?= htmlspecialchars(print_r($errorDetails['full_trace'], true)) ?></pre>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorDetails['request'])): ?>
            <div class="admin-only">
                <h3>Детали запроса (только для администраторов):</h3>
                <pre><?= htmlspecialchars(print_r($errorDetails['request'], true)) ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>