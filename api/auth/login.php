<?php
// --- API Login Endpoint ---

// Required files
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/lib/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/lib/php-jwt/Key.php';

use Firebase\JWT\JWT;

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST method is accepted.']);
    exit;
}

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

$email = trim($data->email);
$password = $data->password;

try {
    // Find the user by email
    $stmt = $pdo->prepare("SELECT id, password, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password and user status
    if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
        // Credentials are correct, generate JWT
        $issuer = BASE_URL;
        $issued_at = time();
        $expiration_time = $issued_at + (60 * 60 * 24); // Token valid for 24 hours

        $payload = [
            'iss' => $issuer,
            'iat' => $issued_at,
            'exp' => $expiration_time,
            'data' => [
                'user_id' => $user['id'],
                'user_email' => $email,
                'user_role' => $user['role']
            ]
        ];

        // Encode the token
        $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

        http_response_code(200);
        echo json_encode([
            'message' => 'Login successful.',
            'token' => $jwt
        ]);

    } else {
        // Invalid credentials or inactive user
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials or user is not active.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred.']);
    error_log('API Login Error: ' . $e->getMessage());
}
?>
