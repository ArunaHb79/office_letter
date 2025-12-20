-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 08:53 PM
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
-- Database: `office_letter`
--

-- --------------------------------------------------------

--
-- Table structure for table `activitylog`
--

CREATE TABLE `activitylog` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `letter_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activitylog`
--

INSERT INTO `activitylog` (`id`, `user_id`, `action`, `letter_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1, 3, 'user_login', NULL, NULL, NULL, '127.0.0.1', 'Sample Browser', '2025-09-30 14:38:20'),
(4, 3, 'letter_viewed', NULL, NULL, NULL, '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Sample Browser', '2025-09-30 10:37:17'),
(5, NULL, 'login_failed', NULL, NULL, NULL, '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Sample Browser', '2025-09-30 11:05:37'),
(9, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 15:26:51'),
(10, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', 'unknown', 'unknown', '2025-09-30 15:27:36'),
(14, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', 'unknown', 'unknown', '2025-09-30 15:34:11'),
(15, NULL, 'login_failed', NULL, NULL, '{\"username\":\"test@example.com\",\"reason\":\"invalid_password\",\"ip_address\":\"unknown\"}', 'unknown', 'unknown', '2025-09-30 12:04:11'),
(17, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 15:36:41'),
(18, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 15:48:48'),
(19, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 16:07:45'),
(21, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 16:17:38'),
(22, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 15:49:56'),
(23, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/119.0', '2025-11-03 16:08:19'),
(24, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 08:45:46'),
(25, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumathi@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 04:20:42'),
(26, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 08:50:53'),
(28, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:14:09'),
(30, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:28:19'),
(32, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:39:59'),
(33, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0', '2025-11-07 09:43:04'),
(34, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 11:43:46'),
(35, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 14:24:33'),
(36, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 14:34:54'),
(37, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 15:00:14'),
(38, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 08:27:49'),
(39, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 09:16:51'),
(40, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 09:25:38'),
(41, 6, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 09:50:54'),
(44, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:39:52'),
(45, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:40:11'),
(46, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:40:39'),
(47, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:40:53'),
(48, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:41:11'),
(49, 10, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:15:48'),
(50, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:34:09'),
(51, 11, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:37:02'),
(54, 7, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:42:21'),
(55, 9, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:00:43'),
(58, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:06:40'),
(59, 7, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:34:05'),
(60, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:50:12'),
(61, 6, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 12:15:45'),
(62, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 12:18:03'),
(63, NULL, 'login_failed', NULL, NULL, '{\"username\":\"mas79ham@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 07:08:07'),
(64, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 11:38:14'),
(65, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 12:18:57'),
(66, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:29:44'),
(67, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:41:35'),
(68, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:52:08'),
(69, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:58:16'),
(70, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:09:27'),
(71, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:11:30'),
(72, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:26:41'),
(73, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:05:48'),
(74, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:06:10'),
(75, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:08:27'),
(76, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:13:14'),
(77, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:15:49'),
(78, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:16:06'),
(79, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:17:48'),
(80, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:53:57'),
(81, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:06:46'),
(82, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:08:12'),
(84, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:21:39'),
(85, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:25:44'),
(86, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:46:05'),
(87, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:52:46'),
(88, NULL, 'login_failed', NULL, NULL, '{\"username\":\"mas79ham@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-02 10:15:14'),
(89, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-02 10:15:24'),
(90, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 15:39:37'),
(91, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 15:55:13'),
(93, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 16:06:38'),
(95, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 16:24:00'),
(96, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 16:41:35'),
(102, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 16:55:03'),
(106, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumathi@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 17:06:38'),
(107, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 17:06:49'),
(108, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-08 17:12:07'),
(110, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-12 16:17:52'),
(111, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 09:01:50'),
(112, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 09:18:09'),
(113, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 10:06:36'),
(114, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 10:18:07'),
(117, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 11:23:14'),
(119, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumathi@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 12:40:33'),
(120, 4, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 12:40:42'),
(121, 3, 'system_operation', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 12:45:55'),
(124, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 13:33:05'),
(126, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 13:34:11'),
(127, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 13:48:45'),
(130, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 14:01:01'),
(132, 7, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 14:17:44'),
(133, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:46:40'),
(134, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 16:21:04'),
(135, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 16:21:48'),
(136, 7, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 16:32:59'),
(137, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sama@gmail.com\",\"reason\":\"user_not_found\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 17:29:07'),
(138, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 17:29:37'),
(139, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 17:34:50'),
(140, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 17:42:25'),
(141, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 17:44:12'),
(142, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 17:49:05'),
(143, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 17:52:03'),
(145, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:00:55'),
(147, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:01:55'),
(148, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 17:16:06'),
(149, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 09:43:34'),
(150, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 09:52:34'),
(151, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:00:23'),
(152, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 10:39:08'),
(153, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 11:02:01'),
(155, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:21:43'),
(156, 8, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:21:55'),
(157, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:48:08'),
(158, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 13:49:06'),
(159, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 15:30:34'),
(160, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 17:43:55'),
(161, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 11:00:10'),
(162, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 11:43:40'),
(163, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 11:45:29'),
(165, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 12:59:28'),
(166, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 13:50:54'),
(168, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 14:34:14'),
(169, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\",\"date_from\":\"2025-12-01\",\"date_to\":\"2025-12-19\"},\"filters_applied\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 14:34:29'),
(170, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Received\",\"date_from\":\"2025-12-01\",\"date_to\":\"2025-12-19\"},\"filters_applied\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 14:34:41'),
(171, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\",\"date_from\":\"2025-12-01\",\"date_to\":\"2025-12-19\"},\"filters_applied\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 14:34:58'),
(173, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 15:38:56'),
(174, 3, '0', NULL, NULL, '{\"search_criteria\":{\"date_from\":\"2025-12-01\",\"date_to\":\"2025-12-19\"},\"filters_applied\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 16:45:02'),
(176, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 17:18:36'),
(179, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:06:23'),
(180, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:09:07'),
(182, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:25:20'),
(183, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Sent\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:25:43'),
(184, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:27:58'),
(185, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:28:16'),
(186, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:28:36'),
(187, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:50:14'),
(188, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Processed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:52:07'),
(190, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Received\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:52:47'),
(191, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:57:40'),
(192, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:57:55'),
(193, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 19:58:03'),
(194, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 21:38:10'),
(195, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 21:40:58'),
(197, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-19 22:03:45'),
(198, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 07:37:53'),
(199, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 10:58:30'),
(200, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:25:24'),
(201, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:34:09'),
(202, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:35:59'),
(203, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:42:22'),
(204, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:48:07'),
(205, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:50:24'),
(207, 4, '0', NULL, '{\"employee_id\":13,\"department_id\":1}', '{\"employee_id\":13,\"department_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:55:47'),
(209, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 11:57:18'),
(210, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 12:02:25'),
(211, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:00:57'),
(212, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:01:43'),
(213, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:03:59'),
(214, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:04:12'),
(215, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:31:29'),
(216, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Approved\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:31:37'),
(217, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:41:46'),
(219, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:44:58'),
(220, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Pending\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:45:29'),
(221, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:46:23'),
(222, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:46:47'),
(224, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:55:03'),
(225, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:58:58'),
(226, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:10:23'),
(227, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:15:50'),
(228, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:16:32'),
(229, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:20:12'),
(230, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:20:14'),
(231, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:20:30'),
(232, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Under Review\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:20:35'),
(233, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:26:24'),
(234, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Under Review\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 15:26:41'),
(236, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:05:43'),
(237, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Under Review\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:06:08'),
(238, 3, '0', NULL, NULL, '{\"search_criteria\":{\"department_id\":\"2\",\"status\":\"Approved\"},\"filters_applied\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:24:03'),
(239, 3, '0', NULL, NULL, '{\"search_criteria\":{\"department_id\":\"3\",\"status\":\"Approved\"},\"filters_applied\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:24:10'),
(240, 3, '0', NULL, NULL, '{\"search_criteria\":{\"department_id\":\"3\",\"status\":\"Completed\"},\"filters_applied\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:24:23'),
(241, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 16:35:13'),
(242, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 19:38:19'),
(243, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 20:30:26'),
(244, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:07:47'),
(245, 8, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:09:20'),
(246, 8, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:10:30'),
(247, 8, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:11:28'),
(249, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:12:36'),
(255, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:16:05'),
(257, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:31:10'),
(258, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Pending\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:31:32'),
(260, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:33:31'),
(262, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:50:41'),
(264, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:52:01'),
(266, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:57:37'),
(267, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:59:02'),
(268, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 21:59:49'),
(277, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:31:59'),
(288, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:42:46'),
(289, 12, '0', 31, NULL, '{\"letter_number\":\"2512-31\",\"subject\":\"නව පරිගණක මිලදී ගැනීම\",\"sender\":\"බෙලිඅත්ත ප්‍රාදේශීය සභාව\",\"receiver\":\"\",\"department_id\":null,\"employee_id\":null,\"status_id\":2,\"method_id\":1,\"date_received\":\"2025-12-20\",\"has_attachment\":\"yes\",\"attachment_filename\":\"83159910_10157087215496094_1569702051519135744_n.jpg\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:44:25'),
(290, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:44:47'),
(291, 3, '0', 31, NULL, '{\"filename\":\"83159910_10157087215496094_1569702051519135744_n.jpg\",\"file_size\":50548,\"file_type\":\"jpg\",\"letter_number\":\"2512-31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:52:25'),
(292, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:53:42'),
(293, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:53:53'),
(294, 4, '0', 31, NULL, '{\"filename\":\"83159910_10157087215496094_1569702051519135744_n.jpg\",\"file_size\":50548,\"file_type\":\"jpg\",\"letter_number\":\"INV-2512-31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:57:42'),
(295, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:58:15'),
(296, 10, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Under Review\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:58:23'),
(297, 10, '0', 31, NULL, '{\"attachment_id\":10,\"filename\":\"52759745_2206454992951921_2485270834281709568_n.jpg\",\"label\":\"Supporting Document\",\"file_size\":33280,\"file_type\":\"jpg\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:58:52'),
(298, 10, '0', 31, NULL, '{\"attachment_id\":10,\"filename\":\"52759745_2206454992951921_2485270834281709568_n.jpg\",\"label\":\"Supporting Document\",\"file_size\":33280,\"file_type\":\"jpg\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:59:00'),
(299, 10, '0', 31, NULL, '{\"attachment_id\":9,\"filename\":\"597477204_122159527364888020_7766315825758221927_n.jpg\",\"label\":\"Reply Letter\",\"file_size\":213143,\"file_type\":\"jpg\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:59:02'),
(300, 10, '0', 31, NULL, '{\"attachment_id\":10,\"filename\":\"52759745_2206454992951921_2485270834281709568_n.jpg\",\"label\":\"Supporting Document\",\"file_size\":33280,\"file_type\":\"jpg\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:59:04'),
(301, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:00:32'),
(302, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Department Head Approved\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:00:49'),
(303, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Department Head Approved\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:02:04'),
(304, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Department Head Approved\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:05:17'),
(305, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Department Head Approved\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:07:35'),
(306, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Department Head Approved\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:11:44'),
(307, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:14:57');
INSERT INTO `activitylog` (`id`, `user_id`, `action`, `letter_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `timestamp`) VALUES
(308, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Under Review\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:19:03'),
(309, 3, '0', 31, NULL, '{\"filename\":\"83159910_10157087215496094_1569702051519135744_n.jpg\",\"file_size\":50548,\"file_type\":\"jpg\",\"letter_number\":\"INV-2512-31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:28:14'),
(310, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:47:39'),
(311, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:13:33'),
(312, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:14:07'),
(313, 4, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Completed\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:31:03'),
(314, 4, '0', 31, NULL, '{\"filename\":\"83159910_10157087215496094_1569702051519135744_n.jpg\",\"file_size\":50548,\"file_type\":\"jpg\",\"letter_number\":\"INV-2512-31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:31:14'),
(315, 12, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:32:41'),
(316, 12, '0', 32, NULL, '{\"letter_number\":\"2512-32\",\"subject\":\"To get Loan\",\"sender\":\"Hambantota UC\",\"receiver\":\"Assistant Commissioner of Local Government\",\"department_id\":null,\"employee_id\":null,\"status_id\":2,\"method_id\":3,\"date_received\":\"2025-12-21\",\"has_attachment\":\"yes\",\"attachment_filename\":\"09.25-01162017091931.pdf\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:34:10'),
(317, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:35:14'),
(318, 3, '0', 32, NULL, '{\"filename\":\"09.25-01162017091931.pdf\",\"file_size\":2951700,\"file_type\":\"pdf\",\"letter_number\":\"ACT-2512-32\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:36:40'),
(319, 9, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:37:51'),
(320, 9, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Assigned\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:38:05'),
(321, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:39:05'),
(322, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:39:15'),
(323, 3, '0', 32, NULL, '{\"filename\":\"09.25-01162017091931.pdf\",\"file_size\":2951700,\"file_type\":\"pdf\",\"letter_number\":\"ACT-2512-32\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:39:24'),
(324, 9, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:42:23'),
(325, 9, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"In Progress\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:42:43'),
(326, 9, '0', 32, NULL, '{\"filename\":\"09.25-01162017091931.pdf\",\"file_size\":2951700,\"file_type\":\"pdf\",\"letter_number\":\"ACT-2512-32\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:43:49'),
(327, 8, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:45:31'),
(328, 8, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Under Review\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:45:45'),
(329, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:47:36'),
(330, 3, '0', NULL, NULL, '{\"search_criteria\":{\"status\":\"Department Head Approved\"},\"filters_applied\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 00:47:47'),
(331, 3, 'attachment_viewed', 32, NULL, '{\"filename\":\"09.25-01162017091931.pdf\",\"file_size\":2951700,\"file_type\":\"pdf\",\"letter_number\":\"ACT-2512-32\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 01:18:21'),
(332, 3, 'attachment_viewed', 31, NULL, '{\"filename\":\"83159910_10157087215496094_1569702051519135744_n.jpg\",\"file_size\":50548,\"file_type\":\"jpg\",\"letter_number\":\"INV-2512-31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 01:18:26'),
(333, 3, 'attachment_viewed', 32, NULL, '{\"filename\":\"09.25-01162017091931.pdf\",\"file_size\":2951700,\"file_type\":\"pdf\",\"letter_number\":\"ACT-2512-32\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 01:18:31'),
(334, 3, 'attachment_viewed', 31, NULL, '{\"filename\":\"83159910_10157087215496094_1569702051519135744_n.jpg\",\"file_size\":50548,\"file_type\":\"jpg\",\"letter_number\":\"INV-2512-31\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 01:18:39');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `abbreviation` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`id`, `name`, `abbreviation`) VALUES
(1, 'Establishments', 'EST'),
(2, 'Accounts', 'ACT'),
(3, 'Investigations', 'INV');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `name`, `email`, `password`, `department_id`, `role`) VALUES
(12, 'Aruna', 'mas79ham@gmail.com', '$2y$10$THyPVHA5SzeulM57fRA0guyaVG6gnqSyL/AtHkj0MAoT3vSbQkPSC', 1, 'institution_head'),
(13, 'Sumathipala', 'sumathi@gmail.com', '$2y$10$w9BllkvD35rgK3O1qlIPieKYe7Sp2s3EdodAgipEDvM3yPMDRoydy', 3, 'subject_officer'),
(15, 'Kamal', 'kamal@gmail.com', '$2y$10$s9Cwi9JKKP5/8qkzM0xFlecPJNq8hat2X0aZZHgx3KgzRCV8Jnyv2', 3, 'subject_officer'),
(16, 'Piyal', 'piyal@gmail.com', '$2y$10$kHJgBRijHcM5exuHWu8HC.Zd9BTeYArfUACwka0Nb5sh01oocdftS', 1, 'department_head'),
(17, 'Sumith', 'sumith@gmail.com', '$2y$10$i55FgTBxOwStfWykAMTFsuZeWciVbG6EmB1TAG8R/DOx28/gc9yfS', 2, 'department_head'),
(18, 'Damith', 'damith@gmail.com', '$2y$10$xR59qbuHtC/plznZH7L5g.kDKZR3RoHcU2jdIGkrQJXi11.Le8V1W', 2, 'subject_officer'),
(19, 'Udaya', 'udaya@gmail.com', '$2y$10$UuBYtpZTbdZjrNetUP1MYe4QIEe/aiXPX0UUW0/CuS1DVtFa0GEj2', 3, 'department_head'),
(20, 'Gayan', 'gayan@gmail.com', '$2y$10$9QCOlC/lo9sLQbtydscIE.YUGxs8LNBep0d6S/gNCX9pdN3rWFZS.', 1, 'subject_officer'),
(21, 'Saman Ekanayaka', 'saman@gmail.com', '$2y$10$hLZvtpaMsw3akmhgnVn5hecMdtXPsZhWOudK2ZB9.7dfc6tabxHyS', 1, 'chief_management_assistant');

-- --------------------------------------------------------

--
-- Table structure for table `letter`
--

CREATE TABLE `letter` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `attachment_filename` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_uploaded_at` timestamp NULL DEFAULT NULL,
  `sender` varchar(255) DEFAULT NULL,
  `receiver` varchar(255) DEFAULT NULL,
  `recipient_organization` varchar(255) DEFAULT NULL COMMENT 'Organization receiving the letter (for outgoing letters)',
  `recipient_person` varchar(255) DEFAULT NULL COMMENT 'Person receiving the letter (for outgoing letters)',
  `reference_letter_number` varchar(100) DEFAULT NULL COMMENT 'Reference to incoming letter if this is a reply',
  `method_id` int(11) DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `date_sent` date DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL COMMENT 'Assigned subject officer',
  `letter_number` varchar(20) DEFAULT NULL,
  `letter_direction` enum('incoming','outgoing_institution','outgoing_officer') DEFAULT 'incoming' COMMENT 'Letter direction: incoming, outgoing from institution, or outgoing from officer',
  `status_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Instructions/notes for assignment or sending',
  `assigned_by` int(11) DEFAULT NULL COMMENT 'User who assigned this letter',
  `assigned_at` timestamp NULL DEFAULT NULL COMMENT 'When letter was assigned',
  `processed_by` int(11) DEFAULT NULL COMMENT 'Department head who processed',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT 'When processed by dept head',
  `completed_by` int(11) DEFAULT NULL COMMENT 'Institution head who completed',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'When marked as completed',
  `letter_type` enum('incoming','outgoing') DEFAULT 'incoming' COMMENT 'Type of letter: incoming (received) or outgoing (sending)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `letter`
--

INSERT INTO `letter` (`id`, `subject`, `content`, `attachment_filename`, `attachment_path`, `attachment_uploaded_at`, `sender`, `receiver`, `recipient_organization`, `recipient_person`, `reference_letter_number`, `method_id`, `date_received`, `date_sent`, `department_id`, `employee_id`, `letter_number`, `letter_direction`, `status_id`, `notes`, `assigned_by`, `assigned_at`, `processed_by`, `processed_at`, `completed_by`, `completed_at`, `letter_type`) VALUES
(31, 'නව පරිගණක මිලදී ගැනීම', NULL, '83159910_10157087215496094_1569702051519135744_n.jpg', 'letter_6946d97135bbe6.21587471_1766250865.jpg', NULL, 'බෙලිඅත්ත ප්‍රාදේශීය සභාව', '', '', '', '', 1, '2025-12-20', '2025-12-22', 3, 13, 'INV-2512-31', 'incoming', 5, '', 3, '2025-12-20 17:22:37', NULL, NULL, 3, '2025-12-20 17:57:40', 'incoming'),
(32, 'To get Loan', NULL, '09.25-01162017091931.pdf', 'letter_6946f329e29283.94379592_1766257449.pdf', NULL, 'Hambantota UC', 'Assistant Commissioner of Local Government', '', '', '', 3, '2025-12-21', '2025-12-21', 2, 18, 'ACT-2512-32', 'incoming', 5, '', 3, '2025-12-20 19:05:56', NULL, NULL, 3, '2025-12-20 19:18:30', 'incoming');

-- --------------------------------------------------------

--
-- Table structure for table `letterattachments`
--

CREATE TABLE `letterattachments` (
  `id` int(11) NOT NULL,
  `letter_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL COMMENT 'Original filename',
  `file_path` varchar(255) NOT NULL COMMENT 'Stored filename',
  `file_size` int(11) NOT NULL COMMENT 'File size in bytes',
  `file_type` varchar(50) NOT NULL COMMENT 'File extension',
  `attachment_label` varchar(100) DEFAULT NULL COMMENT 'Label: Original Letter, Reply, Supporting Doc, etc.',
  `uploaded_by` int(11) NOT NULL COMMENT 'User who uploaded',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Multiple attachments per letter';

--
-- Dumping data for table `letterattachments`
--

INSERT INTO `letterattachments` (`id`, `letter_id`, `file_name`, `file_path`, `file_size`, `file_type`, `attachment_label`, `uploaded_by`, `uploaded_at`) VALUES
(9, 31, '597477204_122159527364888020_7766315825758221927_n.jpg', '1766251555_597477204_122159527364888020_7766315825758221927_n.jpg', 213143, 'jpg', 'Reply Letter', 4, '2025-12-20 17:25:55'),
(10, 31, '52759745_2206454992951921_2485270834281709568_n.jpg', '1766251590_52759745_2206454992951921_2485270834281709568_n.jpg', 33280, 'jpg', 'Supporting Document', 4, '2025-12-20 17:26:30'),
(11, 32, '83159910_10157087215496094_1569702051519135744_n.jpg', '1766258026_83159910_10157087215496094_1569702051519135744_n.jpg', 50548, 'jpg', 'Reply Letter', 9, '2025-12-20 19:13:46');

-- --------------------------------------------------------

--
-- Table structure for table `letterinstructions`
--

CREATE TABLE `letterinstructions` (
  `id` int(11) NOT NULL,
  `letter_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `instruction_type` enum('assignment','note','instruction','update') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Instructions and notes for letters with user tracking';

-- --------------------------------------------------------

--
-- Table structure for table `lettermethod`
--

CREATE TABLE `lettermethod` (
  `id` int(11) NOT NULL,
  `method_name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lettermethod`
--

INSERT INTO `lettermethod` (`id`, `method_name`) VALUES
(1, 'email'),
(2, 'regular mail'),
(3, 'registered mail'),
(4, 'hand delivery');

-- --------------------------------------------------------

--
-- Table structure for table `letterstatus`
--

CREATE TABLE `letterstatus` (
  `id` int(11) NOT NULL,
  `status_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `letterstatus`
--

INSERT INTO `letterstatus` (`id`, `status_name`) VALUES
(1, 'Received'),
(2, 'Pending'),
(3, 'Processed'),
(4, 'Sent'),
(5, 'Completed'),
(6, 'Assigned'),
(7, 'In Progress'),
(8, 'Sent to Department H'),
(9, 'On Hold'),
(10, 'Draft'),
(11, 'Under Department Rev'),
(12, 'Awaiting Institution'),
(13, 'Ready to Send'),
(14, 'Filed - Information Only'),
(15, 'Under Review'),
(16, 'Approved'),
(17, 'Rejected');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `employee_id`, `token`, `expires_at`) VALUES
(1, 12, '725dd6631270fc0142f24cde4666177ea592b3d0f8b6779121adfa6298d2de9d', '2025-09-30 09:23:49'),
(2, 12, 'b40b659201711b7dd1064264b33f98435dfddf25103c3eb695c93984cd606313', '2025-09-30 09:29:29'),
(3, 12, '8d7215dd751f177cc5d8df97e32f7dab55dccc93e7a6a8d2cbf72b8870e89449', '2025-09-30 09:29:48'),
(4, 12, '37e7d1801c3670ba1c556587ee05803da34ecd31c9768e4db3db2ab45517927c', '2025-09-30 09:29:55'),
(5, 12, 'cce324ddf48bdba606b10e973a20abd6d58da7e9920002253fa2dfa777201408', '2025-09-30 09:29:57'),
(6, 12, 'f867a0b4d67301b15d54ad546a9ffc4bda8dc2eb9579ca19678e3f314c18d59d', '2025-09-30 09:30:00'),
(9, 12, 'e76d0f481f75fbb71cd41d8ba8e0fc080df55c00f99fe51b632db4448256803a', '2025-11-03 12:42:12'),
(10, 17, '2b4fe0520636079444f936c0e5832b0753aba518fde9f371def1f13b09b2f4ee', '2025-11-18 17:12:57'),
(11, 12, 'bbbc3392c2d2f9e03b8d3dab62a57655bc7cf575ae2bfa0ca6e6c10f84779ad9', '2025-12-15 10:17:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL COMMENT 'Last login timestamp',
  `employee_id` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) DEFAULT 0 COMMENT 'Account approval status: 0=pending, 1=approved',
  `approved_by` int(11) DEFAULT NULL COMMENT 'User ID who approved this account',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'When account was approved',
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created this account'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `last_login`, `employee_id`, `role`, `email`, `created_at`, `approved`, `approved_by`, `approved_at`, `created_by`) VALUES
(3, 'mas79ham@gmail.com', '$2y$10$THyPVHA5SzeulM57fRA0guyaVG6gnqSyL/AtHkj0MAoT3vSbQkPSC', '2025-12-21 00:47:36', 12, 'institution_head', 'mas79ham@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(4, 'sumathi@gmail.com', '$2y$10$w9BllkvD35rgK3O1qlIPieKYe7Sp2s3EdodAgipEDvM3yPMDRoydy', '2025-12-21 00:14:06', 13, 'subject_officer', 'sumathi@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(6, 'kamal@gmail.com', '$2y$10$s9Cwi9JKKP5/8qkzM0xFlecPJNq8hat2X0aZZHgx3KgzRCV8Jnyv2', '2025-12-20 16:44:55', 15, 'subject_officer', 'kamal@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(7, 'piyal@gmail.com', '$2y$10$kHJgBRijHcM5exuHWu8HC.Zd9BTeYArfUACwka0Nb5sh01oocdftS', '2025-12-20 16:44:55', 16, 'department_head', 'piyal@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(8, 'sumith@gmail.com', '$2y$10$i55FgTBxOwStfWykAMTFsuZeWciVbG6EmB1TAG8R/DOx28/gc9yfS', '2025-12-21 00:45:30', 17, 'department_head', 'sumith@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(9, 'damith@gmail.com', '$2y$10$xR59qbuHtC/plznZH7L5g.kDKZR3RoHcU2jdIGkrQJXi11.Le8V1W', '2025-12-21 00:42:22', 18, 'subject_officer', 'damith@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(10, 'udaya@gmail.com', '$2y$10$UuBYtpZTbdZjrNetUP1MYe4QIEe/aiXPX0UUW0/CuS1DVtFa0GEj2', '2025-12-21 00:13:33', 19, 'department_head', 'udaya@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(11, 'gayan@gmail.com', '$2y$10$9QCOlC/lo9sLQbtydscIE.YUGxs8LNBep0d6S/gNCX9pdN3rWFZS.', '2025-12-20 16:44:55', 20, 'subject_officer', 'gayan@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL),
(12, 'saman@gmail.com', '$2y$10$hLZvtpaMsw3akmhgnVn5hecMdtXPsZhWOudK2ZB9.7dfc6tabxHyS', '2025-12-21 00:32:41', 21, 'chief_management_assistant', 'saman@gmail.com', '2025-12-19 10:14:28', 1, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activitylog`
--
ALTER TABLE `activitylog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `letter_id` (`letter_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `letter`
--
ALTER TABLE `letter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `method_id` (`method_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `idx_letter_attachment` (`attachment_filename`),
  ADD KEY `fk_letter_assigned_by` (`assigned_by`),
  ADD KEY `fk_letter_processed_by` (`processed_by`),
  ADD KEY `fk_letter_completed_by` (`completed_by`),
  ADD KEY `idx_letter_type` (`letter_type`),
  ADD KEY `idx_letter_direction` (`letter_direction`),
  ADD KEY `idx_reference_letter` (`reference_letter_number`);

--
-- Indexes for table `letterattachments`
--
ALTER TABLE `letterattachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_letter_attachments` (`letter_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `letterinstructions`
--
ALTER TABLE `letterinstructions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instructions_letter` (`letter_id`),
  ADD KEY `idx_instructions_user` (`user_id`),
  ADD KEY `idx_instructions_created` (`created_at`);

--
-- Indexes for table `lettermethod`
--
ALTER TABLE `lettermethod`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `letterstatus`
--
ALTER TABLE `letterstatus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_users_approved_by` (`approved_by`),
  ADD KEY `fk_users_created_by` (`created_by`),
  ADD KEY `idx_users_approved` (`approved`),
  ADD KEY `idx_last_login` (`last_login`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activitylog`
--
ALTER TABLE `activitylog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=335;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `letter`
--
ALTER TABLE `letter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `letterattachments`
--
ALTER TABLE `letterattachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `letterinstructions`
--
ALTER TABLE `letterinstructions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lettermethod`
--
ALTER TABLE `lettermethod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `letterstatus`
--
ALTER TABLE `letterstatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activitylog`
--
ALTER TABLE `activitylog`
  ADD CONSTRAINT `activitylog_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `activitylog_ibfk_2` FOREIGN KEY (`letter_id`) REFERENCES `letter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `letter`
--
ALTER TABLE `letter`
  ADD CONSTRAINT `fk_letter_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_letter_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_letter_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `letter_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`),
  ADD CONSTRAINT `letter_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `letter_ibfk_3` FOREIGN KEY (`method_id`) REFERENCES `lettermethod` (`id`),
  ADD CONSTRAINT `letter_ibfk_4` FOREIGN KEY (`status_id`) REFERENCES `letterstatus` (`id`);

--
-- Constraints for table `letterattachments`
--
ALTER TABLE `letterattachments`
  ADD CONSTRAINT `letterattachments_ibfk_1` FOREIGN KEY (`letter_id`) REFERENCES `letter` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `letterattachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `letterinstructions`
--
ALTER TABLE `letterinstructions`
  ADD CONSTRAINT `letterinstructions_ibfk_1` FOREIGN KEY (`letter_id`) REFERENCES `letter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `letterinstructions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
