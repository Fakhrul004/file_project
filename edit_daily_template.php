<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_content'])) {
    $template_content = $_POST['template_content'];
    $sql = "REPLACE INTO templates (type, content) VALUES ('daily', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $template_content);
    if ($stmt->execute()) {
        $message = "Daily report template updated successfully!";
    } else {
        $message = "Error updating template.";
    }
    $stmt->close();
}

// Fetch current template
$template_content = "";
$sql = "SELECT content FROM templates WHERE type = 'daily'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $template_content = $row['content'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Daily Report Template</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <h2>Edit Daily Report Template</h2>
        <?php if ($message): ?>
            <div class="message"> <?= $message ?> </div>
        <?php endif; ?>
        <form method="POST">
            <textarea name="template_content" rows="12" style="width:100%;"><?= htmlspecialchars($template_content) ?></textarea>
            <button type="submit" class="dashboard-btn purple" style="margin-top:20px;">Save Template</button>
        </form>
        <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    </div>
</body>
</html> 