-- POCOR-3219
UPDATE `report_progress`
INNER JOIN `z_3219_report_progress` ON `z_3219_report_progress`.`id` = `report_progress`.`id`
SET `report_progress`.`expiry_date` = `z_3219_report_progress`.`expiry_date`;

DROP TABLE `z_3219_report_progress`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3219';


-- 3.5.10
UPDATE config_items SET value = '3.5.10' WHERE code = 'db_version';
