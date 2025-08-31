<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new project
    if (isset($_POST['add_project'])) {
        $project_name = trim($_POST['project_name']);
        $description = trim($_POST['description']);
        $budget = trim($_POST['budget']);
        $status = trim($_POST['status']);
        $start_date = trim($_POST['start_date']);
        $location = trim($_POST['location']);

        $stmt = $pdo->prepare("INSERT INTO projects (project_name, description, budget, status, start_date, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$project_name, $description, $budget, $status, $start_date, $location]);

        $_SESSION['toast_message'] = "Project added successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Update project
    if (isset($_POST['update_project'])) {
        $project_id = $_POST['project_id'];
        $project_name = trim($_POST['project_name']);
        $description = trim($_POST['description']);
        $budget = trim($_POST['budget']);
        $status = trim($_POST['status']);
        $start_date = trim($_POST['start_date']);
        $location = trim($_POST['location']);

        $stmt = $pdo->prepare("UPDATE projects SET project_name = ?, description = ?, budget = ?, status = ?, start_date = ?, location = ? WHERE id = ?");
        $stmt->execute([$project_name, $description, $budget, $status, $start_date, $location, $project_id]);

        $_SESSION['toast_message'] = "Project updated successfully!";
        $_SESSION['toast_type'] = "success";
    }

    // Delete project
    if (isset($_POST['delete_project'])) {
        $project_id = $_POST['project_id'];

        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);

        $_SESSION['toast_message'] = "Project deleted successfully!";
        $_SESSION['toast_type'] = "success";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch data for charts
$current_year = date('Y');
$stmt = $pdo->prepare("SELECT MONTH(start_date) as month, COUNT(*) as count FROM projects WHERE YEAR(start_date) = ? GROUP BY MONTH(start_date)");
$stmt->execute([$current_year]);
$projects_per_month = array_fill(1, 12, 0);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $projects_per_month[$row['month']] = $row['count'];
}

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status");
$status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_colors = [];
$colors = ["#3385D6", "#34C759", "#FF9500", "#FF2D55", "#5856D6"];
$index = 0;
foreach ($status_data as $data) {
    $status_colors[$data['status']] = $colors[$index % count($colors)];
    $index++;
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
$total_projects = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(budget) as total_budget FROM projects");
$total_budget = $stmt->fetchColumn();

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "";
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE project_name LIKE ? OR status LIKE ? OR location LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$count_sql = "SELECT COUNT(*) FROM projects $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM projects $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - Barangay Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/projects.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body>
    <?php include 'sidebar/sidebar.php'; ?>

    <section class="content-body">
        <div class="cards-container">
            <div class="card">
                <div class="card-header">
                    <h3>Projects per Month (<?php echo $current_year; ?>)</h3>
                </div>
                <div class="card-body">
                    <div style="margin: 0 auto; width: 100%;">
                        <canvas id="projectsPerMonthChart"></canvas>
                        <script>
                            const ctx1 = document.getElementById('projectsPerMonthChart').getContext('2d');
                            new Chart(ctx1, {
                                type: 'bar',
                                data: {
                                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                    datasets: [{
                                        label: 'Projects',
                                        data: [<?php echo implode(',', $projects_per_month); ?>],
                                        backgroundColor: '#3385D6',
                                        borderColor: '#ffffff',
                                        borderWidth: 2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    }
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>

            <div class="card projects-card">
                <div class="card-header">
                    <h3>Project Statistics</h3>
                </div>
                <div class="card-body stats-grid">
                    <div class="stat-item">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <span class="stat-label">Total Projects</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" data-target="<?php echo $total_projects; ?>">0</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <span class="stat-label">Total Budget</span>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" data-target="<?php echo $total_budget; ?>">0</span>
                        </div>
                    </div>
                    <div class="stat-item full-width">
                        <div style="max-width: 300px; margin: 0 auto;">
                            <canvas id="statusChart"></canvas>
                            <script>
                                const ctx2 = document.getElementById('statusChart').getContext('2d');
                                new Chart(ctx2, {
                                    type: 'doughnut',
                                    data: {
                                        labels: [<?php foreach ($status_data as $data) { echo '"' . htmlspecialchars($data['status']) . '",'; } ?>],
                                        datasets: [{
                                            data: [<?php foreach ($status_data as $data) { echo $data['count'] . ','; } ?>],
                                            backgroundColor: [<?php foreach ($status_data as $data) { echo '"' . $status_colors[$data['status']] . '",'; } ?>],
                                            borderColor: ['#ffffff'],
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
                                                    color: '#1F2A44'
                                                }
                                            }
                                        }
                                    }
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-header">
            <button class="btn btn-primary add-btn" id="addProjectBtn">
                <i class="fas fa-plus"></i>
                <span>Add Project</span>
            </button>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" class="search-form">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search projects..."
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
                <table class="projects-table">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr class="no-data">
                                <td colspan="6">
                                    <div class="no-data-content">
                                        <i class="fas fa-project-diagram"></i>
                                        <h3>No projects found</h3>
                                        <p><?php echo !empty($search) ? 'No projects match your search criteria.' : 'No projects have been added yet.'; ?></p>
                                        <?php if (empty($search)): ?>
                                            <button class="btn btn-primary" onclick="document.getElementById('addProjectBtn').click()">
                                                <i class="fas fa-plus"></i>
                                                Add First Project
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <div class="project-info">
                                            <div class="project-details">
                                                <div class="project-name"><?php echo htmlspecialchars($project['project_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₱<?php echo number_format($project['budget'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($project['status']); ?>">
                                            <?php echo htmlspecialchars($project['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($project['location'] ?: 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view"
                                                onclick="viewProject(<?php echo $project['id']; ?>)"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-edit"
                                                onclick="editProject(<?php echo $project['id']; ?>)"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete"
                                                onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['project_name'], ENT_QUOTES); ?>')"
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

    <!-- Add/Edit Project Modal -->
    <div class="modal" id="projectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Project</h3>
                <button class="modal-close" id="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" class="modal-form" id="projectForm">
                <input type="hidden" id="project_id" name="project_id" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="project_name">Project Name <span class="required">*</span></label>
                        <input type="text" id="project_name" name="project_name" required>
                    </div>
                    <div class="form-group">
                        <label for="budget">Budget <span class="required">*</span></label>
                        <input type="number" id="budget" name="budget" step="0.01" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="Planned">Planned</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Completed</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" name="add_project" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Add Project
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Project Modal -->
    <div class="modal" id="viewProjectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Project Details</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="project-details-view">
                    <p><strong>Name:</strong> <span id="view_project_name"></span></p>
                    <p><strong>Budget:</strong> <span id="view_budget"></span></p>
                    <p><strong>Status:</strong> <span id="view_status"></span></p>
                    <p><strong>Start Date:</strong> <span id="view_start_date"></span></p>
                    <p><strong>Location:</strong> <span id="view_location"></span></p>
                    <p><strong>Description:</strong> <span id="view_description"></span></p>
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
                <p class="delete-text">Are you sure you want to delete <strong id="deleteProjectName"></strong>?</p>
                <p class="delete-warning">This action cannot be undone.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="project_id" id="deleteProjectId">
                    <input type="hidden" name="delete_project" value="1">
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

    const modal = document.getElementById('projectModal');
    const viewModal = document.getElementById('viewProjectModal');
    const addBtn = document.getElementById('addProjectBtn');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    addBtn.addEventListener('click', () => {
        resetForm();
        modalTitle.textContent = 'Add New Project';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Project';
        submitBtn.setAttribute('name', 'add_project');
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
        document.getElementById('projectForm').reset();
        document.getElementById('project_id').value = '';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Project';
        submitBtn.setAttribute('name', 'add_project');
    }

    function editProject(id) {
        fetch(`get_project.php?project_id=${id}`)
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

                document.getElementById('project_id').value = data.id;
                document.getElementById('project_name').value = data.project_name || '';
                document.getElementById('budget').value = data.budget || '';
                document.getElementById('status').value = data.status || '';
                document.getElementById('start_date').value = data.start_date || '';
                document.getElementById('location').value = data.location || '';
                document.getElementById('description').value = data.description || '';

                modalTitle.textContent = 'Edit Project';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Project';
                submitBtn.setAttribute('name', 'update_project');
                modal.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load project data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function viewProject(id) {
        fetch(`get_project.php?project_id=${id}`)
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

                document.getElementById('view_project_name').textContent = data.project_name || 'N/A';
                document.getElementById('view_budget').textContent = data.budget ? `₱${Number(data.budget).toLocaleString('en-US', { minimumFractionDigits: 2 })}` : 'N/A';
                document.getElementById('view_status').textContent = data.status || 'N/A';
                document.getElementById('view_start_date').textContent = data.start_date ? new Date(data.start_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                document.getElementById('view_location').textContent = data.location || 'N/A';
                document.getElementById('view_description').textContent = data.description || 'No description available';

                viewModal.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: error.message || "Failed to load project data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545",
                    stopOnFocus: true,
                }).showToast();
            });
    }

    function closeViewModal() {
        document.getElementById('viewProjectModal').style.display = 'none';
    }

    function deleteProject(id, name) {
        document.getElementById('deleteProjectId').value = id;
        document.getElementById('deleteProjectName').textContent = name;
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