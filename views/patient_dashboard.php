<?php
// This file is included by dashboard.php, so it has access to $pdo and session data.
// Ensure user is a patient
if ($_SESSION['user_role'] !== 'patient') {
    die('Access Denied: You are not a patient.');
}

//--- DATA HANDLERS & FETCHING ---

$patient_user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// --- Handle Health Record Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_health_record'])) {
    $record_type = $_POST['record_type'];
    $value1 = trim($_POST['value1']);
    $value2 = trim($_POST['value2']);
    $recorded_at = $_POST['recorded_at'];
    if (empty($record_type) || empty($value1) || empty($recorded_at)) {
        $errors[] = 'لطفاً تمام فیلدهای فرم رکورد سلامتی را پر کنید.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO health_records (patient_user_id, record_type, value1, value2, recorded_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$patient_user_id, $record_type, $value1, empty($value2) ? null : $value2, $recorded_at]);
            $success_message = 'رکورد سلامتی با موفقیت ثبت شد.';
        } catch (Exception $e) { $errors[] = 'خطا در ثبت رکورد سلامتی.'; error_log('Health Record Insert Error: ' . $e->getMessage()); }
    }
}

// --- Handle Appointment Booking ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
    $appointment_time = $_POST['appointment_time'];
    $appointment_notes = trim($_POST['appointment_notes']);
    if (empty($doctor_id) || empty($appointment_time)) {
        $errors[] = 'لطفاً پزشک و زمان مورد نظر را انتخاب کنید.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_user_id, doctor_user_id, appointment_time, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$patient_user_id, $doctor_id, $appointment_time, $appointment_notes]);
            $success_message = 'درخواست رزرو شما با موفقیت ثبت شد.';
        } catch (Exception $e) { $errors[] = 'خطا در ثبت درخواست رزرو.'; error_log('Appointment Booking Error: ' . $e->getMessage()); }
    }
}

// --- Handle File Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_result'])) {
    $result_title = trim($_POST['result_title']);
    if (empty($result_title)) {
        $errors[] = 'عنوان آزمایش اجباری است.';
    } elseif (isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['result_file']['tmp_name'];
        $file_name = $_FILES['result_file']['name'];
        $file_size = $_FILES['result_file']['size'];
        $file_type = mime_content_type($file_tmp_path);
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'نوع فایل مجاز نیست. فقط فایل‌های PDF, JPG, PNG قابل قبول هستند.';
        } elseif ($file_size > (5 * 1024 * 1024)) {
            $errors[] = 'حجم فایل بیش از حد مجاز (5MB) است.';
        } else {
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid('result_', true) . '.' . $file_extension;
            $upload_path = ROOT_PATH . '/uploads/' . $new_file_name;
            if (move_uploaded_file($file_tmp_path, $upload_path)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO lab_results (patient_user_id, title, file_name, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$patient_user_id, $result_title, $new_file_name, 'uploads/' . $new_file_name, $file_type]);
                    $success_message = 'فایل با موفقیت بارگذاری شد.';
                } catch (Exception $e) { $errors[] = 'خطا در ذخیره اطلاعات فایل.'; error_log('File Upload DB Error: ' . $e->getMessage()); unlink($upload_path); }
            } else { $errors[] = 'خطا در بارگذاری فایل.'; }
        }
    } else { $errors[] = 'لطفاً یک فایل را انتخاب کنید یا خطایی در بارگذاری رخ داده است.'; }
}

// --- Handle Meal Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meal'])) {
    $meal_type = $_POST['meal_type'];
    $meal_time = $_POST['meal_time'];
    $meal_description = trim($_POST['meal_description']);
    if (empty($meal_type) || empty($meal_time) || empty($meal_description)) {
        $errors[] = 'لطفاً تمام فیلدهای فرم غذا را پر کنید.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO meals (patient_user_id, meal_type, meal_time, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$patient_user_id, $meal_type, $meal_time, $meal_description]);
            $success_message = 'وعده غذایی با موفقیت ثبت شد.';
        } catch (Exception $e) { $errors[] = 'خطا در ثبت وعده غذایی.'; error_log('Meal Insert Error: ' . $e->getMessage()); }
    }
}

