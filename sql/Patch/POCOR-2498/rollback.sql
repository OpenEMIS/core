-- code here
ALTER TABLE `student_behaviour_categories` DROP `classification_id`;
DROP TABLE classifications;
DROP TABLE indexes_criteria;
DROP TABLE indexes;


-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2498';
