-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 05, 2025 at 11:05 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

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

DROP TABLE IF EXISTS `clubs`;
CREATE TABLE IF NOT EXISTS `clubs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `meeting_schedule` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','active','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `proposed_by_user_id` int DEFAULT NULL COMMENT 'User ID who submitted the request',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logo_path` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proposed_by_user_id` (`proposed_by_user_id`),
  KEY `idx_club_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `description`, `category`, `meeting_schedule`, `status`, `proposed_by_user_id`, `created_at`, `logo_path`) VALUES
(1, 'ISGS Experts Club', 'Isg Experts Club 🌟\nNonprofit organization\nNotre club offre des ateliers dans le domaine des affaires ainsi que des évents socioculturels pour apprendre et se connecter dans un cadre dynamique.', 'Technology', 'Tuesdays at 5 PM', 'active', NULL, '2025-03-25 10:17:43', NULL),
(2, 'Microsoft ISGS Club', 'ISGS Microsoft Club\r\n✨Invest In Your Future\r\n📍Higher Institute of management of Sousse\r\n• FB: ISGS Microsoft\r\n• Linkedin : ISGs Microsoft Club\r\n• TikTok: isgsmicrosoftclub', 'Technology', 'Fridays at 3 PM', 'active', NULL, '2025-03-25 10:17:43', '/cm/uploads/club_logos/micro.jpg'),
(3, 'Nexusart ISGS Club', 'Nexus art🌟🌠\r\n´´✨ le lieu où l’art prend vie .rejoignez-nous pour exprimer votre créativité !🎭🎼 ‘´\r\n📍institut supérieur de gestion sousse', 'Arts & Culture', 'Wednesdays at 6 PM', 'active', NULL, '2025-03-25 10:17:43', '/cm/uploads/club_logos/art.jpg'),
(4, 'Rotaract ISGS Club', 'Rotaract ISG.S\r\n📍Sousse, Tunisia district 9010\r\n📧 Rotaract.isgs@gmail.com', 'Academic', 'Tuesday 5pm', 'active', 1, '2025-04-06 20:19:34', '/cm/uploads/club_logos/rotaract.png');

-- --------------------------------------------------------

--
-- Table structure for table `club_members`
--

