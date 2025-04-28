<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Filter by month
$where = 'WHERE mr.user_id = ?';
$params = [$user_id];
if (!empty($_GET['filter_month'])) {
    $where .= ' AND mr.report_month = ?';
    $params[] = $_GET['filter_month'];
}

// Fetch user's monthly reports
$sql = "SELECT mr.* FROM monthly_reports mr $where ORDER BY mr.report_month DESC, mr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
$reports = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Fetch template fields for display order
$templateResult = $conn->query("SELECT content FROM templates WHERE type = 'monthly'");
$template = $templateResult ? $templateResult->fetch_assoc() : null;
$template_fields = [];
if ($template && !empty($template['content'])) {
    $fields = explode("\n---\n", $template['content']);
    foreach ($fields as $field) {
        $parts = explode(":\n", $field, 2);
        $label = trim($parts[0] ?? '');
        if ($label) {
            $template_fields[] = $label;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Monthly Reports</title>
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
        }
        .filter-bar {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
        }
        .filter-bar form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-bar input[type="month"] {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
        }
        .filter-bar button, .filter-bar a {
            background: #4666ff;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
        }
        .filter-bar button:hover, .filter-bar a:hover {
            background: #2a5298;
        }
        .report-card {
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.07);
            padding: 30px;
        }
        .report-meta {
            font-size: 15px;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }
        .checked-badge {
            display: inline-block;
            background: #198754;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            margin-right: 8px;
        }
        .field-group {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 18px;
            border: 1px solid #e0e0e0;
        }
        .field-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 16px;
        }
        .field-value {
            color: #222;
            font-size: 15px;
            white-space: pre-line;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: var(--white);
            text-decoration: none;
            font-size: 16px;
            background: #4666ff;
            border-radius: 10px;
            padding: 14px 0;
            max-width: 350px;
            margin-left: auto;
            margin-right: auto;
            transition: background 0.2s;
        }
        .back-link:hover {
            background: #2a5298;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Monthly Reports</h2>
        <div class="filter-bar">
            <form method="GET" action="">
                <input type="month" name="filter_month" value="<?= isset($_GET['filter_month']) ? htmlspecialchars($_GET['filter_month']) : '' ?>">
                <button type="submit">Filter</button>
            </form>
            <a href="view_monthly_reports.php">Refresh</a>
        </div>
        <?php if (empty($reports)): ?>
            <p>You have not submitted any monthly reports yet.</p>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <div class="report-card">
                    <div class="report-meta">
                        <span><strong>Month:</strong> <?= htmlspecialchars($report['report_month']) ?></span>
                        <span><strong>Submitted:</strong> <?= date('Y-m-d H:i', strtotime($report['created_at'])) ?></span>
                        <?php if (!empty($report['checked_by_admin'])): ?>
                            <span class="checked-badge">Checked by Admin</span>
                        <?php endif; ?>
                    </div>
                    <?php 
                    $answers = json_decode($report['report_text'], true);
                    if ($answers && is_array($answers)) {
                        foreach ($template_fields as $label) {
                    ?>
                        <div class="field-group">
                            <div class="field-label"><?= htmlspecialchars($label) ?></div>
                            <div class="field-value"><?= nl2br(htmlspecialchars($answers[$label] ?? '')) ?></div>
                        </div>
                    <?php }} else { ?>
                        <em>No details submitted.</em>
                    <?php } ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    </div>
</body>
</html> 