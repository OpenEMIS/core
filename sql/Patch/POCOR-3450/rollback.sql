-- code here
DROP TABLE `staff_appraisals`;
DROP TABLE `staff_appraisal_types`;
DROP TABLE `competencies`;
DROP TABLE `competency_sets`;
DROP TABLE `competency_set_competencies`;


-- field_options
DELETE FROM `field_options` WHERE `code` = 'Competencies';
DELETE FROM `field_options` WHERE `code` = 'CompetencySets';

UPDATE `field_options` SET `order` = `order` - 2 WHERE `order` >= 19;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3450';
