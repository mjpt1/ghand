<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once ROOT_PATH . '/includes/database.php';

// --- Authentication & Authorization ---

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// User must be a doctor
if ($_SESSION['user_role'] !== 'doctor') {
    // Redirect non-doctors to their own dashboard
    header('Location: dashboard.php');
    exit;
}

$doctor_user_id = $_SESSION['user_id'];
$patient_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$patient_user_id) {
    // No valid ID provided, redirect to dashboard
    header('Location: dashboard.php');
    exit;
}

// --- Data Fetching & Form Handling ---
$patient_profile = null;
$health_records = [];
$prescriptions = [];
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

try {
    // --- Handle Prescription Form Submission ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_prescription'])) {
        $prescription_details = trim($_POST['prescription_details']);
        if (!empty($prescription_details)) {
            $stmt = $pdo->prepare(
                "INSERT INTO prescriptions (doctor_user_id, patient_user_id, prescription_details) VALUES (?, ?, ?)"
            );
            $stmt->execute([$doctor_user_id, $patient_user_id, $prescription_details]);

            // Set success message and redirect to prevent form resubmission
            $_SESSION['success_message'] = "نسخه با موفقیت ثبت شد.";
            header("Location: view_patient.php?id=" . $patient_user_id);
            exit;
        }
    }

    // 1. Fetch patient profile and verify the doctor is assigned to this patient
    $stmt = $pdo->prepare(
        "SELECT p.*, u.email
         FROM patients p
         JOIN users u ON p.user_id = u.id
         WHERE p.user_id = ? AND p.assigned_doctor_id = ?"
    );
    $stmt->execute([$patient_user_id, $doctor_user_id]);
    $patient_profile = $stmt->fetch();

    // If no profile is found, it means the patient doesn't exist OR is not assigned to this doctor.
    if (!$patient_profile) {
        // Redirect to dashboard with an error message (optional)
        $_SESSION['error_message'] = "Patient not found or you do not have permission to view this profile.";
        header('Location: dashboard.php');
        exit;
    }

    // 2. Fetch patient's health records
    $stmt = $pdo->prepare("SELECT record_type, value1, value2, recorded_at, notes FROM health_records WHERE patient_user_id = ? ORDER BY recorded_at DESC");
    $stmt->execute([$patient_user_id]);
    $health_records = $stmt->fetchAll();

    // 3. Fetch patient's prescriptions
    $stmt = $pdo->prepare("SELECT id, prescription_details, status, created_at FROM prescriptions WHERE patient_user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$patient_user_id]);
    $prescriptions = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("View Patient Error: " . $e->getMessage());
    // In a real app, show a proper error page
    die("An error occurred while fetching patient data.");
}


header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشاهده پروفایل بیمار - <?php echo htmlspecialchars($patient_profile['first_name'] . ' ' . $patient_profile['last_name']); ?></title>
    <!-- Bootstrap 5 RTL CSS & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; background-color: #f8f9fa; }
    </style>
