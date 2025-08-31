<?php
session_start();
require 'config/db_config.php';

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll(PDO::FETCH_ASSOC);

$officials = $pdo->query("SELECT * FROM brgy_officials WHERE status = 'Active' ORDER BY official_id ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $contact = filter_input(INPUT_POST, 'contact', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (name, contact_number, email_address, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $contact, $email, $subject, $message]);
        $success = "Message sent successfully!";
    } catch (PDOException $e) {
        $error = "Failed to send message: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_request'])) {
    $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
    $middlename = filter_input(INPUT_POST, 'middlename', FILTER_SANITIZE_STRING);
    $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $contact = filter_input(INPUT_POST, 'contact', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $civil_status = filter_input(INPUT_POST, 'civil_status', FILTER_SANITIZE_STRING);
    $sector = filter_input(INPUT_POST, 'sector', FILTER_SANITIZE_STRING);
    $years_of_residency = filter_input(INPUT_POST, 'years_of_residency', FILTER_SANITIZE_NUMBER_INT);
    $zip_code = filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_STRING);
    $province = filter_input(INPUT_POST, 'province', FILTER_SANITIZE_STRING);
    $city_municipality = filter_input(INPUT_POST, 'city_municipality', FILTER_SANITIZE_STRING);
    $barangay = filter_input(INPUT_POST, 'barangay', FILTER_SANITIZE_STRING);
    $purok_sitio_street = filter_input(INPUT_POST, 'purok_sitio_street', FILTER_SANITIZE_STRING);
    $subdivision = filter_input(INPUT_POST, 'subdivision', FILTER_SANITIZE_STRING);
    $house_number = filter_input(INPUT_POST, 'house_number', FILTER_SANITIZE_STRING);
    $document_type = filter_input(INPUT_POST, 'document_type', FILTER_SANITIZE_STRING);
    $purpose_of_request = filter_input(INPUT_POST, 'purpose_of_request', FILTER_SANITIZE_STRING);
    $requesting_for_self = filter_input(INPUT_POST, 'requesting_for_self', FILTER_SANITIZE_STRING);

    // Initialize error array
    $errors = [];

    // Validate required fields
    if (empty($requesting_for_self)) {
        $errors[] = "Requesting for self is required.";
    }

    // Handle file upload for proof of identity
    $proof_of_identity = null;
    if (isset($_FILES['proof_of_identity']) && $_FILES['proof_of_identity']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['proof_of_identity']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'admin/uploads/validID/';
            $dbUploadDir = 'uploads/validID/'; // Path to store in DB (without admin/)

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid() . '-' . basename($_FILES['proof_of_identity']['name']);
            $uploadPath = $uploadDir . $fileName;
            $dbPath = $dbUploadDir . $fileName;

            // Validate file type (e.g., allow only images and PDFs)
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            $fileType = mime_content_type($_FILES['proof_of_identity']['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid file type for proof of identity. Only JPEG, PNG, and PDF are allowed.";
            } elseif ($_FILES['proof_of_identity']['size'] > 5 * 1024 * 1024) { // 5MB limit
                $errors[] = "Proof of identity file is too large. Maximum size is 5MB.";
            } else {
                // Move the uploaded file
                if (move_uploaded_file($_FILES['proof_of_identity']['tmp_name'], $uploadPath)) {
                    $proof_of_identity = $dbPath;
                } else {
                    $errors[] = "Failed to upload proof of identity.";
                }
            }
        } else {
            $errors[] = "Error uploading proof of identity: " . $_FILES['proof_of_identity']['error'];
        }
    } else {
        $errors[] = "Proof of identity is required.";
    }

    // Proceed with database insertion if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO document_requests (
                firstname, middlename, lastname, gender, dob, contact, email, civil_status, sector,
                years_of_residency, zip_code, province, city_municipality, barangay, purok_sitio_street,
                subdivision, house_number, document_type, purpose_of_request, requesting_for_self, proof_of_identity, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $firstname,
                $middlename,
                $lastname,
                $gender,
                $dob,
                $contact,
                $email,
                $civil_status,
                $sector,
                $years_of_residency,
                $zip_code,
                $province,
                $city_municipality,
                $barangay,
                $purok_sitio_street,
                $subdivision,
                $house_number,
                $document_type,
                $purpose_of_request,
                $requesting_for_self,
                $proof_of_identity
            ]);
            $success = "Document request submitted successfully!";
        } catch (PDOException $e) {
            $error = "Failed to submit document request: " . $e->getMessage();
        }
    } else {
        $error = implode(" ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Tinga Labak - Official Website</title>
    <link rel="stylesheet" href="home.css">
    <link rel="icon" href="imgs/brgy-logo.png" type="image/jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Permanent+Marker&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=assignment,health_and_safety" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css">

</head>

<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="container">
            <span class="header-text">
                Open Hours of Barangay Tinga Labak <strong>Mon - Fri: 8.00 am - 6.00 pm</strong>
            </span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <div class="nav-content">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="imgs/brgy-logo.png" alt="Barangay Logo">
                    </div>
                </div>
                <ul class="nav-menu" id="nav-menu">
                    <li class="nav-item"><a href="#home" class="nav-link">Home</a></li>
                    <li class="nav-item dropdown-item">
                        <a href="#about" class="nav-link" data-toggle="dropdown">
                            About <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a href="#introduction" class="dropdown-link">Introduction</a>
                            <a href="#history" class="dropdown-link">History</a>
                            <a href="#population" class="dropdown-link">Population</a>
                            <a href="#geography" class="dropdown-link">Geography</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown-item">
                        <a href="#officials" class="nav-link" data-toggle="dropdown">
                            Official <i class="fas fa-chevron-down dropdown-icon"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a href="#barangay-council" class="dropdown-link">Barangay Council</a>
                            <a href="#sk" class="dropdown-link">Sangguniang Kabataan (SK)</a>
                        </div>
                    </li>
                    <li class="nav-item"><a href="#calendar" class="nav-link">Schedules</a></li>
                    <li class="nav-item"><a href="#announcements" class="nav-link">Announcements</a></li>
                    <li class="nav-item"><a href="#contactus" class="nav-link">Contact</a></li>
                </ul>
                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <span class="hamburger-icon">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </span>
                    <span class="close-icon"><i class="fa-solid fa-xmark"></i></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-background"></div>
        <div class="hero-overlay"></div>
        <div>
            <div class="hero-content">
                <h1 class="hero-title">
                    <span class="heading-sm">Welcome to</span><br>
                    <span class="highlight-text">Barangay Tinga Labak</span>
                </h1>
                <p class="hero-description">
                    A progressive community, dedicated to genuine service to enrich the lives of its residents through good governance.
                </p>
                <div class="hero-buttons">
                    <a href="#about" class="btn btn-primary">Learn More</a>
                    <a href="#calendar" class="btn btn-outline">Calendar of Activities</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-header">
                <h6 class="section-subtitle">Barangay Tinga Labak</h6>
                <h2 class="section-title">Services</h2>
                <div class="section-divider"></div>
            </div>
            <div class="service-filters">
                <button class="filter-btn active" data-filter="permits">Permits</button>
                <button class="filter-btn" data-filter="certificates">Certificates</button>
                <button class="filter-btn" data-filter="assistance">Assistance</button>
            </div>
            <div class="service-grid" id="service-grid">
                <!-- Permits -->
                <?php
                $permits = [
                    "Electrical Connection",
                    "Permit to Cut Tree",
                    "Permit to Construct",
                    "Permit to Work",
                    "Clearance to Sell"
                ];
                foreach ($permits as $permit) {
                    echo "<a href='#document-request' class='service-card' data-category='permits' onclick='openDocumentRequest(\"$permit\")'>";
                    echo "<span class='service-text'>$permit</span>";
                    echo "</a>";
                }
                ?>
                <!-- Certificates -->
                <?php
                $certificates = [
                    "Indigency",
                    "Residency",
                    "Good Moral",
                    "Relationship",
                    "No Income",
                    "Business Ownership",
                    "No Longer Resident",
                    "No Longer Operating",
                    "Death Certificate",
                    "Scholarship",
                    "Job Seeker"
                ];
                foreach ($certificates as $certificate) {
                    echo "<a href='#document-request' class='service-card hidden' data-category='certificates' onclick='openDocumentRequest(\"$certificate\")'>";
                    echo "<span class='service-text'>$certificate</span>";
                    echo "</a>";
                }
                ?>
                <!-- Assistance -->
                <?php
                $assistances = ["Mayor's Assistant", "Solo Parent", "Livelihood Assistant"];
                foreach ($assistances as $assistance) {
                    echo "<a href='#document-request' class='service-card hidden' data-category='assistance' onclick='openDocumentRequest(\"$assistance\")'>";
                    echo "<span class='service-text'>$assistance</span>";
                    echo "</a>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Document Request Form Section -->
    <section class="form-section" id="document-request">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Document Request Form</h2>
                <div class="section-divider"></div>
            </div>
            <div class="progress-bar">
                <div class="progress-step active" data-step="1">1<span class="progress-step-title">Personal Information</span></div>
                <div class="progress-step" data-step="2">2<span class="progress-step-title">Contact & Address</span></div>
                <div class="progress-step" data-step="3">3<span class="progress-step-title">Request Details</span></div>
            </div>
            <form class="contact-form" id="document-request-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="document_request" value="1">
                <input type="hidden" name="document_type" id="document_type">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step-1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname" class="form-label required">First Name</label>
                            <input type="text" id="firstname" name="firstname" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" class="form-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastname" class="form-label required">Last Name</label>
                            <input type="text" id="lastname" name="lastname" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="gender" class="form-label required">Gender</label>
                            <select id="gender" name="gender" class="form-input" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dob" class="form-label required">Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="civil_status" class="form-label required">Civil Status</label>
                            <select id="civil_status" name="civil_status" class="form-input" required>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Living in">Living in</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-navigation">
                        <div></div>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)">Next</button>
                    </div>
                </div>
                <!-- Step 2: Contact and Address -->
                <div class="form-step" id="step-2">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact" class="form-label required">Contact Number</label>
                            <input type="tel" id="contact" name="contact" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label required">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="province" class="form-label required">Province</label>
                            <input type="text" id="province" name="province" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="city_municipality" class="form-label required">City/Municipality</label>
                            <input type="text" id="city_municipality" name="city_municipality" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="barangay" class="form-label required">Barangay</label>
                            <input type="text" id="barangay" name="barangay" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="purok_sitio_street" class="form-label">Purok/Sitio/Street</label>
                            <input type="text" id="purok_sitio_street" name="purok_sitio_street" class="form-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subdivision" class="form-label">Subdivision</label>
                            <input type="text" id="subdivision" name="subdivision" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="house_number" class="form-label">House Number</label>
                            <input type="text" id="house_number" name="house_number" class="form-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code" class="form-label required">Zip Code</label>
                            <input type="text" id="zip_code" name="zip_code" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="years_of_residency" class="form-label required">Years of Residency</label>
                            <input type="number" id="years_of_residency" name="years_of_residency" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-outline" onclick="prevStep(1)">Previous</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next</button>
                    </div>
                </div>
                <!-- Step 3: Request Details -->
                <div class="form-step" id="step-3">
                    <div class="form-group">
                        <label for="sector" class="form-label required">Sector</label>
                        <select id="sector" name="sector" class="form-input" required>
                            <option value="Solo Parent">Solo Parent</option>
                            <option value="PWD">PWD</option>
                            <option value="Youth">Youth</option>
                            <option value="Senior Citizen">Senior Citizen</option>
                            <option value="Indigent indigenous people">Indigent indigenous people</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="purpose_of_request" class="form-label required">Purpose of Request</label>
                        <textarea id="purpose_of_request" name="purpose_of_request" class="form-textarea" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="requesting_for_self" class="form-label required">Requesting for Self</label>
                        <select id="requesting_for_self" name="requesting_for_self" class="form-input" required>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="proof_of_identity" class="form-label required">Valid ID/Proof of Identity (Upload)</label>
                        <input type="file" id="proof_of_identity" name="proof_of_identity" class="form-input" accept="image/jpeg,image/png,application/pdf" required>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-outline" onclick="prevStep(2)">Previous</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Quick Links Section -->
    <section class="quick-links" id="quick-links">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Quick Links Guide</h2>
                <div class="section-divider"></div>
            </div>
            <div class="quick-links-wrapper">
                <div class="quick-sidebar">
                    <button class="quick-tab active" data-content="disclosure">
                        <span class="material-symbols-outlined">assignment</span>
                        Disclosure Board
                    </button>
                    <button class="quick-tab" data-content="emergency">
                        <span class="material-symbols-outlined">health_and_safety</span>
                        Emergency Hotlines
                    </button>
                </div>
                <div class="quick-content">
                    <div class="quick-panel active" id="disclosure-panel">
                        <h3 class="panel-title">Disclosure Board</h3>
                        <div class="disclosure-grid">
                            <div class="disclosure-item">
                                <div class="disclosure-icon"><i class="fas fa-calculator"></i></div>
                                <span>Accounting Department</span>
                            </div>
                            <div class="disclosure-item">
                                <div class="disclosure-icon"><i class="fas fa-gavel"></i></div>
                                <span>Legal Department</span>
                            </div>
                            <div class="disclosure-item">
                                <div class="disclosure-icon"><i class="fas fa-shopping-cart"></i></div>
                                <span>Procurement Department</span>
                            </div>
                            <div class="disclosure-item">
                                <div class="disclosure-icon"><i class="fas fa-users"></i></div>
                                <span>Sangguniang Kabataan</span>
                            </div>
                        </div>
                    </div>
                    <div class="quick-panel" id="emergency-panel">
                        <h3 class="panel-title">Emergency Hotlines</h3>
                        <div class="emergency-grid">
                            <div class="emergency-item">
                                <div class="emergency-icon"><i class="fas fa-phone-alt"></i></div>
                                <span>National Emergency Hotline: 911</span>
                            </div>
                            <div class="emergency-item">
                                <div class="emergency-icon"><i class="fas fa-phone-alt"></i></div>
                                <span>Batangas City Emergency Hotline: (043) 980-4033</span>
                            </div>
                            <div class="emergency-item">
                                <div class="emergency-icon"><i class="fas fa-phone-alt"></i></div>
                                <span>Tinga Labak Emergency Hotline: (043) 123-4567</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Calendar Section -->
    <div class="section-header">
        <h2 class="section-title">Calendar of Activities</h2>
        <div class="section-divider"></div>
    </div>
    <section class="calendar-section" id="calendar">
        <div class="container">
            <div class="calendar-wrapper">
                <div id="calendar"></div>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="announcements" id="announcements">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Latest Announcements</h2>
                <div class="section-divider"></div>
            </div>
            <div class="announcements-grid" id="announcements-grid">
                <?php
                if (empty($announcements)) {
                    echo "<p>No announcements available.</p>";
                } else {
                    $count = 0;
                    foreach ($announcements as $announcement) {
                        $count++;
                        $date = date('F d, Y', strtotime($announcement['announcement_date']));
                        $image = $announcement['image'] ? $announcement['image'] : 'https://images.pexels.com/photos/6129507/pexels-photo-6129507.jpeg?auto=compress&cs=tinysrgb&w=800';
                        $hiddenClass = $count > 3 ? 'hidden' : '';
                        echo "
                    <article class='announcement-card $hiddenClass' data-id='{$announcement['id']}' onclick='showAnnouncementModal({$announcement['id']}, \"{$announcement['title']}\", \"{$date}\", \"{$announcement['content']}\", \"{$image}\")'>
                        <div class='card-image'>
                            <img src='admin/$image' alt='{$announcement['title']}'>
                            <div class='card-overlay'></div>
                        </div>
                        <div class='card-content'>
                            <time class='card-date'>$date</time>
                            <h3 class='card-title'>{$announcement['title']}</h3>
                            <p class='card-excerpt'>" . substr($announcement['content'], 0, 100) . "...</p>
                        </div>
                    </article>";
                    }
                }
                ?>
            </div>
            <div class="announcements-footer">
                <?php if (count($announcements) > 3): ?>
                    <button class="btn btn-primary" id="announcements-toggle-btn" onclick="toggleAnnouncements()">
                        View All Announcements
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Officials Section -->
    <section class="officials" id="officials">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Barangay Officials</h2>
                <div class="section-divider"></div>
            </div>
            <div class="officials-grid">
                <?php
                if (empty($officials)) {
                    echo "<p>No officials available.</p>";
                } else {
                    foreach ($officials as $official) {
                        $avatar = $official['avatar'] ? $official['avatar'] : 'imgs/sample.png';
                        echo "
                        <div class='official-card'>
                            <div class='official-avatar'>
                                <img src='admin/$avatar' alt='{$official['name']}'>
                            </div>
                            <div class='official-info'>
                                <h3 class='official-name'>{$official['name']}</h3>
                                <p class='official-position'>{$official['position']}</p>
                            </div>
                        </div>";
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section id="mapp" class="map-section">
        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d30999.208769021392!2d121.0582923030932!3d13.784835372988384!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd0fe27c6ece29%3A0x1f83a38b5842272!2sTingga%20Labac%2C%20Batangas!5e0!3m2!1sen!2sph!4v1745055395443!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contactus">
        <div class="container contact-wrapper">
            <div class="contact-image">
                <img src="imgs/bg.jpg" alt="Contact Us">
            </div>
            <div class="contact-form-wrapper">
                <div class="section-header">
                    <h2 class="section-title">Message Us</h2>
                    <div class="section-divider"></div>
                </div>
                <form class="contact-form" method="POST">
                    <input type="hidden" name="contact_form" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" id="name" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="contact" class="form-label">Contact Number</label>
                            <input type="tel" id="contact" name="contact" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="message" class="form-label">Message</label>
                        <textarea id="message" name="message" class="form-textarea" rows="6" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-paper-plane"></i> SEND MESSAGE
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <img src="imgs/brgy-logo.png" alt="Barangay Logo">
                        </div>
                        <h3 class="footer-title">Barangay Tinga Labak</h3>
                    </div>
                    <p class="footer-description">
                        A strong and united barangay thrives through the shared commitment of its leaders and citizens—working together with integrity, compassion, and a genuine dedication to community service.
                    </p>
                </div>
                <div class="footer-section">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#officials">Officials</a></li>
                        <li><a href="#calendar">Schedules</a></li>
                        <li><a href="#announcements">Announcements</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4 class="footer-heading">Services</h4>
                    <ul class="footer-links">
                        <li><a href="#services">Permits</a></li>
                        <li><a href="#services">Certificates</a></li>
                        <li><a href="#services">Assistance</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4 class="footer-heading">Contact</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Tinga Labak, Batangas City</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>(043) 772-1863</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>barangaytingalabac@gmail.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span>Mon-Fri: 8:00 AM - 6:00 PM</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-divider"></div>
                <p class="footer-copyright">&copy; 2025 Barangay Tinga Labak. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modal -->
    <div class="modal" id="modal">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="modal-title"></h2>
                    <button class="modal-close" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="modal-body"></div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
    <script>
        // Global Variables
        let mobileMenuOpen = false;
        let currentServiceFilter = 'permits';
        let currentQuickTab = 'disclosure';
        let currentStep = 1;

        // DOM Elements
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const navMenu = document.getElementById('nav-menu');
        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');
        const documentRequestSection = document.getElementById('document-request');
        const documentRequestForm = document.getElementById('document-request-form');

        // Toastify Notifications
        <?php if (isset($success)): ?>
            Toastify({
                text: "<?php echo $success; ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#22c55e",
                stopOnFocus: true,
            }).showToast();
        <?php endif; ?>
        <?php if (isset($error)): ?>
            Toastify({
                text: "<?php echo $error; ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#ef4444",
                stopOnFocus: true,
            }).showToast();
        <?php endif; ?>

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeNavigation();
            initializeServiceFilters();
            initializeQuickLinks();
            initializeCalendar();
            initializeSmoothScrolling();
            initializeScrollEffects();
        });

        // Navigation Functions
        function initializeNavigation() {
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', toggleMobileMenu);
            }
            const dropdownToggles = document.querySelectorAll('.nav-link[data-toggle="dropdown"]');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        const parent = this.parentElement;
                        const dropdownMenu = parent.querySelector('.dropdown-menu');
                        const isActive = dropdownMenu.classList.contains('active');
                        document.querySelectorAll('.dropdown-menu').forEach(menu => {
                            if (menu !== dropdownMenu) {
                                menu.classList.remove('active');
                                menu.parentElement.querySelector('.nav-link').classList.remove('active');
                            }
                        });
                        dropdownMenu.classList.toggle('active');
                        this.classList.toggle('active');
                    }
                });
            });
            const navLinks = document.querySelectorAll('.nav-link:not([data-toggle="dropdown"])');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (mobileMenuOpen) {
                        toggleMobileMenu();
                    }
                });
            });
            const dropdownLinks = document.querySelectorAll('.dropdown-link');
            dropdownLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (mobileMenuOpen) {
                        toggleMobileMenu();
                    }
                });
            });
            document.addEventListener('click', (e) => {
                if (mobileMenuOpen && !navMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    toggleMobileMenu();
                }
            });
        }

        function toggleMobileMenu() {
            mobileMenuOpen = !mobileMenuOpen;
            navMenu.classList.toggle('active');
            mobileMenuBtn.classList.toggle('active');
            document.body.style.overflow = mobileMenuOpen ? 'hidden' : '';
        }

        // Service Filters
        function initializeServiceFilters() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const serviceCards = document.querySelectorAll('.service-card');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const filter = btn.dataset.filter;
                    currentServiceFilter = filter;
                    serviceCards.forEach(card => {
                        if (card.dataset.category === filter) {
                            card.classList.remove('hidden');
                            card.style.animationDelay = `${Array.from(serviceCards).indexOf(card) * 50}ms`;
                            card.style.animation = 'fadeInUp 0.6s ease forwards';
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                });
            });
        }

        // Quick Links Functions
        function initializeQuickLinks() {
            const quickTabs = document.querySelectorAll('.quick-tab');
            const quickPanels = document.querySelectorAll('.quick-panel');
            quickTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    quickTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    const content = tab.dataset.content;
                    currentQuickTab = content;
                    quickPanels.forEach(panel => {
                        if (panel.id === `${content}-panel`) {
                            panel.classList.add('active');
                        } else {
                            panel.classList.remove('active');
                        }
                    });
                });
            });
        }

        // Calendar Functions
        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (calendarEl && window.FullCalendar) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek'
                    },
                    events: [
                        <?php
                        foreach ($events as $event) {
                            echo "{
                                title: '" . addslashes($event['title']) . "',
                                date: '" . $event['event_date'] . "',
                                description: '" . addslashes($event['description']) . "'
                            },";
                        }
                        ?>
                    ],
                    eventClick: function(info) {
                        showEventModal(info.event);
                    }
                });
                calendar.render();
            }
        }

        // Modal Functions
        function showEventModal(event) {
            modalTitle.textContent = event.title;
            modalBody.innerHTML = `
                <div style="margin-bottom: 1rem;">
                    <strong>Date:</strong> ${event.start.toLocaleDateString()}
                </div>
                <div>
                    <strong>Description:</strong> ${event.extendedProps.description}
                </div>
            `;
            showModal();
        }

        function showAnnouncementModal(id, title, date, content, image) {
            modalTitle.textContent = title;
            modalBody.innerHTML = `
                <div style="margin-bottom: 1rem;">
                    <img src="admin/${image}" alt="${title}" style="width: 100%; border-radius: var(--radius-lg);">
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Date:</strong> ${date}
                </div>
                <div>
                    ${content}
                </div>
            `;
            showModal();
        }

        function showModal() {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                modal.querySelector('.modal-dialog').style.animation = 'modalSlideIn 0.3s ease-out';
            }, 10);
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function toggleAnnouncements() {
            const toggleBtn = document.getElementById('announcements-toggle-btn');
            const announcementCards = document.querySelectorAll('.announcement-card');
            const isShowingAll = toggleBtn.textContent.includes('See Less');

            if (isShowingAll) {
                announcementCards.forEach((card, index) => {
                    if (index >= 3) {
                        card.classList.add('hidden');
                        card.style.animation = 'none';
                    }
                });
                toggleBtn.textContent = 'View All Announcements';
            } else {
                announcementCards.forEach((card, index) => {
                    card.classList.remove('hidden');
                    card.style.animation = `fadeInUp 0.6s ease forwards ${index * 50}ms`;
                });
                toggleBtn.textContent = 'See Less';
            }
        }

        // Document Request Functions
        function openDocumentRequest(documentType) {
            document.getElementById('document_type').value = documentType;
            documentRequestSection.classList.add('active');
            currentStep = 1;
            updateFormStep();
            window.scrollTo({
                top: documentRequestSection.getBoundingClientRect().top + window.pageYOffset - 80,
                behavior: 'smooth'
            });
        }

        function nextStep(step) {
            if (validateStep(currentStep)) {
                document.querySelector(`#step-${currentStep}`).classList.remove('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('completed');
                currentStep = step;
                updateFormStep();
            }
        }

        function prevStep(step) {
            document.querySelector(`#step-${currentStep}`).classList.remove('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
            if (currentStep > 1) {
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('completed');
            }
            currentStep = step;
            updateFormStep();
        }

        function updateFormStep() {
            document.querySelector(`#step-${currentStep}`).classList.add('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');
        }

        function validateStep(step) {
            const inputs = document.querySelectorAll(`#step-${step} .form-input[required], #step-${step} .form-textarea[required]`);
            let valid = true;
            inputs.forEach(input => {
                if (input.type === 'file') {
                    // Special handling for file inputs
                    if (!input.files.length) {
                        valid = false;
                        input.style.borderColor = 'var(--error)';
                        Toastify({
                            text: `Please upload a file for ${input.name}`,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#ef4444",
                            stopOnFocus: true,
                        }).showToast();
                    } else {
                        input.style.borderColor = 'var(--gray-200)';
                    }
                } else if (!input.value.trim()) {
                    valid = false;
                    input.style.borderColor = 'var(--error)';
                    Toastify({
                        text: `Please fill out ${input.name} field`,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#ef4444",
                        stopOnFocus: true,
                    }).showToast();
                } else {
                    input.style.borderColor = 'var(--gray-200)';
                }
            });
            return valid;
        }

        function showMoreAnnouncements() {
            alert('More announcements feature would load additional announcement cards here.');
        }

        // Smooth Scrolling
        function initializeSmoothScrolling() {
            const links = document.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!link.closest('.dropdown-menu') || window.innerWidth > 768) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href');
                        const targetElement = document.querySelector(targetId);
                        if (targetElement) {
                            const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 80;
                            window.scrollTo({
                                top: offsetTop,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
        }

        // Scroll Effects
        function initializeScrollEffects() {
            const navbar = document.getElementById('navbar');
            let lastScrollTop = 0;
            window.addEventListener('scroll', () => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (scrollTop > 50) {
                    navbar.style.background = '#042c3c';
                    navbar.style.boxShadow = 'var(--shadow-md)';
                } else {
                    navbar.style.background = 'transparent';
                    navbar.style.boxShadow = 'none';
                }
                lastScrollTop = scrollTop;
            });
        }
    </script>
</body>

</html>