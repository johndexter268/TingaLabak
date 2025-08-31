<?php
// Prevent any output before JSON
ob_start();

require __DIR__ . "../../config/db_config.php";

// Set JSON header
header('Content-Type: application/json');

try {
    // Validate request_id
    if (!isset($_GET['request_id']) || empty(trim($_GET['request_id']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Request ID is required']);
        exit();
    }

    $request_id = trim($_GET['request_id']);
    
    // Ensure PDO is properly initialized
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Select relevant columns
    $stmt = $pdo->prepare("
        SELECT firstname, middlename, lastname, gender, dob, contact, email, civil_status, 
               sector, years_of_residency, zip_code, province, city_municipality, barangay, 
               purok_sitio_street, subdivision, house_number, document_type, purpose_of_request, 
               requesting_for_self, created_at, status 
        FROM document_requests 
        WHERE request_id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found']);
        exit();
    }

    // Ensure consistent JSON output
    echo json_encode($request);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} finally {
    // Clear any output buffer
    ob_end_flush();
}
?>