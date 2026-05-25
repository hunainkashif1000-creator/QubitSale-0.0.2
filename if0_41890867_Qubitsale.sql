-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql112.infinityfree.com
-- Generation Time: May 25, 2026 at 07:51 AM
-- Server version: 11.4.11-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41890867_Qubitsale`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`key`, `value`) VALUES
('accent2_color', '#06b6d4'),
('accent_color', '#14b8a6'),
('app_name', 'Sam\'s Creations'),
('app_tagline', 'POS'),
('bg2_color', '#0f2626'),
('bg_color', '#0a1a1a'),
('border_radius', '20'),
('danger_color', '#ef4444'),
('favicon_file', 'favicon_1778082787.png'),
('font_family', 'Poppins'),
('logo_emoji', '🏪'),
('logo_file', 'logo_1778082326.png'),
('logo_size', '62'),
('logo_type', 'image'),
('sidebar_width', '230');

-- --------------------------------------------------------

--
-- Table structure for table `branch_sales`
--

CREATE TABLE `branch_sales` (
  `id` int(11) NOT NULL,
  `branch_name` varchar(100) DEFAULT 'Main Branch',
  `customer_name` varchar(100) DEFAULT 'Walk-in',
  `customer_phone` varchar(20) DEFAULT '-',
  `products` text DEFAULT NULL,
  `total` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `cashier_name` varchar(100) DEFAULT '',
  `sale_time` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `barcode` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock`, `barcode`, `image`) VALUES
