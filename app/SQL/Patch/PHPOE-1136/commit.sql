-- Need to backup field_options and field_option_values
CREATE TABLE IF NOT EXISTS 1136_field_options LIKE field_options;
INSERT 1136_field_options SELECT * FROM field_options WHERE NOT EXISTS (SELECT * FROM 1136_field_options);
ALTER TABLE `1136_field_options` ADD `old_id` INT NOT NULL AFTER `id`;
ALTER TABLE `1136_field_options` CHANGE `old_id` `old_id` INT(11) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS 1136_field_option_values LIKE field_option_values;
INSERT 1136_field_option_values SELECT * FROM field_option_values WHERE NOT EXISTS (SELECT * FROM 1136_field_option_values);

-- Need to edit id of field options
CREATE TABLE IF NOT EXISTS 1136edit_field_options LIKE 1136_field_options;
TRUNCATE TABLE 1136edit_field_options;
INSERT 1136edit_field_options SELECT * FROM 1136_field_options;
UPDATE 1136edit_field_options SET code = 'TrainingResultType' WHERE code = 'TrainingCourseResultType';

DELETE FROM `1136edit_field_options` WHERE `1136edit_field_options`.`code` = 'Country';

-- Updating wrong model names
UPDATE `1136edit_field_options` SET `code` = 'StaffPositionGrade' WHERE `1136edit_field_options`.`code` = 'PositionGrade';

UPDATE `1136edit_field_options` SET `code` = 'StaffPositionStep' WHERE `1136edit_field_options`.`code` = 'PositionStep';

UPDATE `1136edit_field_options` SET `code` = 'StaffPositionTitle' WHERE `1136edit_field_options`.`code` = 'PositionTitle';

-- FIELD OPTIONS
-- https://docs.google.com/a/kordit.com/spreadsheets/d/1X0zyO6eOwu5wccNWMgKhi34HJyIMDJ_45oLYMe9hEN8/edit#gid=548941468
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'InstitutionSiteType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'InstitutionSiteOwnership' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'InstitutionSiteLocality' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'InstitutionSiteStatus' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'AssessmentResultType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'EmploymentType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'ExtracurricularType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'Language' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'IdentityType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'LicenseType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'SpecialNeedType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'QualityVisitType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'HealthRelationship' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'HealthCondition' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'HealthImmunization' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'HealthAllergyType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'HealthTestType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'HealthConsultationType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'SalaryAdditionType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'SalaryDeductionType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingCourseType' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingFieldStudy' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingLevel' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingModeDelivery' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingPriority' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingProvider' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingRequirement' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'TrainingStatus' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'StudentCategory' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'StudentBehaviourCategory' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'StaffPositionTitle' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'StaffPositionGrade' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'StaffPositionStep' LIMIT 1;
UPDATE 1136edit_field_options SET params = NULL WHERE code = 'QualificationSpecialisation' LIMIT 1;

-- rename field option names and parents
UPDATE 1136edit_field_options SET parent = 'Finance' WHERE code = 'Bank';	
UPDATE 1136edit_field_options SET parent = 'Finance' WHERE code = 'BankBranch';	
UPDATE 1136edit_field_options SET parent = 'Others' WHERE code = 'ContactType';	
UPDATE 1136edit_field_options SET parent = 'Others' WHERE code = 'EmploymentType';	
UPDATE 1136edit_field_options SET parent = 'Others' WHERE code = 'ExtracurricularType';	
UPDATE 1136edit_field_options SET parent = 'Others' WHERE code = 'IdentityType';	
UPDATE 1136edit_field_options SET parent = 'Others' WHERE code = 'Language';	
UPDATE 1136edit_field_options SET parent = 'Others' WHERE code = 'LicenseType';	
UPDATE 1136edit_field_options SET parent = 'Others' WHERE code = 'SpecialNeedType';
-- Rename field option Name SQL
UPDATE 1136edit_field_options SET name = 'Bank Branches' WHERE code = 'BankBranch';
UPDATE 1136edit_field_options SET name = 'Contact Types' WHERE code = 'ContactType';
UPDATE 1136edit_field_options SET name = 'Employment Types' WHERE code = 'EmploymentType';
UPDATE 1136edit_field_options SET name = 'Extracurricular Types' WHERE code = 'ExtracurricularType';	
	
-- drop create insert field_options
DROP TABLE field_options;
CREATE TABLE `field_options` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `old_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent` varchar(50) DEFAULT NULL,
  `params` text,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


