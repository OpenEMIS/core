-- student_statuses
UPDATE `student_statuses`
SET `code` = 'DROPOUT', `name` = 'Dropout'
WHERE `code` = 'WITHDRAWN';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3764';
