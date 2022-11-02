-- import_mapping
DELETE FROM `import_mapping` 
WHERE `model` = 'Examination.ExaminationCentreRooms';

-- security_functions
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5057;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3661';
