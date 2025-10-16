<?php
require_once 'config/config.php';
requireLogin();

$user_id = getUserId();

$page_title = 'Settings';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Settings</h1>
</div>

<div class="dashboard-grid">
    <!-- Application Settings -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-sliders-h"></i> Application Settings</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label>
                        <input type="checkbox" checked disabled>
                        Email Notifications
                    </label>
                    <small>Receive email notifications for reminders (Coming Soon)</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" checked disabled>
                        Reminder Alerts
                    </label>
                    <small>Show reminder alerts on dashboard</small>
                </div>

                <div class="form-group">
                    <label for="date_format">Date Format</label>
                    <select id="date_format" disabled>
                        <option>DD/MM/YYYY</option>
                        <option selected>DD MMM YYYY</option>
                        <option>YYYY-MM-DD</option>
                    </select>
                    <small>Display format for dates (Coming Soon)</small>
                </div>

                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" disabled>
                        <option selected>INR (₹)</option>
                        <option>USD ($)</option>
                        <option>EUR (€)</option>
                    </select>
                    <small>Currency for cost display (Coming Soon)</small>
                </div>

                <div class="form-group">
                    <label for="distance_unit">Distance Unit</label>
                    <select id="distance_unit" disabled>
                        <option selected>Kilometers (km)</option>
                        <option>Miles (mi)</option>
                    </select>
                    <small>Unit for distance measurement (Coming Soon)</small>
                </div>

                <div class="form-group">
                    <label for="fuel_unit">Fuel Unit</label>
                    <select id="fuel_unit" disabled>
                        <option selected>Liters (L)</option>
                        <option>Gallons (gal)</option>
                    </select>
                    <small>Unit for fuel measurement (Coming Soon)</small>
                </div>

                <button type="submit" class="btn btn-primary" disabled>
                    <i class="fas fa-save"></i> Save Settings (Coming Soon)
                </button>
            </form>
        </div>
    </div>

    <!-- Display Settings -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-palette"></i> Display Settings</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="theme">Theme</label>
                    <select id="theme" disabled>
                        <option selected>Light</option>
                        <option>Dark (Coming Soon)</option>
                        <option>Auto (Coming Soon)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="language">Language</label>
                    <select id="language" disabled>
                        <option selected>English</option>
                        <option>Hindi (Coming Soon)</option>
                        <option>Spanish (Coming Soon)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="records_per_page">Records per Page</label>
                    <select id="records_per_page" disabled>
                        <option selected>10</option>
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" disabled>
                    <i class="fas fa-save"></i> Save Settings (Coming Soon)
                </button>
            </form>
        </div>
    </div>

    <!-- Privacy Settings -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-shield-alt"></i> Privacy & Security</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>
                    <input type="checkbox" checked disabled>
                    Two-Factor Authentication
                </label>
                <small>Add an extra layer of security (Coming Soon)</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" disabled>
                    Share Anonymous Usage Data
                </label>
                <small>Help us improve MotoTrack (Coming Soon)</small>
            </div>

            <div class="form-group">
                <label>Session Timeout</label>
                <select disabled>
                    <option>30 minutes</option>
                    <option selected>1 hour</option>
                    <option>2 hours</option>
                    <option>Never</option>
                </select>
                <small>Auto logout after inactivity (Coming Soon)</small>
            </div>

            <hr style="margin: 20px 0;">

            <h4 style="margin-bottom: 15px;">Active Sessions</h4>
            <div style="background: var(--light-color); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <p><strong>Current Device</strong></p>
                <p><small><i class="fas fa-desktop"></i> <?php echo $_SERVER['HTTP_USER_AGENT']; ?></small></p>
                <p><small><i class="fas fa-map-marker-alt"></i> IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></small></p>
            </div>

            <button type="button" class="btn btn-danger" disabled>
                <i class="fas fa-sign-out-alt"></i> Logout All Devices (Coming Soon)
            </button>
        </div>
    </div>

    <!-- Data Management -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-database"></i> Data Management</h3>
        </div>
        <div class="card-body">
            <div class="action-buttons">
                <a href="#" onclick="alert('Export feature coming soon!'); return false;" class="action-btn">
                    <i class="fas fa-file-export"></i>
                    <span>Export All Data</span>
                </a>
                <a href="#" onclick="alert('Import feature coming soon!'); return false;" class="action-btn">
                    <i class="fas fa-file-import"></i>
                    <span>Import Data</span>
                </a>
                <a href="#" onclick="alert('Backup feature coming soon!'); return false;" class="action-btn">
                    <i class="fas fa-cloud-download-alt"></i>
                    <span>Download Backup</span>
                </a>
            </div>

            <hr style="margin: 20px 0;">

            <h4 style="color: var(--danger-color); margin-bottom: 15px;">Danger Zone</h4>
            
            <button type="button" class="btn btn-danger" 
                    onclick="if(confirm('Are you sure you want to delete all your data? This action cannot be undone!')) { alert('Feature coming soon!'); }">
                <i class="fas fa-trash-alt"></i> Delete All Data
            </button>
            
            <button type="button" class="btn btn-danger" style="margin-left: 10px;"
                    onclick="if(confirm('Are you sure you want to delete your account? This action cannot be undone!')) { alert('Feature coming soon!'); }">
                <i class="fas fa-user-times"></i> Delete Account
            </button>
        </div>
    </div>
</div>

<!-- Information Note -->
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>Note:</strong> Most settings features are planned for future releases. 
    Currently, you can update your profile and change password from the <a href="profile.php">Profile page</a>.
</div>

<!-- About Application -->
<div class="dashboard-card">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> About MotoTrack</h3>
    </div>
    <div class="card-body">
        <table class="stats-table">
            <tr>
                <td>Application Name</td>
                <td><strong><?php echo APP_NAME; ?></strong></td>
            </tr>
            <tr>
                <td>Version</td>
                <td><strong><?php echo APP_VERSION; ?></strong></td>
            </tr>
            <tr>
                <td>PHP Version</td>
                <td><strong><?php echo phpversion(); ?></strong></td>
            </tr>
            <tr>
                <td>Database</td>
                <td><strong>MySQL <?php 
                    try {
                        $db = getDB();
                        $version = $db->query('SELECT VERSION()')->fetchColumn();
                        echo $version;
                    } catch(Exception $e) {
                        echo 'Connected';
                    }
                ?></strong></td>
            </tr>
            <tr>
                <td>Developed By</td>
                <td><strong>MotoTrack Team</strong></td>
            </tr>
            <tr>
                <td>License</td>
                <td><strong>MIT License</strong></td>
            </tr>
        </table>

        <div style="margin-top: 20px; text-align: center;">
            <a href="README.md" target="_blank" class="btn btn-secondary">
                <i class="fas fa-book"></i> View Documentation
            </a>
            <a href="https://github.com" target="_blank" class="btn btn-secondary" style="margin-left: 10px;">
                <i class="fab fa-github"></i> GitHub Repository
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
