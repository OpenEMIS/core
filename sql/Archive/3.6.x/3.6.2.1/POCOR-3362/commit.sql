-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3362', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'StudentTransfer', 'education_grade_id', 'Institution > StudentTransfer', 'From Education Grade', '1', '1', NOW());