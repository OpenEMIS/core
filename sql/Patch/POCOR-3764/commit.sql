-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3764', NOW());

-- student_statuses
UPDATE `student_statuses`
SET `code` = 'WITHDRAWN', `name` = 'Withdrawn'
WHERE `code` = 'DROPOUT';
