<?php
function renderSidebar($activePage = '') {
?>
    <div class="sidebar">
        <div class="logo">WMS Dashboard</div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="overview.php" class="nav-link <?php echo ($activePage === 'overview') ? 'active' : ''; ?>">
                    Overview
                </a>
            </li>
            <li class="nav-item">
                <a href="inventory.php" class="nav-link <?php echo ($activePage === 'inventory') ? 'active' : ''; ?>">
                    Inventory Management
                </a>
            </li>
            <li class="nav-item">
                <a href="materialOrderAdd.php" class="nav-link <?php echo ($activePage === 'rawMaterialOrder') ? 'active' : ''; ?>">
                    Order Raw Materials
                </a>
            </li>
            <li class="nav-item">
                <a href="materialOrderHistory.php" class="nav-link <?php echo ($activePage === 'materialOrderHistory') ? 'active' : ''; ?>">
                    Material Order History
                </a>
            </li>
            <li class="nav-item">
                <a href="clientOrderAdd.php" class="nav-link <?php echo ($activePage === 'clientOrderAdd') ? 'active' : ''; ?>">
                    Add Client Orders
                </a>
            </li>
            <li class="nav-item">
                <a href="clientOrderTracker.php" class="nav-link <?php echo ($activePage === 'clientOrderTracker') ? 'active' : ''; ?>">
                    Client Order Tracker
                </a>
            </li>
            <li class="nav-item">
                <a href="DeliverProcessedProduct.php" class="nav-link <?php echo ($activePage === 'DeliverProcessedProduct.php') ? 'active' : ''; ?>">
                    Deliver Processed Product
                </a>
            </li>
            <li class="nav-item">
                <a href="warehouse.php" class="nav-link <?php echo ($activePage === 'warehouse') ? 'active' : ''; ?>">
                    Warehouse Operations
                </a>
            </li>
            <li class="nav-item">
                <a href="reports_analytics.php" class="nav-link <?php echo ($activePage === 'reports_analytics') ? 'active' : ''; ?>">
                    Reports & Analytics
                </a>
            </li>
            <li class="nav-item">
                <a href="employee_profile.php" class="nav-link <?php echo ($activePage === 'employee_profile') ? 'active' : ''; ?>">
                    Employee Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="email_support_form.php" class="nav-link <?php echo ($activePage === 'email_support_form') ? 'active' : ''; ?>">
                    Email Support
                </a>
            </li>
        </ul>
    </div>
<?php
}
?>