</head>
<body>
    <?php include 'dashboard_nav.php'; // We'll create a reusable nav bar component ?>

    <div class="container mt-4">
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">پروفایل بیمار: <?php echo htmlspecialchars($patient_profile['first_name'] . ' ' . $patient_profile['last_name']); ?></h1>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> بازگشت به داشبورد
            </a>
        </div>

        <!-- Patient Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-user-circle"></i> اطلاعات بیمار</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><strong>نام:</strong> <?php echo htmlspecialchars($patient_profile['first_name']); ?></div>
                    <div class="col-md-6"><strong>نام خانوادگی:</strong> <?php echo htmlspecialchars($patient_profile['last_name']); ?></div>
                    <div class="col-md-6"><strong>ایمیل:</strong> <?php echo htmlspecialchars($patient_profile['email']); ?></div>
                    <div class="col-md-6"><strong>کد ملی:</strong> <?php echo htmlspecialchars($patient_profile['national_id'] ?? 'ثبت نشده'); ?></div>
                    <div class="col-md-6"><strong>تاریخ تولد:</strong> <?php echo htmlspecialchars($patient_profile['date_of_birth'] ?? 'ثبت نشده'); ?></div>
                    <div class="col-md-6"><strong>جنسیت:</strong> <?php echo htmlspecialchars($patient_profile['gender'] ?? 'ثبت نشده'); ?></div>
                </div>
            </div>
        </div>

        <!-- Health Records Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-chart-line"></i> سوابق سلامتی بیمار</h5>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#prescriptionModal">
                    <i class="fas fa-file-medical"></i> صدور نسخه جدید
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>نوع رکورد</th>
                                <th>مقدار</th>
                                <th>یادداشت</th>
                                <th>تاریخ ثبت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($health_records)): ?>
                                <tr><td colspan="4" class="text-center">هیچ رکوردی برای این بیمار ثبت نشده است.</td></tr>
                            <?php else: ?>
                                <?php foreach ($health_records as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['record_type']); ?></td>
                                        <td>
                                            <?php
                                                echo htmlspecialchars($record['value1']);
                                                if (!empty($record['value2'])) { echo ' / ' . htmlspecialchars($record['value2']); }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($record['recorded_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Prescriptions Card -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-prescription"></i> نسخه‌های صادر شده</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>کد نسخه</th>
                                <th>جزئیات</th>
                                <th>وضعیت</th>
                                <th>تاریخ صدور</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prescriptions)): ?>
                                <tr><td colspan="4" class="text-center">هیچ نسخه‌ای برای این بیمار صادر نشده است.</td></tr>
                            <?php else: ?>
                                <?php foreach ($prescriptions as $prescription): ?>
                                    <tr>
                                        <td><?php echo $prescription['id']; ?></td>
                                        <td><pre><?php echo htmlspecialchars($prescription['prescription_details']); ?></pre></td>
                                        <td><?php echo htmlspecialchars($prescription['status']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Prescription Modal -->
    <div class="modal fade" id="prescriptionModal" tabindex="-1" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="view_patient.php?id=<?php echo $patient_user_id; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="prescriptionModalLabel">صدور نسخه برای <?php echo htmlspecialchars($patient_profile['first_name'] . ' ' . $patient_profile['last_name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="prescription_details" class="form-label">جزئیات نسخه</label>
                            <textarea class="form-control" id="prescription_details" name="prescription_details" rows="10" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" name="create_prescription" class="btn btn-primary">ثبت نسخه</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center mt-5 py-3 bg-light">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> سیستم وب مدیریت جامع سلامت</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chat Box UI -->
    <div id="chat-box" class="card shadow">
        <div class="card-header bg-primary text-white">
            چت با <?php echo htmlspecialchars($patient_profile['first_name']); ?>
        </div>
        <div id="chat-messages" class="card-body">
            <!-- Messages will be appended here -->
        </div>
        <div class="card-footer">
            <div class="input-group">
                <input type="text" id="chat-input" class="form-control" placeholder="پیام خود را بنویسید...">
                <button id="chat-send-btn" class="btn btn-primary">ارسال</button>
            </div>
        </div>
    </div>
    <style>
        #chat-box {
            position: fixed;
            bottom: 0;
            right: 20px;
            width: 350px;
            height: 400px;
            border-radius: 15px 15px 0 0;
            display: flex;
            flex-direction: column;
        }
        #chat-messages {
            flex-grow: 1;
            overflow-y: auto;
        }
    </style>

    <script src="assets/js/chat.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const chatInput = document.getElementById('chat-input');
            const sendBtn = document.getElementById('chat-send-btn');

            const recipientId = <?php echo $patient_user_id; ?>;
            const currentUserId = <?php echo $doctor_user_id; ?>;

            function appendMessage(message) {
                const msgDiv = document.createElement('div');
                const isSender = message.sender_id === currentUserId;
                msgDiv.className = `p-2 my-1 rounded ${isSender ? 'bg-primary text-white ms-auto' : 'bg-light text-dark'}`;
                msgDiv.style.maxWidth = '75%';
                msgDiv.textContent = message.content;
                chatMessages.appendChild(msgDiv);
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            // Initialize the chat client
            ChatClient.init(function(message) {
                // This is the callback for when a new message is received
                // We only care about messages from the patient we are currently viewing
                if (message.sender_id === recipientId) {
                    appendMessage(message);
                }
            });

            // Handle sending a message
            function sendMessage() {
                const messageContent = chatInput.value.trim();
                if (messageContent) {
                    ChatClient.sendMessage(recipientId, messageContent);
                    // Also display the sent message immediately in the UI
                    appendMessage({ sender_id: currentUserId, content: messageContent });
                    chatInput.value = '';
                }
            }

            sendBtn.addEventListener('click', sendMessage);
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>
