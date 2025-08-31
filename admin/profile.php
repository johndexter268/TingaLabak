<?php 
session_start();
require __DIR__ . "../../config/db_config.php"; 

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $bio = trim($_POST['bio']);
        
        $stmt = $pdo->prepare("UPDATE users SET name = ?, position = ?, bio = ? WHERE user_id = ?");
        $stmt->execute([$name, $position, $bio, $user_id]);
        
        // Store success message in session for Toastify
        $_SESSION['toast_message'] = "Profile updated successfully!";
        $_SESSION['toast_type'] = "success";

        require_once __DIR__ . "/utils.php";
        logActivity($_SESSION['user_id'], "$name updated profile information.");
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password from database
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    
                    $_SESSION['toast_message'] = "Password changed successfully!";
                    $_SESSION['toast_type'] = "success";
                } else {
                    $_SESSION['toast_message'] = "Password must be at least 6 characters long.";
                    $_SESSION['toast_type'] = "error";
                }
            } else {
                $_SESSION['toast_message'] = "New passwords do not match.";
                $_SESSION['toast_type'] = "error";
            }
        } else {
            $_SESSION['toast_message'] = "Current password is incorrect.";
            $_SESSION['toast_type'] = "error";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>

<body>
    <?php include 'sidebar/sidebar.php'; ?>
    <section class="content-body">
        <div class="profile-header">
            <div class="profile-banner">
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="profile-position"><?php echo htmlspecialchars($user['position'] ?: 'No position set'); ?></p>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-number">
                                <?php echo date('M Y', strtotime($user['date_created'])); ?>
                            </span>
                            <span class="stat-label">Member Since</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">
                                <?php echo $user['verified'] ? 'Verified' : 'Pending'; ?>
                            </span>
                            <span class="stat-label">Status</span>
                        </div>
                    </div>
                </div>
                
                <button class="edit-profile-btn" id="editProfileBtn">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
            </div>
        </div>

        <!-- Cards Container -->
        <div class="cards-container">
            <!-- Biography Card -->
            <div class="card bio-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-circle"></i>
                        Biography
                    </h2>
                </div>
                <div class="card-body">
                    <div class="bio-content">
                        <?php if (!empty($user['bio'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        <?php else: ?>
                            <p class="no-bio">No biography available. Click "Edit Profile" to add one.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="card password-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-lock"></i>
                        Change Password
                    </h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="password-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-input">
                                <input type="password" id="current_password" name="current_password" required>
                                <button type="button" class="toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-input">
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="modal-form">
                <div class="form-group">
                    <label for="edit_name">Full Name</label>
                    <input type="text" id="edit_name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="edit_position">Position</label>
                    <input type="text" id="edit_position" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" placeholder="e.g., Barangay Secretary">
                </div>

                <div class="form-group">
                    <label for="edit_bio">Biography</label>
                    <textarea id="edit_bio" name="bio" rows="5" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelEdit">
                        Cancel
                    </button>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Modal functionality
        const editBtn = document.getElementById('editProfileBtn');
        const modal = document.getElementById('editProfileModal');
        const closeBtn = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelEdit');

        editBtn.addEventListener('click', () => {
            modal.style.display = 'block';
        });

        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        <?php if (isset($_SESSION['toast_message'])): ?>
            Toastify({
                text: "<?php echo $_SESSION['toast_message']; ?>",
                duration: 5000,
                gravity: "top",
                position: "right",
                backgroundColor: "<?php echo $_SESSION['toast_type'] === 'success' ? '#28a745' : '#dc3545'; ?>",
                stopOnFocus: true,
            }).showToast();
            <?php
                unset($_SESSION['toast_message']);
                unset($_SESSION['toast_type']);
            ?>
        <?php endif; ?>
    </script>
</body>

</html>