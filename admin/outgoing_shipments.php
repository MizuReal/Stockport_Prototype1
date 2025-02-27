<?php
$current_page = basename($_SERVER['PHP_SELF']);

include '../server/database.php';
require_once '../admin/session_check_admin.php';
requireAdminAccess();

// Fetch outgoing shipments
$query = "SELECT * FROM shipments ORDER BY ShipmentDate DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Get summary counts
$summary_query = "SELECT 
    COUNT(*) as total_shipments,
    COUNT(CASE WHEN Status = 'Pending' THEN 1 END) as pending_shipments,
    COUNT(CASE WHEN Status = 'In Transit' THEN 1 END) as in_transit
    FROM shipments";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outgoing Shipments - Warehouse System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../layouts/sidebar.php'; ?>

        <div class="main-content">
            <header>
                <h1>Outgoing Shipments</h1>
            </header>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Shipments</h3>
                    <p><?php echo $summary['total_shipments'] ?? 0; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Pending Shipments</h3>
                    <p><?php echo $summary['pending_shipments'] ?? 0; ?></p>
                </div>
                <div class="summary-card">
                    <h3>In Transit</h3>
                    <p><?php echo $summary['in_transit'] ?? 0; ?></p>
                </div>
            </div>

            <div class="content">
                <div class="shipments-table-container">
                    <table class="shipments-table">
                        <thead>
                            <tr>
                                <th>Shipment ID</th>
                                <th>Order ID</th>
                                <th>Carrier ID</th>
                                <th>Shipment Date</th>
                                <th>Tracking Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['ShipmentID']); ?></td>
                                        <td><?php echo htmlspecialchars($row['CustomerOrderID']); ?></td>
                                        <td><?php echo htmlspecialchars($row['CarrierID']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($row['ShipmentDate'])); ?></td>
                                        <td>
                                            <span class="tracking-number">
                                                <?php echo htmlspecialchars($row['TrackingNumber']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($row['Status']); ?>">
                                                <?php echo htmlspecialchars($row['Status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-records">No shipments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>