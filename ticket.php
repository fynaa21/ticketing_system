<?php
session_start();
if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config.php';

$errors = [];
$message = '';
$deviceOptions = [];

$deviceQuery = $conn->query('SELECT id_device_tracking, serial_number, model FROM device_tracking ORDER BY serial_number ASC');
if ($deviceQuery) {
    $deviceOptions = $deviceQuery->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceId = (int)($_POST['id_device_tracking'] ?? 0);
    $reportedBy = trim($_POST['reported_by'] ?? '');
    $issuesDescription = trim($_POST['issues_description'] ?? '');
    $ticketDate = $_POST['date'] ?? date('Y-m-d');
    $technicianId = (int)$_SESSION['technician_id'];

    if ($deviceId === 0) {
        $errors[] = 'Please select a device.';
    }
    if ($reportedBy === '') {
        $errors[] = 'Reported by is required.';
    }
    if ($issuesDescription === '') {
        $errors[] = 'Issues description is required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            'INSERT INTO ticket_intake (id_device_tracking, id_technician_assignment, reported_by, issues_description, date)
             VALUES (?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $stmt->bind_param('iisss', $deviceId, $technicianId, $reportedBy, $issuesDescription, $ticketDate);
            if ($stmt->execute()) {
                $message = 'Ticket created and assigned to you. Proceed to repair and parts tracking.';
                $_POST = [];
            } else {
                $errors[] = 'Unable to create ticket. Please try again.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Unable to prepare ticket creation.';
        }
    }
}

$recentTickets = [];
$ticketStmt = $conn->prepare(
    'SELECT t.id_ticket_intake, d.serial_number, d.model, t.issues_description, t.date 
     FROM ticket_intake t 
     INNER JOIN device_tracking d ON d.id_device_tracking = t.id_device_tracking
     ORDER BY t.id_ticket_intake DESC LIMIT 10'
);
if ($ticketStmt) {
    $ticketStmt->execute();
    $recentTickets = $ticketStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ticketStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Log a Ticket</h5>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif ($message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="id_device_tracking" class="form-label">Device</label>
                            <select class="form-select" id="id_device_tracking" name="id_device_tracking" required>
                                <option value="">Select device</option>
                                <?php foreach ($deviceOptions as $device): ?>
                                    <option value="<?php echo (int)$device['id_device_tracking']; ?>"
                                        <?php echo ((int)($device['id_device_tracking']) === (int)($_POST['id_device_tracking'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($device['serial_number'] . ' - ' . $device['model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reported_by" class="form-label">Reported By</label>
                            <input type="text" class="form-control" id="reported_by" name="reported_by"
                                   value="<?php echo htmlspecialchars($_POST['reported_by'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="issues_description" class="form-label">Issues Description</label>
                            <textarea class="form-control" id="issues_description" name="issues_description" rows="3" required><?php echo htmlspecialchars($_POST['issues_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Ticket Date</label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create Ticket</button>
                            <a href="device.php" class="btn btn-outline-secondary">Back to Device Check</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Recent Tickets</h5>
                    <?php if ($recentTickets): ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Serial</th>
                                    <th>Issue</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo (int)$ticket['id_ticket_intake']; ?></td>
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
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

