-- restore table
DROP TABLE assessment_item_results;
RENAME TABLE z_1878_assessment_item_results TO assessment_item_results;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1878';
