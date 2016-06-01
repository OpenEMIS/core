DROP TABLE assessment_items;
RENAME TABLE z2423_assessment_items TO assessment_items;
DROP TABLE assessment_item_results;
RENAME TABLE z2423_assessment_item_results TO assessment_item_results;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2423';