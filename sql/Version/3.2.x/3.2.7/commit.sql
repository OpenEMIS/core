-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-680', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) 
VALUES (uuid(), 'InstitutionRubrics', 'institution_site_id', 'Institution -> Rubrics', 'Institution', '1', '1', NOW());

UPDATE `config_items` SET `value` = '3.2.7' WHERE `code` = 'db_version';
