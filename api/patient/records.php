<?php
// --- API Endpoint for Patient Health Records ---

// Required files
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../api/middleware/auth_middleware.php';

// 1. Validate the token and get user data
$auth_user = validate_token();

// 2. Authorize: Ensure the user is a patient
if ($auth_user->user_role !== 'patient') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You are not authorized to access this resource.']);
    exit;
}

$patient_id = $auth_user->user_id;
$method = $_SERVER['REQUEST_METHOD'];

// 3. Route based on the request method
switch ($method) {
    case 'GET':
        // --- Fetch all health records for the patient ---
        try {
            $stmt = $pdo->prepare("SELECT id, record_type, value1, value2, unit, recorded_at, notes FROM health_records WHERE patient_user_id = ? ORDER BY recorded_at DESC");
            $stmt->execute([$patient_id]);
            $records = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode($records);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred while fetching records.']);
            error_log('API GET patient/records Error: ' . $e->getMessage());
        }
        break;

    case 'POST':
        // --- Create a new health record ---
        $data = json_decode(file_get_contents("php://input"));

        // Basic validation
        if (!isset($data->record_type) || !isset($data->value1) || !isset($data->recorded_at)) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Missing required fields: record_type, value1, recorded_at.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO health_records (patient_user_id, record_type, value1, value2, unit, recorded_at, notes) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $patient_id,
                $data->record_type,
                $data->value1,
                $data->value2 ?? null,
                $data->unit ?? null,
                $data->recorded_at,
                $data->notes ?? null
            ]);

            $new_record_id = $pdo->lastInsertId();

            http_response_code(201); // Created
            echo json_encode([
                'message' => 'Health record created successfully.',
                'id' => $new_record_id
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred while creating the record.']);
            error_log('API POST patient/records Error: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed.']);
        break;
}
?>
