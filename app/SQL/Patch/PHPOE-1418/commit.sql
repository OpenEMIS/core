-- 
-- PHPOE-1418 commit.sql
-- 

CREATE TABLE `z_1418_staff_attachments` LIKE `staff_attachments`;
INSERT INTO `z_1418_staff_attachments` SELECT * FROM `staff_attachments`;

ALTER TABLE `staff_attachments` ADD `date_on_file` DATE NOT NULL AFTER `file_content`;
