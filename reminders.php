<?php
require_once 'config/config.php';
requireLogin();

$user_id = getUserId();
$db = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$bike_id = isset($_GET['bike_id']) ? $_GET['bike_id'] : null;

// Get all user bikes
$stmt = $db->prepare("SELECT bike_id, bike_name, registration_number FROM bikes WHERE user_id = ? ORDER BY bike_name");
$stmt->execute([$user_id]);
$user_bikes = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_reminder'])) {
        $bike_id = $_POST['bike_id'];
        $reminder_type = $_POST['reminder_type'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $due_date = $_POST['due_date'];
        $due_odometer = $_POST['due_odometer'];
        $priority = $_POST['priority'];

        try {
            $stmt = $db->prepare("INSERT INTO reminders (bike_id, user_id, reminder_type, title, description, due_date, due_odometer, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$bike_id, $user_id, $reminder_type, $title, $description, $due_date, $due_odometer, $priority]);
            
            setFlashMessage('Reminder created successfully!', 'success');
            redirect('reminders.php?bike_id=' . $bike_id);
        } catch (PDOException $e) {
            setFlashMessage('Failed to create reminder. Please try again.', 'error');
        }
    } elseif (isset($_POST['update_reminder'])) {
        $reminder_id = $_POST['reminder_id'];
        $bike_id = $_POST['bike_id'];
        $reminder_type = $_POST['reminder_type'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $due_date = $_POST['due_date'];
        $due_odometer = $_POST['due_odometer'];
        $priority = $_POST['priority'];

        try {
            $stmt = $db->prepare("UPDATE reminders SET bike_id=?, reminder_type=?, title=?, description=?, due_date=?, due_odometer=?, priority=? WHERE reminder_id=? AND user_id=?");
            $stmt->execute([$bike_id, $reminder_type, $title, $description, $due_date, $due_odometer, $priority, $reminder_id, $user_id]);
            
            setFlashMessage('Reminder updated successfully!', 'success');
            redirect('reminders.php?bike_id=' . $bike_id);
        } catch (PDOException $e) {
            setFlashMessage('Failed to update reminder. Please try again.', 'error');
        }
    } elseif (isset($_POST['complete_reminder'])) {
        $reminder_id = $_POST['reminder_id'];
        
        try {
            $stmt = $db->prepare("UPDATE reminders SET is_completed=1, completed_date=CURDATE() WHERE reminder_id=? AND user_id=?");
            $stmt->execute([$reminder_id, $user_id]);
            
            setFlashMessage('Reminder marked as completed!', 'success');
            redirect('reminders.php' . ($bike_id ? '?bike_id=' . $bike_id : ''));
        } catch (PDOException $e) {
            setFlashMessage('Failed to complete reminder. Please try again.', 'error');
        }
    } elseif (isset($_POST['delete_reminder'])) {
        $reminder_id = $_POST['reminder_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM reminders WHERE reminder_id=? AND user_id=?");
            $stmt->execute([$reminder_id, $user_id]);
            
            setFlashMessage('Reminder deleted successfully!', 'success');
            redirect('reminders.php' . ($bike_id ? '?bike_id=' . $bike_id : ''));
        } catch (PDOException $e) {
            setFlashMessage('Failed to delete reminder. Please try again.', 'error');
        }
    }
}

// Get reminder for editing
$edit_reminder = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM reminders WHERE reminder_id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $user_id]);
    $edit_reminder = $stmt->fetch();
    if ($edit_reminder) {
        $bike_id = $edit_reminder['bike_id'];
    }
}

// Get reminders with filters
$where_clause = "WHERE r.user_id = ?";
$params = [$user_id];

