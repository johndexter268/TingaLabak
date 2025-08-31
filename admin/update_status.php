<?php
session_start();
require __DIR__ . "../../config/db_config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $request_id = trim($_POST['request_id']);
    $status = trim($_POST['status']);

    // Validate status
    $valid_statuses = ['Pending', 'Approved', 'Rejected', 'Received'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['error' => 'Invalid status value']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE document_requests SET status = ? WHERE request_id = ?");
        $stmt->execute([$status, $request_id]);

        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to update status: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>