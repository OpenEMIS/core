-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-4042', NOW());


-- institution_classes
CREATE TABLE `z_4042_institution_classes` LIKE `institution_classes`;
INSERT `z_4042_institution_classes` SELECT * FROM `institution_classes`;

ALTER TABLE `institution_classes` CHANGE `staff_id` `staff_id` INT(11) NULL DEFAULT '0' COMMENT 'links to security_users.id';
