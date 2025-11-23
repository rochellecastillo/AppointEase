-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 01:54 PM
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
-- Database: `appointment`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblappointment`
--

CREATE TABLE `tblappointment` (
  `id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time DEFAULT NULL,
  `user_id` varchar(30) NOT NULL,
  `doctor` varchar(30) NOT NULL,
  `status` int(11) NOT NULL,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblappointment`
--

INSERT INTO `tblappointment` (`id`, `booking_date`, `booking_time`, `user_id`, `doctor`, `status`, `reminder_sent`, `reminder_sent_at`) VALUES
(2, '2025-11-15', NULL, 'U251103-005', 'U251109-005', 1, 0, NULL),
(3, '2025-11-15', NULL, 'U251110-006', 'U251109-005', 1, 0, NULL),
(4, '2025-11-15', NULL, 'U251110-006', 'U251109-005', 2, 0, NULL),
(5, '2025-11-28', '07:30:00', 'U251110-006', 'U251109-005', 1, 0, NULL),
(6, '2025-11-22', '09:28:00', 'U251110-006', 'U251109-005', 3, 0, NULL),
(7, '2025-12-13', '13:00:00', 'U251110-006', 'U251107-001', 2, 0, NULL),
(8, '2025-11-29', '10:28:00', 'U251110-006', 'U251109-005', 1, 0, NULL),
(9, '2025-12-06', '07:28:00', 'U251110-006', 'U251109-005', 2, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblinfo`
--