// --- Data Fetching for Display ---
$doctors = $my_appointments = $patient_profile = $health_records = $meals = $lab_results = [];
try {
    $doctors = $pdo->query("SELECT user_id, first_name, last_name FROM doctors")->fetchAll();
    $patient_profile = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE user_id = ?");
    $patient_profile->execute([$patient_user_id]);
    $patient_profile = $patient_profile->fetch();

    $stmt = $pdo->prepare("SELECT record_type, value1, value2, recorded_at FROM health_records WHERE patient_user_id = ? ORDER BY recorded_at DESC");
    $stmt->execute([$patient_user_id]);
    $health_records = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT meal_type, description, meal_time FROM meals WHERE patient_user_id = ? ORDER BY meal_time DESC");
    $stmt->execute([$patient_user_id]);
    $meals = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT title, file_name, file_path, uploaded_at FROM lab_results WHERE patient_user_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$patient_user_id]);
    $lab_results = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT a.id, d.first_name, d.last_name, a.appointment_time, a.status FROM appointments a JOIN doctors d ON a.doctor_user_id = d.user_id WHERE a.patient_user_id = ? ORDER BY a.appointment_time DESC");
    $stmt->execute([$patient_user_id]);
    $my_appointments = $stmt->fetchAll();

} catch (Exception $e) { $errors[] = "خطا در بارگذاری اطلاعات داشبورد."; error_log('Patient Dashboard Fetch Error: ' . $e->getMessage()); }

