<?php
require_once 'config/config.php';
requireLogin();

$user_id = getUserId();
$db = getDB();

// Get user's bikes
$stmt = $db->prepare("SELECT * FROM bikes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$bikes = $stmt->fetchAll();

// Get selected bike (default to first bike or show add bike prompt)
$selected_bike_id = isset($_GET['bike_id']) ? $_GET['bike_id'] : (count($bikes) > 0 ? $bikes[0]['bike_id'] : null);

if ($selected_bike_id) {
    // Get bike details
    $stmt = $db->prepare("SELECT * FROM bikes WHERE bike_id = ? AND user_id = ?");
    $stmt->execute([$selected_bike_id, $user_id]);
    $current_bike = $stmt->fetch();

    if ($current_bike) {
        // Get current odometer
        $current_odometer = $current_bike['current_odometer'];
        
        // Get initial odometer (starting point for tracking)
        // If initial_odometer field exists and is set, use it
        // Otherwise, use the earliest odometer reading from trips/fuel logs
        $initial_odometer = 0;
        
        // Check if initial_odometer column exists
        try {
            $stmt = $db->query("SHOW COLUMNS FROM bikes LIKE 'initial_odometer'");
            $column_exists = $stmt->fetch();
            
            if ($column_exists && isset($current_bike['initial_odometer']) && $current_bike['initial_odometer'] > 0) {
                // Use the stored initial odometer
                $initial_odometer = $current_bike['initial_odometer'];
            } else {
                // Fallback: get the earliest odometer reading from trips or fuel logs
                $stmt = $db->prepare("
                    SELECT MIN(start_odometer) as earliest_odo FROM (
                        SELECT start_odometer FROM trips WHERE bike_id = ?
                        UNION ALL
                        SELECT odometer_reading as start_odometer FROM fuel_logs WHERE bike_id = ?
                    ) as combined
                ");
                $stmt->execute([$selected_bike_id, $selected_bike_id]);
                $earliest = $stmt->fetch();
                $initial_odometer = $earliest['earliest_odo'] ?? $current_odometer;
            }
        } catch (PDOException $e) {
            // Column doesn't exist, use fallback method
            $stmt = $db->prepare("
                SELECT MIN(start_odometer) as earliest_odo FROM (
                    SELECT start_odometer FROM trips WHERE bike_id = ?
                    UNION ALL
                    SELECT odometer_reading as start_odometer FROM fuel_logs WHERE bike_id = ?
                ) as combined
            ");
            $stmt->execute([$selected_bike_id, $selected_bike_id]);
            $earliest = $stmt->fetch();
            $initial_odometer = $earliest['earliest_odo'] ?? $current_odometer;
        }
        
        // Calculate actual distance driven since tracking started
        $total_distance_driven = $current_odometer - $initial_odometer;
        
        // Get statistics
        // Total fuel cost
        $stmt = $db->prepare("SELECT SUM(fuel_cost) as total_fuel_cost, SUM(fuel_quantity) as total_fuel, COUNT(*) as total_fills FROM fuel_logs WHERE bike_id = ?");
        $stmt->execute([$selected_bike_id]);
        $fuel_stats = $stmt->fetch();

        // Average mileage
        $stmt = $db->prepare("SELECT AVG(mileage) as avg_mileage FROM fuel_logs WHERE bike_id = ? AND mileage IS NOT NULL");
        $stmt->execute([$selected_bike_id]);
        $mileage_stats = $stmt->fetch();

        // Total service cost
        $stmt = $db->prepare("SELECT SUM(service_cost) as total_service_cost, COUNT(*) as total_services FROM service_records WHERE bike_id = ?");
        $stmt->execute([$selected_bike_id]);
        $service_stats = $stmt->fetch();

        // Recent fuel logs
        $stmt = $db->prepare("SELECT * FROM fuel_logs WHERE bike_id = ? ORDER BY fill_date DESC LIMIT 5");
        $stmt->execute([$selected_bike_id]);
        $recent_fuel = $stmt->fetchAll();

        // Pending reminders
        $stmt = $db->prepare("SELECT * FROM reminders WHERE bike_id = ? AND is_completed = 0 ORDER BY due_date ASC LIMIT 5");
        $stmt->execute([$selected_bike_id]);
        $pending_reminders = $stmt->fetchAll();

        // Monthly fuel cost (last 6 months)
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(fill_date, '%Y-%m') as month, SUM(fuel_cost) as cost 
            FROM fuel_logs 
            WHERE bike_id = ? AND fill_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month 
            ORDER BY month ASC
        ");
        $stmt->execute([$selected_bike_id]);
        $monthly_fuel = $stmt->fetchAll();
    }
}

include 'includes/header.php';
?>

<div class="dashboard-content">
    <?php if (count($bikes) == 0): ?>
        <div class="no-bikes-message">
            <i class="fas fa-motorcycle fa-4x"></i>
            <h2>No Bikes Added Yet</h2>
            <p>Start by adding your bike to track its performance and maintenance.</p>
            <a href="bikes.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Your First Bike
            </a>
        </div>
    <?php else: ?>
        <!-- Bike Selector -->
        <div class="bike-selector">
            <label>Select Bike:</label>
            <select id="bikeSelect" onchange="window.location.href='dashboard.php?bike_id='+this.value">
                <?php foreach ($bikes as $bike): ?>
                    <option value="<?php echo $bike['bike_id']; ?>" <?php echo $bike['bike_id'] == $selected_bike_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($bike['bike_name']); ?> (<?php echo htmlspecialchars($bike['registration_number']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Bike Overview Card -->
        <div class="bike-overview-card">
            <div class="bike-header">
                <div class="bike-info">
                    <h2><i class="fas fa-motorcycle"></i> <?php echo htmlspecialchars($current_bike['bike_name']); ?></h2>
                    <p><?php echo htmlspecialchars($current_bike['manufacturer']); ?> <?php echo htmlspecialchars($current_bike['model']); ?> (<?php echo $current_bike['year']; ?>)</p>
                    <p><strong>Registration:</strong> <?php echo htmlspecialchars($current_bike['registration_number']); ?></p>
                </div>
                <div class="bike-odometer">
                    <div class="odometer-display">
                        <i class="fas fa-tachometer-alt"></i>
                        <div>
                            <h3><?php echo number_format($current_odometer); ?> km</h3>
                            <p>Current Odometer</p>
                        </div>
                    </div>
                    <div class="distance-info">
                        <small><i class="fas fa-road"></i> <?php echo number_format($total_distance_driven); ?> km driven since tracking started</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-road"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_distance_driven); ?> km</h3>
                    <p>Total Distance Driven</p>
                    <small><i class="fas fa-info-circle"></i> Based on odometer</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-gas-pump"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($mileage_stats['avg_mileage'] ?? 0, 2); ?> km/l</h3>
                    <p>Average Mileage</p>
                    <small><?php echo number_format($fuel_stats['total_fuel'] ?? 0, 2); ?> L consumed</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-fill-drip"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $fuel_stats['total_fills'] ?? 0; ?></h3>
                    <p>Total Fuel Fills</p>
                    <small><?php echo number_format($fuel_stats['total_fuel'] ?? 0, 2); ?> L total</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($fuel_stats['total_fuel_cost'] ?? 0); ?></h3>
                    <p>Total Fuel Cost</p>
                    <small>All time</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($service_stats['total_service_cost'] ?? 0); ?></h3>
                    <p>Service Cost</p>
                    <small><?php echo $service_stats['total_services'] ?? 0; ?> services</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon teal">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-content">
                    <h3><?php 
                        $cost_per_km = $total_distance_driven > 0 ? ($fuel_stats['total_fuel_cost'] + $service_stats['total_service_cost']) / $total_distance_driven : 0;
                        echo formatCurrency($cost_per_km); 
                    ?></h3>
                    <p>Cost per Km</p>
                    <small>Fuel + Service</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="action-buttons">
                <a href="fuel.php?action=add&bike_id=<?php echo $selected_bike_id; ?>" class="action-btn">
                    <i class="fas fa-gas-pump"></i>
                    <span>Log Fuel</span>
                </a>
                <a href="service.php?action=add&bike_id=<?php echo $selected_bike_id; ?>" class="action-btn">
                    <i class="fas fa-tools"></i>
                    <span>Add Service</span>
                </a>
                <a href="reminders.php?action=add&bike_id=<?php echo $selected_bike_id; ?>" class="action-btn">
                    <i class="fas fa-bell"></i>
                    <span>Set Reminder</span>
                </a>
                <a href="reports.php?bike_id=<?php echo $selected_bike_id; ?>" class="action-btn">
                    <i class="fas fa-chart-line"></i>
                    <span>View Reports</span>
                </a>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="dashboard-grid">
            <!-- Recent Fuel Logs -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-gas-pump"></i> Recent Fuel Logs</h3>
                    <a href="fuel.php?bike_id=<?php echo $selected_bike_id; ?>">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_fuel) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Quantity</th>
                                    <th>Cost</th>
                                    <th>Mileage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_fuel as $fuel): ?>
                                    <tr>
                                        <td><?php echo formatDate($fuel['fill_date']); ?></td>
                                        <td><?php echo $fuel['fuel_quantity']; ?>L</td>
                                        <td><?php echo formatCurrency($fuel['fuel_cost']); ?></td>
                                        <td><?php echo $fuel['mileage'] ? $fuel['mileage'] . ' km/l' : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No fuel logs yet. <a href="fuel.php?action=add&bike_id=<?php echo $selected_bike_id; ?>">Add your first fuel log</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Reminders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> Pending Reminders</h3>
                    <a href="reminders.php?bike_id=<?php echo $selected_bike_id; ?>">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($pending_reminders) > 0): ?>
                        <div class="reminders-list">
                            <?php foreach ($pending_reminders as $reminder): ?>
                                <?php
                                $days_left = '';
                                $priority_class = '';
                                if ($reminder['due_date']) {
                                    $days = floor((strtotime($reminder['due_date']) - time()) / 86400);
                                    if ($days < 0) {
                                        $days_left = 'Overdue';
                                        $priority_class = 'overdue';
                                    } elseif ($days == 0) {
                                        $days_left = 'Today';
                                        $priority_class = 'urgent';
                                    } elseif ($days <= REMINDER_WARNING_DAYS) {
                                        $days_left = $days . ' days left';
                                        $priority_class = 'warning';
                                    } else {
                                        $days_left = $days . ' days left';
                                    }
                                }
                                ?>
                                <div class="reminder-item <?php echo $priority_class; ?>">
                                    <div class="reminder-icon">
                                        <i class="fas fa-<?php echo $reminder['reminder_type'] == 'Service' ? 'wrench' : 'bell'; ?>"></i>
                                    </div>
                                    <div class="reminder-content">
                                        <h4><?php echo htmlspecialchars($reminder['title']); ?></h4>
                                        <p><?php echo $reminder['due_date'] ? formatDate($reminder['due_date']) : 'No due date'; ?></p>
                                        <?php if ($days_left): ?>
                                            <span class="reminder-status"><?php echo $days_left; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No pending reminders. <a href="reminders.php?action=add&bike_id=<?php echo $selected_bike_id; ?>">Add a reminder</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fuel Cost Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Monthly Fuel Cost</h3>
                </div>
                <div class="card-body">
                    <?php if (count($monthly_fuel) > 0): ?>
                        <canvas id="fuelCostChart" width="400" height="200"></canvas>
                    <?php else: ?>
                        <p class="no-data">Not enough data to display chart</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($monthly_fuel) && count($monthly_fuel) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('fuelCostChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_fuel, 'month')); ?>,
            datasets: [{
                label: 'Fuel Cost (₹)',
                data: <?php echo json_encode(array_column($monthly_fuel, 'cost')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
