<?php
// This file is included by dashboard.php, so it has access to $pdo and session data.
// Ensure user is a doctor
if ($_SESSION['user_role'] !== 'doctor') {
    die('Access Denied: You are not a doctor.');
}

$doctor_user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// --- Handle Appointment Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['confirm_appointment']) || isset($_POST['cancel_appointment']))) {
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $new_status = isset($_POST['confirm_appointment']) ? 'confirmed' : 'cancelled_by_doctor';

    if ($appointment_id) {
        try {
            // Verify the appointment belongs to this doctor before updating
            $stmt = $pdo->prepare(
                "UPDATE appointments SET status = ? WHERE id = ? AND doctor_user_id = ?"
            );
            $stmt->execute([$new_status, $appointment_id, $doctor_user_id]);

            if ($stmt->rowCount() > 0) {
                $success_message = "وضعیت نوبت با موفقیت به‌روزرسانی شد.";
            } else {
                $error_message = "نوبت یافت نشد یا شما اجازه تغییر آن را ندارید.";
            }
        } catch (Exception $e) {
            $error_message = "خطا در به‌روزرسانی وضعیت نوبت.";
            error_log('Doctor Dashboard - Appointment Update Error: ' . $e->getMessage());
        }
    } else {
        $error_message = "درخواست نامعتبر.";
    }
}


// --- Fetch Doctor's Assigned Patients ---
$assigned_patients = [];
try {
    // We need to join patients with users to get their email if needed,
    // and filter by the assigned_doctor_id which is the doctor's user_id.
    $stmt = $pdo->prepare(
        "SELECT p.user_id, p.first_name, p.last_name, u.email
         FROM patients p
         JOIN users u ON p.user_id = u.id
         WHERE p.assigned_doctor_id = ?"
    );
    $stmt->execute([$doctor_user_id]);
    $assigned_patients = $stmt->fetchAll();
} catch (Exception $e) {

// --- Fetch Doctor's Appointments ---
$appointments = [];
try {
    $stmt = $pdo->prepare(
        "SELECT a.id, a.appointment_time, a.status, p.first_name, p.last_name
         FROM appointments a
         JOIN patients p ON a.patient_user_id = p.user_id
         WHERE a.doctor_user_id = ?
         ORDER BY a.appointment_time DESC"
    );
    $stmt->execute([$doctor_user_id]);
    $appointments = $stmt->fetchAll();
} catch (Exception $e) {
    // In a real app, you'd have a more robust error handling system
    error_log('Doctor Dashboard - Patient Fetch Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">خطا در بارگذاری لیست بیماران.</div>';
}

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>داشبورد پزشک</h1>
    </div>
    <hr>

    <!-- Status Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Section: View Appointments -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-calendar-alt"></i> درخواست‌های نوبت</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>بیمار</th>
                            <th>زمان درخواستی</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="4" class="text-center">هیچ درخواستی برای نوبت ثبت نشده است.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($appointment['status']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <form method="POST" action="dashboard.php" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" name="confirm_appointment" class="btn btn-sm btn-success">تایید</button>
                                            </form>
                                            <form method="POST" action="dashboard.php" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" name="cancel_appointment" class="btn btn-sm btn-danger">لغو</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: View Assigned Patients -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-users"></i> بیماران شما</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>نام</th>
                            <th>نام خانوادگی</th>
                            <th>ایمیل</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assigned_patients)): ?>
                            <tr>
                                <td colspan="4" class="text-center">هیچ بیماری به شما اختصاص داده نشده است.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assigned_patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                    <td>
                                        <a href="view_patient.php?id=<?php echo $patient['user_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> مشاهده پروفایل
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
