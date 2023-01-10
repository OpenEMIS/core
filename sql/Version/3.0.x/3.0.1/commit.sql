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


-- 9th July 2015

RENAME TABLE assessment_results TO z_1461_assessment_results;

ALTER TABLE `assessment_item_results` DROP `assessment_result_id`;
ALTER TABLE `assessment_item_results` DROP `assessment_result_type_id`;

-- New table - institution_site_assessments
DROP TABLE IF EXISTS `institution_site_assessments`;
CREATE TABLE IF NOT EXISTS `institution_site_assessments` (
  `id` char(36) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed',
  `academic_period_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_site_assessments`
  ADD PRIMARY KEY (`id`), ADD KEY `institution_site_id` (`institution_site_id`);


ALTER TABLE `institution_site_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


DELETE FROM config_items where `type`='Nationality' AND `code`='country_id';
DELETE FROM config_items where `type`='Year Book Report';
DELETE FROM config_items where `code` = 'institution_code';

UPDATE config_items 
SET 
  `name`='Institution Code',
  `code`='institution_code',
  `label`='Institution Code'
WHERE
  `type`='Custom Validation'
AND `name`='Institution Site Code'
AND `code`='institution_site_code'
AND `label`='Institution Site Code';

UPDATE config_items 
SET 
  `name`='Institution Telephone',
  `code`='institution_telephone',
  `label`='Institution Telephone'
WHERE
  `type`='Custom Validation'
AND `name`='Institution Site Telephone'
AND `code`='institution_site_telephone'
AND `label`='Institution Site Telephone';

UPDATE config_items 
SET 
  `name`='Institution Fax',
  `code`='institution_fax',
  `label`='Institution Fax'
WHERE
  `type`='Custom Validation'
AND `name`='Institution Site Fax'
AND `code`='institution_site_fax'
AND `label`='Institution Site Fax';

UPDATE config_items 
SET 
  `name`='Institution Postal Code',
  `code`='institution_postal_code',
  `label`='Institution Postal Code'
WHERE
  `type`='Custom Validation'
AND `name`='Institution Site Postal Code'
AND `code`='institution_site_postal_code'
AND `label`='Institution Site Postal Code';


UPDATE config_items 
SET 
  `type`='Institution',
  `code`='institution_area_level_id'
WHERE
  `type`='Institution Site'
AND `name`='Display Area Level'
AND `code`='institution_site_area_level_id'
AND `label`='Display Area Level';

-- added by jeff
UPDATE `config_item_options` SET `value` = 0 WHERE `option` = 'Sunday';
UPDATE `config_item_options` SET `value` = 1 WHERE `option` = 'Monday';
UPDATE `config_item_options` SET `value` = 2 WHERE `option` = 'Tuesday';
UPDATE `config_item_options` SET `value` = 3 WHERE `option` = 'Wednesday';
UPDATE `config_item_options` SET `value` = 4 WHERE `option` = 'Thursday';
UPDATE `config_item_options` SET `value` = 5 WHERE `option` = 'Friday';
UPDATE `config_item_options` SET `value` = 6 WHERE `option` = 'Saturday';

UPDATE `config_items` SET `value` = 0, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'sunday';
UPDATE `config_items` SET `value` = 1, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'monday';
UPDATE `config_items` SET `value` = 2, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'tuesday';
UPDATE `config_items` SET `value` = 3, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'wednesday';
UPDATE `config_items` SET `value` = 4, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'thursday';
UPDATE `config_items` SET `value` = 5, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'friday';
UPDATE `config_items` SET `value` = 6, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'saturday';
UPDATE `config_items` SET `value` = 1, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = '';

-- 16th July 2015

UPDATE `custom_modules` SET `filter` = 'Infrastructure.InfrastructureLevels' WHERE `code` = 'Infrastructure';

--
-- For Institutions - Infrastructure
--

-- New table - infrastructure_custom_forms
DROP TABLE IF EXISTS `infrastructure_custom_forms`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `infrastructure_custom_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_custom_forms_fields
DROP TABLE IF EXISTS `infrastructure_custom_forms_fields`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_forms_fields` (
  `id` char(36) NOT NULL,
  `infrastructure_custom_form_id` int(11) NOT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_forms_fields`
  ADD PRIMARY KEY (`id`);

-- New table - infrastructure_custom_forms_filters
DROP TABLE IF EXISTS `infrastructure_custom_forms_filters`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_forms_filters` (
  `id` char(36) NOT NULL,
  `infrastructure_custom_form_id` int(11) NOT NULL,
  `infrastructure_custom_filter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_forms_filters`
  ADD PRIMARY KEY (`id`);

-- Alter table - infrastructure_levels
DROP TABLE IF EXISTS `infrastructure_levels`;
CREATE TABLE IF NOT EXISTS `infrastructure_levels` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
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

-- Drop tables
DROP TABLE IF EXISTS `infrastructure_level_fields`;
DROP TABLE IF EXISTS `institution_site_infrastructures`;
DROP TABLE IF EXISTS `institution_site_infrastructure_custom_field_values`;
DROP TABLE IF EXISTS `institution_site_infrastructure_custom_table_cells`;

-- New table - institution_infrastructures
DROP TABLE IF EXISTS `institution_infrastructures`;
CREATE TABLE IF NOT EXISTS `institution_infrastructures` (
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


ALTER TABLE `institution_infrastructures`
  ADD PRIMARY KEY (`id`), ADD KEY `code` (`code`), ADD KEY `name` (`name`), ADD KEY `institution_site_id` (`institution_site_id`), ADD KEY `infrastructure_level_id` (`infrastructure_level_id`), ADD KEY `infrastructure_type_id` (`infrastructure_type_id`), ADD KEY `infrastructure_ownership_id` (`infrastructure_ownership_id`), ADD KEY `infrastructure_condition_id` (`infrastructure_condition_id`);


ALTER TABLE `institution_infrastructures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - infrastructure_custom_field_values
DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- New table - infrastructure_custom_table_cells
DROP TABLE IF EXISTS `infrastructure_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `infrastructure_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `infrastructure_custom_table_column_id` int(11) NOT NULL,
  `infrastructure_custom_table_row_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `infrastructure_custom_table_cells`
  ADD PRIMARY KEY (`id`);


-- 16th July 2015

--
-- For Institutions
--

-- New table - institution_custom_fields
DROP TABLE IF EXISTS `institution_custom_fields`;
CREATE TABLE IF NOT EXISTS `institution_custom_fields` (
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


ALTER TABLE `institution_custom_fields`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_field_options
DROP TABLE IF EXISTS `institution_custom_field_options`;
CREATE TABLE IF NOT EXISTS `institution_custom_field_options` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `institution_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_field_options`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_field_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_table_columns
DROP TABLE IF EXISTS `institution_custom_table_columns`;
CREATE TABLE IF NOT EXISTS `institution_custom_table_columns` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_table_columns`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_table_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_table_rows
DROP TABLE IF EXISTS `institution_custom_table_rows`;
CREATE TABLE IF NOT EXISTS `institution_custom_table_rows` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `institution_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_table_rows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_table_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_forms
DROP TABLE IF EXISTS `institution_custom_forms`;
CREATE TABLE IF NOT EXISTS `institution_custom_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `institution_custom_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - institution_custom_forms_fields
DROP TABLE IF EXISTS `institution_custom_forms_fields`;
CREATE TABLE IF NOT EXISTS `institution_custom_forms_fields` (
  `id` char(36) NOT NULL,
  `institution_custom_form_id` int(11) NOT NULL,
  `institution_custom_field_id` int(11) NOT NULL,
  `section` varchar(250) DEFAULT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_forms_fields`
  ADD PRIMARY KEY (`id`);

-- New table - institution_custom_forms_filters
DROP TABLE IF EXISTS `institution_custom_forms_filters`;
CREATE TABLE IF NOT EXISTS `institution_custom_forms_filters` (
  `id` char(36) NOT NULL,
  `institution_custom_form_id` int(11) NOT NULL,
  `institution_custom_filter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_forms_filters`
  ADD PRIMARY KEY (`id`);

-- Drop table
DROP TABLE IF EXISTS `institution_site_custom_field_values`;
DROP TABLE IF EXISTS `institution_site_custom_table_cells`;

-- Alter table - institution_custom_field_values
DROP TABLE IF EXISTS `institution_custom_field_values`;
CREATE TABLE IF NOT EXISTS `institution_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `institution_custom_field_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_field_values`
  ADD PRIMARY KEY (`id`);

-- Alter table - institution_custom_table_cells
DROP TABLE IF EXISTS `institution_custom_table_cells`;
CREATE TABLE IF NOT EXISTS `institution_custom_table_cells` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `institution_custom_field_id` int(11) NOT NULL,
  `institution_custom_table_column_id` int(11) NOT NULL,
  `institution_custom_table_row_id` int(11) NOT NULL,
  `institution_site_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_custom_table_cells`
  ADD PRIMARY KEY (`id`);

--
-- For data patch
--

-- patch institution_custom_forms
TRUNCATE TABLE `institution_custom_forms`;
INSERT INTO `institution_custom_forms` (`id`, `name`, `description`, `custom_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'General', '', 1, 1, '2015-01-01 00:00:00', 1, '2015-01-01 00:00:00');

-- patch institution_custom_fields
TRUNCATE TABLE `institution_custom_fields`;
INSERT INTO `institution_custom_fields` (`id`, `name`, `field_type`, `is_mandatory`, `is_unique`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`,
  CASE
    WHEN `type` = 2 THEN 'TEXT'
    WHEN `type` = 3 THEN 'DROPDOWN'
    WHEN `type` = 4 THEN 'CHECKBOX'
    WHEN `type` = 5 THEN 'TEXTAREA'
    ELSE '-1'
  END,
  0, 0, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_custom_fields`
WHERE `type` != 1;

-- patch institution_custom_field_options
TRUNCATE TABLE `institution_custom_field_options`;
INSERT INTO `institution_custom_field_options` (`id`, `name`, `is_default`, `visible`, `order`, `institution_custom_field_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `value`, 0, `visible`, `order`, `institution_site_custom_field_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_custom_field_options`;

-- patch institution_custom_forms_fields
DELIMITER $$

DROP PROCEDURE IF EXISTS custom_patch
$$
CREATE PROCEDURE custom_patch()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE fieldId, formId INT(11);
  DECLARE fieldOrder INT(3);
  DECLARE fieldType INT(1);
  DECLARE fieldName VARCHAR(250);
  DECLARE sectionName VARCHAR(250);
  DECLARE sfq CURSOR FOR 
    SELECT `CustomFields`.`id`, `CustomFields`.`name`, `CustomFields`.`type`, `CustomFields`.`order`, 1
    FROM `z_1461_institution_site_custom_fields` AS `CustomFields`
    ORDER BY `CustomFields`.`order`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN sfq;
  TRUNCATE TABLE `institution_custom_forms_fields`;

  read_loop: LOOP
  FETCH sfq INTO fieldId, fieldName, fieldType, fieldOrder, formId;
  IF done THEN
    LEAVE read_loop;
  END IF;

    IF fieldType = 1 THEN
      SET @sectionName = fieldName;
    END IF;

    IF fieldType <> 1 THEN
      INSERT INTO `institution_custom_forms_fields` (`id`, `institution_custom_form_id`, `institution_custom_field_id`, `section`, `order`) VALUES (uuid(), formId, fieldId, @sectionName, fieldOrder);
    END IF;

  END LOOP read_loop;

  CLOSE sfq;
END
$$

CALL custom_patch
$$

DROP PROCEDURE IF EXISTS custom_patch
$$

DELIMITER ;

-- patch institution_custom_forms_filters
INSERT INTO `institution_custom_forms_filters` (`id`, `institution_custom_form_id`, `institution_custom_filter_id`) VALUES
(uuid(), 1, 0);

-- patch institution_custom_field_values
TRUNCATE TABLE `institution_custom_field_values`;
INSERT INTO `institution_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `institution_custom_field_id`, `institution_site_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(),
  CASE
    WHEN `CustomFields`.`type` = 2 THEN `CustomValues`.`value`
    ELSE NULL
  END,
  CASE
    WHEN `CustomFields`.`type` = 3 THEN `CustomValues`.`value`
    WHEN `CustomFields`.`type` = 4 THEN `CustomValues`.`value`
    ELSE NULL
  END,
  CASE
    WHEN `CustomFields`.`type` = 5 THEN `CustomValues`.`value`
    ELSE NULL
  END,
`CustomValues`.`institution_site_custom_field_id`, `CustomValues`.`institution_site_id`, `CustomValues`.`modified_user_id`, `CustomValues`.`modified`, `CustomValues`.`created_user_id`, `CustomValues`.`created`
FROM `z_1461_institution_site_custom_values` AS `CustomValues`
INNER JOIN `z_1461_institution_site_custom_fields` AS `CustomFields` ON `CustomFields`.`id` = `CustomValues`.`institution_site_custom_field_id`;

-- 16th July 2015

RENAME TABLE `custom_form_fields` TO `custom_forms_fields`;
RENAME TABLE `custom_form_filters` TO `custom_forms_filters`;

-- 16th July 2015

--
-- For Staff
--

-- New table - staff_custom_fields
DROP TABLE IF EXISTS `staff_custom_fields`;
CREATE TABLE IF NOT EXISTS `staff_custom_fields` (
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


ALTER TABLE `staff_custom_fields`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `staff_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - staff_custom_field_options
DROP TABLE IF EXISTS `staff_custom_field_options`;
CREATE TABLE IF NOT EXISTS `staff_custom_field_options` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `staff_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_field_options`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `staff_custom_field_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - staff_custom_table_columns
DROP TABLE IF EXISTS `staff_custom_table_columns`;
CREATE TABLE IF NOT EXISTS `staff_custom_table_columns` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `staff_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_table_columns`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `staff_custom_table_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - staff_custom_table_rows
DROP TABLE IF EXISTS `staff_custom_table_rows`;
CREATE TABLE IF NOT EXISTS `staff_custom_table_rows` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `staff_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_table_rows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `staff_custom_table_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - staff_custom_forms
DROP TABLE IF EXISTS `staff_custom_forms`;
CREATE TABLE IF NOT EXISTS `staff_custom_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `staff_custom_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - staff_custom_forms_fields
DROP TABLE IF EXISTS `staff_custom_forms_fields`;
CREATE TABLE IF NOT EXISTS `staff_custom_forms_fields` (
  `id` char(36) NOT NULL,
  `staff_custom_form_id` int(11) NOT NULL,
  `staff_custom_field_id` int(11) NOT NULL,
  `section` varchar(250) DEFAULT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_forms_fields`
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
  `staff_custom_field_id` int(11) NOT NULL,
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
  `staff_custom_field_id` int(11) NOT NULL,
  `staff_custom_table_column_id` int(11) NOT NULL,
  `staff_custom_table_row_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `staff_custom_table_cells`
  ADD PRIMARY KEY (`id`);

--
-- For data patch
--

-- patch staff_custom_forms
TRUNCATE TABLE `staff_custom_forms`;
INSERT INTO `staff_custom_forms` (`id`, `name`, `description`, `custom_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'General', '', 3, 1, '2015-01-01 00:00:00', 1, '2015-01-01 00:00:00');

-- patch staff_custom_fields
TRUNCATE TABLE `staff_custom_fields`;
INSERT INTO `staff_custom_fields` (`id`, `name`, `field_type`, `is_mandatory`, `is_unique`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`,
  CASE
    WHEN `type` = 2 THEN 'TEXT'
    WHEN `type` = 3 THEN 'DROPDOWN'
    WHEN `type` = 4 THEN 'CHECKBOX'
    WHEN `type` = 5 THEN 'TEXTAREA'
    ELSE '-1'
  END,
  0, 0, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_staff_custom_fields`
WHERE `type` != 1;

-- patch staff_custom_field_options
TRUNCATE TABLE `staff_custom_field_options`;
INSERT INTO `staff_custom_field_options` (`id`, `name`, `is_default`, `visible`, `order`, `staff_custom_field_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `value`, 0, `visible`, `order`, `staff_custom_field_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_staff_custom_field_options`;

-- patch staff_custom_forms_fields
DELIMITER $$

DROP PROCEDURE IF EXISTS custom_patch
$$
CREATE PROCEDURE custom_patch()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE fieldId, formId INT(11);
  DECLARE fieldOrder INT(3);
  DECLARE fieldType INT(1);
  DECLARE fieldName VARCHAR(250);
  DECLARE sectionName VARCHAR(250);
  DECLARE sfq CURSOR FOR 
    SELECT `CustomFields`.`id`, `CustomFields`.`name`, `CustomFields`.`type`, `CustomFields`.`order`, 1
    FROM `z_1461_staff_custom_fields` AS `CustomFields`
    ORDER BY `CustomFields`.`order`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN sfq;
  TRUNCATE TABLE `staff_custom_forms_fields`;

  read_loop: LOOP
  FETCH sfq INTO fieldId, fieldName, fieldType, fieldOrder, formId;
  IF done THEN
    LEAVE read_loop;
  END IF;

    IF fieldType = 1 THEN
      SET @sectionName = fieldName;
    END IF;

    IF fieldType <> 1 THEN
      INSERT INTO `staff_custom_forms_fields` (`id`, `staff_custom_form_id`, `staff_custom_field_id`, `section`, `order`) VALUES (uuid(), formId, fieldId, @sectionName, fieldOrder);
    END IF;

  END LOOP read_loop;

  CLOSE sfq;
END
$$

CALL custom_patch
$$

DROP PROCEDURE IF EXISTS custom_patch
$$

DELIMITER ;

-- patch staff_custom_field_values
TRUNCATE TABLE `staff_custom_field_values`;
INSERT INTO `staff_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `staff_custom_field_id`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(),
  CASE
    WHEN `CustomFields`.`type` = 2 THEN `CustomValues`.`value`
    ELSE NULL
  END,
  CASE
    WHEN `CustomFields`.`type` = 3 THEN `CustomValues`.`value`
    WHEN `CustomFields`.`type` = 4 THEN `CustomValues`.`value`
    ELSE NULL
  END,
  CASE
    WHEN `CustomFields`.`type` = 5 THEN `CustomValues`.`value`
    ELSE NULL
  END,
`CustomValues`.`staff_custom_field_id`, `CustomValues`.`security_user_id`, `CustomValues`.`modified_user_id`, `CustomValues`.`modified`, `CustomValues`.`created_user_id`, `CustomValues`.`created`
FROM `z_1461_staff_custom_values` AS `CustomValues`
INNER JOIN `z_1461_staff_custom_fields` AS `CustomFields` ON `CustomFields`.`id` = `CustomValues`.`staff_custom_field_id`;


-- 16th July 2015

--
-- For Students
--

-- New table - student_custom_fields
DROP TABLE IF EXISTS `student_custom_fields`;
CREATE TABLE IF NOT EXISTS `student_custom_fields` (
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


ALTER TABLE `student_custom_fields`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `student_custom_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - student_custom_field_options
DROP TABLE IF EXISTS `student_custom_field_options`;
CREATE TABLE IF NOT EXISTS `student_custom_field_options` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `order` int(3) NOT NULL DEFAULT '0',
  `student_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_field_options`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `student_custom_field_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - student_custom_table_columns
DROP TABLE IF EXISTS `student_custom_table_columns`;
CREATE TABLE IF NOT EXISTS `student_custom_table_columns` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `student_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_table_columns`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `student_custom_table_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - student_custom_table_rows
DROP TABLE IF EXISTS `student_custom_table_rows`;
CREATE TABLE IF NOT EXISTS `student_custom_table_rows` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL DEFAULT '0',
  `visible` int(1) NOT NULL DEFAULT '1',
  `student_custom_field_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_table_rows`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `student_custom_table_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - student_custom_forms
DROP TABLE IF EXISTS `student_custom_forms`;
CREATE TABLE IF NOT EXISTS `student_custom_forms` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `custom_module_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_forms`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `student_custom_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- New table - student_custom_forms_fields
DROP TABLE IF EXISTS `student_custom_forms_fields`;
CREATE TABLE IF NOT EXISTS `student_custom_forms_fields` (
  `id` char(36) NOT NULL,
  `student_custom_form_id` int(11) NOT NULL,
  `student_custom_field_id` int(11) NOT NULL,
  `section` varchar(250) DEFAULT NULL,
  `name` varchar(250) NOT NULL,
  `is_mandatory` int(1) NOT NULL DEFAULT '0',
  `is_unique` int(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_forms_fields`
  ADD PRIMARY KEY (`id`);

-- New table - student_custom_field_values
DROP TABLE IF EXISTS `student_custom_field_values`;
CREATE TABLE IF NOT EXISTS `student_custom_field_values` (
  `id` char(36) NOT NULL,
  `text_value` varchar(250) DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `student_custom_field_id` int(11) NOT NULL,
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
  `student_custom_field_id` int(11) NOT NULL,
  `student_custom_table_column_id` int(11) NOT NULL,
  `student_custom_table_row_id` int(11) NOT NULL,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `student_custom_table_cells`
  ADD PRIMARY KEY (`id`);

--
-- For data patch
--

-- patch student_custom_forms
TRUNCATE TABLE `student_custom_forms`;
INSERT INTO `student_custom_forms` (`id`, `name`, `description`, `custom_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'General', '', 2, 1, '2015-01-01 00:00:00', 1, '2015-01-01 00:00:00');

-- patch student_custom_fields
TRUNCATE TABLE `student_custom_fields`;
INSERT INTO `student_custom_fields` (`id`, `name`, `field_type`, `is_mandatory`, `is_unique`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`,
  CASE
    WHEN `type` = 2 THEN 'TEXT'
    WHEN `type` = 3 THEN 'DROPDOWN'
    WHEN `type` = 4 THEN 'CHECKBOX'
    WHEN `type` = 5 THEN 'TEXTAREA'
    ELSE '-1'
  END,
  0, 0, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_student_custom_fields`
WHERE `type` != 1;

-- patch student_custom_field_options
TRUNCATE TABLE `student_custom_field_options`;
INSERT INTO `student_custom_field_options` (`id`, `name`, `is_default`, `visible`, `order`, `student_custom_field_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `value`, 0, `visible`, `order`, `student_custom_field_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_student_custom_field_options`;

-- patch student_custom_forms_fields
DELIMITER $$

DROP PROCEDURE IF EXISTS custom_patch
$$
CREATE PROCEDURE custom_patch()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE fieldId, formId INT(11);
  DECLARE fieldOrder INT(3);
  DECLARE fieldType INT(1);
  DECLARE fieldName VARCHAR(250);
  DECLARE sectionName VARCHAR(250);
  DECLARE sfq CURSOR FOR 
    SELECT `CustomFields`.`id`, `CustomFields`.`name`, `CustomFields`.`type`, `CustomFields`.`order`, 1
    FROM `z_1461_student_custom_fields` AS `CustomFields`
    ORDER BY `CustomFields`.`order`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN sfq;
  TRUNCATE TABLE `student_custom_forms_fields`;

  read_loop: LOOP
  FETCH sfq INTO fieldId, fieldName, fieldType, fieldOrder, formId;
  IF done THEN
    LEAVE read_loop;
  END IF;

    IF fieldType = 1 THEN
      SET @sectionName = fieldName;
    END IF;

    IF fieldType <> 1 THEN
      INSERT INTO `student_custom_forms_fields` (`id`, `student_custom_form_id`, `student_custom_field_id`, `section`, `order`) VALUES (uuid(), formId, fieldId, @sectionName, fieldOrder);
    END IF;

  END LOOP read_loop;

  CLOSE sfq;
END
$$

CALL custom_patch
$$

DROP PROCEDURE IF EXISTS custom_patch
$$

DELIMITER ;

-- patch student_custom_field_values
TRUNCATE TABLE `student_custom_field_values`;
INSERT INTO `student_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `student_custom_field_id`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(),
  CASE
    WHEN `CustomFields`.`type` = 2 THEN `CustomValues`.`value`
    ELSE NULL
  END,
  CASE
    WHEN `CustomFields`.`type` = 3 THEN `CustomValues`.`value`
    WHEN `CustomFields`.`type` = 4 THEN `CustomValues`.`value`
    ELSE NULL
  END,
  CASE
    WHEN `CustomFields`.`type` = 5 THEN `CustomValues`.`value`
    ELSE NULL
  END,
`CustomValues`.`student_custom_field_id`, `CustomValues`.`security_user_id`, `CustomValues`.`modified_user_id`, `CustomValues`.`modified`, `CustomValues`.`created_user_id`, `CustomValues`.`created`
FROM `z_1461_student_custom_values` AS `CustomValues`
INNER JOIN `z_1461_student_custom_fields` AS `CustomFields` ON `CustomFields`.`id` = `CustomValues`.`student_custom_field_id`;

-- 14th July 2015

-- patch rubric_statuses
TRUNCATE TABLE `rubric_statuses`;
INSERT INTO `rubric_statuses` (`id`, `date_enabled`, `date_disabled`, `status`, `rubric_template_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `date_enabled`, `date_disabled`, `status`, `rubric_template_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_quality_statuses`;

-- patch rubric_status_periods
TRUNCATE TABLE `rubric_status_periods`;
INSERT INTO `rubric_status_periods` (`id`, `academic_period_id`, `rubric_status_id`)
SELECT `id`, `academic_period_id`, `quality_status_id`
FROM `z_1461_quality_status_periods`;

-- patch rubric_status_programmes
TRUNCATE TABLE `rubric_status_programmes`;
INSERT INTO `rubric_status_programmes` (`id`, `education_programme_id`, `rubric_status_id`)
SELECT `id`, `education_programme_id`, `quality_status_id`
FROM `z_1461_quality_status_programmes`;

-- patch rubric_status_roles
TRUNCATE TABLE `rubric_status_roles`;
INSERT INTO `rubric_status_roles` (`id`, `security_role_id`, `rubric_status_id`)
SELECT `id`, `security_role_id`, `quality_status_id`
FROM `z_1461_quality_status_roles`;

-- 27th June 2015
UPDATE `security_functions` SET 
  `_edit` = REPLACE(`_edit`, '_view:', ''), 
  `_add` = REPLACE(`_add`, '_view:', ''), 
  `_delete` = REPLACE(`_delete`, '_view:', ''),
  `_execute` = REPLACE(`_execute`, '_view:', '');

UPDATE `security_functions` SET `controller` = 'Institutions' WHERE `controller` = 'InstitutionSites';
UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'Students.index|Students.view', `_add` = 'Students.add', `_edit` = 'Students.edit', `_delete` = 'Students.remove', `_execute` = 'Students.excel' WHERE `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Details' AND `name` = 'Student';

-- Hide all 'More' functions and 'Census' functions
UPDATE `security_functions` SET `visible` = -1 
WHERE `controller` = 'Census';

DELETE FROM `security_functions` WHERE `name` IN ('More', 'Students - Academic', 'Staff - Academic', 'Population', 'Dashboard Image');
DELETE FROM `security_functions` WHERE `name` = 'Finance' AND `controller` = 'Finance';

-- fixed names and categories
UPDATE `security_functions` SET `name` = 'Students', `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Student' AND `category` = 'Details';
UPDATE `security_functions` SET `name` = 'Behaviour', `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `name` = 'Attendance', `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Attendance';
UPDATE `security_functions` SET `category` = 'Students' WHERE `controller` = 'Institutions' AND `name` = 'Results' AND `category` = 'Assessment';
UPDATE `security_functions` SET `controller` = 'Institutions', `name` = 'Staff', `category` = 'Staff' WHERE `controller` = 'Staff' AND `name` = 'Staff' AND `category` = 'Details';
UPDATE `security_functions` SET `name` = 'Behaviour', `category` = 'Staff' WHERE `controller` = 'Institutions' AND `name` = 'Staff' AND `category` = 'Behaviour';
UPDATE `security_functions` SET `name` = 'Attendance', `category` = 'Staff' WHERE `controller` = 'Institutions' AND `name` = 'Staff' AND `category` = 'Attendance';
UPDATE `security_functions` SET `name` = 'Students' WHERE `controller` = 'Students' AND `name` = 'Student' AND `category` = 'General';

-- reorganise functions
SET @funcId := 0;

SET @id := 1000;
-- Institutions Module
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Institution';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index|view|dashboard', `_edit` = 'edit', `_add` = 'add', `_delete` = 'remove', `_execute` = 'excel'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- History
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'History';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index', `_edit` = null, `_add` = null, `_delete` = null, `visible` = 1 
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Attachments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Attachments';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Attachments.index|Attachments.view', `_add` = 'Attachments.add', `_edit` = 'Attachments.edit', `_delete` = 'Attachments.remove', `_execute` = 'Attachments.download'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 1. Details

-- Positions
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Positions';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Positions.index|Positions.view', `_add` = 'Positions.add', `_edit` = 'Positions.edit', `_delete` = 'Positions.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Programmes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Programmes';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Programmes.index|Programmes.view', `_add` = 'Programmes.add', `_edit` = 'Programmes.edit', `_delete` = 'Programmes.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Shifts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Shifts';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Shifts.index|Shifts.view', `_add` = 'Shifts.add', `_edit` = 'Shifts.edit', `_delete` = 'Shifts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Sections
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Sections';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Sections.index|Sections.view', `_add` = 'Sections.add', `_edit` = 'Sections.edit', `_delete` = 'Sections.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Classes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Classes';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Classes.index|Classes.view', `_add` = 'Classes.add', `_edit` = 'Classes.edit', `_delete` = 'Classes.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Infrastructures
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Infrastructure';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Infrastructures.index|Infrastructures.view', `_add` = 'Infrastructures.add', `_edit` = 'Infrastructures.edit', `_delete` = 'Infrastructures.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;


-- 2. Students
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Students.index|Students.view', `_add` = 'Students.add', `_edit` = 'Students.edit', `_delete` = 'Students.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Behaviour' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StudentBehaviours.index|StudentBehaviours.view', `_add` = 'StudentBehaviours.add', `_edit` = 'StudentBehaviours.edit', `_delete` = 'StudentBehaviours.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Attendance
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Attendance' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StudentAttendances.index|StudentAbsences.index|StudentAbsences.view', `_add` = 'StudentAbsences.add', `_edit` = 'StudentAttendances.edit|StudentAbsences.edit', `_delete` = 'StudentAbsences.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Results
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Results' AND `category` = 'Students';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Assessments.index|Results.index', `_add` = 'Results.add', `_edit` = 'Results.edit', `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;


-- 3. Staff
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Staff' AND `category` = 'Staff';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Staff.index|Staff.view', `_add` = 'Staff.add', `_edit` = 'Staff.edit', `_delete` = 'Staff.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Behaviour' AND `category` = 'Staff';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StaffBehaviours.index|StaffBehaviours.view', `_add` = 'StaffBehaviours.add', `_edit` = 'StaffBehaviours.edit', `_delete` = 'StaffBehaviours.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Attendance
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Attendance' AND `category` = 'Staff';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StaffAttendances.index|StaffAbsences.index|StaffAbsences.view', `_add` = 'StaffAbsences.add', `_edit` = 'StaffAttendances.edit|StaffAbsences.edit', `_delete` = 'StaffAbsences.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4 . Finance

-- Bank Accounts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Bank Accounts' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'BankAccounts.index|BankAccounts.view', `_add` = 'BankAccounts.add', `_edit` = 'BankAccounts.edit', `_delete` = 'BankAccounts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Fees
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Fees' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Fees.index|Fees.view', `_add` = 'Fees.add', `_edit` = 'Fees.edit', `_delete` = 'Fees.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Fees
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Institutions' AND `name` = 'Students' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'StudentFees.index|StudentFees.view', `_add` = 'StudentFees.add', `_edit` = 'StudentFees.edit', `_delete` = 'StudentFees.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;


-- 5. Surveys

-- Student Module
DELETE FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Search';

SET @id := 2000;

-- 1. Overview
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Students' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index|view|Accounts.view', `_edit` = 'edit|Accounts.edit', `_add` = 'add', `_delete` = 'remove', `_execute` = 'excel'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Contacts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Contacts' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Contacts.index|Contacts.view', `_edit` = 'Contacts.edit', `_add` = 'Contacts.add', `_delete` = 'Contacts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Identities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Identities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Identities.index|Identities.view', `_edit` = 'Identities.edit', `_add` = 'Identities.add', `_delete` = 'Identities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Nationalities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Nationalities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Nationalities.index|Nationalities.view', `_edit` = 'Nationalities.edit', `_add` = 'Nationalities.add', `_delete` = 'Nationalities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Languages
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Languages' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Languages.index|Languages.view', `_edit` = 'Languages.edit', `_add` = 'Languages.add', `_delete` = 'Languages.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Comments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Comments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Comments.index|Comments.view', `_edit` = 'Comments.edit', `_add` = 'Comments.add', `_delete` = 'Comments.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Special Needs
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Special Needs' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'SpecialNeeds.index|SpecialNeeds.view', `_edit` = 'SpecialNeeds.edit', `_add` = 'SpecialNeeds.add', `_delete` = 'SpecialNeeds.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Awards
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Awards' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Awards.index|Awards.view', `_edit` = 'Awards.edit', `_add` = 'Awards.add', `_delete` = 'Awards.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 9. Attachments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Attachments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Attachments.index|Attachments.view', `_edit` = 'Attachments.edit', `_add` = 'Attachments.add', `_delete` = 'Attachments.remove', `_execute` = 'Attachments.download'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 10. History
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'History' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'History.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL, `visible` = 1
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Details
-- 1. Guardians
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Guardians' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Guardians.index|Guardians.view', `_edit` = 'Guardians.edit', `_add` = 'Guardians.add', `_delete` = 'Guardians.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Programmes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Programmes' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Programmes.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Sections
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Sections' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Sections.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Classes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Classes' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Classes.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Absence
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Absence' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Absences.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Behaviour' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Behaviours.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Results
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Results' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Results.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Extracurricular
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Extracurricular' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Extracurriculars.index|Extracurriculars.view', `_edit` = 'Extracurriculars.edit', `_add` = 'Extracurriculars.add', `_delete` = 'Extracurriculars.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Student Finance

-- 1. Bank Accounts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Bank Accounts' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'BankAccounts.index|BankAccounts.view', `_edit` = 'BankAccounts.edit', `_add` = 'BankAccounts.add', `_delete` = 'BankAccounts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Fees
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Fees' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Fees.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Removing Student Health permissions
DELETE FROM `security_functions` WHERE `controller` = 'Students' AND `category` = 'Health';

-- Update Student parent ids
UPDATE `security_functions` SET `parent_id` = 2000 WHERE `controller` = 'Students' AND `parent_id` <> -1;

-- SET @id := 2011;
-- SET @funcId := @id - 1;
-- UPDATE `security_functions` SET `id` = @id WHERE `id` = @funcId;
-- UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;

-- Staff Module
DELETE FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Search';

SET @id := 3000;

-- 1. Overview
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Staff' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'index|view|Accounts.view', `_edit` = 'edit|Accounts.edit', `_add` = 'add', `_delete` = 'remove', `_execute` = 'excel'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Contacts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Contacts' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Contacts.index|Contacts.view', `_edit` = 'Contacts.edit', `_add` = 'Contacts.add', `_delete` = 'Contacts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Identities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Identities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Identities.index|Identities.view', `_edit` = 'Identities.edit', `_add` = 'Identities.add', `_delete` = 'Identities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Nationalities
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Nationalities' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Nationalities.index|Nationalities.view', `_edit` = 'Nationalities.edit', `_add` = 'Nationalities.add', `_delete` = 'Nationalities.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Languages
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Languages' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Languages.index|Languages.view', `_edit` = 'Languages.edit', `_add` = 'Languages.add', `_delete` = 'Languages.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Comments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Comments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Comments.index|Comments.view', `_edit` = 'Comments.edit', `_add` = 'Comments.add', `_delete` = 'Comments.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Special Needs
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Special Needs' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'SpecialNeeds.index|SpecialNeeds.view', `_edit` = 'SpecialNeeds.edit', `_add` = 'SpecialNeeds.add', `_delete` = 'SpecialNeeds.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Awards
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Awards' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Awards.index|Awards.view', `_edit` = 'Awards.edit', `_add` = 'Awards.add', `_delete` = 'Awards.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 9. Attachments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Attachments' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Attachments.index|Attachments.view', `_edit` = 'Attachments.edit', `_add` = 'Attachments.add', `_delete` = 'Attachments.remove', `_execute` = 'Attachments.download'
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 10. History
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'History' AND `category` = 'General';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'History.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL, `visible` = 1
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Details

-- 1. Qualifications
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Qualifications' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Qualifications.index|Qualifications.view', `_edit` = 'Qualifications.edit', `_add` = 'Qualifications.add', `_delete` = 'Qualifications.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 2. Trainings
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Training' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Trainings.index|Trainings.view', `_edit` = 'Trainings.edit', `_add` = 'Trainings.add', `_delete` = 'Trainings.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 3. Positions
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Positions' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Positions.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 4. Sections
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Sections' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Sections.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 5. Classes
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Classes' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Classes.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 6. Absence
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Absence' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Absences.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 7. Leaves
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Leave' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Leaves.index|Leaves.view', `_edit` = 'Leaves.edit', `_add` = 'Leaves.add', `_delete` = 'Leaves.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 8. Behaviour
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Behaviour' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Behaviours.index', `_edit` = NULL, `_add` = NULL, `_delete` = NULL, `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 9. Extracurricular
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Extracurricular' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Extracurriculars.index|Extracurriculars.view', `_edit` = 'Extracurriculars.edit', `_add` = 'Extracurriculars.add', `_delete` = 'Extracurriculars.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 10. Employments
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Employment' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Employments.index|Employments.view', `_edit` = 'Employments.edit', `_add` = 'Employments.add', `_delete` = 'Employments.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 11. Salary
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Salary' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Salaries.index|Salaries.view', `_edit` = 'Salaries.edit', `_add` = 'Salaries.add', `_delete` = 'Salaries.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 12. Memberships
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Memberships' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Memberships.index|Memberships.view', `_edit` = 'Memberships.edit', `_add` = 'Memberships.add', `_delete` = 'Memberships.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- 13. Licenses
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Licenses' AND `category` = 'Details';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'Licenses.index|Licenses.view', `_edit` = 'Licenses.edit', `_add` = 'Licenses.add', `_delete` = 'Licenses.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Staff Finance

-- 1. Bank Accounts
SELECT `id` INTO @funcId FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Bank Accounts' AND `category` = 'Finance';
UPDATE `security_functions` SET `id` = @id, `order` = @id,
`_view` = 'BankAccounts.index|BankAccounts.view', `_edit` = 'BankAccounts.edit', `_add` = 'BankAccounts.add', `_delete` = 'BankAccounts.remove', `_execute` = NULL
WHERE `id` = @funcId;
UPDATE `security_role_functions` SET `security_function_id` = @id WHERE `security_function_id` = @funcId;
SET @id := @id + 1;

-- Removing Staff Health permissions
DELETE FROM `security_functions` WHERE `controller` = 'Staff' AND `category` = 'Health';

-- Update Student parent ids
UPDATE `security_functions` SET `parent_id` = 3000 WHERE `controller` = 'Staff' AND `parent_id` <> -1;

-- Clean up missing functions from roles
DELETE FROM `security_role_functions` 
WHERE NOT EXISTS (SELECT 1 FROM `security_functions` WHERE `security_functions`.`id` = `security_role_functions`.`security_function_id`);

ALTER TABLE `institution_site_staff_absences` CHANGE `first_date_absent` `start_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `last_date_absent` `end_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `start_time_absent` `start_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `end_time_absent` `end_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `full_day_absent` `full_day` INT( 1 ) NOT NULL ;
ALTER TABLE `institution_site_staff_absences` DROP `absence_type` ;

ALTER TABLE `institution_site_student_absences` ADD `institution_site_id` INT( 11 ) NOT NULL AFTER `institution_site_section_id` ;

UPDATE `institution_site_student_absences` 
JOIN `institution_site_sections` ON `institution_site_sections`.`id` = `institution_site_student_absences`.`institution_site_section_id`
SET `institution_site_student_absences`.`institution_site_id` = IFNULL(`institution_site_sections`.`institution_site_id`, 0);

-- Absence
ALTER TABLE `institution_site_student_absences` CHANGE `first_date_absent` `start_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `last_date_absent` `end_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `start_time_absent` `start_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `end_time_absent` `end_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `full_day_absent` `full_day` INT( 1 ) NOT NULL ;
ALTER TABLE `institution_site_student_absences` DROP `institution_site_section_id` ;
ALTER TABLE `institution_site_student_absences` DROP `absence_type` ;

-- 14th July 2015

ALTER TABLE `custom_field_types` ADD `visible` INT(1) NOT NULL DEFAULT '1' AFTER `is_unique`;
UPDATE `custom_field_types` SET `visible` = 1;
UPDATE `custom_field_types` SET `visible` = 0 WHERE `code` IN ('DATE', 'TIME');
UPDATE `custom_field_types` SET `value` = 'number_value' WHERE `code` = 'CHECKBOX';

RENAME TABLE `survey_form_questions` TO `survey_forms_questions`;
ALTER TABLE `survey_forms_questions` ADD `section` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `survey_question_id`;
ALTER TABLE `survey_forms_questions` CHANGE `name` `name` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- patch survey_forms
TRUNCATE TABLE `survey_forms`;
INSERT INTO `survey_forms` (`id`, `name`, `description`, `custom_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `description`, `survey_module_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_templates`;

-- patch survey_questions
TRUNCATE TABLE `survey_questions`;
INSERT INTO `survey_questions` (`id`, `name`, `field_type`, `is_mandatory`, `is_unique`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`,
  CASE
    WHEN `type` = 2 THEN 'TEXT'
      WHEN `type` = 3 THEN 'DROPDOWN'
      WHEN `type` = 4 THEN 'CHECKBOX'
      WHEN `type` = 5 THEN 'TEXTAREA'
      WHEN `type` = 6 THEN 'NUMBER'
      WHEN `type` = 7 THEN 'TABLE'
      ELSE '-1'
  END,
  `is_mandatory`, `is_unique`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_questions`
WHERE `type` != 1;

-- patch survey_question_choices
TRUNCATE TABLE `survey_question_choices`;
INSERT INTO `survey_question_choices` (`id`, `name`, `is_default`, `visible`, `order`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `value`, `default_option`, `visible`, `order`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_question_choices`;

-- patch survey_table_columns
TRUNCATE TABLE `survey_table_columns`;
INSERT INTO `survey_table_columns` (`id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_table_columns`;

-- patch survey_table_rows
TRUNCATE TABLE `survey_table_rows`;
INSERT INTO `survey_table_rows` (`id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `order`, `visible`, `survey_question_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_survey_table_rows`;

-- patch survey_forms_questions
DELIMITER $$

DROP PROCEDURE IF EXISTS survey_patch
$$
CREATE PROCEDURE survey_patch()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE questionId, formId INT(11);
  DECLARE questionOrder INT(3);
  DECLARE questionType INT(1);
  DECLARE questionName VARCHAR(250);
  DECLARE sectionName VARCHAR(250);
  DECLARE sfq CURSOR FOR 
    SELECT `SurveyQuestions`.`id`, `SurveyQuestions`.`name`, `SurveyQuestions`.`type`, `SurveyQuestions`.`order`, `SurveyQuestions`.`survey_template_id`
    FROM `z_1461_survey_questions` AS `SurveyQuestions`
    ORDER BY `SurveyQuestions`.`survey_template_id`, `SurveyQuestions`.`order`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN sfq;
  TRUNCATE TABLE `survey_forms_questions`;

  read_loop: LOOP
  FETCH sfq INTO questionId, questionName, questionType, questionOrder, formId;
  IF done THEN
    LEAVE read_loop;
  END IF;

    IF questionType = 1 THEN
      SET @sectionName = questionName;
    END IF;

    IF questionType <> 1 THEN
      INSERT INTO `survey_forms_questions` (`id`, `survey_form_id`, `survey_question_id`, `section`, `order`) VALUES (uuid(), formId, questionId, @sectionName, questionOrder);
    END IF;

  END LOOP read_loop;

  CLOSE sfq;
END
$$

CALL survey_patch
$$

DROP PROCEDURE IF EXISTS survey_patch
$$

DELIMITER ;

-- patch institution_site_surveys
TRUNCATE TABLE `institution_site_surveys`;
INSERT INTO `institution_site_surveys` (`id`, `status`, `academic_period_id`, `survey_form_id`, `institution_site_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `status`, `academic_period_id`, `survey_template_id`, `institution_site_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_surveys`;

-- patch institution_site_survey_answers
TRUNCATE TABLE `institution_site_survey_answers`;
INSERT INTO `institution_site_survey_answers` (`id`, `text_value`, `number_value`, `textarea_value`, `survey_question_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `text_value`, `int_value`, `textarea_value`, `survey_question_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_survey_answers`;

-- patch institution_site_survey_table_cells
TRUNCATE TABLE `institution_site_survey_table_cells`;
INSERT INTO `institution_site_survey_table_cells` (`id`, `text_value`, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `value`, `survey_question_id`, `survey_table_column_id`, `survey_table_row_id`, `institution_site_survey_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_1461_institution_site_survey_table_cells`;

ALTER TABLE `student_guardians` CHANGE `security_user_id` `student_user_id` INT(11) NOT NULL COMMENT 'is security_user.id';
ALTER TABLE `student_guardians` CHANGE `guardian_id` `guardian_user_id` INT(11) NOT NULL COMMENT 'is security_user.id';
ALTER TABLE `student_guardians` ADD INDEX( `student_user_id`, `guardian_user_id`);

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`) VALUES (NULL, 'Guardian Prefix', 'guardian_prefix', 'Auto Generated OpenEMIS ID', 'Guardian Prefix', ',0', ',0', '1', '1', '', '');

CREATE TABLE guardian_activities LIKE student_activities;

-- DB version
UPDATE `config_items` SET `value` = '3.0.1' WHERE `code` = 'db_version';
-- end DB version