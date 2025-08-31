<?php
require __DIR__ . "../../config/db_config.php";
header('Content-Type: application/json');
if (isset($_GET['message_id'])) {
    $message_id = $_GET['message_id'];
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE message_id = ?");
    $stmt->execute([$message_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Message not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>