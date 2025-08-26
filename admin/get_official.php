<?php

session_start();
require __DIR__ . "../../config/db_config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['official_id'])) {
    $official_id = (int)$_GET['official_id'];
    
    if ($official_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid official ID']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM brgy_officials WHERE official_id = ?");
        $stmt->execute([$official_id]);
        $official = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($official) {
            // Return official data as JSON
            echo json_encode($official);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Official not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method or missing official_id parameter']);
}
?>