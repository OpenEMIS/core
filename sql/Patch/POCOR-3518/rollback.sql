
DELETE FROM `config_items` WHERE `code` = 'openemis_id_prefix';

INSERT INTO `config_items`
SELECT * FROM `z_3518_config_items`;

DROP TABLE `z_3518_config_items`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3518';
