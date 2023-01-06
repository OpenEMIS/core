-- user_nationalities
DROP TABLE IF EXISTS `user_nationalities`;
RENAME TABLE `z_3606_user_nationalities` TO `user_nationalities`;

-- security_users
UPDATE `security_users` S
SET S.`identity_type_id` = NULL, 
    S.`identity_number` = NULL,
    S.`nationality_id` = NULL;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3606';
