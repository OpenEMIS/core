-- labels
DELETE FROM `labels` WHERE `id` = '6143285a-ac8d-11e6-8bda-525400b263eb';

-- user_attachments_roles
DROP TABLE IF EXISTS `user_attachments_roles`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3111';
