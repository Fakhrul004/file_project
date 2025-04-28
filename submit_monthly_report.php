<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$message = "";

// Fetch monthly template fields
$templateResult = $conn->query("SELECT content FROM templates WHERE type = 'monthly' AND is_active = 1 LIMIT 1");
$template = $templateResult ? $templateResult->fetch_assoc() : null;
$template_fields = [];
if ($template && !empty($template['content'])) {
    $fields = explode("\n---\n", $template['content']);
    foreach ($fields as $field) {
        $parts = explode(":\n", $field, 2);
        $label = trim($parts[0] ?? '');
        $desc = trim($parts[1] ?? '');
        if ($label) {
            $template_fields[] = [
                'label' => $label,
                'desc' => $desc
            ];
        }
    }
}

// Handle monthly report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_month'])) {
    $user_id = $_SESSION['user_id'];
    $report_month = $_POST['report_month'];
    $responses = [];
    foreach ($template_fields as $idx => $field) {
        $key = 'field_' . $idx;
        $responses[$field['label']] = $_POST[$key] ?? '';
    }
    $report_text = json_encode($responses, JSON_UNESCAPED_UNICODE);
    $sql = "INSERT INTO monthly_reports (user_id, report_text, report_month) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $report_text, $report_month);
    if ($stmt->execute()) {
        $message = "<div class='success'>Monthly report submitted successfully!</div>";
    } else {
        $message = "<div class='error'>Error submitting report.</div>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Monthly Report</title>
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
        .date-field input[type="month"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Submit Monthly Report</h2>
        <?= $message ?>
        <form method="POST">
            <div class="date-field">
                <label for="report_month">Report Month:</label>
                <input type="month" name="report_month" id="report_month" required>
            </div>
            <?php foreach ($template_fields as $idx => $field): ?>
                <div class="field-group">
                    <div class="field-label"><?= htmlspecialchars($field['label']) ?></div>
                    <?php if (!empty($field['desc'])): ?>
                        <div class="field-description"><?= htmlspecialchars($field['desc']) ?></div>
                    <?php endif; ?>
                    <textarea name="field_<?= $idx ?>" id="field_<?= $idx ?>" class="no-resize" placeholder="Enter your response" required></textarea>
                </div>
            <?php endforeach; ?>
            <div class="btn-container">
                <button type="submit">Submit Monthly Report</button>
            </div>
        </form>
        <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    </div>
</body>
</html> 