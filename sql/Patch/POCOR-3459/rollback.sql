-- institutions
ALTER TABLE `institutions`
MODIFY COLUMN `classification` INT(1) NOT NULL DEFAULT '1' COMMENT '0 -> Non-academic institution, 1 -> Academic Institution';

UPDATE `institutions`
SET `classification` = 0
WHERE `classification` = 2;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3459';
