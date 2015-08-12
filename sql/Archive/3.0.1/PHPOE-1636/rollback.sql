ALTER TABLE `student_guardians` CHANGE `student_user_id` `security_user_id` INT(11) NOT NULL COMMENT '';
ALTER TABLE `student_guardians` CHANGE `guardian_user_id` `guardian_id` INT(11) NOT NULL COMMENT '';

DELETE FROM config_items WHERE code = 'guardian_prefix';

DROP TABLE guardian_activities;