(1, 'Handle', '100.00', 50, '6a11afe6dbd07', NULL),
(2, 'acrylic-basket-l', '100.00', 50, '6a11afe6dbe81', NULL),
(3, 'acrylic-cosmetic-organizer', '500.00', 60, '6a11afe6dbf62', NULL),
(4, 'acrylic-fnc-holder', '100.00', 50, '6a11afe6dc025', NULL),
(5, 'acrylic-glass-pack', '100.00', 50, '6a11afe6dc0f3', NULL),
(6, 'acrylic-organiser-with-lid', '100.00', 50, '6a11afe6dc1b3', NULL),
(7, 'acrylic-organizer', '100.00', 50, '6a11afe6dc271', NULL),
(8, 'acrylic-plates-set', '100.00', 50, '6a11afe6dc331', NULL),
(9, 'acrylic-rack-holder', '100.00', 50, '6a11afe6dc3eb', NULL),
(10, 'acrylic-rectangle-rack', '100.00', 50, '6a11afe6dc497', NULL),
(11, 'acrylic-triangle-rack', '100.00', 50, '6a11afe6dc544', NULL),
(12, 'aluminium-chana-big', '100.00', 50, '6a11afe6dc60a', NULL),
(13, 'aluminium-chana-small', '100.00', 50, '6a11afe6dc6bf', NULL),
(14, 'aluminium-peeler', '100.00', 50, '6a11afe6dc770', NULL),
(15, 'anti-slip-matt', '100.00', 50, '6a11afe6dc824', NULL),
(16, 'anti-slip-matt-printed', '100.00', 50, '6a11afe6dc8d7', NULL),
(17, 'arc-lighter', '100.00', 50, '6a11afe6dc99a', NULL),
(18, 'bath-belt', '100.00', 50, '6a11afe6dca4f', NULL),
(19, 'black-brush-pair', '100.00', 50, '6a11afe6dcb0a', NULL),
(20, 'black-stainsteel-dust-bin', '100.00', 50, '6a11afe6dcbce', NULL),
(21, 'bottle-brush', '100.00', 50, '6a11afe6dcc7d', NULL),
(22, 'bruno-slicer-1', '100.00', 50, '6a11afe6dcd2a', NULL),
(23, 'brush-spetula', '100.00', 50, '6a11afe6dcde5', NULL),
(24, 'brush-wiper', '100.00', 50, '6a11afe6dceb1', NULL),
(25, 'cake-cutter', '100.00', 50, '6a11afe6dcfe5', NULL),
(26, 'camod-brush', '100.00', 50, '6a11afe6dd09f', NULL),
(27, 'carrot-sealer', '100.00', 50, '6a11afe6dd168', NULL),
(28, 'ceramic-salt-n-pepper', '100.00', 50, '6a11afe6dd21d', NULL),
(29, 'chani-big', '100.00', 50, '6a11afe6dd2d6', NULL),
(30, 'chai-chani', '100.00', 50, '6a11afe6dd386', NULL),
(31, 'charities-chopper', '100.00', 50, '6a11afe6dd435', NULL),
(32, 'chimta', '100.00', 50, '6a11afe6dd4e0', NULL),
(33, 'chocolate-mould', '100.00', 50, '6a11afe6dd594', NULL),
(34, 'chota-acrylic-basket', '100.00', 50, '6a11afe6dd646', NULL),
(35, 'chutki-pack-12-pca', '100.00', 50, '6a11afe6dd729', NULL),
(36, 'chutki-pouch', '100.00', 50, '6a11afe6dd7da', NULL),
(37, 'cloth-brush', '100.00', 50, '6a11afe6dd89b', NULL),
(38, 'cloth-brush(-china)', '100.00', 50, '6a11afe6dd955', NULL),
(39, 'cofee-beater', '100.00', 50, '6a11afe6dda09', NULL),
(40, 'coffe-mug', '100.00', 50, '6a11afe6ddac2', NULL),
(41, 'coffe-mug-small', '100.00', 50, '6a11afe6ddb71', NULL),
(42, 'coffee-mug', '100.00', 50, '6a11afe6ddc20', NULL),
(43, 'dastarkhan', '100.00', 50, '6a11afe6ddccf', NULL),
(44, 'dino-soap-dish', '100.00', 50, '6a11afe6ddd83', NULL),
(45, 'dori-chopper-big', '100.00', 50, '6a11afe6dde35', NULL),
(46, 'dough-maker', '100.00', 50, '6a11afe6ddee5', NULL),
(47, 'dust-pan-set', '100.00', 50, '6a11afe6ddf90', NULL),
(48, 'ear-wax-kit', '100.00', 50, '6a11afe6de066', NULL),
(49, 'fan-duster-(microfiber)', '100.00', 50, '6a11afe6de11f', NULL),
(50, 'flat-mop', '100.00', 50, '6a11afe6de1d5', NULL),
(51, 'flower-mop', '100.00', 50, '6a11afe6de291', NULL),
(52, 'frigde-cover', '100.00', 50, '6a11afe6de340', NULL),
(53, 'fruit-knife', '100.00', 50, '6a11afe6de3ee', NULL),
(54, 'fry-pan-l', '100.00', 50, '6a11afe6de4af', NULL),
(55, 'fry-pan-m', '100.00', 50, '6a11afe6de566', NULL),
(56, 'fry-pan-s', '100.00', 50, '6a11afe6de64a', NULL),
(57, 'garlic-press-1', '100.00', 50, '6a11afe6de704', NULL),
(58, 'garlic-press', '100.00', 50, '6a11afe6de7ba', NULL),
(59, 'gas-lighter', '100.00', 50, '6a11afe6de881', NULL),
(60, 'gloves-big', '100.00', 50, '6a11afe6de938', NULL),
(61, 'gloves', '100.00', 50, '6a11afe6dea7b', NULL),
(62, 'golden-hooks-3pcs', '100.00', 50, '6a11afe6deb2f', NULL),
(63, 'grater', '100.00', 50, '6a11afe6debf8', NULL),
(64, 'grey-duster-(9-foot-)', '100.00', 50, '6a11afe6decb3', NULL),
(65, 'handy-jhona', '100.00', 50, '6a11afe6ded67', NULL),
(66, 'hanger', '100.00', 50, '6a11afe6dee25', NULL),
(67, 'home-hook', '100.00', 50, '6a11afe6deed6', NULL),
(68, 'hook-white--(6-pcs-)', '100.00', 50, '6a11afe6def86', NULL),
(69, 'hot-pot-picker', '100.00', 50, '6a11afe6df03f', NULL),
(70, 'ice-bucket-1', '100.00', 50, '6a11afe6df109', NULL),
(71, 'ice-cube-tray', '100.00', 50, '6a11afe6df1bb', NULL),
(72, 'ice-roller', '100.00', 50, '6a11afe6df26f', NULL),
(73, 'ice-tray-(cube)', '100.00', 50, '6a11afe6df31e', NULL),
(74, 'ice-tray-with-lid', '100.00', 50, '6a11afe6df3d0', NULL),
(75, 'jali', '100.00', 50, '6a11afe6df48a', NULL),
(76, 'juice-mug(acrylic)', '100.00', 50, '6a11afe6df539', NULL),
(77, 'kh-mahroon-rajistani', '100.00', 50, '6a11afe6df5f6', NULL),
(78, 'kh-mahroon-rajistani', '100.00', 50, '6a11afe6df6af', NULL),
(79, 'kh-mahroon-rajistani', '100.00', 50, '6a11afe6df763', NULL),
(80, 'kh-mahroon-rajistani', '100.00', 50, '6a11afe6df811', NULL),
(81, 'kh-mahroon-rajistani', '100.00', 50, '6a11afe6df8c4', NULL),
(82, 'kitchen-dispenser', '100.00', 50, '6a11afe6df972', NULL),
(83, 'kitchen-knife-black-big', '100.00', 50, '6a11afe6dfa47', NULL),
(84, 'kitchen-knife-black-small', '100.00', 50, '6a11afe6dfb11', NULL),
(85, 'kitchen-sticker', '100.00', 50, '6a11afe6dfbcf', NULL),
(86, 'kp-antique-flowers', '100.00', 50, '6a11afe6dfca1', NULL),
(87, 'kp-antique-flowers', '100.00', 50, '6a11afe6dfdb6', NULL),
(88, 'kp-antique-flowers', '100.00', 50, '6a11afe6dfe6d', NULL),
(89, 'kp-antique-flowers', '100.00', 50, '6a11afe6dff24', NULL),
(90, 'kp-antique-flowers', '100.00', 50, '6a11afe6dffe4', NULL),
(91, 'lemon-squeezer', '100.00', 50, '6a11afe6e00b0', NULL),
(92, 'lid-covers-(100pcs)', '100.00', 50, '6a11afe6e015d', NULL),
(93, 'masala-box-(acrylic)', '100.00', 50, '6a11afe6e022a', NULL),
(94, 'micro-fibre-towel-pack', '100.00', 50, '6a11afe6e02fe', NULL),
(95, 'mini-beater', '100.00', 50, '6a11afe6e0419', NULL),
(96, 'mini-cotton-duster', '100.00', 50, '6a11afe6e04ca', NULL),
(97, 'mini-tong', '100.00', 50, '6a11afe6e058d', NULL),
(98, 'multi-brush-(3pcs)', '100.00', 50, '6a11afe6e0642', NULL),
(99, 'nali-brush', '100.00', 50, '6a11afe6e06f7', NULL),
(100, 'nice-bottle', '100.00', 50, '6a11afe6e07aa', NULL),
(101, 'null-shower', '100.00', 50, '6a11afe6e0862', NULL),
(102, 'nylon-rope', '100.00', 50, '6a11afe6e0912', NULL),
(103, 'oil-comb', '100.00', 50, '6a11afe6e09e2', NULL),
(104, 'oil-container-(stainless-steel)', '100.00', 50, '6a11afe6e0ab8', NULL),
(105, 'oil-spray-bottle', '100.00', 50, '6a11afe6e0b70', NULL),
(106, 'pata-hook', '100.00', 50, '6a11afe6e0c27', NULL),
(107, 'pizza-cutter', '100.00', 50, '6a11afe6e0cdb', NULL),
(108, 'plastic-basket', '100.00', 50, '6a11afe6e0d97', NULL),
(109, 'plastic-chai-chani', '100.00', 50, '6a11afe6e0e4d', NULL),
(110, 'plastic-chani-l', '100.00', 50, '6a11afe6e0f05', NULL),
(111, 'plastic-chani-m', '100.00', 50, '6a11afe6e0fb7', NULL),
(112, 'plastic-cutting-board', '100.00', 50, '6a11afe6e107c', NULL),
(113, 'plastic-dustbin', '100.00', 50, '6a11afe6e1138', NULL),
(114, 'plastic-namak-dani', '100.00', 50, '6a11afe6e11e7', NULL),
(115, 'plastic-salt-n-pepper', '100.00', 50, '6a11afe6e129a', NULL),
(116, 'plstc-beater', '100.00', 50, '6a11afe6e134c', NULL),
(117, 'portable-keef', '100.00', 50, '6a11afe6e1406', NULL),
(118, 'potato-masher-(steel)', '100.00', 50, '6a11afe6e14c3', NULL),
(119, 'potato-masher', '100.00', 50, '6a11afe6e1574', NULL),
(120, 'press-chopper-0.7-litre', '100.00', 50, '6a11afe6e162d', NULL),
(121, 'chopper-1.5-litre', '100.00', 50, '6a11afe6e16ea', NULL),
(122, 'press-chopper-2-litre', '100.00', 50, '6a11afe6e17a3', NULL),
(123, 'rectangle-hanging-rack', '100.00', 50, '6a11afe6e1859', NULL),
(124, 'cloth-line-(rope)', '100.00', 50, '6a11afe6e190d', NULL),
(125, 'roti-chimta', '100.00', 50, '6a11afe6e19c7', NULL),
(126, 'roti-matt', '100.00', 50, '6a11afe6e1a7e', NULL),
(127, 's.s-straw-2pcs', '100.00', 50, '6a11afe6e1b2c', NULL),
(128, 'scalp-massager', '100.00', 50, '6a11afe6e1be5', NULL),
(129, 'self-drainage-soap-dish', '100.00', 50, '6a11afe6e1c9b', NULL),
(130, 'self-mop', '100.00', 50, '6a11afe6e1d4d', NULL),
(131, 'semi-auto-beater', '100.00', 50, '6a11afe6e1dff', NULL),
(132, 'semi-auto-beater-(-small)', '100.00', 50, '6a11afe6e1eb9', NULL),
(133, 'shoe-wipe', '100.00', 50, '6a11afe6e1f92', NULL),
(134, 'silicon-spetula', '100.00', 50, '6a11afe6e2050', NULL),
(135, 'silicone-air-fryer-basket', '100.00', 50, '6a11afe6e2111', NULL),
(136, 'silicone-beater', '100.00', 50, '6a11afe6e21c0', NULL),
(137, 'silicone-deep-spetula', '100.00', 50, '6a11afe6e2273', NULL),
(138, 'silicone-deep-spoon', '100.00', 50, '6a11afe6e232c', NULL),
(139, 'silicone-palta', '100.00', 50, '6a11afe6e23ee', NULL),
(140, 'silicone-socks', '100.00', 50, '6a11afe6e24a2', NULL),
(141, 'silicone-soup-spoon', '100.00', 50, '6a11afe6e2566', NULL),
(142, 'silicone-spetula', '100.00', 50, '6a11afe6e261c', NULL),
(143, 'silicone-spetula-set-(10pcs)', '100.00', 50, '6a11afe6e26d6', NULL),
(144, 'silicone-travel-glass', '100.00', 50, '6a11afe6e2795', NULL),
(145, 'skeleton-soap-tray', '100.00', 50, '6a11afe6e285b', NULL),
(146, 'small-dori-chopper', '100.00', 50, '6a11afe6e2920', NULL),
(147, 'small-steel-beater', '100.00', 50, '6a11afe6e29cf', NULL),
(148, 'soap-box-acrylic', '100.00', 50, '6a11afe6e2a80', NULL),
(149, 'soap-dish-(peach)', '100.00', 50, '6a11afe6e2b63', NULL),
(150, 'soap-dish-(t.r)', '100.00', 50, '6a11afe6e2c59', NULL),
(151, 'soap-dish-(wall-mounted)', '100.00', 50, '6a11afe6e2d12', NULL),
(152, 'soap-dish-duck', '100.00', 50, '6a11afe6e2dc7', NULL),
(153, 'soap-dish-smp', '100.00', 50, '6a11afe6e2e7b', NULL),
(154, 'soap-dispenser-plastic', '100.00', 50, '6a11afe6e2f34', NULL),
(155, 'spray-bottle', '100.00', 50, '6a11afe6e2fe0', NULL),
(156, 'steam-jali', '100.00', 50, '6a11afe6e3098', NULL),
(157, 'steel-chana-big', '100.00', 50, '6a11afe6e3147', NULL),
(158, 'steel-chana-small', '100.00', 50, '6a11afe6e31f3', NULL),
(159, 'chai-chani-l', '100.00', 50, '6a11afe6e32a4', NULL),
(160, 'steel-chani-small', '100.00', 50, '6a11afe6e335c', NULL),
(161, 'steel-chani', '100.00', 50, '6a11afe6e340a', NULL),
(162, 'chai-chani-xl', '100.00', 50, '6a11afe6e34c4', NULL),
(163, 'steel-chuki-20pcs', '100.00', 50, '6a11afe6e3587', NULL),
(164, 'steel-chutki', '100.00', 50, '6a11afe6e3648', NULL),
(165, 'steel-container', '100.00', 50, '6a11afe6e3710', NULL),
(166, 'peeler', '100.00', 50, '6a11afe6e37c9', NULL),
(167, 'steel-spoon', '100.00', 50, '6a11afe6e3888', NULL),
(168, 'steel-straw-(pink)', '100.00', 50, '6a11afe6e393d', NULL),
(169, 'steel-straw-2-pcs', '100.00', 50, '6a11afe6e3a3c', NULL),
(170, 'steel-straw-blue', '100.00', 50, '6a11afe6e3b17', NULL),
(171, 'stickon-hook-patti', '100.00', 50, '6a11afe6e3bd8', NULL),
(172, 'tea-ball', '100.00', 50, '6a11afe6e3c95', NULL),
(173, 'transparent-hook', '100.00', 50, '6a11afe6e3d74', NULL),
(174, 'travel-kit', '100.00', 50, '6a11afe6e3e30', NULL),
(175, 'triangle-hanging-rack', '100.00', 50, '6a11afe6e3f9c', NULL),
(176, 'tumbler(800ml)', '100.00', 50, '6a11afe6e4060', NULL),
(177, 'water-pump', '100.00', 50, '6a11afe6e411c', NULL),
(178, 'white-soap-dish-e.r', '100.00', 50, '6a11afe6e41e8', NULL),
(179, 'wiper-yellow', '100.00', 50, '6a11afe6e42bc', NULL),
(180, 'wire-brush-small-pack', '100.00', 50, '6a11afe6e43ab', NULL),
(181, 'Biscuits Pack 181', '3566.00', 171, '6a11afe6e446a', NULL),
(182, 'Tea Pack 500G 182', '1367.00', 163, '6a11afe6e4549', NULL),
(183, 'Face Wash 183', '3502.00', 151, '6a11afe6e4618', NULL),
(184, 'Sausages Pack 184', '1093.00', 26, '6a11afe6e46e3', NULL),
(185, 'Milk Pack 185', '4568.00', 209, '6a11afe6e47a7', NULL),
(186, 'Soft Drink 1.5L 186', '1743.00', 6, '6a11afe6e486f', NULL),
(187, 'Flour 10KG 187', '1236.00', 217, '6a11afe6e4929', NULL),
(188, 'Cheese Slice 188', '3253.00', 145, '6a11afe6e49ff', NULL),
(189, 'Hand Wash 189', '3766.00', 122, '6a11afe6e4ae4', NULL),
(190, 'Coffee Jar 190', '2299.00', 201, '6a11afe6e4bcd', NULL),
(191, 'Noodles Pack 191', '4078.00', 284, '6a11afe6e4c97', NULL),
(192, 'Detergent Powder 192', '1780.00', 111, '6a11afe6e4d5a', NULL),
(193, 'Ketchup Bottle 193', '4094.00', 142, '6a11afe6e4e37', NULL),
(194, 'Mayonnaise Jar 194', '1699.00', 247, '6a11afe6e4f24', NULL),
(195, 'Detergent Powder 195', '3244.00', 178, '6a11afe6e4fee', NULL),
(196, 'Honey Jar 196', '3851.00', 231, '6a11afe6e50d1', NULL),
(197, 'Detergent Powder 197', '2164.00', 160, '6a11afe6e5187', NULL),
(198, 'Rice Bag 5KG 198', '725.00', 220, '6a11afe6e524d', NULL),
(199, 'Chips Pack 199', '2024.00', 290, '6a11afe6e530e', NULL),
(200, 'Sausages Pack 200', '4130.00', 146, '6a11afe6e53d4', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `cashier_id` int(11) DEFAULT NULL,
  `cashier_name` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `role` enum('admin','cashier','viewer') NOT NULL DEFAULT 'viewer'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user`, `pass`, `created_at`, `role`) VALUES
(1, 'hunain', '$2y$10$0q4PVRN.HKJabtxhd.nY1OsEaORItDfeQ/LUwF1wlxKsUvga0sDOC', '2026-01-23 11:49:29', 'admin'),
(2, 'Abdulsamad', '$2y$10$Ooti1P0o.MKVQF/JlT0ureYjwnbNQ62zWs4aQ60pwAyFErHADN2Gy', '2026-05-23 13:21:19', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `branch_sales`
--
ALTER TABLE `branch_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user` (`user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branch_sales`
--
ALTER TABLE `branch_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
