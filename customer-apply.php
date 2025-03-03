<?php
include 'server/database.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['CustomerName'];
    $phone = $_POST['Phone'];
    $email = $_POST['Email'];
    $address = $_POST['Address'];
    
    $query = "INSERT INTO customers (CustomerName, Phone, Email, Address, customer_status) 
              VALUES (?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $name, $phone, $email, $address);
    
    if ($stmt->execute()) {
        $success_message = "Registration is on pending status. Please Kindly wait for approval.";
    } else {
        $error_message = "Registration failed";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://fonts.googleapis.com/css?family=Poppins:300,400,500,600' rel='stylesheet'>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Customer Registration</h2>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="CustomerName">Name</label>
                    <input type="text" id="CustomerName" name="CustomerName" required>
                </div>
                
                <div class="form-group">
                    <label for="Phone">Phone</label>
                    <input type="tel" id="Phone" name="Phone" required>
                </div>
                
                <div class="form-group">
                    <label for="Email">Email</label>
                    <input type="email" id="Email" name="Email" required>
                </div>
                
                <div class="form-group">
                    <label for="Address">Address</label>
                    <textarea id="Address" name="Address" required></textarea>
                </div>
                
                <button type="submit">Register</button>
            </form>
        </div>
    </div>
</body>
</html>