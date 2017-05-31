-- institution_statuses
DROP TABLE IF EXISTS `institution_statuses`;
RENAME TABLE `z_3983_institution_statuses` TO `institution_statuses`;

-- institutions
DROP TABLE IF EXISTS `institutions`;
RENAME TABLE `z_3983_institutions` TO `institutions`;

-- import_mapping
UPDATE `import_mapping` SET `id` = `id`+1, `order` = `order`+1
WHERE `model` = 'Institution.Institutions' AND `id` >= 20
ORDER BY `id` DESC;

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES ('20', 'Institution.Institutions', 'institution_status_id', 'Code', '20', '1', 'Institution', 'Statuses', 'national_code');


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3983';

