<?php
include 'includes/db.php';

// Drop the existing comments table if it exists
$conn->query("DROP TABLE IF EXISTS comments");

// Create the comments table with the correct structure
$sql = "CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL UNIQUE,  -- Added UNIQUE constraint to ensure one comment per report
    admin_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "Comments table created successfully with proper structure.<br>";
    
    // Verify the structure
    $result = $conn->query("DESCRIBE comments");
    echo "<h3>New Table Structure:</h3>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Show indexes
    $result = $conn->query("SHOW INDEXES FROM comments");
    echo "<h3>Table Indexes:</h3>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "Error creating table: " . $conn->error;
}
?> 