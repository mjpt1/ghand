<?php
// --- JWT Authentication Middleware ---

require_once __DIR__ . '/../../includes/lib/php-jwt/JWT.php';
require_once __DIR__ . '/../../includes/lib/php-jwt/Key.php';
require_once __DIR__ . '/../../includes/lib/php-jwt/ExpiredException.php';
require_once __DIR__ . '/../../includes/lib/php-jwt/SignatureInvalidException.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

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
        // Decode and verify the token
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        // Return the decoded payload (which contains user data)
        return $decoded->data;
    } catch (ExpiredException $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Token has expired.']);
        exit;
    } catch (SignatureInvalidException $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token signature.']);
        exit;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token.']);
        exit;
    }
}
?>
