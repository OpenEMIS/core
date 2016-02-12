DROP TABLE IF EXISTS `institutions`;

ALTER TABLE `institution_site_attachments` DROP `visible`;
ALTER TABLE `institution_site_attachments` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;


-- May 27th 1142hrs
-- For field options to work with plural alias
update field_options set code = 'InstitutionSiteGenders' where code = 'InstitutionSiteGender' limit 1;
update field_options set code = 'InstitutionSiteLocalities' where code = 'InstitutionSiteLocality' limit 1;
update field_options set code = 'InstitutionSiteOwnerships' where code = 'InstitutionSiteOwnership' limit 1;
update field_options set code = 'InstitutionSiteProviders' where code = 'InstitutionSiteProvider' limit 1;
update field_options set code = 'InstitutionSiteSectors' where code = 'InstitutionSiteSector' limit 1;
update field_options set code = 'InstitutionSiteStatuses' where code = 'InstitutionSiteStatus' limit 1;
update field_options set code = 'InstitutionSiteTypes' where code = 'InstitutionSiteType' limit 1;
update field_options set code = 'InstitutionSiteCustomFields' where code = 'InstitutionSiteCustomField' limit 1;
update field_options set code = 'CensusCustomFieldOptions' where code = 'CensusCustomFieldOption' limit 1;
update field_options set code = 'CensusCustomFields' where code = 'CensusCustomField' limit 1;
update field_options set code = 'CensusGrids' where code = 'CensusGrid' limit 1;
update field_options set code = 'StudentAbsenceReasons' where code = 'StudentAbsenceReason' limit 1;
update field_options set code = 'StudentBehaviourCategories' where code = 'StudentBehaviourCategory' limit 1;
update field_options set code = 'StudentCategories' where code = 'StudentCategory' limit 1;
update field_options set code = 'Genders' where code = 'Gender' limit 1;
update field_options set code = 'StudentStatuses' where code = 'StudentStatus' limit 1;
update field_options set code = 'StudentCustomFields' where code = 'StudentCustomField' limit 1;
update field_options set code = 'StaffAbsenceReasons' where code = 'StaffAbsenceReason' limit 1;
update field_options set code = 'StaffBehaviourCategories' where code = 'StaffBehaviourCategory' limit 1;
update field_options set code = 'LeaveStatuses' where code = 'LeaveStatus' limit 1;
update field_options set code = 'StaffLeaveTypes' where code = 'StaffLeaveType' limit 1;
update field_options set code = 'StaffTypes' where code = 'StaffType' limit 1;
update field_options set code = 'StaffStatuses' where code = 'StaffStatus' limit 1;
update field_options set code = 'StaffTrainingCategories' where code = 'StaffTrainingCategory' limit 1;
update field_options set code = 'StaffCustomFields' where code = 'StaffCustomField' limit 1;
update field_options set code = 'AsssesmentResultTypes' where code = 'AssessmentResultType' limit 1;
update field_options set code = 'Banks' where code = 'Bank' limit 1;
update field_options set code = 'BankBranches' where code = 'BankBranch' limit 1;
update field_options set code = 'FinanceNatures' where code = 'FinanceNature' limit 1;
update field_options set code = 'FinanceTypes' where code = 'FinanceType' limit 1;
update field_options set code = 'FinanceCategories' where code = 'FinanceCategory' limit 1;
update field_options set code = 'FinanceSources' where code = 'FinanceSource' limit 1;
update field_options set code = 'FeeTypes' where code = 'FeeType' limit 1;
update field_options set code = 'GuardianEducationLevels' where code = 'GuardianEducationLevel' limit 1;
update field_options set code = 'GuardianRelations' where code = 'GuardianRelation' limit 1;
update field_options set code = 'HealthAllergyTypes' where code = 'HealthAllergyType' limit 1;
update field_options set code = 'HealthConditions' where code = 'HealthCondition' limit 1;
update field_options set code = 'HealthConsultationTypes' where code = 'HealthConsultationType' limit 1;
update field_options set code = 'HealthImmunizations' where code = 'HealthImmunization' limit 1;
update field_options set code = 'HealthRelationships' where code = 'HealthRelationship' limit 1;
update field_options set code = 'HealthTestTypes' where code = 'HealthTestType' limit 1;
update field_options set code = 'InfrastructureBuildings' where code = 'InfrastructureBuilding' limit 1;
update field_options set code = 'InfrastructureCategories' where code = 'InfrastructureCategory' limit 1;
update field_options set code = 'InfrastructureEnergies' where code = 'InfrastructureEnergy' limit 1;
update field_options set code = 'InfrastructureFurnitures' where code = 'InfrastructureFurniture' limit 1;
update field_options set code = 'InfrastructureMaterials' where code = 'InfrastructureMaterial' limit 1;
update field_options set code = 'InfrastructureResources' where code = 'InfrastructureResource' limit 1;
update field_options set code = 'InfrastructureRooms' where code = 'InfrastructureRoom' limit 1;
update field_options set code = 'InfrastructureSanitations' where code = 'InfrastructureSanitation' limit 1;
update field_options set code = 'SanitationGenders' where code = 'SanitationGender' limit 1;
update field_options set code = 'InfrastructureStatuses' where code = 'InfrastructureStatus' limit 1;
update field_options set code = 'InfrastructureWaters' where code = 'InfrastructureWater' limit 1;
update field_options set code = 'StaffPositionGrades' where code = 'StaffPositionGrade' limit 1;
update field_options set code = 'StaffPositionSteps' where code = 'StaffPositionStep' limit 1;
update field_options set code = 'StaffPositionTitles' where code = 'StaffPositionTitle' limit 1;
update field_options set code = 'QualificationLevels' where code = 'QualificationLevel' limit 1;
update field_options set code = 'QualificationSpecialisations' where code = 'QualificationSpecialisation' limit 1;
update field_options set code = 'QualityVisitTypes' where code = 'QualityVisitType' limit 1;
update field_options set code = 'SalaryAdditionTypes' where code = 'SalaryAdditionType' limit 1;
update field_options set code = 'SalaryDeductionTypes' where code = 'SalaryDeductionType' limit 1;
update field_options set code = 'TrainingAchievementTypes' where code = 'TrainingAchievementType' limit 1;
update field_options set code = 'TrainingCourseTypes' where code = 'TrainingCourseType' limit 1;
update field_options set code = 'TrainingFieldStudies' where code = 'TrainingFieldStudy' limit 1;
update field_options set code = 'TrainingLevels' where code = 'TrainingLevel' limit 1;
update field_options set code = 'TrainingModeDeliveries' where code = 'TrainingModeDelivery' limit 1;
update field_options set code = 'TrainingNeedCategories' where code = 'TrainingNeedCategory' limit 1;
update field_options set code = 'TrainingPriorities' where code = 'TrainingPriority' limit 1;
update field_options set code = 'TrainingProviders' where code = 'TrainingProvider' limit 1;
update field_options set code = 'TrainingRequirements' where code = 'TrainingRequirement' limit 1;
update field_options set code = 'TrainingResultTypes' where code = 'TrainingResultType' limit 1;
update field_options set code = 'TrainingStatuses' where code = 'TrainingStatus' limit 1;
update field_options set code = 'ContactTypes' where code = 'ContactType' limit 1;
update field_options set code = 'EmploymentTypes' where code = 'EmploymentType' limit 1;
update field_options set code = 'ExtracurricularTypes' where code = 'ExtracurricularType' limit 1;
update field_options set code = 'IdentityTypes' where code = 'IdentityType' limit 1;
update field_options set code = 'Languages' where code = 'Language' limit 1;
update field_options set code = 'LicenseTypes' where code = 'LicenseType' limit 1;
update field_options set code = 'SpecialNeedTypes' where code = 'SpecialNeedType' limit 1;
update field_options set code = 'InfrastructureOwnerships' where code = 'InfrastructureOwnership' limit 1;
update field_options set code = 'InfrastructureConditions' where code = 'InfrastructureCondition' limit 1;
update field_options set code = 'Countries' where code = 'Country' limit 1;

