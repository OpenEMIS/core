-- code here
DELETE FROM `security_functions` WHERE `id` = 1055;
DELETE FROM `security_functions` WHERE `id` = 2032;
DELETE FROM `security_functions` WHERE `id` = 5066;
ALTER TABLE `student_behaviours` DROP `academic_period_id`;
ALTER TABLE `student_behaviour_categories` DROP `behaviour_classification_id`;
DROP TABLE student_indexes_criterias;
DROP TABLE institution_student_indexes;
DROP TABLE behaviour_classifications;
DROP TABLE indexes_criterias;
DROP TABLE institution_indexes;
DROP TABLE indexes;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-2498';
