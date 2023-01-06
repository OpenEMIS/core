-- 
-- PHPOE-2436
--

INSERT INTO `db_patches` VALUES ('PHPOE-2436', NOW());

CREATE TABLE `z_2436_import_mapping` LIKE `import_mapping`;
INSERT INTO `z_2436_import_mapping` SELECT * FROM `import_mapping`;

DELETE FROM `import_mapping` WHERE `id`=11 or `id`=13;
UPDATE `import_mapping` SET `order` = `order`+1000 WHERE `order` > 14 and `model`='Institution.Institutions';
UPDATE `import_mapping` SET `order` = `order`-1001 WHERE `order` > 1000 and `model`='Institution.Institutions';
UPDATE `import_mapping` SET `order` = `order`+1000 WHERE `order` > 12 and `model`='Institution.Institutions';
UPDATE `import_mapping` SET `order` = `order`-1001 WHERE `order` > 1000 and `model`='Institution.Institutions';

UPDATE `import_mapping` SET `id` = `id`+1000 WHERE `id` > 12;
UPDATE `import_mapping` SET `id` = `id`-1001 WHERE `id` > 1000;

UPDATE `import_mapping` SET `id` = `id`+1000 WHERE `id` > 10;
UPDATE `import_mapping` SET `id` = `id`-1001 WHERE `id` > 1000;

UPDATE `import_mapping` SET `description` = '( DD/MM/YYYY )' WHERE `column_name` LIKE '%date%';

DROP TABLE IF EXISTS `z_2403_import_mapping`;
DROP TABLE IF EXISTS `z_2421_import_mapping`;