// --- Prepare Data for Chart.js ---
$chart_labels = [];
$chart_data = [];
foreach (array_reverse($health_records) as $record) {
    if ($record['record_type'] === 'blood_sugar') {
        $date = new DateTime($record['recorded_at']);
        $chart_labels[] = $date->format('Y-m-d H:i');
        $chart_data[] = $record['value1'];
    }
}
$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode($chart_data);
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
            <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- Chart.js Card -->
    <div class="card mb-4">
        <div class="card-header"><h5><i class="fas fa-chart-line"></i> نمودار روند قند خون</h5></div>
        <div class="card-body"><canvas id="bloodSugarChart"></canvas></div>
    </div>

    <!-- Forms Row -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-plus-circle"></i> ثبت رکورد جدید سلامتی</h5></div>
                <div class="card-body">
                    <form method="POST" action="dashboard.php">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="record_type" class="form-label">نوع رکورد*</label><select class="form-select" name="record_type" required><option value="blood_sugar">قند خون</option><option value="blood_pressure">فشار خون</option><option value="heart_rate">ضربان قلب</option><option value="spo2">SpO2</option></select></div>
                            <div class="col-md-6 mb-3"><label for="recorded_at" class="form-label">تاریخ و زمان ثبت*</label><input type="datetime-local" class="form-control" name="recorded_at" required></div>
                            <div class="col-md-6 mb-3"><label for="value1" class="form-label">مقدار ۱*</label><input type="text" class="form-control" name="value1" placeholder="مثلا برای قند خون: 110"></div>
                            <div class="col-md-6 mb-3"><label for="value2" class="form-label">مقدار ۲</label><input type="text" class="form-control" name="value2" placeholder="مثلا برای فشار خون: 80"></div>
                        </div>
                        <button type="submit" name="add_health_record" class="btn btn-primary">ثبت</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-utensils"></i> ثبت وعده غذایی</h5></div>
                <div class="card-body">
                     <form method="POST" action="dashboard.php">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="meal_type" class="form-label">نوع وعده*</label><select class="form-select" name="meal_type" required><option value="breakfast">صبحانه</option><option value="lunch">ناهار</option><option value="dinner">شام</option><option value="snack">میان‌وعده</option></select></div>
                            <div class="col-md-6 mb-3"><label for="meal_time" class="form-label">تاریخ و زمان*</label><input type="datetime-local" class="form-control" name="meal_time" required></div>
                        </div>
                        <div class="mb-3"><label for="meal_description" class="form-label">شرح غذا*</label><textarea class="form-control" name="meal_description" rows="2" required></textarea></div>
                        <button type="submit" name="add_meal" class="btn btn-primary">ثبت غذا</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-clipboard-list"></i> سوابق سلامتی شما</h5></div>
                <div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>نوع رکورد</th><th>مقدار</th><th>تاریخ ثبت</th></tr></thead><tbody>
                    <?php if (empty($health_records)): ?><tr><td colspan="3" class="text-center">هیچ رکوردی یافت نشد.</td></tr>
                    <?php else: foreach ($health_records as $record): ?><tr><td><?php echo htmlspecialchars($record['record_type']); ?></td><td><?php echo htmlspecialchars($record['value1']); if (!empty($record['value2'])) { echo ' / ' . htmlspecialchars($record['value2']); } ?></td><td><?php echo htmlspecialchars($record['recorded_at']); ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-book-medical"></i> سوابق غذایی شما</h5></div>
                <div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>وعده</th><th>شرح</th><th>زمان</th></tr></thead><tbody>
                    <?php if (empty($meals)): ?><tr><td colspan="3" class="text-center">هیچ وعده غذایی یافت نشد.</td></tr>
                    <?php else: foreach ($meals as $meal): ?><tr><td><?php echo htmlspecialchars($meal['meal_type']); ?></td><td><?php echo htmlspecialchars($meal['description']); ?></td><td><?php echo htmlspecialchars($meal['meal_time']); ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div></div>
            </div>
        </div>
    </div>

    <!-- Upload and Booking Row -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-upload"></i> بارگذاری نتایج آزمایش</h5></div>
                <div class="card-body">
                    <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="result_title" class="form-label">عنوان آزمایش*</label><input type="text" class="form-control" name="result_title" required></div>
                            <div class="col-md-6 mb-3"><label for="result_file" class="form-label">فایل (PDF, JPG, PNG)*</label><input type="file" class="form-control" name="result_file" required></div>
                        </div>
                        <button type="submit" name="upload_result" class="btn btn-primary">بارگذاری فایل</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-calendar-check"></i> رزرو نوبت جدید</h5></div>
                <div class="card-body">
                    <form method="POST" action="dashboard.php">
                        <div class="mb-3"><label for="doctor_id" class="form-label">انتخاب پزشک*</label><select name="doctor_id" class="form-select" required><option value="">یک پزشک را انتخاب کنید...</option><?php foreach ($doctors as $doctor): ?><option value="<?php echo $doctor['user_id']; ?>"><?php echo htmlspecialchars('دکتر ' . $doctor['first_name'] . ' ' . $doctor['last_name']); ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3"><label for="appointment_time" class="form-label">تاریخ و زمان درخواستی*</label><input type="datetime-local" class="form-control" name="appointment_time" required></div>
                        <div class="mb-3"><label for="appointment_notes" class="form-label">یادداشت (اختیاری)</label><textarea class="form-control" name="appointment_notes" rows="2"></textarea></div>
                        <button type="submit" name="book_appointment" class="btn btn-primary">ارسال درخواست رزرو</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row 2 -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-vials"></i> نتایج آزمایش‌های بارگذاری شده</h5></div>
                <div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>عنوان</th><th>نام فایل</th><th>تاریخ بارگذاری</th><th>مشاهده</th></tr></thead><tbody>
                    <?php if (empty($lab_results)): ?><tr><td colspan="4" class="text-center">هیچ نتیجه آزمایشی بارگذاری نشده است.</td></tr>
                    <?php else: foreach ($lab_results as $result): ?><tr><td><?php echo htmlspecialchars($result['title']); ?></td><td><?php echo htmlspecialchars($result['file_name']); ?></td><td><?php echo htmlspecialchars($result['uploaded_at']); ?></td><td><a href="<?php echo htmlspecialchars($result['file_path']); ?>" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> مشاهده</a></td></tr><?php endforeach; endif; ?>
                </tbody></table></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fas fa-calendar-alt"></i> نوبت‌های من</h5></div>
                <div class="card-body"><div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>پزشک</th><th>زمان</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
                    <?php if (empty($my_appointments)): ?><tr><td colspan="4" class="text-center">شما هیچ نوبتی رزرو نکرده‌اید.</td></tr>
                    <?php else: foreach ($my_appointments as $appt): ?><tr><td><?php echo htmlspecialchars('دکتر ' . $appt['first_name'] . ' ' . $appt['last_name']); ?></td><td><?php echo htmlspecialchars($appt['appointment_time']); ?></td><td><?php echo htmlspecialchars($appt['status']); ?></td><td><?php if ($appt['status'] === 'confirmed'): ?><a href="start_payment.php?id=<?php echo $appt['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-credit-card"></i> پرداخت</a><?php endif; ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('bloodSugarChart').getContext('2d');
    const chartLabels = <?php echo $chart_labels_json; ?>;
    const chartData = <?php echo $chart_data_json; ?>;
    if (chartLabels.length > 0) {
        const bloodSugarChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'قند خون (mg/dL)',
                    data: chartData,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: false, title: { display: true, text: 'مقدار قند خون' } }, x: { title: { display: true, text: 'تاریخ و زمان' } } },
                plugins: { legend: { display: true, position: 'top' }, tooltip: { mode: 'index', intersect: false } }
            }
        });
    } else {
        ctx.font = '16px Vazirmatn'; ctx.fillStyle = '#888'; ctx.textAlign = 'center';
        ctx.fillText('داده‌ای برای نمایش نمودار قند خون وجود ندارد.', ctx.canvas.width / 2, 50);
    }
});
</script>
