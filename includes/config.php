<?php
// --- Database Configuration ---
// As per the project requirements, edit these details during installation.

// Database host (e.g., "localhost")
define('DB_HOST', 'localhost');

// Database name
define('DB_NAME', '');

// Database username
define('DB_USER', '');

// Database password
define('DB_PASS', '');

// --- Application Settings ---

// Base URL of the application (e.g., "http://yourdomain.com/")
// It's important to set this for generating correct links.
define('BASE_URL', 'http://localhost');

// --- Security ---

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!! SECURITY WARNING: DO NOT USE THESE DEFAULT KEYS IN A PRODUCTION ENVIRONMENT !!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//
// You MUST generate new, strong, random keys for your application.
// You can use an online generator or a command-line tool like `openssl rand -hex 32`.
//
// Secret key for JWT.
define('JWT_SECRET', 'your-super-secret-key-please-change-me');

// AES-256 Encryption Key and IV.
// The key MUST be 32 bytes (256 bits) long.
// The IV MUST be 16 bytes (128 bits) long.
define('ENCRYPTION_KEY', 'your-32-byte-encryption-key-1234');
define('ENCRYPTION_IV', 'your-16-byte-iv-5678');

// --- PHP Settings ---
// Set default timezone
date_default_timezone_set('Asia/Tehran');

// Enable error reporting for development.
// In a production environment, this should be set to 0.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set character encoding
mb_internal_encoding('UTF-8');
?>
