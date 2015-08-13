DELETE FROM `labels` WHERE `module` = 'StudentPromotion';

INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'openemis_no', 'OpenEMIS ID', 1, NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'student_id', 'Student', 1, NOW());
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StudentPromotion', 'education_grade_id', 'Next Grade', 1, NOW());
