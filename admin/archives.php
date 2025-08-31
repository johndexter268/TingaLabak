<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete document request
    if (isset($_POST['delete_document_request'])) {
        $request_id = $_POST['request_id'];

        // Delete associated proof of identity
        $stmt = $pdo->prepare("SELECT proof_of_identity FROM document_requests WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($request['proof_of_identity'] && file_exists($request['proof_of_identity'])) {
            unlink($request['proof_of_identity']);
        }

        // Delete associated generated document
        $stmt = $pdo->prepare("SELECT file_path FROM generated_documents WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $generated = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($generated['file_path'] && file_exists($generated['file_path'])) {
            unlink($generated['file_path']);
        }

        // Delete from generated_documents
        $stmt = $pdo->prepare("DELETE FROM generated_documents WHERE request_id = ?");
        $stmt->execute([$request_id]);

        // Delete from document_requests
        $stmt = $pdo->prepare("DELETE FROM document_requests WHERE request_id = ?");
        $stmt->execute([$request_id]);

        $_SESSION['toast_message'] = "Archived document request deleted successfully!";
        $_SESSION['toast_type'] = "success";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE dr.is_archived = 1";
$params = [];
if (! empty($search)) {
    $where_clause .= " AND (dr.firstname LIKE ? OR dr.lastname LIKE ? OR dr.document_type LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$count_sql = "SELECT COUNT(*) FROM document_requests dr $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT dr.*, gd.file_path 
        FROM document_requests dr 
        LEFT JOIN generated_documents gd ON dr.request_id = gd.request_id 
        $where_clause 
        ORDER BY dr.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Documents - Barangay Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/archive.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="page-header">
            <h2 class="page-title">Archived Documents</h2>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search archived documents..."
                            class="search-input">
                        <?php if (! empty($search)): ?>
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
                            <th>Contact Number</th>
                            <th>Document Requested</th>
                            <th>Generated File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr class="no-data">
                                <td colspan="5">
                                    <div class="no-data-content">
                                        <i class="fas fa-archive"></i>
                                        <h3>No archived documents found</h3>
                                        <p><?php echo ! empty($search) ? 'No archived documents match your search criteria.' : 'No documents have been archived yet.'; ?></p>
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
                                    <td><?php echo htmlspecialchars($request['contact'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($request['document_type']); ?></td>
                                    <td>
                                        <?php if ($request['file_path'] && file_exists($request['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($request['file_path']); ?>" 
                                               class="btn btn-primary btn-view-file" 
                                               target="_blank">
                                                <i class="fas fa-file-pdf"></i> View File
                                            </a>
                                        <?php else: ?>
                                            <span>No file available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view"
                                                onclick="viewRequest(<?php echo $request['request_id']; ?>)"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-delete"
                                                onclick="deleteRequest(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname'], ENT_QUOTES); ?>')"
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

    <!-- View Document Request Modal -->
    <div class="modal" id="viewRequestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Archived Document Details</h3>
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
                    <!-- <p><strong>Generated File:</strong></p>
                    <div id="view_file_path_container"></div> -->
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
                <p class="delete-text">Are you sure you want to delete the archived document for <strong id="deleteRequestName"></strong>?</p>
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
</body>
<script>
    const viewModal = document.getElementById('viewRequestModal');
    const deleteModal = document.getElementById('deleteModal');

    function viewRequest(id) {
        fetch(`get_document_request.php?request_id=${id}`)
            .then(response => {
                if (! response.ok) {
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
                document.getElementById('view_requesting_for_self').textContent = data.requesting_for_self == 1 ? 'Yes' : 'No';
                const proofContainer = document.getElementById('view_proof_of_identity_container');
                proofContainer.innerHTML = data.proof_of_identity ? `<img src="${data.proof_of_identity}" alt="Proof of Identity" class="request-image">` : 'No proof of identity available';
                
                // const fileContainer = document.getElementById('view_file_path_container');
                // fileContainer.innerHTML = data.file_path ? `<a href="${data.file_path}" target="_blank" class="btn btn-primary btn-view-file"><i class="fas fa-file-pdf"></i> View Generated File</a>` : 'No generated file available';

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

    function closeViewModal() {
        viewModal.style.display = 'none';
    }

    function deleteRequest(id, name) {
        document.getElementById('deleteRequestId').value = id;
        document.getElementById('deleteRequestName').textContent = name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    window.addEventListener('click', (e) => {
        if (e.target === viewModal) {
            viewModal.style.display = 'none';
        } else if (e.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
    });

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