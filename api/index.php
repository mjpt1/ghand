<?php
// --- API Entry Point ---

// Set default headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); // Allow all origins for now, should be restricted in production
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Basic router
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api'; // The base directory for the API
$route = str_replace($base_path, '', $request_uri);
$route = trim($route, '/');
$route_parts = explode('/', $route);

// The resource is the first part of the route
$resource = $route_parts[0] ?? null;
$action = $route_parts[1] ?? null;

// Route mapping
$routes = [
    'auth' => [
        'login' => 'auth/login.php',
        'socket_token' => 'auth/socket_token.php',
    ],
    'patient' => [
        'records' => 'patient/records.php',
        'meals' => 'patient/meals.php',
    ],
    // Add other resources like 'doctor', 'pharmacy' here
];

// Find and include the handler file
$handler_file = $routes[$resource][$action] ?? null;

if ($handler_file && file_exists(__DIR__ . '/' . $handler_file)) {
    require_once __DIR__ . '/' . $handler_file;
} else {
    // Not found
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found.']);
}

?>
