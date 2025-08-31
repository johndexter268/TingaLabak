<?php
session_start();

require __DIR__ . "../../config/db_config.php";

if (!isset($_GET['file_path']) || empty($_GET['file_path'])) {
    http_response_code(400);
    echo "No file path provided";
    exit();
}

$file_path = urldecode($_GET['file_path']);
$uploads_dir = realpath(__DIR__ . '/Uploads');
$file_path = realpath($file_path);

if ($file_path === false || strpos($file_path, $uploads_dir) !== 0 || !file_exists($file_path)) {
    http_response_code(404);
    echo "File not found or access denied";
    exit();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit();
?>