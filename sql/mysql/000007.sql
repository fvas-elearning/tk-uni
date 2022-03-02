-- ---------------------------------
-- Update SQL v3.4.0
--
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------

-- Create the new type field
alter table user add type varchar(32) default '' not null after uid;

RENAME TABLE user_permission TO _user_role_permission;
RENAME TABLE user_role_institution TO _user_role_institution;
RENAME TABLE user_role TO _user_role;


-- -----------------------------------------------------
-- The user permission table
-- Table structure for table `user_permission`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_permission` (
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY `user_id_name` (`user_id`, `name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `_user_role_id` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY `user_id_role` (`user_id`, `role_id`)
) ENGINE=InnoDB;

INSERT INTO _user_role_id (user_id, role_id)
  (
    SELECT a.id, a.role_id
    FROM user a
    WHERE 1
  )
;



UPDATE user a, _user_role b SET a.type = b.type
    WHERE a.role_id = b.id;

INSERT INTO user_permission (user_id, name)
  (
    SELECT a.id, c.name
    FROM user a, _user_role b, _user_role_permission c
    WHERE a.role_id = b.id AND b.id = c.role_id AND c.name NOT LIKE 'type.%'
  )
;

INSERT INTO user_permission (user_id, name)
    (
        SELECT a.id, 'perm.masquerade'
        FROM user a
        WHERE a.type = 'client' OR a.type = 'admin'
    )
;


alter table user drop column role_id;
alter table user drop column name;


-- DROP TABLE _user_role;
-- DROP TABLE _user_role_permission;
-- DROP TABLE _user_role_institution


-- NOTE: This has to be run manually before upgrading to ver 3.2
-- RENAME TABLE migration TO _migration;

-- RENAME TABLE data TO _data;
-- RENAME TABLE plugin TO _plugin;
-- # Do this for live sites only
-- RENAME TABLE session TO _session;


-- MENTOR SETUP

CREATE TABLE IF NOT EXISTS `user_mentor` (
  `user_id` int(10) unsigned NOT NULL,
  `mentor_id` int(10) unsigned NOT NULL,            -- staff user.id
  PRIMARY KEY `user_mentor` (`user_id`, `mentor_id`)
) ENGINE=InnoDB;






