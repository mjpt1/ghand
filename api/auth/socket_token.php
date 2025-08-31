<?php
// --- API Endpoint to issue a JWT for a session-authenticated user ---

session_start();

// Required files
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/lib/php-jwt/JWT.php';

use Firebase\JWT\JWT;

// Check if the user is logged in via PHP session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated. Please log in first.']);
    exit;
}

// User is logged in, so we can issue a JWT for them.
try {
    $issuer = BASE_URL;
    $issued_at = time();
    // This token can be short-lived as it's just for establishing the socket connection
    $expiration_time = $issued_at + (60 * 10); // Token valid for 10 minutes

    $payload = [
        'iss' => $issuer,
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'data' => [
            'user_id' => $_SESSION['user_id'],
            'user_email' => $_SESSION['user_email'],
            'user_role' => $_SESSION['user_role']
        ]
    ];

    // Encode the token
    $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

    http_response_code(200);
    echo json_encode([
        'token' => $jwt
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred while generating the token.']);
    error_log('API Socket Token Error: ' . $e->getMessage());
}
?>
