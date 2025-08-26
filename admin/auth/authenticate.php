<?php
session_start();
require __DIR__ . "../../../config/db_config.php"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
        header("Location: login.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, email, password, name, verified FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check verification
            if ($user['verified'] == 0) {
                $_SESSION['error'] = "Your account is not yet verified.";
                header("Location: login.php");
                exit;
            }

            // Login success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];

           if ($remember) {
                $remember_token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE user_id = :uid");
                $stmt->execute([':token' => $remember_token, ':uid' => $user['user_id']]);
                setcookie('remember_token', $remember_token, time() + (30 * 24 * 60 * 60), "/"); // 30 days
            }

            header("Location: ../dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            header("Location: login.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Login failed: " . $e->getMessage();
        header("Location: login.php");
        exit;
    }
}
