-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2025 at 01:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecomerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(2, 'collins', '$2y$10$9GQFfQEYiP20WtiuH.3mfudpc14Z2oFRf85NHVak3DA6CDxySvasu'),
(3, 'admin', '$2y$10$4kO5uotfdwx7JY3UFfSyt./EtBTUKcRvAMAGnGq6sV.yxLEBdG6rG');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(2, 'clothes', ''),
(3, 'Electronics', 'electronics');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `username` varchar(18) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Shipped','Delivered') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `username`, `total`, `status`, `created_at`) VALUES
(1, 'ORD202502270001', 'vinny', 102200.00, 'Processing', '2025-02-27 10:27:28'),
(2, 'ORD202502270002', 'vinny', 102200.00, 'Delivered', '2025-02-27 10:29:34'),
(3, 'ORD202502270003', 'vinny', 105200.00, 'Pending', '2025-02-27 10:34:33'),
(4, 'ORD202502270004', 'vinny', 105200.00, 'Pending', '2025-02-27 10:35:45'),
(5, 'ORD202502270005', 'vinny', 105200.00, 'Pending', '2025-02-27 10:35:58'),
(6, 'ORD202502270006', 'vinny', 105200.00, 'Pending', '2025-02-27 10:39:29'),
(7, 'ORD202502270007', 'vinny', 105200.00, 'Pending', '2025-02-27 10:41:07'),
(8, 'ORD202502270008', 'vinny', 105200.00, 'Pending', '2025-02-27 10:45:18'),
(9, 'ORD202502270009', 'vinny', 105200.00, 'Pending', '2025-02-27 11:35:22'),
(10, 'ORD202502270010', 'vinny', 105200.00, 'Pending', '2025-02-27 11:35:42'),
(11, 'ORD202502270011', 'vinny', 105200.00, 'Pending', '2025-02-27 11:37:43'),
(12, 'ORD202502270012', 'vinny', 105200.00, 'Pending', '2025-02-27 11:39:24'),
(13, 'ORD202502270013', 'vinny', 105200.00, 'Pending', '2025-02-27 11:44:51'),
(14, 'ORD202502270014', 'vinny', 3000.00, 'Shipped', '2025-02-27 12:07:41'),
(15, 'ORD202502270015', 'vinny', 3000.00, 'Processing', '2025-02-27 12:08:14'),
(16, 'ORD202502270016', 'Edwin', 1200.00, 'Delivered', '2025-02-27 12:18:56'),
(17, 'ORD202502280001', 'vinny', 1200.00, 'Pending', '2025-02-28 22:53:06'),
(18, 'ORD202503010001', 'vinny', 25000.00, 'Pending', '2025-03-01 05:23:21'),
(19, 'ORD202503010002', 'vinny', 75000.00, 'Pending', '2025-03-01 05:30:08'),
(20, 'ORD202503090001', 'vinny', 3000.00, 'Pending', '2025-03-09 21:08:50'),
(21, 'ORD202503090002', 'vinny', 3000.00, 'Pending', '2025-03-09 21:33:22'),
(22, 'ORD202503090003', 'vinny', 3000.00, 'Pending', '2025-03-09 21:38:53'),
(23, 'ORD202503090004', 'vinny', 95000.00, 'Pending', '2025-03-09 21:39:41'),
(24, 'ORD202503100001', 'vinny', 35000.00, 'Pending', '2025-03-10 15:15:52'),
(25, 'ORD202503100002', 'vinny', 35000.00, 'Pending', '2025-03-10 15:33:31'),
(26, 'ORD202503300001', 'Osumba', 1500.00, 'Shipped', '2025-03-30 18:02:48'),
(27, 'ORD202503300002', 'Osumba', 1500.00, 'Pending', '2025-03-30 18:04:31'),
(28, 'ORD202504040001', 'Osumba', 6000.00, 'Pending', '2025-04-04 10:23:42'),
(29, 'ORD202504040002', 'vinny', 111200.00, 'Pending', '2025-04-04 12:47:54'),
(30, 'ORD202504040003', 'vinny', 111200.00, 'Pending', '2025-04-04 13:01:35'),
(31, 'ORD202504040004', 'vinny', 5400.00, 'Pending', '2025-04-04 13:26:43'),
(32, 'ORD202504040005', 'Edwin', 3000.00, 'Pending', '2025-04-04 13:28:56');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 'ORD202502270002', 11, 1, 1200.00),
(2, 'ORD202502270002', 16, 1, 95000.00),
(3, 'ORD202502270002', 33, 2, 3000.00),
(4, 'ORD202502270003', 11, 1, 1200.00),
(5, 'ORD202502270003', 16, 1, 95000.00),
(6, 'ORD202502270003', 33, 3, 3000.00),
(7, 'ORD202502270004', 11, 1, 1200.00),
(8, 'ORD202502270004', 16, 1, 95000.00),
(9, 'ORD202502270004', 33, 3, 3000.00),
(10, 'ORD202502270005', 11, 1, 1200.00),
(11, 'ORD202502270005', 16, 1, 95000.00),
(12, 'ORD202502270005', 33, 3, 3000.00),
(13, 'ORD202502270006', 11, 1, 1200.00),
(14, 'ORD202502270006', 16, 1, 95000.00),
(15, 'ORD202502270006', 33, 3, 3000.00),
(16, 'ORD202502270007', 11, 1, 1200.00),
(17, 'ORD202502270007', 16, 1, 95000.00),
(18, 'ORD202502270007', 33, 3, 3000.00),
(19, 'ORD202502270008', 11, 1, 1200.00),
(20, 'ORD202502270008', 16, 1, 95000.00),
(21, 'ORD202502270008', 33, 3, 3000.00),
(22, 'ORD202502270009', 11, 1, 1200.00),
(23, 'ORD202502270009', 16, 1, 95000.00),
(24, 'ORD202502270009', 33, 3, 3000.00),
(25, 'ORD202502270010', 11, 1, 1200.00),
(26, 'ORD202502270010', 16, 1, 95000.00),
(27, 'ORD202502270010', 33, 3, 3000.00),
(28, 'ORD202502270011', 11, 1, 1200.00),
(29, 'ORD202502270011', 16, 1, 95000.00),
(30, 'ORD202502270011', 33, 3, 3000.00),
(31, 'ORD202502270012', 11, 1, 1200.00),
(32, 'ORD202502270012', 16, 1, 95000.00),
(33, 'ORD202502270012', 33, 3, 3000.00),
(34, 'ORD202502270013', 11, 1, 1200.00),
(35, 'ORD202502270013', 16, 1, 95000.00),
(36, 'ORD202502270013', 33, 3, 3000.00),
(37, 'ORD202502270014', 33, 1, 3000.00),
(38, 'ORD202502270015', 33, 1, 3000.00),
(39, 'ORD202502270016', 11, 1, 1200.00),
(40, 'ORD202502280001', 11, 1, 1200.00),
(41, 'ORD202503010001', 24, 1, 25000.00),
(42, 'ORD202503010002', 30, 1, 75000.00),
(43, 'ORD202503090001', 33, 1, 3000.00),
(44, 'ORD202503090002', 33, 1, 3000.00),
(45, 'ORD202503090003', 33, 1, 3000.00),
(46, 'ORD202503090004', 16, 1, 95000.00),
(47, 'ORD202503100001', 25, 1, 35000.00),
(48, 'ORD202503100002', 25, 1, 35000.00),
(49, 'ORD202503300001', 12, 1, 1500.00),
(50, 'ORD202503300002', 12, 1, 1500.00),
(51, 'ORD202504040001', 33, 2, 3000.00),
(52, 'ORD202504040002', 11, 1, 1200.00),
(53, 'ORD202504040002', 25, 1, 35000.00),
(54, 'ORD202504040002', 30, 1, 75000.00),
(55, 'ORD202504040003', 11, 1, 1200.00),
(56, 'ORD202504040003', 25, 1, 35000.00),
(57, 'ORD202504040003', 30, 1, 75000.00),
(58, 'ORD202504040004', 11, 2, 1200.00),
(59, 'ORD202504040004', 33, 1, 3000.00),
(60, 'ORD202504040005', 33, 1, 3000.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `quantity`, `image`, `category_id`) VALUES
(8, 'T-shirt', 'natural cotton', 800.00, 21, 'https://th.bing.com/th/id/OIP.DSjZPk9uy01_f2ox4Q5QPgAAAA?rs=1&pid=ImgDetMain', 2),
(9, 'sneakers', 'air foce', 3500.00, 6, 'https://assetscdn1.paytm.com/images/catalog/product/F/FO/FOONIKE-RUNALLDSMAR262972E61C911/0..jpg', NULL),
(11, 'bag', 'bag with 2 extra pockets', 1200.00, 19, 'https://th.bing.com/th/id/R.28e207b477f86e4ddefcd2ad4ed95866?rik=eNKk4VablzELiw&riu=http%3a%2f%2fbensbargains.net%2fthecheckout%2fwp-content%2fuploads%2f2013%2f07%2fbags_herschel.jpg&ehk=hwKRmF%2bs5Fm9StVQ4eoizRB7f1OQJrw2R5XU4aUN7ho%3d&risl=&pid=ImgRaw&r=', NULL),
(12, 'Hoodie', 'Black Color Stylish Hoodie', 1500.00, 97, 'https://media.endclothing.com/media/f_auto,q_auto:eco/prodmedia/media/catalog/product/0/6/06-11-2018_stussy_smoothstockappliquehoody_black_118285-blac_blr_1.jpg', 2),
(14, 'Herschel Little America Backpack', 'Stylish and functional backpack for everyday use.', 12000.00, 10, 'https://th.bing.com/th/id/OIP.V3wL6zU9Z1mKoEwWDwuO1gAAAA?rs=1&pid=ImgDetMain', NULL),
(15, 'Nike Air Max 270', 'Comfortable and stylish sneakers for all occasions.', 15000.00, 20, 'https://th.bing.com/th/id/OIP.MacAvC3oQPDEtiHd5KK5ewAAAA?rs=1&pid=ImgDetMain', NULL),
(16, 'Apple iPhone 13', 'Latest model with advanced features and sleek design.', 95000.00, 0, 'https://th.bing.com/th/id/OIP.jQZLcM5pyOr7Xzw1f8yqjQHaOd?rs=1&pid=ImgDetMain', NULL),
(17, 'Samsung Galaxy S21', 'High-performance smartphone with stunning display.', 85000.00, 15, 'https://th.bing.com/th/id/OIP.3iRYOSf6W5lk-EcweYMnAwAAAA?rs=1&pid=ImgDetMain', NULL),
(18, 'Sony WH-1000XM4', 'Industry-leading noise-canceling headphones.', 30000.00, 8, 'https://www.bhphotovideo.com/images/images2500x2500/sony_wh1000xm4_b_wh_1000xm4_wireless_noise_canceling_over_ear_1582549.jpg', NULL),
(19, 'Dell XPS 13 Laptop', 'Compact laptop with powerful performance.', 120000.00, 12, 'https://microless.com/cdn/products/f026b0f0fb6302d095eda73e25215408-hi.jpg', NULL),
(20, 'Fitbit Charge 5', 'Advanced fitness tracker with health metrics.', 25000.00, 25, 'https://th.bing.com/th/id/OIP.WH8eQ5rL6F7PnxT-NVz0cAHaIw?rs=1&pid=ImgDetMain', NULL),
(21, 'Kindle Paperwhite', 'Lightweight e-reader with adjustable warm light.', 12000.00, 30, 'https://th.bing.com/th/id/OIP.8m_fxJXAsVf_AmOLpcD6zgHaHa?rs=1&pid=ImgDetMain', NULL),
(22, 'JBL Flip 5', 'Portable waterproof Bluetooth speaker.', 10000.00, 50, 'https://www.bhphotovideo.com/images/images1500x1500/jbl_jblflip5grenam_flip_5_waterproof_bluetooth_1482524.jpg', NULL),
(23, 'Logitech MX Master 3', 'Advanced wireless mouse for productivity.', 15000.00, 40, 'https://th.bing.com/th/id/R.9d30793a42f31cc0f20e18085c11d0d2?rik=FZAvBVjX1RwaLQ&riu=http%3a%2f%2fjari.me%2fimages%2f2019%2f10%2f14%2flogitech-mx-master-3.png&ehk=fZP56Tr9NOs%2fM3qfItYkBNZXpMfTvZJ5zkD1E%2fKLE8Y%3d&risl=&pid=ImgRaw&r=0', NULL),
(24, 'Bose QuietComfort Earbuds', 'Noise-canceling true wireless earbuds.', 25000.00, 17, 'https://www.bhphotovideo.com/images/images1500x1500/bose_870730_0010_quietcomfort_earbuds_ii_noise_canceling_1723138.jpg', NULL),
(25, 'Apple Watch Series 7', 'Smartwatch with fitness tracking features.', 35000.00, 15, 'https://th.bing.com/th/id/OIP.Ve4FEnIBJwcF001zyJtbNgAAAA?rs=1&pid=ImgDetMain', NULL),
(26, 'HP Spectre x360', 'Versatile laptop with a stunning design.', 160000.00, 10, 'https://i1.wp.com/witchdoctor.co.nz/wp-content/uploads/2019/08/HP-SPectre-x360-1.jpg?ssl=1', NULL),
(27, 'Oculus Quest 2', 'All-in-one VR headset for gaming.', 45000.00, 15, 'https://www.pcworld.com/wp-content/uploads/2022/12/oculus-quest-7-100857818-orig.jpg?quality=50&strip=all', NULL),
(28, 'GoPro HERO9 Black', 'Versatile action camera for all adventures.', 40000.00, 12, 'https://www.ec-mall.com/wp-content/uploads/2020/09/GoPro-HERO-9-Black-1.jpg', NULL),
(29, 'Nikon D3500', 'Beginner-friendly DSLR camera.', 60000.00, 8, 'https://th.bing.com/th/id/OIP.EVkvDxFauQDs49amjlk86AHaG1?rs=1&pid=ImgDetMain', NULL),
(30, 'Canon EOS M50 Mark II', 'Compact mirrorless camera with great features.', 75000.00, 8, 'https://th.bing.com/th/id/OIP.ZZo4FKO8Bc2K6WKu3wR-4AHaEZ?rs=1&pid=ImgDetMain', NULL),
(31, 'Samsung Galaxy Tab S7', 'High-performance tablet for work and play.', 50000.00, 20, 'https://static.techspot.com/images/products/2020/tablets/org/2020-09-18-product-9.jpg', NULL),
(32, 'Xiaomi Mi Band 6', 'Fitness tracker with AMOLED display.', 4000.00, 50, 'https://www.getic.com/images/catalogue/3281/xiaomiband6_1-6127ed5689fb6-medium.jpg', NULL),
(33, 'Anker PowerCore Portable Charger', 'Fast charging portable power bank.', 3000.00, 22, 'https://th.bing.com/th/id/OIP.Gk-jyIrrlmAEFc0xzwVPsAHaIr?rs=1&pid=ImgDetMain', NULL),
(34, 'Razer BlackWidow Lite Keyboard', 'Mechanical keyboard for gaming and typing.', 12000.00, 15, 'https://cdn.mos.cms.futurecdn.net/3tvqdy7dkDDR3VveuxmUk7-1200-80.jpg', NULL),
(35, 'Logitech G502 HERO Gaming Mouse', 'High-performance gaming mouse with customizable buttons.', 8000.00, 25, 'https://pisces.bbystatic.com/image2/BestBuy_US/images/products/6265/6265133_sd.jpg', NULL),
(37, 'Jacket', 'warm winter wear', 2000.00, 19, NULL, 2),
(38, 'vest', 'cotton', 200.00, 10, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `reset_tokens`
--

CREATE TABLE `reset_tokens` (
  `email` varchar(21) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reset_tokens`
--

INSERT INTO `reset_tokens` (`email`, `token`, `expiry`) VALUES
('vinny@gmail.com', '42785edd3e2ed3621f01826c88a52e33a87f6d4551d4be4529312342cb712ef7', '2025-03-09 23:05:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `name` text NOT NULL,
  `username` varchar(18) NOT NULL,
  `email` varchar(21) NOT NULL,
  `password` text NOT NULL,
  `phone` varchar(15) NOT NULL DEFAULT '',
  `address` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`name`, `username`, `email`, `password`, `phone`, `address`) VALUES
('Collins Otieno', 'admin', 'otienocollins0949@gma', '$2y$10$ypnS75Rw.u7u8Ii8dLvqvOFuIuiHoRjsnQ/fKgqzmDQRnn5IPIxmO', '', ''),
('Edwin', 'Edwin', 'edwinochieng779@gmail', '$2y$10$TrA6ceK0FcWyMdBiW0dK/ujLln4uP/a13zXzIvcamhzlVldIzCAv2', '', ''),
('kevin', 'kevin', 'kevo@gmail.com', '$2y$10$k/xhP1s3MG0TRB.MElwA1ecXsCQJQHEZ0mtNoPxcog8LBMx1vnfKy', '', ''),
('Collins Otieno', 'mastering', 'otienocollins0549@gma', '$2y$10$6JC1nZaV6nJc23Ef1urdou9e4F21pbrw/da7shslHu6MGkAPpeA5i', '', ''),
('Evance Osumba', 'Osumba', 'osumba@gmail.com', '$2y$10$q4KyhSEflPwuKGudBFT3ROVLbyB9W38CR5cVByePsi56ogIDFw2Dy', '', ''),
('samson', 'sam', 'sam@gmail.com', '$2y$10$oL4yXCb7yx7HWPVvY0LPOuGa5Ak6FBdzGZ/2RXDgXRkUopcBMAYIq', '', ''),
('test user', 'user', 'test@gmail.com', '$2y$10$OQe5JPDqmMXPG.Srw5pcTOMfiiDu7lDKNPWCY917d0Ee4q7xjDUEm', '', ''),
('vincent', 'vinny', 'vinny@gmail.com', '$2y$10$GfX8R64qeznntBuw2MZLVeONVqPjemp9HAlXxEx/yCWtnPI76Gxqm', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `reset_tokens`
--
ALTER TABLE `reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
