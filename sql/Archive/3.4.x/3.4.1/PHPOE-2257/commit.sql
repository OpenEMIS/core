-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2257', NOW());

-- custom_modules
UPDATE `custom_modules` SET `filter`='Institution.Types' WHERE `code`='Institution' AND `model`='Institution.Institutions';
