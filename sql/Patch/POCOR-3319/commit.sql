-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3319', NOW());

-- create backup institutions table
CREATE TABLE `z_3319_institutions` LIKE `institutions`;

INSERT INTO `z_3319_institutions`
SELECT * FROM `institutions`
WHERE `date_closed` IS NULL
OR `date_closed` = ''
OR `date_closed` = '0000-00-00';

-- remove incorrect date-closed and year-closed values
UPDATE `institutions`
SET `year_closed` = NULL, `date_closed` = NULL
WHERE `date_closed` IS NULL
OR `date_closed` = ''
OR `date_closed` = '0000-00-00';