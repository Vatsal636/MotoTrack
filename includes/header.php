<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MotoTrack - Your Personal Bike Tracker for fuel logs, service records, and maintenance reminders">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; ?><?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php
// Set security headers (if not already set by .htaccess)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
?>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-motorcycle"></i>
                <span><?php echo e(APP_NAME); ?></span>
            </div>
            
            <button class="nav-toggle" id="navToggle">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo APP_URL; ?>dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a></li>
                <li><a href="<?php echo APP_URL; ?>bikes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bikes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-motorcycle"></i> My Bikes
                </a></li>
                <li><a href="<?php echo APP_URL; ?>fuel.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'fuel.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gas-pump"></i> Fuel Logs
                </a></li>
                <li><a href="<?php echo APP_URL; ?>service.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'service.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wrench"></i> Service
                </a></li>
                <li><a href="<?php echo APP_URL; ?>reminders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reminders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Reminders
                </a></li>
                <li><a href="<?php echo APP_URL; ?>reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a></li>
                <li class="nav-user">
                    <a href="#" class="user-menu-toggle">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo e($_SESSION['full_name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="user-dropdown">
                        <li><a href="<?php echo APP_URL; ?>profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                        <li><a href="<?php echo APP_URL; ?>settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><a href="<?php echo APP_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo e($flash['type']); ?>" id="flashMessage">
                <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : ($flash['type'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo e($flash['message']); ?>
                <button class="alert-close" onclick="document.getElementById('flashMessage').remove();">×</button>
            </div>
        <?php endif; ?>
