-- ---------------------------------
-- Install SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------



-- ----------------------------
--  user
-- ----------------------------
CREATE TABLE IF NOT EXISTS `user` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `institution_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `uid` VARCHAR(128) NOT NULL DEFAULT '',
  `username` VARCHAR(64) NOT NULL DEFAULT '',
  `password` VARCHAR(128) NOT NULL DEFAULT '',
  -- ROLES: 'admin', 'client', 'staff', 'student
  `role` VARCHAR(255) NOT NULL DEFAULT '',
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `display_name` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(168) NOT NULL DEFAULT '',
  `image` VARCHAR(168) NOT NULL DEFAULT '',
  `notes` TEXT,
  `last_login` DATETIME,
  `session_id` VARCHAR(70) NOT NULL DEFAULT '',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `hash` VARCHAR(128) NOT NULL DEFAULT '',
  `del` TINYINT(1) NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  KEY `user_username` (`institution_id`, `username`),
  KEY `user_email` (`institution_id`, `email`),
  KEY `user_hash` (`institution_id`, `hash`)
) ENGINE=InnoDB;

-- ----------------------------
--  institution
-- ----------------------------
CREATE TABLE IF NOT EXISTS `institution` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `domain` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT,
  `logo` VARCHAR(255) NOT NULL DEFAULT '',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `hash` VARCHAR(128) NOT NULL DEFAULT '',
  `del` TINYINT(1) NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
--  UNIQUE KEY `inst_domain` (`domain`),
  UNIQUE KEY `inst_hash` (`hash`)
) ENGINE=InnoDB;

-- ----------------------------
--  subject Data Tables
-- ----------------------------
CREATE TABLE IF NOT EXISTS `subject` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `institution_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `code` VARCHAR(64) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT,
  `date_start` DATETIME NOT NULL,
  `date_end` DATETIME NOT NULL,
  `del` TINYINT(1) NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  KEY `subject_code_institution` (`code`, `institution_id`)
) ENGINE=InnoDB;

-- ----------------------------
-- For now we will assume that one user has one role in a subject, ie: coordinator, lecturer, student
-- User is enrolled in subject or coordinator of subject
-- ----------------------------
CREATE TABLE IF NOT EXISTS `subject_has_user` (
  `user_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `subject_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `subject_has_user_key` (`user_id`, `subject_id`)
) ENGINE=InnoDB;


-- --------------------------------------------------------
--
--
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subject_pre_enrollment` (
  `subject_id` int(10) unsigned NOT NULL DEFAULT '0',
  `email` VARCHAR(168) NOT NULL DEFAULT '',
  `uid` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`subject_id`, `email`)
) ENGINE=InnoDB;



