-- code here
DROP TABLE student_behaviour_categories_classifications;
DROP TABLE student_behaviour_classifications;
DROP TABLE indexes_criteria;
DROP TABLE indexes;


-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2498';
