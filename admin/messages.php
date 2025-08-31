<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

require_once __DIR__ . "/utils.php";
logActivity($_SESSION['user_id'], "Opened messages page.");

// Handle marking message as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $message_id = $_POST['message_id'];
    $stmt = $pdo->prepare("UPDATE messages SET read_status = 1 WHERE message_id = ?");
    $stmt->execute([$message_id]);
    $_SESSION['toast_message'] = "Message marked as read.";
    $_SESSION['toast_type'] = "success";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "";
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE name LIKE ? OR email_address LIKE ? OR subject LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$count_sql = "SELECT COUNT(*) FROM messages $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM messages $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Barangay Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="page-header">
            <h2 class="page-title">Messages</h2>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search messages..."
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

        <div class="messages-container">
            <div class="messages-list">
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <i class="fas fa-envelope-open-text"></i>
                        <h3>No messages found</h3>
                        <p><?php echo !empty($search) ? 'No messages match your search criteria.' : 'No messages have been received yet.'; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item <?php echo $message['read_status'] ? 'read' : 'unread'; ?>" 
                             onclick="viewMessage(<?php echo $message['message_id']; ?>, '<?php echo htmlspecialchars($message['name'], ENT_QUOTES); ?>')">
                            <div class="message-header">
                                <div class="message-sender">
                                    <span class="sender-name"><?php echo htmlspecialchars($message['name']); ?></span>
                                    <span class="message-time">
                                        <?php echo date('M d, h:i A', strtotime($message['created_at'])); ?>
                                    </span>
                                </div>
                                <span class="read-status">
                                    <?php echo $message['read_status'] ? '<i class="fas fa-check-circle"></i> Read' : '<i class="fas fa-circle"></i> Unread'; ?>
                                </span>
                            </div>
                            <div class="message-content">
                                <h4 class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></h4>
                                <p class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message'], 0, 100)) . (strlen($message['message']) > 100 ? '...' : ''); ?>
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
                    of <?php echo $total_records; ?> messages
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

    <!-- View Message Modal -->
    <div class="modal" id="viewMessageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Message Details</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="message-details">
                    <p><strong>Name:</strong> <span id="view_name"></span></p>
                    <p><strong>Contact Number:</strong> <span id="view_contact_number"></span></p>
                    <p><strong>Email Address:</strong> <span id="view_email_address"></span></p>
                    <p><strong>Subject:</strong> <span id="view_subject"></span></p>
                    <p><strong>Message:</strong></p>
                    <div class="message-text" id="view_message"></div>
                    <p><strong>Sent On:</strong> <span id="view_created_at"></span></p>
                    <p><strong>Read Status:</strong> <span id="view_read_status"></span></p>
                </div>
            </div>
            <div class="modal-actions">
                <form method="POST" id="markReadForm" style="display: none;">
                    <input type="hidden" name="message_id" id="markReadId">
                    <input type="hidden" name="mark_as_read" value="1">
                </form>
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>
</body>
<script>
    const viewModal = document.getElementById('viewMessageModal');

    function viewMessage(id, name) {
        fetch(`get_message.php?message_id=${id}`)
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

                document.getElementById('view_name').textContent = data.name || 'N/A';
                document.getElementById('view_contact_number').textContent = data.contact_number || 'N/A';
                document.getElementById('view_email_address').textContent = data.email_address || 'N/A';
                document.getElementById('view_subject').textContent = data.subject || 'N/A';
                document.getElementById('view_message').textContent = data.message || 'N/A';
                document.getElementById('view_created_at').textContent = data.created_at ? 
                    new Date(data.created_at).toLocaleString('en-US', { 
                        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true 
                    }) : 'N/A';
                document.getElementById('view_read_status').textContent = data.read_status ? 'Read' : 'Unread';

                // Mark as read if unread
                if (!data.read_status) {
                    const form = document.getElementById('markReadForm');
                    document.getElementById('markReadId').value = id;
                    const formData = new FormData(form);
                    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                        method: 'POST',
                        body: formData
                    }).then(() => {
                        document.getElementById('view_read_status').textContent = 'Read';
                        document.querySelector(`.message-item[onclick*="${id}"]`).classList.remove('unread');
                        document.querySelector(`.message-item[onclick*="${id}"] .read-status`).innerHTML = '<i class="fas fa-check-circle"></i> Read';
                    });
                }

                viewModal.style.display = 'flex';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load message data",
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