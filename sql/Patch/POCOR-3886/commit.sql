-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3886', NOW());

-- staff_employments
ALTER TABLE `staff_employments`  
ADD `file_name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL  AFTER `comment`,  
ADD `file_content` LONGBLOB NULL DEFAULT NULL  AFTER `file_name`;