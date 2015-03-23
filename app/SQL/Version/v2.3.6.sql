-- PHPOE-1290

DROP TABLE IF EXISTS `1290_institution_site_class_students`;
CREATE TABLE 1290_institution_site_class_students LIKE institution_site_class_students; 
INSERT 1290_institution_site_class_students SELECT * FROM institution_site_class_students;

ALTER TABLE `institution_site_class_students`
  -- DROP `student_category_id`,
  DROP `education_grade_id`;

-- PHPOE-1244

--
-- 1. New table - wf_workflows
--

DROP TABLE IF EXISTS `wf_workflows`;
CREATE TABLE IF NOT EXISTS `wf_workflows` (
`id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(100) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflows`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflows`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 2. New table - wf_workflow_steps
--

DROP TABLE IF EXISTS `wf_workflow_steps`;
CREATE TABLE IF NOT EXISTS `wf_workflow_steps` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `wf_workflow_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_steps`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_steps`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 3. New table - wf_workflow_step_roles
--

DROP TABLE IF EXISTS `wf_workflow_step_roles`;
CREATE TABLE IF NOT EXISTS `wf_workflow_step_roles` (
`id` int(11) NOT NULL,
  `wf_workflow_step_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_step_roles`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_step_roles`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 4. New table - wf_workflow_actions
--

DROP TABLE IF EXISTS `wf_workflow_actions`;
CREATE TABLE IF NOT EXISTS `wf_workflow_actions` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `next_wf_workflow_step_id` int(11) NOT NULL,
  `wf_workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_actions`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_actions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 5. New table - wf_workflow_logs
--

DROP TABLE IF EXISTS `wf_workflow_logs`;
CREATE TABLE IF NOT EXISTS `wf_workflow_logs` (
`id` int(11) NOT NULL,
  `reference_table` varchar(200) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `comments` text NOT NULL,
  `wf_workflow_step_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `wf_workflow_logs`
 ADD PRIMARY KEY (`id`);


ALTER TABLE `wf_workflow_logs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 6. navigations
--

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `navigations` WHERE `module` LIKE 'Administration' AND `header` LIKE 'Quality' AND `title` LIKE 'Status';

UPDATE `navigations` SET `order` = `order` + 2 WHERE `order` > @orderOfQualityStatus;

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Administration', 'Workflows', 'Workflows', 'Workflows', 'Workflows', 'index', 'index|view|edit|add|delete', '33', '0', @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `navigations` (
`id` ,
`module` ,
`plugin` ,
`controller` ,
`header` ,
`title` ,
`action` ,
`pattern` ,
`parent` ,
`is_wizard` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Administration', 'Workflows', 'WorkflowSteps', 'Workflows', 'Steps', 'index', 'index|view|edit|add|delete', '33', '0', @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

--
-- 7. security_functions
--

SET @orderOfQualityStatus := 0;
SELECT `order` INTO @orderOfQualityStatus FROM `security_functions` WHERE `controller` LIKE 'Quality' AND `category` LIKE 'Quality' AND `name` LIKE 'Status';

UPDATE `security_functions` SET `order` = `order` + 2 WHERE `order` > @orderOfQualityStatus;

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'Workflows', 'Workflows', 'Administration', 'Workflows', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

SET @orderOfQualityStatus := @orderOfQualityStatus + 1;

INSERT INTO `security_functions` (
`id` ,
`name` ,
`controller` ,
`module` ,
`category` ,
`parent_id` ,
`_view` ,
`_edit` ,
`_add` ,
`_delete` ,
`_execute` ,
`order` ,
`visible` ,
`created_user_id` ,
`created`
)
VALUES (
NULL , 'WorkflowSteps', 'WorkflowSteps', 'Administration', 'Workflows', '-1', 'index|view', '_view:edit', '_view:add', '_view:delete', NULL , @orderOfQualityStatus, '1', '1', '0000-00-00 00:00:00'
);

-- DROP ALL BACKUP TABLES

DROP TABLE IF EXISTS `1132_field_options`;
DROP TABLE IF EXISTS `1132_school_years`;
DROP TABLE IF EXISTS `1136_assessment_result_types`;
DROP TABLE IF EXISTS `1136_employment_types`;
DROP TABLE IF EXISTS `1136_extracurricular_types`;
DROP TABLE IF EXISTS `1136_field_option_values`;
DROP TABLE IF EXISTS `1136_field_options`;
DROP TABLE IF EXISTS `1136_health_allergy_types`;
DROP TABLE IF EXISTS `1136_health_conditions`;
DROP TABLE IF EXISTS `1136_health_consultation_types`;
DROP TABLE IF EXISTS `1136_health_immunizations`;
DROP TABLE IF EXISTS `1136_health_relationships`;
DROP TABLE IF EXISTS `1136_health_test_types`;
DROP TABLE IF EXISTS `1136_identity_types`;
DROP TABLE IF EXISTS `1136_institution_site_localities`;
DROP TABLE IF EXISTS `1136_institution_site_ownership`;
DROP TABLE IF EXISTS `1136_institution_site_statuses`;
DROP TABLE IF EXISTS `1136_institution_site_types`;
DROP TABLE IF EXISTS `1136_languages`;
DROP TABLE IF EXISTS `1136_license_types`;
DROP TABLE IF EXISTS `1136_qualification_specialisations`;
DROP TABLE IF EXISTS `1136_quality_visit_types`;
DROP TABLE IF EXISTS `1136_salary_addition_types`;
DROP TABLE IF EXISTS `1136_salary_deduction_types`;
DROP TABLE IF EXISTS `1136_special_need_types`;
DROP TABLE IF EXISTS `1136_staff_position_grades`;
DROP TABLE IF EXISTS `1136_staff_position_steps`;
DROP TABLE IF EXISTS `1136_staff_position_titles`;
DROP TABLE IF EXISTS `1136_student_behaviour_categories`;
DROP TABLE IF EXISTS `1136_student_categories`;
DROP TABLE IF EXISTS `1136_training_course_types`;
DROP TABLE IF EXISTS `1136_training_field_studies`;
DROP TABLE IF EXISTS `1136_training_levels`;
DROP TABLE IF EXISTS `1136_training_mode_deliveries`;
DROP TABLE IF EXISTS `1136_training_priorities`;
DROP TABLE IF EXISTS `1136_training_providers`;
DROP TABLE IF EXISTS `1136_training_requirements`;
DROP TABLE IF EXISTS `1136_training_statuses`;
DROP TABLE IF EXISTS `1136edit_field_options`;
DROP TABLE IF EXISTS `1145_area_education_levels`;
DROP TABLE IF EXISTS `1145_area_educations`;
DROP TABLE IF EXISTS `1145_area_levels`;
DROP TABLE IF EXISTS `1145_areas`;
DROP TABLE IF EXISTS `1190_config_items`;
DROP TABLE IF EXISTS `1190_institution_site_class_subjects`;
DROP TABLE IF EXISTS `1214_institution_site_programmes`;
DROP TABLE IF EXISTS `1214_institution_site_students`;

DROP PROCEDURE IF EXISTS `patch1214`;

-- Fix Attendance export permission

UPDATE `security_functions` 
SET `_edit` = '_view:InstitutionSiteStudentAbsence.edit|InstitutionSiteStudentAbsence.dayedit',
`_add` = '_view:InstitutionSiteStudentAbsence.add',
`_execute` = '_view:InstitutionSiteStudentAbsence.excel|InstitutionSiteStudentAttendance.excel'
WHERE `controller` = 'InstitutionSites'
AND `module` = 'Institutions'
AND `category` = 'Attendance'
AND `name` = 'Students';

UPDATE `security_functions` 
SET `_edit` = '_view:InstitutionSiteStaffAbsence.edit|InstitutionSiteStaffAbsence.dayedit',
`_add` = '_view:InstitutionSiteStaffAbsence.add',
`_execute` = '_view:InstitutionSiteStaffAbsence.excel|InstitutionSiteStaffAttendance.excel'
WHERE `controller` = 'InstitutionSites'
AND `module` = 'Institutions'
AND `category` = 'Attendance'
AND `name` = 'Staff';
