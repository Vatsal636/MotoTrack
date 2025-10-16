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
    if (isset($_POST['add_trip'])) {
        $bike_id = $_POST['bike_id'];
        $trip_date = $_POST['trip_date'];
        $start_odometer = $_POST['start_odometer'];
        $end_odometer = $_POST['end_odometer'];
        $start_location = sanitizeInput($_POST['start_location']);
        $end_location = sanitizeInput($_POST['end_location']);
        $trip_purpose = $_POST['trip_purpose'];
        $notes = sanitizeInput($_POST['notes']);

        if ($end_odometer <= $start_odometer) {
            setFlashMessage('End odometer must be greater than start odometer.', 'error');
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO trips (bike_id, user_id, trip_date, start_odometer, end_odometer, start_location, end_location, trip_purpose, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$bike_id, $user_id, $trip_date, $start_odometer, $end_odometer, $start_location, $end_location, $trip_purpose, $notes]);
                
                // Update bike odometer
                $stmt = $db->prepare("UPDATE bikes SET current_odometer = ? WHERE bike_id = ? AND user_id = ?");
                $stmt->execute([$end_odometer, $bike_id, $user_id]);
                
                setFlashMessage('Trip added successfully!', 'success');
                redirect('trips.php?bike_id=' . $bike_id);
            } catch (PDOException $e) {
                setFlashMessage('Failed to add trip. Please try again.', 'error');
            }
        }
    } elseif (isset($_POST['update_trip'])) {
        $trip_id = $_POST['trip_id'];
        $bike_id = $_POST['bike_id'];
        $trip_date = $_POST['trip_date'];
        $start_odometer = $_POST['start_odometer'];
        $end_odometer = $_POST['end_odometer'];
        $start_location = sanitizeInput($_POST['start_location']);
        $end_location = sanitizeInput($_POST['end_location']);
        $trip_purpose = $_POST['trip_purpose'];
        $notes = sanitizeInput($_POST['notes']);

        if ($end_odometer <= $start_odometer) {
            setFlashMessage('End odometer must be greater than start odometer.', 'error');
        } else {
            try {
                $stmt = $db->prepare("UPDATE trips SET bike_id=?, trip_date=?, start_odometer=?, end_odometer=?, start_location=?, end_location=?, trip_purpose=?, notes=? WHERE trip_id=? AND user_id=?");
                $stmt->execute([$bike_id, $trip_date, $start_odometer, $end_odometer, $start_location, $end_location, $trip_purpose, $notes, $trip_id, $user_id]);
                
                setFlashMessage('Trip updated successfully!', 'success');
                redirect('trips.php?bike_id=' . $bike_id);
            } catch (PDOException $e) {
                setFlashMessage('Failed to update trip. Please try again.', 'error');
            }
        }
    } elseif (isset($_POST['delete_trip'])) {
        $trip_id = $_POST['trip_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM trips WHERE trip_id=? AND user_id=?");
            $stmt->execute([$trip_id, $user_id]);
            
            setFlashMessage('Trip deleted successfully!', 'success');
            redirect('trips.php' . ($bike_id ? '?bike_id=' . $bike_id : ''));
        } catch (PDOException $e) {
            setFlashMessage('Failed to delete trip. Please try again.', 'error');
        }
    }
}

// Get trip for editing
$edit_trip = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM trips WHERE trip_id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $user_id]);
    $edit_trip = $stmt->fetch();
    if ($edit_trip) {
        $bike_id = $edit_trip['bike_id'];
    }
}

// Get current bike odometer for new trip
$current_odometer = 0;
if ($bike_id) {
    $stmt = $db->prepare("SELECT current_odometer FROM bikes WHERE bike_id = ? AND user_id = ?");
    $stmt->execute([$bike_id, $user_id]);
    $bike = $stmt->fetch();
    if ($bike) {
        $current_odometer = $bike['current_odometer'];
    }
}

// Get all trips with filters
$where_clause = "WHERE t.user_id = ?";
$params = [$user_id];

if ($bike_id) {
    $where_clause .= " AND t.bike_id = ?";
    $params[] = $bike_id;
}

