<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/services/PaymentService.php';

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$appointment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$appointment_id) {
    // Redirect with error
    header('Location: dashboard.php');
    exit;
}

// --- Hardcoded price for the appointment ---
$appointment_price = 50000; // 50,000 Toman/Rial

$paymentService = new PaymentService($pdo);
$payment_url = $paymentService->requestPayment(
    $_SESSION['user_id'],
    $appointment_price,
    'Payment for appointment #' . $appointment_id,
    $appointment_id
);

if ($payment_url) {
    // Redirect the user to the payment gateway
    header('Location: ' . $payment_url);
    exit;
} else {
    // Handle error - redirect back to dashboard with a message
    // (Implementation of session flash messages would be good here)
    header('Location: dashboard.php');
    exit;
}
?>
