<?php
session_start();
require __DIR__ . "../../../config/db_config.php";

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/"); 
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = :token");
    $stmt->execute([':token' => $_COOKIE['remember_token']]);
}

session_unset();
session_destroy();

header("Location: login.php");
exit;
?>