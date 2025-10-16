<?php
require_once 'config/config.php';
requireLogin();

$user_id = getUserId();
$db = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$bike_id = isset($_GET['bike_id']) ? $_GET['bike_id'] : null;

// Get all user bikes
$stmt = $db->prepare("SELECT bike_id, bike_name, registration_number, current_odometer FROM bikes WHERE user_id = ? ORDER BY bike_name");
$stmt->execute([$user_id]);
$user_bikes = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_service'])) {
        $bike_id = $_POST['bike_id'];
        $service_date = $_POST['service_date'];
        $odometer_reading = $_POST['odometer_reading'];
        $service_type = $_POST['service_type'];
        $service_center = sanitizeInput($_POST['service_center']);
        $service_cost = $_POST['service_cost'];
        $parts_replaced = sanitizeInput($_POST['parts_replaced']);
        $next_service_km = $_POST['next_service_km'];
        $next_service_date = $_POST['next_service_date'];
        $description = sanitizeInput($_POST['description']);
        $invoice_number = sanitizeInput($_POST['invoice_number']);

        try {
            $stmt = $db->prepare("INSERT INTO service_records (bike_id, user_id, service_date, odometer_reading, service_type, service_center, service_cost, parts_replaced, next_service_km, next_service_date, description, invoice_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$bike_id, $user_id, $service_date, $odometer_reading, $service_type, $service_center, $service_cost, $parts_replaced, $next_service_km, $next_service_date, $description, $invoice_number]);
            
            // Create reminder for next service if specified
            if (!empty($next_service_date) || !empty($next_service_km)) {
                $reminder_title = "Next Service Due - " . $service_type;
                $reminder_desc = "Service reminder based on last service on " . date('d M Y', strtotime($service_date));
                $stmt = $db->prepare("INSERT INTO reminders (bike_id, user_id, reminder_type, title, description, due_date, due_odometer, priority) VALUES (?, ?, 'Service', ?, ?, ?, ?, 'Medium')");
                $stmt->execute([$bike_id, $user_id, $reminder_title, $reminder_desc, $next_service_date, $next_service_km]);
            }
            
            setFlashMessage('Service record added successfully!', 'success');
            redirect('service.php?bike_id=' . $bike_id);
        } catch (PDOException $e) {
            setFlashMessage('Failed to add service record. Please try again.', 'error');
        }
    } elseif (isset($_POST['update_service'])) {
        $service_id = $_POST['service_id'];
        $bike_id = $_POST['bike_id'];
        $service_date = $_POST['service_date'];
        $odometer_reading = $_POST['odometer_reading'];
        $service_type = $_POST['service_type'];
        $service_center = sanitizeInput($_POST['service_center']);
        $service_cost = $_POST['service_cost'];
        $parts_replaced = sanitizeInput($_POST['parts_replaced']);
        $next_service_km = $_POST['next_service_km'];
        $next_service_date = $_POST['next_service_date'];
        $description = sanitizeInput($_POST['description']);
        $invoice_number = sanitizeInput($_POST['invoice_number']);

        try {
            $stmt = $db->prepare("UPDATE service_records SET bike_id=?, service_date=?, odometer_reading=?, service_type=?, service_center=?, service_cost=?, parts_replaced=?, next_service_km=?, next_service_date=?, description=?, invoice_number=? WHERE service_id=? AND user_id=?");
            $stmt->execute([$bike_id, $service_date, $odometer_reading, $service_type, $service_center, $service_cost, $parts_replaced, $next_service_km, $next_service_date, $description, $invoice_number, $service_id, $user_id]);
            
            setFlashMessage('Service record updated successfully!', 'success');
            redirect('service.php?bike_id=' . $bike_id);
        } catch (PDOException $e) {
            setFlashMessage('Failed to update service record. Please try again.', 'error');
        }
    } elseif (isset($_POST['delete_service'])) {
        $service_id = $_POST['service_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM service_records WHERE service_id=? AND user_id=?");
            $stmt->execute([$service_id, $user_id]);
            
            setFlashMessage('Service record deleted successfully!', 'success');
            redirect('service.php' . ($bike_id ? '?bike_id=' . $bike_id : ''));
        } catch (PDOException $e) {
            setFlashMessage('Failed to delete service record. Please try again.', 'error');
        }
    }
}

// Get service record for editing
$edit_service = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM service_records WHERE service_id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $user_id]);
    $edit_service = $stmt->fetch();
    if ($edit_service) {
        $bike_id = $edit_service['bike_id'];
    }
}

