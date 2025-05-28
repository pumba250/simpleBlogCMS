<?php
class Contact {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function saveMessage($name, $email, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs_contacts (name, email, message) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $email, $message]);
    }
	public function deleteMessage($id) {
		$stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs_contacts WHERE id = ?");
		return $stmt->execute([$id]);
	}
}
?>
