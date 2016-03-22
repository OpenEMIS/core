INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 'StudentCategories', 'Categories', 'Student', NULL, 14, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Promoted or New Enrolment', 1, 0, 1, 0, NULL, NULL, (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Promoted (Transferred in)', 2, 0, 1, 0, NULL, NULL, (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Repeated', 3, 1, 0, 0, NULL, NULL, (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Repeated (Transferred in)', 4, 0, 1, 0, NULL, NULL, (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Promoted', 5, 0, 1, 0, NULL, NULL, (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 'Genders', 'Gender', 'Student', NULL, 15, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Male', 1, 1, 1, 0, NULL, NULL, (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Female', 2, 1, 1, 0, NULL, NULL, (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 'StudentStatuses', 'Status', 'Student', NULL, 16, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Current', 1, 1, 0, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Transferred', 2, 1, 0, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Dropout', 3, 1, 0, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Expelled', 4, 1, 0, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Graduated', 5, 1, 0, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'FinanceNatures', 'Nature', 'Finance', '{"model":"FinanceNature"}', 29, 0, NULL, NULL, 1, NOW()),
(NULL, 'FinanceTypes', 'Types', 'Finance', '{"model":"FinanceType"}', 30, 0, NULL, NULL, 1, NOW()),
(NULL, 'FinanceCategories', 'Categories', 'Finance', '{"model":"FinanceCategory"}', 31, 0, NULL, NULL, 1, NOW()),
(NULL, 'FinanceSources', 'Source', 'Finance', '{"model":"FinanceSource"}', 32, 0, NULL, NULL, 1, NOW());


INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'HealthAllergyTypes', 'Allergy Types', 'Health', NULL, 36, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Milk Allergy', 1, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Egg Allergy', 2, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Peanut Allergy', 3, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Shellfish Allergy', 4, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Casein Allergy', 5, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Sulfite Allergy', 6, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Pollen Allergy', 7, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Aspirin Allergy', 8, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'HealthConditions', 'Conditions', 'Health', NULL, 37, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Asthma Attack', 1, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Diabetes', 2, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Tuberculosis', 3, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Epilepsy', 4, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Stroke', 5, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Blood Disorder', 6, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());


INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'HealthConsultationTypes', 'Consultation Types', 'Health', NULL, 38, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Normal Consultation', 1, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Psycho-social', 0, 1, 1, 0, 'PSY', 'PSY', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'HealthImmunizations', 'Immunization', 'Health', NULL, 39, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Hepattities B vaccine', 1, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Diphtheria vaccine', 2, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Tetanus vaccine', 3, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());


INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'HealthRelationships', 'Relationships', 'Health', NULL, 40, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Mother', 1, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Father', 2, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Grandmother', 3, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Grandfather', 4, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Sister', 5, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Brother', 6, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Aunty', 7, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Uncle', 8, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Cousin', 9, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'HealthTestTypes', 'Test Types', 'Health', NULL, 41, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Vision Screening', 1, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('3D Vision Screening', 2, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Growth & Development Assessment', 3, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Hearing Screening', 4, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Spinal Screening', 5, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'SanitationGenders', 'Sanitation Gender', 'Infrastructure', NULL, 50, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Male', 1, 1, 0, 0, 'male', 'male', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Female', 2, 1, 0, 0, 'female', 'female', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('Unisex', 3, 1, 0, 0, 'unisex', 'unisex', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());

INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Staff', 'StaffPositionSteps', 'Steps', 'Position', NULL, 54, 0, NULL, NULL, 1, NOW());

INSERT INTO `field_option_values` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `field_option_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('1', 0, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW()),
('2', 0, 1, 1, 0, '', '', (SELECT MAX(id) from field_options), NULL, NULL, 1, NOW());


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
UPDATE field_options SET plugin = NULL WHERE code = 'Countries';
UPDATE field_options SET plugin = 'Students' WHERE code = 'StudentTransferReasons';

UPDATE field_options SET visible = 1 WHERE code = 'GuardianEducationLevels';

UPDATE field_options SET visible = 1 WHERE parent = 'Training';




DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1904';
