-- code here
ALTER TABLE `student_behaviours` DROP `academic_period_id`;
ALTER TABLE `student_behaviour_categories` DROP `classification_id`;
DROP TABLE student_indexes_criterias;
DROP TABLE institution_student_indexes;
DROP TABLE classifications;
DROP TABLE indexes_criterias;
DROP TABLE indexes;


-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2498';
