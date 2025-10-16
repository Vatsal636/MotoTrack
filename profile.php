<?php
require_once 'config/config.php';
requireLogin();

$user_id = getUserId();
$db = getDB();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);

        // Validation
        if (empty($full_name) || empty($email)) {
            $error = 'Full name and email are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } else {
            try {
                // Check if email already exists for another user
                $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $error = 'Email already in use by another account';
                } else {
                    $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
                    $stmt->execute([$full_name, $email, $phone, $user_id]);
                    
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    
                    setFlashMessage('Profile updated successfully!', 'success');
                    redirect('profile.php');
                }
            } catch (PDOException $e) {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } else {
            try {
                // Verify current password
                $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($current_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    
                    setFlashMessage('Password changed successfully!', 'success');
                    redirect('profile.php');
                } else {
                    $error = 'Current password is incorrect';
                }
            } catch (PDOException $e) {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user statistics
$stmt = $db->prepare("SELECT COUNT(*) as bike_count FROM bikes WHERE user_id = ?");
$stmt->execute([$user_id]);
$bike_stats = $stmt->fetch();

$stmt = $db->prepare("SELECT COUNT(*) as trip_count, COALESCE(SUM(distance), 0) as total_distance FROM trips WHERE user_id = ?");
$stmt->execute([$user_id]);
$trip_stats = $stmt->fetch();

$stmt = $db->prepare("SELECT COUNT(*) as fuel_count, COALESCE(SUM(fuel_cost), 0) as total_fuel_cost FROM fuel_logs WHERE user_id = ?");
$stmt->execute([$user_id]);
$fuel_stats = $stmt->fetch();

$page_title = 'My Profile';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
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
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Profile Information -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-user"></i> Profile Information</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small>Username cannot be changed</small>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-lock"></i> Change Password</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Account Statistics -->
<div class="dashboard-card">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Account Statistics</h3>
    </div>
    <div class="card-body">
        <div class="stats-mini-grid">
            <div class="stat-mini-card">
                <i class="fas fa-motorcycle"></i>
                <div>
                    <h3><?php echo $bike_stats['bike_count']; ?></h3>
                    <p>Bikes Registered</p>
                </div>
            </div>

            <div class="stat-mini-card">
                <i class="fas fa-route"></i>
                <div>
                    <h3><?php echo $trip_stats['trip_count']; ?></h3>
                    <p>Total Trips</p>
                </div>
            </div>

            <div class="stat-mini-card">
                <i class="fas fa-road"></i>
                <div>
                    <h3><?php echo number_format($trip_stats['total_distance']); ?> km</h3>
                    <p>Distance Traveled</p>
                </div>
            </div>

            <div class="stat-mini-card">
                <i class="fas fa-rupee-sign"></i>
                <div>
                    <h3><?php echo formatCurrency($fuel_stats['total_fuel_cost']); ?></h3>
                    <p>Fuel Expenses</p>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <p><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></p>
            <p><strong>Last Updated:</strong> <?php echo formatDate($user['updated_at']); ?></p>
        </div>
    </div>
</div>

<!-- Account Actions -->
<div class="dashboard-card">
    <div class="card-header">
        <h3><i class="fas fa-cog"></i> Account Actions</h3>
    </div>
    <div class="card-body">
        <div class="action-buttons">
            <a href="reports.php" class="action-btn">
                <i class="fas fa-download"></i>
                <span>Export Data</span>
            </a>
            <a href="#" onclick="return confirm('This feature will be available soon!');" class="action-btn">
                <i class="fas fa-database"></i>
                <span>Backup Data</span>
            </a>
            <a href="#" onclick="return confirm('Are you sure you want to delete all your data? This action cannot be undone!');" class="action-btn">
                <i class="fas fa-trash-alt"></i>
                <span>Delete Account</span>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
