-- Database Schema for VHS Retro Riddle Game

CREATE DATABASE IF NOT EXISTS `80s_video_store` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `80s_video_store`;

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Riddles Table
CREATE TABLE IF NOT EXISTS `riddles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` varchar(255) NOT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data: Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Movies', '80s and 90s movie riddles'),
(2, 'Sports Trivia', 'Riddles about various sports'),
(3, 'Games', 'Video game and board game riddles'),
(4, 'Coding Knowledge', 'Riddles about programming, databases, and APIs')
ON DUPLICATE KEY UPDATE name=name;

-- Seed Data: Sample Riddles
INSERT INTO `riddles` (`category_id`, `question`, `answer`, `difficulty`) VALUES
(1, 'I am a movie about a time traveling car. What am I?', 'Back to the Future', 'easy'),
(1, 'Who you gonna call?', 'Ghostbusters', 'easy'),
(4, 'I am a language that snakes use. What am I?', 'Python', 'easy'),
(4, 'I hold all your data but have no hands. What am I?', 'Database', 'medium');