if ($bike_id) {
    $where_clause .= " AND r.bike_id = ?";
    $params[] = $bike_id;
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
if ($status_filter == 'pending') {
    $where_clause .= " AND r.is_completed = 0";
} elseif ($status_filter == 'completed') {
    $where_clause .= " AND r.is_completed = 1";
}

$stmt = $db->prepare("
    SELECT r.*, b.bike_name, b.registration_number, b.current_odometer
    FROM reminders r
    JOIN bikes b ON r.bike_id = b.bike_id
    $where_clause
    ORDER BY r.is_completed ASC, r.due_date ASC, r.priority DESC
");
$stmt->execute($params);
$reminders = $stmt->fetchAll();

$page_title = 'Reminders';
include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-bell"></i> Reminders</h1>
        <div class="filter-group">
            <?php if (count($user_bikes) > 0): ?>
                <label>Bike:</label>
                <select onchange="window.location.href='reminders.php?bike_id='+this.value+'&status=<?php echo $status_filter; ?>'">
                    <option value="">All Bikes</option>
                    <?php foreach ($user_bikes as $bike): ?>
                        <option value="<?php echo $bike['bike_id']; ?>" <?php echo $bike['bike_id'] == $bike_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bike['bike_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            
            <label>Status:</label>
            <select onchange="window.location.href='reminders.php?<?php echo $bike_id ? 'bike_id=' . $bike_id . '&' : ''; ?>status='+this.value">
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
            </select>
        </div>
    </div>
    <a href="?action=add<?php echo $bike_id ? '&bike_id=' . $bike_id : ''; ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Reminder
    </a>
</div>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="form-container">
        <h2><i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i> <?php echo $action == 'add' ? 'Add New Reminder' : 'Edit Reminder'; ?></h2>
        <form method="POST" action="" class="form-horizontal">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="reminder_id" value="<?php echo $edit_reminder['reminder_id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="bike_id">Select Bike *</label>
                    <select id="bike_id" name="bike_id" required>
                        <option value="">Choose a bike</option>
                        <?php foreach ($user_bikes as $bike): ?>
                            <option value="<?php echo $bike['bike_id']; ?>" 
                                    <?php echo ($edit_reminder && $bike['bike_id'] == $edit_reminder['bike_id']) || $bike['bike_id'] == $bike_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bike['bike_name']); ?> (<?php echo htmlspecialchars($bike['registration_number']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reminder_type">Reminder Type *</label>
                    <select id="reminder_type" name="reminder_type" required>
                        <option value="Service" <?php echo $edit_reminder && $edit_reminder['reminder_type'] == 'Service' ? 'selected' : ''; ?>>Service</option>
                        <option value="Insurance" <?php echo $edit_reminder && $edit_reminder['reminder_type'] == 'Insurance' ? 'selected' : ''; ?>>Insurance Renewal</option>
                        <option value="Pollution Check" <?php echo $edit_reminder && $edit_reminder['reminder_type'] == 'Pollution Check' ? 'selected' : ''; ?>>Pollution Check</option>
                        <option value="Registration Renewal" <?php echo $edit_reminder && $edit_reminder['reminder_type'] == 'Registration Renewal' ? 'selected' : ''; ?>>Registration Renewal</option>
                        <option value="Tire Change" <?php echo $edit_reminder && $edit_reminder['reminder_type'] == 'Tire Change' ? 'selected' : ''; ?>>Tire Change</option>
                        <option value="Chain Lubrication" <?php echo $edit_reminder && $edit_reminder['reminder_type'] == 'Chain Lubrication' ? 'selected' : ''; ?>>Chain Lubrication</option>
                        <option value="Custom" <?php echo $edit_reminder && $edit_reminder['reminder_type'] == 'Custom' ? 'selected' : ''; ?>>Custom</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Reminder Title *</label>
                <input type="text" id="title" name="title" required 
                       value="<?php echo $edit_reminder ? htmlspecialchars($edit_reminder['title']) : ''; ?>"
                       placeholder="e.g., Insurance Renewal Due">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Additional details about this reminder..."><?php echo $edit_reminder ? htmlspecialchars($edit_reminder['description']) : ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" 
                           value="<?php echo $edit_reminder ? $edit_reminder['due_date'] : ''; ?>">
                    <small>Set a date-based reminder</small>
                </div>

                <div class="form-group">
                    <label for="due_odometer">Due at Odometer (km)</label>
                    <input type="number" id="due_odometer" name="due_odometer" 
                           value="<?php echo $edit_reminder ? $edit_reminder['due_odometer'] : ''; ?>"
                           placeholder="e.g., 10000">
                    <small>Set a mileage-based reminder</small>
                </div>
            </div>

            <div class="form-group">
                <label for="priority">Priority *</label>
                <select id="priority" name="priority" required>
                    <option value="Low" <?php echo $edit_reminder && $edit_reminder['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo $edit_reminder && $edit_reminder['priority'] == 'Medium' ? 'selected' : 'selected'; ?>>Medium</option>
                    <option value="High" <?php echo $edit_reminder && $edit_reminder['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="<?php echo $action == 'add' ? 'add_reminder' : 'update_reminder'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Add Reminder' : 'Update Reminder'; ?>
                </button>
                <a href="reminders.php<?php echo $bike_id ? '?bike_id=' . $bike_id : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <?php if (count($reminders) > 0): ?>
        <div class="reminders-container">
            <?php foreach ($reminders as $reminder): ?>
                <?php
                $status_class = '';
                $status_text = '';
                $days_left = null;
                
                if (!$reminder['is_completed']) {
                    if ($reminder['due_date']) {
                        $days = floor((strtotime($reminder['due_date']) - time()) / 86400);
                        if ($days < 0) {
                            $status_class = 'overdue';
                            $status_text = 'Overdue by ' . abs($days) . ' days';
                        } elseif ($days == 0) {
                            $status_class = 'due-today';
                            $status_text = 'Due Today!';
                        } elseif ($days <= REMINDER_WARNING_DAYS) {
                            $status_class = 'due-soon';
                            $status_text = 'Due in ' . $days . ' days';
                        } else {
                            $status_class = 'upcoming';
                            $status_text = 'Due in ' . $days . ' days';
                        }
                    }
                    
                    if ($reminder['due_odometer'] && $reminder['current_odometer']) {
                        $km_left = $reminder['due_odometer'] - $reminder['current_odometer'];
                        if ($km_left <= 0) {
                            $status_class = 'overdue';
                            $status_text .= ($status_text ? ' | ' : '') . 'Odometer reached';
                        } elseif ($km_left <= 500) {
                            $status_class = $status_class == 'overdue' ? 'overdue' : 'due-soon';
                            $status_text .= ($status_text ? ' | ' : '') . $km_left . ' km left';
                        }
                    }
                } else {
                    $status_class = 'completed';
                    $status_text = 'Completed';
                }
                ?>
                
                <div class="reminder-card <?php echo $status_class; ?>">
                    <div class="reminder-header">
                        <div class="reminder-type">
                            <i class="fas fa-<?php 
                                echo $reminder['reminder_type'] == 'Service' ? 'wrench' : 
                                    ($reminder['reminder_type'] == 'Insurance' ? 'shield-alt' : 
                                    ($reminder['reminder_type'] == 'Pollution Check' ? 'smog' : 'bell')); 
                            ?>"></i>
                            <span><?php echo $reminder['reminder_type']; ?></span>
                        </div>
                        <span class="priority-badge priority-<?php echo strtolower($reminder['priority']); ?>">
                            <?php echo $reminder['priority']; ?>
                        </span>
                    </div>
                    
                    <div class="reminder-body">
                        <h3><?php echo htmlspecialchars($reminder['title']); ?></h3>
                        <p class="bike-name">
                            <i class="fas fa-motorcycle"></i>
                            <?php echo htmlspecialchars($reminder['bike_name']); ?> (<?php echo htmlspecialchars($reminder['registration_number']); ?>)
                        </p>
                        
                        <?php if ($reminder['description']): ?>
                            <p class="description"><?php echo htmlspecialchars($reminder['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="reminder-details">
                            <?php if ($reminder['due_date']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo formatDate($reminder['due_date']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($reminder['due_odometer']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span><?php echo number_format($reminder['due_odometer']); ?> km</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($status_text): ?>
                            <div class="reminder-status">
                                <i class="fas fa-info-circle"></i>
                                <?php echo $status_text; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="reminder-actions">
                        <?php if (!$reminder['is_completed']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="reminder_id" value="<?php echo $reminder['reminder_id']; ?>">
                                <input type="hidden" name="bike_id" value="<?php echo $reminder['bike_id']; ?>">
                                <button type="submit" name="complete_reminder" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Mark Complete
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="?action=edit&id=<?php echo $reminder['reminder_id']; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this reminder?');">
                            <input type="hidden" name="reminder_id" value="<?php echo $reminder['reminder_id']; ?>">
                            <input type="hidden" name="bike_id" value="<?php echo $reminder['bike_id']; ?>">
                            <button type="submit" name="delete_reminder" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-data-message">
            <i class="fas fa-bell fa-3x"></i>
            <h3>No <?php echo $status_filter == 'completed' ? 'Completed' : ($status_filter == 'pending' ? 'Pending' : ''); ?> Reminders</h3>
            <p>Set reminders for service, insurance, and other important tasks.</p>
            <a href="?action=add<?php echo $bike_id ? '&bike_id=' . $bike_id : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Your First Reminder
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
