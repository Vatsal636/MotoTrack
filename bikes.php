<?php
require_once 'config/config.php';
requireLogin();

$user_id = getUserId();
$db = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$bike_id = isset($_GET['bike_id']) ? $_GET['bike_id'] : null;

// Get all user bikes for selection
$stmt = $db->prepare("SELECT bike_id, bike_name, registration_number FROM bikes WHERE user_id = ? ORDER BY bike_name");
$stmt->execute([$user_id]);
$user_bikes = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bike'])) {
        $bike_name = sanitizeInput($_POST['bike_name']);
        $manufacturer = sanitizeInput($_POST['manufacturer']);
        $model = sanitizeInput($_POST['model']);
        $year = sanitizeInput($_POST['year']);
        $registration_number = sanitizeInput($_POST['registration_number']);
        $engine_capacity = sanitizeInput($_POST['engine_capacity']);
        $purchase_date = sanitizeInput($_POST['purchase_date']);
        $purchase_price = sanitizeInput($_POST['purchase_price']);
        $current_odometer = sanitizeInput($_POST['current_odometer']);
        $fuel_tank_capacity = sanitizeInput($_POST['fuel_tank_capacity']);

        try {
            $stmt = $db->prepare("INSERT INTO bikes (user_id, bike_name, manufacturer, model, year, registration_number, engine_capacity, purchase_date, purchase_price, current_odometer, fuel_tank_capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $bike_name, $manufacturer, $model, $year, $registration_number, $engine_capacity, $purchase_date, $purchase_price, $current_odometer, $fuel_tank_capacity]);
            
            setFlashMessage('Bike added successfully!', 'success');
            redirect('bikes.php');
        } catch (PDOException $e) {
            setFlashMessage('Failed to add bike. Please try again.', 'error');
        }
    } elseif (isset($_POST['update_bike'])) {
        $bike_id = $_POST['bike_id'];
        $bike_name = sanitizeInput($_POST['bike_name']);
        $manufacturer = sanitizeInput($_POST['manufacturer']);
        $model = sanitizeInput($_POST['model']);
        $year = sanitizeInput($_POST['year']);
        $registration_number = sanitizeInput($_POST['registration_number']);
        $engine_capacity = sanitizeInput($_POST['engine_capacity']);
        $purchase_date = sanitizeInput($_POST['purchase_date']);
        $purchase_price = sanitizeInput($_POST['purchase_price']);
        $current_odometer = sanitizeInput($_POST['current_odometer']);
        $fuel_tank_capacity = sanitizeInput($_POST['fuel_tank_capacity']);

        try {
            $stmt = $db->prepare("UPDATE bikes SET bike_name=?, manufacturer=?, model=?, year=?, registration_number=?, engine_capacity=?, purchase_date=?, purchase_price=?, current_odometer=?, fuel_tank_capacity=? WHERE bike_id=? AND user_id=?");
            $stmt->execute([$bike_name, $manufacturer, $model, $year, $registration_number, $engine_capacity, $purchase_date, $purchase_price, $current_odometer, $fuel_tank_capacity, $bike_id, $user_id]);
            
            setFlashMessage('Bike updated successfully!', 'success');
            redirect('bikes.php');
        } catch (PDOException $e) {
            setFlashMessage('Failed to update bike. Please try again.', 'error');
        }
    } elseif (isset($_POST['delete_bike'])) {
        $bike_id = $_POST['bike_id'];
        
        try {
            $stmt = $db->prepare("DELETE FROM bikes WHERE bike_id=? AND user_id=?");
            $stmt->execute([$bike_id, $user_id]);
            
            setFlashMessage('Bike deleted successfully!', 'success');
            redirect('bikes.php');
        } catch (PDOException $e) {
            setFlashMessage('Failed to delete bike. Please try again.', 'error');
        }
    }
}

// Get bike for editing
$edit_bike = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM bikes WHERE bike_id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $user_id]);
    $edit_bike = $stmt->fetch();
}

