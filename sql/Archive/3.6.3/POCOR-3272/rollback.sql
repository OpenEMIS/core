-- replace values with original values from backup user contacts table
UPDATE `user_contacts`
INNER JOIN `z_3272_user_contacts`
ON `user_contacts`.`id` = `z_3272_user_contacts`.`id`
SET `user_contacts`.`value` = `z_3272_user_contacts`.`value`;

-- remove backup table
DROP TABLE `z_3272_user_contacts`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3272';
