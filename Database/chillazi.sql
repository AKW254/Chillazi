-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 06, 2025 at 12:53 PM
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
-- Database: `chillazi`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL,
  `conversation_customer_id` int(11) DEFAULT NULL,
  `conversation_role` enum('user','assistant') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `conversation_token` varchar(2000) NOT NULL,
  `conversation_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `conversation_customer_id`, `conversation_role`, `message`, `conversation_token`, `conversation_created_at`) VALUES
(1415, 3, 'user', 'show menu', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:29:54'),
(1416, 3, 'assistant', 'Certainly, here is our menu:\n\n📋 **CHILLAZI FOODMART MENU**\n🍽️ Total Items: 10\n\n🍴 Pizza Margherita - KSH 500\n🍴 Pepperoni Pizza - KSH 650\n🍴 Veggie Burger - KSH 350\n🍴 Chicken Burger - KSH 400\n🍴 French Fries - KSH 200\n🍴 Spaghetti Bolognese - KSH 550\n🍴 Grilled Chicken - KSH 700\n🍴 Fresh Juice - KSH 150\n🍴 Milkshake - KSH 250\n🍴 Coffee - KSH 120', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:29:59'),
(1417, 3, 'user', 'i wish to order 2 Coffee', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:30:31'),
(1418, 3, 'assistant', 'Alright, 2 Coffee. Got it!\n\nYour current order:\n- 2 Coffee', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:30:39'),
(1419, 3, 'user', 'just that', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:30:51'),
(1420, 3, 'assistant', 'Receipt\nItems:\n2 x coffee @ KSH 120 = KSH 240\nSubtotal: KSH 240\nTax: KSH 38.4\nDelivery Fee: KSH 100\nTotal Amount : KSH 378.4\nPAYMENT: Paybill 90800 | Cash/Card on Delivery\n', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:30:51'),
(1421, 3, 'user', 'i wish to order 2 juice', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:32:12'),
(1422, 3, 'assistant', 'Certainly! I\'ve added 2 Fresh Juices to your order.\nYour current order includes:\n- 2 Coffee\n- 4 Fresh Juice', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:32:21'),
(1423, 3, 'user', 'just that', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:33:23'),
(1424, 3, 'assistant', 'Receipt\nItems:\n2 x juice @ KSH 150 = KSH 300\nSubtotal: KSH 300\nTax: KSH 48\nDelivery Fee: KSH 100\nTotal Amount : KSH 448\nPAYMENT: Paybill 90800 | Cash/Card on Delivery\n', 'conv_3_1758531466_557aa9fe', '2025-10-06 07:33:23');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_created_at`) VALUES
(1, 'Antony', 'you@example.com', '+254711223344', '2025-09-04 15:18:43'),
(2, 'Lazaru', 'lazaru@mail.com', '+254765656565', '2025-09-12 12:34:45'),
(3, 'mary', 'mary@mail.com', '+254789898989', '2025-09-12 13:49:26'),
(4, 'anatali', 'anatoli@mail.com', '+254799999999', '2025-09-17 08:05:37'),
(5, 'Amos', 'Amos@mail.com', '+254799157393', '2025-09-23 14:29:47');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_customer_id` int(11) NOT NULL,
  `order_text` text DEFAULT NULL,
  `order_subtotal` decimal(10,2) DEFAULT NULL,
  `order_tax` decimal(10,2) DEFAULT NULL,
  `order_delivery_fee` decimal(10,2) DEFAULT NULL,
  `order_total` decimal(10,2) DEFAULT NULL,
  `order_status` enum('pending_confirmation','confirmed','preparing','ready','delivered','cancelled') DEFAULT NULL,
  `order_currency` varchar(3) DEFAULT 'KES',
  `order_notes` text DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_customer_id`, `order_text`, `order_subtotal`, `order_tax`, `order_delivery_fee`, `order_total`, `order_status`, `order_currency`, `order_notes`, `delivery_address`, `delivery_time`, `created_at`, `updated_at`) VALUES
(47, 3, '{\"items\":[{\"item\":\"milkshakes\",\"quantity\":2,\"price\":0,\"total\":0},{\"item\":\"milkshakes\",\"quantity\":2,\"price\":0,\"total\":0},{\"item\":\"milkshakes\",\"quantity\":2,\"price\":0,\"total\":0}],\"order_subtotal\":0,\"order_tax\":0,\"delivery_fee\":100,\"order_total\":100,\"order_status\":\"pending_confirmation\",\"order_currency\":\"KES\"}', 0.00, 0.00, 100.00, 100.00, 'pending_confirmation', 'KES', NULL, NULL, NULL, '2025-10-06 07:27:32', '2025-10-06 07:27:32'),
(48, 3, '{\"items\":[{\"item\":\"coffee\",\"quantity\":2,\"price\":120,\"total\":240}],\"order_subtotal\":240,\"order_tax\":38.39999999999999857891452847979962825775146484375,\"delivery_fee\":100,\"order_total\":378.3999999999999772626324556767940521240234375,\"order_status\":\"pending_confirmation\",\"order_currency\":\"KES\"}', 240.00, 38.40, 100.00, 378.40, 'pending_confirmation', 'KES', NULL, NULL, NULL, '2025-10-06 07:30:51', '2025-10-06 07:30:51'),
(49, 3, '{\"items\":[{\"item\":\"juice\",\"quantity\":2,\"price\":150,\"total\":300}],\"order_subtotal\":300,\"order_tax\":48,\"delivery_fee\":100,\"order_total\":448,\"order_status\":\"pending_confirmation\",\"order_currency\":\"KES\"}', 300.00, 48.00, 100.00, 448.00, 'pending_confirmation', 'KES', NULL, NULL, NULL, '2025-10-06 07:33:23', '2025-10-06 07:33:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD KEY `conversation_customer_id` (`conversation_customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_email` (`customer_email`),
  ADD UNIQUE KEY `customer_phone` (`customer_phone`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `order_customer_id` (`order_customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1425;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`conversation_customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`order_customer_id`) REFERENCES `customers` (`customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