-- reinserting in order
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteGender' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteLocality' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteOwnership' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteProvider' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteSector' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteStatus' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InstitutionSiteCustomField' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'CensusCustomFieldOption' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'CensusCustomField' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'CensusGrid' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StudentAbsenceReason' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StudentBehaviourCategory' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StudentCategory' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'Gender' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StudentStatus' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StudentCustomField' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffAbsenceReason' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffBehaviourCategory' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'LeaveStatus' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffLeaveType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffStatus' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffTrainingCategory' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffCustomField' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'AssessmentResultType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'Bank' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'BankBranch' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'FinanceNature' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'FinanceType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'FinanceCategory' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'FinanceSource' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'FeeType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'GuardianEducationLevel' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'GuardianRelation' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'HealthAllergyType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'HealthCondition' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'HealthConsultationType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'HealthImmunization' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'HealthRelationship' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'HealthTestType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureBuilding' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureCategory' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureEnergy' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureFurniture' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureMaterial' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureResource' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureRoom' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureSanitation' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'SanitationGender' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureStatus' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'InfrastructureWater' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffPositionGrade' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffPositionStep' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'StaffPositionTitle' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'QualificationLevel' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'QualificationSpecialisation' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'QualityVisitType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'SalaryAdditionType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'SalaryDeductionType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingAchievementType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingCourseType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingFieldStudy' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingLevel' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingModeDelivery' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingNeedCategory' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingPriority' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingProvider' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingRequirement' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingResultType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'TrainingStatus' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'ContactType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'EmploymentType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'ExtracurricularType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'IdentityType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'Language' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'LicenseType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'SpecialNeedType' ORDER BY id ASC LIMIT 1;
INSERT field_options SELECT * FROM 1136edit_field_options WHERE code = 'SchoolYear' ORDER BY id ASC LIMIT 1;
UPDATE field_options SET field_options.old_id = field_options.id;


SET @count = 0; UPDATE field_options SET id = id+1000;
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteGender';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteLocality';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteOwnership';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteProvider';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteSector';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteStatus';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InstitutionSiteCustomField';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'CensusCustomFieldOption';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'CensusCustomField';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'CensusGrid';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StudentAbsenceReason';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StudentBehaviourCategory';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StudentCategory';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'Gender';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StudentStatus';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StudentCustomField';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffAbsenceReason';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffBehaviourCategory';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'LeaveStatus';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffLeaveType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffStatus';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffTrainingCategory';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffCustomField';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'AssessmentResultType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'Bank';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'BankBranch';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'FinanceNature';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'FinanceType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'FinanceCategory';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'FinanceSource';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'FeeType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'GuardianEducationLevel';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'GuardianRelation';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'HealthAllergyType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'HealthCondition';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'HealthConsultationType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'HealthImmunization';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'HealthRelationship';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'HealthTestType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureBuilding';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureCategory';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureEnergy';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureFurniture';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureMaterial';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureResource';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureRoom';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureSanitation';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'SanitationGender';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureStatus';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'InfrastructureWater';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffPositionGrade';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffPositionStep';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'StaffPositionTitle';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'QualificationLevel';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'QualificationSpecialisation';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'QualityVisitType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'SalaryAdditionType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'SalaryDeductionType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingAchievementType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingCourseType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingFieldStudy';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingLevel';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingModeDelivery';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingNeedCategory';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingPriority';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingProvider';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingRequirement';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingResultType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'TrainingStatus';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'ContactType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'EmploymentType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'ExtracurricularType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'IdentityType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'Language';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'LicenseType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'SpecialNeedType';
UPDATE field_options SET field_options.id = @count:= @count + 1 where code = 'SchoolYear';

UPDATE field_options SET field_options.order = field_options.id;
-- end field options




-- https://docs.google.com/a/kordit.com/spreadsheets/d/1X0zyO6eOwu5wccNWMgKhi34HJyIMDJ_45oLYMe9hEN8/edit#gid=1574144139
-- firstly... must change existing old IDs to new IDs
ALTER TABLE `quality_institution_visits` CHANGE `quality_type_id` `quality_visit_type_id` INT(11) NOT NULL;

UPDATE field_option_values SET old_id = null WHERE 1;

UPDATE field_option_values INNER JOIN field_options ON field_option_values.field_option_id = field_options.old_id SET field_option_values.field_option_id = field_options.id;


-- renaming old tables
 -- need to move these tables over
-- field_option_values.field_option_id, 

RENAME TABLE institution_site_types to 1136_institution_site_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'InstitutionSiteType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_institution_site_types.id, 1136_institution_site_types.name, 1136_institution_site_types.order, 1136_institution_site_types.visible, 1136_institution_site_types.international_code, 1136_institution_site_types.national_code, 1136_institution_site_types.modified_user_id, 1136_institution_site_types.modified, 1136_institution_site_types.created_user_id, 1136_institution_site_types.created, @fieldOptionId FROM 1136_institution_site_types;

