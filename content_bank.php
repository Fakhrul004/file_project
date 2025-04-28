<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get all content with user details
$sql = "
    SELECT c.*, u.name as user_name, u.username,
           DATE_FORMAT(c.created_at, '%d %M %Y %H:%i') as formatted_date
    FROM content c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Bank</title>
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
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .content-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .content-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .content-preview {
            width: 100%;
            height: 200px;
            background: #e0e0e0;
            position: relative;
            overflow: hidden;
        }
        
        .content-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .content-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .content-info {
            padding: 15px;
        }
        
        .content-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .content-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .content-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #666;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .user-username {
            font-size: 12px;
            color: #666;
        }
        
        .content-date {
            font-size: 12px;
            color: #666;
        }
        
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 13px;
            transition: var(--transition);
            margin-top: 10px;
        }
        
        .download-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .download-btn svg {
            width: 16px;
            height: 16px;
        }
        
        .no-content {
            text-align: center;
            padding: 40px;
            color: #666;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Content Bank</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="content-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="content-card">
                        <div class="content-preview">
                            <?php if ($row['file_type'] === 'image'): ?>
                                <img src="../uploads/content/<?= htmlspecialchars($row['file_path']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                            <?php else: ?>
                                <video controls>
                                    <source src="../uploads/content/<?= htmlspecialchars($row['file_path']) ?>" type="video/<?= pathinfo($row['file_path'], PATHINFO_EXTENSION) ?>">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        </div>
                        
                        <div class="content-info">
                            <div class="content-title"><?= htmlspecialchars($row['title']) ?></div>
                            <div class="content-description"><?= nl2br(htmlspecialchars($row['description'])) ?></div>
                            
                            <div class="content-meta">
                                <div class="user-info">
                                    <span class="user-name"><?= htmlspecialchars($row['user_name']) ?></span>
                                    <span class="user-username"><?= htmlspecialchars($row['username']) ?></span>
                                </div>
                                <span class="content-date"><?= $row['formatted_date'] ?></span>
                            </div>
                            
                            <a href="../uploads/content/<?= htmlspecialchars($row['file_path']) ?>" download class="download-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-content">
                <p>No content available.</p>
            </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html> 