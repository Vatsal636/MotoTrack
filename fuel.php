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
    if (isset($_POST['add_fuel'])) {
        $bike_id = $_POST['bike_id'];
        $fill_date = $_POST['fill_date'];
        $odometer_reading = $_POST['odometer_reading'];
        $fuel_quantity = $_POST['fuel_quantity'];
        $fuel_cost = $_POST['fuel_cost'];
        $price_per_liter = $_POST['price_per_liter'];
        $fuel_type = $_POST['fuel_type'];
        $is_full_tank = isset($_POST['is_full_tank']) ? 1 : 0;
        $fuel_station = sanitizeInput($_POST['fuel_station']);
        $notes = sanitizeInput($_POST['notes']);

        // Calculate mileage (works with partial or full tank)
        $mileage = null;
        
        // Get previous fuel log
        $stmt = $db->prepare("SELECT odometer_reading, fuel_quantity FROM fuel_logs WHERE bike_id = ? AND odometer_reading < ? ORDER BY odometer_reading DESC LIMIT 1");
        $stmt->execute([$bike_id, $odometer_reading]);
        $prev_fuel = $stmt->fetch();
        
        if ($prev_fuel) {
            // Calculate distance traveled since last fuel entry
            $distance = $odometer_reading - $prev_fuel['odometer_reading'];
            
            // Calculate mileage: Distance traveled divided by fuel consumed (from previous fill)
            // This is the industry-standard method used by all fuel tracking apps
            if ($prev_fuel['fuel_quantity'] > 0 && $distance > 0) {
                $mileage = round($distance / $prev_fuel['fuel_quantity'], 2);
            }
        }

        try {
            $stmt = $db->prepare("INSERT INTO fuel_logs (bike_id, user_id, fill_date, odometer_reading, fuel_quantity, fuel_cost, price_per_liter, fuel_type, is_full_tank, fuel_station, mileage, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$bike_id, $user_id, $fill_date, $odometer_reading, $fuel_quantity, $fuel_cost, $price_per_liter, $fuel_type, $is_full_tank, $fuel_station, $mileage, $notes]);
            
            // Update bike odometer
            $stmt = $db->prepare("UPDATE bikes SET current_odometer = ? WHERE bike_id = ? AND user_id = ? AND current_odometer < ?");
            $stmt->execute([$odometer_reading, $bike_id, $user_id, $odometer_reading]);
            
            setFlashMessage('Fuel log added successfully!' . ($mileage ? ' Mileage: ' . $mileage . ' km/l' : ''), 'success');
            redirect('fuel.php?bike_id=' . $bike_id);
        } catch (PDOException $e) {
            setFlashMessage('Failed to add fuel log. Please try again.', 'error');
        }
    } elseif (isset($_POST['update_fuel'])) {
        $fuel_id = $_POST['fuel_id'];
        $bike_id = $_POST['bike_id'];
        $fill_date = $_POST['fill_date'];
        $odometer_reading = $_POST['odometer_reading'];
        $fuel_quantity = $_POST['fuel_quantity'];
        $fuel_cost = $_POST['fuel_cost'];
        $price_per_liter = $_POST['price_per_liter'];
        $fuel_type = $_POST['fuel_type'];
        $is_full_tank = isset($_POST['is_full_tank']) ? 1 : 0;
        $fuel_station = sanitizeInput($_POST['fuel_station']);
        $notes = sanitizeInput($_POST['notes']);

        try {
            $stmt = $db->prepare("UPDATE fuel_logs SET bike_id=?, fill_date=?, odometer_reading=?, fuel_quantity=?, fuel_cost=?, price_per_liter=?, fuel_type=?, is_full_tank=?, fuel_station=?, notes=? WHERE fuel_id=? AND user_id=?");
            $stmt->execute([$bike_id, $fill_date, $odometer_reading, $fuel_quantity, $fuel_cost, $price_per_liter, $fuel_type, $is_full_tank, $fuel_station, $notes, $fuel_id, $user_id]);
            
            setFlashMessage('Fuel log updated successfully!', 'success');
            redirect('fuel.php?bike_id=' . $bike_id);
        } catch (PDOException $e) {
            setFlashMessage('Failed to update fuel log. Please try again.', 'error');
        }
    } elseif (isset($_POST['delete_fuel'])) {
        $fuel_id = $_POST['fuel_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM fuel_logs WHERE fuel_id=? AND user_id=?");
            $stmt->execute([$fuel_id, $user_id]);
            
            setFlashMessage('Fuel log deleted successfully!', 'success');
            redirect('fuel.php' . ($bike_id ? '?bike_id=' . $bike_id : ''));
        } catch (PDOException $e) {
            setFlashMessage('Failed to delete fuel log. Please try again.', 'error');
        }
    }
}

