<?php
session_start();
require __DIR__ . "../../../config/db_config.php";

$pdo->exec("SET time_zone = '+00:00'");

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $newPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if (empty($token)) {
        $_SESSION['error'] = "No token provided.";
        header("Location: login.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':token' => $token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :uid");
            $stmt->execute([':password' => $newPassword, ':uid' => $reset['user_id']]);

            $pdo->prepare("DELETE FROM password_resets WHERE user_id = :uid")->execute([':uid' => $reset['user_id']]);

            $_SESSION['success'] = "Password updated successfully. You can now login.";
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid or expired token.";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: login.php");
        exit;
    }
} else {
    if (empty($token)) {
        $_SESSION['error'] = "No token provided.";
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Barangay Tinga Labak</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-section">
            <div class="login-form-container">
                <div class="login-header">
                    <div class="logo-container">
                        <img src="../../imgs/BatangasCity.png" alt="Barangay Logo" class="login-logo">
                        <h4>Barangay Tinga Labak</h4>
                    </div>
                    <h1 class="login-title">Reset Password</h1>
                </div>

                <div class="form-wrapper">
                    <form id="reset-form" class="login-form form-container visible" action="reset-password.php" method="POST" aria-label="Reset password form">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="input-group">
                            <div class="input-container">
                                <input type="password" id="password" name="password" class="form-input" required>
                                <label for="password" class="form-label">New Password</label>
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                        </div>

                        <button type="submit" class="login-btn">
                            <span>Reset Password</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>