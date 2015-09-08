INSERT INTO `db_patches` VALUES ('PHPOE-1904');

DELETE from field_options where code = 'SanitationGenders' and name = 'Sanitation Gender' and parent = 'Infrastructure';

DELETE from field_option_values where name = 'Male' and field_option_id = 50;
DELETE from field_option_values where name = 'Female' and field_option_id = 50;
DELETE from field_option_values where name = 'Unisex' and field_option_id = 50;

DELETE from field_options where code = 'StaffPositionSteps' and name = 'Steps' and parent = 'Position';

DELETE from field_option_values where name = '1' and field_option_id = 54;
DELETE from field_option_values where name = '2' and field_option_id = 54;

DELETE from field_options where plugin = 'Students' and code = 'StudentCategories' and name = 'Categories' and parent = 'Student';

DELETE from field_option_values where name = 'Promoted or New Enrolment' and field_option_id = 14;
DELETE from field_option_values where name = 'Promoted (Transferred in)' and field_option_id = 14;
DELETE from field_option_values where name = 'Repeated' and field_option_id = 14;
DELETE from field_option_values where name = 'Repeated (Transferred in)' and field_option_id = 14;
DELETE from field_option_values where name = 'Promoted' and field_option_id = 14;

DELETE from field_options where plugin = 'Students' and code = 'Genders' and name = 'Gender' and parent = 'Student';

DELETE from field_option_values where name = 'Male' and field_option_id = 15;
DELETE from field_option_values where name = 'Female' and field_option_id = 15;

DELETE from field_options where plugin = 'Students' and code = 'StudentStatuses' and name = 'Status' and parent = 'Student';

DELETE from field_option_values where name = 'Current' and field_option_id = 16;
DELETE from field_option_values where name = 'Transferred' and field_option_id = 16;
DELETE from field_option_values where name = 'Dropout' and field_option_id = 16;
DELETE from field_option_values where name = 'Expelled' and field_option_id = 16;
DELETE from field_option_values where name = 'Graduated' and field_option_id = 16;

DELETE from field_options where code = 'FinanceNatures' and name = 'Nature' and parent = 'Finance';
DELETE from field_options where code = 'FinanceTypes' and name = 'Types' and parent = 'Finance';
DELETE from field_options where code = 'FinanceCategories' and name = 'Categories' and parent = 'Finance';
DELETE from field_options where code = 'FinanceSources' and name = 'Source' and parent = 'Finance';

DELETE from field_options where code = 'HealthAllergyTypes' and name = 'Allergy Types' and parent = 'Health';

DELETE from field_option_values where name = 'Milk Allergy' and field_option_id = 36;
DELETE from field_option_values where name = 'Egg Allergy' and field_option_id = 36;
DELETE from field_option_values where name = 'Peanut Allergy' and field_option_id = 36;
DELETE from field_option_values where name = 'Shellfish Allergy' and field_option_id = 36;
DELETE from field_option_values where name = 'Casein Allergy' and field_option_id = 36;
DELETE from field_option_values where name = 'Sulfite Allergy' and field_option_id = 36;
DELETE from field_option_values where name = 'Pollen Allergy' and field_option_id = 36;
DELETE from field_option_values where name = 'Aspirin Allergy' and field_option_id = 36;

DELETE from field_options where code = 'HealthConditions' and name = 'Conditions' and parent = 'Health';

DELETE from field_option_values where name = 'Asthma Attack' and field_option_id = 37;
DELETE from field_option_values where name = 'Diabetes' and field_option_id = 37;
DELETE from field_option_values where name = 'Tuberculosis' and field_option_id = 37;
DELETE from field_option_values where name = 'Epilepsy' and field_option_id = 37;
DELETE from field_option_values where name = 'Stroke' and field_option_id = 37;
DELETE from field_option_values where name = 'Blood Disorder' and field_option_id = 37;

DELETE from field_options where code = 'HealthConsultationTypes' and name = 'Consultation Types' and parent = 'Health';

DELETE from field_option_values where name = 'Normal Consultation' and field_option_id = 38;
DELETE from field_option_values where name = 'Psycho-social' and field_option_id = 38;

DELETE from field_options where code = 'HealthImmunizations' and name = 'Immunization' and parent = 'Health';

DELETE from field_option_values where name = 'Hepattities B vaccine' and field_option_id = 39;
DELETE from field_option_values where name = 'Diphtheria vaccine' and field_option_id = 39;
DELETE from field_option_values where name = 'Tetanus vaccine' and field_option_id = 39;

DELETE from field_options where code = 'HealthRelationships' and name = 'Relationships' and parent = 'Health';

DELETE from field_option_values where name = 'Mother' and field_option_id = 40;
DELETE from field_option_values where name = 'Father' and field_option_id = 40;
DELETE from field_option_values where name = 'Grandmother' and field_option_id = 40;
DELETE from field_option_values where name = 'Grandfather' and field_option_id = 40;
DELETE from field_option_values where name = 'Sister' and field_option_id = 40;
DELETE from field_option_values where name = 'Brother' and field_option_id = 40;
DELETE from field_option_values where name = 'Aunty' and field_option_id = 40;
DELETE from field_option_values where name = 'Uncle' and field_option_id = 40;
DELETE from field_option_values where name = 'Cousin' and field_option_id = 40;

DELETE from field_options where code = 'HealthTestTypes' and name = 'Test Types' and parent = 'Health';

DELETE from field_option_values where name = 'Vision Screening' and field_option_id = 41;
DELETE from field_option_values where name = '3D Vision Screening' and field_option_id = 41;
DELETE from field_option_values where name = 'Growth & Development Assessment' and field_option_id = 41;
DELETE from field_option_values where name = 'Hearing Screening' and field_option_id = 41;
DELETE from field_option_values where name = 'Spinal Screening' and field_option_id = 41;

UPDATE field_options SET plugin = 'Institution', code = 'Genders' WHERE code = 'InstitutionSiteGenders';
UPDATE field_options SET plugin = 'Institution', code = 'Localities' WHERE code = 'InstitutionSiteLocalities';
UPDATE field_options SET plugin = 'Institution', code = 'Ownerships' WHERE code = 'InstitutionSiteOwnerships';
UPDATE field_options SET plugin = 'Institution', code = 'Sectors' WHERE code = 'InstitutionSiteSectors';
UPDATE field_options SET plugin = 'Institution', code = 'Statuses' WHERE code = 'InstitutionSiteStatuses';
UPDATE field_options SET plugin = 'Institution', code = 'Types' WHERE code = 'InstitutionSiteTypes';
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



