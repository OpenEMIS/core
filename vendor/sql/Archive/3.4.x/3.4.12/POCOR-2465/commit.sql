INSERT INTO `db_patches` VALUES ('POCOR-2465', NOW());

UPDATE security_functions SET _execute = 'Visits.download' WHERE id = 1027;