DROP TABLE IF EXISTS `club_members`;
CREATE TABLE IF NOT EXISTS `club_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `club_id` int NOT NULL,
  `role` enum('member','leader','pending') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `department` enum('President','Vice President','HR Responsible','General Secretary','Media Responsible','Sponsoring Responsible','Logistique Responsible','Media Member','Sponsoring Member','Logistique Member','General Member') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'General Member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_membership` (`user_id`,`club_id`),
  KEY `idx_club_pending` (`club_id`,`role`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_members`
--

INSERT INTO `club_members` (`id`, `user_id`, `club_id`, `role`, `joined_at`, `department`) VALUES
(1, 8, 1, 'leader', '2025-03-25 10:17:43', 'President'),
(2, 2, 2, 'leader', '2025-03-25 10:17:43', 'President'),
(3, 3, 3, 'member', '2025-03-25 10:17:43', 'General Member'),
(29, 4, 2, 'member', '2025-04-13 20:06:34', 'Media Responsible'),
(30, 6, 4, 'leader', '2025-05-05 06:38:07', 'President'),
(31, 7, 3, 'leader', '2025-05-05 06:39:22', 'President'),
(32, 9, 2, 'member', '2025-05-05 06:40:21', 'Media Member'),
(33, 9, 3, 'member', '2025-05-05 06:40:27', 'Media Responsible'),
(34, 10, 2, 'member', '2025-05-05 10:14:33', 'HR Responsible'),
(35, 11, 1, 'member', '2025-05-05 10:15:07', 'General Secretary'),
(36, 12, 2, 'member', '2025-05-05 10:15:31', 'Sponsoring Responsible'),
(37, 13, 4, 'member', '2025-05-05 10:16:49', 'Media Member'),
(38, 14, 2, 'member', '2025-05-05 10:17:21', 'Logistique Member'),
(39, 15, 1, 'member', '2025-05-05 10:18:18', 'Sponsoring Member'),
(40, 5, 2, 'member', '2025-05-05 10:20:27', 'Vice President');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `club_id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `event_date` datetime NOT NULL,
  `event_end_date` datetime DEFAULT NULL COMMENT 'Optional: Date and time the event ends',
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','active','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `poster_image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path to event poster image',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `club_id` (`club_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_event_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `club_id`, `name`, `description`, `event_date`, `event_end_date`, `location`, `status`, `poster_image_path`, `created_at`, `created_by`) VALUES
(4, 2, 'CMC', '🚀 Blast off alert! 🌕\nRegistrations for the Coding Moon Challenge are officially LIVE! 🚨\n👉 bit.ly/codingmoon25\nDon’t just watch from the sidelines—be part of the mission! 🌌\n#CodingMoonChallenge #Hackathon #ToTheMoonAndBeyond #RegisterNow\nThe countdown has begun. Will you be on board? 🚀👩‍💻👨‍💻', '2025-04-08 03:18:00', '2025-04-09 03:18:00', 'EPI', 'active', '/cm/uploads/event_posters/event_67f33630b74af2.02937043.jpg', '2025-04-07 02:19:28', 2),
(5, 2, 'CMC', 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffqsfqfqsfqsf', '2025-04-10 03:28:00', '2025-04-11 03:28:00', 'sale 17', 'rejected', '/cm/uploads/event_posters/event_67f338433138f0.13613334.jpg', '2025-04-07 02:28:19', 2),
(6, 2, 'hachathon', 'BLABLABLABLABLABLABALBALABALB   AKABKABKABKABBAKBAKAB', '2025-04-16 12:57:00', '2025-04-17 12:57:00', 'ihecc', 'rejected', '/cm/uploads/event_posters/event_67f3bdd8a86dd7.41879141.jpg', '2025-04-07 11:58:16', 2),
(7, 2, 'Formation Flutter', 'Unlocking the power of mobile development with Flutter! 🚀 Dive into the world of beautiful apps, fast development, and seamless cross-platform experiences\r\nBy the Microsofter : @zied_kmanter\r\nIf you’re interested don’t forget to fill the form 👋:\r\n\r\nhttps://docs.google.com/forms/d/e/1FAIpQLSdlCZ6uBoSb4NL51bbj3L9MKDxtpu0k0luctLlS3bYjZ2gNfg/viewform?ts=67a2856f&fbclid', '2025-05-15 07:45:00', '2025-05-24 07:45:00', 'ISGS NVS2', 'active', '/cm/uploads/event_posters/event_68185eda24cf87.25475336.jpg', '2025-05-05 06:46:50', 1),
(8, 1, 'Match tn ISGS', 'Maybe you will meet your match ,\r\nyou never know 😏', '2025-05-07 07:50:00', '2025-05-16 07:51:00', 'A23', 'active', '/cm/uploads/event_posters/event_68185fffc909e3.73187879.jpg', '2025-05-05 06:51:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `event_attendees`
--

DROP TABLE IF EXISTS `event_attendees`;
CREATE TABLE IF NOT EXISTS `event_attendees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `event_id` int NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`user_id`,`event_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_comments`
--

DROP TABLE IF EXISTS `event_comments`;
CREATE TABLE IF NOT EXISTS `event_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_event_comments_time` (`event_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_comments`
--

INSERT INTO `event_comments` (`id`, `event_id`, `user_id`, `comment_text`, `created_at`) VALUES
(2, 4, 8, 'Yooo', '2025-04-11 21:38:40');

-- --------------------------------------------------------

--
-- Table structure for table `event_interest`
--

DROP TABLE IF EXISTS `event_interest`;
CREATE TABLE IF NOT EXISTS `event_interest` (
  `user_id` int NOT NULL,
  `event_id` int NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`event_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_interest`
--

INSERT INTO `event_interest` (`user_id`, `event_id`, `marked_at`) VALUES
(8, 4, '2025-04-11 21:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `event_likes`
--

DROP TABLE IF EXISTS `event_likes`;
CREATE TABLE IF NOT EXISTS `event_likes` (
  `user_id` int NOT NULL,
  `event_id` int NOT NULL,
  `liked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`event_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_likes`
--

INSERT INTO `event_likes` (`user_id`, `event_id`, `liked_at`) VALUES
(1, 4, '2025-05-01 11:19:51'),
(4, 4, '2025-04-07 18:15:50'),
(5, 4, '2025-04-07 13:03:46'),
(8, 4, '2025-04-11 21:38:20');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message_content` text COLLATE utf8mb4_general_ci NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_conversation` (`sender_id`,`receiver_id`,`sent_at`),
  KEY `idx_receiver_read` (`receiver_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(50, 4, 'Club Joined', 'You have successfully joined Chess Club', 0, '2025-04-13 20:06:34'),
(51, 9, 'Club Joined', 'You have successfully joined Microsoft ISGS Club', 0, '2025-05-05 06:40:21'),
(52, 9, 'Club Joined', 'You have successfully joined Nexusart ISGS Club', 0, '2025-05-05 06:40:27'),
(53, 10, 'Club Joined', 'You have successfully joined Microsoft ISGS Club', 0, '2025-05-05 10:14:33'),
(54, 11, 'Club Joined', 'You have successfully joined ISGS Experts Club', 0, '2025-05-05 10:15:07'),
(55, 12, 'Club Joined', 'You have successfully joined Microsoft ISGS Club', 0, '2025-05-05 10:15:31'),
(56, 13, 'Club Joined', 'You have successfully joined Rotaract ISGS Club', 0, '2025-05-05 10:16:49'),
(57, 14, 'Club Joined', 'You have successfully joined Microsoft ISGS Club', 0, '2025-05-05 10:17:21'),
(58, 15, 'Club Joined', 'You have successfully joined ISGS Experts Club', 0, '2025-05-05 10:18:18'),
(59, 5, 'Club Joined', 'You have successfully joined Microsoft ISGS Club', 0, '2025-05-05 10:20:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('student','club_leader','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'student',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `ban_reason` text COLLATE utf8mb4_general_ci,
  `banned_until` datetime DEFAULT NULL COMMENT 'Null for permanent ban',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_picture_path` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_status` (`is_banned`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `is_banned`, `ban_reason`, `banned_until`, `created_at`, `profile_picture_path`) VALUES
(1, 'admin', 'admin@university.edu', 'admin123', 'admin', 0, NULL, NULL, '2025-03-25 10:17:43', NULL),
(2, 'MejdEddine', 'leader@university.edu', 'leader123', 'club_leader', 0, NULL, NULL, '2025-03-25 10:17:43', '/cm/uploads/profile_pics/2_1745490708.png'),
(3, 'Salah', 'student@university.edu', 'student123', 'student', 0, NULL, NULL, '2025-03-25 10:17:43', NULL),
(4, 'zied kmantar', 'ziedkmantar@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-04-05 19:53:01', '/cm/uploads/profile_pics/4_1746439366.png'),
(5, 'azer', 'azerfarhat@gmail.com', 'azer123', 'student', 0, NULL, NULL, '2025-04-07 13:00:54', '/cm/uploads/profile_pics/5_1746440459.jpg'),
(6, 'Ella', 'ellahamdi@gmail.com', '123456', 'club_leader', 0, NULL, NULL, '2025-04-08 07:53:22', NULL),
(7, 'yassine', 'yassinekmantar@gmail.com', '123456', 'club_leader', 0, NULL, NULL, '2025-04-09 14:25:23', NULL),
(8, 'anoirTN', 'anoirajej02@gmail.com', 'anoir0987', 'student', 0, NULL, NULL, '2025-04-11 21:37:11', NULL),
(9, 'Mohammed baccari', 'Mohammedbaccari@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-05-05 06:40:15', '/cm/uploads/profile_pics/9_1746427263.jpg'),
(10, 'saoussen', 'saoussen@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-05-05 10:14:28', NULL),
(11, 'Salma Chaieb', 'SalmaChaieb@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-05-05 10:14:47', NULL),
(12, 'Chaima', 'Chaima@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-05-05 10:15:25', '/cm/uploads/profile_pics/12_1746440147.jpg'),
(13, 'Yasmine Farhat', 'YasmineFarhat@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-05-05 10:16:18', '/cm/uploads/profile_pics/13_1746440200.jpg'),
(14, 'Ilyees', 'Ilyees@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-05-05 10:17:03', '/cm/uploads/profile_pics/14_1746440235.jpeg'),
(15, 'Melliti Arwed', 'MellitiArwed@gmail.com', '123456', 'student', 0, NULL, NULL, '2025-05-05 10:17:44', NULL);

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