$stmt = $db->prepare("
    SELECT t.*, b.bike_name, b.registration_number
    FROM trips t
    JOIN bikes b ON t.bike_id = b.bike_id
    $where_clause
    ORDER BY t.trip_date DESC, t.created_at DESC
");
$stmt->execute($params);
$trips = $stmt->fetchAll();

$page_title = 'Trip Logs';
include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-route"></i> Trip Logs</h1>
        <?php if ($bike_id && count($user_bikes) > 0): ?>
            <div class="filter-group">
                <label>Filter by Bike:</label>
                <select onchange="window.location.href='trips.php?bike_id='+this.value">
                    <option value="">All Bikes</option>
                    <?php foreach ($user_bikes as $bike): ?>
                        <option value="<?php echo $bike['bike_id']; ?>" <?php echo $bike['bike_id'] == $bike_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bike['bike_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>
    <a href="?action=add<?php echo $bike_id ? '&bike_id=' . $bike_id : ''; ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Trip
    </a>
</div>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="form-container">
        <h2><i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i> <?php echo $action == 'add' ? 'Add New Trip' : 'Edit Trip'; ?></h2>
        <form method="POST" action="" class="form-horizontal">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="trip_id" value="<?php echo $edit_trip['trip_id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="bike_id">Select Bike *</label>
                    <select id="bike_id" name="bike_id" required onchange="updateOdometer(this.value)">
                        <option value="">Choose a bike</option>
                        <?php foreach ($user_bikes as $bike): ?>
                            <option value="<?php echo $bike['bike_id']; ?>" 
                                    <?php echo ($edit_trip && $bike['bike_id'] == $edit_trip['bike_id']) || $bike['bike_id'] == $bike_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bike['bike_name']); ?> (<?php echo htmlspecialchars($bike['registration_number']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="trip_date">Trip Date *</label>
                    <input type="date" id="trip_date" name="trip_date" required 
                           value="<?php echo $edit_trip ? $edit_trip['trip_date'] : date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_odometer">Start Odometer (km) *</label>
                    <input type="number" id="start_odometer" name="start_odometer" required 
                           value="<?php echo $edit_trip ? $edit_trip['start_odometer'] : $current_odometer; ?>"
                           placeholder="e.g., 5000">
                </div>

                <div class="form-group">
                    <label for="end_odometer">End Odometer (km) *</label>
                    <input type="number" id="end_odometer" name="end_odometer" required 
                           value="<?php echo $edit_trip ? $edit_trip['end_odometer'] : ''; ?>"
                           placeholder="e.g., 5150">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_location">Start Location</label>
                    <input type="text" id="start_location" name="start_location" 
                           value="<?php echo $edit_trip ? htmlspecialchars($edit_trip['start_location']) : ''; ?>"
                           placeholder="e.g., Mumbai">
                </div>

                <div class="form-group">
                    <label for="end_location">End Location</label>
                    <input type="text" id="end_location" name="end_location" 
                           value="<?php echo $edit_trip ? htmlspecialchars($edit_trip['end_location']) : ''; ?>"
                           placeholder="e.g., Pune">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="trip_purpose">Trip Purpose *</label>
                    <select id="trip_purpose" name="trip_purpose" required>
                        <option value="Commute" <?php echo $edit_trip && $edit_trip['trip_purpose'] == 'Commute' ? 'selected' : ''; ?>>Commute</option>
                        <option value="Leisure" <?php echo $edit_trip && $edit_trip['trip_purpose'] == 'Leisure' ? 'selected' : ''; ?>>Leisure</option>
                        <option value="Long Ride" <?php echo $edit_trip && $edit_trip['trip_purpose'] == 'Long Ride' ? 'selected' : ''; ?>>Long Ride</option>
                        <option value="Delivery" <?php echo $edit_trip && $edit_trip['trip_purpose'] == 'Delivery' ? 'selected' : ''; ?>>Delivery</option>
                        <option value="Other" <?php echo $edit_trip && $edit_trip['trip_purpose'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" 
                              placeholder="Any additional notes..."><?php echo $edit_trip ? htmlspecialchars($edit_trip['notes']) : ''; ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="<?php echo $action == 'add' ? 'add_trip' : 'update_trip'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Add Trip' : 'Update Trip'; ?>
                </button>
                <a href="trips.php<?php echo $bike_id ? '?bike_id=' . $bike_id : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <?php if (count($trips) > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bike</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Distance</th>
                        <th>Route</th>
                        <th>Purpose</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?php echo formatDate($trip['trip_date']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($trip['bike_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($trip['registration_number']); ?></small>
                            </td>
                            <td><?php echo number_format($trip['start_odometer']); ?> km</td>
                            <td><?php echo number_format($trip['end_odometer']); ?> km</td>
                            <td><strong><?php echo number_format($trip['distance']); ?> km</strong></td>
                            <td>
                                <?php if ($trip['start_location'] || $trip['end_location']): ?>
                                    <small>
                                        <?php echo htmlspecialchars($trip['start_location'] ?: '-'); ?> 
                                        <i class="fas fa-arrow-right"></i> 
                                        <?php echo htmlspecialchars($trip['end_location'] ?: '-'); ?>
                                    </small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $trip['trip_purpose'])); ?>"><?php echo $trip['trip_purpose']; ?></span></td>
                            <td class="actions">
                                <a href="?action=edit&id=<?php echo $trip['trip_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                                    <input type="hidden" name="trip_id" value="<?php echo $trip['trip_id']; ?>">
                                    <input type="hidden" name="bike_id" value="<?php echo $trip['bike_id']; ?>">
                                    <button type="submit" name="delete_trip" class="btn-icon btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-data-message">
            <i class="fas fa-route fa-3x"></i>
            <h3>No Trips Recorded Yet</h3>
            <p>Start logging your trips to track your riding patterns.</p>
            <a href="?action=add<?php echo $bike_id ? '&bike_id=' . $bike_id : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Your First Trip
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
