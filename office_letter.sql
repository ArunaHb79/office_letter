-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 01:22 PM
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
(9, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 15:26:51'),
(10, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', 'unknown', 'unknown', '2025-09-30 15:27:36'),
(14, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', 'unknown', 'unknown', '2025-09-30 15:34:11'),
(15, NULL, 'login_failed', NULL, NULL, '{\"username\":\"test@example.com\",\"reason\":\"invalid_password\",\"ip_address\":\"unknown\"}', 'unknown', 'unknown', '2025-09-30 12:04:11'),
(17, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 15:36:41'),
(18, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 15:48:48'),
(19, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 16:07:45'),
(21, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 16:17:38'),
(22, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 15:49:56'),
(23, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/119.0', '2025-11-03 16:08:19'),
(24, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 08:45:46'),
(25, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumathi@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 04:20:42'),
(26, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 08:50:53'),
(28, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:14:09'),
(29, 3, '0', 10, NULL, '{\"letter_number\":\"INV-2511-10\",\"subject\":\"නව පරිගණකයක් මිලදී ගැනීම\",\"sender\":\"තිස්සමහාරාම ප්‍රාදේශීය සභාව\",\"receiver\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:27:55'),
(30, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:28:19'),
(31, 4, '0', 10, '{\"subject\":\"නව පරිගණකයක් මිලදී ගැනීම\",\"content\":\"නව පරිගණකයක් මිලදී ගැනීම\",\"sender\":\"තිස්සමහාරාම ප්‍රාදේශීය සභාව\",\"receiver\":\"\",\"letter_number\":\"0\"}', '{\"subject\":\"නව පරිගණකයක් මිලදී ගැනීම\",\"content\":\"නව පරිගණකයක් මිලදී ගැනීම\",\"sender\":\"තිස්සමහාරාම ප්‍රාදේශීය සභාව\",\"receiver\":\"\",\"letter_number\":\"0\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:30:00'),
(32, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 09:39:59'),
(33, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0', '2025-11-07 09:43:04'),
(34, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 11:43:46'),
(35, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 14:24:33'),
(36, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 14:34:54'),
(37, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 15:00:14'),
(38, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 08:27:49'),
(39, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 09:16:51'),
(40, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 09:25:38'),
(41, 6, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 09:50:54'),
(42, 6, '0', 11, NULL, '{\"letter_number\":\"INV-2511-11\",\"subject\":\"à¶…à¶½à·”à¶­à·Šà¶œà·œà¶©à¶†à¶» à¶´à·Šâ€à¶»à¶¯à·šà·à¶ºà·š à¶…à¶±à·€à·ƒà¶» à¶‰à¶¯à·’à¶šà·’à¶»à·“à¶¸à¶šà·Š à·ƒà¶¸à·Šà¶¶à¶±à·Šà¶°à·€à¶ºà·’\",\"sender\":\"à¶šà·š.à¶šà·š. à·ƒà¶¸à¶±à·Šà¶´à·à¶½ à¶¸à·„à¶­à·\",\"receiver\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 09:58:15'),
(43, 6, '0', 12, NULL, '{\"letter_number\":\"INV-2511-12\",\"subject\":\"à¶œà·œà¶©à·€à·à¶±à·à¶œà·œà¶© à¶´à·à·ƒà¶½ à¶…à·ƒà¶½ à¶‡à¶­à·’ à¶…à¶±à¶­à·”à¶»à·”à¶¯à·à¶ºà¶š à¶¸à·à¶» à¶œà·ƒà¶šà·Š à·ƒà¶¸à·Šà¶¶à¶±à·Šà¶°à·€à¶ºà·’\",\"sender\":\"à¶§à·“.à¶´à·“  à¶…à¶¸à¶»à¶´à·à¶½\",\"receiver\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:07:38'),
(44, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:39:52'),
(45, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:40:11'),
(46, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:40:39'),
(47, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:40:53'),
(48, NULL, 'login_failed', NULL, NULL, '{\"username\":\"udayasiri@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 05:41:11'),
(49, 10, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:15:48'),
(50, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:34:09'),
(51, 11, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:37:02'),
(52, 11, '0', 13, NULL, '{\"letter_number\":\"EST-2511-13\",\"subject\":\"Transfer Request - Mr.K. Kumara - Tangalle PS\",\"sender\":\"Mr.K. Kumara\",\"receiver\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:39:36'),
(53, 11, '0', 14, NULL, '{\"letter_number\":\"EST-2511-14\",\"subject\":\"Monthly Management Review Meeting For Local Government Institution\",\"sender\":\"Local Government Department\",\"receiver\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:41:30'),
(54, 7, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 10:42:21'),
(55, 9, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:00:43'),
(56, 9, '0', 15, NULL, '{\"letter_number\":\"ACT-2511-15\",\"subject\":\"Salary Increments for Management Service Officers\",\"sender\":\"Local Government Department\",\"receiver\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:02:27'),
(58, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:06:40'),
(59, 7, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:34:05'),
(60, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 11:50:12'),
(61, 6, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 12:15:45'),
(62, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-08 12:18:03'),
(63, NULL, 'login_failed', NULL, NULL, '{\"username\":\"mas79ham@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 07:08:07'),
(64, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 11:38:14'),
(65, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 12:18:57'),
(66, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:29:44'),
(67, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:41:35'),
(68, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:52:08'),
(69, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 14:58:16'),
(70, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:09:27'),
(71, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:11:30'),
(72, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:26:41'),
(73, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:05:48'),
(74, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:06:10'),
(75, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:08:27'),
(76, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:13:14'),
(77, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:15:49'),
(78, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:16:06'),
(79, NULL, 'login_failed', NULL, NULL, '{\"username\":\"sumith@gmail.com\",\"reason\":\"invalid_password\",\"ip_address\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 11:17:48'),
(80, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 15:53:57'),
(81, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:06:46'),
(82, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:08:12'),
(83, 4, '0', 17, NULL, '{\"letter_number\":\"INV-2511-17\",\"subject\":\"කාර්යාලයේ පරිගණක ජාල පද්ධතිය අලුත්වැඩියා කිරීම\",\"sender\":\"බෙලිඅත්ත ප්‍රාදේශීය සභාව\",\"receiver\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:11:02'),
(84, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:21:39'),
(85, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:25:44'),
(86, 4, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:46:05'),
(87, 3, '0', NULL, NULL, '{\"method\":\"standard\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 16:52:46');

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
(20, 'Gayan', 'gayan@gmail.com', '$2y$10$9QCOlC/lo9sLQbtydscIE.YUGxs8LNBep0d6S/gNCX9pdN3rWFZS.', 1, 'subject_officer');

-- --------------------------------------------------------

--
-- Table structure for table `letter`
--

CREATE TABLE `letter` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `sender` varchar(100) DEFAULT NULL,
  `receiver` varchar(100) DEFAULT NULL,
  `method_id` int(11) DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `date_sent` date DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `letter_number` varchar(20) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `letter`
--

INSERT INTO `letter` (`id`, `subject`, `content`, `sender`, `receiver`, `method_id`, `date_received`, `date_sent`, `department_id`, `employee_id`, `letter_number`, `status_id`) VALUES
(10, 'නව පරිගණකයක් මිලදී ගැනීම', 'නව පරිගණකයක් මිලදී ගැනීම', 'තිස්සමහාරාම ප්‍රාදේශීය සභාව', '', 1, '2025-11-01', '2025-11-03', 3, 13, '0', 4),
(11, 'à¶…à¶½à·”à¶­à·Šà¶œà·œà¶©à¶†à¶» à¶´à·Šâ€à¶»à¶¯à·šà·à¶ºà·š à¶…à¶±à·€à·ƒà¶» à¶‰à¶¯à·’à¶šà·’à¶»à·“à¶¸à¶šà·Š à·ƒà¶¸à·Šà¶¶à¶±à·Šà¶°à·€à¶ºà·’', 'à¶…à¶±à·€à·ƒà¶» à¶‰à¶¯à·’à¶šà·’à¶»à·“à¶¸à·Š', 'à¶šà·š.à¶šà·š. à·ƒà¶¸à¶±à·Šà¶´à·à¶½ à¶¸à·„à¶­à·', '', 3, '2025-11-08', '0000-00-00', 3, 15, '0', 2),
(12, 'à¶œà·œà¶©à·€à·à¶±à·à¶œà·œà¶© à¶´à·à·ƒà¶½ à¶…à·ƒà¶½ à¶‡à¶­à·’ à¶…à¶±à¶­à·”à¶»à·”à¶¯à·à¶ºà¶š à¶¸à·à¶» à¶œà·ƒà¶šà·Š à·ƒà¶¸à·Šà¶¶à¶±à·Šà¶°à·€à¶ºà·’', 'à¶…à¶±à¶­à·”à¶»à·”à¶¯à·à¶ºà¶š à¶¸à·à¶» à¶œà·ƒà¶šà·Š à·ƒà¶¸à·Šà¶¶à¶±à·Šà¶°à·€à¶ºà·’', 'à¶§à·“.à¶´à·“  à¶…à¶¸à¶»à¶´à·à¶½', '', 2, '2025-11-08', '0000-00-00', 3, 15, '0', 1),
(13, 'Transfer Request - Mr.K. Kumara - Tangalle PS', 'Transfer Request', 'Mr.K. Kumara', '', 1, '2025-11-08', '0000-00-00', 1, 20, '0', 1),
(14, 'Monthly Management Review Meeting For Local Government Institution', 'Monthly Meeting', 'Local Government Department', '', 3, '2025-11-08', '0000-00-00', 1, 20, '0', 1),
(15, 'Salary Increments for Management Service Officers', 'Salary Increments', 'Local Government Department', '', 1, '2025-11-08', '0000-00-00', 2, 18, '0', 1),
(17, 'කාර්යාලයේ පරිගණක ජාල පද්ධතිය අලුත්වැඩියා කිරීම', 'කාර්යාලයේ පරිගණක ජාල පද්ධතිය අලුත්වැඩියා කිරීම', 'බෙලිඅත්ත ප්‍රාදේශීය සභාව', '', 3, '2025-11-18', '0000-00-00', 3, 13, '0', 3);

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
  `status_name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `letterstatus`
--

INSERT INTO `letterstatus` (`id`, `status_name`) VALUES
(1, 'Received'),
(2, 'Pending'),
(3, 'Processed'),
(4, 'Sent'),
(5, 'Completed');

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
(10, 17, '2b4fe0520636079444f936c0e5832b0753aba518fde9f371def1f13b09b2f4ee', '2025-11-18 17:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `employee_id`, `role`, `email`) VALUES
(3, 'mas79ham@gmail.com', '$2y$10$THyPVHA5SzeulM57fRA0guyaVG6gnqSyL/AtHkj0MAoT3vSbQkPSC', 12, 'institution_head', 'mas79ham@gmail.com'),
(4, 'sumathi@gmail.com', '$2y$10$w9BllkvD35rgK3O1qlIPieKYe7Sp2s3EdodAgipEDvM3yPMDRoydy', 13, 'subject_officer', 'sumathi@gmail.com'),
(6, 'kamal@gmail.com', '$2y$10$s9Cwi9JKKP5/8qkzM0xFlecPJNq8hat2X0aZZHgx3KgzRCV8Jnyv2', 15, 'subject_officer', 'kamal@gmail.com'),
(7, 'piyal@gmail.com', '$2y$10$kHJgBRijHcM5exuHWu8HC.Zd9BTeYArfUACwka0Nb5sh01oocdftS', 16, 'department_head', 'piyal@gmail.com'),
(8, 'sumith@gmail.com', '$2y$10$i55FgTBxOwStfWykAMTFsuZeWciVbG6EmB1TAG8R/DOx28/gc9yfS', 17, 'department_head', 'sumith@gmail.com'),
(9, 'damith@gmail.com', '$2y$10$xR59qbuHtC/plznZH7L5g.kDKZR3RoHcU2jdIGkrQJXi11.Le8V1W', 18, 'subject_officer', 'damith@gmail.com'),
(10, 'udaya@gmail.com', '$2y$10$UuBYtpZTbdZjrNetUP1MYe4QIEe/aiXPX0UUW0/CuS1DVtFa0GEj2', 19, 'department_head', 'udaya@gmail.com'),
(11, 'gayan@gmail.com', '$2y$10$9QCOlC/lo9sLQbtydscIE.YUGxs8LNBep0d6S/gNCX9pdN3rWFZS.', 20, 'subject_officer', 'gayan@gmail.com');

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
  ADD KEY `status_id` (`status_id`);

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
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activitylog`
--
ALTER TABLE `activitylog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `letter`
--
ALTER TABLE `letter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `lettermethod`
--
ALTER TABLE `lettermethod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `letterstatus`
--
ALTER TABLE `letterstatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  ADD CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`);

--
-- Constraints for table `letter`
--
ALTER TABLE `letter`
  ADD CONSTRAINT `letter_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`),
  ADD CONSTRAINT `letter_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `letter_ibfk_3` FOREIGN KEY (`method_id`) REFERENCES `lettermethod` (`id`),
  ADD CONSTRAINT `letter_ibfk_4` FOREIGN KEY (`status_id`) REFERENCES `letterstatus` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
