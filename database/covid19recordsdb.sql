-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2023 at 04:55 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `covid19recordsdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `temp` decimal(5,2) NOT NULL,
  `diagnosed` enum('YES','NO') NOT NULL,
  `encountered` enum('YES','NO') NOT NULL,
  `vaccinated` enum('YES','NO') NOT NULL,
  `nationality` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `email`, `full_name`, `gender`, `age`, `temp`, `diagnosed`, `encountered`, `vaccinated`, `nationality`) VALUES
(16, 'john.doe@example.com', 'John Doe', 'Male', 25, 37.00, 'NO', 'NO', 'YES', 'United States'),
(17, 'jane.smith@example.com', 'Jane Smith', 'Female', 30, 36.50, 'NO', 'YES', 'NO', 'Canada'),
(18, 'michael.johnson@example.com', 'Michael Johnson', 'Male', 40, 38.20, 'YES', 'YES', 'YES', 'Australia'),
(19, 'emily.brown@example.com', 'Emily Brown', 'Female', 28, 36.90, 'NO', 'NO', 'NO', 'United Kingdom'),
(20, 'robert.williams@example.com', 'Robert Williams', 'Male', 35, 37.30, 'YES', 'YES', 'YES', 'Germany'),
(22, 'daniel.taylor@example.com', 'Daniel Taylor', 'Male', 33, 37.10, 'NO', 'YES', 'NO', 'France'),
(23, 'jessica.garcia@example.com', 'Jessica Garcia', 'Female', 29, 36.80, 'YES', 'YES', 'YES', 'Mexico'),
(24, 'william.anderson@example.com', 'William Anderson', 'Male', 50, 37.50, 'NO', 'NO', 'NO', 'Canada'),
(25, 'olivia.hernandez@example.com', 'Olivia Hernandez', 'Female', 27, 36.60, 'NO', 'YES', 'NO', 'United States');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_users` int(11) NOT NULL,
  `uid_users` tinytext NOT NULL,
  `email_users` tinytext NOT NULL,
  `pwd_users` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_users`, `uid_users`, `email_users`, `pwd_users`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$lpFoRw/Fw2spqmjOvH5c0eTKyWPVu4hLtR9vC8XlVnoHxkpR22Tky'),
(2, 'test3', 'test3@example.com', '$2y$10$jOl2sVgJIPvDBpJfZrNlUe2Thp3x64SVjEBndDCsuOvHZlXns/KNa'),
(3, 'test4', 'test4@example.com', '$2y$10$C8eY4G018tdjn20dBvuHAeoOWiTX8BVEJZyG7BaIpr13jYCsCPgTu'),
(4, 'test5', 'test5@email.com', '$2y$10$/Z0Ahh55rAXN5CnJ1zaMQOAPUbDQbzgjPAZE7qY.cBYn07XrBSszG'),
(5, 'admin99', 'admin99@exmple.com', '$2y$10$NW/QbweYKx1B5IKnIWCI/uu.Gslam1ftW5GDDswV5Dq9bQ1Fep3iS'),
(6, 'admin3123123', 'john.doe@example.com', '$2y$10$uXOJ7YE5EmSRBjeUbNUROunhnpL7CWBkHJ/qeWqLyiMDnkRzr3mJO');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_users`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_users` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
