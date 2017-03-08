-- institutions
ALTER TABLE `institutions`
CHANGE `classification` `is_academic` INT(1) NOT NULL DEFAULT '1' COMMENT '0 -> Non-academic institution, 1 -> Academic Institution';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3427';
