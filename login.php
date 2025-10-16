<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
        logActivity(0, 'LOGIN_FAILED', 'CSRF token validation failed', 'WARNING');
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password';
        } else {
            // Check rate limiting
            $rateCheck = checkLoginAttempts($username);
            
            if (is_array($rateCheck) && isset($rateCheck['locked'])) {
                $error = 'Too many failed login attempts. Please try again in ' . $rateCheck['remaining'] . ' minutes.';
                logActivity(0, 'LOGIN_BLOCKED', "Rate limit exceeded for: $username", 'WARNING');
            } else {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("SELECT user_id, username, password, full_name, email FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        // Successful login
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['login_time'] = time();
                        
                        recordLoginAttempt($username, true);
                        logActivity($user['user_id'], 'LOGIN_SUCCESS', "Successful login: $username", 'INFO');

                        setFlashMessage('Welcome back, ' . e($user['full_name']) . '!', 'success');
                        redirect('dashboard.php');
                    } else {
                        recordLoginAttempt($username, false);
                        logActivity(0, 'LOGIN_FAILED', "Invalid credentials for: $username", 'WARNING');
                        $error = 'Invalid username or password';
                    }
                } catch (PDOException $e) {
                    logActivity(0, 'LOGIN_ERROR', 'Database error: ' . $e->getMessage(), 'ERROR');
                    $error = 'Login failed. Please try again.';
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
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-motorcycle"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Your Personal Bike Tracker</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input type="text" id="username" name="username" required 
                        value="<?php echo isset($_POST['username']) ? e($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>

            <!-- <div class="demo-info">
                <p><strong>Demo Credentials:</strong></p>
                <p>Username: <code>admin</code> | Password: <code>admin123</code></p>
            </div> -->
        </div>
    </div>
</body>
</html>
