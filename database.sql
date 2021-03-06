-- Adminer 4.7.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `telegram_file_id` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `md5_sum` binary(16) NOT NULL,
  `sha1_sum` binary(20) NOT NULL,
  `file_type` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'unknown',
  `extension` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `hit_count` bigint(20) unsigned NOT NULL DEFAULT '1',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `telegram_file_id` (`telegram_file_id`),
  KEY `md5_sum` (`md5_sum`),
  KEY `sha1_sum` (`sha1_sum`),
  KEY `hit_count` (`hit_count`),
  KEY `file_type` (`file_type`),
  KEY `extension` (`extension`),
  KEY `updated_at` (`updated_at`),
  KEY `created_at` (`created_at`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `username` varchar(72) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `link` varchar(128) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `photo` bigint(20) unsigned DEFAULT NULL,
  `msg_count` bigint(20) unsigned NOT NULL DEFAULT '0',
  `welcome_msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `captcha` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_id` (`group_id`),
  KEY `photo` (`photo`),
  KEY `name` (`name`),
  KEY `username` (`username`),
  KEY `link` (`link`),
  KEY `msg_count` (`msg_count`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  KEY `captcha` (`captcha`),
  FULLTEXT KEY `welcome_msg` (`welcome_msg`),
  CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`photo`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `groups_history`;
CREATE TABLE `groups_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `username` varchar(72) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `link` varchar(128) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `photo` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `photo` (`photo`),
  KEY `name` (`name`),
  KEY `username` (`username`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `groups_history_ibfk_4` FOREIGN KEY (`photo`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `groups_history_ibfk_5` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `groups_messages`;
CREATE TABLE `groups_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `tmsg_id` bigint(20) unsigned DEFAULT NULL,
  `reply_to_tmsg_id` bigint(20) unsigned DEFAULT NULL,
  `msg_type` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'unknown',
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `text_entities` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `file` bigint(20) unsigned DEFAULT NULL,
  `is_edited` enum('0','1') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '0',
  `tmsg_datetime` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`),
  KEY `file` (`file`),
  KEY `tmsg_id` (`tmsg_id`),
  KEY `reply_to_tmsg_id` (`reply_to_tmsg_id`),
  KEY `msg_type` (`msg_type`),
  KEY `is_edited` (`is_edited`),
  KEY `tmsg_datetime` (`tmsg_datetime`),
  KEY `created_at` (`created_at`),
  FULLTEXT KEY `text` (`text`),
  CONSTRAINT `groups_messages_ibfk_10` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `groups_messages_ibfk_12` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `groups_messages_ibfk_8` FOREIGN KEY (`file`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `id_1n_words_cloud`;
CREATE TABLE `id_1n_words_cloud` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_message_id` bigint(20) unsigned NOT NULL,
  `word` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `word` (`word`),
  KEY `created_at` (`created_at`),
  KEY `group_message_id` (`group_message_id`),
  CONSTRAINT `id_1n_words_cloud_ibfk_2` FOREIGN KEY (`group_message_id`) REFERENCES `groups_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `private_messages`;
CREATE TABLE `private_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `tmsg_id` bigint(20) unsigned DEFAULT NULL,
  `reply_to_tmsg_id` bigint(20) unsigned DEFAULT NULL,
  `msg_type` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'unknown',
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `text_entities` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `file` bigint(20) unsigned DEFAULT NULL,
  `is_edited` enum('0','1') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '0',
  `tmsg_datetime` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tmsg_id` (`tmsg_id`),
  KEY `reply_to_tmsg_id` (`reply_to_tmsg_id`),
  KEY `msg_type` (`msg_type`),
  KEY `is_edited` (`is_edited`),
  KEY `tmsg_datetime` (`tmsg_datetime`),
  KEY `datetime` (`created_at`),
  KEY `user_id` (`user_id`),
  KEY `file` (`file`),
  FULLTEXT KEY `text` (`text`),
  CONSTRAINT `private_messages_ibfk_4` FOREIGN KEY (`file`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `private_messages_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `username` varchar(72) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `photo` bigint(20) unsigned DEFAULT NULL,
  `is_bot` enum('0','1') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '0',
  `group_msg_count` bigint(20) unsigned NOT NULL DEFAULT '0',
  `private_msg_count` bigint(20) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`),
  KEY `username` (`username`),
  KEY `photo` (`photo`),
  KEY `group_msg_count` (`group_msg_count`),
  KEY `private_msg_count` (`private_msg_count`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`photo`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `users_history`;
CREATE TABLE `users_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `username` varchar(72) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `photo` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `photo` (`photo`),
  KEY `username` (`username`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `users_history_ibfk_3` FOREIGN KEY (`photo`) REFERENCES `files` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `users_history_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


-- 2019-12-29 16:37:35
