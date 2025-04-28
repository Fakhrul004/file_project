<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Build the filename based on filter
$filename = "reports";
if (!empty($_GET['report_date'])) {
    $filename .= "_" . $_GET['report_date'];
}
$filename .= "_" . date('Y-m-d') . ".csv";

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel display
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, array('User Name', 'Username', 'Report Date', 'Submission Date', 'Report Content'));

// Build SQL query with filters
$where_conditions = [];
$params = [];
$sql = "
    SELECT u.name, u.username, r.report_text, r.report_date, r.created_at
    FROM reports r
    JOIN users u ON r.user_id = u.id
";

// Filter by user
if (!empty($_GET['user_id'])) {
    $where_conditions[] = "r.user_id = ?";
    $params[] = $_GET['user_id'];
}

// Filter by Report Date
if (!empty($_GET['report_date'])) {
    $where_conditions[] = "r.report_date = ?";
    $params[] = $_GET['report_date'];
}

// Filter by Created Date
if (!empty($_GET['created_date'])) {
    $where_conditions[] = "DATE(r.created_at) = ?";
    $params[] = $_GET['created_date'];
}

// Apply filters
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " ORDER BY r.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $row['name'],
        $row['username'],
        $row['report_date'],
        date('Y-m-d H:i', strtotime($row['created_at'])),
        $row['report_text']
    ));
}

// Close the output stream
fclose($output);
exit;
