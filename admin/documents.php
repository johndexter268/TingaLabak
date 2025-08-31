<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new document request
    if (isset($_POST['add_document_request'])) {
        $firstname = trim($_POST['firstname']);
        $middlename = trim($_POST['middlename']);
        $lastname = trim($_POST['lastname']);
        $gender = trim($_POST['gender']);
        $dob = trim($_POST['dob']);
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $civil_status = trim($_POST['civil_status']);
        $sector = trim($_POST['sector']);
        $years_of_residency = trim($_POST['years_of_residency']);
        $zip_code = trim($_POST['zip_code']);
        $province = trim($_POST['province']);
        $city_municipality = trim($_POST['city_municipality']);
        $barangay = trim($_POST['barangay']);
        $purok_sitio_street = trim($_POST['purok_sitio_street']);
        $subdivision = trim($_POST['subdivision']);
        $house_number = trim($_POST['house_number']);
        $document_type = trim($_POST['document_type']);
        $purpose_of_request = trim($_POST['purpose_of_request']);
        $requesting_for_self = isset($_POST['requesting_for_self']) ? 1 : 0;
        $proof_of_identity = '';

        if (isset($_FILES['proof_of_identity']) && $_FILES['proof_of_identity']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $proof_of_identity = $upload_dir . basename($_FILES['proof_of_identity']['name']);
            move_uploaded_file($_FILES['proof_of_identity']['tmp_name'], $proof_of_identity);
        }

        $stmt = $pdo->prepare("INSERT INTO document_requests (firstname, middlename, lastname, gender, dob, contact, email, civil_status, sector, years_of_residency, zip_code, province, city_municipality, barangay, purok_sitio_street, subdivision, house_number, document_type, purpose_of_request, requesting_for_self, proof_of_identity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$firstname, $middlename, $lastname, $gender, $dob, $contact, $email, $civil_status, $sector, $years_of_residency, $zip_code, $province, $city_municipality, $barangay, $purok_sitio_street, $subdivision, $house_number, $document_type, $purpose_of_request, $requesting_for_self, $proof_of_identity, 'Pending']);

        $_SESSION['toast_message'] = "Document request added successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Update document request
    if (isset($_POST['update_document_request'])) {
        $request_id = $_POST['request_id'];
        $firstname = trim($_POST['firstname']);
        $middlename = trim($_POST['middlename']);
        $lastname = trim($_POST['lastname']);
        $gender = trim($_POST['gender']);
        $dob = trim($_POST['dob']);
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $civil_status = trim($_POST['civil_status']);
        $sector = trim($_POST['sector']);
        $years_of_residency = trim($_POST['years_of_residency']);
        $zip_code = trim($_POST['zip_code']);
        $province = trim($_POST['province']);
        $city_municipality = trim($_POST['city_municipality']);
        $barangay = trim($_POST['barangay']);
        $purok_sitio_street = trim($_POST['purok_sitio_street']);
        $subdivision = trim($_POST['subdivision']);
        $house_number = trim($_POST['house_number']);
        $document_type = trim($_POST['document_type']);
        $purpose_of_request = trim($_POST['purpose_of_request']);
        $requesting_for_self = isset($_POST['requesting_for_self']) ? 1 : 0;
        $proof_of_identity = trim($_POST['existing_proof_of_identity']);

        if (isset($_FILES['proof_of_identity']) && $_FILES['proof_of_identity']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $proof_of_identity = $upload_dir . basename($_FILES['proof_of_identity']['name']);
            move_uploaded_file($_FILES['proof_of_identity']['tmp_name'], $proof_of_identity);
        }

        $stmt = $pdo->prepare("UPDATE document_requests SET firstname = ?, middlename = ?, lastname = ?, gender = ?, dob = ?, contact = ?, email = ?, civil_status = ?, sector = ?, years_of_residency = ?, zip_code = ?, province = ?, city_municipality = ?, barangay = ?, purok_sitio_street = ?, subdivision = ?, house_number = ?, document_type = ?, purpose_of_request = ?, requesting_for_self = ?, proof_of_identity = ? WHERE request_id = ?");
        $stmt->execute([$firstname, $middlename, $lastname, $gender, $dob, $contact, $email, $civil_status, $sector, $years_of_residency, $zip_code, $province, $city_municipality, $barangay, $purok_sitio_street, $subdivision, $house_number, $document_type, $purpose_of_request, $requesting_for_self, $proof_of_identity, $request_id]);

        $_SESSION['toast_message'] = "Document request updated successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Delete document request
    if (isset($_POST['delete_document_request'])) {
        $request_id = $_POST['request_id'];

        $stmt = $pdo->prepare("SELECT proof_of_identity FROM document_requests WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($request['proof_of_identity'] && file_exists($request['proof_of_identity'])) {
            unlink($request['proof_of_identity']);
        }

        $stmt = $pdo->prepare("DELETE FROM document_requests WHERE request_id = ?");
        $stmt->execute([$request_id]);

        $_SESSION['toast_message'] = "Document request deleted successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Archive document request
    if (isset($_POST['archive_document_request'])) {
        $request_id = $_POST['request_id'];
        $stmt = $pdo->prepare("UPDATE document_requests SET is_archived = 1 WHERE request_id = ?");
        $stmt->execute([$request_id]);

        $_SESSION['toast_message'] = "Document request archived successfully!";
        $_SESSION['toast_type'] = "success";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE is_archived = 0";
$params = [];
if (!empty($search)) {
    $where_clause .= " AND (firstname LIKE ? OR lastname LIKE ? OR document_type LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$count_sql = "SELECT COUNT(*) FROM document_requests $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM document_requests $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Barangay Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/documents.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="page-header">
            <div class="page-header-buttons">
                <!-- <button class="btn btn-primary add-btn" id="addRequestBtn">
                    <i class="fas fa-plus"></i>
                    <span>Add Request</span>
                </button> -->
                <a href="create_document.php" class="btn btn-primary create-btn">
                    <i class="fas fa-file-alt"></i>
                    <span>Create Document</span>
                </a>
            </div>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search document requests..."
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
                <table class="documents-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Document Type</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr class="no-data">
                                <td colspan="5">
                                    <div class="no-data-content">
                                        <i class="fas fa-file-alt"></i>
                                        <h3>No document requests found</h3>
                                        <p><?php echo !empty($search) ? 'No document requests match your search criteria.' : 'No document requests have been added yet.'; ?></p>
                                        <?php if (empty($search)): ?>
                                            <button class="btn btn-primary" onclick="document.getElementById('addRequestBtn').click()">
                                                <i class="fas fa-plus"></i>
                                                Add First Request
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>
                                        <div class="request-info">
                                            <div class="request-details">
                                                <div class="request-name">
                                                    <?php echo htmlspecialchars($request['firstname'] . ' ' . ($request['middlename'] ? $request['middlename'][0] . '. ' : '') . $request['lastname']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['document_type']); ?></td>
                                    <td><?php echo htmlspecialchars($request['contact'] ?: 'N/A'); ?></td>
                                    <td>
                                        <form class="status-form" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <select name="status"
                                                class="status-select <?php echo strtolower($request['status']); ?>"
                                                onchange="updateStatus(this)">
                                                <option value="Pending" <?php echo $request['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Approved" <?php echo $request['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="Rejected" <?php echo $request['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                <option value="Received" <?php echo $request['status'] === 'Received' ? 'selected' : ''; ?>>Received</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view"
                                                onclick="viewRequest(<?php echo $request['request_id']; ?>)"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-edit"
                                                onclick="editRequest(<?php echo $request['request_id']; ?>)"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete"
                                                onclick="deleteRequest(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname'], ENT_QUOTES); ?>')"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button class="btn-action btn-archive"
                                                onclick="archiveRequest(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname'], ENT_QUOTES); ?>')"
                                                title="Archive">
                                                <i class="fas fa-archive"></i>
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
                            <a href="?<?php $query_params['page'] = 1; echo http_build_query($query_params); ?>" class="pagination-number">1</a>
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
                            <a href="?<?php $query_params['page'] = $total_pages; echo http_build_query($query_params); ?>" class="pagination-number"><?php echo $total_pages; ?></a>
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

    <!-- Add/Edit Document Request Modal -->
    <div class="modal" id="requestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Document Request</h3>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" class="modal-form" id="requestForm" enctype="multipart/form-data">
                <input type="hidden" id="request_id" name="request_id" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First Name <span class="required">*</span></label>
                        <input type="text" id="firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="middlename">Middle Name</label>
                        <input type="text" id="middlename" name="middlename">
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastname" name="lastname" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="dob" name="dob" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="text" id="contact" name="contact">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="civil_status">Civil Status <span class="required">*</span></label>
                        <select id="civil_status" name="civil_status" required>
                            <option value="">Select Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sector">Sector</label>
                        <input type="text" id="sector" name="sector">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="years_of_residency">Years of Residency <span class="required">*</span></label>
                        <input type="number" id="years_of_residency" name="years_of_residency" required>
                    </div>
                    <div class="form-group">
                        <label for="zip_code">Zip Code <span class="required">*</span></label>
                        <input type="text" id="zip_code" name="zip_code" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="province">Province <span class="required">*</span></label>
                        <input type="text" id="province" name="province" required>
                    </div>
                    <div class="form-group">
                        <label for="city_municipality">City/Municipality <span class="required">*</span></label>
                        <input type="text" id="city_municipality" name="city_municipality" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="barangay">Barangay <span class="required">*</span></label>
                        <input type="text" id="barangay" name="barangay" required>
                    </div>
                    <div class="form-group">
                        <label for="purok_sitio_street">Purok/Sitio/Street</label>
                        <input type="text" id="purok_sitio_street" name="purok_sitio_street">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="subdivision">Subdivision</label>
                        <input type="text" id="subdivision" name="subdivision">
                    </div>
                    <div class="form-group">
                        <label for="house_number">House Number</label>
                        <input type="text" id="house_number" name="house_number">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="document_type">Document Type <span class="required">*</span></label>
                        <input type="text" id="document_type" name="document_type" required>
                    </div>
                    <div class="form-group">
                        <label for="purpose_of_request">Purpose of Request <span class="required">*</span></label>
                        <textarea id="purpose_of_request" name="purpose_of_request" rows="4" required></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="requesting_for_self">Requesting for Self</label>
                        <input type="checkbox" id="requesting_for_self" name="requesting_for_self">
                    </div>
                    <div class="form-group">
                        <label for="proof_of_identity">Proof of Identity</label>
                        <input type="file" id="proof_of_identity" name="proof_of_identity" accept="image/*">
                        <input type="hidden" id="existing_proof_of_identity" name="existing_proof_of_identity">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" name="add_document_request" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Add Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Document Request Modal -->
    <div class="modal" id="viewRequestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Document Request Details</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="request-details-view">
                    <p><strong>First Name:</strong> <span id="view_firstname"></span></p>
                    <p><strong>Middle Name:</strong> <span id="view_middlename"></span></p>
                    <p><strong>Last Name:</strong> <span id="view_lastname"></span></p>
                    <p><strong>Gender:</strong> <span id="view_gender"></span></p>
                    <p><strong>Date of Birth:</strong> <span id="view_dob"></span></p>
                    <p><strong>Contact Number:</strong> <span id="view_contact"></span></p>
                    <p><strong>Email:</strong> <span id="view_email"></span></p>
                    <p><strong>Civil Status:</strong> <span id="view_civil_status"></span></p>
                    <p><strong>Sector:</strong> <span id="view_sector"></span></p>
                    <p><strong>Years of Residency:</strong> <span id="view_years_of_residency"></span></p>
                    <p><strong>Zip Code:</strong> <span id="view_zip_code"></span></p>
                    <p><strong>Province:</strong> <span id="view_province"></span></p>
                    <p><strong>City/Municipality:</strong> <span id="view_city_municipality"></span></p>
                    <p><strong>Barangay:</strong> <span id="view_barangay"></span></p>
                    <p><strong>Purok/Sitio/Street:</strong> <span id="view_purok_sitio_street"></span></p>
                    <p><strong>Subdivision:</strong> <span id="view_subdivision"></span></p>
                    <p><strong>House Number:</strong> <span id="view_house_number"></span></p>
                    <p><strong>Document Type:</strong> <span id="view_document_type"></span></p>
                    <p><strong>Purpose of Request:</strong> <span id="view_purpose_of_request"></span></p>
                    <p><strong>Requesting for Self:</strong> <span id="view_requesting_for_self"></span></p>
                    <p><strong>Proof of Identity:</strong></p>
                    <div id="view_proof_of_identity_container"></div>
                </div>
            </div>
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
                <p class="delete-text">Are you sure you want to delete the document request for <strong id="deleteRequestName"></strong>?</p>
                <p class="delete-warning">This action cannot be undone.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="request_id" id="deleteRequestId">
                    <input type="hidden" name="delete_document_request" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <div class="modal" id="archiveModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>Confirm Archive</h3>
                <button class="modal-close" onclick="closeArchiveModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="delete-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <p class="delete-text">Are you sure you want to archive the document request for <strong id="archiveRequestName"></strong>?</p>
                <p class="delete-warning">This will hide the request from the active list.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeArchiveModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="archiveForm">
                    <input type="hidden" name="request_id" id="archiveRequestId">
                    <input type="hidden" name="archive_document_request" value="1">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-archive"></i>
                        Archive
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
<script>
    const modal = document.getElementById('requestModal');
    const viewModal = document.getElementById('viewRequestModal');
    const deleteModal = document.getElementById('deleteModal');
    const archiveModal = document.getElementById('archiveModal');
    const addBtn = document.getElementById('addRequestBtn');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    addBtn.addEventListener('click', () => {
        resetForm();
        modalTitle.textContent = 'Add New Document Request';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Request';
        submitBtn.setAttribute('name', 'add_document_request');
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
        } else if (e.target === viewModal) {
            viewModal.style.display = 'none';
        } else if (e.target === deleteModal) {
            deleteModal.style.display = 'none';
        } else if (e.target === archiveModal) {
            archiveModal.style.display = 'none';
        }
    });

    function resetForm() {
        document.getElementById('requestForm').reset();
        document.getElementById('request_id').value = '';
        document.getElementById('existing_proof_of_identity').value = '';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Request';
        submitBtn.setAttribute('name', 'add_document_request');
    }

    function editRequest(id) {
        fetch(`get_document_request.php?request_id=${id}`)
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

                document.getElementById('request_id').value = data.request_id;
                document.getElementById('firstname').value = data.firstname || '';
                document.getElementById('middlename').value = data.middlename || '';
                document.getElementById('lastname').value = data.lastname || '';
                document.getElementById('gender').value = data.gender || '';
                document.getElementById('dob').value = data.dob || '';
                document.getElementById('contact').value = data.contact || '';
                document.getElementById('email').value = data.email || '';
                document.getElementById('civil_status').value = data.civil_status || '';
                document.getElementById('sector').value = data.sector || '';
                document.getElementById('years_of_residency').value = data.years_of_residency || '';
                document.getElementById('zip_code').value = data.zip_code || '';
                document.getElementById('province').value = data.province || '';
                document.getElementById('city_municipality').value = data.city_municipality || '';
                document.getElementById('barangay').value = data.barangay || '';
                document.getElementById('purok_sitio_street').value = data.purok_sitio_street || '';
                document.getElementById('subdivision').value = data.subdivision || '';
                document.getElementById('house_number').value = data.house_number || '';
                document.getElementById('document_type').value = data.document_type || '';
                document.getElementById('purpose_of_request').value = data.purpose_of_request || '';
                document.getElementById('requesting_for_self').checked = data.requesting_for_self == 1;
                document.getElementById('existing_proof_of_identity').value = data.proof_of_identity || '';

                modalTitle.textContent = 'Edit Document Request';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Request';
                submitBtn.setAttribute('name', 'update_document_request');
                modal.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load document request data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function viewRequest(id) {
        fetch(`get_document_request.php?request_id=${id}`)
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

                document.getElementById('view_firstname').textContent = data.firstname || 'N/A';
                document.getElementById('view_middlename').textContent = data.middlename || 'N/A';
                document.getElementById('view_lastname').textContent = data.lastname || 'N/A';
                document.getElementById('view_gender').textContent = data.gender || 'N/A';
                document.getElementById('view_dob').textContent = data.dob ? new Date(data.dob).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                document.getElementById('view_contact').textContent = data.contact || 'N/A';
                document.getElementById('view_email').textContent = data.email || 'N/A';
                document.getElementById('view_civil_status').textContent = data.civil_status || 'N/A';
                document.getElementById('view_sector').textContent = data.sector || 'N/A';
                document.getElementById('view_years_of_residency').textContent = data.years_of_residency || 'N/A';
                document.getElementById('view_zip_code').textContent = data.zip_code || 'N/A';
                document.getElementById('view_province').textContent = data.province || 'N/A';
                document.getElementById('view_city_municipality').textContent = data.city_municipality || 'N/A';
                document.getElementById('view_barangay').textContent = data.barangay || 'N/A';
                document.getElementById('view_purok_sitio_street').textContent = data.purok_sitio_street || 'N/A';
                document.getElementById('view_subdivision').textContent = data.subdivision || 'N/A';
                document.getElementById('view_house_number').textContent = data.house_number || 'N/A';
                document.getElementById('view_document_type').textContent = data.document_type || 'N/A';
                document.getElementById('view_purpose_of_request').textContent = data.purpose_of_request || 'N/A';
                document.getElementById('view_requesting_for_self').textContent = data.requesting_for_self == 'Yes' ? 'Yes' : 'No';
                const proofContainer = document.getElementById('view_proof_of_identity_container');
                proofContainer.innerHTML = data.proof_of_identity ? `<img src="${data.proof_of_identity}" alt="Proof of Identity" class="request-image">` : 'No proof of identity available';

                viewModal.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load document request data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function updateStatus(selectElement) {
        const requestId = selectElement.parentElement.querySelector('input[name="request_id"]').value;
        const newStatus = selectElement.value;

        // Update the select element's class to reflect the new status
        selectElement.classList.remove('pending', 'approved', 'rejected', 'received');
        selectElement.classList.add(newStatus.toLowerCase());

        // Send AJAX request to update status
        fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `request_id=${encodeURIComponent(requestId)}&status=${encodeURIComponent(newStatus)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toastify({
                    text: data.message,
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#28a745",
                    stopOnFocus: true,
                }).showToast();
            } else {
                throw new Error(data.error || 'Failed to update status');
            }
        })
        .catch(error => {
            Toastify({
                text: error.message || "Failed to update status",
                duration: 5000,
                gravity: "top",
                position: "right",
                backgroundColor: "#dc3545",
                stopOnFocus: true,
            }).showToast();
            // Revert the select value if update fails
            selectElement.value = selectElement.dataset.originalValue || 'Pending';
            selectElement.classList.remove('pending', 'approved', 'rejected', 'received');
            selectElement.classList.add((selectElement.dataset.originalValue || 'Pending').toLowerCase());
        });

        // Store the current value as the original value for potential revert
        selectElement.dataset.originalValue = newStatus;
    }

    function closeViewModal() {
        document.getElementById('viewRequestModal').style.display = 'none';
    }

    function deleteRequest(id, name) {
        document.getElementById('deleteRequestId').value = id;
        document.getElementById('deleteRequestName').textContent = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    function archiveRequest(id, name) {
        document.getElementById('archiveRequestId').value = id;
        document.getElementById('archiveRequestName').textContent = name;
        document.getElementById('archiveModal').style.display = 'block';
    }

    function closeArchiveModal() {
        document.getElementById('archiveModal').style.display = 'none';
    }

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