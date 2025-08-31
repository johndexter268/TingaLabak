<?php
// save_document.php - Dedicated API endpoint for saving documents
header('Content-Type: application/json');

// Prevent any accidental output
ob_start();

// Enable error logging but disable display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

session_start();

try {
    // Check if user is logged in
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        throw new Exception("User not logged in");
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests allowed");
    }

    // Check for required POST parameter
    if (!isset($_POST['save_document'])) {
        throw new Exception("Invalid request");
    }

    require __DIR__ . "../../config/db_config.php";

    // Get and validate input data
    $request_id = trim($_POST['request_id'] ?? '');
    $template_id = trim($_POST['template_id'] ?? '');
    $file_path = trim($_POST['file_path'] ?? '');

    if (empty($request_id) || empty($template_id) || empty($file_path)) {
        throw new Exception("Missing required fields: request_id, template_id, or file_path");
    }

    // Handle base64 PDF data
    $relative_path = $file_path; // Default to input file_path
    if (strpos($file_path, 'data:application/pdf') === 0) {
        // Extract the base64 string, ignoring additional parameters like ;filename=...
        $dataParts = explode(',', $file_path, 2);
        if (count($dataParts) !== 2) {
            throw new Exception("Invalid data URI format");
        }
        $base64_string = $dataParts[1];
        $pdf_data = base64_decode($base64_string, true);

        if ($pdf_data === false) {
            throw new Exception("Invalid base64 PDF data");
        }

        // Create uploads directory if it doesn't exist
        $uploads_dir = __DIR__ . '/uploads';
        if (!is_dir($uploads_dir)) {
            if (!mkdir($uploads_dir, 0755, true)) {
                throw new Exception("Failed to create uploads directory");
            }
        }

        // Generate unique filename
        $filename = 'edited_document_' . $user_id . '_' . time() . '.pdf';
        $new_file_path = $uploads_dir . '/' . $filename;

        // Save PDF file
        if (!file_put_contents($new_file_path, $pdf_data)) {
            throw new Exception("Failed to save PDF file to disk");
        }

        // Set relative path for database and response
        $relative_path = 'uploads/' . $filename;
    } elseif (!file_exists($file_path)) {
        throw new Exception("Template file does not exist: " . basename($file_path));
    }

    // Validate that request and template exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE request_id = ?");
    $stmt->execute([$request_id]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception("Invalid request ID");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM document_templates WHERE template_id = ?");
    $stmt->execute([$template_id]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception("Invalid template ID");
    }

    // Insert into database using relative path
    $stmt = $pdo->prepare("
        INSERT INTO generated_documents (request_id, template_id, file_path, user_id, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");

    $result = $stmt->execute([$request_id, $template_id, $relative_path, $user_id]);

    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database insert failed: " . $errorInfo[2]);
    }

    // Get the generated document ID
    $document_id = $pdo->lastInsertId();

    // Clean output buffer
    if (ob_get_level()) {
        ob_clean();
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Document saved successfully',
        'file_path' => $relative_path,
        'document_id' => $document_id
    ]);

    require_once __DIR__ . "/utils.php";

    // Fetch requestor and template for logs
    $stmt = $pdo->prepare("SELECT firstname, lastname FROM document_requests WHERE request_id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    $fullname = $request ? $request['firstname'] . ' ' . $request['lastname'] : "Unknown";

    $stmt = $pdo->prepare("SELECT document_name FROM document_templates WHERE template_id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    $templateName = $template['document_name'] ?? "Unknown";

    // More descriptive log
    logActivity(
        $_SESSION['user_id'],
        "Created a document for {$fullname} using template '{$templateName}'. File: {$relative_path}"
    );
} catch (Exception $e) {
    // Log the error
    error_log("Error in save_document.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Clean output buffer
    if (ob_get_level()) {
        ob_clean();
    }

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}

// Clean up output buffer
if (ob_get_level()) {
    ob_end_flush();
}
