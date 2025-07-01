<?php
/**
 * Contact form message handler - manages contact submissions and admin operations
 *
 * @package    SimpleBlog
 * @subpackage Models
 * @category   Contact
 * @version    0.7.0
 * @author     pumba250
 * 
 * @property PDO $pdo Active database connection
 *
 * @method bool   saveMessage(string $name, string $email, string $message)  Stores new contact message
 * @method array  getAllMessages()                                          Retrieves all messages (admin)
 * @method bool   deleteMessage(int $id)                                    Removes message by ID (admin)
 * 
 * @table {prefix}blogs_contacts
 *  - id          :int
 *  - name        :varchar
 *  - email       :varchar
 *  - message     :text
 *  - created_at  :timestamp
 */
class Contact {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function saveMessage($name, $email, $message) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("INSERT INTO `{$dbPrefix}blogs_contacts` (name, email, message) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $email, $message]);
    }
	public function getAllMessages() {
		global $dbPrefix;
        $stmt = $this->pdo->query("SELECT * FROM `{$dbPrefix}blogs_contacts` ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function deleteMessage($id) {
		global $dbPrefix;
        try {
            $stmt = $this->pdo->prepare("DELETE FROM `{$dbPrefix}blogs_contacts` WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
?>
