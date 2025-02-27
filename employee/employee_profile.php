<?php
session_start();
include '../server/database.php';
require_once 'session_check.php'; // Adjust path as needed
requireActiveLogin(); // This ensures user is logged in AND has Active status
require_once '../layouts/employeeSidebar.php';
require_once '../layouts/employeeHeader.php';

// Redirect if not logged in
if (!isset($_SESSION['employeeID']) || empty($_SESSION['employeeID'])) {
    header('Location: ../employee-login.php');
    exit();
}

$employeeID = $_SESSION['employeeID'];

// Fetch employee details from the database
try {
    $stmt = $conn->prepare("
        SELECT EmployeeID, FirstName, LastName, Role, Phone, employeeEmail, HireDate, Status
        FROM employees
        WHERE EmployeeID = ?
    ");
    $stmt->bind_param("i", $employeeID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
    } else {
        die("Employee not found.");
    }

    $stmt->close();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/eminventory.css">
    <title>Employee Profile</title>
</head>
<body>
    <div class="container">
        <?php renderSidebar('employee_profile'); // Note different active page ?>
        
        <div class="main-content">
            <?php renderHeader('Employee Profile'); ?>

            <!-- Employee Profile Section -->
            <section class="card">
                <h2 class="card-header">Profile Details</h2>
                <div class="profile-details">
                    <p><strong>First Name:</strong> <?= htmlspecialchars($employee['FirstName']) ?></p>
                    <p><strong>Last Name:</strong> <?= htmlspecialchars($employee['LastName']) ?></p>
                    <p><strong>Role:</strong> <?= htmlspecialchars($employee['Role']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($employee['Phone']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($employee['employeeEmail']) ?></p>
                    <p><strong>Hire Date:</strong> <?= htmlspecialchars($employee['HireDate']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($employee['Status']) ?></p>
                </div>
            </section>
        </div>
    </div>
</body>
</html>