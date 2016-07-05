-- -- code here
-- field_options table
DELETE FROM `field_options` WHERE `field_options`.`id` = 101;

-- field_option_value
DELETE FROM `field_option_values` WHERE `field_option_id` = 101;

-- user_comments table
ALTER TABLE `user_comments` DROP COLUMN `comment_type_id`;



-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3103';