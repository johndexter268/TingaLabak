<?php
session_start();
require __DIR__ . "../../../config/db_config.php";
require __DIR__ . "../../../vendor/autoload.php"; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $_SESSION['error'] = "Please enter an email address.";
        header("Location: login.php");
        exit;
    }

    try {
        // Check if email exists and is verified
        $stmt = $pdo->prepare("SELECT user_id, verified FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['verified'] == 0) {
                $_SESSION['error'] = "Your account is not yet verified. Please verify your email first.";
                header("Location: login.php");
                exit;
            }

            // Generate and store reset token
            $token = bin2hex(random_bytes(32));
            $expires = gmdate("Y-m-d H:i:s", time() + 3600); 
            $stmt = $pdo->prepare(
                "INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (:uid, :token, :expires)
                ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires"
            );
            $stmt->execute([':uid' => $user['user_id'], ':token' => $token, ':expires' => $expires]);

            // Configure PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;
                $mail->Username = 'rubiondexter@gmail.com'; 
                $mail->Password = 'ysrf taua wpfx hton';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Sender and recipient
                $mail->setFrom('no-reply@barangaytingalabak.com', 'Barangay Tinga Labak');
                $mail->addAddress($email);

                // Email content
                $resetLink = "http://localhost/TinggaLabac/admin/auth/reset-password.php?token=$token";
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset - Barangay Tinga Labak';
                $mail->Body = '
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: "Montserrat", sans-serif; color: #0f172a; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #ffffff; border-radius: 12px; }
                            .header { text-align: center; padding-bottom: 20px; }
                            .header img { width: 80px; height: 80px; border-radius: 50%; border: 4px solid #ffd700; }
                            .content { text-align: center; }
                            .button { 
                                display: inline-block; 
                                padding: 12px 24px; 
                                background: #0B2C3D; 
                                color: #ffffff; 
                                text-decoration: none !important; 
                                border-radius: 12px; 
                                font-weight: 600; 
                                margin-top: 20px; 
                            }
                            .button, 
                            .button:link, 
                            .button:visited, 
                            .button:active {
                            color: #ffffff !important;
                            text-decoration: none !important;
                            }
                            .button:hover { background: #2F3E46; }
                            .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #475569; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <img src="https://www.batangascity.gov.ph/CHO/telemedicine/BatangasCity.png" alt="Barangay Logo">
                                <h2>Barangay Tinga Labak</h2>
                            </div>
                            <div class="content">
                                <h3>Password Reset Request</h3>
                                <p>We received a request to reset your password. Click the button below to proceed:</p>
                                <a href="' . $resetLink . '" class="button">Reset Password</a>
                                <p>This link will expire in 1 hour. If you did not request a password reset, please ignore this email.</p>
                            </div>
                            <div class="footer">
                                <p>&copy; ' . date('Y') . ' Barangay Tinga Labak. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ';
                $mail->AltBody = "Click here to reset your password: $resetLink\nThis link will expire in 1 hour.";

                // Send email
                $mail->send();
                $_SESSION['success'] = "A password reset link has been sent to your email.";
                header("Location: login.php");
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to send reset email: " . $mail->ErrorInfo;
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "No account found with that email.";
            header("Location: login.php");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        header("Location: login.php");
        exit;
    }
}
?>