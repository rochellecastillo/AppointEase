-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 11:20 AM
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
-- Table structure for table `tblactivity_log`
--

CREATE TABLE `tblactivity_log` (
  `id` int(11) NOT NULL,
  `user_id` varchar(30) DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(6, '2025-11-22', '09:28:00', 'U251110-006', 'U251109-005', 3, 0, NULL),
(13, '2025-12-15', '09:00:00', 'U251110-006', 'U251109-005', 3, 0, NULL),
(14, '2025-12-01', '09:00:00', 'U251110-006', 'U251109-005', 0, 0, NULL),
(16, '2025-12-01', '09:00:00', 'U251110-006', 'DOC-1764241542-65', 3, 0, NULL),
(17, '2025-12-12', '08:00:00', 'U251110-006', 'DOC-1764252203-76', 2, 0, NULL),
(18, '2025-12-06', '09:28:00', 'U251110-006', 'U251109-005', 3, 0, NULL),
(19, '2025-12-18', '14:00:00', 'U251110-006', 'U251107-002', 0, 0, NULL),
(20, '2025-12-15', '10:00:00', 'U251127-001', 'U251109-005', 1, 0, NULL),
(21, '2025-12-15', '11:00:00', 'PAT-1764269096-76', 'U251109-005', 1, 0, NULL),
(22, '2025-12-06', '07:28:00', 'PAT-1764269096-76', 'U251109-005', 3, 0, NULL),
(23, '2025-12-01', '08:00:00', 'U251110-006', 'DOC-1764269655-56', 1, 0, NULL),
(24, '2025-12-13', '09:28:00', 'U251110-006', 'U251109-005', 0, 0, NULL),
(26, '2025-12-03', '09:00:00', 'PAT-1764269096-76', 'U251109-005', 1, 0, NULL),
(27, '2025-12-17', '09:00:00', 'U251110-006', 'U251109-005', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblinfo`
--

CREATE TABLE `tblinfo` (
  `id` int(11) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `last_name` varchar(40) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(40) NOT NULL,
  `specialization` varchar(30) NOT NULL,
  `bdate` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblinfo`
--

INSERT INTO `tblinfo` (`id`, `user_id`, `last_name`, `first_name`, `middle_name`, `specialization`, `bdate`, `gender`, `address`, `contact`, `image`) VALUES
(1, 'user0001', 'dela cruz', 'juan', 'cruz', '', '2000-01-01', 'male', 'rosario batangas', '09913575449', ''),
(14, 'U251107-002', 'Quak', 'Quak', 'V', '', '2025-11-01', 'female', '416', '09162841625', ''),
(15, 'U251109-005', 'Col.', 'Nina', 'Salas', 'Cardiology', '2004-06-10', 'Female', '416', '09162841625', ''),
(16, 'U251110-006', 'Armor', 'AK', 'M', '', '2004-12-11', 'Male', '416', '09162841625', ''),
(23, 'DOC-1764241542-65', 'Craig', 'Marduk', 'K', 'Psychiatrist', '2000-06-08', 'Male', '416', '09162841625', NULL),
(24, 'PAT-1764246251-74', 'El', 'King', 'V', '', '1997-10-27', 'Male', '416', '09162841625', NULL),
(25, 'DOC-1764252203-76', 'Fox', 'Steve', 'L', 'Dermatologist', '1990-07-25', 'Male', '416', '09162841625', NULL),
(26, 'U251127-001', 'Manguiat', 'Kit', 'N', '', '2004-11-08', 'Male', '416', '09162841625', ''),
(27, 'U251127-002', 'Phoenix', 'Paul', 'K', '', '1999-07-07', 'Male', '416', '09162841625', ''),
(28, 'DOC-1764266367-66', 'Gordo', 'Eddy', 'V', 'Cardiologist', '2000-01-01', 'Male', '416', '09162841625', NULL),
(29, 'PAT-1764269096-76', 'Bot', 'Alissa', 'K', '', '2008-07-16', 'Female', '416', '09162841625', NULL),
(30, 'DOC-1764269655-56', 'Six', 'Jack', 'B', 'Orthopedic', '1974-03-13', 'Male', '416', '09162841625', NULL);

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
(10, 'U251109-005', '2025-11-29', '2025-11-29', 'umay'),
(11, 'U251107-001', '2025-12-08', '2025-12-08', 'umay'),
(12, 'U251107-001', '2025-12-12', '2025-12-12', 'high fever'),
(13, 'U251109-005', '2025-12-08', '2025-12-08', 'Ayaw ko na'),
(14, 'U251107-002', '2025-12-02', '2025-12-02', 'Ayaw ko na'),
(15, 'DOC-1764269655-56', '2025-12-05', '2025-12-05', 'LALA');

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
(8, 'U251107-001', 5, '20:00:00', '17:00:00', 10),
(17, 'U251107-001', 6, '08:00:00', '17:00:00', 10),
(18, 'U251109-005', 1, '09:00:00', '17:00:00', 10),
(19, 'U251107-002', 2, '08:00:00', '17:00:00', 10),
(20, 'DOC-1764241542-65', 1, '09:00:00', '17:00:00', 5),
(22, 'DOC-1764269655-56', 5, '08:00:00', '17:00:00', 10),
(23, 'DOC-1764252203-76', 5, '08:00:00', '17:00:00', 10),
(25, 'DOC-1764269655-56', 1, '08:00:00', '17:00:00', 10),
(32, 'U251107-002', 6, '08:00:00', '15:00:00', 14),
(35, 'U251107-002', 4, '08:00:00', '15:00:00', 14),
(37, 'U251109-005', 3, '09:00:00', '15:00:00', 12);

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
(18, 'U251109-005', 'Nina', '$2y$12$LryAZKaSI8/vgvhOx5nqHecR/nWctbnlLITsGpoI7owb1cozcdGe2', 'doctor', 1),
(19, 'U251110-006', 'ak123', '$2y$12$gTJ7JHzzozzTqrK8ZhL9hua1tmlKdlTDMXyTXb.MnYSq.fiLj7bFC', 'user', 1),
(23, 'DOC-1764241542-65', 'Marduk', '$2y$10$7VJQuN1xNoL35ZXEhm1KR.GEx6GPO5dvM3K5tpl5CUTewRO6NH8.a', 'doctor', 1),
(24, 'PAT-1764246251-74', 'king', '$2y$10$J0p.OR3nebztny/3slNs/e2QcSyGH2EqQf0scSu9GkFyTt.hdIJQO', 'user', 1),
(25, 'DOC-1764252203-76', 'steve', '$2y$10$ii9fEaqeFNNsmIfXU44YteNT/l2N.MlYBFAu9J2Fyh0YN9XTUd8S.', 'doctor', 1),
(26, 'U251127-001', 'Kit123', '$2y$10$KZrWKyhmgx7VdPYs4imPGeLdHWRcFokRyzSARC.2qMWG2zrWrUZYe', 'user', 1),
(27, 'U251127-002', 'Paul', '$2y$10$.tSDSo29DlqJl3Iaw82ZIuU0wsqgnAytEh/f.U1twFjk9m7ZRCh3q', 'user', 1),
(28, 'DOC-1764266367-66', 'Eddy', '$2y$10$bnd8A04OXhZE6GaVth8GhOjmztloAMBf.JurQaPceV9ZsnBpDIEpO', 'doctor', 1),
(29, 'PAT-1764269096-76', 'Alissa', '$2y$10$NFlTQR.KCntxR4gOcYoUU.Kdya/Y0PzYkpUYKkN5DESl8hi9pAu32', 'user', 1),
(30, 'DOC-1764269655-56', 'Jack', '$2y$10$U2VSwVsBetTDBp2VkOszs.PKnPmVGIZrnqjJ8vqznpZTtBktEvWgK', 'doctor', 1);

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
(1, 'U251110-006', 'O+', '169', '73', 'Nani', 'PWD', 'GG', 'NONE', 'NONE', 'Paps', 'N/a', '2025-11-27 20:30:10'),
(3, 'U251103-005', 'A-', '165', '45', 'LALALAL', 'WSL', 'GAMOT', 'NONE', 'NONE', '09162841625', 'N/A', '2025-11-27 10:24:35'),
(4, 'PAT-1764246251-74', 'B+', '150', '45', 'Test', 'Test', 'Test', 'Test', 'Test', 'Mams', 'N/A', '2025-11-29 07:32:53'),
(5, 'PAT-1764269096-76', 'B-', '170', '60', 'N/A', 'None', 'None', 'None', 'None', 'Paps', '09162841625', '2025-11-29 20:19:35'),
(6, 'U251127-001', 'B+', '179', '70', 'Test', 'Test', 'Test', 'Test', 'Test', 'Paps', '09162841625', '2025-11-29 19:04:32'),
(7, 'U251127-002', '', '', '', '', '', '', '', '', '', '', '2025-12-01 17:17:46');

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
(3, 6, 'Check up', 'Gamot', 'GG', '2025-11-22 12:33:53'),
(4, 13, 'Check up', 'Paracetamol', 'Umay', '2025-11-28 18:26:10'),
(5, 18, 'Test', 'Test', 'Test', '2025-11-29 08:18:22'),
(6, 22, 'Test', 'Test', 'Test', '2025-11-29 20:15:23'),
(7, 16, 'Check up', 'Test', 'Test', '2025-12-01 08:29:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblactivity_log`
--
ALTER TABLE `tblactivity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- AUTO_INCREMENT for table `tblactivity_log`
--
ALTER TABLE `tblactivity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=243;

--
-- AUTO_INCREMENT for table `tblappointment`
--
ALTER TABLE `tblappointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tblinfo`
--
ALTER TABLE `tblinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tblnoappointment`
--
ALTER TABLE `tblnoappointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tblschedule`
--
ALTER TABLE `tblschedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `tblspecialization`
--
ALTER TABLE `tblspecialization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tbl_health_profile`
--
ALTER TABLE `tbl_health_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_medical_records`
--
ALTER TABLE `tbl_medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
