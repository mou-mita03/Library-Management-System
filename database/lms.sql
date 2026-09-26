-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2026 at 07:40 PM
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
-- Database: `lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(250) NOT NULL,
  `mobile` bigint(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `mobile`, `profile_image`) VALUES
(1, 'admin', 'admin@gmail.com', 'MuraD1097', 1148458757, 'admin_1_1789713480.png');

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `author_id` int(11) NOT NULL,
  `author_name` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`author_id`, `author_name`) VALUES
(101, 'M D Guptaa'),
(102, 'Chetan Bhagat'),
(103, 'Munshi Prem Chand'),
(105, 'murad'),
(106, 'robindronath thakur'),
(107, 'kingshuk dhor');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `book_name` varchar(250) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `pending_author_name` varchar(250) DEFAULT NULL,
  `cat_id` int(11) DEFAULT NULL,
  `pending_category_name` varchar(100) DEFAULT NULL,
  `book_no` int(11) NOT NULL,
  `book_price` int(11) NOT NULL,
  `book_file` varchar(255) DEFAULT NULL,
  `pdf_comment` varchar(500) DEFAULT NULL,
  `book_cover` varchar(255) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `added_by_type` varchar(20) NOT NULL DEFAULT 'Admin',
  `approval_status` varchar(20) NOT NULL DEFAULT 'Approved',
  `rejection_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `book_name`, `author_id`, `pending_author_name`, `cat_id`, `pending_category_name`, `book_no`, `book_price`, `book_file`, `pdf_comment`, `book_cover`, `added_by`, `added_by_type`, `approval_status`, `rejection_reason`) VALUES
(1, 'Software Engineering', 101, NULL, 1, NULL, 4518, 270, '1789706895_Handbook-of-Software-Engineering-Methods-1774561674.pdf', NULL, NULL, NULL, 'Admin', 'Approved', NULL),
(2, 'Data Structure', 102, NULL, 2, NULL, 6541, 300, '1789706866_algorithms_and_data_structures__1_.pdf', NULL, NULL, NULL, 'Admin', 'Approved', NULL),
(3, 'na bolte shikun', 103, NULL, 5, NULL, 1234, 400, '1789707277____________________________________.pdf', NULL, NULL, NULL, 'Admin', 'Approved', NULL),
(5, 'abultabul', 105, NULL, 5, NULL, 2, 200, '1789722929_6aad01315480e_Md.Murad_Hasan.pdf', NULL, NULL, 1, 'Staff', 'Approved', NULL),
(6, 'seser kobita', 106, NULL, 7, NULL, 5, 430, '1789729158_6aad198641ec6_________________________________Know________________.pdf', NULL, '1789838416_6aaec4503b448_cover.jpeg', 2, 'Staff', 'Approved', NULL),
(7, 'general math', 103, NULL, 6, NULL, 34, 345, '1789835056_6aaeb7307da6f_Who_Moved_My_Cheese_by_Spencer_Johnson.pdf', NULL, '1789837210_6aaebf9a56e39_cover.jpg', NULL, 'Admin', 'Approved', NULL),
(8, 'data communication', 102, NULL, 1, NULL, 1000, 876, '1789836712_6aaebda8d3f1c_The_5_Second_Rule_by_Mel_Robbins.pdf', NULL, '1789836712_6aaebda8d415b_cover.jpg', NULL, 'Admin', 'Approved', NULL),
(9, 'cn', 107, NULL, 8, NULL, 567, 798, '1789837966_6aaec28e05825_How-to-Program-in-C-With-100-Examples-Volume-I.pdf', NULL, '1789837966_6aaec28e05a34_cover.png', 1, 'Staff', 'Approved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `cat_id` int(11) NOT NULL,
  `cat_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`cat_id`, `cat_name`) VALUES
(1, 'Computer Science Engineering'),
(2, 'Novel'),
(4, 'Motivational'),
(5, 'Story'),
(6, 'math'),
(7, 'oponnas'),
(8, 'networking');

-- --------------------------------------------------------

--
-- Table structure for table `issued_books`
--

CREATE TABLE `issued_books` (
  `s_no` int(11) NOT NULL,
  `book_no` int(11) NOT NULL,
  `book_name` varchar(200) NOT NULL,
  `book_author` varchar(200) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `issue_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issued_books`
--

INSERT INTO `issued_books` (`s_no`, `book_no`, `book_name`, `book_author`, `student_id`, `status`, `issue_date`) VALUES
(1, 6541, 'Data Structure', 'D S Gupta', 4, 1, '2025-01-01 00:00:00'),
(2, 1234, 'na bolte shikun', 'Munshi Prem Chand', 2, 1, '2026-09-18 08:13:28'),
(3, 4518, 'Software Engineering', 'M D Guptaa', 2, 0, '2026-09-18 08:15:23');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_amount` int(11) NOT NULL,
  `payment_method` varchar(30) NOT NULL,
  `payment_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `order_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `delivery_address` varchar(250) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `book_id`, `quantity`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `delivery_address`, `mobile`, `order_date`) VALUES
(1, 2, 3, 1, 400, 'Online Payment', 'Pending', 'Confirmed', 'Premier University Chittagong\r\nKaokhali,Rangamati', '', '2026-09-18 14:07:22'),
(2, 2, 3, 1, 400, 'Cash on Delivery', 'Pending', 'Pending', 'Premier University Chittagong\r\nKaokhali,Rangamati', '1870200938', '2026-09-18 14:13:29'),
(3, 2, 3, 1, 400, 'Cash on Delivery', 'Pending', 'Pending', 'Premier University Chittagong\r\nKaokhali,Rangamati', '1870200938', '2026-09-18 14:25:19'),
(4, 2, 2, 1, 300, 'Cash on Delivery', 'Pending', 'Pending', 'Premier University Chittagong\r\nKaokhali,Rangamati', '1870200938', '2026-09-18 14:30:42'),
(5, 2, 1, 1, 270, 'Online Payment', 'Paid', 'Delivered', 'Premier University Chittagong\r\nKaokhali,Rangamati', '1870200938', '2026-09-18 14:38:35'),
(6, 2, 2, 1, 300, 'Online Payment', 'Paid', 'Delivered', 'Premier University Chittagong\r\nKaokhali,Rangamati', '1870200938', '2026-09-18 14:45:02');

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(250) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`id`, `name`, `email`, `password`, `mobile`, `profile_image`) VALUES
(1, 'Staff', 'staff@gmail.com', 'staff@1234', '01700000000', '1789725742_6aad0c2eeab5c.jpg'),
(2, 'srabon', 'sdn@gmail.com', '123456', '12345678901', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `mobile` bigint(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `address` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `mobile`, `profile_image`, `address`) VALUES
(1, 'user', 'user@gmail.com', 'user@1234', 2147483644, NULL, 'XYZ Colony, PQR Nagar, Jaipur'),
(2, '1097 Murad Hasan', 'muradhasan3180@gmail.com', '123456', 1870200938, '1789726703_6aad0fef94945.jpg', 'Premier University Chittagong\r\nKaokhali,Rangamati');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`author_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`cat_id`);

--
-- Indexes for table `issued_books`
--
ALTER TABLE `issued_books`
  ADD PRIMARY KEY (`s_no`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `author_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `cat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `issued_books`
--
ALTER TABLE `issued_books`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
