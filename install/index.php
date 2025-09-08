<?php
// A simple, single-file installer for the application.

// --- Configuration ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('MIN_PHP_VERSION', '8.2.0');
$config_file = __DIR__ . '/../includes/config.php';
$sql_file = __DIR__ . '/../DB.sql';
$uploads_dir = __DIR__ . '/../uploads';

// --- State Management ---
session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];

require_once 'installer_logic.php';

// --- Process Step 2 Form (Database Config) ---
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
    $db_details = [
        'db_host' => $_POST['db_host'],
        'db_name' => $_POST['db_name'],
        'db_user' => $_POST['db_user'],
        'db_pass' => $_POST['db_pass']
    ];

    // Store details in session to pass to the next step
    $_SESSION['db_details'] = $db_details;

    // 1. Write the config file
    if (!write_config_file($config_file, $db_details)) {
        $errors[] = "امکان نوشتن فایل پیکربندی (config) وجود ندارد. لطفاً دسترسی‌های فایل را بررسی کنید.";
        $step = 2; // Go back to step 2
    } else {
        // 2. Test the database connection
        try {
            $dsn = "mysql:host={$db_details['db_host']};dbname={$db_details['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_details['db_user'], $db_details['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $errors[] = "امکان اتصال به پایگاه داده وجود ندارد: " . $e->getMessage();
            $step = 2; // Go back to step 2
        }
    }
}

// --- Process Step 3 Form (Admin & Install) ---
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_email'])) {
    $db_details = $_SESSION['db_details'];
    $admin_details = [
        'admin_email' => $_POST['admin_email'],
        'admin_password' => $_POST['admin_password']
    ];

    if (empty($admin_details['admin_email']) || empty($admin_details['admin_password'])) {
        $errors[] = "ایمیل و رمز عبور ادمین الزامی است.";
        $step = 3;
    } else {
        try {
            // Re-establish connection
            $dsn = "mysql:host={$db_details['db_host']};dbname={$db_details['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_details['db_user'], $db_details['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Import SQL schema
            $import_result = import_sql_schema($pdo, $sql_file);
            if ($import_result !== true) {
                throw new Exception($import_result);
            }

            // Create admin user
            $admin_result = create_admin_user($pdo, $admin_details);
             if ($admin_result !== true) {
                throw new Exception($admin_result);
            }

            // If we reach here, installation is successful

        } catch (Exception $e) {
            $errors[] = "در حین نصب یک خطا رخ داد: " . $e->getMessage();
            $step = 3; // Go back to admin creation step
        }
    }
}


// --- Pre-installation Checks (for Step 1) ---
if ($step === 1) {
    // Check PHP version
    if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
        $errors[] = 'نسخه PHP شما ' . PHP_VERSION . ' است. این برنامه به نسخه ' . MIN_PHP_VERSION . ' یا بالاتر نیاز دارد.';
    }
    // Check if config file is writable
    if (file_exists($config_file) && !is_writable($config_file)) {
        $errors[] = 'فایل ' . $config_file . ' قابل نوشتن نیست. لطفاً دسترسی‌های (permissions) آن را بررسی کنید.';
    } elseif (!file_exists($config_file) && !is_writable(dirname($config_file))) {
         $errors[] = 'پوشه ' . dirname($config_file) . ' قابل نوشتن نیست.';
    }
    // Check if uploads folder is writable
    if (!is_writable($uploads_dir)) {
        $errors[] = 'پوشه ' . $uploads_dir . ' قابل نوشتن نیست.';
    }
    // Check for PDO extension
    if (!extension_loaded('pdo_mysql')) {
        $errors[] = 'افزونه PDO MySQL نصب نشده است که برای ارتباط با دیتابیس ضروری است.';
    }
    // Check if DB.sql is readable
    if (!is_readable($sql_file)) {
        $errors[] = 'فایل ' . $sql_file . ' قابل خواندن نیست.';
    }
}

// --- HTML Header ---
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم مدیریت سلامت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Vazirmatn', sans-serif; }
        .installer-container { max-width: 700px; margin-top: 50px; }
    </style>
</head>
<body>
<div class="container installer-container">
    <div class="card shadow">
        <div class="card-header text-center bg-primary text-white">
            <h2>نصب سیستم مدیریت سلامت</h2>
        </div>
        <div class="card-body p-4">
            <?php if ($step === 1): ?>
                <h4 class="mb-4">مرحله ۱: بررسی پیش‌نیازها</h4>
                <?php if (empty($errors)): ?>
                    <div class="alert alert-success">تمام پیش‌نیازها برآورده شده‌اند. می‌توانید به مرحله بعد بروید.</div>
                    <a href="?step=2" class="btn btn-primary w-100">شروع نصب</a>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <p>سیستم شما برخی از پیش‌نیازهای لازم برای نصب را ندارد:</p>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <a href="?step=1" class="btn btn-secondary w-100">بررسی مجدد</a>
                <?php endif; ?>

            <?php elseif ($step === 2): ?>
                <h4 class="mb-4">مرحله ۲: تنظیمات دیتابیس</h4>
                <p>لطفاً اطلاعات دیتابیسی که از قبل ساخته‌اید را وارد کنید.</p>
                <form method="POST" action="?step=3">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">میزبان دیتابیس (Host)</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_name" class="form-label">نام دیتابیس</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_user" class="form-label">نام کاربری دیتابیس</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">رمز عبور دیتابیس</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">ادامه</button>
                </form>

            <?php elseif ($step === 3): ?>
                <h4 class="mb-4">مرحله ۳: ساخت کاربر ادمین</h4>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p>کاربر اصلی سیستم (ادمین) را ایجاد کنید.</p>
                 <form method="POST" action="?step=4">
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">ایمیل ادمین</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">رمز عبور ادمین</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">نصب و ایجاد کاربر</button>
                </form>

            <?php elseif ($step === 4): ?>
                 <h4 class="mb-4">نصب با موفقیت انجام شد!</h4>
                 <?php if (empty($errors)): ?>
                    <div class="alert alert-success">
                        <p>سیستم مدیریت سلامت با موفقیت نصب شد.</p>
                        <p>شما اکنون می‌توانید با کاربری ادمینی که ساختید وارد شوید.</p>
                    </div>
                    <div class="alert alert-warning">
                        <strong>اقدام مهم برای فعال‌سازی چت:</strong> برای اینکه چت لحظه‌ای کار کند، باید یک مقدار را به صورت دستی کپی کنید.
                        <ol class="mt-2">
                            <li>فایل <code>includes/config.php</code> را باز کنید و مقدار ثابت <code>JWT_SECRET</code> را کپی کنید.</li>
                            <li>فایل <code>server.js</code> (سرور چت) را باز کنید.</li>
                            <li>مقدار کپی شده را جایگزین مقدار فعلی ثابت <code>JWT_SECRET</code> در آن فایل کنید.</li>
                        </ol>
                        <p class="mb-0">این دو مقدار باید دقیقاً یکسان باشند تا احراز هویت سرور چت به درستی کار کند.</p>
                    </div>
                    <div class="alert alert-danger">
                        <strong>هشدار امنیتی:</strong> لطفاً پوشه `install` را فوراً از روی هاست خود حذف کنید.
                    </div>
                    <a href="../index.php" class="btn btn-primary w-100">ورود به سایت</a>
                <?php else: ?>
                     <div class="alert alert-danger">
                        <p>متاسفانه در مرحله نهایی نصب خطایی رخ داد:</p>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <a href="?step=3" class="btn btn-secondary w-100">بازگشت و تلاش مجدد</a>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
