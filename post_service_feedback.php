<?php
session_start();
if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config.php';

$errors = [];
$message = '';

$tickets = [];
$ticketQuery = $conn->query(
    'SELECT id_ticket_intake 
     FROM ticket_intake 
     ORDER BY id_ticket_intake DESC'
);
if ($ticketQuery) {
    $tickets = $ticketQuery->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int)($_POST['id_ticket_intake'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $dateSolved = $_POST['date_solved'] ?? date('Y-m-d');
    $technicianId = (int)$_SESSION['technician_id'];

    if ($ticketId === 0) {
        $errors[] = 'Ticket is required.';
    }
    if ($remarks === '') {
        $errors[] = 'Remarks are required.';
    }
    if ($status === '') {
        $errors[] = 'Status is required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            'INSERT INTO post_service_feedback (id_ticket_intake, id_technician_assignment, remarks, status, date_solved)
             VALUES (?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $stmt->bind_param('iisss', $ticketId, $technicianId, $remarks, $status, $dateSolved);
            if ($stmt->execute()) {
                $message = 'Feedback saved. Workflow finished.';
                $_POST = [];
            } else {
                $errors[] = 'Failed to save feedback.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Unable to prepare feedback statement.';
        }
    }
}

$feedbackLog = [];
$feedbackStmt = $conn->query(
    'SELECT f.id_post_service_feedback, f.id_ticket_intake, f.remarks, f.status, f.date_solved
     FROM post_service_feedback f
     ORDER BY f.id_post_service_feedback DESC LIMIT 10'
);
if ($feedbackStmt) {
    $feedbackLog = $feedbackStmt->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Service Feedback | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Close Ticket</h5>
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
                            <label for="id_ticket_intake" class="form-label">Ticket</label>
                            <select id="id_ticket_intake" name="id_ticket_intake" class="form-select" required>
                                <option value="">Select ticket</option>
                                <?php foreach ($tickets as $ticket): ?>
                                    <option value="<?php echo (int)$ticket['id_ticket_intake']; ?>"
                                        <?php echo ((int)$ticket['id_ticket_intake'] === (int)($_POST['id_ticket_intake'] ?? 0)) ? 'selected' : ''; ?>>
                                        Ticket #<?php echo (int)$ticket['id_ticket_intake']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select status</option>
                                <?php
                                $statusOptions = ['Completed', 'Pending Parts', 'Awaiting User', 'Closed'];
                                foreach ($statusOptions as $option):
                                    $selected = ($option === ($_POST['status'] ?? '')) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" required><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="date_solved" class="form-label">Date Solved</label>
                            <input type="date" class="form-control" id="date_solved" name="date_solved"
                                   value="<?php echo htmlspecialchars($_POST['date_solved'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Submit Feedback</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Recent Feedback</h5>
                    <?php if ($feedbackLog): ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ticket</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($feedbackLog as $feedback): ?>
                                    <tr>
                                        <td><?php echo (int)$feedback['id_post_service_feedback']; ?></td>
                                        <td>#<?php echo (int)$feedback['id_ticket_intake']; ?></td>
                                        <td><?php echo htmlspecialchars($feedback['status']); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['date_solved']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No feedback submitted yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

