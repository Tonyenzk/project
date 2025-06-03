-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2025 at 01:11 PM
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
-- Database: `socialland`
--

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `blocker_id` int(11) NOT NULL,
  `blocked_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocked_users`
--

INSERT INTO `blocked_users` (`blocker_id`, `blocked_id`, `created_at`) VALUES
(5, 6, '2025-06-01 06:13:45');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `parent_comment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `post_id`, `user_id`, `content`, `created_at`, `updated_at`, `parent_comment_id`) VALUES
(145, 88, 6, 'test', '2025-05-31 10:03:46', '2025-05-31 10:03:46', NULL),
(146, 89, 6, 'rege', '2025-05-31 10:09:06', '2025-05-31 10:09:06', NULL),
(147, 89, 6, 'rege', '2025-05-31 10:10:39', '2025-05-31 10:10:39', NULL),
(148, 89, 6, 'regele', '2025-05-31 10:13:50', '2025-05-31 10:13:50', NULL),
(149, 89, 6, 'dada', '2025-05-31 10:13:59', '2025-05-31 10:13:59', NULL),
(150, 89, 6, 'sefuu', '2025-05-31 10:15:14', '2025-05-31 10:15:14', NULL),
(151, 89, 6, 'regeee', '2025-05-31 10:15:24', '2025-05-31 10:15:24', NULL),
(152, 89, 6, 'haha', '2025-05-31 11:03:02', '2025-05-31 11:03:02', NULL),
(153, 89, 6, 'rege', '2025-05-31 11:03:15', '2025-05-31 11:03:15', NULL),
(154, 90, 5, 'test', '2025-05-31 11:03:27', '2025-05-31 11:03:27', NULL),
(155, 90, 6, 'regele', '2025-05-31 11:07:46', '2025-05-31 11:07:46', NULL),
(156, 91, 6, 'seff', '2025-05-31 11:08:30', '2025-05-31 11:08:30', NULL),
(157, 91, 6, 'rege', '2025-05-31 11:09:13', '2025-05-31 11:09:13', NULL),
(158, 91, 5, '@mssv ce faci?', '2025-05-31 11:16:49', '2025-05-31 11:16:49', 157),
(159, 90, 5, 'test', '2025-05-31 12:29:16', '2025-05-31 12:29:16', NULL),
(160, 88, 5, '@mssv test', '2025-06-01 08:37:10', '2025-06-01 08:37:10', 145),
(161, 88, 5, 'test', '2025-06-01 08:38:14', '2025-06-01 08:38:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comment_reports`
--

