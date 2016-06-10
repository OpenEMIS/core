INSERT INTO `db_patches` VALUES ('PHPOE-1904', NOW());

DELETE from field_options where code = 'SanitationGenders' and name = 'Sanitation Gender' and parent = 'Infrastructure';
DELETE from field_options where code = 'StaffPositionSteps' and name = 'Steps' and parent = 'Position';
DELETE from field_options where plugin = 'Students' and code = 'StudentCategories' and name = 'Categories' and parent = 'Student';
DELETE from field_options where plugin = 'Students' and code = 'Genders' and name = 'Gender' and parent = 'Student';
DELETE from field_options where plugin = 'Students' and code = 'StudentStatuses' and name = 'Status' and parent = 'Student';

DELETE from field_options where code = 'FinanceNatures' and name = 'Nature' and parent = 'Finance';
DELETE from field_options where code = 'FinanceTypes' and name = 'Types' and parent = 'Finance';
DELETE from field_options where code = 'FinanceCategories' and name = 'Categories' and parent = 'Finance';
DELETE from field_options where code = 'FinanceSources' and name = 'Source' and parent = 'Finance';

DELETE from field_options where code = 'HealthAllergyTypes' and name = 'Allergy Types' and parent = 'Health';
DELETE from field_options where code = 'HealthConditions' and name = 'Conditions' and parent = 'Health';
DELETE from field_options where code = 'HealthConsultationTypes' and name = 'Consultation Types' and parent = 'Health';
DELETE from field_options where code = 'HealthImmunizations' and name = 'Immunization' and parent = 'Health';
DELETE from field_options where code = 'HealthRelationships' and name = 'Relationships' and parent = 'Health';
DELETE from field_options where code = 'HealthTestTypes' and name = 'Test Types' and parent = 'Health';

DELETE FROM field_option_values WHERE NOT EXISTS (SELECT 1 FROM field_options WHERE field_options.id = field_option_values.field_option_id);

UPDATE field_options SET plugin = 'Institution', code = 'Genders' WHERE code = 'InstitutionSiteGenders';
UPDATE field_options SET plugin = 'Institution', code = 'Localities' WHERE code = 'InstitutionSiteLocalities';
UPDATE field_options SET plugin = 'Institution', code = 'Ownerships' WHERE code = 'InstitutionSiteOwnerships';
UPDATE field_options SET plugin = 'Institution', code = 'Sectors' WHERE code = 'InstitutionSiteSectors';
UPDATE field_options SET plugin = 'Institution', code = 'Statuses' WHERE code = 'InstitutionSiteStatuses';
UPDATE field_options SET plugin = 'Institution', code = 'Types' WHERE code = 'InstitutionSiteTypes';


UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StudentAbsenceReasons';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StudentBehaviourCategories';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffAbsenceReasons';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffBehaviourCategories';

UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'LeaveStatuses';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffLeaveTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffStatuses';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffTrainingCategories';

UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'Banks';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'BankBranches';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'FeeTypes';

UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'GuardianRelations';

UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'QualificationLevels';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'QualificationSpecialisations';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'QualityVisitTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'SalaryAdditionTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'SalaryDeductionTypes';

UPDATE field_options SET plugin = 'User' WHERE code = 'ContactTypes';

UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'EmploymentTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'ExtracurricularTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'IdentityTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'LicenseTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'SpecialNeedTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'InfrastructureOwnerships';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'InfrastructureConditions';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'Countries';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StudentTransferReasons';

UPDATE field_options SET visible = 0 WHERE code = 'GuardianEducationLevels';

UPDATE field_options SET visible = 0 WHERE parent = 'Training';

--
-- PHPOE-2092
--
INSERT INTO `db_patches` VALUES ('PHPOE-2092', NOW());

UPDATE `security_functions` SET `controller`='Educations' WHERE `controller`='Education' AND `module`='Administration' AND `category`='Education';

-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2178', NOW());

-- backup institution site data
CREATE TABLE `z_2178_Institution_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_opened` date NOT NULL,
  `year_opened` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8;

INSERT INTO `z_2178_Institution_sites` (`id`, `date_opened`, `year_opened`)
SELECT `id`, `date_opened`, `year_opened`
FROM `institution_sites`;

-- institution_sites
UPDATE `institution_sites`
SET `institution_sites`.`date_opened` = 
	(
		SELECT MIN(`academic_periods`.`start_date`)
    FROM `academic_periods`
    WHERE NOT `academic_periods`.`start_date` = '0000-00-00'
	)
