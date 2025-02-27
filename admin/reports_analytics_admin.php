<?php


// Include database connection
include '../server/database.php';
require_once '../admin/session_check_admin.php';
requireAdminAccess();


// Debugging: Check if sidebar.php and header.php exist

require_once '../layouts/sidebar.php';

require_once '../layouts/header.php';

// Check for database connection errors
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
} 

// Fetch raw materials analytics using prepared statements
$rawMaterialsQuery = $conn->prepare("SELECT 
    MaterialName,
    QuantityInStock,
    MinimumStock,
    UnitCost,
    (QuantityInStock * UnitCost) as TotalValue,
    LastRestockedDate,
    raw_warehouse
    FROM rawmaterials");
if (!$rawMaterialsQuery) {
    die("Error in raw materials query: " . $conn->error);
} 
$rawMaterialsQuery->execute();
$rawMaterialsResult = $rawMaterialsQuery->get_result();

// Fetch production orders analytics using prepared statements
$productionQuery = $conn->prepare("SELECT 
    p.ProductName,
    po.Status,
    COUNT(*) as OrderCount,
    SUM(po.QuantityOrdered) as TotalOrdered,
    SUM(po.QuantityProduced) as TotalProduced
    FROM productionorders po
    JOIN products p ON po.ProductID = p.ProductID
    GROUP BY p.ProductName, po.Status");
if (!$productionQuery) {
    die("Error in production orders query: " . $conn->error);
} 
$productionQuery->execute();
$productionResult = $productionQuery->get_result();

// Fetch customer orders analytics using prepared statements
$orderQuery = $conn->prepare("SELECT 
    Status,
    COUNT(*) as OrderCount,
    SUM(TotalAmount) as TotalRevenue
    FROM customerorders
    GROUP BY Status");
if (!$orderQuery) {
    die("Error in customer orders query: " . $conn->error);
}
$orderQuery->execute();
$orderResult = $orderQuery->get_result();

// Fetch inventory analytics using prepared statements
$inventoryQuery = $conn->prepare("SELECT 
    p.ProductName,
    p.Category,
    i.Quantity,
    pw.productWarehouse,
    pw.Section
    FROM inventory i
    JOIN products p ON i.ProductID = p.ProductID
    JOIN products_warehouse pw ON i.LocationID = pw.productLocationID");
if (!$inventoryQuery) {
    die("Error in inventory query: " . $conn->error);
} 
$inventoryQuery->execute();
$inventoryResult = $inventoryQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Reports and Analytics - Warehouse System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
          .analytics-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .analytics-card:hover {
            transform: translateY(-5px);
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .container{
            padding: 0; 
            max-width: 100%;  
        }
        
    </style>
</head>
<body class = "bg-light">
<div class="dashboard-container">
        
        <div class="main-content">
            
        <h1> Reports and Analytics </h1>
            <div class="container-fluid py-4">
        
                <!-- Quick Stats Row -->
                <div class="row mb-4">
                    <?php
                    // Raw Materials Stats
                    $totalMaterials = $rawMaterialsResult->num_rows;
                    $totalValue = 0;
                    $lowStock = 0;
                    while($row = $rawMaterialsResult->fetch_assoc()) {
                        $totalValue += $row['TotalValue'];
                        if($row['QuantityInStock'] <= $row['MinimumStock']) {
                            $lowStock++;
                        }
                    }
                    ?>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Total Raw Materials</h6>
                                <div class="metric-value"><?php echo htmlspecialchars($totalMaterials); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Total Inventory Value</h6>
                                <div class="metric-value">₱<?php echo htmlspecialchars(number_format($totalValue, 2)); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Low Stock Items</h6>
                                <div class="metric-value"><?php echo htmlspecialchars($lowStock); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h6 class="metric-label">Active Orders</h6>
                                <div class="metric-value" id="activeOrders">Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Raw Materials Chart -->
                    <div class="col-md-6">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h5 class="card-title">Raw Materials Stock Level</h5>
                                <div class="chart-container">
                                    <canvas id="rawMaterialsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Status Chart -->
                    <div class="col-md-6">
                        <div class="card analytics-card">
                            <div class="card-body">
                                <h5 class="card-title">Order Status Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="orderStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Raw Materials Table -->
                <div class="card analytics-card">
                    <div class="card-body">
                        <h5 class="card-title">Raw Materials Inventory</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Warehouse</th>
                                        <th>Quantity</th>
                                        <th>Minimum Stock</th>
                                        <th>Status</th>
                                        <th>Last Restocked</th>
                                        <th>Total Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rawMaterialsResult->data_seek(0);
                                    while($row = $rawMaterialsResult->fetch_assoc()) {
                                        $status = $row['QuantityInStock'] <= $row['MinimumStock'] ? 
                                            '<span class="badge bg-danger">Low Stock</span>' : 
                                            '<span class="badge bg-success">Adequate</span>';
                                        echo "<tr>
                                            <td>" . htmlspecialchars($row['MaterialName']) . "</td>
                                            <td>" . htmlspecialchars($row['raw_warehouse']) . "</td>
                                            <td>" . htmlspecialchars($row['QuantityInStock']) . "</td>
                                            <td>" . htmlspecialchars($row['MinimumStock']) . "</td>
                                            <td>{$status}</td>
                                            <td>" . htmlspecialchars($row['LastRestockedDate']) . "</td>
                                            <td>₱" . htmlspecialchars(number_format($row['TotalValue'], 2)) . "</td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bootstrap JS and dependencies -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            
            <script>
                // Raw Materials Chart
                const rawMaterialsCtx = document.getElementById('rawMaterialsChart').getContext('2d');
                const rawMaterialsData = {
                    labels: <?php 
                        $rawMaterialsResult->data_seek(0);
                        $labels = [];
                        $data = [];
                        $minStock = [];
                        while($row = $rawMaterialsResult->fetch_assoc()) {
                            $labels[] = $row['MaterialName'];
                            $data[] = $row['QuantityInStock'];
                            $minStock[] = $row['MinimumStock'];
                        }
                        echo json_encode($labels);
                    ?>,
                    datasets: [{
                        label: 'Current Stock',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Minimum Stock',
                        data: <?php echo json_encode($minStock); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                };

                new Chart(rawMaterialsCtx, {
                    type: 'bar',
                    data: rawMaterialsData,
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

                // Order Status Chart
                const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
                const orderStatusData = {
                    labels: <?php 
                        $orderResult->data_seek(0);
                        $statusLabels = [];
                        $statusData = [];
                        while($row = $orderResult->fetch_assoc()) {
                            $statusLabels[] = $row['Status'];
                            $statusData[] = $row['OrderCount'];
                        }
                        echo json_encode($statusLabels);
                    ?>,
                    datasets: [{
                        data: <?php echo json_encode($statusData); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                };

                new Chart(orderStatusCtx, {
                    type: 'pie',
                    data: orderStatusData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });

                // Update active orders count
                fetch('server/dashboard.php')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('activeOrders').textContent = data.activeOrders || '0';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('activeOrders').textContent = 'Error';
                    });
            </script>
        </div>
    </div>
</body>
</html>