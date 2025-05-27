<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Statistical Reports';
$page_icon = 'fa-chart-bar';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <!-- Quick Statistics -->
        <div class="row g-4 mb-4">
            <!-- Stores Statistics -->
            <div class="col-md-3">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-store fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title mb-0">Stores</h6>
                            </div>
                        </div>
                        <?php
                        $stats['total_stores'] = $conn->query("SELECT COUNT(*) as count FROM stores")->fetch_assoc()['count'];
                        $stats['active_stores'] = $conn->query("SELECT COUNT(*) as count FROM stores WHERE status = 'active'")->fetch_assoc()['count'];
                        ?>
                        <h3 class="mb-2"><?php echo number_format($stats['total_stores']); ?></h3>
                        <p class="card-text text-success mb-0">
                            <i class="fas fa-check-circle"></i>
                            <?php echo number_format($stats['active_stores']); ?> active stores
                        </p>
                    </div>
                </div>
            </div>
            <!-- Customers Statistics -->
            <div class="col-md-3">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title mb-0">Customers</h6>
                            </div>
                        </div>
                        <?php
                        $stats['total_customers'] = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
                        ?>
                        <h3 class="mb-2"><?php echo number_format($stats['total_customers']); ?></h3>
                    </div>
                </div>
            </div>
            <!-- Products Statistics -->
            <div class="col-md-3">
                <div class="card h-100 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-box fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title mb-0">Products</h6>
                            </div>
                        </div>
                        <?php
                        $stats['total_products'] = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
                        ?>
                        <h3 class="mb-2"><?php echo number_format($stats['total_products']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Search Operations</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Search Term</th>
                                        <th>Number of Searches</th>
                                        <th>Average Results</th>
                                        <th>Last Search</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $search_stats_query = "
                                        SELECT 
                                            search_query,
                                            COUNT(*) as search_count,
                                            AVG(results_count) as avg_results,
                                            MAX(search_date) as last_search
                                        FROM search_logs
                                        GROUP BY search_query
                                        ORDER BY search_count DESC
                                        LIMIT 10";
                                    $search_stats_result = $conn->query($search_stats_query);
                                    $search_stats = $search_stats_result ? $search_stats_result->fetch_all(MYSQLI_ASSOC) : [];
                                    foreach ($search_stats as $search): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($search['search_query']); ?></td>
                                        <td><?php echo $search['search_count']; ?></td>
                                        <td><?php echo $search['avg_results']; ?></td>
                                        <td><?php echo $search['last_search']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Customer Statistics</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Email</th>
                                        <th>Join Date</th>
                                        <th>Orders Count</th>
                                        <th>Searches Count</th>
                                        <th>Last Order</th>
                                        <th>Last Search</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $customer_stats_query = "
                                        SELECT 
                                            c.name,
                                            c.email,
                                            c.created_at as join_date,
                                            COUNT(DISTINCT o.id) as orders_count,
                                            COUNT(DISTINCT sl.id) as searches_count,
                                            MAX(o.created_at) as last_order_date,
                                            MAX(sl.search_date) as last_search_date
                                        FROM customers c
                                        LEFT JOIN orders o ON c.id = o.customer_id
                                        LEFT JOIN search_logs sl ON c.id = sl.user_id
                                        GROUP BY c.id
                                        ORDER BY orders_count DESC, searches_count DESC
                                        LIMIT 10";
                                    $customer_stats_result = $conn->query($customer_stats_query);
                                    $customer_stats = $customer_stats_result ? $customer_stats_result->fetch_all(MYSQLI_ASSOC) : [];
                                    foreach ($customer_stats as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo $customer['join_date']; ?></td>
                                        <td><?php echo $customer['orders_count']; ?></td>
                                        <td><?php echo $customer['searches_count']; ?></td>
                                        <td><?php echo $customer['last_order_date']; ?></td>
                                        <td><?php echo $customer['last_search_date']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Visits Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Store Visits</h5>
                        <div class="chart-container">
                            <canvas id="storeVisitsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Visited Stores -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Most Visited Stores</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Store</th>
                                        <th>Visit Count</th>
                                        <th>Unique Visitors</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $top_stores_query = "
                                        SELECT 
                                            s.name,
                                            COUNT(sv.id) as total_visits,
                                            COUNT(DISTINCT sv.visitor_ip) as unique_visitors
                                        FROM stores s
                                        LEFT JOIN store_visits sv ON s.id = sv.store_id
                                        WHERE sv.visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                        GROUP BY s.id, s.name
                                        ORDER BY total_visits DESC
                                        LIMIT 10";
                                    $top_stores_result = $conn->query($top_stores_query);
                                    $top_stores = $top_stores_result ? $top_stores_result->fetch_all(MYSQLI_ASSOC) : [];
                                    foreach ($top_stores as $store): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($store['name']); ?></td>
                                        <td><?php echo $store['total_visits']; ?></td>
                                        <td><?php echo $store['unique_visitors']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Setup data for store visits chart
        const storeVisitsData = <?php
        $store_visits_query = "
            SELECT 
                DATE(sv.visit_date) as visit_day,
                COUNT(sv.id) as total_visits,
                COUNT(DISTINCT sv.visitor_ip) as unique_visitors
            FROM store_visits sv
            WHERE sv.visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(sv.visit_date)
            ORDER BY visit_day DESC";
        $store_visits_result = $conn->query($store_visits_query);
        $store_visits = $store_visits_result ? $store_visits_result->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode($store_visits); ?>;
        const labels = storeVisitsData.map(item => item.visit_day);
        const visitsData = storeVisitsData.map(item => item.total_visits);
        const uniqueVisitorsData = storeVisitsData.map(item => item.unique_visitors);

        // Create the chart
        new Chart(document.getElementById('storeVisitsChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Store Visits',
                    data: visitsData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'Unique Visitors',
                    data: uniqueVisitorsData,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