, `institution_sites`.`year_opened` =
	(
		SELECT `academic_periods`.`start_year`
    FROM `academic_periods` 
    WHERE NOT `academic_periods`.`start_year` = 0
    HAVING MIN(`academic_periods`.`start_date`)
  )
WHERE `institution_sites`.`date_opened` = '0000-00-00';

DROP TABLE IF EXISTS `z1407_staff_leaves`;
DROP TABLE IF EXISTS `z1407_staff_licenses`;
DROP TABLE IF EXISTS `z1407_staff_memberships`;
DROP TABLE IF EXISTS `z1407_staff_qualifications`;
DROP TABLE IF EXISTS `z1407_staff_salaries`;
DROP TABLE IF EXISTS `z1407_staff_training`;
DROP TABLE IF EXISTS `z1407_staff_training_needs`;
DROP TABLE IF EXISTS `z1407_staff_training_self_studies`;
DROP TABLE IF EXISTS `z1407_student_activities`;
DROP TABLE IF EXISTS `z1407_student_attachments`;
DROP TABLE IF EXISTS `z1407_student_attendances`;
DROP TABLE IF EXISTS `z1407_student_bank_accounts`;
DROP TABLE IF EXISTS `z1407_student_behaviours`;
DROP TABLE IF EXISTS `z1407_student_custom_values`;
DROP TABLE IF EXISTS `z1407_student_custom_value_history`;
DROP TABLE IF EXISTS `z1407_student_details_custom_values`;
DROP TABLE IF EXISTS `z1407_student_extracurriculars`;
DROP TABLE IF EXISTS `z1407_student_fees`;
DROP TABLE IF EXISTS `z1407_student_guardians`;
DROP TABLE IF EXISTS `z1407_student_healths`;
DROP TABLE IF EXISTS `z1407_student_health_allergies`;
DROP TABLE IF EXISTS `z1407_student_health_consultations`;
DROP TABLE IF EXISTS `z1407_student_health_families`;
DROP TABLE IF EXISTS `z1407_student_health_histories`;
DROP TABLE IF EXISTS `z1407_student_health_immunizations`;
DROP TABLE IF EXISTS `z1407_student_health_medications`;
DROP TABLE IF EXISTS `z1407_student_health_tests`;
DROP TABLE IF EXISTS `z1407_training_session_trainees`;
DROP TABLE IF EXISTS `z_1407_wf_workflows`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_actions`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_comments`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_models`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_records`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_steps`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_step_roles`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_submodels`;
DROP TABLE IF EXISTS `z_1407_wf_workflow_transitions`;
DROP TABLE IF EXISTS `z_1407_workflows`;
DROP TABLE IF EXISTS `z_1407_workflow_logs`;
DROP TABLE IF EXISTS `z_1407_workflow_steps`;
DROP TABLE IF EXISTS `z_1458_institution_site_classes`;
DROP TABLE IF EXISTS `z_1458_institution_site_sections`;
DROP TABLE IF EXISTS `z_1458_institution_site_section_staff`;
DROP TABLE IF EXISTS `z_1716_field_option_values`;
DROP TABLE IF EXISTS `z_1716_institution_site_section_students`;
DROP TABLE IF EXISTS `z_1825_security_user_types`;
DROP TABLE IF EXISTS `z_1878_assessment_item_results`;
DROP TABLE IF EXISTS `z_1461_assessment_results`;
DROP TABLE IF EXISTS `z_1461_student_details_custom_fields`;
DROP TABLE IF EXISTS `z_1461_student_details_custom_field_options`;
DROP TABLE IF EXISTS `z_1461_student_details_custom_values`;
DROP TABLE IF EXISTS `z_1461_staff_details_custom_fields`;
DROP TABLE IF EXISTS `z_1461_staff_details_custom_field_options`;
DROP TABLE IF EXISTS `z_1461_staff_details_custom_values`;
DROP TABLE IF EXISTS `z_1461_quality_statuses`;
DROP TABLE IF EXISTS `z_1461_quality_status_periods`;
DROP TABLE IF EXISTS `z_1461_quality_status_programmes`;
DROP TABLE IF EXISTS `z_1461_quality_status_roles`;
DROP TABLE IF EXISTS `z_1461_institution_site_custom_value_history`;
DROP TABLE IF EXISTS `z_1461_staff_custom_value_history`;
DROP TABLE IF EXISTS `z_1461_student_custom_value_history`;
DROP TABLE IF EXISTS `z_1916_academic_periods`;

UPDATE `config_items` SET `value` = '3.2.4' WHERE `code` = 'db_version';