-- May 28th 1737hrs
-- changing student and staff id to security user id
CREATE TABLE z1407_assessment_item_results  LIKE assessment_item_results;
INSERT INTO z1407_assessment_item_results SELECT * FROM assessment_item_results;
CREATE TABLE z1407_assessment_results  LIKE assessment_results;
INSERT INTO z1407_assessment_results SELECT * FROM assessment_results;
CREATE TABLE z1407_institution_site_class_students  LIKE institution_site_class_students;
INSERT INTO z1407_institution_site_class_students SELECT * FROM institution_site_class_students;
CREATE TABLE z1407_institution_site_section_students  LIKE institution_site_section_students;
INSERT INTO z1407_institution_site_section_students SELECT * FROM institution_site_section_students;
CREATE TABLE z1407_institution_site_student_absences  LIKE institution_site_student_absences;
INSERT INTO z1407_institution_site_student_absences SELECT * FROM institution_site_student_absences;
CREATE TABLE z1407_institution_site_students  LIKE institution_site_students;
INSERT INTO z1407_institution_site_students SELECT * FROM institution_site_students;
CREATE TABLE z1407_student_activities  LIKE student_activities;
INSERT INTO z1407_student_activities SELECT * FROM student_activities;
CREATE TABLE z1407_student_attachments  LIKE student_attachments;
INSERT INTO z1407_student_attachments SELECT * FROM student_attachments;
CREATE TABLE z1407_student_attendances  LIKE student_attendances;
INSERT INTO z1407_student_attendances SELECT * FROM student_attendances;
CREATE TABLE z1407_student_bank_accounts  LIKE student_bank_accounts;
INSERT INTO z1407_student_bank_accounts SELECT * FROM student_bank_accounts;
CREATE TABLE z1407_student_behaviours  LIKE student_behaviours;
INSERT INTO z1407_student_behaviours SELECT * FROM student_behaviours;
CREATE TABLE z1407_student_custom_value_history  LIKE student_custom_value_history;
INSERT INTO z1407_student_custom_value_history SELECT * FROM student_custom_value_history;
CREATE TABLE z1407_student_custom_values  LIKE student_custom_values;
INSERT INTO z1407_student_custom_values SELECT * FROM student_custom_values;
CREATE TABLE z1407_student_details_custom_values  LIKE student_details_custom_values;
INSERT INTO z1407_student_details_custom_values SELECT * FROM student_details_custom_values;
CREATE TABLE z1407_student_extracurriculars  LIKE student_extracurriculars;
INSERT INTO z1407_student_extracurriculars SELECT * FROM student_extracurriculars;
CREATE TABLE z1407_student_fees  LIKE student_fees;
INSERT INTO z1407_student_fees SELECT * FROM student_fees;
CREATE TABLE z1407_student_guardians  LIKE student_guardians;
INSERT INTO z1407_student_guardians SELECT * FROM student_guardians;
CREATE TABLE z1407_student_health_allergies  LIKE student_health_allergies;
INSERT INTO z1407_student_health_allergies SELECT * FROM student_health_allergies;
CREATE TABLE z1407_student_health_consultations  LIKE student_health_consultations;
INSERT INTO z1407_student_health_consultations SELECT * FROM student_health_consultations;
CREATE TABLE z1407_student_health_families  LIKE student_health_families;
INSERT INTO z1407_student_health_families SELECT * FROM student_health_families;
CREATE TABLE z1407_student_health_histories  LIKE student_health_histories;
INSERT INTO z1407_student_health_histories SELECT * FROM student_health_histories;
CREATE TABLE z1407_student_health_immunizations  LIKE student_health_immunizations;
INSERT INTO z1407_student_health_immunizations SELECT * FROM student_health_immunizations;
CREATE TABLE z1407_student_health_medications  LIKE student_health_medications;
INSERT INTO z1407_student_health_medications SELECT * FROM student_health_medications;
CREATE TABLE z1407_student_health_tests  LIKE student_health_tests;
INSERT INTO z1407_student_health_tests SELECT * FROM student_health_tests;
CREATE TABLE z1407_student_healths  LIKE student_healths;
INSERT INTO z1407_student_healths SELECT * FROM student_healths;
CREATE TABLE z1407_institution_site_class_staff  LIKE institution_site_class_staff;
INSERT INTO z1407_institution_site_class_staff SELECT * FROM institution_site_class_staff;
CREATE TABLE z1407_institution_site_quality_rubrics  LIKE institution_site_quality_rubrics;
INSERT INTO z1407_institution_site_quality_rubrics SELECT * FROM institution_site_quality_rubrics;
CREATE TABLE z1407_institution_site_quality_visits  LIKE institution_site_quality_visits;
INSERT INTO z1407_institution_site_quality_visits SELECT * FROM institution_site_quality_visits;
CREATE TABLE z1407_institution_site_section_staff  LIKE institution_site_section_staff;
INSERT INTO z1407_institution_site_section_staff SELECT * FROM institution_site_section_staff;
CREATE TABLE z1407_institution_site_sections  LIKE institution_site_sections;
INSERT INTO z1407_institution_site_sections SELECT * FROM institution_site_sections;
CREATE TABLE z1407_institution_site_staff  LIKE institution_site_staff;
INSERT INTO z1407_institution_site_staff SELECT * FROM institution_site_staff;
CREATE TABLE z1407_institution_site_staff_absences  LIKE institution_site_staff_absences;
INSERT INTO z1407_institution_site_staff_absences SELECT * FROM institution_site_staff_absences;
CREATE TABLE z1407_staff_activities  LIKE staff_activities;
INSERT INTO z1407_staff_activities SELECT * FROM staff_activities;
CREATE TABLE z1407_staff_attachments  LIKE staff_attachments;
INSERT INTO z1407_staff_attachments SELECT * FROM staff_attachments;
CREATE TABLE z1407_staff_attendances  LIKE staff_attendances;
INSERT INTO z1407_staff_attendances SELECT * FROM staff_attendances;
CREATE TABLE z1407_staff_bank_accounts  LIKE staff_bank_accounts;
INSERT INTO z1407_staff_bank_accounts SELECT * FROM staff_bank_accounts;
CREATE TABLE z1407_staff_behaviours  LIKE staff_behaviours;
INSERT INTO z1407_staff_behaviours SELECT * FROM staff_behaviours;
CREATE TABLE z1407_staff_custom_value_history  LIKE staff_custom_value_history;
INSERT INTO z1407_staff_custom_value_history SELECT * FROM staff_custom_value_history;
CREATE TABLE z1407_staff_custom_values  LIKE staff_custom_values;
INSERT INTO z1407_staff_custom_values SELECT * FROM staff_custom_values;
CREATE TABLE z1407_staff_details_custom_values  LIKE staff_details_custom_values;
INSERT INTO z1407_staff_details_custom_values SELECT * FROM staff_details_custom_values;
CREATE TABLE z1407_staff_employments  LIKE staff_employments;
INSERT INTO z1407_staff_employments SELECT * FROM staff_employments;
CREATE TABLE z1407_staff_extracurriculars  LIKE staff_extracurriculars;
INSERT INTO z1407_staff_extracurriculars SELECT * FROM staff_extracurriculars;
CREATE TABLE z1407_staff_health_allergies  LIKE staff_health_allergies;
INSERT INTO z1407_staff_health_allergies SELECT * FROM staff_health_allergies;
CREATE TABLE z1407_staff_health_consultations  LIKE staff_health_consultations;
INSERT INTO z1407_staff_health_consultations SELECT * FROM staff_health_consultations;
CREATE TABLE z1407_staff_health_families  LIKE staff_health_families;
INSERT INTO z1407_staff_health_families SELECT * FROM staff_health_families;
CREATE TABLE z1407_staff_health_histories  LIKE staff_health_histories;
INSERT INTO z1407_staff_health_histories SELECT * FROM staff_health_histories;
CREATE TABLE z1407_staff_health_immunizations  LIKE staff_health_immunizations;
INSERT INTO z1407_staff_health_immunizations SELECT * FROM staff_health_immunizations;
CREATE TABLE z1407_staff_health_medications  LIKE staff_health_medications;
INSERT INTO z1407_staff_health_medications SELECT * FROM staff_health_medications;
CREATE TABLE z1407_staff_health_tests  LIKE staff_health_tests;
INSERT INTO z1407_staff_health_tests SELECT * FROM staff_health_tests;
CREATE TABLE z1407_staff_healths  LIKE staff_healths;
INSERT INTO z1407_staff_healths SELECT * FROM staff_healths;
CREATE TABLE z1407_staff_leaves  LIKE staff_leaves;
INSERT INTO z1407_staff_leaves SELECT * FROM staff_leaves;
CREATE TABLE z1407_staff_licenses  LIKE staff_licenses;
INSERT INTO z1407_staff_licenses SELECT * FROM staff_licenses;
CREATE TABLE z1407_staff_memberships  LIKE staff_memberships;
INSERT INTO z1407_staff_memberships SELECT * FROM staff_memberships;
CREATE TABLE z1407_staff_qualifications  LIKE staff_qualifications;
INSERT INTO z1407_staff_qualifications SELECT * FROM staff_qualifications;
CREATE TABLE z1407_staff_salaries  LIKE staff_salaries;
INSERT INTO z1407_staff_salaries SELECT * FROM staff_salaries;
CREATE TABLE z1407_staff_training  LIKE staff_training;
INSERT INTO z1407_staff_training SELECT * FROM staff_training;
CREATE TABLE z1407_staff_training_needs  LIKE staff_training_needs;
INSERT INTO z1407_staff_training_needs SELECT * FROM staff_training_needs;
CREATE TABLE z1407_staff_training_self_studies  LIKE staff_training_self_studies;
INSERT INTO z1407_staff_training_self_studies SELECT * FROM staff_training_self_studies;
CREATE TABLE z1407_training_session_trainees  LIKE training_session_trainees;
INSERT INTO z1407_training_session_trainees SELECT * FROM training_session_trainees;

