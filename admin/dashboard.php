<?php
include '../server/database.php';
require_once '../admin/session_check_admin.php';
requireAdminAccess();


?>



<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../layouts/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <header>
                <h1>Welcome to the Warehouse Management System</h1>
            </header>
            <div class="content">
                <p>display for data and other info</p>  
            </div>
        </div>
    </div>
</body>
</html>