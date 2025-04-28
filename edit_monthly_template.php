<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = [];
    $labels = $_POST['labels'] ?? [];
    $textareas = $_POST['template_fields'] ?? [];
    for ($i = 0; $i < count($labels); $i++) {
        if (!empty($labels[$i])) {
            $fields[] = $labels[$i] . ":\n" . ($textareas[$i] ?? '');
        }
    }
    $template_text = implode("\n---\n", array_map('trim', $fields));
    $stmt = $conn->prepare("REPLACE INTO templates (type, content) VALUES ('monthly', ?)");
    $stmt->bind_param("s", $template_text);
    if ($stmt->execute()) {
        $message = "<div class='success'>Template updated successfully!</div>";
    } else {
        $message = "<div class='error'>Error saving template: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Load latest monthly template
$templateResult = $conn->query("SELECT content FROM templates WHERE type = 'monthly'");
$template = $templateResult ? $templateResult->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Monthly Report Template</title>
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
            position: relative;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        .field-label {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
            font-weight: 500;
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
        textarea:focus, .field-label:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }
        .delete-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            background: var(--danger-color);
            color: var(--white);
            border: none;
            width: 30px;
            height: 30px;
            font-size: 20px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .delete-btn:hover {
            background: #c82333;
            transform: scale(1.1);
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
        button.add-btn {
            background-color: var(--success-color);
        }
        button.add-btn:hover {
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
        .no-resize {
            resize: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Monthly Report Template</h2>
        <?= $message ?>
        <form method="POST">
            <div id="inputContainer">
                <?php
                if (isset($template['content'])) {
                    $fields = explode("\n---\n", $template['content']);
                    foreach ($fields as $field) {
                        $parts = explode(":\n", $field, 2);
                        $label = $parts[0] ?? '';
                        $content = $parts[1] ?? '';
                        echo '<div class="field-group">
                                <input type="text" class="field-label" name="labels[]" value="' . htmlspecialchars($label) . '" placeholder="Enter field label (e.g. No. IC Pelajar)">
                                <textarea name="template_fields[]" class="no-resize" rows="3" placeholder="Enter field description or instructions">' . htmlspecialchars($content) . '</textarea>
                                <button type="button" class="delete-btn" onclick="deleteField(this)">×</button>
                              </div>';
                    }
                } else {
                    echo '<div class="field-group">
                            <input type="text" class="field-label" name="labels[]" placeholder="Enter field label">
                            <textarea name="template_fields[]" class="no-resize" rows="3" placeholder="Instructions for User"></textarea>
                            <button type="button" class="delete-btn" onclick="deleteField(this)">×</button>
                          </div>';
                }
                ?>
            </div>
            <div class="btn-container">
                <button type="button" class="add-btn" onclick="addField()">+ Add Field</button>
                <button type="submit">Save Template</button>
            </div>
        </form>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        function addField() {
            const container = document.getElementById('inputContainer');
            const fieldGroup = document.createElement('div');
            fieldGroup.className = 'field-group';
            
            fieldGroup.innerHTML = `
                <input type="text" class="field-label" name="labels[]" placeholder="Enter field label">
                <textarea name="template_fields[]" class="no-resize" rows="3" placeholder="Instructions for User"></textarea>
                <button type="button" class="delete-btn" onclick="deleteField(this)">×</button>
            `;
            
            container.appendChild(fieldGroup);
        }

        function deleteField(button) {
            const fieldGroup = button.parentElement;
            fieldGroup.remove();
        }
    </script>
</body>
</html> 