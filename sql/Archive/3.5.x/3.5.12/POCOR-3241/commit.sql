-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3241', NOW());

-- report_progress
DELETE FROM report_progress
WHERE `expiry_date` = NULL 
AND `file_path` = NULL
AND `current_records` = 0
AND `status` = 1;
