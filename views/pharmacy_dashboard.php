<?php
// This file is included by dashboard.php, so it has access to $pdo and session data.
// Ensure user is a pharmacy
if ($_SESSION['user_role'] !== 'pharmacy') {
    die('Access Denied: You are not a pharmacy user.');
}

$pharmacy_user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// --- Handle Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $prescription_id = filter_input(INPUT_POST, 'prescription_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['issued', 'filled', 'cancelled'];

    if ($prescription_id && in_array($new_status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE prescriptions SET status = ?, pharmacy_user_id = ? WHERE id = ?"
            );
            $stmt->execute([$new_status, $pharmacy_user_id, $prescription_id]);
            $success_message = "وضعیت نسخه با موفقیت به‌روزرسانی شد.";
        } catch (Exception $e) {
            $error_message = "خطا در به‌روزرسانی وضعیت.";
            error_log('Pharmacy Dashboard - Status Update Error: ' . $e->getMessage());
        }
    } else {
        $error_message = "درخواست نامعتبر.";
    }
}


// --- Fetch Prescriptions ---
$prescriptions = [];
$filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);

try {
    $sql = "SELECT
            pres.id,
            pres.prescription_details,
            pres.status,
            pres.created_at,
            pat.first_name AS patient_first_name,
            pat.last_name AS patient_last_name,
            doc.first_name AS doctor_first_name,
            doc.last_name AS doctor_last_name
         FROM prescriptions AS pres
         JOIN patients AS pat ON pres.patient_user_id = pat.user_id
         JOIN doctors AS doc ON pres.doctor_user_id = doc.user_id";

    $params = [];
    if (!empty($filter_status) && in_array($filter_status, ['issued', 'filled', 'cancelled'])) {
        $sql .= " WHERE pres.status = ?";
        $params[] = $filter_status;
    }

    $sql .= " ORDER BY pres.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $prescriptions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Pharmacy Dashboard - Prescription Fetch Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">خطا در بارگذاری لیست نسخه‌ها.</div>';
}

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h1>داشبورد داروخانه</h1>
    </div>
    <hr>

    <!-- Status Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> فیلتر کردن نسخه‌ها</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="dashboard.php">
                <div class="row">
                    <div class="col-md-4">
                        <label for="filter_status" class="form-label">فیلتر بر اساس وضعیت</label>
                        <select name="status" id="filter_status" class="form-select">
                            <option value="" <?php if(empty($filter_status)) echo 'selected'; ?>>همه وضعیت‌ها</option>
                            <option value="issued" <?php if($filter_status === 'issued') echo 'selected'; ?>>Issued</option>
                            <option value="filled" <?php if($filter_status === 'filled') echo 'selected'; ?>>Filled</option>
                            <option value="cancelled" <?php if($filter_status === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-info">اعمال فیلتر</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Section: View Prescriptions -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-prescription-bottle-alt"></i> لیست نسخه‌ها</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>کد نسخه</th>
                            <th>بیمار</th>
                            <th>پزشک</th>
                            <th>تاریخ صدور</th>
                            <th>جزئیات</th>
                            <th>وضعیت فعلی</th>
                            <th>تغییر وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prescriptions)): ?>
                            <tr>
                                <td colspan="7" class="text-center">هیچ نسخه‌ای یافت نشد.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <tr>
                                    <td><?php echo $prescription['id']; ?></td>
                                    <td><?php echo htmlspecialchars($prescription['patient_first_name'] . ' ' . $prescription['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['doctor_first_name'] . ' ' . $prescription['doctor_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['created_at']); ?></td>
                                    <td><pre><?php echo htmlspecialchars($prescription['prescription_details']); ?></pre></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($prescription['status'] === 'filled' ? 'success' : ($prescription['status'] === 'cancelled' ? 'danger' : 'warning')); ?>">
                                            <?php echo htmlspecialchars($prescription['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="dashboard.php" class="d-flex">
                                            <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                                            <select name="new_status" class="form-select form-select-sm me-2">
                                                <option value="issued" <?php if($prescription['status'] === 'issued') echo 'selected'; ?>>Issued</option>
                                                <option value="filled" <?php if($prescription['status'] === 'filled') echo 'selected'; ?>>Filled</option>
                                                <option value="cancelled" <?php if($prescription['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">ثبت</button>
                                        </form>
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
