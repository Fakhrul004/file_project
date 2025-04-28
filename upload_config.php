<?php
// This file contains configuration settings for file uploads
// It should be included at the beginning of any file that handles uploads

// Increase memory limit
ini_set('memory_limit', '256M');

// Increase upload limits
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?> 