RENAME TABLE institution_site_ownership to 1136_institution_site_ownership;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'InstitutionSiteOwnership'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_institution_site_ownership.id, 1136_institution_site_ownership.name, 1136_institution_site_ownership.order, 1136_institution_site_ownership.visible, 1136_institution_site_ownership.international_code, 1136_institution_site_ownership.national_code, 1136_institution_site_ownership.modified_user_id, 1136_institution_site_ownership.modified, 1136_institution_site_ownership.created_user_id, 1136_institution_site_ownership.created, @fieldOptionId FROM 1136_institution_site_ownership;

RENAME TABLE institution_site_localities to 1136_institution_site_localities;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'InstitutionSiteLocality'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_institution_site_localities.id, 1136_institution_site_localities.name, 1136_institution_site_localities.order, 1136_institution_site_localities.visible, 1136_institution_site_localities.international_code, 1136_institution_site_localities.national_code, 1136_institution_site_localities.modified_user_id, 1136_institution_site_localities.modified, 1136_institution_site_localities.created_user_id, 1136_institution_site_localities.created, @fieldOptionId FROM 1136_institution_site_localities;

RENAME TABLE institution_site_statuses to 1136_institution_site_statuses;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'InstitutionSiteStatus'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_institution_site_statuses.id, 1136_institution_site_statuses.name, 1136_institution_site_statuses.order, 1136_institution_site_statuses.visible, 1136_institution_site_statuses.international_code, 1136_institution_site_statuses.national_code, 1136_institution_site_statuses.modified_user_id, 1136_institution_site_statuses.modified, 1136_institution_site_statuses.created_user_id, 1136_institution_site_statuses.created, @fieldOptionId FROM 1136_institution_site_statuses;

RENAME TABLE assessment_result_types to 1136_assessment_result_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'AssessmentResultType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_assessment_result_types.id, 1136_assessment_result_types.name, 1136_assessment_result_types.order, 1136_assessment_result_types.visible, 1136_assessment_result_types.international_code, 1136_assessment_result_types.national_code, 1136_assessment_result_types.modified_user_id, 1136_assessment_result_types.modified, 1136_assessment_result_types.created_user_id, 1136_assessment_result_types.created, @fieldOptionId FROM 1136_assessment_result_types;

RENAME TABLE employment_types to 1136_employment_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'EmploymentType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_employment_types.id, 1136_employment_types.name, 1136_employment_types.order, 1136_employment_types.visible, 1136_employment_types.international_code, 1136_employment_types.national_code, 1136_employment_types.modified_user_id, 1136_employment_types.modified, 1136_employment_types.created_user_id, 1136_employment_types.created, @fieldOptionId FROM 1136_employment_types;

RENAME TABLE extracurricular_types to 1136_extracurricular_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'ExtracurricularType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_extracurricular_types.id, 1136_extracurricular_types.name, 1136_extracurricular_types.order, 1136_extracurricular_types.visible, 1136_extracurricular_types.international_code, 1136_extracurricular_types.national_code, 1136_extracurricular_types.modified_user_id, 1136_extracurricular_types.modified, 1136_extracurricular_types.created_user_id, 1136_extracurricular_types.created, @fieldOptionId FROM 1136_extracurricular_types;

RENAME TABLE languages to 1136_languages;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'Language'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_languages.id, 1136_languages.name, 1136_languages.order, 1136_languages.visible, 1136_languages.international_code, 1136_languages.national_code, 1136_languages.modified_user_id, 1136_languages.modified, 1136_languages.created_user_id, 1136_languages.created, @fieldOptionId FROM 1136_languages;

RENAME TABLE identity_types to 1136_identity_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'IdentityType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_identity_types.id, 1136_identity_types.name, 1136_identity_types.order, 1136_identity_types.visible, 1136_identity_types.international_code, 1136_identity_types.national_code, 1136_identity_types.modified_user_id, 1136_identity_types.modified, 1136_identity_types.created_user_id, 1136_identity_types.created, @fieldOptionId FROM 1136_identity_types;

RENAME TABLE license_types to 1136_license_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'LicenseType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_license_types.id, 1136_license_types.name, 1136_license_types.order, 1136_license_types.visible, 1136_license_types.international_code, 1136_license_types.national_code, 1136_license_types.modified_user_id, 1136_license_types.modified, 1136_license_types.created_user_id, 1136_license_types.created, @fieldOptionId FROM 1136_license_types;

RENAME TABLE special_need_types to 1136_special_need_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'SpecialNeedType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_special_need_types.id, 1136_special_need_types.name, 1136_special_need_types.order, 1136_special_need_types.visible, 1136_special_need_types.international_code, 1136_special_need_types.national_code, 1136_special_need_types.modified_user_id, 1136_special_need_types.modified, 1136_special_need_types.created_user_id, 1136_special_need_types.created, @fieldOptionId FROM 1136_special_need_types;

