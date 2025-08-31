<?php
require __DIR__ . "../../config/db_config.php";
header('Content-Type: application/json');
if (isset($_GET['log_id'])) {
    $log_id = $_GET['log_id'];
    $stmt = $pdo->prepare("SELECT * FROM activity_log WHERE log_id = ?");
    $stmt->execute([$log_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Activity not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>