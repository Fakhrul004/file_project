<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$user_id = $_SESSION['user_id'];

// Get user's reports
$sql = "
    SELECT r.*, r.checked_by_admin, DATE_FORMAT(r.report_date, '%d %M %Y') as formatted_date,
           DATE_FORMAT(r.created_at, '%d %M %Y %H:%i') as formatted_created_at,
           c.comment_text, c.updated_at as comment_updated_at,
           a.name as admin_name
    FROM reports r
    LEFT JOIN comments c ON r.id = c.report_id
    LEFT JOIN users a ON c.admin_id = a.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background-color: var(--white);
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }
        
        h2 {
            text-align: left;
            color: var(--dark-color);
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .report-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-align: left;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
            text-align: left;
        }
        
        .report-date {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 16px;
            text-align: left;
        }
        
        .report-meta {
            font-size: 13px;
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
        }
        
        .admin-comment {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .admin-comment h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .comment-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
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
        
        .no-reports {
            text-align: left;
            padding: 40px;
            color: #666;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .no-reports p {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .submit-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .checked-badge {
            display: inline-block;
            background: #198754;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            border-radius: 12px;
            padding: 3px 12px;
            margin-left: 10px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>My Reports</h2>
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 12px;">
                <a href="submit_report.php" class="submit-btn">Submit New Report</a>
                <button onclick="window.location.reload();" class="submit-btn" style="background:#198754;">Refresh</button>
            </div>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="report-card">
                    <div class="report-header">
                        <div>
                            <div class="report-date">Report for <?= $row['formatted_date'] ?>
                                <?php if (!empty($row['checked_by_admin'])): ?>
                                    <span class="checked-badge">Checked by Admin</span>
                                <?php endif; ?>
                            </div>
                            <div class="report-meta">Submitted on <?= $row['formatted_created_at'] ?></div>
                        </div>
                    </div>
                    <div class="report-content">
                        <?= nl2br(htmlspecialchars($row['report_text'])) ?>
                    </div>
                    
                    <?php if ($row['comment_text']): ?>
                        <div class="admin-comment">
                            <h4>Admin Feedback</h4>
                            <div class="comment-box">
                                <div class="comment-meta">
                                    By <?= htmlspecialchars($row['admin_name']) ?> • 
                                    Last updated: <?= date('d M Y H:i', strtotime($row['comment_updated_at'])) ?>
                                </div>
                                <div class="comment-text">
                                    <?= nl2br(htmlspecialchars($row['comment_text'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-reports">
                <p>You haven't submitted any reports yet.</p>
                <a href="submit_report.php" class="submit-btn">Submit Your First Report</a>
            </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html> 