ALTER TABLE `assessment_item_results` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `assessment_item_results` ADD INDEX(`security_user_id`);
ALTER TABLE `assessment_results` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `assessment_results` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_class_students` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `institution_site_class_students` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_section_students` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `institution_site_section_students` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_student_absences` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `institution_site_student_absences` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_students` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `institution_site_students` ADD INDEX(`security_user_id`);
ALTER TABLE `student_activities` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_activities` ADD INDEX(`security_user_id`);
ALTER TABLE `student_attachments` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_attachments` ADD INDEX(`security_user_id`);
ALTER TABLE `student_attendances` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_attendances` ADD INDEX(`security_user_id`);
ALTER TABLE `student_bank_accounts` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_bank_accounts` ADD INDEX(`security_user_id`);
ALTER TABLE `student_behaviours` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_behaviours` ADD INDEX(`security_user_id`);
ALTER TABLE `student_custom_value_history` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_custom_value_history` ADD INDEX(`security_user_id`);
ALTER TABLE `student_custom_values` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_custom_values` ADD INDEX(`security_user_id`);
ALTER TABLE `student_details_custom_values` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_details_custom_values` ADD INDEX(`security_user_id`);
ALTER TABLE `student_extracurriculars` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_extracurriculars` ADD INDEX(`security_user_id`);
ALTER TABLE `student_fees` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_fees` ADD INDEX(`security_user_id`);
ALTER TABLE `student_guardians` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_guardians` ADD INDEX(`security_user_id`);
ALTER TABLE `student_health_allergies` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_health_allergies` ADD INDEX(`security_user_id`);
ALTER TABLE `student_health_consultations` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_health_consultations` ADD INDEX(`security_user_id`);
ALTER TABLE `student_health_families` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_health_families` ADD INDEX(`security_user_id`);
ALTER TABLE `student_health_histories` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_health_histories` ADD INDEX(`security_user_id`);
ALTER TABLE `student_health_immunizations` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_health_immunizations` ADD INDEX(`security_user_id`);
ALTER TABLE `student_health_medications` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_health_medications` ADD INDEX(`security_user_id`);
ALTER TABLE `student_health_tests` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_health_tests` ADD INDEX(`security_user_id`);
ALTER TABLE `student_healths` ADD `security_user_id` INT NOT NULL AFTER `student_id`;
ALTER TABLE `student_healths` ADD INDEX(`security_user_id`);

