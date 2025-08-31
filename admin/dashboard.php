<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'];

$stmt = $pdo->query("SELECT resident_id, name, sex, classification, contact_number FROM residents LIMIT 5");
$residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT message_id, name, contact_number, email_address, subject, created_at, read_status FROM messages LIMIT 4");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT request_id, firstname, middlename, lastname, document_type, purpose_of_request, is_archived, status 
                     FROM document_requests 
                     WHERE is_archived = 0 
                     LIMIT 4");

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Tinga Labak</title>
    <link rel="icon" href="../imgs/brgy-logo.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/home.css">
</head>

<body>
    <?php include 'sidebar/sidebar.php'; ?>
    <section class="content-body">
        <div class="cards-container">
            <div class="card messages-card">
                <div class="card-header">
                    <h2 class="card-title">Messages</h2>
                    <button class="view-all-btn" onclick="window.location.href='messages.php'">
                        <i class="fas fa-comment"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Sender</th>
                                    <th>Subject</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="2">No messages found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr>
                                            <td class="<?php echo $message['read_status'] == 0 ? 'unread' : ''; ?>">
                                                <a class="sender-name" href="messages.php"><?php echo htmlspecialchars($message['name']); ?></a>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($message['subject']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php
                        $unread_count = $pdo->query("SELECT COUNT(*) FROM messages WHERE read_status = 0")->fetchColumn();
                        if ($unread_count > 0): ?>
                            <span class="badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card requests-card">
                <div class="card-header">
                    <h2 class="card-title">Requests</h2>
                    <button class="view-all-btn" onclick="window.location.href='documents.php'">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>Resident</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="2">No requests found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['document_type']); ?></td>
                                            <td><?php echo htmlspecialchars(trim($request['firstname'] . ' ' . $request['middlename'] . ' ' . $request['lastname'])); ?></td>
                                            <td><?php echo htmlspecialchars($request['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-full-width residents-card">
                <div class="card-header">
                    <h2 class="card-title">Residents</h2>
                    <button class="view-all-btn" onclick="window.location.href='residents.php'">
                        <i class="fa-solid fa-people-group"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Classification</th>
                                    <th>Gender</th>
                                    <th>Contact Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($residents)): ?>
                                    <tr>
                                        <td colspan="4">No residents found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($residents as $resident): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($resident['name']); ?></td>
                                            <td><?php echo htmlspecialchars($resident['classification']); ?></td>
                                            <td><?php echo htmlspecialchars($resident['sex']); ?></td>
                                            <td><?php echo htmlspecialchars($resident['contact_number']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <a class="btn btn-primary" href="residents.php">View All</a>
            </div>
        </div>
    </section>
    </main>
</body>

</html>