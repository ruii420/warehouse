-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2025 at 09:14 PM
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
-- Database: `warehouse`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `quantity_change` int(11) DEFAULT NULL,
  `new_quantity` int(11) NOT NULL,
  `action_description` text DEFAULT NULL,
  `is_edit_or_delete` tinyint(1) DEFAULT 0,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `product_id`, `user_id`, `action_type`, `quantity_change`, `new_quantity`, `action_description`, `is_edit_or_delete`, `log_time`) VALUES
(1, 2, 1, 'Order', 65, 164, 'Ordered 65 units', 0, '2025-06-08 15:38:57'),
(2, 4, 1, 'Add', 23, 23, 'Initial stock for new product', 0, '2025-06-08 15:39:48'),
(3, 5, 1, 'Add', 6657, 6657, 'Initial stock for new product', 0, '2025-06-08 15:40:11'),
(4, 3, 2, 'Order', 3, 26, 'Ordered 3 units', 0, '2025-06-08 16:15:52'),
(5, 2, 2, 'Order', 300, 464, 'Ordered 300 units', 0, '2025-06-08 16:18:37'),
(6, 6, 1, 'Add', 4, 4, 'Sākotnējais produkta daudzums', 0, '2025-06-08 17:36:59'),
(7, 2, 2, 'Order', 32, 32, 'Pasūtīti 32 produkta (Papildināts noliktavas krājums)', 0, '2025-06-08 18:46:09'),
(8, 6, 1, 'Novietošana', 4, 0, 'Novietoti 4 3g483g04 plauktā B1', 0, '2025-06-08 19:02:44'),
(9, 2, 1, 'Order', 4554, 4586, 'Pasūtīti 4554 produkta (Papildināts noliktavas krājums)', 0, '2025-06-08 19:03:03'),
(10, 7, 1, 'Pievienošana', 43, 43, 'Sākotnējais preces daudzums', 0, '2025-06-08 19:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_quantity` int(11) NOT NULL,
  `old_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `order_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `product_id`, `user_id`, `order_quantity`, `old_quantity`, `new_quantity`, `order_time`) VALUES
