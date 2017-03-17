-- staff_employments
ALTER TABLE `staff_employments`
  DROP `file_name`,
  DROP `file_content`;

-- labels
DELETE FROM `labels`
WHERE `id` = 'cdf0fca9-0a07-11e7-b9c5-525400b263eb';

-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` IN (3019, 7020);

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3886';
