<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle content deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $content_id = $_GET['delete'];
    
    // Verify the content belongs to the current user
    $check_sql = "SELECT file_path FROM content WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $content_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = '../uploads/content/' . $row['file_path'];
        
        // Delete the file
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $delete_sql = "DELETE FROM content WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $content_id, $user_id);
        
        if ($delete_stmt->execute()) {
            $message = "Content deleted successfully!";
        } else {
            $error = "Error deleting content.";
        }
        $delete_stmt->close();
    } else {
        $error = "Content not found or you don't have permission to delete it.";
    }
    $check_stmt->close();
}

// Fetch user's content
$sql = "SELECT * FROM content WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$content_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Content</title>
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
        
        .error {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .content-item {
            background-color: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .content-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .content-media {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .content-info {
            padding: 15px;
        }
        
        .content-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .content-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .content-date {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        
        .content-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .content-actions a {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .view-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .view-btn:hover {
            background-color: #0056b3;
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state p {
            margin-bottom: 20px;
        }
        
        .upload-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--success-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .upload-btn:hover {
            background-color: #218838;
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
        
        .checked-badge {
            display: inline-block;
            background: #198754;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            margin-bottom: 8px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Content</h2>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (empty($content_items)): ?>
            <div class="empty-state">
                <p>You haven't uploaded any content yet.</p>
                <a href="upload_content.php" class="upload-btn">Upload Content</a>
            </div>
        <?php else: ?>
            <div class="content-grid">
                <?php foreach ($content_items as $item): ?>
                    <div class="content-item">
                        <?php if ($item['file_type'] === 'image'): ?>
                            <img src="../uploads/content/<?= htmlspecialchars($item['file_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="content-media">
                        <?php else: ?>
                            <video src="../uploads/content/<?= htmlspecialchars($item['file_path']) ?>" class="content-media" controls></video>
                        <?php endif; ?>
                        
                        <div class="content-info">
                            <?php if (!empty($item['checked_by_admin'])): ?>
                                <span class="checked-badge">Checked by Admin</span>
                            <?php endif; ?>
                            <h3 class="content-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="content-description"><?= htmlspecialchars($item['description']) ?></p>
                            <div class="content-date">Uploaded: <?= date('M d, Y', strtotime($item['created_at'])) ?></div>
                            
                            <div class="content-actions">
                                <a href="../uploads/content/<?= htmlspecialchars($item['file_path']) ?>" target="_blank" class="view-btn">View</a>
                                <a href="?delete=<?= $item['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this content?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html> 