-- import_mapping
DELETE FROM `import_mapping` 
WHERE `model` = 'Examination.ExaminationCentreRooms';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3661';
