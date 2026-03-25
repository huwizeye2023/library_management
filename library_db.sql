-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 09:31 AM
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
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-23 14:04:40');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(100) NOT NULL,
  `year_published` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available_quantity` int(11) DEFAULT 1,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `year_published`, `quantity`, `available_quantity`, `status`, `content`, `created_at`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', 1925, 5, 5, 'active', 'Full book content for online reading...', '2026-03-23 14:04:40'),
(2, 'To Kill a Mockingbird', 'Harper Lee', 1960, 3, 3, 'active', 'Full book content for online reading...', '2026-03-23 14:04:40'),
(3, '1984', 'George Orwell', 1949, 4, 3, 'active', 'Full book content for online reading...', '2026-03-23 14:04:40'),
(4, 'Pride and Prejudice', 'Jane Austen', 1813, 3, 3, 'active', 'Full book content for online reading...', '2026-03-23 14:04:40'),
(5, 'The Catcher in the Rye', 'J.D. Salinger', 1951, 2, 2, 'active', 'Full book content for online reading...', '2026-03-23 14:04:40'),
(6, 'math', 'kimi', 1982, 14, 14, 'active', NULL, '2026-03-23 22:57:16'),
(7, 'biology', 'alain', 2020, 4, 4, 'active', NULL, '2026-03-24 08:47:21'),
(8, 'how to winn frend and influence people', 'jhno', 1978, 1, 1, 'active', NULL, '2026-03-24 08:59:57'),
(9, 'chemistry', 'joh', 2023, 12, 12, 'active', NULL, '2026-03-24 09:23:35'),
(10, 'ikinyarwanda', 'rumaga', 2026, 1, 1, 'active', NULL, '2026-03-24 09:27:26'),
(11, 'kinyamateka', 'nyirurwanda', 2022, 1, 1, 'active', NULL, '2026-03-24 09:40:55'),
(12, 'kinyamateka', 'rukundo', 2020, 1, 1, 'active', NULL, '2026-03-24 10:00:47'),
(13, 'statistical mk', 'esta', 2020, 1, 1, 'active', NULL, '2026-03-24 10:06:27');

-- --------------------------------------------------------

--
-- Table structure for table `borrow`
--

CREATE TABLE `borrow` (
  `borrow_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `librarian_id` int(11) DEFAULT NULL,
  `borrow_date` date NOT NULL,
  `return_date` date NOT NULL,
  `actual_return_date` date DEFAULT NULL,
  `status` enum('pending','approved','denied','borrowed','returned','overdue') DEFAULT 'pending',
  `request_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow`
--

INSERT INTO `borrow` (`borrow_id`, `member_id`, `book_id`, `librarian_id`, `borrow_date`, `return_date`, `actual_return_date`, `status`, `request_notes`, `created_at`) VALUES
(1, 1, 3, NULL, '2026-12-23', '2026-12-24', NULL, 'pending', 'thanks', '2026-03-23 14:13:08'),
(2, 5, 3, NULL, '2026-12-22', '2027-12-01', NULL, 'pending', 'request', '2026-03-23 14:29:52'),
(3, 6, 4, 2, '0000-00-00', '2026-12-11', '2026-03-24', 'returned', 'reques', '2026-03-23 15:08:53'),
(4, 7, 3, 2, '2026-12-31', '2027-11-22', '2026-03-24', 'returned', 'thanks', '2026-03-23 18:30:45'),
(5, 4, 3, 1, '2026-11-01', '2026-11-12', NULL, 'borrowed', 'tkanks', '2026-03-24 07:48:58'),
(6, 10, 4, 1, '2026-03-25', '2026-04-05', '2026-03-24', 'returned', 'thank you', '2026-03-24 08:37:25');

-- --------------------------------------------------------

--
-- Table structure for table `librarians`
--