// Get fuel log for editing
$edit_fuel = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM fuel_logs WHERE fuel_id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $user_id]);
    $edit_fuel = $stmt->fetch();
    if ($edit_fuel) {
        $bike_id = $edit_fuel['bike_id'];
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

// Get all fuel logs with filters
$where_clause = "WHERE f.user_id = ?";
$params = [$user_id];

if ($bike_id) {
    $where_clause .= " AND f.bike_id = ?";
    $params[] = $bike_id;
}

$stmt = $db->prepare("
    SELECT f.*, b.bike_name, b.registration_number
    FROM fuel_logs f
    JOIN bikes b ON f.bike_id = b.bike_id
    $where_clause
    ORDER BY f.fill_date DESC, f.created_at DESC
");
$stmt->execute($params);
$fuel_logs = $stmt->fetchAll();

// Calculate statistics
$total_fuel = 0;
$total_cost = 0;
$avg_mileage = 0;
$mileage_count = 0;

foreach ($fuel_logs as $log) {
    $total_fuel += $log['fuel_quantity'];
    $total_cost += $log['fuel_cost'];
    if ($log['mileage']) {
        $avg_mileage += $log['mileage'];
        $mileage_count++;
    }
}

$avg_mileage = $mileage_count > 0 ? $avg_mileage / $mileage_count : 0;

$page_title = 'Fuel Logs';
include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-gas-pump"></i> Fuel Logs</h1>
        <?php if ($bike_id && count($user_bikes) > 0): ?>
            <div class="filter-group">
                <label>Filter by Bike:</label>
                <select onchange="window.location.href='fuel.php?bike_id='+this.value">
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
        <i class="fas fa-plus"></i> Add Fuel Log
    </a>
</div>

<?php if (count($fuel_logs) > 0 && $action == 'list'): ?>
    <div class="stats-mini-grid">
        <div class="stat-mini-card">
            <i class="fas fa-gas-pump"></i>
            <div>
                <h3><?php echo number_format($total_fuel, 2); ?> L</h3>
                <p>Total Fuel</p>
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
                <h3><?php echo number_format($avg_mileage, 2); ?> km/l</h3>
                <p>Average Mileage</p>
            </div>
        </div>
        <div class="stat-mini-card">
            <i class="fas fa-history"></i>
            <div>
                <h3><?php echo count($fuel_logs); ?></h3>
                <p>Total Logs</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="form-container">
        <h2><i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i> <?php echo $action == 'add' ? 'Add Fuel Log' : 'Edit Fuel Log'; ?></h2>
        <form method="POST" action="" class="form-horizontal" id="fuelForm">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="fuel_id" value="<?php echo $edit_fuel['fuel_id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="bike_id">Select Bike *</label>
                    <select id="bike_id" name="bike_id" required>
                        <option value="">Choose a bike</option>
                        <?php foreach ($user_bikes as $bike): ?>
                            <option value="<?php echo $bike['bike_id']; ?>" 
                                    data-odometer="<?php echo $bike['current_odometer']; ?>"
                                    <?php echo ($edit_fuel && $bike['bike_id'] == $edit_fuel['bike_id']) || $bike['bike_id'] == $bike_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bike['bike_name']); ?> (<?php echo htmlspecialchars($bike['registration_number']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fill_date">Fill Date *</label>
                    <input type="date" id="fill_date" name="fill_date" required 
                           value="<?php echo $edit_fuel ? $edit_fuel['fill_date'] : date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="odometer_reading">Odometer Reading (km) *</label>
                    <input type="number" id="odometer_reading" name="odometer_reading" required 
                           value="<?php echo $edit_fuel ? $edit_fuel['odometer_reading'] : $current_odometer; ?>"
                           placeholder="e.g., 5000">
                </div>

                <div class="form-group">
                    <label for="fuel_quantity">Fuel Quantity (Liters) *</label>
                    <input type="number" step="0.01" id="fuel_quantity" name="fuel_quantity" required 
                           value="<?php echo $edit_fuel ? $edit_fuel['fuel_quantity'] : ''; ?>"
                           placeholder="e.g., 10.5" onchange="calculateCost()">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price_per_liter">Price per Liter (₹) *</label>
                    <input type="number" step="0.01" id="price_per_liter" name="price_per_liter" required 
                           value="<?php echo $edit_fuel ? $edit_fuel['price_per_liter'] : ''; ?>"
                           placeholder="e.g., 105.50" onchange="calculateCost()">
                </div>

                <div class="form-group">
                    <label for="fuel_cost">Total Cost (₹) *</label>
                    <input type="number" step="0.01" id="fuel_cost" name="fuel_cost" required 
                           value="<?php echo $edit_fuel ? $edit_fuel['fuel_cost'] : ''; ?>"
                           placeholder="e.g., 1107.75">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fuel_type">Fuel Type *</label>
                    <select id="fuel_type" name="fuel_type" required>
                        <option value="Petrol" <?php echo $edit_fuel && $edit_fuel['fuel_type'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                        <option value="Diesel" <?php echo $edit_fuel && $edit_fuel['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Electric" <?php echo $edit_fuel && $edit_fuel['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fuel_station">Fuel Station</label>
                    <input type="text" id="fuel_station" name="fuel_station" 
                           value="<?php echo $edit_fuel ? htmlspecialchars($edit_fuel['fuel_station']) : ''; ?>"
                           placeholder="e.g., HP Petrol Pump, Station Road">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_full_tank" value="1" 
                               <?php echo $edit_fuel && $edit_fuel['is_full_tank'] ? 'checked' : ''; ?>>
                        Full Tank (optional - for your reference only)
                    </label>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" 
                    placeholder="Any additional notes..."><?php echo $edit_fuel ? htmlspecialchars($edit_fuel['notes']) : ''; ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="<?php echo $action == 'add' ? 'add_fuel' : 'update_fuel'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Add Fuel Log' : 'Update Fuel Log'; ?>
                </button>
                <a href="fuel.php<?php echo $bike_id ? '?bike_id=' . $bike_id : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        function calculateCost() {
            const quantity = parseFloat(document.getElementById('fuel_quantity').value) || 0;
            const price = parseFloat(document.getElementById('price_per_liter').value) || 0;
            const cost = (quantity * price).toFixed(2);
            document.getElementById('fuel_cost').value = cost;
        }
    </script>
<?php else: ?>
    <?php if (count($fuel_logs) > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bike</th>
                        <th>Odometer</th>
                        <th>Quantity</th>
                        <th>Price/L</th>
                        <th>Total Cost</th>
                        <th>Mileage</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fuel_logs as $log): ?>
                        <tr>
                            <td data-label="Date"><?php echo formatDate($log['fill_date']); ?></td>
                            <td data-label="Bike">
                                <strong><?php echo htmlspecialchars($log['bike_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($log['registration_number']); ?></small>
                            </td>
                            <td data-label="Odometer"><?php echo number_format($log['odometer_reading']); ?> km</td>
                            <td data-label="Quantity"><?php echo number_format($log['fuel_quantity'], 2); ?> L</td>
                            <td data-label="Price/L"><?php echo formatCurrency($log['price_per_liter']); ?></td>
                            <td data-label="Total Cost"><strong><?php echo formatCurrency($log['fuel_cost']); ?></strong></td>
                            <td data-label="Mileage">
                                <?php if ($log['mileage']): ?>
                                    <span class="badge badge-success"><?php echo number_format($log['mileage'], 2); ?> km/l</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Type"><?php echo $log['fuel_type']; ?></td>
                            <td class="actions">
                                <a href="?action=edit&id=<?php echo $log['fuel_id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this fuel log?');">
                                    <input type="hidden" name="fuel_id" value="<?php echo $log['fuel_id']; ?>">
                                    <input type="hidden" name="bike_id" value="<?php echo $log['bike_id']; ?>">
                                    <button type="submit" name="delete_fuel" class="btn-icon btn-danger" title="Delete">
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
            <i class="fas fa-gas-pump fa-3x"></i>
            <h3>No Fuel Logs Yet</h3>
            <p>Start logging your fuel fills to track mileage and expenses.</p>
            <a href="?action=add<?php echo $bike_id ? '&bike_id=' . $bike_id : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Your First Fuel Log
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
