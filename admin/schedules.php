<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new event
    if (isset($_POST['add_event'])) {
        $title = trim($_POST['title']);
        $event_date = trim($_POST['event_date']);
        $description = trim($_POST['description']);
        $image = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image = $upload_dir . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $image);
        }

        $stmt = $pdo->prepare("INSERT INTO events (title, event_date, description, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $event_date, $description, $image]);

        $_SESSION['toast_message'] = "Event added successfully!";
        $_SESSION['toast_type'] = "success";

        require_once __DIR__ . "/utils.php";
        logActivity(
            $_SESSION['user_id'],
            "Added a new schedule: {$title} (Date: {$event_date}, Description: {$description})"
        );
    }

    // Update event
    if (isset($_POST['update_event'])) {
        $event_id = $_POST['event_id'];
        $title = trim($_POST['title']);
        $event_date = trim($_POST['event_date']);
        $description = trim($_POST['description']);
        $image = trim($_POST['existing_image']);

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image = $upload_dir . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $image);
        }

        $stmt = $pdo->prepare("UPDATE events SET title = ?, event_date = ?, description = ?, image = ? WHERE id = ?");
        $stmt->execute([$title, $event_date, $description, $image, $event_id]);

        $_SESSION['toast_message'] = "Event updated successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Delete event
    if (isset($_POST['delete_event'])) {
        $event_id = $_POST['event_id'];

        $stmt = $pdo->prepare("SELECT image FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($event['image'] && file_exists($event['image'])) {
            unlink($event['image']);
        }

        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);

        $_SESSION['toast_message'] = "Event deleted successfully!";
        $_SESSION['toast_type'] = "success";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch events for calendar
$stmt = $pdo->query("SELECT id, title, event_date FROM events");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$calendar_events = json_encode(array_map(function($event) {
    return [
        'id' => $event['id'],
        'title' => $event['title'],
        'start' => $event['event_date']
    ];
}, $events));

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "";
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE title LIKE ? OR description LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
}

$count_sql = "SELECT COUNT(*) FROM events $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM events $where_clause ORDER BY event_date DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedules - Barangay Tinga Labak</title>
    <link rel="icon" href="brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/schedules.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="calendar-container">
            <div class="card">
                <div class="card-header">
                    <h3>Event Calendar</h3>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var calendarEl = document.getElementById('calendar');
                            var calendar = new FullCalendar.Calendar(calendarEl, {
                                initialView: 'dayGridMonth',
                                events: <?php echo $calendar_events; ?>,
                                eventClick: function(info) {
                                    viewEvent(info.event.id);
                                },
                                height: 'auto',
                                headerToolbar: {
                                    left: 'prev,next today',
                                    center: 'title',
                                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                                }
                            });
                            calendar.render();
                        });
                    </script>
                </div>
            </div>
        </div>

        <div class="page-header">
            <button class="btn btn-primary add-btn" id="addEventBtn">
                <i class="fas fa-plus"></i>
                <span>Add Event</span>
            </button>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search events..."
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
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr class="no-data">
                                <td colspan="3">
                                    <div class="no-data-content">
                                        <i class="fas fa-calendar-alt"></i>
                                        <h3>No events found</h3>
                                        <p><?php echo !empty($search) ? 'No events match your search criteria.' : 'No events have been added yet.'; ?></p>
                                        <?php if (empty($search)): ?>
                                            <button class="btn btn-primary" onclick="document.getElementById('addEventBtn').click()">
                                                <i class="fas fa-plus"></i>
                                                Add First Event
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <div class="event-info">
                                            <div class="event-details">
                                                <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['description']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view"
                                                onclick="viewEvent(<?php echo $event['id']; ?>)"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-edit"
                                                onclick="editEvent(<?php echo $event['id']; ?>)"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete"
                                                onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>')"
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

    <!-- Add/Edit Event Modal -->
    <div class="modal" id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Event</h3>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" class="modal-form" id="eventForm" enctype="multipart/form-data">
                <input type="hidden" id="event_id" name="event_id" value="">
                <input type="hidden" id="existing_image" name="existing_image" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="event_date">Event Date <span class="required">*</span></label>
                        <input type="date" id="event_date" name="event_date" required>
                    </div>
                </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>
                    <!-- <div class="form-group">
                        <label for="image">Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div> -->

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" name="add_event" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Add Event
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Event Modal -->
    <div class="modal" id="viewEventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Event Details</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="event-details-view">
                    <p><strong>Title:</strong> <span id="view_title"></span></p>
                    <p><strong>Event Date:</strong> <span id="view_event_date"></span></p>
                    <p><strong>Description:</strong> <span id="view_description"></span></p>
                    <!-- <p><strong>Image:</strong></p>
                    <div id="view_image_container"></div> -->
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
                <p class="delete-text">Are you sure you want to delete <strong id="deleteEventTitle"></strong>?</p>
                <p class="delete-warning">This action cannot be undone.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <input type="hidden" name="delete_event" value="1">
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
    const modal = document.getElementById('eventModal');
    const viewModal = document.getElementById('viewEventModal');
    const addBtn = document.getElementById('addEventBtn');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    addBtn.addEventListener('click', () => {
        resetForm();
        modalTitle.textContent = 'Add New Event';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Event';
        submitBtn.setAttribute('name', 'add_event');
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
        }
    });

    function resetForm() {
        document.getElementById('eventForm').reset();
        document.getElementById('event_id').value = '';
        document.getElementById('existing_image').value = '';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Event';
        submitBtn.setAttribute('name', 'add_event');
    }

    function editEvent(id) {
        fetch(`get_event.php?event_id=${id}`)
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

                document.getElementById('event_id').value = data.id;
                document.getElementById('title').value = data.title || '';
                document.getElementById('event_date').value = data.event_date || '';
                document.getElementById('description').value = data.description || '';
                document.getElementById('existing_image').value = data.image || '';

                modalTitle.textContent = 'Edit Event';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Event';
                submitBtn.setAttribute('name', 'update_event');
                modal.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load event data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function viewEvent(id) {
        fetch(`get_event.php?event_id=${id}`)
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

                document.getElementById('view_title').textContent = data.title || 'N/A';
                document.getElementById('view_event_date').textContent = data.event_date ? new Date(data.event_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                document.getElementById('view_description').textContent = data.description || 'No description available';
                // const imageContainer = document.getElementById('view_image_container');
                // imageContainer.innerHTML = data.image ? `<img src="${data.image}" alt="Event Image" class="event-image">` : 'No image available';

                viewModal.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load event data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function closeViewModal() {
        document.getElementById('viewEventModal').style.display = 'none';
    }

    function deleteEvent(id, title) {
        document.getElementById('deleteEventId').value = id;
        document.getElementById('deleteEventTitle').textContent = title;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
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