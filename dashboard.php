<?php
session_start();
require_once 'includes/database.php';

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data from session
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - سیستم مدیریت سلامت</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Socket.io Client -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'dashboard_nav.php'; ?>

    <main>
        <?php
        // Route to the correct dashboard view based on user role
        switch ($user_role) {
            case 'patient':
                include 'views/patient_dashboard.php';
                break;
            case 'doctor':
                echo '<div class="container mt-4"><div class="alert alert-info">داشبورد پزشک به زودی آماده می‌شود.</div></div>';
                break;
            case 'pharmacy':
                echo '<div class="container mt-4"><div class="alert alert-info">داشبورد داروخانه به زودی آماده می‌شود.</div></div>';
                break;
            case 'admin':
                echo '<div class="container mt-4"><div class="alert alert-info">داشبورد ادمین به زودی آماده می‌شود.</div></div>';
                break;
            default:
                echo '<div class="container mt-4"><div class="alert alert-danger">نقش کاربری نامعتبر است.</div></div>';
                break;
        }
        ?>
    </main>

    <footer class="text-center mt-5 py-3 bg-light">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> سیستم وب مدیریت جامع سلامت</p>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
