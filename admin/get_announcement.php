<?php
session_start();
require __DIR__ . "../../config/db_config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['announcement_id'])) {
    $announcement_id = (int)$_GET['announcement_id'];
    
    if ($announcement_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid announcement ID']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($announcement) {
            echo json_encode($announcement);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Announcement not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method or missing announcement_id parameter']);
}
?>