(1, 2, 1, 5, 88, 93, '2025-06-08 15:13:31'),
(2, 2, 1, 6, 93, 99, '2025-06-08 15:31:22'),
(3, 2, 1, 65, 99, 164, '2025-06-08 15:38:57'),
(4, 3, 2, 3, 23, 26, '2025-06-08 16:15:52'),
(5, 2, 2, 300, 164, 464, '2025-06-08 16:18:37'),
(6, 2, 2, 32, 0, 32, '2025-06-08 18:46:09'),
(7, 2, 1, 4554, 32, 4586, '2025-06-08 19:03:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `company_id` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `category`, `company_id`, `quantity`, `price`, `user_id`, `created_at`, `updated_at`) VALUES
(2, 'produkta', 'nm./,v mv ,.', 'produkts', 'prodkti', 4586, 99.00, 1, '2025-06-08 15:13:21', '2025-06-08 19:03:03'),
(3, 'produkts 2', 'testi', 'testi', 'tests', 26, 13.00, 1, '2025-06-08 15:31:57', '2025-06-08 16:15:52'),
(4, 'test3', 'test3', 'test3', 'test3', 23, 76.00, 1, '2025-06-08 15:39:48', '2025-06-08 15:39:48'),
(5, 'deletedekjef', 'jefeihfoue', 'jfnueg', 'jengniue', 6657, 7667.00, 1, '2025-06-08 15:40:11', '2025-06-08 15:40:11'),
(6, '3g483g04', '23,.4 >@M 34', 'rb7y34bt086g', 'ruy4vbrv78w4vrb08y', 0, 44.00, 1, '2025-06-08 17:36:59', '2025-06-08 19:02:44'),
(7, 'testadmin', 'testadmin', 'testadmin', 'testadminid', 43, 999.00, 1, '2025-06-08 19:03:31', '2025-06-08 19:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `product_locations`
--

CREATE TABLE `product_locations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `shelf_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_locations`
--

INSERT INTO `product_locations` (`id`, `product_id`, `shelf_id`, `quantity`, `last_updated`, `updated_by`) VALUES
(1, 5, 2, 0, '2025-06-08 18:45:03', 4),
(2, 5, 2, 0, '2025-06-08 18:45:03', 4),
(3, 6, 3, 0, '2025-06-08 19:02:57', 4),
(5, 5, 4, 2, '2025-06-08 18:45:03', 4),
(6, 6, 3, 2, '2025-06-08 19:02:57', 1),
(7, 6, 3, 2, '2025-06-08 19:02:57', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_log`
--

CREATE TABLE `product_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_log`
--

INSERT INTO `product_log` (`id`, `product_id`, `user_id`, `action_type`, `action_description`, `action_time`) VALUES
(1, 2, 1, 'Edit', 'Updated product details', '2025-06-08 15:39:15'),
(2, 2, 1, 'Edit', 'Updated product details', '2025-06-08 16:21:30'),
(3, 2, 1, 'Edit', 'Updated product details', '2025-06-08 16:21:58'),
(4, 2, 1, 'Edit', 'Updated product details', '2025-06-08 16:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `can_add_product` tinyint(1) DEFAULT 0,
  `can_add_user` tinyint(1) DEFAULT 0,
  `can_manage_users` tinyint(1) DEFAULT 0,
  `can_create_report` tinyint(1) DEFAULT 0,
  `can_make_order` tinyint(1) DEFAULT 0,
  `can_manage_inventory` tinyint(1) DEFAULT 0,
  `can_delete_product` tinyint(1) DEFAULT 0,
  `can_edit_product` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `can_add_product`, `can_add_user`, `can_manage_users`, `can_create_report`, `can_make_order`, `can_manage_inventory`, `can_delete_product`, `can_edit_product`) VALUES
(1, 'Admin', 1, 1, 1, 1, 1, 1, 1, 1),
(2, 'Warehouse Worker', 1, 0, 0, 1, 1, 0, 0, 1),
(3, 'Regular User', 0, 0, 0, 0, 0, 0, 0, 0),
(4, 'Shelf Organizer', 1, 0, 0, 1, 0, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `shelf_activity_log`
--

CREATE TABLE `shelf_activity_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `from_shelf_id` int(11) DEFAULT NULL,
  `to_shelf_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `action_type` enum('place','remove','transfer') NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shelf_activity_log`
--

INSERT INTO `shelf_activity_log` (`id`, `product_id`, `from_shelf_id`, `to_shelf_id`, `quantity`, `action_type`, `user_id`, `action_time`, `notes`) VALUES
(1, 5, NULL, 2, 4, 'place', 4, '2025-06-08 18:15:06', 'Produkts novietots plauktā'),
(2, 5, 2, 2, 2, 'transfer', 4, '2025-06-08 18:15:19', 'Produkts pārvietots starp plauktiem'),
(3, 6, NULL, 3, 2, '', 4, '2025-06-08 18:23:27', 'Produkts novietots plauktā'),
(5, 5, 2, 4, 2, 'transfer', 4, '2025-06-08 18:45:03', 'Produkts pārvietots starp plauktiem'),
(6, 6, NULL, 3, 4, '', 1, '2025-06-08 19:02:44', 'Prece novietota plauktā'),
(7, 6, 3, 3, 2, '', 1, '2025-06-08 19:02:57', 'Prece pārvietota starp plauktiem');

-- --------------------------------------------------------

--
-- Table structure for table `shelves`
--

CREATE TABLE `shelves` (
  `id` int(11) NOT NULL,
  `shelf_code` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shelves`
--

INSERT INTO `shelves` (`id`, `shelf_code`, `section`, `capacity`, `description`, `created_at`) VALUES
(1, 'A1', 'Section A', 100, 'General storage shelf A1', '2025-06-08 18:09:32'),
(2, 'A2', 'Section A', 100, 'General storage shelf A2', '2025-06-08 18:09:32'),
(3, 'B1', 'Section B', 150, 'Heavy duty shelf B1', '2025-06-08 18:09:32'),
(4, 'B2', 'Section B', 150, 'Heavy duty shelf B2', '2025-06-08 18:09:32'),
(5, 'C1', 'Section C', 75, 'Small items shelf C1', '2025-06-08 18:09:32'),
(6, 'C2', 'Section C', 75, 'Small items shelf C2', '2025-06-08 18:09:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$0/OmFAsgExUxMjF.UwQj2uPNUb0bGpZqRtUV7f7LPuZNpQsJfrreS', 1, '2025-06-08 15:11:21'),
(2, 'worker', '$2y$10$Wtsn2iiYnb1bwANiXwHRtOIiUTwfO9s5j4wQHA3VLL///gq5gdAhq', 2, '2025-06-08 15:12:02'),
(3, 'shelf', '$2y$10$DIVhwBcr06Sihvhl3q1BC.kEAFOIECtNfWeb0lS94gAaXUSKY0bd2', 4, '2025-06-08 15:12:16'),
(4, 'realsnabags', '$2y$10$1UCL/mO8liBYmHH55gybVOxzWuiIeQAX9.4PNbv3hFM1vTVO6KZuG', 3, '2025-06-08 15:32:19'),
(5, 'testing', '$2y$10$M7wDFUK9BI.zKE5W179.feHqv2OGKdV3smhNbquiV3mv6/pjgWgWm', 4, '2025-06-08 15:40:31'),
(7, 'testadmin', '$2y$10$LvRK1aljbCBoCT5u60KtFe8BVALwi/gFh7yEl9mkKNIeQrqBWVQfq', 3, '2025-06-08 19:03:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `product_locations`
--
ALTER TABLE `product_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `shelf_id` (`shelf_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `product_log`
--
ALTER TABLE `product_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `shelf_activity_log`
--
ALTER TABLE `shelf_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `from_shelf_id` (`from_shelf_id`),
  ADD KEY `to_shelf_id` (`to_shelf_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shelves`
--
ALTER TABLE `shelves`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shelf_code` (`shelf_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_locations`
--
ALTER TABLE `product_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_log`
--
ALTER TABLE `product_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `shelf_activity_log`
--
ALTER TABLE `shelf_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `shelves`
--
ALTER TABLE `shelves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `inventory_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_locations`
--
ALTER TABLE `product_locations`
  ADD CONSTRAINT `product_locations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_locations_ibfk_2` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`id`),
  ADD CONSTRAINT `product_locations_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_log`
--
ALTER TABLE `product_log`
  ADD CONSTRAINT `product_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `shelf_activity_log`
--
ALTER TABLE `shelf_activity_log`
  ADD CONSTRAINT `shelf_activity_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `shelf_activity_log_ibfk_2` FOREIGN KEY (`from_shelf_id`) REFERENCES `shelves` (`id`),
  ADD CONSTRAINT `shelf_activity_log_ibfk_3` FOREIGN KEY (`to_shelf_id`) REFERENCES `shelves` (`id`),
  ADD CONSTRAINT `shelf_activity_log_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
