<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = $_POST['name'];
    $phone    = $_POST['phone'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, phone, username, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $phone, $username, $password);

    if ($stmt->execute()) {
        $message = "<div class='success'>Registration successful! <a href='login.php'>Login here</a></div>";
    } else {
        $message = "<div class='error'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>User Registration</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="form-container">
        <h2>User Registration</h2>
        <?= isset($message) ? $message : '' ?>
        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>
            
            <label for="phone">Phone Number:</label>
            <input type="text" name="phone" id="phone" required>
            
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            
            <button type="submit">Register</button>
        </form>
        
        <div style="margin-top: 20px; text-align: center;">
            <p>Already have an account? <a href="login.php" style="display: inline; font-size: 14px;">Login here</a></p>
            <a href="../index.php" style="display: inline-block; margin-top: 15px; font-size: 14px;">Back to Home</a>
        </div>
    </div>
</body>
</html>
