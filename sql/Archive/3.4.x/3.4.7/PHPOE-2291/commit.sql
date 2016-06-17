INSERT INTO `db_patches` VALUES ('PHPOE-2291', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'fromAcademicPeriod', 'Institution > Promotion', 'From Academic Period', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'toAcademicPeriod', 'Institution > Promotion', 'To Academic Period', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'fromGrade', 'Institution > Promotion', 'From Grade', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'toGrade', 'Institution > Promotion', 'To Grade', 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'StudentPromotion', 'status', 'Institution > Promotion', 'Status', 1, NOW());