<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "WHERE user_id = ?";
$params = [$user_id];
if (!empty($search)) {
    $where_clause .= " AND (action LIKE ? OR details LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$count_sql = "SELECT COUNT(*) FROM activity_log $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM activity_log $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Barangay Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/activity.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="page-header">
            <h2 class="page-title">Activity Log</h2>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search activity log..."
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

        <div class="activity-container">
            <div class="activity-list">
                <?php if (empty($activities)): ?>
                    <div class="no-activity">
                        <i class="fas fa-history"></i>
                        <h3>No activity found</h3>
                        <p><?php echo !empty($search) ? 'No activities match your search criteria.' : 'No activities have been logged yet.'; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item" 
                             onclick="viewActivity(<?php echo $activity['log_id']; ?>)">
                            <div class="activity-header">
                                <div class="activity-info">
                                    <span class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></span>
                                    <span class="activity-time">
                                        <?php echo date('M d, h:i A', strtotime($activity['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="activity-content">
                                <p class="activity-details">
                                    <?php echo htmlspecialchars(substr($activity['details'], 0, 100)) . (strlen($activity['details']) > 100 ? '...' : ''); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="controls-section">
            <div class="results-info">
                <span class="results-count">
                    Showing <?php echo min($offset + 1, $total_records); ?>-<?php echo min($offset + $limit, $total_records); ?>
                    of <?php echo $total_records; ?> activities
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

    <!-- View Activity Modal -->
    <div class="modal" id="viewActivityModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Activity Details</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="activity-details">
                    <p><strong>Action:</strong> <span id="view_action"></span></p>
                    <p><strong>Details:</strong></p>
                    <div class="activity-text" id="view_details"></div>
                    <p><strong>Created At:</strong> <span id="view_created_at"></span></p>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>
</body>
<script>
    const viewModal = document.getElementById('viewActivityModal');

    function viewActivity(id) {
        fetch(`get_activity.php?log_id=${id}`)
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

                document.getElementById('view_action').textContent = data.action || 'N/A';
                document.getElementById('view_details').textContent = data.details || 'N/A';
                document.getElementById('view_created_at').textContent = data.created_at ? 
                    new Date(data.created_at).toLocaleString('en-US', { 
                        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true 
                    }) : 'N/A';

                viewModal.style.display = 'flex';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load activity data",
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

    window.addEventListener('click', (e) => {
        if (e.target === viewModal) {
            closeViewModal();
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