-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 27, 2025 at 06:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stockport`
--

-- --------------------------------------------------------

--
-- Table structure for table `billofmaterials`
--

CREATE TABLE `billofmaterials` (
  `BOMID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `MaterialID` int(11) NOT NULL,
  `QuantityRequired` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carriers`
--

CREATE TABLE `carriers` (
  `CarrierID` int(11) NOT NULL,
  `CarrierName` varchar(100) NOT NULL,
  `ContactPerson` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customerorders`
--

CREATE TABLE `customerorders` (
  `CustomerOrderID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `OrderDate` datetime NOT NULL DEFAULT current_timestamp(),
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `Status` enum('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customerorders`
--

INSERT INTO `customerorders` (`CustomerOrderID`, `CustomerID`, `OrderDate`, `TotalAmount`, `Status`) VALUES
(1, 1, '2025-02-27 18:04:59', 80.00, 'Pending'),
(2, 2, '2025-02-27 18:14:00', 400000.00, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `CustomerID` int(11) NOT NULL,
  `CustomerName` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`CustomerID`, `CustomerName`, `Phone`, `Email`, `Address`) VALUES
(1, 'Can Company PH', '09123456789', 'cancompanyph@gmail.com', 'San Pedro City'),
(2, 'Biscuit Company', '09123456789', 'biscuitcompany@gmail.com', 'San Pedro City'),
(3, 'Storage Company', '09123456789', 'storagecompany@gmail.com', 'Block 1 Lot 2 Pretty City');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `EmployeeID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Role` varchar(45) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `employeeEmail` varchar(100) DEFAULT NULL,
  `employeePassword` varchar(255) NOT NULL,
  `HireDate` date NOT NULL,
  `Status` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`EmployeeID`, `FirstName`, `LastName`, `Role`, `Phone`, `employeeEmail`, `employeePassword`, `HireDate`, `Status`) VALUES
