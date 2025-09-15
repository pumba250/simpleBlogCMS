<?php

if (!defined('IN_SIMPLECMS')) 
{ 
    die('Прямой доступ запрещен');
}
/**
 * Класс для работы с контактными сообщениями
 *
 * @package    SimpleBlog
 * @subpackage Models
 * @category   Contact
 * @version    0.9.9
 *
 * @method bool   saveMessage(string $name, string $email, string $message) Сохраняет контактное сообщение в БД
 * @method array  getAllMessages() Получает все сообщения (сортировка по дате создания)
 * @method bool   deleteMessage(int $id) Удаляет сообщение по ID
 */
class Contact
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function saveMessage($name, $email, $message)
    {
        global $dbPrefix;
        $stmt = $this->pdo->prepare(
            "INSERT INTO `{$dbPrefix}blogs_contacts` (name, email, message) VALUES (?, ?, ?)"
        );
        flash(Lang::get('msg_send', 'core'), 'success');
        return $stmt->execute([$name, $email, $message]);
    }

    public function getAllMessages()
    {
        global $dbPrefix;
        $stmt = $this->pdo->query(
            "SELECT * FROM `{$dbPrefix}blogs_contacts` ORDER BY created_at DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteMessage($id)
    {
        global $dbPrefix;

        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM `{$dbPrefix}blogs_contacts` WHERE id = ?"
            );

            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
