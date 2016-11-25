-- code here
DROP TABLE indexes_criteria;
DROP TABLE indexes;


-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2498';