RENAME TABLE quality_visit_types to 1136_quality_visit_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'QualityVisitType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_quality_visit_types.id, 1136_quality_visit_types.name, 1136_quality_visit_types.order, 1136_quality_visit_types.visible, 1136_quality_visit_types.international_code, 1136_quality_visit_types.national_code, 1136_quality_visit_types.modified_user_id, 1136_quality_visit_types.modified, 1136_quality_visit_types.created_user_id, 1136_quality_visit_types.created, @fieldOptionId FROM 1136_quality_visit_types;

RENAME TABLE health_relationships to 1136_health_relationships;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'HealthRelationship'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_health_relationships.id, 1136_health_relationships.name, 1136_health_relationships.order, 1136_health_relationships.visible, 1136_health_relationships.international_code, 1136_health_relationships.national_code, 1136_health_relationships.modified_user_id, 1136_health_relationships.modified, 1136_health_relationships.created_user_id, 1136_health_relationships.created, @fieldOptionId FROM 1136_health_relationships;

RENAME TABLE health_conditions to 1136_health_conditions;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'HealthCondition'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_health_conditions.id, 1136_health_conditions.name, 1136_health_conditions.order, 1136_health_conditions.visible, 1136_health_conditions.international_code, 1136_health_conditions.national_code, 1136_health_conditions.modified_user_id, 1136_health_conditions.modified, 1136_health_conditions.created_user_id, 1136_health_conditions.created, @fieldOptionId FROM 1136_health_conditions;

RENAME TABLE health_immunizations to 1136_health_immunizations;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'HealthImmunization'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_health_immunizations.id, 1136_health_immunizations.name, 1136_health_immunizations.order, 1136_health_immunizations.visible, 1136_health_immunizations.international_code, 1136_health_immunizations.national_code, 1136_health_immunizations.modified_user_id, 1136_health_immunizations.modified, 1136_health_immunizations.created_user_id, 1136_health_immunizations.created, @fieldOptionId FROM 1136_health_immunizations;

RENAME TABLE health_allergy_types to 1136_health_allergy_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'HealthAllergyType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_health_allergy_types.id, 1136_health_allergy_types.name, 1136_health_allergy_types.order, 1136_health_allergy_types.visible, 1136_health_allergy_types.international_code, 1136_health_allergy_types.national_code, 1136_health_allergy_types.modified_user_id, 1136_health_allergy_types.modified, 1136_health_allergy_types.created_user_id, 1136_health_allergy_types.created, @fieldOptionId FROM 1136_health_allergy_types;

RENAME TABLE health_test_types to 1136_health_test_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'HealthTestType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_health_test_types.id, 1136_health_test_types.name, 1136_health_test_types.order, 1136_health_test_types.visible, 1136_health_test_types.international_code, 1136_health_test_types.national_code, 1136_health_test_types.modified_user_id, 1136_health_test_types.modified, 1136_health_test_types.created_user_id, 1136_health_test_types.created, @fieldOptionId FROM 1136_health_test_types;

RENAME TABLE health_consultation_types to 1136_health_consultation_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'HealthConsultationType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_health_consultation_types.id, 1136_health_consultation_types.name, 1136_health_consultation_types.order, 1136_health_consultation_types.visible, 1136_health_consultation_types.international_code, 1136_health_consultation_types.national_code, 1136_health_consultation_types.modified_user_id, 1136_health_consultation_types.modified, 1136_health_consultation_types.created_user_id, 1136_health_consultation_types.created, @fieldOptionId FROM 1136_health_consultation_types;

RENAME TABLE salary_addition_types to 1136_salary_addition_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'SalaryAdditionType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_salary_addition_types.id, 1136_salary_addition_types.name, 1136_salary_addition_types.order, 1136_salary_addition_types.visible, 1136_salary_addition_types.international_code, 1136_salary_addition_types.national_code, 1136_salary_addition_types.modified_user_id, 1136_salary_addition_types.modified, 1136_salary_addition_types.created_user_id, 1136_salary_addition_types.created, @fieldOptionId FROM 1136_salary_addition_types;

RENAME TABLE salary_deduction_types to 1136_salary_deduction_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'SalaryDeductionType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_salary_deduction_types.id, 1136_salary_deduction_types.name, 1136_salary_deduction_types.order, 1136_salary_deduction_types.visible, 1136_salary_deduction_types.international_code, 1136_salary_deduction_types.national_code, 1136_salary_deduction_types.modified_user_id, 1136_salary_deduction_types.modified, 1136_salary_deduction_types.created_user_id, 1136_salary_deduction_types.created, @fieldOptionId FROM 1136_salary_deduction_types;