CREATE TABLE `librarians` (
  `librarian_id` int(11) NOT NULL,
  `names` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `librarians`
--

INSERT INTO `librarians` (`librarian_id`, `names`, `telephone`, `email`, `password`, `status`, `created_at`) VALUES
(1, 'John Librarian', '1234567890', 'librarian@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2026-03-23 14:04:40'),
(2, 'uwizeyimana honore', '0788899449', 'huwizeye202@gmail.com', '$2y$10$Vry549/dBwerO1a8QDpTXuSTaGnDPQGsChbblTFxj7jLmMs6fcl2K', 'active', '2026-03-23 22:49:51');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `name`, `email`, `phone`, `password`, `status`, `created_at`) VALUES
(1, 'bora', 'bora@gmail.com', '+25078444443', '$2y$10$OACPK.PSKZakhtq4KHoDyuRMTHKpuCI/M7jmFUZcaplWjBcfGg4L2', 'active', '2026-03-23 14:08:32'),
(2, 'john', 'herena12@gmail.com', '+25078444443', '$2y$10$xtAb56oJA0FTpAisYK1ma.CeIdCER9VHL/Eyc4AKs8kI4iJmKpJaa', 'active', '2026-03-23 14:16:06'),
(3, 'jjjjjjjjjjj', 'cyusa@12gmail.com', '+2507844444', '$2y$10$f.hgoCUWh/kxJjisPaHNnell0ElwyQR9JrG8ejdR1..Awf.c/5oya', 'active', '2026-03-23 14:18:07'),
(4, 'honore UWIZEYIMANA', 'huwizeye202@gmail.com', '0733177101', '$2y$10$rjKoIGrVbO0dBAEJHOwlRubblLkp3B4XZiHDsrMk1jP/p2JcYq4Tu', 'active', '2026-03-23 14:21:31'),
(5, 'cyubahiro', 'cyubahiro12@gmail.com', '0788563421', '$2y$10$A1Vre5u.EH2X4dox4yXGMO85yZbvVldlDfWRbXnMmVfCceZih45oG', 'active', '2026-03-23 14:27:59'),
(6, 'gihozo', 'huwizeye2023@gmail.com', '0789999999', '$2y$10$bp./56lZVUy4MEonZHBuneG3531zC87zcY5AV5SLOohcwLuBIoSVG', 'active', '2026-03-23 15:05:32'),
(7, 'hunga', 'hunga23@gmail.com', '0788101234', '$2y$10$9BVQOMH2S3RCWzQF8nrw7e2KVWOxsUe1Ia1iddFUA1wnxve0XANKi', 'active', '2026-03-23 17:40:31'),
(8, 'longine', 'longine@gmail.com', '0794567321', '$2y$10$1y4CVjjWhRrPwLCCcRVwPOtaeLIJHL0NxlRf4.XeVecGTRAIrDrvO', 'active', '2026-03-23 18:31:57'),
(10, 'BORA Aisha', 'aishabora619@gmail.com', '0782741137', '$2y$10$e4cRQwuCR9uyarguKJpBjOqqRJ4ZlVZsT6ToAiCyQRHcSW3F3ZveC', 'inactive', '2026-03-24 08:33:45'),
(11, 'jean', 'ishimwejclaude79@gmail.com', '0721265823', '$2y$10$8Df399y7G0eB1RcIcrdg2u61NPmO9OP/48kW6ifLaqWhPhcrVYiJ.', 'inactive', '2026-03-24 08:39:45'),
(12, 'Aisha BORA', 'boraaisha@gmail.com', '0792034794', '$2y$10$aeF7GfOFp8bQjS8u.UKh2OeTCQpFe2r1YYRWi517U42DmV11GxT3q', 'active', '2026-03-24 10:16:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `borrow`
--
ALTER TABLE `borrow`
  ADD PRIMARY KEY (`borrow_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `librarian_id` (`librarian_id`);

--
-- Indexes for table `librarians`
--
ALTER TABLE `librarians`
  ADD PRIMARY KEY (`librarian_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `borrow`
--
ALTER TABLE `borrow`
  MODIFY `borrow_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `librarians`
--
ALTER TABLE `librarians`
  MODIFY `librarian_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow`
--
ALTER TABLE `borrow`
  ADD CONSTRAINT `borrow_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`),
  ADD CONSTRAINT `borrow_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `borrow_ibfk_3` FOREIGN KEY (`librarian_id`) REFERENCES `librarians` (`librarian_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