ALTER TABLE `institution_site_class_staff` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `institution_site_class_staff` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_quality_rubrics` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `institution_site_quality_rubrics` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_quality_visits` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `institution_site_quality_visits` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_section_staff` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `institution_site_section_staff` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_sections` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `institution_site_sections` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_staff` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `institution_site_staff` ADD INDEX(`security_user_id`);
ALTER TABLE `institution_site_staff_absences` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `institution_site_staff_absences` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_activities` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_activities` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_attachments` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_attachments` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_attendances` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_attendances` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_bank_accounts` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_bank_accounts` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_behaviours` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_behaviours` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_custom_value_history` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_custom_value_history` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_custom_values` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_custom_values` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_details_custom_values` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_details_custom_values` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_employments` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_employments` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_extracurriculars` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_extracurriculars` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_health_allergies` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_health_allergies` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_health_consultations` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_health_consultations` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_health_families` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_health_families` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_health_histories` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_health_histories` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_health_immunizations` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_health_immunizations` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_health_medications` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_health_medications` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_health_tests` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_health_tests` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_healths` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_healths` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_leaves` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_leaves` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_licenses` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_licenses` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_memberships` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_memberships` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_qualifications` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_qualifications` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_salaries` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_salaries` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_training` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_training` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_training_needs` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_training_needs` ADD INDEX(`security_user_id`);
ALTER TABLE `staff_training_self_studies` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `staff_training_self_studies` ADD INDEX(`security_user_id`);
ALTER TABLE `training_session_trainees` ADD `security_user_id` INT NOT NULL AFTER `staff_id`;
ALTER TABLE `training_session_trainees` ADD INDEX(`security_user_id`);



UPDATE assessment_item_results LEFT JOIN students ON assessment_item_results.student_id = students.id SET assessment_item_results.security_user_id = students.security_user_id;
UPDATE assessment_results LEFT JOIN students ON assessment_results.student_id = students.id SET assessment_results.security_user_id = students.security_user_id;
UPDATE institution_site_class_students LEFT JOIN students ON institution_site_class_students.student_id = students.id SET institution_site_class_students.security_user_id = students.security_user_id;
UPDATE institution_site_section_students LEFT JOIN students ON institution_site_section_students.student_id = students.id SET institution_site_section_students.security_user_id = students.security_user_id;
UPDATE institution_site_student_absences LEFT JOIN students ON institution_site_student_absences.student_id = students.id SET institution_site_student_absences.security_user_id = students.security_user_id;
UPDATE institution_site_students LEFT JOIN students ON institution_site_students.student_id = students.id SET institution_site_students.security_user_id = students.security_user_id;
UPDATE student_activities LEFT JOIN students ON student_activities.student_id = students.id SET student_activities.security_user_id = students.security_user_id;
UPDATE student_attachments LEFT JOIN students ON student_attachments.student_id = students.id SET student_attachments.security_user_id = students.security_user_id;
UPDATE student_attendances LEFT JOIN students ON student_attendances.student_id = students.id SET student_attendances.security_user_id = students.security_user_id;
UPDATE student_bank_accounts LEFT JOIN students ON student_bank_accounts.student_id = students.id SET student_bank_accounts.security_user_id = students.security_user_id;
UPDATE student_behaviours LEFT JOIN students ON student_behaviours.student_id = students.id SET student_behaviours.security_user_id = students.security_user_id;
UPDATE student_custom_value_history LEFT JOIN students ON student_custom_value_history.student_id = students.id SET student_custom_value_history.security_user_id = students.security_user_id;
UPDATE student_custom_values LEFT JOIN students ON student_custom_values.student_id = students.id SET student_custom_values.security_user_id = students.security_user_id;
UPDATE student_details_custom_values LEFT JOIN students ON student_details_custom_values.student_id = students.id SET student_details_custom_values.security_user_id = students.security_user_id;
UPDATE student_extracurriculars LEFT JOIN students ON student_extracurriculars.student_id = students.id SET student_extracurriculars.security_user_id = students.security_user_id;
UPDATE student_fees LEFT JOIN students ON student_fees.student_id = students.id SET student_fees.security_user_id = students.security_user_id;
UPDATE student_guardians LEFT JOIN students ON student_guardians.student_id = students.id SET student_guardians.security_user_id = students.security_user_id;
UPDATE student_health_allergies LEFT JOIN students ON student_health_allergies.student_id = students.id SET student_health_allergies.security_user_id = students.security_user_id;
UPDATE student_health_consultations LEFT JOIN students ON student_health_consultations.student_id = students.id SET student_health_consultations.security_user_id = students.security_user_id;
UPDATE student_health_families LEFT JOIN students ON student_health_families.student_id = students.id SET student_health_families.security_user_id = students.security_user_id;
UPDATE student_health_histories LEFT JOIN students ON student_health_histories.student_id = students.id SET student_health_histories.security_user_id = students.security_user_id;
UPDATE student_health_immunizations LEFT JOIN students ON student_health_immunizations.student_id = students.id SET student_health_immunizations.security_user_id = students.security_user_id;
UPDATE student_health_medications LEFT JOIN students ON student_health_medications.student_id = students.id SET student_health_medications.security_user_id = students.security_user_id;
UPDATE student_health_tests LEFT JOIN students ON student_health_tests.student_id = students.id SET student_health_tests.security_user_id = students.security_user_id;
UPDATE student_healths LEFT JOIN students ON student_healths.student_id = students.id SET student_healths.security_user_id = students.security_user_id;

UPDATE institution_site_class_staff LEFT JOIN staff ON institution_site_class_staff.staff_id = staff.id SET institution_site_class_staff.security_user_id = staff.security_user_id;
UPDATE institution_site_quality_rubrics LEFT JOIN staff ON institution_site_quality_rubrics.staff_id = staff.id SET institution_site_quality_rubrics.security_user_id = staff.security_user_id;
UPDATE institution_site_quality_visits LEFT JOIN staff ON institution_site_quality_visits.staff_id = staff.id SET institution_site_quality_visits.security_user_id = staff.security_user_id;
UPDATE institution_site_section_staff LEFT JOIN staff ON institution_site_section_staff.staff_id = staff.id SET institution_site_section_staff.security_user_id = staff.security_user_id;
UPDATE institution_site_sections LEFT JOIN staff ON institution_site_sections.staff_id = staff.id SET institution_site_sections.security_user_id = staff.security_user_id;
UPDATE institution_site_staff LEFT JOIN staff ON institution_site_staff.staff_id = staff.id SET institution_site_staff.security_user_id = staff.security_user_id;
UPDATE institution_site_staff_absences LEFT JOIN staff ON institution_site_staff_absences.staff_id = staff.id SET institution_site_staff_absences.security_user_id = staff.security_user_id;
UPDATE staff_activities LEFT JOIN staff ON staff_activities.staff_id = staff.id SET staff_activities.security_user_id = staff.security_user_id;
UPDATE staff_attachments LEFT JOIN staff ON staff_attachments.staff_id = staff.id SET staff_attachments.security_user_id = staff.security_user_id;
UPDATE staff_attendances LEFT JOIN staff ON staff_attendances.staff_id = staff.id SET staff_attendances.security_user_id = staff.security_user_id;
UPDATE staff_bank_accounts LEFT JOIN staff ON staff_bank_accounts.staff_id = staff.id SET staff_bank_accounts.security_user_id = staff.security_user_id;
UPDATE staff_behaviours LEFT JOIN staff ON staff_behaviours.staff_id = staff.id SET staff_behaviours.security_user_id = staff.security_user_id;
UPDATE staff_custom_value_history LEFT JOIN staff ON staff_custom_value_history.staff_id = staff.id SET staff_custom_value_history.security_user_id = staff.security_user_id;
UPDATE staff_custom_values LEFT JOIN staff ON staff_custom_values.staff_id = staff.id SET staff_custom_values.security_user_id = staff.security_user_id;
UPDATE staff_details_custom_values LEFT JOIN staff ON staff_details_custom_values.staff_id = staff.id SET staff_details_custom_values.security_user_id = staff.security_user_id;
UPDATE staff_employments LEFT JOIN staff ON staff_employments.staff_id = staff.id SET staff_employments.security_user_id = staff.security_user_id;
UPDATE staff_extracurriculars LEFT JOIN staff ON staff_extracurriculars.staff_id = staff.id SET staff_extracurriculars.security_user_id = staff.security_user_id;
UPDATE staff_health_allergies LEFT JOIN staff ON staff_health_allergies.staff_id = staff.id SET staff_health_allergies.security_user_id = staff.security_user_id;
UPDATE staff_health_consultations LEFT JOIN staff ON staff_health_consultations.staff_id = staff.id SET staff_health_consultations.security_user_id = staff.security_user_id;
UPDATE staff_health_families LEFT JOIN staff ON staff_health_families.staff_id = staff.id SET staff_health_families.security_user_id = staff.security_user_id;
UPDATE staff_health_histories LEFT JOIN staff ON staff_health_histories.staff_id = staff.id SET staff_health_histories.security_user_id = staff.security_user_id;
UPDATE staff_health_immunizations LEFT JOIN staff ON staff_health_immunizations.staff_id = staff.id SET staff_health_immunizations.security_user_id = staff.security_user_id;
UPDATE staff_health_medications LEFT JOIN staff ON staff_health_medications.staff_id = staff.id SET staff_health_medications.security_user_id = staff.security_user_id;
UPDATE staff_health_tests LEFT JOIN staff ON staff_health_tests.staff_id = staff.id SET staff_health_tests.security_user_id = staff.security_user_id;
UPDATE staff_healths LEFT JOIN staff ON staff_healths.staff_id = staff.id SET staff_healths.security_user_id = staff.security_user_id;
UPDATE staff_leaves LEFT JOIN staff ON staff_leaves.staff_id = staff.id SET staff_leaves.security_user_id = staff.security_user_id;
UPDATE staff_licenses LEFT JOIN staff ON staff_licenses.staff_id = staff.id SET staff_licenses.security_user_id = staff.security_user_id;
UPDATE staff_memberships LEFT JOIN staff ON staff_memberships.staff_id = staff.id SET staff_memberships.security_user_id = staff.security_user_id;
UPDATE staff_qualifications LEFT JOIN staff ON staff_qualifications.staff_id = staff.id SET staff_qualifications.security_user_id = staff.security_user_id;
UPDATE staff_salaries LEFT JOIN staff ON staff_salaries.staff_id = staff.id SET staff_salaries.security_user_id = staff.security_user_id;
UPDATE staff_training LEFT JOIN staff ON staff_training.staff_id = staff.id SET staff_training.security_user_id = staff.security_user_id;
UPDATE staff_training_needs LEFT JOIN staff ON staff_training_needs.staff_id = staff.id SET staff_training_needs.security_user_id = staff.security_user_id;
UPDATE staff_training_self_studies LEFT JOIN staff ON staff_training_self_studies.staff_id = staff.id SET staff_training_self_studies.security_user_id = staff.security_user_id;
UPDATE training_session_trainees LEFT JOIN staff ON training_session_trainees.staff_id = staff.id SET training_session_trainees.security_user_id = staff.security_user_id;
-- testing example
-- select institution_site_students.id, institution_site_students.student_id, institution_site_students.security_user_id, students.id as student_id, students.security_user_id as student_security_user_id from institution_site_students LEFT JOIN students ON institution_site_students.student_id = students.id where institution_site_students.security_user_id = students.security_user_id

ALTER TABLE `assessment_item_results` DROP `student_id`;
ALTER TABLE `assessment_results` DROP `student_id`;
ALTER TABLE `institution_site_class_students` DROP `student_id`;
ALTER TABLE `institution_site_section_students` DROP `student_id`;
ALTER TABLE `institution_site_student_absences` DROP `student_id`;
ALTER TABLE `institution_site_students` DROP `student_id`;
ALTER TABLE `student_activities` DROP `student_id`;
ALTER TABLE `student_attachments` DROP `student_id`;
ALTER TABLE `student_attendances` DROP `student_id`;
ALTER TABLE `student_bank_accounts` DROP `student_id`;
ALTER TABLE `student_behaviours` DROP `student_id`;
ALTER TABLE `student_custom_value_history` DROP `student_id`;
ALTER TABLE `student_custom_values` DROP `student_id`;
ALTER TABLE `student_details_custom_values` DROP `student_id`;
ALTER TABLE `student_extracurriculars` DROP `student_id`;
ALTER TABLE `student_fees` DROP `student_id`;
ALTER TABLE `student_guardians` DROP `student_id`;
ALTER TABLE `student_health_allergies` DROP `student_id`;
ALTER TABLE `student_health_consultations` DROP `student_id`;
ALTER TABLE `student_health_families` DROP `student_id`;
ALTER TABLE `student_health_histories` DROP `student_id`;
ALTER TABLE `student_health_immunizations` DROP `student_id`;
ALTER TABLE `student_health_medications` DROP `student_id`;
ALTER TABLE `student_health_tests` DROP `student_id`;
ALTER TABLE `student_healths` DROP `student_id`;

ALTER TABLE `institution_site_class_staff` DROP `staff_id`;
ALTER TABLE `institution_site_quality_rubrics` DROP `staff_id`;
ALTER TABLE `institution_site_quality_visits` DROP `staff_id`;
ALTER TABLE `institution_site_section_staff` DROP `staff_id`;
ALTER TABLE `institution_site_sections` DROP `staff_id`;
ALTER TABLE `institution_site_staff` DROP `staff_id`;
ALTER TABLE `institution_site_staff_absences` DROP `staff_id`;
ALTER TABLE `staff_activities` DROP `staff_id`;
ALTER TABLE `staff_attachments` DROP `staff_id`;
ALTER TABLE `staff_attendances` DROP `staff_id`;
ALTER TABLE `staff_bank_accounts` DROP `staff_id`;
ALTER TABLE `staff_behaviours` DROP `staff_id`;
ALTER TABLE `staff_custom_value_history` DROP `staff_id`;
ALTER TABLE `staff_custom_values` DROP `staff_id`;
ALTER TABLE `staff_details_custom_values` DROP `staff_id`;
ALTER TABLE `staff_employments` DROP `staff_id`;
ALTER TABLE `staff_extracurriculars` DROP `staff_id`;
ALTER TABLE `staff_health_allergies` DROP `staff_id`;
ALTER TABLE `staff_health_consultations` DROP `staff_id`;
ALTER TABLE `staff_health_families` DROP `staff_id`;
ALTER TABLE `staff_health_histories` DROP `staff_id`;
ALTER TABLE `staff_health_immunizations` DROP `staff_id`;
ALTER TABLE `staff_health_medications` DROP `staff_id`;
ALTER TABLE `staff_health_tests` DROP `staff_id`;
ALTER TABLE `staff_healths` DROP `staff_id`;
ALTER TABLE `staff_leaves` DROP `staff_id`;
ALTER TABLE `staff_licenses` DROP `staff_id`;
ALTER TABLE `staff_memberships` DROP `staff_id`;
ALTER TABLE `staff_qualifications` DROP `staff_id`;
ALTER TABLE `staff_salaries` DROP `staff_id`;
ALTER TABLE `staff_training` DROP `staff_id`;
ALTER TABLE `staff_training_needs` DROP `staff_id`;
ALTER TABLE `staff_training_self_studies` DROP `staff_id`;
ALTER TABLE `training_session_trainees` DROP `staff_id`;

-- June 2 1323hrs
-- changing student and staff id to security user id (part 2)
ALTER TABLE `student_attachments` ADD `date_on_file` DATE NOT NULL AFTER `file_content`;
CREATE TABLE user_attachments LIKE student_attachments;
INSERT INTO user_attachments (name, description, file_name, file_content, date_on_file, visible, security_user_id, modified_user_id, modified, created_user_id, created) SELECT name, description, file_name, file_content, date_on_file, visible, security_user_id, modified_user_id, modified, created_user_id, created FROM student_attachments;
INSERT INTO user_attachments (name, description, file_name, file_content, date_on_file, visible, security_user_id, modified_user_id, modified, created_user_id, created) SELECT name, description, file_name, file_content, date_on_file, visible, security_user_id, modified_user_id, modified, created_user_id, created FROM staff_attachments;
DROP TABLE student_attachments;
DROP TABLE staff_attachments;

-- June 2nd 1320hrs
-- Workflow - Backup tables
RENAME TABLE workflows TO z_1407_workflows;
RENAME TABLE workflow_logs TO z_1407_workflow_logs;
RENAME TABLE workflow_steps TO z_1407_workflow_steps;

RENAME TABLE wf_workflows TO z_1407_wf_workflows;
RENAME TABLE wf_workflow_actions TO z_1407_wf_workflow_actions;
RENAME TABLE wf_workflow_comments TO z_1407_wf_workflow_comments;
RENAME TABLE wf_workflow_models TO z_1407_wf_workflow_models;
RENAME TABLE wf_workflow_records TO z_1407_wf_workflow_records;
RENAME TABLE wf_workflow_steps TO z_1407_wf_workflow_steps;
RENAME TABLE wf_workflow_step_roles TO z_1407_wf_workflow_step_roles;
RENAME TABLE wf_workflow_submodels TO z_1407_wf_workflow_submodels;
RENAME TABLE wf_workflow_transitions TO z_1407_wf_workflow_transitions;

CREATE TABLE IF NOT EXISTS workflows LIKE z_1407_wf_workflows;
CREATE TABLE IF NOT EXISTS workflow_actions LIKE z_1407_wf_workflow_actions;
CREATE TABLE IF NOT EXISTS workflow_comments LIKE z_1407_wf_workflow_comments;
CREATE TABLE IF NOT EXISTS workflow_models LIKE z_1407_wf_workflow_models;
CREATE TABLE IF NOT EXISTS workflow_records LIKE z_1407_wf_workflow_records;
CREATE TABLE IF NOT EXISTS workflow_steps LIKE z_1407_wf_workflow_steps;
CREATE TABLE IF NOT EXISTS workflow_step_roles LIKE z_1407_wf_workflow_step_roles;
CREATE TABLE IF NOT EXISTS workflow_submodels LIKE z_1407_wf_workflow_submodels;
CREATE TABLE IF NOT EXISTS workflow_transitions LIKE z_1407_wf_workflow_transitions;

INSERT workflows SELECT * FROM z_1407_wf_workflows;
INSERT workflow_actions SELECT * FROM z_1407_wf_workflow_actions;
INSERT workflow_comments SELECT * FROM z_1407_wf_workflow_comments;
INSERT workflow_models SELECT * FROM z_1407_wf_workflow_models;
INSERT workflow_records SELECT * FROM z_1407_wf_workflow_records;
INSERT workflow_steps SELECT * FROM z_1407_wf_workflow_steps;
INSERT workflow_step_roles SELECT * FROM z_1407_wf_workflow_step_roles;
INSERT workflow_submodels SELECT * FROM z_1407_wf_workflow_submodels;
INSERT workflow_transitions SELECT * FROM z_1407_wf_workflow_transitions;

-- June 3 1605hrs
-- changing student and staff id to security user id (part 3)
CREATE TABLE user_bank_accounts LIKE student_bank_accounts;
INSERT INTO user_bank_accounts (account_name, account_number, active, security_user_id, bank_branch_id, remarks, modified_user_id, modified, created_user_id, created) SELECT account_name, account_number, active, security_user_id, bank_branch_id, remarks, modified_user_id, modified, created_user_id, created FROM student_bank_accounts;
INSERT INTO user_bank_accounts (account_name, account_number, active, security_user_id, bank_branch_id, remarks, modified_user_id, modified, created_user_id, created) SELECT account_name, account_number, active, security_user_id, bank_branch_id, remarks, modified_user_id, modified, created_user_id, created FROM staff_bank_accounts;
DROP TABLE student_bank_accounts;
DROP TABLE staff_bank_accounts;

-- June 9 1505hrs
-- Shifted default country data from config items to country table
ALTER TABLE `countries` ADD `default` INT(1) NOT NULL DEFAULT '0' AFTER `visible`;

-- June 12 0926hrs
ALTER TABLE `security_users` CHANGE `password` `password` CHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;

-- June 15 1800hrs
-- remove education_grade_id from institution_site_sections as it will not be used
CREATE TABLE `z_1458_institution_site_sections` LIKE  `institution_site_sections`;
INSERT INTO `z_1458_institution_site_sections` SELECT * FROM `institution_site_sections` WHERE 1;
ALTER TABLE `institution_site_sections` DROP `education_grade_id`;
-- rename institution_site_section_staff as it will not be used
ALTER TABLE `institution_site_section_staff` RENAME  `z_1458_institution_site_section_staff`;

-- June 16 1020hrs
ALTER TABLE `rubric_templates` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `rubric_template_options` CHANGE `color` `color` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '#ffffff';
UPDATE `workflow_models` SET `submodel` = 'FieldOption.StaffLeaveTypes' WHERE `workflow_models`.`model` = 'StaffLeave';

-- June 16 1100hrs
-- New tables for Custom Field

-- New table - custom_modules
DROP TABLE IF EXISTS `custom_modules`;
CREATE TABLE IF NOT EXISTS `custom_modules` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `model` varchar(200),
  `behavior` varchar(200) DEFAULT NULL,
  `filter` varchar(200) DEFAULT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `parent_id` int(11) DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_modules`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

