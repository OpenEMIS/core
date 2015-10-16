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