// Get all bikes with stats
$stmt = $db->prepare("
    SELECT b.*, 
           COUNT(DISTINCT t.trip_id) as total_trips,
           b.current_odometer as total_distance,
           COALESCE(SUM(f.fuel_cost), 0) as total_fuel_cost,
           COALESCE(SUM(s.service_cost), 0) as total_service_cost,
           AVG(f.mileage) as avg_mileage
    FROM bikes b
    LEFT JOIN trips t ON b.bike_id = t.bike_id
    LEFT JOIN fuel_logs f ON b.bike_id = f.bike_id
    LEFT JOIN service_records s ON b.bike_id = s.bike_id
    WHERE b.user_id = ?
    GROUP BY b.bike_id
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bikes = $stmt->fetchAll();

$page_title = 'My Bikes';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-motorcycle"></i> My Bikes</h1>
    <a href="?action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Bike
    </a>
</div>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="form-container">
        <h2><i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i> <?php echo $action == 'add' ? 'Add New Bike' : 'Edit Bike'; ?></h2>
        <form method="POST" action="" class="form-horizontal">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="bike_id" value="<?php echo $edit_bike['bike_id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="bike_name">Bike Name *</label>
                    <input type="text" id="bike_name" name="bike_name" required 
                           value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['bike_name']) : ''; ?>"
                           placeholder="e.g., My Royal Enfield">
                </div>

                <div class="form-group">
                    <label for="manufacturer">Manufacturer *</label>
                    <input type="text" id="manufacturer" name="manufacturer" required 
                           value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['manufacturer']) : ''; ?>"
                           placeholder="e.g., Royal Enfield, Honda, Yamaha">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="model">Model *</label>
                    <input type="text" id="model" name="model" required 
                           value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['model']) : ''; ?>"
                           placeholder="e.g., Classic 350">
                </div>

                <div class="form-group">
                    <label for="year">Year *</label>
                    <input type="number" id="year" name="year" required 
                           value="<?php echo $edit_bike ? $edit_bike['year'] : date('Y'); ?>"
                           min="1900" max="<?php echo date('Y') + 1; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="registration_number">Registration Number</label>
                    <input type="text" id="registration_number" name="registration_number" 
                           value="<?php echo $edit_bike ? htmlspecialchars($edit_bike['registration_number']) : ''; ?>"
                           placeholder="e.g., MH 01 AB 1234">
                </div>

                <div class="form-group">
                    <label for="engine_capacity">Engine Capacity (cc)</label>
                    <input type="number" id="engine_capacity" name="engine_capacity" 
                           value="<?php echo $edit_bike ? $edit_bike['engine_capacity'] : ''; ?>"
                           placeholder="e.g., 350">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="purchase_date">Purchase Date</label>
                    <input type="date" id="purchase_date" name="purchase_date" 
                           value="<?php echo $edit_bike ? $edit_bike['purchase_date'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="purchase_price">Purchase Price (₹)</label>
                    <input type="number" step="0.01" id="purchase_price" name="purchase_price" 
                           value="<?php echo $edit_bike ? $edit_bike['purchase_price'] : ''; ?>"
                           placeholder="e.g., 200000">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="current_odometer">Current Odometer (km)</label>
                    <input type="number" id="current_odometer" name="current_odometer" 
                           value="<?php echo $edit_bike ? $edit_bike['current_odometer'] : '0'; ?>"
                           placeholder="e.g., 5000">
                </div>

                <div class="form-group">
                    <label for="fuel_tank_capacity">Fuel Tank Capacity (L)</label>
                    <input type="number" step="0.01" id="fuel_tank_capacity" name="fuel_tank_capacity" 
                           value="<?php echo $edit_bike ? $edit_bike['fuel_tank_capacity'] : ''; ?>"
                           placeholder="e.g., 13.5">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="<?php echo $action == 'add' ? 'add_bike' : 'update_bike'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Add Bike' : 'Update Bike'; ?>
                </button>
                <a href="bikes.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="bikes-grid">
        <?php if (count($bikes) > 0): ?>
            <?php foreach ($bikes as $bike): ?>
                <div class="bike-card">
                    <div class="bike-card-header">
                        <h3><?php echo htmlspecialchars($bike['bike_name']); ?></h3>
                        <div class="bike-actions">
                            <a href="?action=edit&id=<?php echo $bike['bike_id']; ?>" class="btn-icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this bike? All related data will be deleted.');">
                                <input type="hidden" name="bike_id" value="<?php echo $bike['bike_id']; ?>">
                                <button type="submit" name="delete_bike" class="btn-icon btn-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="bike-card-body">
                        <div class="bike-info">
                            <p><strong><?php echo htmlspecialchars($bike['manufacturer']); ?> <?php echo htmlspecialchars($bike['model']); ?></strong></p>
                            <p><i class="fas fa-calendar"></i> Year: <?php echo $bike['year']; ?></p>
                            <?php if ($bike['registration_number']): ?>
                                <p><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($bike['registration_number']); ?></p>
                            <?php endif; ?>
                            <p><i class="fas fa-tachometer-alt"></i> Odometer: <?php echo number_format($bike['current_odometer']); ?> km</p>
                        </div>
                        <div class="bike-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo number_format($bike['total_distance']); ?></span>
                                <span class="stat-label">km</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo formatCurrency($bike['total_fuel_cost']); ?></span>
                                <span class="stat-label">Fuel</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $bike['avg_mileage'] ? number_format($bike['avg_mileage'], 1) : '-'; ?></span>
                                <span class="stat-label">km/l</span>
                            </div>
                        </div>
                        <a href="dashboard.php?bike_id=<?php echo $bike['bike_id']; ?>" class="btn btn-block btn-secondary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data-message">
                <i class="fas fa-motorcycle fa-3x"></i>
                <h3>No Bikes Added Yet</h3>
                <p>Start by adding your first bike to begin tracking.</p>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Bike
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