TRUNCATE TABLE `custom_modules`;
INSERT INTO `custom_modules` (`id`, `code`, `name`, `model`, `behavior`, `filter`, `visible`, `parent_id`, `created_user_id`, `created`) VALUES
(1, 'Institution', 'Institution - Overview', 'Institution.Institutions', NULL, 'FieldOption.InstitutionSiteTypes', 1, 0, 1, '0000-00-00 00:00:00'),
(2, 'Student', 'Student - Overview', 'User.Users', 'Student', NULL, 1, 0, 1, '0000-00-00 00:00:00'),
(3, 'Staff', 'Staff - Overview', 'User.Users', 'Staff', NULL, 1, 0, 1, '0000-00-00 00:00:00'),
(4, 'Infrastructure', 'Institution - Infrastructure', 'Institution.InstitutionInfrastructures', NULL, NULL, 0, 1, 1, '0000-00-00 00:00:00');

-- New table - custom_field_types
DROP TABLE IF EXISTS `custom_field_types`;
CREATE TABLE IF NOT EXISTS `custom_field_types` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `value` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `format` varchar(50) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_field_types`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_field_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

TRUNCATE TABLE `custom_field_types`;
INSERT INTO `custom_field_types` (`id`, `code`, `name`, `value`, `description`, `format`, `is_mandatory`, `is_unique`) VALUES
(1, 'TEXT', 'Text', 'text_value', '', 'OpenEMIS', 1, 1),
(2, 'NUMBER', 'Number', 'number_value', '', 'OpenEMIS', 1, 1),
(3, 'TEXTAREA', 'Textarea', 'textarea_value', '', 'OpenEMIS', 1, 0),
(4, 'DROPDOWN', 'Dropdown', 'number_value', '', 'OpenEMIS', 0, 0),
(5, 'CHECKBOX', 'Checkbox', 'text_value', '', 'OpenEMIS', 0, 0),
(6, 'TABLE', 'Table', 'text_value', '', 'OpenEMIS', 0, 0),
(7, 'DATE', 'Date', 'date_value', '', 'OpenEMIS', 1, 0),
(8, 'TIME', 'Time', 'time_value', '', 'OpenEMIS', 1, 0);

-- New table - custom_fields
DROP TABLE IF EXISTS `custom_fields`;
CREATE TABLE IF NOT EXISTS `custom_fields` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `field_type` varchar(100) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_fields`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - custom_field_options
DROP TABLE IF EXISTS `custom_field_options`;
CREATE TABLE IF NOT EXISTS `custom_field_options` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_field_options`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_field_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - custom_table_columns
DROP TABLE IF EXISTS `custom_table_columns`;
CREATE TABLE IF NOT EXISTS `custom_table_columns` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_table_columns`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_table_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - custom_table_rows
DROP TABLE IF EXISTS `custom_table_rows`;
CREATE TABLE IF NOT EXISTS `custom_table_rows` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_table_rows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_table_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - custom_forms
DROP TABLE IF EXISTS `custom_forms`;
CREATE TABLE IF NOT EXISTS `custom_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - custom_form_fields
DROP TABLE IF EXISTS `custom_form_fields`;
CREATE TABLE IF NOT EXISTS `custom_form_fields` (
  `id` char(36) NOT NULL,
  `custom_form_id` int(11) NOT NULL,
  `custom_field_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_form_fields`
  ADD PRIMARY KEY (`id`);

-- New table - custom_form_filters
DROP TABLE IF EXISTS `custom_form_filters`;
CREATE TABLE IF NOT EXISTS `custom_form_filters` (
  `id` char(36) NOT NULL,
  `custom_form_id` int(11) NOT NULL,
  `custom_filter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_form_filters`
  ADD PRIMARY KEY (`id`);

-- New table - custom_records
DROP TABLE IF EXISTS `custom_records`;
CREATE TABLE IF NOT EXISTS `custom_records` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `custom_form_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_records`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `custom_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - custom_field_values
DROP TABLE IF EXISTS `custom_field_values`;
CREATE TABLE IF NOT EXISTS `custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `custom_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_field_values`
  ADD PRIMARY KEY (`id`);

-- New table - custom_table_cells
DROP TABLE IF EXISTS `custom_table_cells`;
CREATE TABLE IF NOT EXISTS `custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `custom_table_column_id` int(11) NOT NULL,
  `custom_table_row_id` int(11) NOT NULL,
  `custom_record_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `custom_table_cells`
  ADD PRIMARY KEY (`id`);

-- June 17 0911hrs
-- Update for infrastructure

-- Backup old tables
RENAME TABLE infrastructure_custom_fields TO z_1461_infrastructure_custom_fields;
RENAME TABLE infrastructure_custom_field_options TO z_1461_infrastructure_custom_field_options;

-- New table - infrastructure_custom_fields
DROP TABLE IF EXISTS `infrastructure_custom_fields`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_fields` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `field_type` varchar(100) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_fields`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_custom_field_options
DROP TABLE IF EXISTS `infrastructure_custom_field_options`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_field_options` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_field_options`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_custom_field_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_custom_table_columns
DROP TABLE IF EXISTS `infrastructure_custom_table_columns`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_table_columns` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_table_columns`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_custom_table_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_custom_table_rows
DROP TABLE IF EXISTS `infrastructure_custom_table_rows`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_table_rows` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_table_rows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_custom_table_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- June 17 1118hrs
-- Update for survey
ALTER TABLE `survey_statuses` CHANGE `survey_template_id` `survey_form_id` INT(11) NOT NULL;

