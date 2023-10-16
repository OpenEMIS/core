-- restore user_identities
INSERT INTO `user_identities`
SELECT * FROM `z_3623_user_identities`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3623';
