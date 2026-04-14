-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 14, 2026 at 03:29 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `food_sales_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int NOT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `nama_cabang`, `alamat`, `created_at`) VALUES
(1, 'Cabang Utama', 'Pusat', '2026-04-09 01:53:01');

-- --------------------------------------------------------

--
-- Table structure for table `operational`
--

CREATE TABLE `operational` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `nama_alat` varchar(150) NOT NULL,
  `harga` decimal(12,0) NOT NULL DEFAULT '0',
  `tempat_beli` varchar(150) DEFAULT NULL,
  `merk` varchar(100) DEFAULT NULL,
  `periode_ganti` int DEFAULT '0' COMMENT 'in months',
  `tanggal_beli` date DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `operational`
--

INSERT INTO `operational` (`id`, `user_id`, `nama_alat`, `harga`, `tempat_beli`, `merk`, `periode_ganti`, `tanggal_beli`, `keterangan`, `created_at`) VALUES
(1, 2, 'wajan', '125000', 'toko yosua ketua pemuda gereja', 'maspion', 12, '2026-04-09', 'yosua  sucipto', '2026-04-09 03:04:05'),
(2, 2, 'Gas', '60', 'Jibran sucipto store', 'Bahlil', 0, '2026-04-09', 'JIBRAN SUCIPTOO', '2026-04-09 03:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `kategori` enum('offline','shopeefood','gofood') NOT NULL,
  `total` decimal(12,0) NOT NULL DEFAULT '0',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `kategori`, `total`, `keterangan`, `created_at`, `branch_id`) VALUES
(1, 1, 'offline', '21000', '', '2026-04-07 02:31:27', NULL),
(2, 1, 'offline', '24000', 'yang pesen yos', '2026-04-09 01:58:39', 1),
(3, 1, 'offline', '20000', 'yosua gila', '2026-04-09 01:59:40', 1),
(4, 2, 'shopeefood', '110000', 'PESANANNYA BINTANG BILLAH SUCIPTO', '2026-04-09 04:35:09', 1),
(5, 2, 'shopeefood', '120000', '', '2026-04-09 04:44:01', 1),
(6, 2, 'gofood', '30000', 'Ditinggal dulu', '2026-04-09 06:52:50', 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `produk` varchar(100) NOT NULL,
  `harga` decimal(12,0) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `produk`, `harga`) VALUES
(1, 1, 'Cimol', '7000'),
(2, 1, 'Kentang', '2000'),
(3, 1, 'Otak-otak', '4000'),
(4, 1, 'Bakso', '8000'),
(5, 2, 'Cimol', '6500'),
(6, 2, 'Otak-otak', '8500'),
(7, 2, 'Sosis', '9000'),
(8, 3, 'Kentang', '2500'),
(9, 3, 'Sosis', '2500'),
(10, 3, 'Tahu', '6500'),
(11, 3, 'Cimol', '8500'),
(12, 4, 'Cimol', '110000'),
(13, 5, 'Bakso', '120000'),
(14, 6, 'Cimol', '5000'),
(15, 6, 'Kentang', '20000'),
(16, 6, 'Sosis', '5000');

-- --------------------------------------------------------

--
-- Table structure for table `production`
--

CREATE TABLE `production` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `nama_item` varchar(150) NOT NULL,
  `harga` decimal(65,0) NOT NULL DEFAULT '0',
  `supplier` varchar(150) DEFAULT NULL,
  `tempat` varchar(150) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_by` int DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `branch_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `production`
--

INSERT INTO `production` (`id`, `user_id`, `nama_item`, `harga`, `supplier`, `tempat`, `tanggal`, `keterangan`, `created_at`, `edited_by`, `edited_at`, `branch_id`) VALUES
(1, 1, 'tapioka', '98', 'murtadho', 'pasar majt', '2026-04-07', 'ophg', '2026-04-07 02:42:53', NULL, NULL, NULL),
(2, 1, 'tapioka', '98777', 'murtadho', 'pasar majt', '2026-04-07', 'ophg', '2026-04-07 08:35:58', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_records`
--

