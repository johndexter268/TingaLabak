-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 26, 2025 at 02:32 PM
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
-- Database: `tingalabak`
--

-- --------------------------------------------------------

--
-- Table structure for table `brgy_officials`
--

CREATE TABLE `brgy_officials` (
  `official_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL,
  `date_inducted` date NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brgy_officials`
--

INSERT INTO `brgy_officials` (`official_id`, `name`, `position`, `date_inducted`, `contact_number`, `address`, `avatar`, `status`, `created_at`) VALUES
(1, 'Cynthia D. Cervantes', 'Barangay Captain', '2025-08-05', '09123456789', 'Tinga Labak', 'uploads/officials/68a7fee741ca5.png', 'Active', '2025-08-22 02:43:27'),
(4, 'Kevin Andrew T. Dionisio', 'Barangay Kagawad', '2025-08-05', '09123456789', 'Tinga Labak', 'uploads/officials/68a7fa8024401.jpg', 'Active', '2025-08-22 05:05:04'),
(5, 'Crisanto Pineda', 'Barangay Kagawad', '2025-08-05', '09123456789', 'Tinga Labak', 'uploads/officials/68a82a3d46e11.jpg', 'Active', '2025-08-22 08:28:45'),
(6, 'Roberto Salazar', 'Barangay Kagawad', '2025-08-05', '09123456789', 'Tinga Labak', 'uploads/officials/68a82a6238211.jpg', 'Active', '2025-08-22 08:29:22'),
(7, 'Diego Cruz', 'Barangay Treasurer', '2025-08-05', '09123456789', 'Tinga Labac', 'uploads/officials/68a82a85643d3.jpg', 'Active', '2025-08-22 08:29:57');

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `request_id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `dob` date NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `civil_status` varchar(100) NOT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `years_of_residency` int(11) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city_municipality` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `purok_sitio_street` varchar(150) NOT NULL,
  `subdivision` varchar(150) DEFAULT NULL,
  `house_number` varchar(50) DEFAULT NULL,
  `document_type` text NOT NULL,
  `purpose_of_request` text NOT NULL,
  `requesting_for_self` enum('Yes','No') NOT NULL,
  `proof_of_identity` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email_address` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `name`, `contact_number`, `email_address`, `subject`, `message`, `created_at`, `read_status`) VALUES
(1, 'Juan Dela Cruz', '09123456790', 'juan.delacruz@example.com', 'Garbage Collection', 'Schedule garbage collection for tomorrow.', '2025-08-22 10:42:00', 0),
(2, 'Ana Rodriguez', '09123456791', 'ana.rodriguez@example.com', 'Water Supply Issue', 'There’s no water supply in Purok 1.', '2025-08-22 10:42:00', 0),
(3, 'Carlos Mendoza', '09123456792', 'carlos.mendoza@example.com', 'Road Maintenance', 'Road needs repair near the market.', '2025-08-22 10:42:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`) VALUES
(9, 1, 'ebf8aba6d7807fe4ca55d2aa86b3ba413cac13af435a188d4febb9ff594fb072', '2025-08-23 13:34:27');

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `resident_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `classification` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`resident_id`, `name`, `sex`, `classification`, `contact_number`) VALUES
(1, 'Juan Dela Cruz', 'Male', 'Children', '09171234567'),
(2, 'Maria Santos', 'Female', 'Children', '09281234567'),
(3, 'Pedro Ramirez', 'Male', 'Youth', '09391234567'),
(4, 'Ana Lopez', 'Female', 'Youth', '09451234567'),
(5, 'Josefa Reyes', 'Female', 'Senior Citizen', '09561234567'),
(6, 'Ramon Garcia', 'Male', 'Senior Citizen', '09671234567'),
(7, 'Carmen Villanueva', 'Female', 'Indigent', '09781234567'),
(8, 'Ricardo Mendoza', 'Male', 'Persons With Disability', '09891234567'),
(9, 'Luisa Hernandez', 'Female', 'Not Classified', '09901234567'),
(10, 'Francisco Bautista', 'Male', 'Not Classified', '09181234567'),
(11, 'Diego Cruz', 'Male', 'Children', '09191230001'),
(12, 'Isabel Flores', 'Female', 'Children', '09291230002'),
(13, 'Miguel Torres', 'Male', 'Youth', '09391230003'),
(14, 'Sofia Gutierrez', 'Female', 'Youth', '09491230004'),
(15, 'Crisanto Pineda', 'Male', 'Senior Citizen', '09591230005'),
(16, 'Teresa Villamor', 'Female', 'Senior Citizen', '09691230006'),
(17, 'Andres Navarro', 'Male', 'Indigent', '09791230007'),
(18, 'Elena Robles', 'Female', 'Persons With Disability', '09891230008'),
(19, 'Roberto Salazar', 'Male', 'Not Classified', '09991230009'),
(20, 'Gloria Castillo', 'Female', 'Not Classified', '09181230010');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(150) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `bio` text DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `name`, `position`, `verified`, `bio`, `date_created`, `remember_token`) VALUES
(1, 'jdlanot.2003@gmail.com', '$2y$10$1QuccKei2qRcYxB8sgl9.uD1I91/oBMPTsaNT8Pd4003gvGpJIING', 'Quincy Evangelista', 'Secretary', 1, 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', '2025-08-21 22:15:27', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brgy_officials`
--
ALTER TABLE `brgy_officials`
  ADD PRIMARY KEY (`official_id`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`resident_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brgy_officials`
--
ALTER TABLE `brgy_officials`
  MODIFY `official_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `resident_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
