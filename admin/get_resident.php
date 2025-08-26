<?php
session_start();
require __DIR__ . "../../config/db_config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['resident_id'])) {
    $resident_id = (int)$_GET['resident_id'];
    
    if ($resident_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid resident ID']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM residents WHERE resident_id = ?");
        $stmt->execute([$resident_id]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resident) {
            echo json_encode($resident);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Resident not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method or missing resident_id parameter']);
}
?>