CREATE TABLE `stock_records` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `tanggal` date NOT NULL,
  `tipe` enum('pembukaan','penutupan') NOT NULL,
  `produk` varchar(100) NOT NULL,
  `jumlah` int NOT NULL DEFAULT '0',
  `satuan` varchar(20) DEFAULT 'pcs',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_records`
--

INSERT INTO `stock_records` (`id`, `user_id`, `tanggal`, `tipe`, `produk`, `jumlah`, `satuan`, `created_at`, `branch_id`) VALUES
(1, 1, '2026-04-07', 'pembukaan', 'Cimol', 56, 'pcs', '2026-04-07 02:44:07', NULL),
(2, 1, '2026-04-07', 'pembukaan', 'Kentang', 34, 'pcs', '2026-04-07 02:44:07', NULL),
(3, 1, '2026-04-07', 'pembukaan', 'Otak-otak', 23, 'pcs', '2026-04-07 02:44:07', NULL),
(4, 1, '2026-04-07', 'pembukaan', 'Tahu', 10, 'pcs', '2026-04-07 02:44:07', NULL),
(5, 1, '2026-04-07', 'pembukaan', 'Sosis', 0, 'pcs', '2026-04-07 02:44:07', NULL),
(6, 1, '2026-04-07', 'pembukaan', 'Bakso', 34, 'pcs', '2026-04-07 02:44:07', NULL),
(7, 1, '2026-04-07', 'penutupan', 'Cimol', 10, 'pcs', '2026-04-07 02:44:33', NULL),
(8, 1, '2026-04-07', 'penutupan', 'Kentang', 10, 'pcs', '2026-04-07 02:44:33', NULL),
(9, 1, '2026-04-07', 'penutupan', 'Otak-otak', 0, 'pcs', '2026-04-07 02:44:33', NULL),
(10, 1, '2026-04-07', 'penutupan', 'Tahu', 4, 'pcs', '2026-04-07 02:44:33', NULL),
(11, 1, '2026-04-07', 'penutupan', 'Sosis', 0, 'pcs', '2026-04-07 02:44:33', NULL),
(12, 1, '2026-04-07', 'penutupan', 'Bakso', 0, 'pcs', '2026-04-07 02:44:33', NULL),
(13, 1, '2026-04-09', 'pembukaan', 'Cimol', 50, 'pcs', '2026-04-09 02:01:50', 1),
(14, 1, '2026-04-09', 'pembukaan', 'Kentang', 50, 'pcs', '2026-04-09 02:01:50', 1),
(15, 1, '2026-04-09', 'pembukaan', 'Otak-otak', 40, 'pcs', '2026-04-09 02:01:50', 1),
(16, 1, '2026-04-09', 'pembukaan', 'Tahu', 40, 'pcs', '2026-04-09 02:01:50', 1),
(17, 1, '2026-04-09', 'pembukaan', 'Sosis', 0, 'pcs', '2026-04-09 02:01:50', 1),
(18, 1, '2026-04-09', 'pembukaan', 'Bakso', 0, 'pcs', '2026-04-09 02:01:50', 1),
(19, 1, '2026-04-09', 'penutupan', 'Cimol', 5, 'pcs', '2026-04-09 02:02:30', 1),
(20, 1, '2026-04-09', 'penutupan', 'Kentang', 10, 'pcs', '2026-04-09 02:02:30', 1),
(21, 1, '2026-04-09', 'penutupan', 'Otak-otak', 5, 'pcs', '2026-04-09 02:02:30', 1),
(22, 1, '2026-04-09', 'penutupan', 'Tahu', 5, 'pcs', '2026-04-09 02:02:30', 1),
(23, 1, '2026-04-09', 'penutupan', 'Sosis', 0, 'pcs', '2026-04-09 02:02:30', 1),
(24, 1, '2026-04-09', 'penutupan', 'Bakso', 0, 'pcs', '2026-04-09 02:02:30', 1),
(25, 2, '2026-04-09', 'pembukaan', 'Cimol', 10, 'pcs', '2026-04-09 06:54:42', 1),
(26, 2, '2026-04-09', 'pembukaan', 'Kentang', 50, 'pcs', '2026-04-09 06:54:42', 1),
(27, 2, '2026-04-09', 'pembukaan', 'Otak-otak', 40, 'pcs', '2026-04-09 06:54:42', 1),
(28, 2, '2026-04-09', 'pembukaan', 'Tahu', 40, 'pcs', '2026-04-09 06:54:42', 1),
(29, 2, '2026-04-09', 'pembukaan', 'Sosis', 0, 'pcs', '2026-04-09 06:54:42', 1),
(30, 2, '2026-04-09', 'pembukaan', 'Bakso', 0, 'pcs', '2026-04-09 06:54:42', 1),
(31, 2, '2026-04-09', 'penutupan', 'Cimol', 5, 'pcs', '2026-04-09 06:54:57', 1),
(32, 2, '2026-04-09', 'penutupan', 'Kentang', 10, 'pcs', '2026-04-09 06:54:57', 1),
(33, 2, '2026-04-09', 'penutupan', 'Otak-otak', 5, 'pcs', '2026-04-09 06:54:57', 1),
(34, 2, '2026-04-09', 'penutupan', 'Tahu', 5, 'pcs', '2026-04-09 06:54:57', 1),
(35, 2, '2026-04-09', 'penutupan', 'Sosis', 0, 'pcs', '2026-04-09 06:54:57', 1),
(36, 2, '2026-04-09', 'penutupan', 'Bakso', 0, 'pcs', '2026-04-09 06:54:57', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` enum('owner','admin','admin_cadangan') DEFAULT 'admin_cadangan',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `level`, `created_at`, `branch_id`) VALUES
(1, 'Rifat', 'rifatjibranhudaya@gmail.com', '877-7799-4896', '$2y$10$uN1VZowy.hOGCwrunpNYFeCsh/OhHef8ejmwT2a8tHBqWN7SEmIbK', 'owner', '2026-04-07 02:29:42', NULL),
(2, 'Jibran', 'Jibransixx@gmail.com', '877-7799-4896', '$2y$10$VJxD2FsyI64O63m7T958NubmOnIUtCqi0v2YQsKq1/7s6t9mnq4bm', 'owner', '2026-04-09 01:43:08', NULL),
(3, 'destiar', 'destiar@gmail.com', '87777994896', '$2y$10$X7DuH/JQwVn7BrnJsUDzK.W6IyCIv4r0zi.HI7tNYBg9PERfzAqx.', 'owner', '2026-04-09 06:45:24', 1),
(4, 'yosua', 'yosua@gmail.com', '0852123456789', '$2y$10$ml8xUrQQ.wfRBCJ2irJyh.7CEUbS5mD9wUxB7PqXpFRu0Wi3Cxcz.', 'admin_cadangan', '2026-04-14 03:27:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wisata`
--

CREATE TABLE `wisata` (
  `alat` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `operational`
--
ALTER TABLE `operational`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_br_orders` (`branch_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `production`
--
ALTER TABLE `production`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_prod_editor` (`edited_by`),
  ADD KEY `fk_br_production` (`branch_id`);

--
-- Indexes for table `stock_records`
--
ALTER TABLE `stock_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_br_stock_records` (`branch_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `operational`
--
ALTER TABLE `operational`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `production`
--
ALTER TABLE `production`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_records`
--
ALTER TABLE `stock_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `operational`
--
ALTER TABLE `operational`
  ADD CONSTRAINT `operational_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_br_orders` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production`
--
ALTER TABLE `production`
  ADD CONSTRAINT `fk_br_production` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prod_editor` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_records`
--
ALTER TABLE `stock_records`
  ADD CONSTRAINT `fk_br_stock_records` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
