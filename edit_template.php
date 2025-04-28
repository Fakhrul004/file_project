<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle Save (add or update)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_template'])) {
    $type = trim($_POST['template_type']);
    $fields = [];
    $labels = $_POST['labels'] ?? [];
    $textareas = $_POST['template_fields'] ?? [];
    for ($i = 0; $i < count($labels); $i++) {
        if (!empty($labels[$i])) {
            $fields[] = $labels[$i] . ":\n" . ($textareas[$i] ?? '');
        }
    }
    $template_text = implode("\n---\n", array_map('trim', $fields));
    $stmt = $conn->prepare("INSERT INTO templates (type, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = VALUES(content)");
    $stmt->bind_param("ss", $type, $template_text);
    if ($stmt->execute()) {
        $message = "<div class='success'>Template for <b>".htmlspecialchars($type)."</b> updated successfully!</div>";
    } else {
        $message = "<div class='error'>Error saving template: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Handle delete template
if (isset($_POST['delete_template']) && isset($_POST['delete_type'])) {
    $del_type = $_POST['delete_type'];
    $conn->query("DELETE FROM templates WHERE type = '" . $conn->real_escape_string($del_type) . "'");
    $message = "<div class='success'>Template for <b>".htmlspecialchars($del_type)."</b> deleted successfully!</div>";
}

// Handle activate template
if (isset($_POST['activate_template']) && isset($_POST['activate_id']) && isset($_POST['activate_type'])) {
    $activate_id = intval($_POST['activate_id']);
    $activate_type = $conn->real_escape_string($_POST['activate_type']);
    // Set all templates of this type to inactive
    $conn->query("UPDATE templates SET is_active = 0 WHERE type = '" . $activate_type . "'");
    // Set selected template to active
    $conn->query("UPDATE templates SET is_active = 1 WHERE id = $activate_id");
    $message = "<div class='success'>Template for <b>".htmlspecialchars($activate_type)."</b> activated!</div>";
}

// Load all templates
$templatesResult = $conn->query("SELECT * FROM templates ORDER BY type");
$templates = $templatesResult ? $templatesResult->fetch_all(MYSQLI_ASSOC) : [];

// Load template for editing if requested
$edit_type = $_GET['edit_type'] ?? '';
$edit_template = null;
if ($edit_type) {
    $editResult = $conn->query("SELECT * FROM templates WHERE type = '" . $conn->real_escape_string($edit_type) . "' LIMIT 1");
    $edit_template = $editResult ? $editResult->fetch_assoc() : null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Templates</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 1000px; margin: 0 auto; background-color: var(--white); padding: 40px; border-radius: 20px; box-shadow: var(--shadow); }
        h2 { text-align: center; color: var(--dark-color); margin-bottom: 30px; }
        .template-list { margin-bottom: 40px; }
        .template-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .template-table th, .template-table td { border: 1px solid #e0e0e0; padding: 12px; text-align: left; }
        .template-table th { background: #f8f9fa; color: #185a9d; }
        .template-table td { background: #fff; }
        .template-actions { display: flex; gap: 10px; }
        .edit-btn, .delete-btn { padding: 7px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; }
        .edit-btn { background: #4666ff; color: #fff; }
        .edit-btn:hover { background: #2a5298; }
        .delete-btn { background: #dc3545; color: #fff; }
        .delete-btn:hover { background: #b52a37; }
        .field-group { position: relative; background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #e0e0e0; }
        .field-label { width: 100%; padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #e0e0e0; margin-bottom: 10px; font-weight: 500; }
        textarea { width: 100%; padding: 15px; font-size: 16px; border-radius: 10px; border: 1px solid #e0e0e0; resize: vertical; min-height: 100px; transition: var(--transition); font-family: 'Poppins', sans-serif; }
        textarea:focus, .field-label:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2); }
        .btn-container { display: flex; gap: 15px; margin-top: 30px; justify-content: center; }
        button { background-color: var(--primary-color); color: var(--white); border: none; padding: 12px 25px; font-size: 16px; border-radius: 10px; cursor: pointer; transition: var(--transition); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        button:hover { background-color: var(--primary-dark); transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15); }
        button.add-btn { background-color: var(--success-color); }
        button.add-btn:hover { background-color: #218838; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--white); text-decoration: none; font-size: 14px; transition: color 0.3s ease; }
        .back-link:hover { color: var(--primary-color); }
        .no-resize { resize: none; }
        .template-preview { font-size: 13px; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Templates</h2>
        <?= $message ?>
        <div class="template-list">
            <table class="template-table">
                <tr>
                    <th>Type</th>
                    <th>Preview</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($templates as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['type']) ?></td>
                        <td class="template-preview">
                            <?php
                            $fields = explode("\n---\n", $row['content']);
                            foreach ($fields as $field) {
                                $parts = explode(":\n", $field, 2);
                                $label = $parts[0] ?? '';
                                $desc = $parts[1] ?? '';
                                echo '<strong>' . htmlspecialchars($label) . '</strong>: ' . htmlspecialchars($desc) . '<br>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($row['is_active'])): ?>
                                <span style="color:#198754;font-weight:600;">Active</span>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="activate_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="activate_type" value="<?= htmlspecialchars($row['type']) ?>">
                                    <button type="submit" name="activate_template" class="edit-btn" style="background:#198754;">Activate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="template-actions">
                            <a href="edit_template.php?edit_type=<?= urlencode($row['type']) ?>" class="edit-btn">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this template?');">
                                <input type="hidden" name="delete_type" value="<?= htmlspecialchars($row['type']) ?>">
                                <button type="submit" name="delete_template" class="delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <hr style="margin:40px 0;">
        <h3><?= $edit_type ? 'Edit Template: ' . htmlspecialchars($edit_type) : 'Add New Template' ?></h3>
        <form method="POST">
            <div style="margin-bottom:20px;">
                <label for="template_type"><b>Template Type:</b></label>
                <input type="text" id="template_type" name="template_type" value="<?= htmlspecialchars($edit_type ?: '') ?>" <?= $edit_type ? 'readonly' : '' ?> required style="padding:8px 12px;border-radius:6px;border:1px solid #e0e0e0;">
            </div>
            <div id="inputContainer">
                <?php
                $edit_fields = [];
                if ($edit_template && isset($edit_template['content'])) {
                    $edit_fields = explode("\n---\n", $edit_template['content']);
                }
                if (!empty($edit_fields)) {
                    foreach ($edit_fields as $field) {
                        $parts = explode(":\n", $field, 2);
                        $label = $parts[0] ?? '';
                        $content = $parts[1] ?? '';
                        echo '<div class="field-group">
                                <input type="text" class="field-label" name="labels[]" value="' . htmlspecialchars($label) . '" placeholder="Enter field label">
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
                <button type="submit" name="save_template">Save Template</button>
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
