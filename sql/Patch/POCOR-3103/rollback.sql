-- -- code here
ALTER TABLE `user_comments` DROP COLUMN `comment_type_id`;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3103';