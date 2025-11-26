<?php
session_start();
require_once __DIR__ . '/config.php';

$errors = [];
$statusMessage = '';

// Redirect logged in technicians
if (isset($_SESSION['technician_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        $errors[] = 'Email is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            'SELECT id_technician_assignment, first_name, last_name, password 
             FROM technician_assignment 
             WHERE email = ? LIMIT 1'
        );

        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $technician = $result->fetch_assoc();

            if (!$technician || !password_verify($password, $technician['password'])) {
                $errors[] = 'Invalid credentials. Please try again.';
            } else {
                $_SESSION['technician_id'] = $technician['id_technician_assignment'];
                $_SESSION['technician_name'] = $technician['first_name'] . ' ' . $technician['last_name'];
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $errors[] = 'Unable to process your request right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Login | Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h4">Technician Portal</h1>
                        <p class="text-muted mb-0">Ticketing System Workflow</p>
                    </div>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif ($statusMessage): ?>
                        <div class="alert alert-info mb-3">
                            <?php echo htmlspecialchars($statusMessage); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                   required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Log In</button>
                            <a href="signup.php" class="btn btn-outline-secondary">Sign Up</a>
                        </div>
                    </form>

                    <hr class="my-4">
                    <div class="small text-muted">
                        <strong>Process Overview</strong>
                        <ol class="mt-2 mb-0 ps-3">
                            <li>Check device presence; register if missing.</li>
                            <li>Create ticket for existing devices.</li>
                            <li>Assign technician and resolve issues.</li>
                            <li>Record part usage when needed.</li>
                            <li>Complete post-service feedback.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