CREATE TABLE `tblinfo` (
  `id` int(11) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `last_name` varchar(40) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `middle_name` varchar(40) NOT NULL,
  `specialization` varchar(30) NOT NULL,
  `bdate` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `image` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblinfo`
--

INSERT INTO `tblinfo` (`id`, `user_id`, `last_name`, `first_name`, `email`, `middle_name`, `specialization`, `bdate`, `gender`, `address`, `contact`, `image`) VALUES
(1, 'user0001', 'dela cruz', 'juan', NULL, 'cruz', '', '2000-01-01', 'male', 'rosario batangas', '09913575449', ''),
(3, 'U251024-236', 'Cruz', 'Joan', NULL, 'asas', '', '2025-10-24', 'female', 'San Juan, Batangas', '12121212', ''),
(4, 'U251025-579', 'uuu', 'uuu', NULL, 'uuu', 'General Practitioner', '2025-01-01', 'female', 'Rosario, Batangas', '09162841625', ''),
(8, 'U251103-001', 'Manguiat', 'Kit', NULL, 'N', '', '2025-11-03', 'male', '416', '09162841625', ''),
(9, 'U251103-002', 'Manguiat', 'Deanna', NULL, 'Salas', '', '2005-05-02', 'male', '416', '09938591694', ''),
(10, 'U251103-003', 'Manguiat', 'Kit', NULL, 'N', '', '2025-11-03', 'male', '416', '09162841625', ''),
(11, 'U251103-004', 'Manguiat', 'Kit', NULL, 'N', '', '2025-11-03', 'male', '416', '09938591694', ''),
(12, 'U251103-005', 'Manguiat', 'Kit', NULL, 'N', '', '2025-11-03', 'male', '416', '09938591694', ''),
(13, 'U251107-001', 'Manguiat', 'Kit', NULL, 'N', '', '2025-11-07', 'male', '416', '09162841625', ''),
(14, 'U251107-002', 'Quak', 'Quak', NULL, 'V', '', '2025-11-01', 'female', '416', '09162841625', ''),
(15, 'U251109-005', 'Co.', 'Nina', NULL, 'Salas', 'Cardiology', '2004-06-10', 'female', '416', '09162841625', ''),
(16, 'U251110-006', 'Armor', 'AK', NULL, 'L', '', '2004-12-11', 'male', '416', '09162841625', '');

-- --------------------------------------------------------

--
-- Table structure for table `tblnoappointment`
--

CREATE TABLE `tblnoappointment` (
  `id` int(11) NOT NULL,
  `doctor_id` varchar(30) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `reason` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblnoappointment`
--

INSERT INTO `tblnoappointment` (`id`, `doctor_id`, `date_start`, `date_end`, `reason`) VALUES
(1, 'U251107-001', '2025-11-12', '2025-11-12', 'Conference'),
(2, 'U251109-005', '2025-11-18', '2025-11-18', 'Personal Leave'),
(3, 'U251107-001', '2025-11-13', '2025-11-13', 'umay'),
(4, 'U251107-001', '2027-03-12', '2027-03-12', 'umay'),
(5, 'U251107-001', '2025-11-07', '2025-11-07', 'umay'),
(6, 'U251107-001', '2025-11-07', '2025-11-07', 'high fever'),
(7, 'U251107-001', '2025-11-06', '2025-11-06', 'high fever'),
(8, 'U251107-001', '2025-11-21', '2025-11-21', 'umay'),
(9, 'U251109-005', '2025-11-28', '2025-11-28', 'umay'),
(10, 'U251109-005', '2025-11-29', '2025-11-29', 'umay');

-- --------------------------------------------------------

--
-- Table structure for table `tblnoappointment_backup`
--

CREATE TABLE `tblnoappointment_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `doctor_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_start` date NOT NULL,
  `reason` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblnoappointment_backup`
--

INSERT INTO `tblnoappointment_backup` (`id`, `doctor_id`, `date_start`, `reason`) VALUES
(1, 'U251107-001', '2025-11-12', 'Conference'),
(2, 'U251109-005', '2025-11-18', 'Personal Leave');

-- --------------------------------------------------------

--
-- Table structure for table `tblschedule`
--

CREATE TABLE `tblschedule` (
  `id` int(11) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `day` int(11) NOT NULL,
  `time` time NOT NULL,
  `time2` time NOT NULL,
  `max_appointment` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblschedule`
--

INSERT INTO `tblschedule` (`id`, `user_id`, `day`, `time`, `time2`, `max_appointment`) VALUES
(3, 'U251109-005', 6, '07:28:00', '17:31:00', 10),
(4, 'U251109-005', 7, '08:06:00', '23:00:00', 5),
(5, 'U251109-005', 5, '04:30:00', '18:50:00', 10),
(8, 'U251107-001', 5, '20:00:00', '17:00:00', 10),
(17, 'U251107-001', 6, '08:00:00', '17:00:00', 10);

-- --------------------------------------------------------

--
-- Table structure for table `tblspecialization`
--

CREATE TABLE `tblspecialization` (
  `id` int(11) NOT NULL,
  `specialization` varchar(30) NOT NULL
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
  `id` int(11) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(10) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbluser`
--

INSERT INTO `tbluser` (`id`, `user_id`, `user_name`, `password`, `user_type`, `status`) VALUES
(3, 'user0001', 'admin', '$2y$12$vgKSVhXG6nNZzQ.Vmgfk2.5YkNrxnqVURZ.UiaGr2tYoopZkArswS', 'admin', 1),
(15, 'U251103-005', 'admin123', 'admin12345678', 'user', 1),
(16, 'U251107-001', 'doc1', '12345678', 'doctor', 1),
(17, 'U251107-002', 'doc2', '12345678', 'doctor', 1),
(18, 'U251109-005', 'Nina', '$2y$12$LryAZKaSI8/vgvhOx5nqHecR/nWctbnlLITsGpoI7owb1cozcdGe2', 'doctor', 1),
(19, 'U251110-006', 'ak123', '$2y$12$gTJ7JHzzozzTqrK8ZhL9hua1tmlKdlTDMXyTXb.MnYSq.fiLj7bFC', 'user', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_health_profile`
--

CREATE TABLE `tbl_health_profile` (
  `id` int(11) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `past_surgeries` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_health_profile`
--

INSERT INTO `tbl_health_profile` (`id`, `user_id`, `blood_type`, `height`, `weight`, `allergies`, `chronic_conditions`, `current_medications`, `past_surgeries`, `family_history`, `emergency_contact_name`, `emergency_contact_phone`, `updated_at`) VALUES
(1, 'U251110-006', 'A+', '169', '70', 'Nani', 'PWD', 'GG', 'NONE', 'NONE', '09162841625', 'N/A', '2025-11-22 03:04:33');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_medical_records`
--

CREATE TABLE `tbl_medical_records` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_medical_records`
--

INSERT INTO `tbl_medical_records` (`id`, `appointment_id`, `diagnosis`, `prescription`, `notes`, `created_at`) VALUES
(1, 5, 'Vital', 'Paracetamol', 'Examination', '2025-11-21 05:36:08'),
(2, 5, 'Check up', 'Gamot', 'GG', '2025-11-21 07:36:32'),
(3, 6, 'Check up', 'Gamot', 'GG', '2025-11-22 12:33:53');

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
-- Indexes for table `tbl_health_profile`
--
ALTER TABLE `tbl_health_profile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_medical_records`
--
ALTER TABLE `tbl_medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblappointment`
--
ALTER TABLE `tblappointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tblinfo`
--
ALTER TABLE `tblinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tblnoappointment`
--
ALTER TABLE `tblnoappointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tblschedule`
--
ALTER TABLE `tblschedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tblspecialization`
--
ALTER TABLE `tblspecialization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `tbl_health_profile`
--
ALTER TABLE `tbl_health_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_medical_records`
--
ALTER TABLE `tbl_medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblappointment`
--
ALTER TABLE `tblappointment`
  ADD CONSTRAINT `tblappointment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`);

--
-- Constraints for table `tblnoappointment`
--
ALTER TABLE `tblnoappointment`
  ADD CONSTRAINT `tblnoappointment_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `tblinfo` (`user_id`);

--
-- Constraints for table `tblschedule`
--
ALTER TABLE `tblschedule`
  ADD CONSTRAINT `tblschedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`);

--
-- Constraints for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD CONSTRAINT `user_details` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`);

--
-- Constraints for table `tbl_health_profile`
--
ALTER TABLE `tbl_health_profile`
  ADD CONSTRAINT `fk_health_user` FOREIGN KEY (`user_id`) REFERENCES `tblinfo` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_medical_records`
--
ALTER TABLE `tbl_medical_records`
  ADD CONSTRAINT `tbl_medical_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `tblappointment` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
