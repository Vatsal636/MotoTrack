<?php
require_once 'config/config.php';

// Log logout activity
if (isLoggedIn()) {
    logActivity(getUserId(), 'LOGOUT', 'User logged out', 'INFO');
}

// Clear all session data
session_unset();
session_destroy();

// Set goodbye message
session_start();
setFlashMessage('You have been logged out successfully', 'info');

// Redirect to login page
redirect('login.php');
?>
