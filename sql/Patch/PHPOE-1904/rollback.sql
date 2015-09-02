UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteGenders' WHERE plugin = 'Institution' and code = 'Genders';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteLocalities' WHERE plugin = 'Institution' and code = 'Localities';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteOwnerships' WHERE plugin = 'Institution' and code = 'Ownerships';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteSectors' WHERE plugin = 'Institution' and code = 'Sectors';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteStatuses' WHERE plugin = 'Institution' and code = 'Statuses';
UPDATE field_options SET plugin = NULL, code = 'InstitutionSiteTypes' WHERE plugin = 'Institution' and code = 'Types';

UPDATE field_options SET plugin = 'Student' WHERE code = 'StudentAbsenceReasons';
UPDATE field_options SET plugin = 'Student' WHERE code = 'StudentBehaviourCategories';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffAbsenceReasons';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffBehaviourCategories';

UPDATE field_options SET plugin = 'Staff' WHERE code = 'LeaveStatuses';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffLeaveTypes';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffTypes';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffStatuses';
UPDATE field_options SET plugin = 'Staff' WHERE code = 'StaffTrainingCategories';

UPDATE field_options SET plugin = NULL WHERE code = 'Banks';
UPDATE field_options SET plugin = NULL WHERE code = 'BankBranches';

UPDATE field_options SET plugin = 'Students' WHERE code = 'GuardianRelations';

UPDATE field_options SET plugin = 'Staff' WHERE code = 'QualificationLevels';
UPDATE field_options SET plugin = 'Training' WHERE code = 'QualificationSpecialisations';
UPDATE field_options SET plugin = NULL WHERE code = 'QualityVisitTypes';
UPDATE field_options SET plugin = NULL WHERE code = 'SalaryAdditionTypes';
UPDATE field_options SET plugin = NULL WHERE code = 'SalaryDeductionTypes';

UPDATE field_options SET plugin = NULL WHERE code = 'ContactTypes';

UPDATE field_options SET plugin = 'Staff' WHERE code = 'EmploymentTypes';
UPDATE field_options SET plugin = NULL WHERE code = 'ExtracurricularTypes';
UPDATE field_options SET plugin = NULL WHERE code = 'IdentityTypes';
UPDATE field_options SET plugin = NULL WHERE code = 'LicenseTypes';
UPDATE field_options SET plugin = NULL WHERE code = 'SpecialNeedTypes';
UPDATE field_options SET plugin = NULL WHERE code = 'InfrastructureOwnerships';
UPDATE field_options SET plugin = NULL WHERE code = 'InfrastructureConditions';

UPDATE field_options SET plugin = 'Students' WHERE code = 'StudentTransferReasons';

UPDATE field_options SET visible = 1 WHERE code = 'GuardianEducationLevels';

UPDATE field_options SET visible = 1 WHERE parent = 'Training';

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 'StudentCategories', 'Categories', 'Student', NULL, 14, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
('Students', 'Genders', 'Gender', 'Student', NULL, 15, 0, NULL, NULL, 1, '2014-08-12 17:32:25'),
('Students', 'StudentStatuses', 'Status', 'Student', NULL, 16, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'FinanceNatures', 'Nature', 'Finance', '{"model":"FinanceNature"}', 29, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'FinanceTypes', 'Types', 'Finance', '{"model":"FinanceType"}', 30, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'FinanceCategories', 'Categories', 'Finance', '{"model":"FinanceCategory"}', 31, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'FinanceSources', 'Source', 'Finance', '{"model":"FinanceSource"}', 32, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'HealthAllergyTypes', 'Allergy Types', 'Health', NULL, 36, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'HealthConditions', 'Conditions', 'Health', NULL, 37, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'HealthConsultationTypes', 'Consultation Types', 'Health', NULL, 38, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'HealthImmunizations', 'Immunization', 'Health', NULL, 39, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'HealthRelationships', 'Relationships', 'Health', NULL, 40, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'HealthTestTypes', 'Test Types', 'Health', NULL, 41, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
(NULL, 'SanitationGenders', 'Sanitation Gender', 'Infrastructure', NULL, 50, 0, NULL, NULL, 1, '0000-00-00 00:00:00'),
('Staff', 'StaffPositionSteps', 'Steps', 'Position', NULL, 54, 0, NULL, NULL, 1, '0000-00-00 00:00:00');


DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1904';
