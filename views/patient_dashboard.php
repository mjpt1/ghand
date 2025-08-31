<?php
// This file is included by dashboard.php, so it has access to $pdo and session data.
// Ensure user is a patient
if ($_SESSION['user_role'] !== 'patient') {
    // Or redirect to a generic dashboard / error page
    die('Access Denied: You are not a patient.');
}

$patient_user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// --- Handle Health Record Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_health_record'])) {
    $record_type = $_POST['record_type'];
    $value1 = trim($_POST['value1']);
    $value2 = trim($_POST['value2']); // For blood pressure
    $recorded_at = $_POST['recorded_at'];

    // Basic validation
    if (empty($record_type) || empty($value1) || empty($recorded_at)) {
        $errors[] = 'لطفاً تمام فیلدهای ستاره‌دار را پر کنید.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO health_records (patient_user_id, record_type, value1, value2, recorded_at) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$patient_user_id, $record_type, $value1, empty($value2) ? null : $value2, $recorded_at]);
            $success_message = 'رکورد سلامتی با موفقیت ثبت شد.';
        } catch (Exception $e) {
            $errors[] = 'خطا در ثبت رکورد.';
            error_log('Health Record Insert Error: ' . $e->getMessage());
        }
    }
}


// --- Fetch Patient Data ---
$patient_profile = null;
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE user_id = ?");
    $stmt->execute([$patient_user_id]);
    $patient_profile = $stmt->fetch();
} catch (Exception $e) {
    // Handle error
    error_log('Patient Profile Fetch Error: ' . $e->getMessage());
}

// --- Fetch Health Records ---
$health_records = [];
try {
    $stmt = $pdo->prepare("SELECT record_type, value1, value2, recorded_at FROM health_records WHERE patient_user_id = ? ORDER BY recorded_at DESC");
    $stmt->execute([$patient_user_id]);
    $health_records = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error
    error_log('Health Records Fetch Error: ' . $e->getMessage());
}

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>داشبورد بیمار</h1>
        <?php if ($patient_profile): ?>
            <h4 class="text-muted">خوش آمدید, <?php echo htmlspecialchars($patient_profile['first_name'] . ' ' . $patient_profile['last_name']); ?>!</h4>
        <?php endif; ?>
    </div>
    <hr>

    <!-- Status Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <!-- Section: Add New Health Record -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-plus-circle"></i> ثبت رکورد جدید</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="dashboard.php">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="record_type" class="form-label">نوع رکورد*</label>
                        <select class="form-select" name="record_type" id="record_type" required>
                            <option value="blood_sugar">قند خون</option>
                            <option value="blood_pressure">فشار خون</option>
                            <option value="heart_rate">ضربان قلب</option>
                            <option value="spo2">SpO2</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="value1" class="form-label">مقدار ۱*</label>
                        <input type="text" class="form-control" name="value1" id="value1" placeholder="مثلا برای قند خون: 110">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="value2" class="form-label">مقدار ۲</label>
                        <input type="text" class="form-control" name="value2" id="value2" placeholder="مثلا برای فشار خون: 80">
                    </div>
                     <div class="col-md-3 mb-3">
                        <label for="recorded_at" class="form-label">تاریخ و زمان ثبت*</label>
                        <input type="datetime-local" class="form-control" name="recorded_at" id="recorded_at" required>
                    </div>
                </div>
                <button type="submit" name="add_health_record" class="btn btn-primary">ثبت</button>
            </form>
        </div>
    </div>

    <!-- Section: View Health Records -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-line"></i> سوابق سلامتی شما</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>نوع رکورد</th>
                            <th>مقدار</th>
                            <th>تاریخ ثبت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($health_records)): ?>
                            <tr>
                                <td colspan="3" class="text-center">هیچ رکوردی یافت نشد.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($health_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['record_type']); ?></td>
                                    <td>
                                        <?php
                                            echo htmlspecialchars($record['value1']);
                                            if (!empty($record['value2'])) {
                                                echo ' / ' . htmlspecialchars($record['value2']);
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['recorded_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
