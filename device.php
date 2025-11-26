<?php
session_start();
if (!isset($_SESSION['technician_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config.php';

$lookupSerial = trim($_GET['serial'] ?? '');
$device = null;
$message = '';
$errors = [];

if ($lookupSerial !== '') {
    $stmt = $conn->prepare('SELECT * FROM device_tracking WHERE serial_number = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $lookupSerial);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial = trim($_POST['serial_number'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $os = trim($_POST['os'] ?? '');
    $dateIssued = $_POST['date_issued'] ?? '';

    if ($serial === '') {
        $errors[] = 'Serial number is required.';
    }
    if ($model === '') {
        $errors[] = 'Model is required.';
    }
    if ($location === '') {
        $errors[] = 'Location is required.';
    }
    if ($os === '') {
        $errors[] = 'Operating system is required.';
    }
    if ($dateIssued === '') {
        $errors[] = 'Date issued is required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            'INSERT INTO device_tracking (serial_number, model, location, os, date_issued) 
             VALUES (?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $stmt->bind_param('sssss', $serial, $model, $location, $os, $dateIssued);
            if ($stmt->execute()) {
                $message = 'Device registered successfully. You can now create a ticket.';
                $lookupSerial = $serial;
                $device = [
                    'serial_number' => $serial,
                    'model' => $model,
                    'location' => $location,
                    'os' => $os,
                    'date_issued' => $dateIssued
                ];
            } else {
                $errors[] = 'Failed to register the device. This serial may already exist.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Unable to prepare device registration.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Verification | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Check Device</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-12">
                            <label for="serial" class="form-label">Serial Number</label>
                            <input type="text" id="serial" name="serial" class="form-control"
                                   value="<?php echo htmlspecialchars($lookupSerial); ?>" required>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="device.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                    <?php if ($lookupSerial !== '' && !$device && !$errors): ?>
                        <div class="alert alert-warning mt-3">
                            Device not found. Please register the device below.
                        </div>
                    <?php elseif ($device): ?>
                        <div class="alert alert-success mt-3">
                            Device found. You can proceed to <a href="ticket.php" class="alert-link">create a ticket</a>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Register Device</h5>
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
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="serial_number" class="form-label">Serial Number</label>
                                <input type="text" id="serial_number" name="serial_number" class="form-control"
                                       value="<?php echo htmlspecialchars($lookupSerial); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" id="model" name="model" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" id="location" name="location" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="os" class="form-label">Operating System</label>
                                <input type="text" id="os" name="os" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['os'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="date_issued" class="form-label">Date Issued</label>
                                <input type="date" id="date_issued" name="date_issued" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['date_issued'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success">Register Device</button>
                            <a href="ticket.php" class="btn btn-outline-primary">Skip to Ticket Creation</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

