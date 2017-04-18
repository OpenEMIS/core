-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3570', NOW());

-- institutions table update the year_opened
CREATE TABLE `z_3570_institutions` LIKE `institutions`;
INSERT INTO `z_3570_institutions`
SELECT * FROM `institutions`;

UPDATE `institutions` SET `year_opened` = YEAR('date_opened');
