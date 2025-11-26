<?php
session_start();
if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config.php';

$deviceCount = 0;
$ticketCount = 0;
$partsLogged = 0;
$feedbackCount = 0;
$recentTickets = [];

function fetchSingleValue(mysqli $conn, string $query): int
{
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

$deviceCount = fetchSingleValue($conn, 'SELECT COUNT(*) as total FROM device_tracking');
$ticketCount = fetchSingleValue($conn, 'SELECT COUNT(*) as total FROM ticket_intake');
$partsLogged = fetchSingleValue($conn, 'SELECT COUNT(*) as total FROM part_usage');
$feedbackCount = fetchSingleValue($conn, 'SELECT COUNT(*) as total FROM post_service_feedback');

$stmt = $conn->prepare(
    'SELECT t.id_ticket_intake, d.serial_number, t.issues_description, t.date 
     FROM ticket_intake t 
     INNER JOIN device_tracking d ON d.id_device_tracking = t.id_device_tracking
     ORDER BY t.date DESC LIMIT 5'
);
if ($stmt) {
    $stmt->execute();
    $recentTickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials/nav.php'; ?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Tracked Devices</p>
                    <h3 class="mb-0"><?php echo $deviceCount; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Tickets Logged</p>
                    <h3 class="mb-0"><?php echo $ticketCount; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Parts Recorded</p>
                    <h3 class="mb-0"><?php echo $partsLogged; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted mb-1">Feedback Closed</p>
                    <h3 class="mb-0"><?php echo $feedbackCount; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">Recent Tickets</h5>
                    <?php if ($recentTickets): ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Serial</th>
                                    <th>Issue</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <tr>
                                        <td>#<?php echo (int)$ticket['id_ticket_intake']; ?></td>
                                        <td><?php echo htmlspecialchars($ticket['serial_number']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['issues_description']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No tickets logged yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">Workflow Shortcuts</h5>
                    <div class="d-grid gap-2">
                        <a href="device.php" class="btn btn-outline-primary">Device Verification</a>
                        <a href="ticket.php" class="btn btn-outline-primary">Create Ticket</a>
                        <a href="part_usage.php" class="btn btn-outline-primary">Record Part Usage</a>
                        <a href="post_service_feedback.php" class="btn btn-outline-primary">Post Service Feedback</a>
                    </div>
                    <hr>
                    <p class="small text-muted mb-1">Standard procedure:</p>
                    <ol class="small ps-3 mb-0">
                        <li>Verify or register the device.</li>
                        <li>Log the ticket and auto-assign technician.</li>
                        <li>Resolve issues and record necessary parts.</li>
                        <li>Complete post-service feedback.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

