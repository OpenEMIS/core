-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3772', NOW());

-- security_group_areas
DROP TABLE IF EXISTS `z_3772_security_group_areas`;
CREATE TABLE `z_3772_security_group_areas` LIKE `security_group_areas`; 

INSERT `z_3772_security_group_areas` SELECT * FROM `security_group_areas`;

DELETE FROM `security_group_areas` 
WHERE NOT EXISTS ( 
	SELECT 1 FROM `security_groups` 
	WHERE `security_groups`.`id` = `security_group_areas`.`security_group_id` 
);

-- security_group_institutions
DROP TABLE IF EXISTS `z_3772_security_group_institutions`;
CREATE TABLE `z_3772_security_group_institutions` LIKE `security_group_institutions`; 

INSERT `z_3772_security_group_institutions` SELECT * FROM `security_group_institutions`;

DELETE FROM `security_group_institutions`
WHERE NOT EXISTS (
    SELECT 1
    FROM `security_groups`
    WHERE `security_groups`.`id` = `security_group_institutions`.`security_group_id`
);

-- security_group_users
DROP TABLE IF EXISTS `z_3772_security_group_users`;
CREATE TABLE `z_3772_security_group_users` LIKE `security_group_users`; 

INSERT `z_3772_security_group_users` SELECT * FROM `security_group_users`;

DELETE FROM `security_group_users`
WHERE NOT EXISTS (
    SELECT 1
    FROM `security_groups`
    WHERE `security_groups`.`id` = `security_group_users`.`security_group_id`
);