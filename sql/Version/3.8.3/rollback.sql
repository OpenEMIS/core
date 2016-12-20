-- POCOR-3633
-- security_function
UPDATE `security_functions` SET `_edit`='StudentUser.edit' WHERE `id`='2000';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3633';


-- POCOR-3623
-- restore user_identities
INSERT INTO `user_identities`
SELECT * FROM `z_3623_user_identities`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3623';


-- 3.8.2
UPDATE config_items SET value = '3.8.2' WHERE code = 'db_version';
