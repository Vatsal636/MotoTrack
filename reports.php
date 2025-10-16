<?php
require_once 'config/config.php';
requireLogin();

$user_id = getUserId();
$db = getDB();
$bike_id = isset($_GET['bike_id']) ? $_GET['bike_id'] : null;

// Get all user bikes
$stmt = $db->prepare("SELECT bike_id, bike_name, registration_number FROM bikes WHERE user_id = ? ORDER BY bike_name");
$stmt->execute([$user_id]);
$user_bikes = $stmt->fetchAll();

// Build where clause
$where_clause = "WHERE user_id = ?";
$params = [$user_id];

if ($bike_id) {
    $where_clause .= " AND bike_id = ?";
    $params[] = $bike_id;
}

// Get comprehensive statistics
$stats = [];

// Bike stats
if ($bike_id) {
    $stmt = $db->prepare("SELECT * FROM bikes WHERE bike_id = ? AND user_id = ?");
    $stmt->execute([$bike_id, $user_id]);
    $stats['bike'] = $stmt->fetch();
}

// Calculate total distance from odometer (real distance)
if ($bike_id) {
    // Single bike - use its odometer
    $total_distance = ($stats['bike']['current_odometer'] ?? 0) - ($stats['bike']['initial_odometer'] ?? 0);
} else {
    // All bikes - sum up odometer distances
    $stmt = $db->prepare("SELECT SUM(current_odometer - initial_odometer) as total_distance FROM bikes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $total_distance = $result['total_distance'] ?? 0;
}

// Trip stats (for backward compatibility, though trips are removed from UI)
$stmt = $db->prepare("SELECT COUNT(*) as total_trips, SUM(distance) as logged_distance, AVG(distance) as avg_distance FROM trips $where_clause");
$stmt->execute($params);
$stats['trips'] = $stmt->fetch();

// Add real odometer-based distance to trips array for easy access
$stats['trips']['total_distance'] = $total_distance;

// Fuel stats
$stmt = $db->prepare("SELECT SUM(fuel_quantity) as total_fuel, SUM(fuel_cost) as total_fuel_cost, AVG(price_per_liter) as avg_price, AVG(mileage) as avg_mileage, COUNT(*) as total_fills FROM fuel_logs $where_clause");
$stmt->execute($params);
$stats['fuel'] = $stmt->fetch();

// Service stats
$stmt = $db->prepare("SELECT SUM(service_cost) as total_service_cost, COUNT(*) as total_services FROM service_records $where_clause");
$stmt->execute($params);
$stats['service'] = $stmt->fetch();

// Monthly trip data
$stmt = $db->prepare("
    SELECT DATE_FORMAT(trip_date, '%Y-%m') as month, 
           COUNT(*) as trip_count, 
           SUM(distance) as total_distance 
    FROM trips 
    $where_clause
    GROUP BY month 
    ORDER BY month DESC 
    LIMIT 12
");
$stmt->execute($params);
$monthly_trips = $stmt->fetchAll();

// Monthly fuel data
$stmt = $db->prepare("
    SELECT DATE_FORMAT(fill_date, '%Y-%m') as month, 
           SUM(fuel_quantity) as total_fuel,
           SUM(fuel_cost) as total_cost,
           AVG(mileage) as avg_mileage
    FROM fuel_logs 
    $where_clause
    GROUP BY month 
    ORDER BY month DESC 
    LIMIT 12
");
$stmt->execute($params);
$monthly_fuel = $stmt->fetchAll();

// Trip purpose breakdown
$stmt = $db->prepare("
    SELECT trip_purpose, COUNT(*) as count, SUM(distance) as distance 
    FROM trips 
    $where_clause
    GROUP BY trip_purpose
");
$stmt->execute($params);
$trip_purposes = $stmt->fetchAll();

// Service type breakdown
$stmt = $db->prepare("
    SELECT service_type, COUNT(*) as count, SUM(service_cost) as cost 
    FROM service_records 
    $where_clause
    GROUP BY service_type
");
$stmt->execute($params);
$service_types = $stmt->fetchAll();

$page_title = 'Reports & Analytics';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
    <?php if (count($user_bikes) > 0): ?>
        <div class="filter-group">
            <label>Filter by Bike:</label>
            <select onchange="window.location.href='reports.php?bike_id='+this.value">
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

<!-- Overview Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-route"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['trips']['total_distance'] ?? 0); ?> km</h3>
            <p>Total Distance</p>
            <small><?php echo $stats['trips']['total_trips'] ?? 0; ?> trips</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-gas-pump"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['fuel']['avg_mileage'] ?? 0, 2); ?> km/l</h3>
            <p>Average Mileage</p>
            <small><?php echo number_format($stats['fuel']['total_fuel'] ?? 0, 2); ?>L consumed</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-rupee-sign"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($stats['fuel']['total_fuel_cost'] ?? 0); ?></h3>
            <p>Total Fuel Cost</p>
            <small>All time</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-wrench"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo formatCurrency($stats['service']['total_service_cost'] ?? 0); ?></h3>
            <p>Service Cost</p>
            <small><?php echo $stats['service']['total_services'] ?? 0; ?> services</small>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="dashboard-grid">
    <!-- Monthly Distance Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Monthly Distance Traveled</h3>
        </div>
        <div class="card-body">
            <?php if (count($monthly_trips) > 0): ?>
                <canvas id="monthlyDistanceChart" height="250"></canvas>
            <?php else: ?>
                <p class="no-data">No trip data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Monthly Fuel Cost Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-area"></i> Monthly Fuel Cost</h3>
        </div>
        <div class="card-body">
            <?php if (count($monthly_fuel) > 0): ?>
                <canvas id="monthlyFuelChart" height="250"></canvas>
            <?php else: ?>
                <p class="no-data">No fuel data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Trip Purpose Breakdown -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Trip Purpose Breakdown</h3>
        </div>
        <div class="card-body">
            <?php if (count($trip_purposes) > 0): ?>
                <canvas id="tripPurposeChart" height="250"></canvas>
            <?php else: ?>
                <p class="no-data">No trip data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service Cost Breakdown -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Service Cost Breakdown</h3>
        </div>
        <div class="card-body">
            <?php if (count($service_types) > 0): ?>
                <canvas id="serviceTypeChart" height="250"></canvas>
            <?php else: ?>
                <p class="no-data">No service data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detailed Statistics Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h3><i class="fas fa-table"></i> Detailed Statistics</h3>
    </div>
    <div class="card-body">
        <table class="stats-table">
            <tr>
                <th colspan="2">Trip Statistics</th>
            </tr>
            <tr>
                <td>Total Trips</td>
                <td><strong><?php echo $stats['trips']['total_trips'] ?? 0; ?></strong></td>
            </tr>
            <tr>
                <td>Total Distance</td>
                <td><strong><?php echo number_format($stats['trips']['total_distance'] ?? 0); ?> km</strong></td>
            </tr>
            <tr>
                <td>Average Trip Distance</td>
                <td><strong><?php echo number_format($stats['trips']['avg_distance'] ?? 0, 2); ?> km</strong></td>
            </tr>
            <tr>
                <th colspan="2">Fuel Statistics</th>
            </tr>
            <tr>
                <td>Total Fuel Consumed</td>
                <td><strong><?php echo number_format($stats['fuel']['total_fuel'] ?? 0, 2); ?> Liters</strong></td>
            </tr>
            <tr>
                <td>Total Fuel Cost</td>
                <td><strong><?php echo formatCurrency($stats['fuel']['total_fuel_cost'] ?? 0); ?></strong></td>
            </tr>
            <tr>
                <td>Average Fuel Price</td>
                <td><strong><?php echo formatCurrency($stats['fuel']['avg_price'] ?? 0); ?> per liter</strong></td>
            </tr>
            <tr>
                <td>Average Mileage</td>
                <td><strong><?php echo number_format($stats['fuel']['avg_mileage'] ?? 0, 2); ?> km/l</strong></td>
            </tr>
            <tr>
                <th colspan="2">Service Statistics</th>
            </tr>
            <tr>
                <td>Total Services</td>
                <td><strong><?php echo $stats['service']['total_services'] ?? 0; ?></strong></td>
            </tr>
            <tr>
                <td>Total Service Cost</td>
                <td><strong><?php echo formatCurrency($stats['service']['total_service_cost'] ?? 0); ?></strong></td>
            </tr>
            <?php if (($stats['service']['total_services'] ?? 0) > 0): ?>
            <tr>
                <td>Average Service Cost</td>
                <td><strong><?php echo formatCurrency($stats['service']['total_service_cost'] / $stats['service']['total_services']); ?></strong></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="2">Overall Cost</th>
            </tr>
            <tr>
                <td>Total Expenditure (Fuel + Service)</td>
                <td><strong class="highlight"><?php echo formatCurrency(($stats['fuel']['total_fuel_cost'] ?? 0) + ($stats['service']['total_service_cost'] ?? 0)); ?></strong></td>
            </tr>
            <?php if (($stats['trips']['total_distance'] ?? 0) > 0): ?>
            <tr>
                <td>Cost per Kilometer</td>
                <td><strong><?php echo formatCurrency((($stats['fuel']['total_fuel_cost'] ?? 0) + ($stats['service']['total_service_cost'] ?? 0)) / $stats['trips']['total_distance']); ?>/km</strong></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php if (count($monthly_trips) > 0): ?>
    // Monthly Distance Chart
    new Chart(document.getElementById('monthlyDistanceChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_reverse(array_column($monthly_trips, 'month'))); ?>,
            datasets: [{
                label: 'Distance (km)',
                data: <?php echo json_encode(array_reverse(array_column($monthly_trips, 'total_distance'))); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    <?php endif; ?>

    <?php if (count($monthly_fuel) > 0): ?>
    // Monthly Fuel Cost Chart
    new Chart(document.getElementById('monthlyFuelChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_reverse(array_column($monthly_fuel, 'month'))); ?>,
            datasets: [{
                label: 'Fuel Cost (₹)',
                data: <?php echo json_encode(array_reverse(array_column($monthly_fuel, 'total_cost'))); ?>,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    <?php endif; ?>

    <?php if (count($trip_purposes) > 0): ?>
    // Trip Purpose Pie Chart
    new Chart(document.getElementById('tripPurposeChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($trip_purposes, 'trip_purpose')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($trip_purposes, 'count')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    <?php endif; ?>

    <?php if (count($service_types) > 0): ?>
    // Service Type Pie Chart
    new Chart(document.getElementById('serviceTypeChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($service_types, 'service_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($service_types, 'cost')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
