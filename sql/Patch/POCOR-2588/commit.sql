-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2588', NOW());

ALTER TABLE `academic_period_levels` ADD `editable` INT(1) NOT NULL DEFAULT TRUE AFTER `level`, ADD INDEX (`editable`);
UPDATE `academic_period_levels` SET `editable` = '0' WHERE `academic_period_levels`.`name` = 'Year';