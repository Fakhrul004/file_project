<?php
session_start();
include '../includes/db.php';  // Make sure this path is correct

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's name from the database
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Dashboard</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Dashboard specific styles */
    body {
      background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
    }
    
    .dashboard-container {
      background-color: var(--white);
      padding: 40px;
      border-radius: 20px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 500px;
      text-align: center;
      animation: fadeIn 1.2s ease-in-out;
      position: relative;
      overflow: hidden;
    }
    
    .dashboard-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--success-color), var(--primary-color));
    }
    
    .dashboard-container h2 {
      color: var(--dark-color);
      margin-bottom: 30px;
    }
    
    .dashboard-container a {
      display: block;
      background-color: var(--success-color);
      color: var(--white);
      text-decoration: none;
      padding: 15px 25px;
      border-radius: 10px;
      margin: 15px 0;
      font-size: 16px;
      font-weight: 500;
      transition: var(--transition);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .dashboard-container a:hover {
      background-color: #218838;
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    
    .dashboard-container a:active {
      transform: translateY(0);
    }
    
    .dashboard-container a.logout {
      background-color: var(--danger-color);
      margin-top: 30px;
    }
    
    .dashboard-container a.logout:hover {
      background-color: #c82333;
    }
    
    .dashboard-container a.daily {
      background-color: #007bff;
    }
    
    .dashboard-container a.daily:hover {
      background-color: #0056b3;
    }
    
    .dashboard-container a.viewreports {
      background-color: #fd7e14;
    }
    
    .dashboard-container a.viewreports:hover {
      background-color: #e8590c;
    }
    
    .dashboard-container a.upload {
      background-color: #28a745;
    }
    
    .dashboard-container a.upload:hover {
      background-color: #218838;
    }
    
    .dashboard-container a.view {
      background-color: #17a2b8;
    }
    
    .dashboard-container a.view:hover {
      background-color: #138496;
    }
    
    .dashboard-container a.monthly {
      background-color: #6f42c1;
    }
    
    .dashboard-container a.monthly:hover {
      background-color: #5a32a3;
    }
    
    .dashboard-container a.monthlysub {
      background-color: #20c997;
    }
    
    .dashboard-container a.monthlysub:hover {
      background-color: #198754;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <h2>Welcome to Daily Report System</h2>
    <a href="submit_report.php" class="daily">Daily Report</a>
    <a href="submit_monthly_report.php" class="monthly">Monthly Report</a>
        <a href="upload_content.php" class="upload">Upload Content</a>
    <a href="view_reports.php" class="viewreports">View Daily Submitted</a>
    <a href="view_monthly_reports.php" class="monthlysub">View Monthly Submitted</a>

    <a href="view_content.php" class="view">View Content Submitted</a>

    <a href="../logout.php" class="logout">Logout</a>
  </div>
</body>
</html>
