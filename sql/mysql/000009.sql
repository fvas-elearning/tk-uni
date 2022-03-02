-- ---------------------------------
-- Update SQL v3.4.82
--
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------

-- Adding a mentor impot lookup table



CREATE TABLE IF NOT EXISTS `user_mentor_import` (
 `mentor_id` VARCHAR(128) NOT NULL,     # could be a number,username or email
 `student_id` VARCHAR(128) NOT NULL,    # could be a number,username or email
 PRIMARY KEY `user_mentor` (`mentor_id`, `student_id`)
) ENGINE=InnoDB;
