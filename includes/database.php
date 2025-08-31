<?php
/**
 * Database Connection Handler
 *
 * This script establishes a connection to the database using PDO.
 * It uses the configuration constants defined in config.php.
 */

// Include configuration file only once
require_once __DIR__ . '/config.php';

// PDO instance variable
$pdo = null;

// DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation of prepared statements
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If connection fails, stop the script and show an error.
    // In a production environment, you would log this error and show a generic message.
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please check your configuration or contact support.");
}

// The $pdo variable can now be used in other scripts by including this file.
// For example: require_once 'includes/database.php';
?>
