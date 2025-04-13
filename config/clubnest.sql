-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2025 at 10:33 PM
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
-- Database: `clubnest`
--

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `meeting_schedule` varchar(100) DEFAULT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending',
  `proposed_by_user_id` int(11) DEFAULT NULL COMMENT 'User ID who submitted the request',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `description`, `category`, `meeting_schedule`, `status`, `proposed_by_user_id`, `created_at`) VALUES
(1, 'Computer Science Club', 'A club for computer science enthusiasts', 'Technology', 'Tuesdays at 5 PM', 'active', NULL, '2025-03-25 10:17:43'),
(2, 'Chess Club', 'Learn and play chess with other students', 'Recreation', 'Fridays at 3 PM', 'active', NULL, '2025-03-25 10:17:43'),
(3, 'Environmental Club', 'Working together for a sustainable campus', 'Social', 'Wednesdays at 6 PM', 'active', NULL, '2025-03-25 10:17:43'),
(4, 'microsoft club', 'yes yes very good club join now', 'computer sc', 'Tuesday 5pm', 'pending', 1, '2025-04-06 20:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `club_members`
--

CREATE TABLE `club_members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `role` enum('member','leader','pending') NOT NULL DEFAULT 'pending',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_members`
--

INSERT INTO `club_members` (`id`, `user_id`, `club_id`, `role`, `joined_at`) VALUES
(1, 2, 1, 'leader', '2025-03-25 10:17:43'),
(2, 2, 2, 'leader', '2025-03-25 10:17:43'),
(3, 3, 3, 'member', '2025-03-25 10:17:43'),
(29, 4, 2, 'pending', '2025-04-13 20:06:34');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `event_end_date` datetime DEFAULT NULL COMMENT 'Optional: Date and time the event ends',
  `location` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending',
  `poster_image_path` varchar(255) DEFAULT NULL COMMENT 'Path to event poster image',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `club_id`, `name`, `description`, `event_date`, `event_end_date`, `location`, `status`, `poster_image_path`, `created_at`, `created_by`) VALUES
