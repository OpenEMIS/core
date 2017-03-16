-- staff_employments
ALTER TABLE `staff_employments`
  DROP `file_name`,
  DROP `file_content`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3886';