(1, 'Kim Jensen', 'Yebes', 'Admin', '09123456789', 'kimjensenyebes@gmail.com', '$2y$10$zgByliOTroett3FUGuvTbeK1mv0ERICQ.67kgsFql.xOXc6af8Cmm', '2025-02-17', 'Active'),
(2, 'Christian Earl', 'Tapit', 'Employee', '09123456789', 'christianearltapit@gmail.com', '$2y$10$lwK9pau8gk4j5L2uOOwpWe3yUM.poSe8KhuTw1My7b5QU0rdgvNyO', '2025-02-25', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `LocationID` int(11) NOT NULL,
  `LocationName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`LocationID`, `LocationName`) VALUES
(1, 'Warehouse A'),
(2, 'Warehouse B'),
(3, 'Storage Room 1'),
(4, 'Distribution Center');

-- --------------------------------------------------------

--
-- Table structure for table `orderdetails`
--

CREATE TABLE `orderdetails` (
  `OrderDetailID` int(11) NOT NULL,
  `CustomerOrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `orderdetails`
--

INSERT INTO `orderdetails` (`OrderDetailID`, `CustomerOrderID`, `ProductID`, `Quantity`, `UnitPrice`, `EmployeeID`) VALUES
(1, 1, 4, 2, 40.00, 1),
(2, 2, 10, 2000, 200.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `productionorders`
--

CREATE TABLE `productionorders` (
  `OrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `EmployeeID` int(11) NOT NULL,
  `StartDate` datetime NOT NULL,
  `EndDate` datetime DEFAULT NULL,
  `Status` enum('Planned','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
  `QuantityOrdered` int(11) NOT NULL,
  `QuantityProduced` int(11) NOT NULL DEFAULT 0,
  `Delivery_Status` tinyint(1) NOT NULL,
  `warehouseID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `productionorders`
--

INSERT INTO `productionorders` (`OrderID`, `ProductID`, `EmployeeID`, `StartDate`, `EndDate`, `Status`, `QuantityOrdered`, `QuantityProduced`, `Delivery_Status`, `warehouseID`) VALUES
(1, 1, 1, '2025-02-27 00:00:00', '2025-03-01 00:00:00', 'In Progress', 97800, 0, 0, 1),
(2, 2, 1, '2025-02-27 00:00:00', '2025-03-01 00:00:00', 'In Progress', 23000, 0, 0, 2),
(3, 2, 1, '2025-02-27 00:00:00', '2025-03-01 00:00:00', 'Completed', 23000, 23000, 0, 4),
(4, 9, 1, '2025-02-27 00:00:00', '2025-03-01 00:00:00', 'Completed', 344960, 344960, 0, 1),
(5, 6, 2, '2025-02-27 00:00:00', '2025-03-01 00:00:00', 'In Progress', 40, 0, 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ProductID` int(11) NOT NULL,
  `ProductName` varchar(100) NOT NULL,
  `Category` varchar(45) DEFAULT NULL,
  `Weight` decimal(10,2) DEFAULT NULL,
  `ProductionCost` decimal(10,2) DEFAULT NULL,
  `SellingPrice` decimal(10,2) DEFAULT NULL,
  `LocationID` int(11) DEFAULT NULL,
  `MaterialID` int(11) DEFAULT NULL,
  `minimum_quantity` int(11) NOT NULL,
  `product_img` varchar(255) DEFAULT NULL,
  `weight_unit` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ProductID`, `ProductName`, `Category`, `Weight`, `ProductionCost`, `SellingPrice`, `LocationID`, `MaterialID`, `minimum_quantity`, `product_img`, `weight_unit`) VALUES
(1, 'Food Can', 'Can', 10.00, 10.00, 15.00, 1, 1, 200, 'century_tuna_can.jpg', 'g'),
(2, 'Biscuit Tin', 'Tin', 10.00, 20.00, 25.00, 1, 1, 115, 'biscuit_tin.jpg', 'g'),
(3, 'Paint can', 'Can', 10.00, 20.00, 25.00, 1, 1, 82, 'paint_can.jpg', 'g'),
(4, 'Baking Mold', 'Mold', 30.00, 35.00, 40.00, 1, 1, 96, 'baking_mold.jpg', 'g'),
(5, 'Oil Drum', 'Drum', 30.00, 2500.00, 3000.00, 2, 2, 3, 'oil_drum.jpg', 'kg'),
(6, 'Fuel Tank', 'Tank', 50.00, 1000.00, 1500.00, 2, 2, 2, 'fuel_tank.jpg', 'kg'),
(7, 'Coin Bank/Safe', 'Safe', 20.00, 700.00, 1000.00, 2, 2, 11, 'coin_bank.jpg', 'kg'),
(8, 'Beverage can', 'Can', 10.00, 50.00, 100.00, 2, 3, 823, 'beverage_can.jpg', 'g'),
(9, 'Food Tray', 'Tray', 50.00, 100.00, 200.00, 2, 3, 640, 'food_tray.jpg', 'g'),
(10, 'Aerosol Can', 'Can', 20.00, 100.00, 200.00, 2, 3, 576, 'aerosol_can.jpg', 'g'),
(11, 'Storage Bin', 'Bin', 200.00, 5000.00, 10000.00, 1, 4, 6, 'storage_bin.jpg', 'kg');

-- --------------------------------------------------------

--
-- Table structure for table `products_warehouse`
--

CREATE TABLE `products_warehouse` (
  `productLocationID` int(11) NOT NULL,
  `productWarehouse` varchar(45) NOT NULL,
  `Section` varchar(45) NOT NULL,
  `Capacity` int(11) NOT NULL,
  `warehouse_weight_unit` varchar(32) NOT NULL,
  `current_usage` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `products_warehouse`
--

INSERT INTO `products_warehouse` (`productLocationID`, `productWarehouse`, `Section`, `Capacity`, `warehouse_weight_unit`, `current_usage`) VALUES
(1, 'San Pedro City', 'Metal Storage', 20000, 'kg', 18226.00),
(2, 'Taguig City', 'Metal Storage', 20000, 'kg', 2230.00),
(3, 'Laguna City', 'Metal Storage', 50000, 'kg', 0.00),
(4, 'Quezon City', 'Metal Storage', 50000, 'kg', 230.00);

-- --------------------------------------------------------

--
-- Table structure for table `rawmaterials`
--

CREATE TABLE `rawmaterials` (
  `MaterialID` int(11) NOT NULL,
  `MaterialName` varchar(100) NOT NULL,
  `SupplierID` int(11) DEFAULT NULL,
  `QuantityInStock` int(11) DEFAULT NULL,
  `UnitCost` decimal(10,2) DEFAULT NULL,
  `LastRestockedDate` date DEFAULT NULL,
  `MinimumStock` int(11) DEFAULT NULL,
  `raw_warehouse` varchar(255) NOT NULL,
  `raw_material_img` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `rawmaterials`
--

INSERT INTO `rawmaterials` (`MaterialID`, `MaterialName`, `SupplierID`, `QuantityInStock`, `UnitCost`, `LastRestockedDate`, `MinimumStock`, `raw_warehouse`, `raw_material_img`) VALUES
(1, 'TinPlate', 1, 10000, 1500.00, '2025-02-28', 5000, 'Paranaque City', 'tinplate.jpg'),
(2, 'Steel', 2, 9980, 1500.00, '2025-02-28', 5000, 'Makati City', 'steel.jpg'),
(3, 'Aluminum', 3, 10000, 1500.00, '2025-02-18', 5000, 'Caloocan City', 'aluminum.jpg'),
(4, 'Stainless Steel', 4, 9769, 1500.00, '2025-02-18', 5000, 'Quezon City', 'stainlesssteel.jpg'),
(5, 'Bronze', 4, 10000, 50.00, '2025-02-27', 5000, 'Quezon City', 'bronze_jpg');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `ShipmentID` int(11) NOT NULL,
  `CustomerOrderID` int(11) NOT NULL,
  `CarrierID` int(11) NOT NULL,
  `ShipmentDate` datetime DEFAULT NULL,
  `TrackingNumber` varchar(100) DEFAULT NULL,
  `Status` enum('Pending','In Transit','Delivered','Failed') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `SupplierID` int(11) NOT NULL,
  `SupplierName` varchar(100) NOT NULL,
  `ContactPerson` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`SupplierID`, `SupplierName`, `ContactPerson`, `Phone`, `Email`, `Address`) VALUES
(1, 'TinPlate Supplier', 'Christian Earl Tapit', '09123456789', 'christianearltapit@gmail.com', 'Block 1 Lot 2 Normal City'),
(2, 'Steel Plate Supplier', 'Kim Jensen Yebes', '09123456789', 'kimjensenyebes@gmail.com', 'Block 1 Lot 2 Normal Street'),
(3, 'Aluminum Supplier', 'Axel Jilian Bumatay', '09123456789', 'axeljilianbumatay@gmail.com', 'Block 1 Lot 2 Normal Steet'),
(4, 'Stainless Steel', 'Aly Sacay', '09123456789', 'alysacay@gmail.com', 'Block 1 Lot 2 Normal Street'),
(5, 'Bronze Supplier', 'Suisei Hoshimachi', '09123456789', 'suiseihoshimachi@gmail.com', 'Block 1 Lot 2 Normal Street');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billofmaterials`
--
ALTER TABLE `billofmaterials`
  ADD PRIMARY KEY (`BOMID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `MaterialID` (`MaterialID`);

--
-- Indexes for table `carriers`
--
ALTER TABLE `carriers`
  ADD PRIMARY KEY (`CarrierID`);

--
-- Indexes for table `customerorders`
--
ALTER TABLE `customerorders`
  ADD PRIMARY KEY (`CustomerOrderID`),
  ADD KEY `CustomerID` (`CustomerID`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`CustomerID`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`EmployeeID`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`LocationID`);

--
-- Indexes for table `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD PRIMARY KEY (`OrderDetailID`),
  ADD KEY `CustomerOrderID` (`CustomerOrderID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `fk_employee` (`EmployeeID`);

--
-- Indexes for table `productionorders`
--
ALTER TABLE `productionorders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `fk_warehouse` (`warehouseID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `fk_product_location` (`LocationID`),
  ADD KEY `fk_product_material` (`MaterialID`);

--
-- Indexes for table `products_warehouse`
--
ALTER TABLE `products_warehouse`
  ADD PRIMARY KEY (`productLocationID`);

--
-- Indexes for table `rawmaterials`
--
ALTER TABLE `rawmaterials`
  ADD PRIMARY KEY (`MaterialID`),
  ADD KEY `idx_supplier` (`SupplierID`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`ShipmentID`),
  ADD KEY `CustomerOrderID` (`CustomerOrderID`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`SupplierID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billofmaterials`
--
ALTER TABLE `billofmaterials`
  MODIFY `BOMID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carriers`
--
ALTER TABLE `carriers`
  MODIFY `CarrierID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customerorders`
--
ALTER TABLE `customerorders`
  MODIFY `CustomerOrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `EmployeeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orderdetails`
--
ALTER TABLE `orderdetails`
  MODIFY `OrderDetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `productionorders`
--
ALTER TABLE `productionorders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products_warehouse`
--
ALTER TABLE `products_warehouse`
  MODIFY `productLocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rawmaterials`
--
ALTER TABLE `rawmaterials`
  MODIFY `MaterialID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `ShipmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `SupplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billofmaterials`
--
ALTER TABLE `billofmaterials`
  ADD CONSTRAINT `billofmaterials_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`),
  ADD CONSTRAINT `billofmaterials_ibfk_2` FOREIGN KEY (`MaterialID`) REFERENCES `rawmaterials` (`MaterialID`);

--
-- Constraints for table `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD CONSTRAINT `fk_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `productionorders`
--
ALTER TABLE `productionorders`
  ADD CONSTRAINT `fk_warehouse` FOREIGN KEY (`warehouseID`) REFERENCES `products_warehouse` (`productLocationID`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_location` FOREIGN KEY (`LocationID`) REFERENCES `products_warehouse` (`productLocationID`),
  ADD CONSTRAINT `fk_product_material` FOREIGN KEY (`MaterialID`) REFERENCES `rawmaterials` (`MaterialID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