RENAME TABLE training_course_types to 1136_training_course_types;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingCourseType'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_course_types.id, 1136_training_course_types.name, 1136_training_course_types.order, 1136_training_course_types.visible, 1136_training_course_types.international_code, 1136_training_course_types.national_code, 1136_training_course_types.modified_user_id, 1136_training_course_types.modified, 1136_training_course_types.created_user_id, 1136_training_course_types.created, @fieldOptionId FROM 1136_training_course_types;

RENAME TABLE training_field_studies to 1136_training_field_studies;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingFieldStudy'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_field_studies.id, 1136_training_field_studies.name, 1136_training_field_studies.order, 1136_training_field_studies.visible, 1136_training_field_studies.international_code, 1136_training_field_studies.national_code, 1136_training_field_studies.modified_user_id, 1136_training_field_studies.modified, 1136_training_field_studies.created_user_id, 1136_training_field_studies.created, @fieldOptionId FROM 1136_training_field_studies;

RENAME TABLE training_levels to 1136_training_levels;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingLevel'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_levels.id, 1136_training_levels.name, 1136_training_levels.order, 1136_training_levels.visible, 1136_training_levels.international_code, 1136_training_levels.national_code, 1136_training_levels.modified_user_id, 1136_training_levels.modified, 1136_training_levels.created_user_id, 1136_training_levels.created, @fieldOptionId FROM 1136_training_levels;

RENAME TABLE training_mode_deliveries to 1136_training_mode_deliveries;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingModeDelivery'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_mode_deliveries.id, 1136_training_mode_deliveries.name, 1136_training_mode_deliveries.order, 1136_training_mode_deliveries.visible, 1136_training_mode_deliveries.international_code, 1136_training_mode_deliveries.national_code, 1136_training_mode_deliveries.modified_user_id, 1136_training_mode_deliveries.modified, 1136_training_mode_deliveries.created_user_id, 1136_training_mode_deliveries.created, @fieldOptionId FROM 1136_training_mode_deliveries;

RENAME TABLE training_priorities to 1136_training_priorities;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingPriority'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_priorities.id, 1136_training_priorities.name, 1136_training_priorities.order, 1136_training_priorities.visible, 1136_training_priorities.international_code, 1136_training_priorities.national_code, 1136_training_priorities.modified_user_id, 1136_training_priorities.modified, 1136_training_priorities.created_user_id, 1136_training_priorities.created, @fieldOptionId FROM 1136_training_priorities;

RENAME TABLE training_providers to 1136_training_providers;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingProvider'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_providers.id, 1136_training_providers.name, 1136_training_providers.order, 1136_training_providers.visible, 1136_training_providers.international_code, 1136_training_providers.national_code, 1136_training_providers.modified_user_id, 1136_training_providers.modified, 1136_training_providers.created_user_id, 1136_training_providers.created, @fieldOptionId FROM 1136_training_providers;

RENAME TABLE training_requirements to 1136_training_requirements;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingRequirement'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_requirements.id, 1136_training_requirements.name, 1136_training_requirements.order, 1136_training_requirements.visible, 1136_training_requirements.international_code, 1136_training_requirements.national_code, 1136_training_requirements.modified_user_id, 1136_training_requirements.modified, 1136_training_requirements.created_user_id, 1136_training_requirements.created, @fieldOptionId FROM 1136_training_requirements;

RENAME TABLE training_statuses to 1136_training_statuses;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'TrainingStatus'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_training_statuses.id, 1136_training_statuses.name, 1136_training_statuses.order, 1136_training_statuses.visible, 1136_training_statuses.international_code, 1136_training_statuses.national_code, 1136_training_statuses.modified_user_id, 1136_training_statuses.modified, 1136_training_statuses.created_user_id, 1136_training_statuses.created, @fieldOptionId FROM 1136_training_statuses;

RENAME TABLE student_categories to 1136_student_categories;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'StudentCategory'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_student_categories.id, 1136_student_categories.name, 1136_student_categories.order, 1136_student_categories.visible, 1136_student_categories.international_code, 1136_student_categories.national_code, 1136_student_categories.modified_user_id, 1136_student_categories.modified, 1136_student_categories.created_user_id, 1136_student_categories.created, @fieldOptionId FROM 1136_student_categories;

RENAME TABLE student_behaviour_categories to 1136_student_behaviour_categories;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'StudentBehaviourCategory'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_student_behaviour_categories.id, 1136_student_behaviour_categories.name, 1136_student_behaviour_categories.order, 1136_student_behaviour_categories.visible, 1136_student_behaviour_categories.international_code, 1136_student_behaviour_categories.national_code, 1136_student_behaviour_categories.modified_user_id, 1136_student_behaviour_categories.modified, 1136_student_behaviour_categories.created_user_id, 1136_student_behaviour_categories.created, @fieldOptionId FROM 1136_student_behaviour_categories;

