-- POCOR-2614
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2614';


-- POCOR-2899
-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2899';


-- POCOR-2885
-- delete new table

DROP TABLE IF EXISTS `special_need_difficulties`;

-- db_rollback
DROP TABLE IF EXISTS `user_special_needs`;

ALTER TABLE `z_2885_user_special_needs`
RENAME TO  `user_special_needs`;

-- db_rollback
DELETE FROM `field_options`
WHERE `order` = 49;

UPDATE `field_options`
SET `order` = `order`-1
WHERE `order` >=49;

-- remove labels
DELETE FROM `labels` WHERE `module`='SpecialNeeds' and `field`='special_need_difficulty_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2885';


-- 3.5.4
UPDATE config_items SET value = '3.5.4' WHERE code = 'db_version';
