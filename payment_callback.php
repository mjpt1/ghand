<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/services/PaymentService.php';

$payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
$message = "An unknown error occurred.";
$is_success = false;

if ($payment_id) {
    $paymentService = new PaymentService($pdo);

    // In a real scenario, $_GET would contain data from the real gateway.
    // Here, we're just passing the whole array for the simulation.
    if ($paymentService->verifyPayment($payment_id, $_GET)) {
        $message = "پرداخت شما با موفقیت انجام شد. نوبت شما نهایی شد.";
        $is_success = true;
    } else {
        $message = "پرداخت ناموفق بود یا توسط شما لغو شد.";
        $is_success = false;
    }
} else {
    $message = "اطلاعات پرداخت نامعتبر است.";
    $is_success = false;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وضعیت پرداخت - سیستم مدیریت سلامت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        .status-container { max-width: 600px; margin-top: 100px; }
    </style>
</head>
<body>
    <div class="container status-container">
        <div class="card text-center">
            <div class="card-header">
                <h3>نتیجه تراکنش</h3>
            </div>
            <div class="card-body">
                <div class="alert <?php echo $is_success ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <a href="dashboard.php" class="btn btn-primary">بازگشت به داشبورد</a>
            </div>
        </div>
    </div>
</body>
</html>
