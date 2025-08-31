<?php
// session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../admin/auth/login.php");
    exit;
}

// Define nav items
$nav_items = [
    'Home' => ['link' => 'dashboard.php', 'icon' => 'fas fa-house'],
    'My Profile' => ['link' => 'profile.php', 'icon' => 'fas fa-user'],
    'Barangay Officials' => ['link' => 'officials.php', 'icon' => 'fas fa-users'],
    'Residents' => ['link' => 'residents.php', 'icon' => 'fas fa-user-friends'],
    'Projects' => ['link' => 'projects.php', 'icon' => 'fas fa-project-diagram'],
    'Schedules' => ['link' => 'schedules.php', 'icon' => 'fas fa-calendar'],
    'Announcement' => ['link' => 'announcements.php', 'icon' => 'fas fa-bullhorn'],
    'Documents Request' => ['link' => 'documents.php', 'icon' => 'fas fa-file-alt'],
    'Archives' => ['link' => 'archives.php', 'icon' => 'fas fa-archive'],
    'Activity Log' => ['link' => 'activity.php', 'icon' => 'fas fa-history'],
    'Report' => ['link' => 'report.php', 'icon' => 'fas fa-chart-bar']
];

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = "Documents Request"; 

foreach ($nav_items as $name => $item) {
    if ($current_page === $item['link']) {
        $page_title = $name;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="overlay" onclick="toggleSidebar()"></div>

    <aside class="sidebar" id="sidebar" role="navigation">
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="../imgs/brgy-logo.png" alt="Logo" class="logo">
            </div>
            <div>
                <h2 class="brand-name">Brgy. Tinga Labak</h2>
                <p class="sub-name">Barangay Information Management System</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul class="nav-list">
                <?php
                foreach ($nav_items as $name => $item) {
                    $active_class = ($current_page == $item['link']) ? 'active' : '';
                    $icon = $item['icon'] ?? 'fas fa-circle';
                    echo "<li class='nav-item'><a href='{$item['link']}' class='nav-link $active_class'><i class='nav-icon $icon'></i><span class='nav-text'>$name</span></a></li>";
                }
                ?>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="../admin/auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="content-header">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div class="header-left" style="display: flex; align-items: center; gap: 1rem;">
                    <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="dashboard-title"><?php echo $page_title; ?></h1>
                </div>
                <div style="display: flex; align-items: center;">
                    <span style="margin-right: 1rem; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <div style="width: 40px; height: 40px; background-color: #0b2c3d; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                        <a class="profile-icon" style="text-decoration: none; color: #fff;" href="profile.php"><i class="fas fa-user"></i></a>
                    </div>
                </div>
            </div>
        </header>

    <!-- </main> -->

    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.overlay');
            const body = document.body;

            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');

            if (sidebar.classList.contains('open')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = 'auto';
            }
        };

        function closeSidebarOnNavClick() {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.overlay');
                const body = document.body;

                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                body.style.overflow = 'auto';
            }
        }

        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.overlay');
            const body = document.body;

            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                body.style.overflow = 'auto';
            }
        }

        function handleKeyPress(event) {
            if (event.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.overlay');
                const body = document.body;

                if (sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                    body.style.overflow = 'auto';
                }
            }
        }

        (function() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeSidebar);
            } else {
                initializeSidebar();
            }

            function initializeSidebar() {
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', closeSidebarOnNavClick);
                });

                window.addEventListener('resize', handleResize);
                document.addEventListener('keydown', handleKeyPress);
                handleResize();
                console.log('Sidebar initialized successfully');
            }
        })();
    </script>
</body>
</html>