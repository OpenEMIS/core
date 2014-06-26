DROP TABLE IF EXISTS `training_session_trainers`;
CREATE TABLE IF NOT EXISTS `training_session_trainers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_id` int(11) NOT NULL,
  `ref_trainer_id` int(11) NOT NULL,
  `ref_trainer_name` varchar(255) NOT NULL,
  `ref_trainer_table` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `ref_trainer_id` (`ref_trainer_id`,`ref_trainer_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `staff_training_needs`;
CREATE TABLE IF NOT EXISTS `staff_training_needs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `ref_course_id` int(11) NOT NULL,
  `ref_course_table` varchar(100) NOT NULL,
  `ref_course_code` varchar(10) DEFAULT NULL,
  `ref_course_title` varchar(100) DEFAULT NULL,
  `ref_course_description` text,
  `ref_course_requirement` varchar(100) DEFAULT NULL,
  `training_priority_id` int(11) NOT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `comments` text,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `ref_course_id`  (`ref_course_id`,`ref_course_table`),
  KEY `training_priority_id` (`training_priority_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `staff_training_self_study_results`;
CREATE TABLE IF NOT EXISTS `staff_training_self_study_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_training_self_study_id` int(11) NOT NULL,
  `pass` int(1) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_training_self_study_id` (`staff_training_self_study_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `workflows`;
CREATE TABLE IF NOT EXISTS `workflows` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `model_name` varchar(50) NOT NULL,
  `workflow_name` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `approve` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `parent_id` int(11) DEFAULT '0',
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


INSERT INTO `workflows` (`id`, `model_name`, `workflow_name`, `action`, `approve`, `visible`, `order`, `parent_id`, `lft`, `rght`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'TrainingCourse', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 1, 2, NULL, '2014-06-17 23:09:29', 1, '2014-04-09 00:00:00'),
(2, 'TrainingCourse', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 31, 32, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(3, 'TrainingCourse', 'Pending for Accreditation', 'Accredit', 'Accredited', 1, 3, NULL, 29, 30, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(4, 'TrainingSession', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 27, 28, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(5, 'TrainingSession', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 25, 26, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(6, 'TrainingSession', 'Pending for Registration', 'Register', 'Registered', 1, 3, NULL, 23, 24, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(7, 'TrainingSessionResult', 'Pending for Evaluation', 'Evaluate', '', 1, 1, NULL, 21, 22, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(8, 'TrainingSessionResult', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 19, 20, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(9, 'TrainingSessionResult', 'Pending for Posting', 'Post', 'Posted', 1, 3, NULL, 17, 18, NULL, '2014-06-17 23:09:30', 1, '2014-04-11 00:00:00'),
(10, 'StaffTrainingNeed', 'Pending for Approval', 'Approve', 'Approved', 1, 1, NULL, 15, 16, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(11, 'StaffTrainingSelfStudy', 'Pending for Recommendation', 'Recommend', '', 1, 1, NULL, 13, 14, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(12, 'StaffTrainingSelfStudy', 'Pending for Approval', 'Approve', '', 1, 2, NULL, 11, 12, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(13, 'StaffTrainingSelfStudy', 'Pending for Accreditation', 'Accredit', 'Accredited', 1, 3, NULL, 3, 10, NULL, '2014-06-17 23:09:29', 1, '2014-04-11 00:00:00'),
(14, 'StaffTrainingSelfStudyResult', 'Pending for Result Recommendation', 'Recommend', '', 1, 1, 13, 6, 7, NULL, '2014-06-17 23:09:29', 1, '2014-06-17 00:00:00'),
(15, 'StaffTrainingSelfStudyResult', 'Pending for Result Approval', 'Approve', '', 1, 2, 13, 4, 5, NULL, '2014-06-17 23:09:29', 1, '2014-06-17 00:00:00'),
(16, 'StaffTrainingSelfStudyResult', 'Pending for Result Accreditation', 'Accredit', 'Result Accredited', 1, 3, 13, 8, 9, NULL, '2014-06-17 23:09:29', 1, '2014-06-17 00:00:00');


DROP TABLE IF EXISTS `staff_training_self_studies`;
CREATE TABLE IF NOT EXISTS `staff_training_self_studies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_achievement_type_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `objective` text,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `training_provider` varchar(255) NOT NULL,
  `hours` int(3) NOT NULL,
  `credit_hours` int(11) NOT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `staff_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_status_id` (`training_status_id`),
  KEY `training_achievement_type_id` (`training_achievement_type_id`),
  KEY `staff_id` (`staff_id`),
  KEY `training_provider_id` (`training_provider`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(69, 'TrainingAchievementType', 'Achievement Types', 'Training', NULL, 69, 1, NULL, NULL, 1, '2014-06-17 00:00:00');

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(null, 'School Based Study', 1, 1, 1, 0, NULL, NULL, 69, NULL, NULL, 1, '2014-06-17 00:00:00'),
(null, 'Self Based Study', 2, 1, 1, 0, NULL, NULL, 69, NULL, NULL, 1, '2014-06-17 00:00:00');

TRUNCATE table workflow_logs;

DROP TABLE IF EXISTS `training_sessions`;
CREATE TABLE IF NOT EXISTS `training_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_course_id` int(11) NOT NULL,
  `training_provider_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `comments` text,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `training_provider_id` (`training_provider_id`),
  KEY `area_id` (`area_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `training_courses` CHANGE `credit_hours` `credit_hours` INT( 3 ) NULL ;
ALTER TABLE `training_courses` DROP `pass_result` ;
ALTER TABLE `training_courses` ADD INDEX ( `training_course_type_id` ) ;

DROP TABLE IF EXISTS `training_course_result_types`;
CREATE TABLE IF NOT EXISTS `training_course_result_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_course_id` int(11) NOT NULL,
  `training_result_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `training_result_type_id` (`training_result_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `training_course_target_populations`;
CREATE TABLE IF NOT EXISTS `training_course_target_populations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_course_id` int(11) NOT NULL,
  `staff_position_title_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_course_id` (`training_course_id`),
  KEY `staff_position_title_id` (`staff_position_title_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

truncate table training_course_attachments;
truncate table training_course_prerequisites;
truncate table training_course_providers;


DROP TABLE IF EXISTS `training_session_results`;
CREATE TABLE IF NOT EXISTS `training_session_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_id` int(11) NOT NULL,
  `training_status_id` int(11) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `training_status_id` (`training_status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `training_session_trainees`;
CREATE TABLE IF NOT EXISTS `training_session_trainees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `pass` int(1) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_id` (`training_session_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `training_session_trainee_results`;
CREATE TABLE IF NOT EXISTS `training_session_trainee_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_session_trainee_id` int(11) NOT NULL,
  `training_result_type_id` int(11) NOT NULL,
  `pass` int(1) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_session_trainee_id` (`training_session_trainee_id`),
  KEY `training_result_type_id` (`training_result_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


TRUNCATE table staff_training_self_study_attachments;

ALTER TABLE  `staff_training_self_study_results` ADD UNIQUE (
`staff_training_self_study_id`
);


Update `security_functions` set _edit='_view:resultEdit|resultEdit|resultDownloadTemplate|resultUpload' where `name` ='Training Results' and controller='Training' and module='Training';
