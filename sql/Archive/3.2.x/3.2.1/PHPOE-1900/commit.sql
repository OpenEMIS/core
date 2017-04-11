-- 
-- PHPOE-1900 commit.sql
-- 

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1900');

UPDATE `institution_sites`
SET `institution_sites`.`date_opened` = CONCAT(`institution_sites`.`year_opened`, '-01-01')
WHERE `institution_sites`.`date_opened` = '0000-00-00' && `institution_sites`.`year_opened` != '0' && `institution_sites`.`year_opened` IS NOT NULL;
