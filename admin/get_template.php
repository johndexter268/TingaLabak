<?php
session_start();
require __DIR__ . "../../config/db_config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['template_id'])) {
    $template_id = (int)$_GET['template_id'];
    
    if ($template_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid template ID']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM document_templates WHERE template_id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($template) {
            echo json_encode($template);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Document template not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method or missing template_id parameter']);
}
?>