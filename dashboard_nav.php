<?php
// This component assumes session has been started and user data is available.
// It should be included from a page that has already handled session logic.
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-heartbeat"></i>
            سیستم مدیریت سلامت
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">داشبورد</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="navbar-text text-white me-3">
                    <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?> (<?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?>)
                </span>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> خروج
                </a>
            </div>
        </div>
    </div>
</nav>
