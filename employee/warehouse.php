<?php
session_start();
require_once 'session_check.php'; // Adjust path as needed
requireActiveLogin(); // This ensures user is logged in AND has Active status
require_once '../layouts/employeeSidebar.php';
require_once '../layouts/employeeHeader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/eminventory.css">
    <title>Warehouse Operations</title>
</head>
<body>
    <div class="container">
        <?php renderSidebar('warehouse'); // Note different active page ?>
        
        <div class="main-content">
            <?php renderHeader('Warehouse Operations'); ?>


            
        </div>
    </div>
</body>
</html>