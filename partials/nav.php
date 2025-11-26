<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Ticketing System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="device.php">Device</a></li>
                <li class="nav-item"><a class="nav-link" href="ticket.php">Ticket</a></li>
                <li class="nav-item"><a class="nav-link" href="part_usage.php">Parts</a></li>
                <li class="nav-item"><a class="nav-link" href="post_service_feedback.php">Feedback</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 small">
                    <?php echo htmlspecialchars($_SESSION['technician_name'] ?? 'Technician'); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>

