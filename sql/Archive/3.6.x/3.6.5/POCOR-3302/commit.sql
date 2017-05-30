-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3302', NOW());

-- institutions
ALTER TABLE `institutions`
ADD COLUMN `is_academic` INT(1) NOT NULL DEFAULT 1 COMMENT '0 -> Non-academic institution\n1 -> Academic Institution' AFTER `shift_type`;
