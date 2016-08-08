-- 
-- PHPOE-832
--

DROP TABLE `config_items`;
ALTER TABLE `z_832_config_items` RENAME `config_items`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-832';