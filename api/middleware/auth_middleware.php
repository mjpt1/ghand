<?php
// --- JWT Authentication Middleware ---

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once ROOT_PATH . '/includes/lib/php-jwt/src/JWT.php';

use JWT\JWT;

function validate_token() {
    // Check for the Authorization header
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header not found.']);
        exit;
    }

    $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    $header_parts = explode(' ', $auth_header);

    // Check for the Bearer scheme
    if (count($header_parts) !== 2 || $header_parts[0] !== 'Bearer') {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid authorization scheme.']);
        exit;
    }

    $jwt = $header_parts[1];

    try {
        // Decode and verify the token using the new library's signature
        $decoded = JWT::decode($jwt, JWT_SECRET, ['HS256']);

        // Return the decoded payload (which contains user data)
        return $decoded->data;
    } catch (\Exception $e) {
        http_response_code(401);
        // Provide a more specific error message based on the exception
        if ($e->getMessage() === 'Expired token') {
            echo json_encode(['error' => 'Token has expired.']);
        } else {
            echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
