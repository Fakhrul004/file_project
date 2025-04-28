<?php
// Include upload configuration
include '../includes/upload_config.php';

session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// Define constants
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize variables with default values to prevent undefined array key warnings
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Validate input
    if (empty($title)) {
        $error = "Title is required";
    } elseif (strlen($title) > 255) {
        $error = "Title must be less than 255 characters";
    } elseif (!isset($_FILES['content_file']) || $_FILES['content_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a file to upload";
    } else {
        $file = $_FILES['content_file'];
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            $error = "File size must be less than 100MB";
        } else {
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validate file type
            if (in_array($file_type, ALLOWED_IMAGE_TYPES)) {
                $content_type = 'image';
            } elseif (in_array($file_type, ALLOWED_VIDEO_TYPES)) {
                $content_type = 'video';
            } else {
                $error = "Invalid file type. Allowed types: " . implode(', ', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES));
            }
            
            if (empty($error)) {
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/content/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename with original extension
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $unique_filename;
                
                // Additional security checks
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowed_mime_types = [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'video/mp4',
                    'video/webm',
                    'video/quicktime'
                ];
                
                if (!in_array($mime_type, $allowed_mime_types)) {
                    $error = "Invalid file type detected";
                } else {
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Save to database
                        $sql = "INSERT INTO content (user_id, title, description, file_path, file_type) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("issss", $user_id, $title, $description, $unique_filename, $content_type);
                        
                        if ($stmt->execute()) {
                            $message = "Content uploaded successfully!";
                            // Clear form data after successful upload
                            $_POST = array();
                        } else {
                            $error = "Error saving to database";
                            // Delete uploaded file if database insert fails
                            unlink($target_path);
                        }
                        $stmt->close();
                    } else {
                        $error = "Error uploading file. Please try again.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content</title>
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
            max-width: 800px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type="file"] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-input-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: var(--white);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .file-input-button:hover {
            background-color: var(--primary-dark);
        }
        
        .selected-file {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .submit-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
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
        
        .file-info {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .preview-container {
            margin-top: 15px;
            max-width: 300px;
            display: none;
        }
        
        .preview-container img,
        .preview-container video {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        
        .loading::after {
            content: "Uploading...";
            animation: dots 1.5s steps(5, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: "Uploading."; }
            40% { content: "Uploading.."; }
            60% { content: "Uploading..."; }
            80%, 100% { content: "Uploading...."; }
        }
        
        .upload-alert {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Content</h2>
        
        <div class="upload-alert">
            <strong>Note:</strong> If you're uploading large files and encounter errors, please try uploading smaller files or contact your administrator.
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required 
                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                       maxlength="255">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Content File (Image or Video)</label>
                <div class="file-input-wrapper">
                    <div class="file-input-button">Choose File</div>
                    <input type="file" name="content_file" accept="image/*,video/*" required 
                           onchange="handleFileSelect(this)">
                </div>
                <div class="selected-file" id="selected-file"></div>
                <div class="file-info">
                    Allowed types: JPG, JPEG, PNG, GIF, MP4, WEBM, MOV<br>
                    Maximum file size: 100MB
                </div>
                <div class="preview-container" id="preview-container"></div>
            </div>
            
            <div class="loading" id="loading"></div>
            
            <button type="submit" class="submit-btn">Upload Content</button>
        </form>
        
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
    
    <script>
        function handleFileSelect(input) {
            const file = input.files[0];
            const previewContainer = document.getElementById('preview-container');
            const selectedFile = document.getElementById('selected-file');
            
            if (file) {
                selectedFile.textContent = file.name;
                previewContainer.style.display = 'block';
                
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    previewContainer.innerHTML = '';
                    previewContainer.appendChild(img);
                } else if (file.type.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.controls = true;
                    video.src = URL.createObjectURL(file);
                    previewContainer.innerHTML = '';
                    previewContainer.appendChild(video);
                }
            } else {
                selectedFile.textContent = 'No file selected';
                previewContainer.style.display = 'none';
            }
        }
        
        document.getElementById('uploadForm').addEventListener('submit', function() {
            document.getElementById('loading').style.display = 'block';
        });
    </script>
</body>
</html> 