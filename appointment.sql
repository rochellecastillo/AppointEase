-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 15, 2025 at 03:33 AM
-- Server version: 8.0.34
-- PHP Version: 8.2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `appointment`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblappointment`
--

CREATE TABLE `tblappointment` (
  `id` int NOT NULL,
  `booking_date` date NOT NULL,
  `user_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblappointment`
--

INSERT INTO `tblappointment` (`id`, `booking_date`, `user_id`, `doctor`, `status`) VALUES
(2, '2025-11-13', 'client1001', 'U251025-579', 0),
(3, '2025-11-20', 'client1001', 'U251025-579', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tblinfo`
--

CREATE TABLE `tblinfo` (
  `id` int NOT NULL,
  `user_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bdate` date NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblinfo`
--

INSERT INTO `tblinfo` (`id`, `user_id`, `last_name`, `first_name`, `middle_name`, `specialization`, `bdate`, `gender`, `address`, `contact`, `image`) VALUES
(1, 'user0001', 'dela cruz', 'juan', 'cruz', '', '2000-01-01', 'male', 'rosario batangas', '09913575449', ''),
(3, 'U251024-236', 'Cruz', 'Joan', 'asas', '', '2025-10-24', 'female', 'San Juan, Batangas', '12121212', ''),
(4, 'U251025-579', 'Doctor', 'Nobita', 'A', 'General Practitioner', '2025-01-01', 'female', 'Rosario, Batangas', '1111111', ''),
(9, 'client1001', 'Matundan', 'Ash', 'Caelum', '', '2000-11-01', 'male', 'Lipa City', '09633883956', ''),
(10, 'haiiro', 'Sora', 'Haiiro', '', '', '2025-11-15', 'male', 'Rosario Batangas', '09913575449', '');

-- --------------------------------------------------------

--
-- Table structure for table `tblnoappointment`
--

CREATE TABLE `tblnoappointment` (
  `id` int NOT NULL,
  `doctor_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `reason` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblotp`
--

CREATE TABLE `tblotp` (
  `id` int NOT NULL,
  `user_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timegenerated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblotp`
--

INSERT INTO `tblotp` (`id`, `user_id`, `otp`, `timegenerated`) VALUES
(4, 'user0001', '350821', '2025-10-19 17:55:01'),
(5, 'user0001', '976061', '2025-10-20 12:05:39'),
(6, 'user0001', '797161', '2025-10-20 15:51:44'),
(7, 'user0001', '306466', '2025-10-21 13:07:27'),
(8, 'user0001', '649219', '2025-10-24 14:36:32'),
(9, 'user0001', '391706', '2025-10-24 21:47:02'),
(10, 'user0001', '995894', '2025-10-25 08:03:56'),
(11, 'user0001', '830284', '2025-10-25 09:18:21'),
(12, 'U251024-236', '756113', '2025-10-25 09:33:40'),
(13, 'user0001', '779529', '2025-10-25 10:06:49'),
(14, 'user0001', '270492', '2025-10-25 12:01:12'),
(15, 'user0001', '113009', '2025-10-25 16:00:14'),
(16, 'user0001', '412091', '2025-10-25 16:54:40'),
(17, 'user0001', '952584', '2025-10-25 23:31:43'),
(18, 'user0001', '607951', '2025-10-28 08:38:00'),
(19, 'user0001', '100751', '2025-10-31 11:57:08'),
(20, 'user0001', '692796', '2025-10-31 12:02:21'),
(21, 'user0001', '128623', '2025-10-31 12:07:41'),
(22, 'U251024-236', '620505', '2025-10-31 13:58:27'),
(23, 'user0001', '603879', '2025-10-31 17:03:34'),
(24, 'user0001', '779295', '2025-10-31 17:09:13'),
(25, 'user0001', '210644', '2025-10-31 17:17:49'),
(26, 'user0001', '706758', '2025-10-31 17:19:15'),
(27, 'U251024-236', '623996', '2025-10-31 21:15:22'),
(28, 'user0001', '739427', '2025-11-01 15:57:11'),
(29, 'client1001', '354228', '2025-11-01 21:41:10'),
(30, 'client1001', '995488', '2025-11-02 09:34:35'),
(31, 'client1001', '173817', '2025-11-02 16:06:41'),
(32, 'client1001', '682811', '2025-11-04 19:38:34'),
(33, 'client1001', '331063', '2025-11-04 19:39:58'),
(34, 'user0001', '787266', '2025-11-04 21:40:53'),
(35, 'client1001', '146325', '2025-11-04 21:42:00'),
(36, 'client1001', '904138', '2025-11-05 11:20:14'),
(37, 'client1001', '922080', '2025-11-11 08:41:28'),
(38, 'client1001', '941635', '2025-11-11 08:47:19'),
(39, 'client1001', '693223', '2025-11-11 11:13:17'),
(40, 'client1001', '519139', '2025-11-11 11:13:50'),
(41, 'haiiro', '539754', '2025-11-15 09:03:06'),
(42, 'client1001', '647829', '2025-11-15 11:26:46'),
(43, 'client1001', '334934', '2025-11-15 11:30:57');

-- --------------------------------------------------------

--
-- Table structure for table `tblschedule`
--

CREATE TABLE `tblschedule` (
  `id` int NOT NULL,
  `user_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day` int NOT NULL,
  `time` time NOT NULL,
  `time2` time NOT NULL,
  `max_appointment` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblschedule`
--

INSERT INTO `tblschedule` (`id`, `user_id`, `day`, `time`, `time2`, `max_appointment`) VALUES
(1, 'U251025-579', 2, '08:00:00', '10:00:00', 15),
(2, 'U251025-579', 4, '08:00:00', '12:00:00', 16);

-- --------------------------------------------------------

--
-- Table structure for table `tblspecialization`
--

CREATE TABLE `tblspecialization` (
  `id` int NOT NULL,
  `specialization` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblspecialization`
--

INSERT INTO `tblspecialization` (`id`, `specialization`) VALUES
(1, 'Internal Medicine'),
(2, 'Pediatrics'),
(3, 'Cardiology'),
(4, 'Pulmonology'),
(5, 'Gastroenterology'),
(6, 'Nephrology'),
(7, 'Hematology'),
(8, 'Rheumatology'),
(9, 'Endocrinology');

-- --------------------------------------------------------

--
-- Table structure for table `tbluser`
--

CREATE TABLE `tbluser` (
  `id` int NOT NULL,
  `user_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbluser`
--

INSERT INTO `tbluser` (`id`, `user_id`, `user_name`, `password`, `user_type`, `status`) VALUES
(3, 'user0001', 'admin', '$2y$10$RM5bFGOl/8Jqgub98Zw02Ou90otsG7rf1BIsd9/b0469Sm54Mc3n2', 'admin', 1),
(6, 'U251024-236', 'U251024-236', '$2y$10$I0ZyZUQ75qOhrwdQQYOhXOFx0iSo7wOExQujRxYYLW6ujxBfIRsEG', 'client', 1),
(7, 'U251025-579', 'U251025-579', '$2y$10$7nMO44N5STK.A4BssoP3ju0LJP4jCNkmVtUigrk1z1V.ljPTPX5my', 'user', 1),
(12, 'client1001', 'client1001', '$2y$10$0CHsvSmdbf4Ai8tOg8GiMuvob.RPdkUPmjEkICUvfcbbdlylh1tt6', 'client', 1),
(14, 'haiiro', 'haiiro', '12345', 'client', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblappointment`
--
ALTER TABLE `tblappointment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tblinfo`
--
ALTER TABLE `tblinfo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `tblnoappointment`
--
ALTER TABLE `tblnoappointment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `tblotp`
--
ALTER TABLE `tblotp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tblschedule`
--
ALTER TABLE `tblschedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tblspecialization`
--
ALTER TABLE `tblspecialization`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`,`user_id`),
  ADD KEY `user_details` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblappointment`
--
ALTER TABLE `tblappointment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblinfo`
--
ALTER TABLE `tblinfo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tblnoappointment`
--
ALTER TABLE `tblnoappointment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblotp`
--
ALTER TABLE `tblotp`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `tblschedule`
--
ALTER TABLE `tblschedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblspecialization`
--
ALTER TABLE `tblspecialization`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblappointment`
--
ALTER TABLE `tblappointment`
  ADD CONSTRAINT `tblappointment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `tblnoappointment`
--
ALTER TABLE `tblnoappointment`
  ADD CONSTRAINT `tblnoappointment_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `tblinfo` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `tblotp`
--
ALTER TABLE `tblotp`
  ADD CONSTRAINT `tblotp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`);

--
-- Constraints for table `tblschedule`
--
ALTER TABLE `tblschedule`
  ADD CONSTRAINT `tblschedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD CONSTRAINT `user_details` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
