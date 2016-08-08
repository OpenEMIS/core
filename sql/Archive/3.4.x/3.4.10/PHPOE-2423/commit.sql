INSERT INTO `db_patches` VALUES ('PHPOE-2423', NOW());

-- backing up
CREATE TABLE `z2423_assessment_items` LIKE `assessment_items`;
INSERT INTO `z2423_assessment_items` SELECT * FROM `assessment_items` WHERE 1;
CREATE TABLE `z2423_assessment_item_results` LIKE `assessment_item_results`;
INSERT INTO `z2423_assessment_item_results` SELECT * FROM `assessment_item_results` WHERE 1;

-- deleting not visible assessment items and their associated results
DELETE FROM assessment_items WHERE visible = 0;
DELETE FROM assessment_item_results WHERE NOT EXISTS (
	SELECT 1 FROM assessment_items WHERE assessment_items.id = assessment_item_results.assessment_item_id
);

ALTER TABLE `assessment_items` DROP `visible`;