<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new official
    if (isset($_POST['add_official'])) {
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $date_inducted = $_POST['date_inducted'];
        $contact_number = trim($_POST['contact_number']);
        $address = trim($_POST['address']);
        $status = $_POST['status'];

        // Handle avatar upload
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $upload_dir = 'uploads/officials/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar = $upload_dir . uniqid() . '.' . $file_extension;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar)) {
                // File uploaded successfully
            } else {
                $avatar = null;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO brgy_officials (name, position, date_inducted, contact_number, address, avatar, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $position, $date_inducted, $contact_number, $address, $avatar, $status]);

        $_SESSION['toast_message'] = "Official added successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Update official
    if (isset($_POST['update_official'])) {
        $official_id = $_POST['official_id'];
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $date_inducted = $_POST['date_inducted'];
        $contact_number = trim($_POST['contact_number']);
        $address = trim($_POST['address']);
        $status = $_POST['status'];

        // Handle avatar upload
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $upload_dir = 'uploads/officials/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Delete old avatar if exists
            $stmt = $pdo->prepare("SELECT avatar FROM brgy_officials WHERE official_id = ?");
            $stmt->execute([$official_id]);
            $old_official = $stmt->fetch();
            if ($old_official && $old_official['avatar'] && file_exists($old_official['avatar'])) {
                unlink($old_official['avatar']);
            }

            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar = $upload_dir . uniqid() . '.' . $file_extension;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar)) {
                // File uploaded successfully
            } else {
                $avatar = null;
            }
        }

        // Prepare update query
        $query = "UPDATE brgy_officials SET name = ?, position = ?, date_inducted = ?, contact_number = ?, address = ?, status = ?";
        $params = [$name, $position, $date_inducted, $contact_number, $address, $status];
        if ($avatar) {
            $query .= ", avatar = ?";
            $params[] = $avatar;
        }
        $query .= " WHERE official_id = ?";
        $params[] = $official_id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $_SESSION['toast_message'] = "Official updated successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Update official status
    if (isset($_POST['update_status'])) {
        $official_id = $_POST['official_id'];
        $status = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE brgy_officials SET status = ? WHERE official_id = ?");
        $stmt->execute([$status, $official_id]);

        $_SESSION['toast_message'] = "Status updated successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Delete official
    if (isset($_POST['delete_official'])) {
        $official_id = $_POST['official_id'];

        // Get avatar path to delete file
        $stmt = $pdo->prepare("SELECT avatar FROM brgy_officials WHERE official_id = ?");
        $stmt->execute([$official_id]);
        $official = $stmt->fetch();

        if ($official && $official['avatar'] && file_exists($official['avatar'])) {
            unlink($official['avatar']);
        }

        $stmt = $pdo->prepare("DELETE FROM brgy_officials WHERE official_id = ?");
        $stmt->execute([$official_id]);

        $_SESSION['toast_message'] = "Official deleted successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "";
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE name LIKE ? OR position LIKE ? OR contact_number LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$count_sql = "SELECT COUNT(*) FROM brgy_officials $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM brgy_officials $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$officials = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Officials - Tinga Labak</title>
    <link rel="icon" href="../imgs/BatangasCity.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/officials.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>

<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="page-header">
            <button class="btn btn-primary add-btn" id="addOfficialBtn">
                <i class="fas fa-plus"></i>
                <span>Add Official</span>
            </button>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search officials..."
                            class="search-input">
                        <!-- <button type="submit" class="search-btn">Search</button> -->
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
                <table class="officials-table">
                    <thead>
                        <tr>
                            <th>Official</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Date Inducted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($officials)): ?>
                            <tr class="no-data">
                                <td colspan="6">
                                    <div class="no-data-content">
                                        <i class="fas fa-users"></i>
                                        <h3>No officials found</h3>
                                        <p><?php echo !empty($search) ? 'No officials match your search criteria.' : 'No officials have been added yet.'; ?></p>
                                        <?php if (empty($search)): ?>
                                            <button class="btn btn-primary" onclick="document.getElementById('addOfficialBtn').click()">
                                                <i class="fas fa-plus"></i>
                                                Add First Official
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($officials as $official): ?>
                                <tr>
                                    <td>
                                        <div class="official-info">
                                            <div class="official-avatar">
                                                <?php if ($official['avatar'] && file_exists($official['avatar'])): ?>
                                                    <img src="<?php echo htmlspecialchars($official['avatar']); ?>" alt="<?php echo htmlspecialchars($official['name']); ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-user"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="official-details">
                                                <div class="official-name"><?php echo htmlspecialchars($official['name']); ?></div>
                                                <div class="official-address"><?php echo htmlspecialchars($official['address'] ?: 'No address provided'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="position-badge"><?php echo htmlspecialchars($official['position']); ?></span>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <?php if ($official['contact_number']): ?>
                                                <a href="tel:<?php echo htmlspecialchars($official['contact_number']); ?>" class="contact-link">
                                                    <!-- <i class="fas fa-phone"></i> -->
                                                    <?php echo htmlspecialchars($official['contact_number']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="no-contact">No contact</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="date-inducted"><?php echo date('M d, Y', strtotime($official['date_inducted'])); ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" class="status-form" style="display: inline;">
                                            <input type="hidden" name="official_id" value="<?php echo $official['official_id']; ?>">
                                            <select name="status"
                                                class="status-select <?php echo strtolower($official['status']); ?>"
                                                onchange="this.form.submit()">
                                                <option value="Active" <?php echo $official['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="Inactive" <?php echo $official['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-edit"
                                                onclick="editOfficial(<?php echo $official['official_id']; ?>)"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete"
                                                onclick="deleteOfficial(<?php echo $official['official_id']; ?>, '<?php echo htmlspecialchars($official['name'], ENT_QUOTES); ?>')"
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

                    // Previous button
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

                    <?php
                    // Next button
                    if ($page < $total_pages):
                        $query_params['page'] = $page + 1;
                    ?>
                        <a href="?<?php echo http_build_query($query_params); ?>" class="pagination-btn pagination-next">
                            Next
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </section>

    <!-- Add/Edit Official Modal -->
    <div class="modal" id="officialModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Official</h3>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="modal-form" id="officialForm">
                <input type="hidden" id="official_id" name="official_id" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="position">Position <span class="required">*</span></label>
                        <select id="position" name="position" required>
                            <option value="">Select Position</option>
                            <option value="Barangay Captain">Barangay Captain</option>
                            <option value="Barangay Kagawad">Barangay Kagawad</option>
                            <option value="Barangay Secretary">Barangay Secretary</option>
                            <option value="Barangay Treasurer">Barangay Treasurer</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_inducted">Date Inducted <span class="required">*</span></label>
                        <input type="date" id="date_inducted" name="date_inducted" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" placeholder="09123456789">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="Complete address"></textarea>
                </div>

                <div class="form-group">
                    <label for="avatar">Profile Photo</label>
                    <div class="file-upload">
                        <input type="file" id="avatar" name="avatar" accept="image/*">
                        <div class="file-upload-display">
                            <div class="file-upload-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="file-upload-text">
                                <span class="file-upload-label">Click to upload photo</span>
                                <span class="file-upload-hint">PNG, JPG up to 5MB</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" name="add_official" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Add Official
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
                <p class="delete-text">Are you sure you want to delete <strong id="deleteOfficialName"></strong>?</p>
                <p class="delete-warning">This action cannot be undone.</p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="official_id" id="deleteOfficialId">
                    <input type="hidden" name="delete_official" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    </main>
    <script>
    const modal = document.getElementById('officialModal');
    const addBtn = document.getElementById('addOfficialBtn');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    addBtn.addEventListener('click', () => {
        resetForm();
        modalTitle.textContent = 'Add New Official';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Official';
        submitBtn.setAttribute('name', 'add_official');
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
        document.getElementById('officialForm').reset();
        document.getElementById('official_id').value = '';
        
        // Reset file upload display
        const fileUploadDisplay = document.querySelector('.file-upload-display');
        fileUploadDisplay.innerHTML = `
            <div class="file-upload-icon">
                <i class="fas fa-camera"></i>
            </div>
            <div class="file-upload-text">
                <span class="file-upload-label">Click to upload photo</span>
                <span class="file-upload-hint">PNG, JPG up to 5MB</span>
            </div>
        `;
        
        // Reset submit button for add mode
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Official';
        submitBtn.setAttribute('name', 'add_official');
    }

    function editOfficial(id) {
        console.log('Editing official with ID:', id); // Debug log
        
        fetch(`get_official.php?official_id=${id}`)
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                console.log('Response headers:', response.headers.get('content-type')); // Debug log
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.log('Non-JSON response:', text); // Debug log
                        throw new Error('Server returned non-JSON response. Check console for details.');
                    });
                }
                
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.error || `HTTP error! status: ${response.status}`);
                    }).catch(() => {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Official data:', data); // Debug log
                
                if (data.error) {
                    throw new Error(data.error);
                }

                // Populate form fields
                document.getElementById('official_id').value = data.official_id;
                document.getElementById('name').value = data.name || '';
                document.getElementById('position').value = data.position || '';
                document.getElementById('date_inducted').value = data.date_inducted || '';
                document.getElementById('status').value = data.status || 'Active';
                document.getElementById('contact_number').value = data.contact_number || '';
                document.getElementById('address').value = data.address || '';

                // Handle avatar preview
                const fileUploadDisplay = document.querySelector('.file-upload-display');
                if (data.avatar && data.avatar.trim() !== '') {
                    fileUploadDisplay.innerHTML = `
                        <div class="file-preview">
                            <img src="${data.avatar}" alt="Preview" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">
                        </div>
                        <div class="file-upload-text">
                            <span class="file-upload-label">Current photo</span>
                            <span class="file-upload-hint">Click to change (PNG, JPG up to 5MB)</span>
                        </div>
                    `;
                } else {
                    fileUploadDisplay.innerHTML = `
                        <div class="file-upload-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="file-upload-text">
                            <span class="file-upload-label">Click to upload photo</span>
                            <span class="file-upload-hint">PNG, JPG up to 5MB</span>
                        </div>
                    `;
                }

                // Update modal title and submit button
                modalTitle.textContent = 'Edit Official';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Official';
                submitBtn.setAttribute('name', 'update_official');
                
                // Show modal
                modal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error); // Debug log
                Toastify({
                    text: error.message || "Failed to load official data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function deleteOfficial(id, name) {
        document.getElementById('deleteOfficialId').value = id;
        document.getElementById('deleteOfficialName').textContent = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // File upload preview
    document.getElementById('avatar').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const display = document.querySelector('.file-upload-display');
                display.innerHTML = `
                    <div class="file-preview">
                        <img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px;">
                    </div>
                    <div class="file-upload-text">
                        <span class="file-upload-label">${file.name}</span>
                        <span class="file-upload-hint">Click to change</span>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }
    });

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
</body>

</html>