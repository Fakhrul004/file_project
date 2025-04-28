<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report_id'])) {
    $delete_id = $_POST['delete_report_id'];

    $delete_stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    // Prevent resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'], $_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    $comment_text = $_POST['comment'];
    $admin_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO comments (report_id, admin_id, comment_text) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE comment_text = VALUES(comment_text), updated_at = CURRENT_TIMESTAMP");
    $stmt->bind_param("iis", $report_id, $admin_id, $comment_text);
    $stmt->execute();
    $stmt->close();
}

// Handle filters
$where_conditions = [];
$params = [];
$sql = "
    SELECT r.id AS report_id, r.report_text, r.report_date, r.created_at, 
           r.checked_by_admin,
           u.name, u.username, c.comment_text, c.updated_at as comment_updated_at,
           a.name as admin_name
    FROM reports r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN comments c ON r.id = c.report_id
    LEFT JOIN users a ON c.admin_id = a.id
";

// Filter by user
if (!empty($_GET['user_id'])) {
    $where_conditions[] = "r.user_id = ?";
    $params[] = $_GET['user_id'];
}

// Filter by Report Date
if (!empty($_GET['report_date'])) {
    $where_conditions[] = "r.report_date = ?";
    $params[] = $_GET['report_date'];
}

// Filter by Created Date
if (!empty($_GET['created_date'])) {
    $where_conditions[] = "DATE(r.created_at) = ?";
    $params[] = $_GET['created_date'];
}

// Apply filters
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " ORDER BY r.created_at DESC";

echo "<!-- DEBUG SQL: $sql -->";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if (!$result) { echo $conn->error; }

// Get users for filter dropdown (excluding admins)
$users_query = "SELECT id, name, username FROM users WHERE role != 'admin' ORDER BY name";
$users_result = $conn->query($users_query);

// Handle checkbox update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_report_id'])) {
    $report_id = $_POST['check_report_id'];
    $checked = isset($_POST['checked']) ? intval($_POST['checked']) : 0;
    $stmt = $conn->prepare("UPDATE reports SET checked_by_admin = ? WHERE id = ?");
    $stmt->bind_param("ii", $checked, $report_id);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>View Reports</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--white);
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        h2 {
            text-align: center;
            color: var(--dark-color);
            margin-bottom: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--success-color);
            color: var(--white);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
        }

        .download-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .download-btn svg {
            width: 16px;
            height: 16px;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        select, input[type="date"] {
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        button {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .report-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 10px;
            text-align: left;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .report-info {
            font-size: 14px;
            color: #666;
            text-align: left;
        }

        .report-content {
            white-space: pre-wrap;
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            text-align: left;
            margin-top: 10px;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .report-user {
            font-weight: 500;
            color: var(--primary-color);
        }

        .report-date {
            color: #666;
            font-size: 0.9em;
        }

        .delete-btn {
            background-color: var(--danger-color);
            padding: 8px 15px;
            font-size: 13px;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .no-reports {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 16px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }

        .comment-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .comment-form {
            margin-top: 10px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 10px;
            resize: vertical;
            min-height: 80px;
        }

        .comment-form textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }

        .comment-display {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            margin-top: 10px;
        }

        .comment-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .comment-text {
            white-space: pre-wrap;
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
        }

        .comment-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: var(--transition);
        }

        .comment-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .admin-check-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            margin-bottom: 8px;
            float: right;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #198754;
        }
        input:checked + .slider:before {
            transform: translateX(20px);
        }
        .admin-check-label {
            font-size: 13px;
            color: #198754;
            font-weight: 500;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>View Reports</h2>
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 12px;">
                <a href="<?= $export_url ?>" class="download-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download CSV
                </a>
                <button onclick="window.location.reload();" class="submit-btn" style="background:#198754;">Refresh</button>
            </div>
        </div>

        <form method="GET" class="filters">
            <div class="filter-group">
                <label for="user_id">Filter by User:</label>
                <select name="user_id" id="user_id">
                    <option value="">All Users</option>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <option value="<?= $user['id'] ?>" <?= isset($_GET['user_id']) && $_GET['user_id'] == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['username']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="report_date">Filter by Report Date:</label>
                <input type="date" name="report_date" id="report_date" value="<?= $_GET['report_date'] ?? '' ?>">
            </div>

            <div class="filter-group">
                <label for="created_date">Filter by Submission Date:</label>
                <input type="date" name="created_date" id="created_date" value="<?= $_GET['created_date'] ?? '' ?>">
            </div>

            <div class="filter-buttons">
                <button type="submit">Apply Filters</button>
                <button type="button" onclick="window.location.href='view_submissions.php'">Clear Filters</button>
            </div>
        </form>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="report-card">
                    <div class="report-meta">
                        <div class="report-user">
                            <?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['username']) ?>)
                        </div>
                        <div class="report-date">
                            Report Date: <?= htmlspecialchars($row['report_date']) ?><br>
                            Submitted: <?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?>
                        </div>
                        <form method="POST" class="admin-check-switch">
                            <input type="hidden" name="check_report_id" value="<?= $row['report_id'] ?>">
                            <input type="hidden" name="checked" value="0">
                            <label class="switch">
                                <input type="checkbox" name="checked" value="1" <?= $row['checked_by_admin'] ? 'checked' : '' ?> onchange="this.form.submit();">
                                <span class="slider"></span>
                            </label>
                            <span class="admin-check-label">Checked</span>
                        </form>
                    </div>
                    <div class="report-content">
                        <?= nl2br(htmlspecialchars($row['report_text'])) ?>
                    </div>
                    
                    <div class="comment-section">
                        <h4>Admin Comments</h4>
                        <?php if ($row['comment_text']): ?>
                            <div class="comment-display">
                                <div class="comment-meta">
                                    Last updated: <?= date('d M Y H:i', strtotime($row['comment_updated_at'])) ?>
                                </div>
                                <div class="comment-text">
                                    <?= nl2br(htmlspecialchars($row['comment_text'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="report_id" value="<?= $row['report_id'] ?>">
                            <textarea name="comment" placeholder="Add or update your comment here..."><?= htmlspecialchars($row['comment_text'] ?? '') ?></textarea>
                            <button type="submit" class="comment-btn"><?= $row['comment_text'] ? 'Update Comment' : 'Add Comment' ?></button>
                        </form>
                    </div>
                    
                    <form method="POST" style="margin-top: 10px; text-align: right;">
                        <input type="hidden" name="delete_report_id" value="<?= $row['report_id'] ?>">
                        <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this report?')">Delete</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-reports">
                <p>No reports found.</p>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
