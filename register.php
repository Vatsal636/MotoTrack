<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $full_name = sanitizeInput($_POST['full_name']);
        $phone = sanitizeInput($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
            $error = 'Please fill in all required fields';
        } elseif (!validateInput($email, 'email')) {
            $error = 'Invalid email address';
        } elseif (!validateInput($username, 'alphanumeric')) {
            $error = 'Username must contain only letters and numbers';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters long';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            // Validate password strength
            $passwordValidation = validatePasswordStrength($password);
            if ($passwordValidation !== true) {
                $error = implode('<br>', $passwordValidation);
            } else {
                try {
                    $db = getDB();
                    
                    // Check if username exists
                    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = 'Username already exists';
                    } else {
                        // Check if email exists
                        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = 'Email already registered';
                        } else {
                            // Insert new user
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$username, $email, $hashed_password, $full_name, $phone]);

                            logActivity($db->lastInsertId(), 'USER_REGISTERED', "New user: $username", 'INFO');
                            $success = 'Registration successful! You can now login.';
                        }
                    }
                } catch (PDOException $e) {
                    logActivity(0, 'REGISTRATION_ERROR', 'Database error: ' . $e->getMessage(), 'ERROR');
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box register-box">
            <div class="login-header">
                <i class="fas fa-motorcycle"></i>
                <h1>Create Account</h1>
                <p>Join <?php echo APP_NAME; ?> today</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                    <a href="login.php">Click here to login</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username *
                    </label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? e($_POST['username']) : ''; ?>">
                    <small>Only letters and numbers, minimum 3 characters</small>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email *
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-id-card"></i> Full Name *
                    </label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? e($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo isset($_POST['phone']) ? e($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password *
                    </label>
                    <input type="password" id="password" name="password" required>
                    <small>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters, must include uppercase, lowercase, and number</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password *
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <div class="login-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