CREATE TABLE `comment_reports` (
  `report_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `reporter_user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_reports`
--

INSERT INTO `comment_reports` (`report_id`, `comment_id`, `reporter_user_id`, `reason`, `description`, `status`, `created_at`, `updated_at`) VALUES
(7, 145, 5, 'inappropriate', 'test', 'pending', '2025-06-01 08:37:24', '2025-06-01 08:37:24');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `organizer` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `event_date` datetime NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `status` enum('upcoming','today','this_week','past') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_prizes`
--

CREATE TABLE `event_prizes` (
  `prize_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follow_requests`
--

CREATE TABLE `follow_requests` (
  `request_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `requested_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `like_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`like_id`, `user_id`, `post_id`, `comment_id`, `created_at`) VALUES
(151, 5, 88, NULL, '2025-05-31 09:17:41'),
(165, 5, 89, NULL, '2025-05-31 09:30:58'),
(176, 6, 88, NULL, '2025-05-31 09:54:13'),
(180, 6, 90, NULL, '2025-05-31 11:03:42'),
(185, 6, 89, NULL, '2025-05-31 11:04:12'),
(186, 6, 91, NULL, '2025-05-31 11:08:59'),
(195, 5, 90, NULL, '2025-05-31 11:42:50'),
(196, 5, 91, NULL, '2025-06-01 04:43:57');

-- --------------------------------------------------------

--
-- Table structure for table `location_reports`
--

CREATE TABLE `location_reports` (
  `report_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `reporter_user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_reports`
--

INSERT INTO `location_reports` (`report_id`, `location`, `reporter_user_id`, `reason`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'test', 5, 'inappropriate', 'test', 'pending', '2025-05-31 07:58:51', '2025-05-31 07:58:51');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_history`
--

CREATE TABLE `maintenance_history` (
  `id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `initiated_by` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('active','completed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `actor_id` int(11) NOT NULL,
  `type` enum('like','comment','follow','mention','follow_request','story_like','follow_request_accepted','new_follower') NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `count` int(11) DEFAULT 1,
  `last_actor_username` varchar(50) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `story_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `actor_id`, `type`, `post_id`, `comment_id`, `is_read`, `created_at`, `count`, `last_actor_username`, `event_id`, `story_id`, `updated_at`) VALUES
(232, 5, 6, 'like', 89, NULL, 1, '2025-05-31 11:04:12', 1, NULL, NULL, NULL, '2025-05-31 11:04:12'),
(233, 5, 6, 'like', 88, NULL, 1, '2025-05-31 09:54:13', 1, NULL, NULL, NULL, NULL),
(234, 5, 6, 'comment', 89, 150, 1, '2025-05-31 10:15:14', 1, NULL, NULL, NULL, NULL),
(250, 6, 5, 'story_like', NULL, NULL, 1, '2025-05-31 11:02:25', 1, NULL, NULL, 23, '2025-05-31 11:02:30'),
(251, 5, 6, 'story_like', NULL, NULL, 1, '2025-05-31 11:02:52', 1, NULL, NULL, 19, '2025-05-31 11:03:06'),
(252, 6, 5, 'comment', 90, 154, 1, '2025-05-31 11:03:27', 1, NULL, NULL, NULL, '2025-05-31 11:03:37'),
(253, 5, 6, 'comment', 91, 156, 1, '2025-05-31 11:08:30', 1, NULL, NULL, NULL, '2025-05-31 11:09:03'),
(254, 5, 6, 'like', 91, NULL, 1, '2025-05-31 11:08:59', 1, NULL, NULL, NULL, '2025-05-31 11:09:03'),
(256, 6, 5, 'like', 90, NULL, 1, '2025-05-31 11:42:50', 1, NULL, NULL, NULL, '2025-05-31 12:26:46'),
(283, 5, 6, 'follow_request_accepted', NULL, NULL, 1, '2025-05-31 15:39:11', 1, NULL, NULL, NULL, '2025-05-31 15:39:16'),
(284, 6, 5, 'new_follower', NULL, NULL, 1, '2025-05-31 15:39:11', 1, NULL, NULL, NULL, '2025-06-01 03:35:25'),
(285, 6, 5, 'follow', NULL, NULL, 1, '2025-06-01 03:36:26', 1, NULL, NULL, NULL, '2025-06-01 03:36:33'),
(286, 5, 6, 'follow', NULL, NULL, 1, '2025-06-01 03:36:37', 1, NULL, NULL, NULL, '2025-06-01 03:36:42'),
(287, 6, 5, 'follow', NULL, NULL, 1, '2025-06-01 03:37:40', 1, NULL, NULL, NULL, '2025-06-01 03:37:45'),
(288, 5, 6, 'follow', NULL, NULL, 1, '2025-06-01 03:37:58', 1, NULL, NULL, NULL, '2025-06-01 03:45:32'),
(289, 6, 5, '', 88, 160, 0, '2025-06-01 08:37:10', 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `location_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deactivated_comments` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `image_url`, `caption`, `location`, `location_photo`, `created_at`, `updated_at`, `deactivated_comments`) VALUES
(88, 5, 'uploads/posts/post_683aaeb8687a5.png', '', 'test', NULL, '2025-05-31 07:24:40', '2025-05-31 07:24:40', 0),
(89, 5, 'uploads/posts/post_683ab03549f7f.jpg', '', '', NULL, '2025-05-31 07:31:01', '2025-05-31 07:31:01', 0),
(90, 6, 'uploads/posts/post_683ada0767955.png', '', '', NULL, '2025-05-31 10:29:27', '2025-05-31 10:29:27', 0),
(91, 5, 'uploads/posts/post_683ae326b906f.png', '', '', NULL, '2025-05-31 11:08:22', '2025-06-01 04:44:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `post_reports`
--

CREATE TABLE `post_reports` (
  `report_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `reporter_user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_reports`
--

INSERT INTO `post_reports` (`report_id`, `post_id`, `reporter_user_id`, `reason`, `description`, `status`, `created_at`, `updated_at`) VALUES
(4, 90, 5, '', 'test', 'pending', '2025-06-01 05:36:09', '2025-06-01 05:36:09');

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `tag_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `tagged_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_tags`
--

INSERT INTO `post_tags` (`tag_id`, `post_id`, `tagged_user_id`, `created_at`) VALUES
(18, 89, 6, '2025-05-31 07:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `saved_posts`
--

CREATE TABLE `saved_posts` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_posts`
--

INSERT INTO `saved_posts` (`user_id`, `post_id`, `created_at`) VALUES
(5, 88, '2025-06-01 08:37:27');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_id`, `setting_name`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'maintenance_mode', '0', '2025-05-30 12:55:53', '2025-05-30 13:06:48');

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `story_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 24 hour),
  `music_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`music_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stories`
--

INSERT INTO `stories` (`story_id`, `user_id`, `image_url`, `created_at`, `expires_at`, `music_data`) VALUES
(17, 5, 'uploads/stories/eb62d2509e8aabf3c3f676e5ae8eac44683914670bc7f_imagexxx.png', '2025-05-30 02:13:59', '2025-05-31 02:13:59', '{\"videoId\":\"Iz_VunlFCM0\",\"title\":\"STRES \\u274c SAMI G \\u274c DJ WICKED - Cand Nimeni Nu Te Vede \\u270a Official Video\",\"artist\":\"STRES\",\"thumbnail\":\"https:\\/\\/i.ytimg.com\\/vi\\/Iz_VunlFCM0\\/default.jpg\",\"startTime\":82}'),
(18, 5, 'uploads/stories/5492727054931e11763d3083f77ed67d6839152c0b8de_image.png', '2025-05-30 02:17:16', '2025-05-31 02:17:16', '{\"videoId\":\"SXY0tvBPCAI\",\"title\":\"Costel Biju \\u274c Tzanca Uraganu - Boierul asta nu cumpara nici nu vinde\",\"artist\":\"Nek Music Tv\",\"thumbnail\":\"https:\\/\\/i.ytimg.com\\/vi\\/SXY0tvBPCAI\\/default.jpg\",\"startTime\":53}'),
(19, 5, 'uploads/stories/e2ff697701f87de138667cbaef84c8e2683a418cf2e27_853486.jpg', '2025-05-30 23:38:52', '2025-05-31 23:38:52', '{\"videoId\":\"SXY0tvBPCAI\",\"title\":\"Costel Biju \\u274c Tzanca Uraganu - Boierul asta nu cumpara nici nu vinde\",\"artist\":\"Nek Music Tv\",\"thumbnail\":\"https:\\/\\/i.ytimg.com\\/vi\\/SXY0tvBPCAI\\/default.jpg\",\"startTime\":51}'),
(23, 6, 'uploads/stories/01119e42eaac6c6dcc4645dad67bf6d3683ada7574cd6_imagexxx.png', '2025-05-31 10:31:17', '2025-06-01 10:31:17', NULL),
(25, 5, 'uploads/stories/31ff892b3becd02148c7028eb401ef05683bfdcdee940_zxxx.jpg', '2025-06-01 07:14:21', '2025-06-02 07:14:21', '{\"videoId\":\"GPmwXVx7gME\",\"title\":\"Florin Salam - Hai cu mine in Bali [videoclip oficial] 2020\",\"artist\":\"Florin Salam Official\",\"thumbnail\":\"https:\\/\\/i.ytimg.com\\/vi\\/GPmwXVx7gME\\/default.jpg\",\"startTime\":123}');

-- --------------------------------------------------------

--
-- Table structure for table `stories_viewed`
--

CREATE TABLE `stories_viewed` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stories_viewed`
--

INSERT INTO `stories_viewed` (`id`, `user_id`, `story_id`, `viewed_at`) VALUES
(41, 5, 17, '2025-05-31 00:26:51'),
(51, 5, 18, '2025-05-31 00:26:19'),
(69, 6, 18, '2025-05-31 00:02:53'),
(82, 6, 17, '2025-05-31 00:47:01'),
(246, 5, 19, '2025-05-31 15:38:24'),
(250, 6, 19, '2025-05-31 11:02:51'),
(322, 5, 23, '2025-06-01 04:43:31'),
(335, 6, 23, '2025-05-31 11:02:44'),
(405, 5, 25, '2025-06-01 08:36:50');

-- --------------------------------------------------------

--
-- Table structure for table `story_reports`
--

CREATE TABLE `story_reports` (
  `report_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `reporter_user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `story_views`
--

CREATE TABLE `story_views` (
  `view_id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `viewer_id` int(11) NOT NULL,
  `has_liked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `story_views`
--

INSERT INTO `story_views` (`view_id`, `story_id`, `viewer_id`, `has_liked`, `created_at`) VALUES
(6, 18, 6, 0, '2025-05-30 02:24:35'),
(7, 17, 6, 0, '2025-05-30 02:27:06'),
(8, 19, 6, 1, '2025-05-31 00:02:55'),
(12, 23, 5, 1, '2025-05-31 10:31:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verifybadge` enum('false','pending','true') DEFAULT 'false',
  `role` enum('User','Moderator','Admin','Master_Admin') DEFAULT 'User',
  `isPrivate` tinyint(1) DEFAULT 0,
  `is_banned` tinyint(1) DEFAULT 0,
  `theme_preference` enum('light','dark') DEFAULT 'light',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `username`, `email`, `password_hash`, `profile_picture`, `bio`, `is_verified`, `verifybadge`, `role`, `isPrivate`, `is_banned`, `theme_preference`, `created_at`, `updated_at`) VALUES
(5, 'enzoku', 'enzoku', 'enzoku@ogland.ro', '$2y$10$a3GYS2zgw9W2WAJRHKQv7uJXvyvZJxniWtSXE4ZIZqAkVMd4mYxIu', 'uploads/profile_photo/profile_683945875a6e2.png', '', 0, 'true', 'Master_Admin', 1, 0, 'light', '2025-05-27 17:35:44', '2025-06-01 10:58:27'),
(6, 'mssv', 'mssv', 'mssv@ogland.ro', '$2y$10$SShq77DbvStkurcSF59jw.DD4dYBd5V1Sxu/UdDNcOfmiJkr4AFMi', './images/profile_placeholder.webp', '', 0, 'false', 'User', 0, 0, 'light', '2025-05-27 17:59:14', '2025-06-01 03:35:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_reports`
--

CREATE TABLE `user_reports` (
  `report_id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `reporter_user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_reports`
--

INSERT INTO `user_reports` (`report_id`, `reported_user_id`, `reporter_user_id`, `reason`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, 5, '', 'test', 'pending', '2025-05-31 01:33:20', '2025-05-31 01:33:20');

-- --------------------------------------------------------

--
-- Table structure for table `verification_requests`
--

CREATE TABLE `verification_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_requests`
--

INSERT INTO `verification_requests` (`request_id`, `user_id`, `request_date`, `status`, `admin_note`, `rejection_reason`) VALUES
(1, 5, '2025-05-30 08:53:00', 'approved', '', NULL),
(2, 5, '2025-05-30 12:24:35', 'approved', '', NULL),
(3, 5, '2025-06-01 09:56:30', 'approved', '', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`blocker_id`,`blocked_id`),
  ADD KEY `blocked_id` (`blocked_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_parent_comment` (`parent_comment_id`);

--
-- Indexes for table `comment_reports`
--
ALTER TABLE `comment_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_prizes`
--
ALTER TABLE `event_prizes`
  ADD PRIMARY KEY (`prize_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Indexes for table `follow_requests`
--
ALTER TABLE `follow_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `unique_request` (`requester_id`,`requested_id`),
  ADD KEY `requested_id` (`requested_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_post_like` (`post_id`,`user_id`),
  ADD UNIQUE KEY `unique_comment_like` (`comment_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `location_reports`
--
ALTER TABLE `location_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`);

--
-- Indexes for table `maintenance_history`
--
ALTER TABLE `maintenance_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `actor_id` (`actor_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  ADD KEY `story_id` (`story_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_reports`
--
ALTER TABLE `post_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `unique_tag` (`post_id`,`tagged_user_id`),
  ADD KEY `tagged_user_id` (`tagged_user_id`);

--
-- Indexes for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD PRIMARY KEY (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`story_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stories_viewed`
--
ALTER TABLE `stories_viewed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_story_view` (`user_id`,`story_id`),
  ADD KEY `story_id` (`story_id`);

--
-- Indexes for table `story_reports`
--
ALTER TABLE `story_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `story_id` (`story_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`);

--
-- Indexes for table `story_views`
--
ALTER TABLE `story_views`
  ADD PRIMARY KEY (`view_id`),
  ADD UNIQUE KEY `story_viewer_unique` (`story_id`,`viewer_id`),
  ADD KEY `viewer_id` (`viewer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_reports`
--
ALTER TABLE `user_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `reporter_user_id` (`reporter_user_id`);

--
-- Indexes for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `comment_reports`
--
ALTER TABLE `comment_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `event_prizes`
--
ALTER TABLE `event_prizes`
  MODIFY `prize_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `follow_requests`
--
ALTER TABLE `follow_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_reports`
--
ALTER TABLE `location_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `maintenance_history`
--
ALTER TABLE `maintenance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `post_reports`
--
ALTER TABLE `post_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `post_tags`
--
ALTER TABLE `post_tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `story_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `stories_viewed`
--
ALTER TABLE `stories_viewed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=411;

--
-- AUTO_INCREMENT for table `story_reports`
--
ALTER TABLE `story_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `story_views`
--
ALTER TABLE `story_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_reports`
--
ALTER TABLE `user_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `verification_requests`
--
ALTER TABLE `verification_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD CONSTRAINT `blocked_users_ibfk_1` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocked_users_ibfk_2` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_parent_comment` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_reports`
--
ALTER TABLE `comment_reports`
  ADD CONSTRAINT `comment_reports_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_reports_ibfk_2` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_prizes`
--
ALTER TABLE `event_prizes`
  ADD CONSTRAINT `event_prizes_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `followers_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followers_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `follow_requests`
--
ALTER TABLE `follow_requests`
  ADD CONSTRAINT `follow_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follow_requests_ibfk_2` FOREIGN KEY (`requested_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_3` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `location_reports`
--
ALTER TABLE `location_reports`
  ADD CONSTRAINT `location_reports_ibfk_1` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`actor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_5` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_6` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE SET NULL;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_reports`
--
ALTER TABLE `post_reports`
  ADD CONSTRAINT `post_reports_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_reports_ibfk_2` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tagged_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD CONSTRAINT `saved_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_posts_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `stories`
--
ALTER TABLE `stories`
  ADD CONSTRAINT `stories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `stories_viewed`
--
ALTER TABLE `stories_viewed`
  ADD CONSTRAINT `stories_viewed_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stories_viewed_ibfk_2` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE;

--
-- Constraints for table `story_reports`
--
ALTER TABLE `story_reports`
  ADD CONSTRAINT `story_reports_ibfk_1` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_reports_ibfk_2` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `story_views`
--
ALTER TABLE `story_views`
  ADD CONSTRAINT `story_views_ibfk_1` FOREIGN KEY (`story_id`) REFERENCES `stories` (`story_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_views_ibfk_2` FOREIGN KEY (`viewer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_reports`
--
ALTER TABLE `user_reports`
  ADD CONSTRAINT `user_reports_ibfk_1` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_reports_ibfk_2` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD CONSTRAINT `verification_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