RENAME TABLE staff_position_titles to 1136_staff_position_titles;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'StaffPositionTitle'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_staff_position_titles.id, 1136_staff_position_titles.name, 1136_staff_position_titles.order, 1136_staff_position_titles.visible, 1136_staff_position_titles.international_code, 1136_staff_position_titles.national_code, 1136_staff_position_titles.modified_user_id, 1136_staff_position_titles.modified, 1136_staff_position_titles.created_user_id, 1136_staff_position_titles.created, @fieldOptionId FROM 1136_staff_position_titles;

RENAME TABLE staff_position_grades to 1136_staff_position_grades;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'StaffPositionGrade'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_staff_position_grades.id, 1136_staff_position_grades.name, 1136_staff_position_grades.order, 1136_staff_position_grades.visible, 1136_staff_position_grades.international_code, 1136_staff_position_grades.national_code, 1136_staff_position_grades.modified_user_id, 1136_staff_position_grades.modified, 1136_staff_position_grades.created_user_id, 1136_staff_position_grades.created, @fieldOptionId FROM 1136_staff_position_grades;

RENAME TABLE staff_position_steps to 1136_staff_position_steps;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'StaffPositionStep'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_staff_position_steps.id, 1136_staff_position_steps.name, 1136_staff_position_steps.order, 1136_staff_position_steps.visible, 1136_staff_position_steps.international_code, 1136_staff_position_steps.national_code, 1136_staff_position_steps.modified_user_id, 1136_staff_position_steps.modified, 1136_staff_position_steps.created_user_id, 1136_staff_position_steps.created, @fieldOptionId FROM 1136_staff_position_steps;

RENAME TABLE qualification_specialisations to 1136_qualification_specialisations;	
SELECT id INTO @fieldOptionId FROM field_options WHERE code = 'QualificationSpecialisation'; 
INSERT INTO field_option_values (field_option_values.old_id, field_option_values.name, field_option_values.order, field_option_values.visible, field_option_values.international_code, field_option_values.national_code, field_option_values.modified_user_id, field_option_values.modified, field_option_values.created_user_id, field_option_values.created, field_option_values.field_option_id ) SELECT 1136_qualification_specialisations.id, 1136_qualification_specialisations.name, 1136_qualification_specialisations.order, 1136_qualification_specialisations.visible, 1136_qualification_specialisations.international_code, 1136_qualification_specialisations.national_code, 1136_qualification_specialisations.modified_user_id, 1136_qualification_specialisations.modified, 1136_qualification_specialisations.created_user_id, 1136_qualification_specialisations.created, @fieldOptionId FROM 1136_qualification_specialisations;



