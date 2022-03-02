-- ---------------------------------
-- Update SQL v3.4.0
--
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------


-- #alter table user modify notes BLOB after image;
alter table user modify password varchar(128) default '' not null after session_id;
alter table user add title varchar(16) default '' not null after username;
alter table user add credentials varchar(255) default '' not null after phone;
alter table user add position varchar(255) default '' not null after credentials;




