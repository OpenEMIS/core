ALTER TABLE `user_identities`
DROP INDEX `number`,
DROP INDEX `username`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4081';
