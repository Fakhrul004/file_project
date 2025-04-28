<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = $_POST['delete_user_id'];
    
    // First delete all reports associated with the user
    $delete_reports = $conn->prepare("DELETE FROM reports WHERE user_id = ?");
    $delete_reports->bind_param("i", $delete_id);
    $delete_reports->execute();
    $delete_reports->close();
    
    // Then delete the user
    $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete_user->bind_param("i", $delete_id);
    $delete_user->execute();
    $delete_user->close();
    
    $message = "User and their reports have been deleted successfully.";
}

// Get all users with their report counts
$sql = "
    SELECT u.*, 
           COUNT(r.id) as report_count,
           MAX(r.created_at) as last_report_date
    FROM users u
    LEFT JOIN reports r ON u.id = r.user_id
    GROUP BY u.id
    ORDER BY u.name ASC
";

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
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark-color);
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Users</h2>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>User Details</th>
                        <th>Reports</th>
                        <th>Last Report</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <span class="user-name"><?= htmlspecialchars($row['name']) ?></span>
                                    <span class="user-username"><?= htmlspecialchars($row['username']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="user-stats">
                                    <span class="stat-item">Total Reports: <span class="stat-count"><?= $row['report_count'] ?></span></span>
                                </div>
                            </td>
                            <td>
                                <div class="user-stats">
                                    <?php if ($row['last_report_date']): ?>
                                        <span class="stat-item"><?= date('d M Y H:i', strtotime($row['last_report_date'])) ?></span>
                                    <?php else: ?>
                                        <span class="stat-item">No reports yet</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their reports.');">
                                    <input type="hidden" name="delete_user_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="delete-btn">Delete User</button>
                                </form>
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