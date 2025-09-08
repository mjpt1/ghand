<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once ROOT_PATH . '/includes/database.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Form Data ---
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Role-specific data
    $medical_license_number = trim($_POST['medical_license_number'] ?? '');
    $pharmacy_name = trim($_POST['pharmacy_name'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');

    // --- Validation ---
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        $errors[] = 'تمام فیلدهای اصلی اجباری هستند.';
    }
    // Role-specific validation
    if ($role === 'doctor' && empty($medical_license_number)) {
        $errors[] = 'شماره نظام پزشکی برای پزشک اجباری است.';
    }
    if ($role === 'pharmacy' && (empty($pharmacy_name) || empty($license_number))) {
        $errors[] = 'نام داروخانه و شماره پروانه برای داروخانه اجباری است.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'فرمت ایمیل نامعتبر است.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
    }
    $allowed_roles = ['patient', 'doctor', 'pharmacy'];
    if (!in_array($role, $allowed_roles)) {
        $errors[] = 'نقش انتخاب شده نامعتبر است.';
    }

    // --- Check if user exists ---
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'کاربری با این ایمیل قبلاً ثبت نام کرده است.';
        }
    }

    // --- Process Registration ---
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // 1. Insert into users table
            $stmt = $pdo->prepare(
                "INSERT INTO users (email, password, role) VALUES (?, ?, ?)"
            );
            $stmt->execute([$email, $hashed_password, $role]);
            $user_id = $pdo->lastInsertId();

            // 2. Insert into role-specific table
            $profile_table_map = [
                'patient' => 'patients',
                'doctor' => 'doctors',
                'pharmacy' => 'pharmacies'
            ];
            $profile_table = $profile_table_map[$role];

            if ($role === 'pharmacy') {
                 $stmt = $pdo->prepare("INSERT INTO {$profile_table} (user_id, pharmacy_name, license_number) VALUES (?, ?, ?)");
                 $stmt->execute([$user_id, $pharmacy_name, $license_number]);
            } else if ($role === 'doctor') {
                $stmt = $pdo->prepare("INSERT INTO {$profile_table} (user_id, first_name, last_name, medical_license_number) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $first_name, $last_name, $medical_license_number]);
            } else { // patient
                $stmt = $pdo->prepare("INSERT INTO {$profile_table} (user_id, first_name, last_name) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $first_name, $last_name]);
            }

            $pdo->commit();

            $success_message = 'ثبت نام شما با موفقیت انجام شد. اکنون می‌توانید وارد شوید.';
            // Redirect to login page after a short delay
            header('Refresh: 3; url=login.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'خطایی در هنگام ثبت نام رخ داد. لطفاً دوباره تلاش کنید.';
            // In development, you might want to log the actual error:
            error_log('Registration Error: ' . $e->getMessage());
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت نام - سیستم مدیریت سلامت</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css"> <!-- We can add custom styles here later -->
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
        }
        .form-container {
            max-width: 600px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="container form-container">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">ایجاد حساب کاربری جدید</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <p class="mb-0"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Registration form -->
                <form action="register.php" method="POST" id="registrationForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">نام</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">نام خانوادگی</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">ایمیل</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">رمز عبور</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">نوع حساب</label>
                        <select class="form-select" id="role" name="role" required>
                            <option selected disabled value="">یک گزینه را انتخاب کنید...</option>
                            <option value="patient">بیمار</option>
                            <option value="doctor">پزشک</option>
                            <option value="pharmacy">داروخانه</option>
                        </select>
                    </div>

                    <!-- Role-specific fields will be shown here via JavaScript -->
                    <div id="role_specific_fields">
                        <!-- Doctor Fields -->
                        <div id="doctor_fields" class="role-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="medical_license_number" class="form-label">شماره نظام پزشکی</label>
                                <input type="text" class="form-control" id="medical_license_number" name="medical_license_number">
                            </div>
                        </div>
                        <!-- Pharmacy Fields -->
                        <div id="pharmacy_fields" class="role-fields" style="display: none;">
                             <div class="mb-3">
                                <label for="pharmacy_name" class="form-label">نام داروخانه</label>
                                <input type="text" class="form-control" id="pharmacy_name" name="pharmacy_name">
                            </div>
                            <div class="mb-3">
                                <label for="license_number" class="form-label">شماره پروانه</label>
                                <input type="text" class="form-control" id="license_number" name="license_number">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">ثبت نام</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p>قبلاً ثبت نام کرده‌اید؟ <a href="login.php">وارد شوید</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleSelect = document.getElementById('role');
            const roleFieldsContainer = document.getElementById('role_specific_fields');

            const doctorFields = document.getElementById('doctor_fields');
            const pharmacyFields = document.getElementById('pharmacy_fields');

            const allRoleFields = [doctorFields, pharmacyFields];

            roleSelect.addEventListener('change', function () {
                const selectedRole = this.value;

                // Hide all role-specific fields and remove 'required' attribute from their inputs
                allRoleFields.forEach(fieldSet => {
                    fieldSet.style.display = 'none';
                    fieldSet.querySelectorAll('input').forEach(input => input.required = false);
                });

                // Show the relevant fields and make them required
                if (selectedRole === 'doctor') {
                    doctorFields.style.display = 'block';
                    doctorFields.querySelectorAll('input').forEach(input => input.required = true);
                } else if (selectedRole === 'pharmacy') {
                    pharmacyFields.style.display = 'block';
                    pharmacyFields.querySelectorAll('input').forEach(input => input.required = true);
                }
            });
        });
    </script>
</body>
</html>