(2, 2, 'qsfsqf', 'qdsqsdqsdqsdqsd', '2025-04-07 04:28:00', '2025-04-07 15:26:00', 'saleqsfqsfqs', 'active', '/cm/uploads/event_posters/event_67f31be802fd27.42977506.png', '2025-04-07 00:27:20', 1),
(3, 1, 'test', 'qsfqsfqsfqsfojsifjqslkfjq\r\nqsfqlskfjqlqsfqsfqsfqsfojsifjqslkfjq\r\nqsfqlskfjqlqsfqsfqsfqsfojsifjqslkfjq\r\nqsfqlskfjqlqsfqsfqsfqsfojsifjqslkfjq\r\nqsfqlskfjqlqsfqsfqsfqsfojsifjqslkfjq\r\nqsfqlskfjql', '2025-04-13 13:41:00', '2025-04-14 17:44:00', 'sale 17', 'active', '/cm/uploads/event_posters/event_67f31f58aa2fb5.24921088.png', '2025-04-07 00:42:00', 1),
(4, 2, 'CMC', 'QSDQSFSQFQSFQSFQSFQSFQSFQSF', '2025-04-08 03:18:00', '2025-04-09 03:18:00', 'EPI', 'active', '/cm/uploads/event_posters/event_67f33630b74af2.02937043.jpg', '2025-04-07 02:19:28', 2),
(5, 2, 'CMC', 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffqsfqfqsfqsf', '2025-04-10 03:28:00', '2025-04-11 03:28:00', 'sale 17', 'rejected', '/cm/uploads/event_posters/event_67f338433138f0.13613334.jpg', '2025-04-07 02:28:19', 2),
(6, 2, 'hachathon', 'BLABLABLABLABLABLABALBALABALB   AKABKABKABKABBAKBAKAB', '2025-04-16 12:57:00', '2025-04-17 12:57:00', 'ihecc', 'rejected', '/cm/uploads/event_posters/event_67f3bdd8a86dd7.41879141.jpg', '2025-04-07 11:58:16', 2);

-- --------------------------------------------------------

--
-- Table structure for table `event_attendees`
--

CREATE TABLE `event_attendees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_attendees`
--

INSERT INTO `event_attendees` (`id`, `user_id`, `event_id`, `registered_at`) VALUES
(3, 1, 3, '2025-04-07 01:17:49');

-- --------------------------------------------------------

--
-- Table structure for table `event_comments`
--

CREATE TABLE `event_comments` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_comments`
--

INSERT INTO `event_comments` (`id`, `event_id`, `user_id`, `comment_text`, `created_at`) VALUES
(1, 3, 1, 'very good event 😊', '2025-04-07 01:25:40'),
(2, 4, 8, 'Yooo', '2025-04-11 21:38:40');

-- --------------------------------------------------------

--
-- Table structure for table `event_interest`
--

CREATE TABLE `event_interest` (
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_interest`
--

INSERT INTO `event_interest` (`user_id`, `event_id`, `marked_at`) VALUES
(1, 3, '2025-04-07 01:17:29'),
(8, 4, '2025-04-11 21:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `event_likes`
--

CREATE TABLE `event_likes` (
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `liked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_likes`
--

INSERT INTO `event_likes` (`user_id`, `event_id`, `liked_at`) VALUES
(1, 3, '2025-04-07 01:17:25'),
(1, 4, '2025-04-08 13:29:07'),
(2, 3, '2025-04-07 12:05:04'),
(4, 4, '2025-04-07 18:15:50'),
(5, 4, '2025-04-07 13:03:46'),
(8, 4, '2025-04-11 21:38:20');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_content`, `sent_at`, `is_read`) VALUES
(1, 2, 4, 'hello :3', '2025-04-06 17:14:40', 1),
(2, 2, 4, 'hello :3', '2025-04-06 17:15:53', 1),
(3, 2, 4, 'hello', '2025-04-06 17:16:04', 1),
(4, 4, 2, 'slm', '2025-04-06 17:22:50', 1),
(5, 2, 4, 'slm2', '2025-04-06 17:24:09', 1),
(6, 2, 4, 'slm3', '2025-04-06 17:28:01', 1),
(7, 2, 4, 'sqffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', '2025-04-06 17:28:31', 1),
(8, 4, 2, 'wnk cv ?', '2025-04-06 17:46:42', 1),
(9, 2, 4, 'test', '2025-04-06 17:50:17', 1),
(10, 4, 2, 'oui oui', '2025-04-06 18:17:36', 1),
(11, 5, 4, 'hola gaberilelle', '2025-04-07 13:01:25', 1),
(12, 4, 5, 'Wnk lbs ?', '2025-04-07 13:01:36', 1),
(13, 4, 5, 'Oui', '2025-04-07 13:02:27', 1),
(14, 5, 4, 'geleg', '2025-04-07 13:03:14', 1),
(15, 5, 4, 'wenek', '2025-04-07 13:03:21', 1),
(16, 4, 5, 'hani wlh', '2025-04-07 18:16:13', 0),
(17, 6, 4, 'hello', '2025-04-08 07:54:07', 1),
(18, 7, 4, 'dfgdf', '2025-04-09 14:27:32', 1),
(19, 4, 7, 'salem', '2025-04-10 09:38:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-05 20:27:12'),
(2, 4, 'Club Left', 'You have left Chess Club', 0, '2025-04-05 20:27:24'),
(3, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-05 20:27:25'),
(4, 4, 'Club Left', 'You have left Chess Club', 0, '2025-04-05 20:27:26'),
(5, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-05 20:27:27'),
(6, 4, 'Club Left', 'You have left Chess Club', 0, '2025-04-05 20:27:28'),
(7, 1, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-06 14:51:14'),
(8, 1, 'Club Left', 'You have left Chess Club', 0, '2025-04-06 14:51:16'),
(9, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 15:53:52'),
(10, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 15:54:11'),
(11, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 16:02:54'),
(12, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 16:02:59'),
(13, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 16:03:26'),
(14, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 16:03:28'),
(15, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 16:05:13'),
(16, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 16:05:58'),
(17, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 16:06:01'),
(18, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 16:06:03'),
(19, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 16:06:07'),
(20, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 16:06:09'),
(21, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 16:06:18'),
(22, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 16:06:36'),
(25, 2, '[Computer Science Club] sfqsf', 'qsfqfs', 0, '2025-04-06 16:11:35'),
(26, 2, '[Chess Club] qfqfsqsqsf', 'qsfqsf', 0, '2025-04-06 17:00:23'),
(27, 1, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-06 18:53:03'),
(28, 1, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-06 18:53:04'),
(29, 2, '[Chess Club] test', 'we have a meet !!', 0, '2025-04-13 16:20:20'),
(30, 2, '[Chess Club] test', 'we have a meet !!', 0, '2025-04-13 16:20:23'),
(32, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 17:04:15'),
(33, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 17:08:19'),
(34, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-13 18:09:58'),
(35, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-13 18:09:59'),
(36, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-13 18:45:16'),
(37, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-13 18:45:19'),
(38, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-13 18:52:40'),
(39, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-13 18:52:43'),
(40, 4, 'Club Joined', 'You have successfully joined Computer Science Club', 0, '2025-04-13 18:59:59'),
(41, 4, 'Club Left', 'You have left Computer Science Club', 0, '2025-04-13 19:01:35'),
(42, 4, 'Club Left', 'You have left Chess Club', 0, '2025-04-13 19:52:22'),
(43, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 19:52:23'),
(44, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 19:58:44'),
(45, 4, 'Club Left', 'You have left Chess Club', 0, '2025-04-13 20:04:19'),
(46, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 20:04:27'),
(47, 4, 'Club Left', 'You have left Chess Club', 0, '2025-04-13 20:04:30'),
(48, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 20:06:25'),
(49, 4, 'Club Left', 'You have left Chess Club', 0, '2025-04-13 20:06:26'),
(50, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 20:06:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','club_leader','admin') NOT NULL DEFAULT 'student',
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `ban_reason` text DEFAULT NULL,
  `banned_until` datetime DEFAULT NULL COMMENT 'Null for permanent ban',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `is_banned`, `ban_reason`, `banned_until`, `created_at`) VALUES
(1, 'admin', 'admin@university.edu', 'admin123', 'admin', 0, NULL, NULL, '2025-03-25 10:17:43'),
(2, 'clubleader', 'leader@university.edu', 'leader123', 'club_leader', 0, NULL, NULL, '2025-03-25 10:17:43'),
(3, 'student', 'student@university.edu', 'student123', 'student', 0, NULL, NULL, '2025-03-25 10:17:43'),
(4, 'zied kmantar', 'ziedkmantar@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-04-05 19:53:01'),
(5, 'azer', 'azerfarhat@gmail.com', 'azer123', 'student', 0, NULL, NULL, '2025-04-07 13:00:54'),
(6, 'Ella', 'ellahamdi@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-04-08 07:53:22'),
(7, 'yassine', 'yassinekmantar@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-04-09 14:25:23'),
(8, 'anoirTN', 'anoirajej02@gmail.com', 'anoir0987', 'student', 0, NULL, NULL, '2025-04-11 21:37:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposed_by_user_id` (`proposed_by_user_id`),
  ADD KEY `idx_club_status` (`status`);

--
-- Indexes for table `club_members`
--
ALTER TABLE `club_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_membership` (`user_id`,`club_id`),
  ADD KEY `idx_club_pending` (`club_id`,`role`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_event_status` (`status`);

--
-- Indexes for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_comments`
--
ALTER TABLE `event_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_event_comments_time` (`event_id`,`created_at`);

--
-- Indexes for table `event_interest`
--
ALTER TABLE `event_interest`
  ADD PRIMARY KEY (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_likes`
--
ALTER TABLE `event_likes`
  ADD PRIMARY KEY (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`sender_id`,`receiver_id`,`sent_at`),
  ADD KEY `idx_receiver_read` (`receiver_id`,`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_status` (`is_banned`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `club_members`
--
ALTER TABLE `club_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_attendees`
--
ALTER TABLE `event_attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_comments`
--
ALTER TABLE `event_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clubs`
--
ALTER TABLE `clubs`
  ADD CONSTRAINT `clubs_ibfk_1` FOREIGN KEY (`proposed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `club_members`
--
ALTER TABLE `club_members`
  ADD CONSTRAINT `club_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_members_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_attendees`
--
ALTER TABLE `event_attendees`
  ADD CONSTRAINT `event_attendees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attendees_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_comments`
--
ALTER TABLE `event_comments`
  ADD CONSTRAINT `event_comments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_interest`
--
ALTER TABLE `event_interest`
  ADD CONSTRAINT `event_interest_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_interest_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_likes`
--
ALTER TABLE `event_likes`
  ADD CONSTRAINT `event_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_likes_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
