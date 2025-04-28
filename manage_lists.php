<?php
session_start();
include '../includes/db.php';

// Debug session variables
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<!-- Session Debug: ";
print_r($_SESSION);
echo " -->";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle user deletion and admin deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['deactivate_admin_id'])) {
        $deactivate_id = $_POST['deactivate_admin_id'];
        
        // Change admin role to user
        $deactivate_admin = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'admin' AND id != ?");
        $deactivate_admin->bind_param("ii", $deactivate_id, $_SESSION['user_id']);
        $deactivate_admin->execute();
        
        if ($deactivate_admin->affected_rows > 0) {
            $message = "Admin account has been deactivated to regular user.";
        } else {
            $message = "Error: Cannot deactivate your own admin account.";
        }
        $deactivate_admin->close();
    }
    
    if (isset($_POST['delete_user_id'])) {
        $delete_id = $_POST['delete_user_id'];
        
        // Prevent deleting your own account
        if ($delete_id == $_SESSION['user_id']) {
            $message = "Error: Cannot delete your own account.";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // First delete all submissions associated with the user
                $delete_submissions = $conn->prepare("DELETE FROM submissions WHERE user_id = ?");
                $delete_submissions->bind_param("i", $delete_id);
                $delete_submissions->execute();
                $delete_submissions->close();
                
                // Delete all reports associated with the user
                $delete_reports = $conn->prepare("DELETE FROM reports WHERE user_id = ?");
                $delete_reports->bind_param("i", $delete_id);
                $delete_reports->execute();
                $delete_reports->close();
                
                // Delete all comments made by the user (if they were an admin)
                $delete_comments = $conn->prepare("DELETE FROM comments WHERE admin_id = ?");
                $delete_comments->bind_param("i", $delete_id);
                $delete_comments->execute();
                $delete_comments->close();
                
                // Finally delete the user
                $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_user->bind_param("i", $delete_id);
                $delete_user->execute();
                $delete_user->close();
                
                // If everything is successful, commit the transaction
                $conn->commit();
                $message = "User and all associated data have been deleted successfully.";
                
            } catch (Exception $e) {
                // If there's an error, rollback the changes
                $conn->rollback();
                $message = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle filter selection
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Get all users with their report counts, filtered by role if needed
$sql = "
    SELECT u.*, 
           COUNT(r.id) as report_count,
           (SELECT COUNT(mr.id) FROM monthly_reports mr WHERE mr.user_id = u.id) as monthly_report_count,
           MAX(r.created_at) as last_report_date
    FROM users u
    LEFT JOIN reports r ON u.id = r.user_id
";
if ($role_filter === 'admin') {
    $sql .= "WHERE u.role = 'admin'\n";
} elseif ($role_filter === 'user') {
    $sql .= "WHERE u.role = 'user'\n";
}
$sql .= "GROUP BY u.id\nORDER BY u.role DESC, u.name ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
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
            font-size: 24px;
        }
        
        .message {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .user-table th,
        .user-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .user-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .user-table tr:last-child td {
            border-bottom: none;
        }
        
        .user-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .user-username {
            font-size: 13px;
            color: #666;
        }
        
        .user-stats {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .stat-item {
            font-size: 13px;
            color: #666;
        }
        
        .stat-count {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: var(--white);
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: var(--transition);
        }
        
        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
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
        
        .no-users {
            text-align: center;
            padding: 40px;
            color: #666;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .admin-badge {
            display: inline-block;
            background-color: #6f42c1;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            vertical-align: middle;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(111, 66, 193, 0.2);
        }
        
        .current-user-row {
            background-color: rgba(111, 66, 193, 0.1) !important;
            position: relative;
            border-left: 4px solid #198754;
        }
        
        .current-user-badge {
            display: inline-block;
            background-color: #198754;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 8px;
            vertical-align: middle;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(25, 135, 84, 0.2);
        }
        
        .current-user-row:hover {
            background-color: rgba(111, 66, 193, 0.15) !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Users</h2>
        <form method="GET" style="margin-bottom: 20px; text-align: right;">
            <label for="role">Filter by Role:</label>
            <select name="role" id="role" onchange="this.form.submit()">
                <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Users</option>
            </select>
        </form>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Error:') === 0 ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>User Details</th>
                        <th>Reports</th>
                        <th>Monthly Reports</th>
                        <th>Last Report</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="<?= $row['id'] === $_SESSION['user_id'] ? 'current-user-row' : '' ?>">
                            <td>
                                <div class="user-info">
                                    <span class="user-name">
                                        <?= htmlspecialchars($row['name']) ?>
                                        <?php if ($row['role'] === 'admin'): ?>
                                            <span class="admin-badge">Admin</span>
                                        <?php endif; ?>
                                        <?php if ($row['id'] === $_SESSION['user_id']): ?>
                                            <span class="current-user-badge">Current Account</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="user-username"><?= htmlspecialchars($row['username']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="user-stats">
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="stat-item">Administrator Account</span>
                                    <?php else: ?>
                                        <span class="stat-item">Total Reports: <span class="stat-count"><?= $row['report_count'] ?></span></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="user-stats">
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="stat-item">Administrator Account</span>
                                    <?php else: ?>
                                        <span class="stat-item">Total Monthly: <span class="stat-count"><?= $row['monthly_report_count'] ?></span></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="user-stats">
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="stat-item">N/A</span>
                                    <?php else: ?>
                                        <?php if ($row['last_report_date']): ?>
                                            <span class="stat-item"><?= date('d M Y H:i', strtotime($row['last_report_date'])) ?></span>
                                        <?php else: ?>
                                            <span class="stat-item">No reports yet</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this <?= $row['role'] === 'admin' ? 'admin' : 'user' ?>? This will also delete all their reports.');">
                                        <input type="hidden" name="delete_user_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="delete-btn" <?= $row['role'] === 'admin' ? 'style="background-color: #6f42c1;"' : '' ?>>Delete <?= $row['role'] === 'admin' ? 'Admin' : 'User' ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-users">
                <p>No users found.</p>
            </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html> 