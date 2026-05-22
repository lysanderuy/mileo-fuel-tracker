-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 10:58 AM
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
-- Database: `mileo`
--
CREATE DATABASE IF NOT EXISTS `mileo`;
USE `mileo`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Lysander Uy', 'lysander.uy@gmail.com', '$2y$10$A1za8vNIyYmH8nqeEQJyauls9B4QeM29Bt8d90ktViGjcOzibWztO', '2026-04-25 09:08:42', '2026-04-25 09:08:42');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `make` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `fuel_type` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `tank_capacity` decimal(6,2) DEFAULT NULL COMMENT 'Full tank size in liters',
  `odometer` int(11) DEFAULT NULL COMMENT 'Current odometer reading in km',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  UNIQUE KEY `uk_user_plate` (`user_id`, `plate_number`),
  CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT IGNORE INTO `vehicles` (`id`, `user_id`, `name`, `make`, `model`, `year`, `fuel_type`, `color`, `plate_number`, `is_archived`, `created_at`, `updated_at`) VALUES
(1, 1, 'Daily', 'Toyota', 'Innova', 2014, 'Diesel', 'Silver', 'AAZ 7574', 0, '2026-04-25 09:22:19', '2026-04-25 09:32:33'),
(2, 1, 'Spanderr', 'Mitsubishi', 'Xpander Cross', 2025, 'Gasoline', 'Orange', 'GBH 3245', 0, '2026-04-25 09:54:30', '2026-04-25 09:54:30'),
(3, 1, 'Paniss', 'Mitsubishi', 'Adventure', 2009, 'Diesel', 'Silver', 'GMX 247', 0, '2026-04-27 08:54:30', '2026-04-27 08:54:30');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_logs`
--

CREATE TABLE IF NOT EXISTS `fuel_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `odometer` int(11) NOT NULL,
  `trip_distance` decimal(8,2) DEFAULT NULL,
  `liters_filled` decimal(8,2) NOT NULL,
  `fuel_price` decimal(10,2) NOT NULL,
  `is_full_tank` tinyint(1) NOT NULL DEFAULT 1,
  `cost_per_liter` decimal(10,2) GENERATED ALWAYS AS (CASE WHEN `liters_filled` > 0 THEN `fuel_price` / `liters_filled` ELSE NULL END) STORED,
  `cost_per_km` decimal(10,4) GENERATED ALWAYS AS (CASE WHEN `trip_distance` > 0 THEN `fuel_price` / `trip_distance` ELSE NULL END) STORED,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vehicle_id_log_date` (`vehicle_id`, `log_date`),
  KEY `idx_log_date` (`log_date`),
  CONSTRAINT `fuel_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fuel_logs_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
