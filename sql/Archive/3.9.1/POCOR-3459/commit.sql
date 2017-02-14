-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3459', NOW());

-- institutions
ALTER TABLE `institutions`
MODIFY COLUMN `classification` INT(1) NOT NULL DEFAULT '1' COMMENT '1 -> Academic Institution, 2 -> Non-academic institution';

UPDATE `institutions`
SET `classification` = 2
WHERE `classification` = 0;