-- correct field option values
UPDATE institution_sites LEFT JOIN field_option_values ON institution_sites.institution_site_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_sites.institution_site_type_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteType';
UPDATE institution_site_custom_fields LEFT JOIN field_option_values ON institution_site_custom_fields.institution_site_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_custom_fields.institution_site_type_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteType';
UPDATE institution_site_history LEFT JOIN field_option_values ON institution_site_history.institution_site_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_history.institution_site_type_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteType';
UPDATE census_grids LEFT JOIN field_option_values ON census_grids.institution_site_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET census_grids.institution_site_type_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteType';
UPDATE census_custom_fields LEFT JOIN field_option_values ON census_custom_fields.institution_site_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET census_custom_fields.institution_site_type_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteType';
UPDATE institution_sites LEFT JOIN field_option_values ON institution_sites.institution_site_ownership_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_sites.institution_site_ownership_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteOwnership';
UPDATE institution_site_history LEFT JOIN field_option_values ON institution_site_history.institution_site_ownership_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_history.institution_site_ownership_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteOwnership';
UPDATE institution_sites LEFT JOIN field_option_values ON institution_sites.institution_site_locality_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_sites.institution_site_locality_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteLocality';
UPDATE institution_site_history LEFT JOIN field_option_values ON institution_site_history.institution_site_locality_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_history.institution_site_locality_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteLocality';
UPDATE institution_sites LEFT JOIN field_option_values ON institution_sites.institution_site_status_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_sites.institution_site_status_id = field_option_values.id WHERE field_options.code = 'InstitutionSiteStatus';
UPDATE assessment_item_results LEFT JOIN field_option_values ON assessment_item_results.assessment_result_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET assessment_item_results.assessment_result_type_id = field_option_values.id WHERE field_options.code = 'AssessmentResultType';
UPDATE staff_employments LEFT JOIN field_option_values ON staff_employments.employment_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_employments.employment_type_id = field_option_values.id WHERE field_options.code = 'EmploymentType';
UPDATE student_extracurriculars LEFT JOIN field_option_values ON student_extracurriculars.extracurricular_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_extracurriculars.extracurricular_type_id = field_option_values.id WHERE field_options.code = 'ExtracurricularType';
UPDATE staff_extracurriculars LEFT JOIN field_option_values ON staff_extracurriculars.extracurricular_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_extracurriculars.extracurricular_type_id = field_option_values.id WHERE field_options.code = 'ExtracurricularType';
UPDATE student_languages LEFT JOIN field_option_values ON student_languages.language_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_languages.language_id = field_option_values.id WHERE field_options.code = 'Language';
UPDATE staff_languages LEFT JOIN field_option_values ON staff_languages.language_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_languages.language_id = field_option_values.id WHERE field_options.code = 'Language';
UPDATE student_identities LEFT JOIN field_option_values ON student_identities.identity_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_identities.identity_type_id = field_option_values.id WHERE field_options.code = 'IdentityType';
UPDATE staff_identities LEFT JOIN field_option_values ON staff_identities.identity_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_identities.identity_type_id = field_option_values.id WHERE field_options.code = 'IdentityType';
UPDATE staff_licenses LEFT JOIN field_option_values ON staff_licenses.license_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_licenses.license_type_id = field_option_values.id WHERE field_options.code = 'LicenseType';
UPDATE student_special_needs LEFT JOIN field_option_values ON student_special_needs.special_need_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_special_needs.special_need_type_id = field_option_values.id WHERE field_options.code = 'SpecialNeedType';
UPDATE staff_special_needs LEFT JOIN field_option_values ON staff_special_needs.special_need_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_special_needs.special_need_type_id = field_option_values.id WHERE field_options.code = 'SpecialNeedType';
UPDATE quality_institution_visits LEFT JOIN field_option_values ON quality_institution_visits.quality_visit_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET quality_institution_visits.quality_visit_type_id = field_option_values.id WHERE field_options.code = 'QualityVisitType';
UPDATE student_health_families LEFT JOIN field_option_values ON student_health_families.health_relationship_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_health_families.health_relationship_id = field_option_values.id WHERE field_options.code = 'HealthRelationship';
UPDATE staff_health_families LEFT JOIN field_option_values ON staff_health_families.health_relationship_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_health_families.health_relationship_id = field_option_values.id WHERE field_options.code = 'HealthRelationship';
UPDATE student_health_histories LEFT JOIN field_option_values ON student_health_histories.health_condition_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_health_histories.health_condition_id = field_option_values.id WHERE field_options.code = 'HealthCondition';
UPDATE staff_health_histories LEFT JOIN field_option_values ON staff_health_histories.health_condition_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_health_histories.health_condition_id = field_option_values.id WHERE field_options.code = 'HealthCondition';
UPDATE staff_health_families LEFT JOIN field_option_values ON staff_health_families.health_condition_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_health_families.health_condition_id = field_option_values.id WHERE field_options.code = 'HealthCondition';
UPDATE student_health_families LEFT JOIN field_option_values ON student_health_families.health_condition_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_health_families.health_condition_id = field_option_values.id WHERE field_options.code = 'HealthCondition';
UPDATE student_health_immunizations LEFT JOIN field_option_values ON student_health_immunizations.health_immunization_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_health_immunizations.health_immunization_id = field_option_values.id WHERE field_options.code = 'HealthImmunization';
UPDATE staff_health_immunizations LEFT JOIN field_option_values ON staff_health_immunizations.health_immunization_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_health_immunizations.health_immunization_id = field_option_values.id WHERE field_options.code = 'HealthImmunization';
UPDATE student_health_allergies LEFT JOIN field_option_values ON student_health_allergies.health_allergy_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_health_allergies.health_allergy_type_id = field_option_values.id WHERE field_options.code = 'HealthAllergyType';
UPDATE staff_health_allergies LEFT JOIN field_option_values ON staff_health_allergies.health_allergy_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_health_allergies.health_allergy_type_id = field_option_values.id WHERE field_options.code = 'HealthAllergyType';
UPDATE student_health_tests LEFT JOIN field_option_values ON student_health_tests.health_test_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_health_tests.health_test_type_id = field_option_values.id WHERE field_options.code = 'HealthTestType';
UPDATE staff_health_tests LEFT JOIN field_option_values ON staff_health_tests.health_test_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_health_tests.health_test_type_id = field_option_values.id WHERE field_options.code = 'HealthTestType';
UPDATE student_health_consultations LEFT JOIN field_option_values ON student_health_consultations.health_consultation_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_health_consultations.health_consultation_type_id = field_option_values.id WHERE field_options.code = 'HealthConsultationType';
UPDATE staff_health_consultations LEFT JOIN field_option_values ON staff_health_consultations.health_consultation_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_health_consultations.health_consultation_type_id = field_option_values.id WHERE field_options.code = 'HealthConsultationType';
UPDATE staff_salary_additions LEFT JOIN field_option_values ON staff_salary_additions.salary_addition_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_salary_additions.salary_addition_type_id = field_option_values.id WHERE field_options.code = 'SalaryAdditionType';
UPDATE staff_salary_deductions LEFT JOIN field_option_values ON staff_salary_deductions.salary_deduction_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_salary_deductions.salary_deduction_type_id = field_option_values.id WHERE field_options.code = 'SalaryDeductionType';
UPDATE training_courses LEFT JOIN field_option_values ON training_courses.training_course_type_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_courses.training_course_type_id = field_option_values.id WHERE field_options.code = 'TrainingCourseType';
UPDATE training_courses LEFT JOIN field_option_values ON training_courses.training_field_study_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_courses.training_field_study_id = field_option_values.id WHERE field_options.code = 'TrainingFieldStudy';
UPDATE training_courses LEFT JOIN field_option_values ON training_courses.training_level_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_courses.training_level_id = field_option_values.id WHERE field_options.code = 'TrainingLevel';
UPDATE training_courses LEFT JOIN field_option_values ON training_courses.training_mode_delivery_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_courses.training_mode_delivery_id = field_option_values.id WHERE field_options.code = 'TrainingModeDelivery';
UPDATE staff_training_needs LEFT JOIN field_option_values ON staff_training_needs.training_priority_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_training_needs.training_priority_id = field_option_values.id WHERE field_options.code = 'TrainingPriority';
UPDATE training_sessions LEFT JOIN field_option_values ON training_sessions.training_provider_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_sessions.training_provider_id = field_option_values.id WHERE field_options.code = 'TrainingProvider';
UPDATE training_course_providers LEFT JOIN field_option_values ON training_course_providers.training_provider_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_course_providers.training_provider_id = field_option_values.id WHERE field_options.code = 'TrainingProvider';
UPDATE staff_training_needs LEFT JOIN field_option_values ON staff_training_needs.ref_course_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_training_needs.ref_course_id = field_option_values.id WHERE field_options.code = 'TrainingRequirement';
UPDATE training_courses LEFT JOIN field_option_values ON training_courses.training_requirement_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_courses.training_requirement_id = field_option_values.id WHERE field_options.code = 'TrainingRequirement';
UPDATE training_courses LEFT JOIN field_option_values ON training_courses.training_status_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_courses.training_status_id = field_option_values.id WHERE field_options.code = 'TrainingStatus';
UPDATE training_sessions LEFT JOIN field_option_values ON training_sessions.training_status_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_sessions.training_status_id = field_option_values.id WHERE field_options.code = 'TrainingStatus';
UPDATE training_session_results LEFT JOIN field_option_values ON training_session_results.training_status_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET training_session_results.training_status_id = field_option_values.id WHERE field_options.code = 'TrainingStatus';
UPDATE staff_training_self_study_results LEFT JOIN field_option_values ON staff_training_self_study_results.training_status_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_training_self_study_results.training_status_id = field_option_values.id WHERE field_options.code = 'TrainingStatus';
UPDATE institution_site_class_students LEFT JOIN field_option_values ON institution_site_class_students.student_category_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_class_students.student_category_id = field_option_values.id WHERE field_options.code = 'StudentCategory';
UPDATE institution_site_section_students LEFT JOIN field_option_values ON institution_site_section_students.student_category_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_section_students.student_category_id = field_option_values.id WHERE field_options.code = 'StudentCategory';
UPDATE census_students LEFT JOIN field_option_values ON census_students.student_category_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET census_students.student_category_id = field_option_values.id WHERE field_options.code = 'StudentCategory';
UPDATE student_behaviours LEFT JOIN field_option_values ON student_behaviours.student_behaviour_category_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET student_behaviours.student_behaviour_category_id = field_option_values.id WHERE field_options.code = 'StudentBehaviourCategory';
UPDATE institution_site_positions LEFT JOIN field_option_values ON institution_site_positions.staff_position_title_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_positions.staff_position_title_id = field_option_values.id WHERE field_options.code = 'StaffPositionTitle';
UPDATE institution_site_positions LEFT JOIN field_option_values ON institution_site_positions.staff_position_grade_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET institution_site_positions.staff_position_grade_id = field_option_values.id WHERE field_options.code = 'StaffPositionGrade';
UPDATE staff_qualifications LEFT JOIN field_option_values ON staff_qualifications.qualification_specialisation_id = field_option_values.old_id LEFT JOIN field_options ON field_option_values.field_option_id = field_options.id SET staff_qualifications.qualification_specialisation_id = field_option_values.id WHERE field_options.code = 'QualificationSpecialisation';