-- Backup old tables
RENAME TABLE survey_modules TO z_1461_survey_modules;
RENAME TABLE survey_questions TO z_1461_survey_questions;
RENAME TABLE survey_question_choices TO z_1461_survey_question_choices;
RENAME TABLE survey_table_columns TO z_1461_survey_table_columns;
RENAME TABLE survey_table_rows TO z_1461_survey_table_rows;
RENAME TABLE survey_templates TO z_1461_survey_templates;

-- New table - survey_questions
DROP TABLE IF EXISTS `survey_questions`;
CREATE TABLE IF NOT EXISTS `survey_questions` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `field_type` varchar(100) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_questions`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - survey_question_choices
DROP TABLE IF EXISTS `survey_question_choices`;
CREATE TABLE IF NOT EXISTS `survey_question_choices` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_question_choices`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_question_choices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - survey_table_columns
DROP TABLE IF EXISTS `survey_table_columns`;
CREATE TABLE IF NOT EXISTS `survey_table_columns` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_table_columns`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_table_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - survey_table_rows
DROP TABLE IF EXISTS `survey_table_rows`;
CREATE TABLE IF NOT EXISTS `survey_table_rows` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `survey_question_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_table_rows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_table_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - survey_forms
DROP TABLE IF EXISTS `survey_forms`;
CREATE TABLE IF NOT EXISTS `survey_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `survey_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - survey_form_questions
DROP TABLE IF EXISTS `survey_form_questions`;
CREATE TABLE IF NOT EXISTS `survey_form_questions` (
  `id` char(36) NOT NULL,
  `survey_form_id` int(11) NOT NULL,
  `survey_question_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `survey_form_questions`
  ADD PRIMARY KEY (`id`);

-- added Labels table
DROP TABLE IF EXISTS `labels`;
CREATE TABLE IF NOT EXISTS `labels` (
  `module` varchar(100) NOT NULL,
  `field` varchar(100) NOT NULL,
  `code` varchar(50) UNIQUE,
  `en` varchar(100) COMMENT 'English',
  `ar` varchar(100) COMMENT 'Arabic',
  `zh` varchar(100) COMMENT 'Chinese',
  `es` varchar(100) COMMENT 'Spanish',
  `fr` varchar(100) COMMENT 'French',
  `ru` varchar(100) COMMENT 'Russian',
  `modified_user_id` int(11),
  `modified` datetime,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`module`, `field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- June 18 1730hrs
-- Update for Institution - Custom Fields

RENAME TABLE institution_site_custom_fields TO z_1461_institution_site_custom_fields;
RENAME TABLE institution_site_custom_field_options TO z_1461_institution_site_custom_field_options;
RENAME TABLE institution_site_custom_values TO z_1461_institution_site_custom_values;
RENAME TABLE institution_site_custom_value_history TO z_1461_institution_site_custom_value_history;

-- New table - institution_site_custom_field_values
DROP TABLE IF EXISTS `institution_site_custom_field_values`;
CREATE TABLE IF NOT EXISTS `institution_site_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- New table - institution_site_custom_table_cells
DROP TABLE IF EXISTS `institution_site_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_site_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `custom_table_column_id` int(11) NOT NULL,
  `custom_table_row_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_custom_table_cells`
  ADD PRIMARY KEY (`id`);

update config_items set name = 'StaffContacts' where name = 'StaffContact' and type = 'Add New Staff';
update config_items set name = 'StaffIdentities' where name = 'StaffIdentity' and type = 'Add New Staff';
update config_items set name = 'StaffNationalities' where name = 'StaffNationality' and type = 'Add New Staff';
update config_items set name = 'StaffSpecialNeeds' where name = 'StaffSpecialNeed' and type = 'Add New Staff';
update config_items set name = 'StudentContacts' where name = 'StudentContact' and type = 'Add New Student';
update config_items set name = 'StudentIdentities' where name = 'StudentIdentity' and type = 'Add New Student';
update config_items set name = 'StudentNationalities' where name = 'StudentNationality' and type = 'Add New Student';
update config_items set name = 'StudentSpecialNeeds' where name = 'StudentSpecialNeed' and type = 'Add New Student';

ALTER TABLE `institution_sites` CHANGE `alternative_name` `alternative_name` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;

ALTER TABLE `field_option_values` DROP `old_id` ;

-- 26th June 2015 16:10hrs - clean up config_items
ALTER TABLE `config_items` ADD `code` VARCHAR( 50 ) NOT NULL AFTER `name` ;
UPDATE `config_items` SET code = name;
ALTER TABLE `config_items` ADD UNIQUE (`code`);
ALTER TABLE `config_items` DROP INDEX name;
UPDATE `config_items` SET name = label;
UPDATE `config_items` SET `visible` = '0' WHERE `config_items`.`code` = 'dashboard_img_width';
UPDATE `config_items` SET `visible` = '0' WHERE `config_items`.`code` = 'dashboard_img_height';
UPDATE `config_items` SET `visible` = '0' WHERE `config_items`.`code` = 'dashboard_img_default';
UPDATE `config_items` SET `visible` = '0' WHERE `config_items`.`code` = 'dashboard_img_size_limit';

-- 29th June 1600hrs
-- Update for Institution - Surveys
RENAME TABLE institution_site_surveys TO z_1461_institution_site_surveys;
RENAME TABLE institution_site_survey_answers TO z_1461_institution_site_survey_answers;
RENAME TABLE institution_site_survey_table_cells TO z_1461_institution_site_survey_table_cells;

-- New table - institution_site_surveys
DROP TABLE IF EXISTS `institution_site_surveys`;
CREATE TABLE IF NOT EXISTS `institution_site_surveys` (
  `id` int(11) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed',
  `academic_period_id` int(11) NOT NULL,
  `survey_form_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_surveys`
  ADD PRIMARY KEY (`id`), ADD KEY `institution_site_id` (`institution_site_id`);


ALTER TABLE `institution_site_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_site_survey_answers
DROP TABLE IF EXISTS `institution_site_survey_answers`;
CREATE TABLE IF NOT EXISTS `institution_site_survey_answers` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `institution_site_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_survey_answers`
  ADD PRIMARY KEY (`id`);

-- New table - institution_site_survey_table_cells
DROP TABLE IF EXISTS `institution_site_survey_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_site_survey_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `survey_question_id` int(11) NOT NULL,
  `survey_table_column_id` int(11) NOT NULL,
  `survey_table_row_id` int(11) NOT NULL,
  `institution_site_survey_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_survey_table_cells`
  ADD PRIMARY KEY (`id`);


-- 29th June 2000hrs
-- Patch Institution - Classes that are having education_subject_id as NULL
CREATE TABLE `z_1458_institution_site_classes` LIKE `institution_site_classes`;
INSERT INTO `z_1458_institution_site_classes` SELECT * FROM `institution_site_classes` WHERE `institution_site_classes`.`education_subject_id` IS NULL;
UPDATE `institution_site_classes` SET `education_subject_id`=1 WHERE `institution_site_classes`.`education_subject_id` IS NULL;

-- 29th June 2015
ALTER TABLE `assessments` DROP `institution_site_id` ;
ALTER TABLE `assessments` DROP `academic_period_id` ;
ALTER TABLE `assessment_items` DROP `education_grade_subject_id` ;
ALTER TABLE `assessment_statuses` DROP `academic_period_level_id` ;

-- 1st July 1000hrs
-- Update for Students and Staff Custom Fields
RENAME TABLE student_custom_fields TO z_1461_student_custom_fields;
RENAME TABLE student_custom_field_options TO z_1461_student_custom_field_options;
RENAME TABLE student_custom_values TO z_1461_student_custom_values;
RENAME TABLE student_custom_value_history TO z_1461_student_custom_value_history;
RENAME TABLE student_details_custom_fields TO z_1461_student_details_custom_fields;
RENAME TABLE student_details_custom_field_options TO z_1461_student_details_custom_field_options;
RENAME TABLE student_details_custom_values TO z_1461_student_details_custom_values;

RENAME TABLE staff_custom_fields TO z_1461_staff_custom_fields;
RENAME TABLE staff_custom_field_options TO z_1461_staff_custom_field_options;
RENAME TABLE staff_custom_values TO z_1461_staff_custom_values;
RENAME TABLE staff_custom_value_history TO z_1461_staff_custom_value_history;
RENAME TABLE staff_details_custom_fields TO z_1461_staff_details_custom_fields;
RENAME TABLE staff_details_custom_field_options TO z_1461_staff_details_custom_field_options;
RENAME TABLE staff_details_custom_values TO z_1461_staff_details_custom_values;

-- New table - student_custom_field_values
DROP TABLE IF EXISTS `student_custom_field_values`;
CREATE TABLE IF NOT EXISTS `student_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- New table - student_custom_table_cells
DROP TABLE IF EXISTS `student_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `student_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `custom_table_column_id` int(11) NOT NULL,
  `custom_table_row_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_table_cells`
  ADD PRIMARY KEY (`id`);

-- New table - staff_custom_field_values
DROP TABLE IF EXISTS `staff_custom_field_values`;
CREATE TABLE IF NOT EXISTS `staff_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- New table - staff_custom_table_cells
DROP TABLE IF EXISTS `staff_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `staff_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `custom_field_id` int(11) NOT NULL,
  `custom_table_column_id` int(11) NOT NULL,
  `custom_table_row_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_table_cells`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `institution_site_attachments` CHANGE `file_content` `file_content` LONGBLOB NULL;  
ALTER TABLE `user_attachments` CHANGE `file_content` `file_content` LONGBLOB NULL;

-- 1st July 2000hrs
-- Update for Rubric Status
RENAME TABLE quality_statuses TO z_1461_quality_statuses;
RENAME TABLE quality_status_periods TO z_1461_quality_status_periods;
RENAME TABLE quality_status_programmes TO z_1461_quality_status_programmes;
RENAME TABLE quality_status_roles TO z_1461_quality_status_roles;

-- New table - rubric_statuses
DROP TABLE IF EXISTS `rubric_statuses`;
CREATE TABLE IF NOT EXISTS `rubric_statuses` (
  `id` int(11) NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `rubric_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_statuses`
  ADD PRIMARY KEY (`id`), ADD KEY `rubric_template_id` (`rubric_template_id`);


ALTER TABLE `rubric_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - rubric_status_periods
DROP TABLE IF EXISTS `rubric_status_periods`;
CREATE TABLE IF NOT EXISTS `rubric_status_periods` (
  `id` char(36) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `rubric_status_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_status_periods`
  ADD PRIMARY KEY (`id`), ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `rubric_status_id` (`rubric_status_id`);

-- New table - rubric_status_programmes
DROP TABLE IF EXISTS `rubric_status_programmes`;
CREATE TABLE IF NOT EXISTS `rubric_status_programmes` (
  `id` char(36) NOT NULL,
  `education_programme_id` int(11) NOT NULL,
  `rubric_status_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_status_programmes`
  ADD PRIMARY KEY (`id`), ADD KEY `education_programme_id` (`education_programme_id`), ADD KEY `rubric_status_id` (`rubric_status_id`);

-- New table - rubric_status_roles
DROP TABLE IF EXISTS `rubric_status_roles`;
CREATE TABLE IF NOT EXISTS `rubric_status_roles` (
  `id` char(36) NOT NULL,
  `rubric_status_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `rubric_status_roles`
  ADD PRIMARY KEY (`id`), ADD KEY `rubric_status_id` (`rubric_status_id`), ADD KEY `security_role_id` (`security_role_id`);

-- 1st July 2100hrs
-- Update for Survey Status
ALTER TABLE `survey_statuses` DROP `academic_period_level_id`;

-- Patch students data if status not equal 1
UPDATE `institution_site_class_students` SET `status` = 1 WHERE `status` NOT IN (0, 1);

-- 2nd July 2100hrs
-- Update Institution - Infrastructure
RENAME TABLE infrastructure_levels TO z_1461_infrastructure_levels;
RENAME TABLE institution_site_infrastructures TO z_1461_institution_site_infrastructures;
RENAME TABLE institution_site_infrastructure_custom_values TO z_1461_institution_site_infrastructure_custom_values;

-- New table - infrastructure_levels
DROP TABLE IF EXISTS `infrastructure_levels`;
CREATE TABLE IF NOT EXISTS `infrastructure_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT '0',
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_levels`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_level_fields
DROP TABLE IF EXISTS `infrastructure_level_fields`;
CREATE TABLE IF NOT EXISTS `infrastructure_level_fields` (
  `id` char(36) NOT NULL,
  `infrastructure_level_id` int(11) NOT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_level_fields`
  ADD PRIMARY KEY (`id`);

-- New table - institution_site_infrastructures
DROP TABLE IF EXISTS `institution_site_infrastructures`;
CREATE TABLE IF NOT EXISTS `institution_site_infrastructures` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `year_acquired` int(4) DEFAULT NULL,
  `year_disposed` int(4) DEFAULT NULL,
  `comment` text,
  `size` float DEFAULT NULL,
  `institution_site_id` int(11) NOT NULL,
  `infrastructure_level_id` int(11) NOT NULL,
  `infrastructure_type_id` int(11) NOT NULL,
  `infrastructure_ownership_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_infrastructures`
  ADD PRIMARY KEY (`id`), ADD KEY `code` (`code`), ADD KEY `name` (`name`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `infrastructure_level_id` (`infrastructure_level_id`), ADD KEY `infrastructure_type_id` (`infrastructure_type_id`), ADD KEY `infrastructure_ownership_id` (`infrastructure_ownership_id`), ADD KEY `infrastructure_condition_id` (`infrastructure_condition_id`);


ALTER TABLE `institution_site_infrastructures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_site_infrastructure_custom_field_values
DROP TABLE IF EXISTS `institution_site_infrastructure_custom_field_values`;
CREATE TABLE IF NOT EXISTS `institution_site_infrastructure_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_site_infrastructure_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_infrastructure_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- New table - institution_site_infrastructure_custom_table_cells
DROP TABLE IF EXISTS `institution_site_infrastructure_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_site_infrastructure_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `infrastructure_custom_table_column_id` int(11) NOT NULL,
  `infrastructure_custom_table_row_id` int(11) NOT NULL,
  `institution_site_infrastructure_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_infrastructure_custom_table_cells`
  ADD PRIMARY KEY (`id`);


-- 6 July 2015 12:56pm
ALTER TABLE `institution_site_attachments` ADD `date_on_file` DATE NOT NULL AFTER `file_content`;
ALTER TABLE `institution_site_attachments` CHANGE `file_content` `file_content` LONGBLOB NOT NULL;  
ALTER TABLE `user_attachments` DROP `visible`;
ALTER TABLE `user_attachments` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;
ALTER TABLE `user_attachments` CHANGE `file_content` `file_content` LONGBLOB NOT NULL;


-- 7 July 2015 
ALTER TABLE `institution_site_activities` ADD `field_type` VARCHAR(128) NOT NULL AFTER `field`;
ALTER TABLE `student_activities` ADD `field_type` VARCHAR(128) NOT NULL AFTER `field`;
ALTER TABLE `staff_activities` ADD `field_type` VARCHAR(128) NOT NULL AFTER `field`;


-- 7 July 2015 by jeff
DELETE FROM `field_options` WHERE `code` IN (
	'InstitutionSiteCustomFields', 'CensusCustomFieldOptions', 'CensusCustomFields', 'CensusGrids', 'StudentCustomFields',
	'StaffCustomFields', 'InfrastructureBuildings', 'InfrastructureCategories', 'InfrastructureEnergies',
	'InfrastructureFurnitures', 'InfrastructureMaterials', 'InfrastructureResources', 'InfrastructureRooms',
	'InfrastructureSanitations', 'InfrastructureStatuses', 'InfrastructureWaters'
);
UPDATE `field_options` SET `params` = '{"model":"FieldOption.Banks"}' WHERE `field_options`.`code` = 'Banks';
UPDATE `field_options` SET `params` = '{"model":"FieldOption.BankBranches"}' WHERE `field_options`.`code` = 'BankBranches';
UPDATE `field_options` SET `params` = '{"model":"User.ContactTypes"}' WHERE `field_options`.`code` = 'ContactTypes';
UPDATE `field_options` SET `params` = '{"model":"FieldOption.Countries"}' WHERE `field_options`.`code` = 'Countries';


