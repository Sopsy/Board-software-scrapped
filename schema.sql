CREATE TABLE `ban` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned DEFAULT NULL,
    `ip` varbinary(16) DEFAULT NULL,
    `length` int(10) unsigned NOT NULL,
    `begin_time` timestamp NULL DEFAULT NULL,
    `end_time` timestamp NULL DEFAULT NULL,
    `reason_id` tinyint(3) unsigned NOT NULL,
    `additional_info` varchar(120) DEFAULT NULL,
    `post_id` int(10) unsigned DEFAULT NULL,
    `banned_by` int(10) unsigned NOT NULL,
    `is_expired` bit(1) NOT NULL DEFAULT b'0',
    `is_appealed` bit(1) NOT NULL DEFAULT b'0',
    `appeal_text` varchar(120) DEFAULT NULL,
    `appeal_is_checked` bit(1) NOT NULL DEFAULT b'0',
    PRIMARY KEY (`id`),
    KEY (`ip`),
    KEY (`is_appealed`),
    KEY (`user_id`),
    KEY (`user_id`,`is_expired`,`end_time`,`begin_time`),
    KEY (`is_appealed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `board` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `url` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `alt_url` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `name` varchar(60) NOT NULL,
    `description` varchar(1000) NOT NULL,
    `is_nsfw` bit(1) NOT NULL DEFAULT b'0',
    `is_hidden` bit(1) NOT NULL DEFAULT b'0',
    `show_flags` bit(1) DEFAULT b'0',
    `inactive_hours_delete` smallint(5) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`url`),
    KEY (`alt_url`),
    KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `username` varchar(30) DEFAULT NULL,
    `password` char(60) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
    `account_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_ip` varbinary(16) DEFAULT NULL,
    `class` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `gold_level` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `gold_expires` DATETIME NULL DEFAULT NULL,
    `last_board` tinyint(3) unsigned NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`username`),
    KEY (`last_board`),
    KEY (`class`),
    KEY (`gold_expires`),
    KEY (`password`,`gold_level`,`id`),
    KEY (`gold_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `file` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NULL DEFAULT NULL,
    `folder` char(2) CHARACTER SET ascii NOT NULL,
    `name` char(8) CHARACTER SET ascii NOT NULL,
    `extension` varchar(30) CHARACTER SET ascii NOT NULL,
    `size` int(10) unsigned NOT NULL,
    `width` smallint(5) unsigned DEFAULT NULL,
    `height` smallint(5) unsigned DEFAULT NULL,
    `thumb_width` smallint(5) unsigned DEFAULT NULL,
    `thumb_height` smallint(5) unsigned DEFAULT NULL,
    `duration` int(10) unsigned DEFAULT NULL,
    `has_thumbnail` bit(1) NOT NULL DEFAULT b'1',
    `has_sound` bit(1) DEFAULT NULL,
    `is_gif` bit(1) DEFAULT NULL,
    `in_progress` bit(1) NOT NULL DEFAULT b'0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `user_id` (`user_id`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `file_md5` (
    `file_id` int(10) unsigned NOT NULL,
    `md5` binary(16) NOT NULL,
    PRIMARY KEY (`file_id`,`md5`),
    KEY (`md5`),
    CONSTRAINT FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned DEFAULT NULL,
    `action_id` smallint(5) unsigned NOT NULL,
    `custom_data` text COLLATE utf8mb4_swedish_ci,
    `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip` varbinary(16) NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`user_id`),
    KEY (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user_board_hide` (
    `user_id` int(10) unsigned NOT NULL,
    `board_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`user_id`,`board_id`),
    KEY (`board_id`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`board_id`) REFERENCES `board` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `post` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned DEFAULT NULL,
    `board_id` int(10) unsigned DEFAULT NULL,
    `thread_id` int(10) unsigned DEFAULT NULL,
    `ip` varbinary(16) DEFAULT NULL,
    `country_code` char(2) CHARACTER SET ascii DEFAULT NULL,
    `username` varchar(30) DEFAULT NULL,
    `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `subject` varchar(60) DEFAULT NULL,
    `message` text COLLATE utf8mb4_swedish_ci,
    `edited` int(10) unsigned DEFAULT NULL,
    `bump_time` timestamp NULL DEFAULT NULL,
    `locked` bit(1) DEFAULT NULL,
    `sticky` bit(1) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY (`bump_time`,`thread_id`,`user_id`,`id`),
    KEY (`thread_id`),
    KEY (`user_id`),
    CONSTRAINT FOREIGN KEY (`thread_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `post_deleted` (
    `id` int(10) unsigned NOT NULL,
    `user_id` int(10) unsigned NOT NULL DEFAULT '0',
    `board_id` tinyint(3) unsigned DEFAULT NULL,
    `thread_id` int(10) unsigned DEFAULT NULL,
    `ip` varbinary(16) NOT NULL,
    `time` timestamp NULL DEFAULT NULL,
    `subject` varchar(60) DEFAULT NULL,
    `message` text NOT NULL,
    `time_deleted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`time`),
    KEY (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `post_edited` (
    `id` int(10) unsigned NOT NULL,
    `edit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip` varbinary(16) NOT NULL,
    `message_before` text NOT NULL,
    PRIMARY KEY (`id`,`edit_time`),
    CONSTRAINT FOREIGN KEY (`id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `post_file` (
    `post_id` int(10) unsigned NOT NULL,
    `file_id` int(10) unsigned NOT NULL,
    `file_name` varchar(255) NOT NULL,
    PRIMARY KEY (`post_id`,`file_id`),
    KEY (`file_id`),
    KEY (`file_name`),
    CONSTRAINT FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`file_id`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `post_reply` (
    `post_id` int(10) unsigned NOT NULL,
    `post_id_replied` int(10) unsigned NOT NULL,
    `user_id` int(10) unsigned DEFAULT NULL,
    `user_id_replied` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`post_id`,`post_id_replied`),
    KEY `post_id_replied` (`post_id_replied`),
    KEY `user_id` (`user_id_replied`),
    KEY `user_id_2` (`user_id`),
    CONSTRAINT FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`post_id_replied`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `post_report` (
    `post_id` int(10) unsigned NOT NULL,
    `reason_id` tinyint(4) NOT NULL,
    `additional_info` varchar(120) DEFAULT NULL,
    `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reported_by` varbinary(16) NOT NULL,
    `is_checked` bit(1) NOT NULL DEFAULT b'0',
    `checked_by` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`post_id`),
    KEY (`is_checked`),
    CONSTRAINT FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `post_statistics` (
    `thread_id` int(10) unsigned NOT NULL DEFAULT '0',
    `read_count` int(10) unsigned NOT NULL DEFAULT '0',
    `reply_count` int(10) unsigned NOT NULL DEFAULT '0',
    `distinct_reply_count` int(10) unsigned NOT NULL DEFAULT '0',
    `hide_count` int(10) unsigned NOT NULL DEFAULT '0',
    `follow_count` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user_notification` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned NOT NULL,
    `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `type` tinyint(3) unsigned NOT NULL,
    `post_id` int(10) unsigned DEFAULT NULL,
    `custom_data` varchar(10000) DEFAULT NULL,
    `count` int(10) unsigned NOT NULL DEFAULT '1',
    `is_read` bit(1) NOT NULL DEFAULT b'0',
    PRIMARY KEY (`id`),
    UNIQUE KEY (`user_id`,`type`,`post_id`),
    KEY (`post_id`),
    KEY (`user_id`,`type`,`time`,`is_read`),
    KEY (`time`),
    KEY (`user_id`,`post_id`,`is_read`),
    KEY (`count`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user_preferences` (
    `user_id` int(10) unsigned NOT NULL,
    `preferences_key` int(10) unsigned NOT NULL,
    `preferences_value` varchar(16000) NOT NULL,
    PRIMARY KEY (`user_id`,`preferences_key`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user_session` (
    `id` binary(32) NOT NULL,
    `user_id` int(10) unsigned NOT NULL,
    `verify_key` binary(32) NOT NULL,
    `csrf_token` binary(32) NOT NULL,
    `ip` varbinary(16) NOT NULL,
    `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`user_id`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user_statistics` (
    `user_id` int(10) unsigned NOT NULL,
    `statistics_key` smallint(5) unsigned NOT NULL DEFAULT '0',
    `statistics_value` bigint(20) NOT NULL,
    PRIMARY KEY (`user_id`,`statistics_key`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user_thread_follow` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(10) unsigned NOT NULL,
    `thread_id` int(10) unsigned NOT NULL,
    `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen_reply` int(10) unsigned DEFAULT NULL,
    `unread_count` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY (`user_id`,`thread_id`,`unread_count`),
    KEY (`thread_id`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`thread_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `user_thread_hide` (
    `user_id` int(10) unsigned NOT NULL,
    `thread_id` int(10) unsigned NOT NULL,
    `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`,`thread_id`),
    KEY (`thread_id`),
    CONSTRAINT FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (`thread_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

CREATE TABLE `word_blacklist` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `word` varchar(1000) NOT NULL,
    `reason_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;

-- Just to get you started...
INSERT INTO `board` (`id`, `url`, `alt_url`, `name`, `description`, `is_nsfw`, `is_hidden`, `show_flags`, `inactive_hours_delete`) VALUES
(1, 'satunnainen', 'b', 'Satunnainen', 'I guess this is why they call this board random', b'1', b'0', b'0', 720);