// Get current bike odometer
$current_odometer = 0;
if ($bike_id) {
    $stmt = $db->prepare("SELECT current_odometer FROM bikes WHERE bike_id = ? AND user_id = ?");
    $stmt->execute([$bike_id, $user_id]);
    $bike = $stmt->fetch();
    if ($bike) {
        $current_odometer = $bike['current_odometer'];
    }
}

// Get all service records with filters
$where_clause = "WHERE s.user_id = ?";
$params = [$user_id];

if ($bike_id) {
    $where_clause .= " AND s.bike_id = ?";
    $params[] = $bike_id;
}

$stmt = $db->prepare("
    SELECT s.*, b.bike_name, b.registration_number
    FROM service_records s
    JOIN bikes b ON s.bike_id = b.bike_id
    $where_clause
    ORDER BY s.service_date DESC, s.created_at DESC
");
$stmt->execute($params);
$service_records = $stmt->fetchAll();

// Calculate statistics
$total_cost = 0;
foreach ($service_records as $record) {
    $total_cost += $record['service_cost'];
}

$page_title = 'Service Records';
include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-wrench"></i> Service Records</h1>
        <?php if ($bike_id && count($user_bikes) > 0): ?>
            <div class="filter-group">
                <label>Filter by Bike:</label>
                <select onchange="window.location.href='service.php?bike_id='+this.value">
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
        <i class="fas fa-plus"></i> Add Service Record
    </a>
</div>

<?php if (count($service_records) > 0 && $action == 'list'): ?>
    <div class="stats-mini-grid">
        <div class="stat-mini-card">
            <i class="fas fa-tools"></i>
            <div>
                <h3><?php echo count($service_records); ?></h3>
                <p>Total Services</p>
            </div>
        </div>
        <div class="stat-mini-card">
            <i class="fas fa-rupee-sign"></i>
            <div>
                <h3><?php echo formatCurrency($total_cost); ?></h3>
                <p>Total Cost</p>
            </div>
        </div>
        <div class="stat-mini-card">
            <i class="fas fa-chart-line"></i>
            <div>
                <h3><?php echo formatCurrency($total_cost / count($service_records)); ?></h3>
                <p>Average Cost</p>
            </div>
        </div>
        <div class="stat-mini-card">
            <i class="fas fa-calendar"></i>
            <div>
                <h3><?php echo formatDate($service_records[0]['service_date']); ?></h3>
                <p>Last Service</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="form-container">
        <h2><i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i> <?php echo $action == 'add' ? 'Add Service Record' : 'Edit Service Record'; ?></h2>
        <form method="POST" action="" class="form-horizontal">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="service_id" value="<?php echo $edit_service['service_id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="bike_id">Select Bike *</label>
                    <select id="bike_id" name="bike_id" required>
                        <option value="">Choose a bike</option>
                        <?php foreach ($user_bikes as $bike): ?>
                            <option value="<?php echo $bike['bike_id']; ?>" 
                                    data-odometer="<?php echo $bike['current_odometer']; ?>"
                                    <?php echo ($edit_service && $bike['bike_id'] == $edit_service['bike_id']) || $bike['bike_id'] == $bike_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bike['bike_name']); ?> (<?php echo htmlspecialchars($bike['registration_number']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="service_date">Service Date *</label>
                    <input type="date" id="service_date" name="service_date" required 
                           value="<?php echo $edit_service ? $edit_service['service_date'] : date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="service_type">Service Type *</label>
                    <select id="service_type" name="service_type" required>
                        <option value="Regular Service" <?php echo $edit_service && $edit_service['service_type'] == 'Regular Service' ? 'selected' : ''; ?>>Regular Service</option>
                        <option value="Oil Change" <?php echo $edit_service && $edit_service['service_type'] == 'Oil Change' ? 'selected' : ''; ?>>Oil Change</option>
                        <option value="Tire Replacement" <?php echo $edit_service && $edit_service['service_type'] == 'Tire Replacement' ? 'selected' : ''; ?>>Tire Replacement</option>
                        <option value="Brake Service" <?php echo $edit_service && $edit_service['service_type'] == 'Brake Service' ? 'selected' : ''; ?>>Brake Service</option>
                        <option value="Chain Maintenance" <?php echo $edit_service && $edit_service['service_type'] == 'Chain Maintenance' ? 'selected' : ''; ?>>Chain Maintenance</option>
                        <option value="Battery Replacement" <?php echo $edit_service && $edit_service['service_type'] == 'Battery Replacement' ? 'selected' : ''; ?>>Battery Replacement</option>
                        <option value="General Repair" <?php echo $edit_service && $edit_service['service_type'] == 'General Repair' ? 'selected' : ''; ?>>General Repair</option>
                        <option value="Other" <?php echo $edit_service && $edit_service['service_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="odometer_reading">Odometer Reading (km) *</label>
                    <input type="number" id="odometer_reading" name="odometer_reading" required 
                           value="<?php echo $edit_service ? $edit_service['odometer_reading'] : $current_odometer; ?>"
                           placeholder="e.g., 5000">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="service_cost">Service Cost (₹) *</label>
                    <input type="number" step="0.01" id="service_cost" name="service_cost" required 
                           value="<?php echo $edit_service ? $edit_service['service_cost'] : ''; ?>"
                           placeholder="e.g., 2500">
                </div>

                <div class="form-group">
                    <label for="invoice_number">Invoice Number</label>
                    <input type="text" id="invoice_number" name="invoice_number" 
                           value="<?php echo $edit_service ? htmlspecialchars($edit_service['invoice_number']) : ''; ?>"
                           placeholder="e.g., INV-2023-001">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="service_center">Service Center</label>
                    <input type="text" id="service_center" name="service_center" 
                           value="<?php echo $edit_service ? htmlspecialchars($edit_service['service_center']) : ''; ?>"
                           placeholder="e.g., Authorized Service Center, Main Road">
                </div>

                <div class="form-group">
                    <label for="parts_replaced">Parts Replaced</label>
                    <input type="text" id="parts_replaced" name="parts_replaced" 
                           value="<?php echo $edit_service ? htmlspecialchars($edit_service['parts_replaced']) : ''; ?>"
                           placeholder="e.g., Engine Oil, Oil Filter, Air Filter">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="next_service_date">Next Service Date</label>
                    <input type="date" id="next_service_date" name="next_service_date" 
                           value="<?php echo $edit_service ? $edit_service['next_service_date'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="next_service_km">Next Service at (km)</label>
                    <input type="number" id="next_service_km" name="next_service_km" 
                           value="<?php echo $edit_service ? $edit_service['next_service_km'] : ''; ?>"
                           placeholder="e.g., 8000">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description / Work Done</label>
                <textarea id="description" name="description" rows="4" 
                          placeholder="Describe the service work performed..."><?php echo $edit_service ? htmlspecialchars($edit_service['description']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="<?php echo $action == 'add' ? 'add_service' : 'update_service'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Add Service' : 'Update Service'; ?>
                </button>
                <a href="service.php<?php echo $bike_id ? '?bike_id=' . $bike_id : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <?php if (count($service_records) > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bike</th>
                        <th>Service Type</th>
                        <th>Odometer</th>
                        <th>Cost</th>
                        <th>Service Center</th>
                        <th>Next Service</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($service_records as $record): ?>
                        <tr>
                            <td><?php echo formatDate($record['service_date']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['bike_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($record['registration_number']); ?></small>
                            </td>
                            <td><span class="badge badge-service"><?php echo $record['service_type']; ?></span></td>
                            <td><?php echo number_format($record['odometer_reading']); ?> km</td>
                            <td><strong><?php echo formatCurrency($record['service_cost']); ?></strong></td>
                            <td><?php echo $record['service_center'] ? htmlspecialchars($record['service_center']) : '-'; ?></td>
                            <td>
                                <?php if ($record['next_service_date'] || $record['next_service_km']): ?>
                                    <small>
                                        <?php echo $record['next_service_date'] ? formatDate($record['next_service_date']) : ''; ?>
                                        <?php echo $record['next_service_km'] ? '<br>' . number_format($record['next_service_km']) . ' km' : ''; ?>
                                    </small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="?action=edit&id=<?php echo $record['service_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this service record?');">
                                    <input type="hidden" name="service_id" value="<?php echo $record['service_id']; ?>">
                                    <input type="hidden" name="bike_id" value="<?php echo $record['bike_id']; ?>">
                                    <button type="submit" name="delete_service" class="btn-icon btn-danger" title="Delete">
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
            <i class="fas fa-wrench fa-3x"></i>
            <h3>No Service Records Yet</h3>
            <p>Start tracking your bike's service history for better maintenance.</p>
            <a href="?action=add<?php echo $bike_id ? '&bike_id=' . $bike_id : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Your First Service Record
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
