-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Institution.Staff' AND `column_name` = 'end_date';
UPDATE `import_mapping` SET `order`='2' WHERE `model`='Institution.Staff' AND `column_name`='start_date';
UPDATE `import_mapping` SET `order`='1' WHERE `model`='Institution.Staff' AND `column_name`='institution_position_id';
UPDATE `import_mapping` SET `order`='5' WHERE `model`='Institution.Staff' AND `column_name`='staff_type_id';
UPDATE `import_mapping` SET `order`='6' WHERE `model`='Institution.Staff' AND `column_name`='staff_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3110';
