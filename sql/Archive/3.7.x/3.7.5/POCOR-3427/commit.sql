-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3427', NOW());

-- institutions
ALTER TABLE `institutions`
CHANGE `is_academic` `classification` INT(1) NOT NULL DEFAULT '1' COMMENT '0 -> Non-academic institution, 1 -> Academic Institution';
