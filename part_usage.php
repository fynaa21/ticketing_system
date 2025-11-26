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
    'SELECT t.id_ticket_intake, d.serial_number, d.model 
     FROM ticket_intake t 
     INNER JOIN device_tracking d ON d.id_device_tracking = t.id_device_tracking
     ORDER BY t.id_ticket_intake DESC'
);
if ($ticketQuery) {
    $tickets = $ticketQuery->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int)($_POST['id_ticket_intake'] ?? 0);
    $partName = trim($_POST['part_name'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $cost = trim($_POST['cost'] ?? '');
    $dateUsed = $_POST['date'] ?? date('Y-m-d');

    if ($ticketId === 0) {
        $errors[] = 'Select the related ticket.';
    }
    if ($partName === '') {
        $errors[] = 'Part name is required.';
    }
    if ($quantity <= 0) {
        $errors[] = 'Quantity must be greater than zero.';
    }
    if ($cost === '') {
        $errors[] = 'Cost is required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            'INSERT INTO part_usage (id_ticket_intake, part_name, quantity, cost, date)
             VALUES (?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $stmt->bind_param('isiss', $ticketId, $partName, $quantity, $cost, $dateUsed);
            if ($stmt->execute()) {
                $message = 'Part usage recorded. Continue to feedback if work is completed.';
                $_POST = [];
            } else {
                $errors[] = 'Failed to record part usage.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Unable to prepare part usage statement.';
        }
    }
}

$partHistory = [];
$partsStmt = $conn->query(
    'SELECT p.id_part_usage, t.id_ticket_intake, p.part_name, p.quantity, p.cost, p.date
     FROM part_usage p
     INNER JOIN ticket_intake t ON t.id_ticket_intake = p.id_ticket_intake
     ORDER BY p.id_part_usage DESC LIMIT 10'
);
if ($partsStmt) {
    $partHistory = $partsStmt->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Part Usage | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Record Parts</h5>
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
                                        #<?php echo (int)$ticket['id_ticket_intake']; ?> - <?php echo htmlspecialchars($ticket['serial_number'] . ' ' . $ticket['model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="part_name" class="form-label">Part Name</label>
                            <input type="text" class="form-control" id="part_name" name="part_name"
                                   value="<?php echo htmlspecialchars($_POST['part_name'] ?? ''); ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" min="1" class="form-control" id="quantity" name="quantity"
                                       value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Cost</label>
                                <input type="text" class="form-control" id="cost" name="cost"
                                       value="<?php echo htmlspecialchars($_POST['cost'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="date" class="form-label">Date Used</label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Part Usage</button>
                            <a href="post_service_feedback.php" class="btn btn-outline-secondary">Next: Feedback</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Recent Parts</h5>
                    <?php if ($partHistory): ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ticket</th>
                                    <th>Part</th>
                                    <th>Qty</th>
                                    <th>Cost</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($partHistory as $part): ?>
                                    <tr>
                                        <td><?php echo (int)$part['id_part_usage']; ?></td>
                                        <td>#<?php echo (int)$part['id_ticket_intake']; ?></td>
                                        <td><?php echo htmlspecialchars($part['part_name']); ?></td>
                                        <td><?php echo (int)$part['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($part['cost']); ?></td>
                                        <td><?php echo htmlspecialchars($part['date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No parts recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

