// modal.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded'); // Проверка загрузки скрипта

    const modal = document.getElementById('id01');
    const loginBtn = document.querySelector('.login-btn');
    const closeBtn = document.querySelector('.close-modal');

    console.log('Modal:', modal); // Должен вывести элемент
    console.log('Login button:', loginBtn); // Должен вывести кнопку

    if (loginBtn && modal) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Предотвращаем действие по умолчанию
            console.log('Login button clicked');
            modal.style.display = 'block';
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
