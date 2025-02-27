<?php
function renderHeader($pageTitle) {
?>
    <div class="header-area">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="employee-logout.php" class="logout-btn">Logout</a>
    </div>
<?php
}
?>