<?php
session_start();
require __DIR__ . "../../../config/db_config.php";

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE remember_token = :token LIMIT 1");
    $stmt->execute([':token' => $_COOKIE['remember_token']]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        header("Location: ../dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Management System</title>
    <link rel="icon" href="../../imgs/BatangasCity.png" type="image/jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
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
                    <h1 class="login-title">Login</h1>
                </div>

                <div class="form-wrapper">
                    <!-- Login Form -->
                    <form id="login-form" class="login-form form-container visible" action="authenticate.php" method="POST" aria-label="Login form">
                        <div class="input-group">
                            <div class="input-container">
                                <input type="email" id="email" name="email" class="form-input" required>
                                <label for="email" class="form-label">Email</label>
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>

                        <div class="input-group">
                            <div class="input-container">
                                <input type="password" id="password" name="password" class="form-input" required>
                                <label for="password" class="form-label">Password</label>
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                        </div>

                        <div class="form-options">
                            <div class="checkbox-container">
                                <input type="checkbox" id="remember" name="remember" class="form-checkbox">
                                <label for="remember" class="checkbox-label">Remember me</label>
                            </div>
                            <a href="#" class="forgot-link" onclick="toggleForms();return false;">Forgot Password?</a>
                        </div>

                        <button type="submit" class="login-btn">
                            <span>Login</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>

                    <!-- Forgot Password Form -->
                    <form id="forgot-password-form" class="login-form form-container hidden" action="forgot-password.php" method="POST" aria-label="Forgot password form">
                        <div class="input-group">
                            <div class="input-container">
                                <input type="email" id="forgot-email" name="email" class="form-input" required>
                                <label for="forgot-email" class="form-label">Enter your email</label>
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>
                        <button type="submit" class="login-btn">
                            <span>Send Reset Link</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" class="login-btn" style="background: var(--gray-600); margin-top: 0.5rem;" onclick="toggleForms();">
                            <span>Cancel</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toastify notifications
            <?php if (isset($_SESSION['error'])): ?>
            Toastify({
                text: '<?php echo $_SESSION['error']; ?>',
                duration: 3000,
                gravity: 'top',
                position: 'right',
                backgroundColor: '#ff4545',
                stopOnFocus: true,
                style: {
                    position: 'fixed'
                }
            }).showToast();
            <?php unset($_SESSION['error']); ?>
        <?php elseif (isset($_SESSION['success'])): ?>
            Toastify({
                text: '<?php echo $_SESSION['success']; ?>',
                duration: 3000,
                gravity: 'top',
                position: 'right',
                backgroundColor: '#008000',
                stopOnFocus: true,
                style: {
                    position: 'fixed'
                }
            }).showToast();
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

            // Form input handling
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    input.classList.add('has-value');
                }
                input.addEventListener('focus', function() {
                    this.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    this.classList.remove('focused');
                    if (this.value.trim() !== '') {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
            });

            // Form toggle with animation
            window.toggleForms = function() {
                const loginForm = document.getElementById('login-form');
                const forgotForm = document.getElementById('forgot-password-form');

                if (loginForm.classList.contains('visible')) {
                    loginForm.classList.remove('visible');
                    loginForm.classList.add('hidden');
                    setTimeout(() => {
                        forgotForm.classList.remove('hidden');
                        forgotForm.classList.add('visible');
                        document.getElementById('forgot-email').focus();
                    }, 150); 
                } else {
                    forgotForm.classList.remove('visible');
                    forgotForm.classList.add('hidden');
                    setTimeout(() => {
                        loginForm.classList.remove('hidden');
                        loginForm.classList.add('visible');
                        document.getElementById('email').focus();
                    }, 150);
                }
            };
        });
    </script>
</body>

</html>