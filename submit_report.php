<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Get daily template
$templateResult = $conn->query("SELECT * FROM templates WHERE type = 'daily' AND is_active = 1 LIMIT 1");
$template = $templateResult->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $responses = $_POST['responses'] ?? [];
    $labels = $_POST['labels'] ?? [];
    $report_date = $_POST['report_date'] ?? date('Y-m-d');
    
    // Validate report date
    if (empty($report_date)) {
        $message = "<div class='error'>Please select a report date.</div>";
    } else {
        // Combine responses with their labels
        $report_text = '';
        $has_response = false;
        
        for ($i = 0; $i < count($labels); $i++) {
            if (!empty($labels[$i]) && !empty($responses[$i])) {
                $report_text .= $labels[$i] . ": " . trim($responses[$i]) . "\n\n";
                $has_response = true;
            }
        }
        
        if (!$has_response) {
            $message = "<div class='error'>Please fill in at least one field.</div>";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO reports (user_id, report_text, report_date) VALUES (?, ?, ?)");
                if ($stmt === false) {
                    throw new Exception("Failed to prepare statement: " . $conn->error);
                }
                
                $stmt->bind_param("iss", $user_id, $report_text, $report_date);
                
                if ($stmt->execute()) {
                    $message = "<div class='success'>Report submitted successfully!</div>";
                } else {
                    throw new Exception("Failed to submit report: " . $stmt->error);
                }
                
                $stmt->close();
            } catch (Exception $e) {
                $message = "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// Get today's date for the default value
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Daily Report</title>
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
            max-width: 960px;
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
        
        .field-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        
        .field-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .field-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        textarea {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            resize: vertical;
            min-height: 100px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }
        
        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        
        button {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
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
        
        .no-resize {
            resize: none;
        }
        
        .date-field {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        
        .date-field label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .date-field input[type="date"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            color: var(--dark-color);
            background-color: white;
        }
        
        .date-field input[type="date"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Submit Daily Report</h2>
        <?= $message ?>
        
        <form method="POST">
            <div class="date-field">
                <label for="report_date">Report Date:</label>
                <input type="date" id="report_date" name="report_date" value="<?= $today ?>" required>
            </div>
            
            <?php
            if (isset($template['content'])) {
                $fields = explode("\n---\n", $template['content']);
                foreach ($fields as $field) {
                    $parts = explode(":\n", $field, 2);
                    $label = $parts[0] ?? '';
                    $description = $parts[1] ?? '';
                    echo '<div class="field-group">
                            <input type="hidden" name="labels[]" value="' . htmlspecialchars($label) . '">
                            <div class="field-label">' . htmlspecialchars($label) . '</div>
                            <div class="field-description">' . nl2br(htmlspecialchars($description)) . '</div>
                            <textarea name="responses[]" class="no-resize" rows="3" placeholder="Enter your response"></textarea>
                          </div>';
                }
            } else {
                echo '<div class="alert">No template available. Please contact the administrator.</div>';
            }
            ?>
            
            <div class="btn-container">
                <button type="submit">Submit Report</button>
            </div>
        </form>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
