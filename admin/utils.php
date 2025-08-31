<?php
require __DIR__ . "../../config/db_config.php";

function logActivity($user_id, $action, $details = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {
        // Log error to a file or system log (avoid exposing to users)
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>