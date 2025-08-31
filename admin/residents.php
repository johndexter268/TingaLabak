<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new resident
    if (isset($_POST['add_resident'])) {
        $name = trim($_POST['name']);
        $sex = trim($_POST['sex']);
        $classification = trim($_POST['classification']);
        $contact_number = trim($_POST['contact_number']);

        $stmt = $pdo->prepare("INSERT INTO residents (name, sex, classification, contact_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $sex, $classification, $contact_number]);

        $_SESSION['toast_message'] = "Resident added successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Update resident
    if (isset($_POST['update_resident'])) {
        $resident_id = $_POST['resident_id'];
        $name = trim($_POST['name']);
        $sex = trim($_POST['sex']);
        $classification = trim($_POST['classification']);
        $contact_number = trim($_POST['contact_number']);

        $stmt = $pdo->prepare("UPDATE residents SET name = ?, sex = ?, classification = ?, contact_number = ? WHERE resident_id = ?");
        $stmt->execute([$name, $sex, $classification, $contact_number, $resident_id]);

        $_SESSION['toast_message'] = "Resident updated successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Delete resident
    if (isset($_POST['delete_resident'])) {
        $resident_id = $_POST['resident_id'];

        $stmt = $pdo->prepare("DELETE FROM residents WHERE resident_id = ?");
        $stmt->execute([$resident_id]);

        $_SESSION['toast_message'] = "Resident deleted successfully!";
        $_SESSION['toast_type'] = "success";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$stmt = $pdo->query("SELECT classification, COUNT(*) as count FROM residents GROUP BY classification");
$classification_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$classification_colors = [];
$colors = ["#3385D6", "#34C759", "#FF9500", "#FF2D55", "#5856D6", "#f4f800ff"];
$index = 0;
foreach ($classification_data as $data) {
    $classification_colors[$data['classification']] = $colors[$index % count($colors)];
    $index++;
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM residents");
$total_population = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM residents WHERE sex = 'Male'");
$total_male = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM residents WHERE sex = 'Female'");
$total_female = $stmt->fetchColumn();

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "";
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE name LIKE ? OR classification LIKE ? OR contact_number LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$count_sql = "SELECT COUNT(*) FROM residents $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM residents $where_clause ORDER BY resident_id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents - Barangay Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/residents.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>

<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="cards-container">
            <div class="card">
                <div class="card-header">
                    <h3>Residents by Classification</h3>
                </div>
                <div class="card-body">
                    <div style="max-width: 400px; margin: 0 auto;">
                        <canvas id="classificationChart"></canvas>
                        <script>
                            const ctx = document.getElementById('classificationChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: [<?php foreach ($classification_data as $data) {
                                                    echo '"' . htmlspecialchars($data['classification']) . '",';
                                                } ?>],
                                    datasets: [{
                                        data: [<?php foreach ($classification_data as $data) {
                                                    echo $data['count'] . ',';
                                                } ?>],
                                        backgroundColor: [<?php foreach ($classification_data as $data) {
                                                                echo '"' . $classification_colors[$data['classification']] . '",';
                                                            } ?>],
                                        borderColor: ["#ffffff"],
                                        borderWidth: 2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'bottom',
                                            labels: {
                                                font: {
                                                    size: 12,
                                                    family: "'Montserrat', sans-serif"
                                                },
                                                color: "#1F2A44"
                                            }
                                        }
                                    }
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>

            <div class="card population-card">
                <div class="card-header">
                    <h3>Population Statistics</h3>
                </div>
                <div class="card-body stats-grid">

                    <!-- Total Population -->
                    <div class="stat-item">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="stat-label">Total Population</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" data-target="<?php echo $total_population; ?>">0</span>
                        </div>
                    </div>

                    <!-- Gender Stats -->
                    <div class="gender-stats">
                        <div class="stat-item">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <i class="fas fa-male"></i>
                                </div>
                                <span class="stat-label">Male</span>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value" data-target="<?php echo $total_male; ?>">0</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <i class="fas fa-female"></i>
                                </div>
                                <span class="stat-label">Female</span>
                            </div>
                            <div class="stat-info">
                                <span class="stat-value" data-target="<?php echo $total_female; ?>">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-header">
            <button class="btn btn-primary add-btn" id="addResidentBtn">
                <i class="fas fa-plus"></i>
                <span>Add Resident</span>
            </button>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search residents..."
                            class="search-input">
                        <?php if (!empty($search)): ?>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="clear-search">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="residents-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Sex</th>
                            <th>Classification</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residents)): ?>
                            <tr class="no-data">
                                <td colspan="5">
                                    <div class="no-data-content">
                                        <i class="fas fa-users"></i>
                                        <h3>No residents found</h3>
                                        <p><?php echo !empty($search) ? 'No residents match your search criteria.' : 'No residents have been added yet.'; ?></p>
                                        <?php if (empty($search)): ?>
                                            <button class="btn btn-primary" onclick="document.getElementById('addResidentBtn').click()">
                                                <i class="fas fa-plus"></i>
                                                Add First Resident
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($residents as $resident): ?>
                                <tr>
                                    <td>
                                        <div class="resident-info">
                                            <div class="resident-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="resident-details">
                                                <div class="resident-name"><?php echo htmlspecialchars($resident['name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="sex-badge <?php echo strtolower($resident['sex']); ?>">
                                            <?php echo htmlspecialchars($resident['sex']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="classification-badge <?php echo strtolower($resident['classification']); ?>">
                                            <?php echo htmlspecialchars($resident['classification']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <?php if ($resident['contact_number']): ?>
                                                <a href="tel:<?php echo htmlspecialchars($resident['contact_number']); ?>" class="contact-link">
                                                    <!-- <i class="fas fa-phone"></i> -->
                                                    <?php echo htmlspecialchars($resident['contact_number']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="no-contact">No contact</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit"
                                                onclick="editResident(<?php echo $resident['resident_id']; ?>)"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete"
                                                onclick="deleteResident(<?php echo $resident['resident_id']; ?>, '<?php echo htmlspecialchars($resident['name'], ENT_QUOTES); ?>')"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="controls-section">
            <div class="results-info">
                <span class="results-count">
                    Showing <?php echo min($offset + 1, $total_records); ?>-<?php echo min($offset + $limit, $total_records); ?>
                    of <?php echo $total_records; ?> results
                </span>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <nav class="pagination">
                    <?php
                    $query_params = $_GET;
                    if ($page > 1):
                        $query_params['page'] = $page - 1;
                    ?>
                        <a href="?<?php echo http_build_query($query_params); ?>" class="pagination-btn pagination-prev">
                            <i class="fas fa-chevron-left"></i>
                            Previous
                        </a>
                    <?php endif; ?>

                    <div class="pagination-numbers">
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        if ($start > 1):
                        ?>
                            <a href="?<?php $query_params['page'] = 1;
                                        echo http_build_query($query_params); ?>" class="pagination-number">1</a>
                            <?php if ($start > 2): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php $query_params['page'] = $i; ?>
                            <a href="?<?php echo http_build_query($query_params); ?>"
                                class="pagination-number <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($end < $total_pages): ?>
                            <?php if ($end < $total_pages - 1): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                            <a href="?<?php $query_params['page'] = $total_pages;
                                        echo http_build_query($query_params); ?>" class="pagination-number"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($page < $total_pages): $query_params['page'] = $page + 1; ?>
                        <a href="?<?php echo http_build_query($query_params); ?>" class="pagination-btn pagination-next">
                            Next
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </section>

    <!-- Add/Edit Resident Modal -->
    <div class="modal" id="residentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Resident</h3>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" class="modal-form" id="residentForm">
                <input type="hidden" id="resident_id" name="resident_id" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="sex">Sex <span class="required">*</span></label>
                        <select id="sex" name="sex" required>
                            <option value="">Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="classification">Classification <span class="required">*</span></label>
                        <input type="text" id="classification" name="classification" required placeholder="e.g., Senior Citizen, PWD, Adult">
                    </div>
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" placeholder="09123456789">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" name="add_resident" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Add Resident
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="delete-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p class="delete-text">Are you sure you want to delete <strong id="deleteResidentName"></strong>?</p>
                <p class="delete-warning">This action cannot be undone.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="resident_id" id="deleteResidentId">
                    <input type="hidden" name="delete_resident" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const counters = document.querySelectorAll('.stat-value');
        const speed = 200;

        counters.forEach(counter => {
            const animate = () => {
                const value = +counter.getAttribute('data-target');
                const current = +counter.innerText.replace(/,/g, '');
                const increment = Math.ceil(value / speed);

                if (current < value) {
                    counter.innerText = (current + increment).toLocaleString();
                    requestAnimationFrame(animate);
                } else {
                    counter.innerText = value.toLocaleString();
                }
            };
            animate();
        });
    });

    const modal = document.getElementById('residentModal');
    const addBtn = document.getElementById('addResidentBtn');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    addBtn.addEventListener('click', () => {
        resetForm();
        modalTitle.textContent = 'Add New Resident';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Resident';
        submitBtn.setAttribute('name', 'add_resident');
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

    function resetForm() {
        document.getElementById('residentForm').reset();
        document.getElementById('resident_id').value = '';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Resident';
        submitBtn.setAttribute('name', 'add_resident');
    }

    function editResident(id) {
        fetch(`get_resident.php?resident_id=${id}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.error || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                document.getElementById('resident_id').value = data.resident_id;
                document.getElementById('name').value = data.name || '';
                document.getElementById('sex').value = data.sex || '';
                document.getElementById('classification').value = data.classification || '';
                document.getElementById('contact_number').value = data.contact_number || '';

                modalTitle.textContent = 'Edit Resident';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Resident';
                submitBtn.setAttribute('name', 'update_resident');
                modal.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load resident data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function deleteResident(id, name) {
        document.getElementById('deleteResidentId').value = id;
        document.getElementById('deleteResidentName').textContent = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Search form auto-submit with debounce
    let searchTimeout;
    document.querySelector('.search-input').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 500);
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

</html>