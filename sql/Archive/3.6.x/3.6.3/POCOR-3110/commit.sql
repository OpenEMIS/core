-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3110', NOW());

-- import_mapping
INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`) VALUES ('Institution.Staff', 'end_date', '( DD/MM/YYYY )', '2', '0');
UPDATE `import_mapping` SET `order`='1' WHERE `model`='Institution.Staff' AND `column_name`='start_date';
UPDATE `import_mapping` SET `order`='5' WHERE `model`='Institution.Staff' AND `column_name`='institution_position_id';
UPDATE `import_mapping` SET `order`='6' WHERE `model`='Institution.Staff' AND `column_name`='staff_type_id';
UPDATE `import_mapping` SET `order`='7' WHERE `model`='Institution.Staff' AND `column_name`='staff_id';
