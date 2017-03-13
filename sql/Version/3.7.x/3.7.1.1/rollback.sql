-- POCOR-3486
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3486';


-- POCOR-3440
-- institution_student_admission
ALTER TABLE `institution_student_admission` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject';

-- institution_student_dropout
ALTER TABLE `institution_student_dropout` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Approve, 2 -> Reject';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3440';


-- POCOR-3436
-- security_functions
UPDATE `security_functions`
SET `_execute` = 'Promotion.index|Promotion.add|Promotion.reconfirm'
WHERE `controller` = 'Institutions' AND `name` = 'Promotion';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3436';


-- 3.7.1
UPDATE config_items SET value = '3.7.1' WHERE code = 'db_version';
