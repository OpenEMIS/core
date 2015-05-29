-- May 27th 1142hrs
-- For field options to work with plural alias (ROLLBACK)
update field_options set code = 'InstitutionSiteLocality' where code = 'InstitutionSiteLocalities' limit 1;
update field_options set code = 'InstitutionSiteOwnership' where code = 'InstitutionSiteOwnerships' limit 1;
update field_options set code = 'InstitutionSiteProvider' where code = 'InstitutionSiteProviders' limit 1;
update field_options set code = 'InstitutionSiteSector' where code = 'InstitutionSiteSectors' limit 1;
update field_options set code = 'InstitutionSiteStatus' where code = 'InstitutionSiteStatuses' limit 1;
update field_options set code = 'InstitutionSiteType' where code = 'InstitutionSiteTypes' limit 1;
update field_options set code = 'InstitutionSiteCustomField' where code = 'InstitutionSiteCustomFields' limit 1;
update field_options set code = 'CensusCustomFieldOption' where code = 'CensusCustomFieldOptions' limit 1;
update field_options set code = 'CensusCustomField' where code = 'CensusCustomFields' limit 1;
update field_options set code = 'CensusGrid' where code = 'CensusGrids' limit 1;
update field_options set code = 'StudentAbsenceReason' where code = 'StudentAbsenceReasons' limit 1;
update field_options set code = 'StudentBehaviourCategory' where code = 'StudentBehaviourCategories' limit 1;
update field_options set code = 'StudentCategory' where code = 'StudentCategories' limit 1;
update field_options set code = 'Gender' where code = 'Genders' limit 1;
update field_options set code = 'StudentStatus' where code = 'StudentStatuses' limit 1;
update field_options set code = 'StudentCustomField' where code = 'StudentCustomFields' limit 1;
update field_options set code = 'StaffAbsenceReason' where code = 'StaffAbsenceReasons' limit 1;
update field_options set code = 'StaffBehaviourCategory' where code = 'StaffBehaviourCategories' limit 1;
update field_options set code = 'LeaveStatus' where code = 'LeaveStatuses' limit 1;
update field_options set code = 'StaffLeaveType' where code = 'StaffLeaveTypes' limit 1;
update field_options set code = 'StaffType' where code = 'StaffTypes' limit 1;
update field_options set code = 'StaffStatus' where code = 'StaffStatuses' limit 1;
update field_options set code = 'StaffTrainingCategory' where code = 'StaffTrainingCategories' limit 1;
update field_options set code = 'StaffCustomField' where code = 'StaffCustomFields' limit 1;
update field_options set code = 'AssessmentResultType' where code = 'AsssesmentResultTypes' limit 1;
update field_options set code = 'Bank' where code = 'Banks' limit 1;
update field_options set code = 'BankBranch' where code = 'BankBranches' limit 1;
update field_options set code = 'FinanceNature' where code = 'FinanceNatures' limit 1;
update field_options set code = 'FinanceType' where code = 'FinanceTypes' limit 1;
update field_options set code = 'FinanceCategory' where code = 'FinanceCategories' limit 1;
update field_options set code = 'FinanceSource' where code = 'FinanceSources' limit 1;
update field_options set code = 'FeeType' where code = 'FeeTypes' limit 1;
update field_options set code = 'GuardianEducationLevel' where code = 'GuardianEducationLevels' limit 1;
update field_options set code = 'GuardianRelation' where code = 'GuardianRelations' limit 1;
update field_options set code = 'HealthAllergyType' where code = 'HealthAllergyTypes' limit 1;
update field_options set code = 'HealthCondition' where code = 'HealthConditions' limit 1;
update field_options set code = 'HealthConsultationType' where code = 'HealthConsultationTypes' limit 1;
update field_options set code = 'HealthImmunization' where code = 'HealthImmunizations' limit 1;
update field_options set code = 'HealthRelationship' where code = 'HealthRelationships' limit 1;
update field_options set code = 'HealthTestType' where code = 'HealthTestTypes' limit 1;
update field_options set code = 'InfrastructureBuilding' where code = 'InfrastructureBuildings' limit 1;
update field_options set code = 'InfrastructureCategory' where code = 'InfrastructureCategories' limit 1;
update field_options set code = 'InfrastructureEnergy' where code = 'InfrastructureEnergies' limit 1;
update field_options set code = 'InfrastructureFurniture' where code = 'InfrastructureFurnitures' limit 1;
update field_options set code = 'InfrastructureMaterial' where code = 'InfrastructureMaterials' limit 1;
update field_options set code = 'InfrastructureResource' where code = 'InfrastructureResources' limit 1;
update field_options set code = 'InfrastructureRoom' where code = 'InfrastructureRooms' limit 1;
update field_options set code = 'InfrastructureSanitation' where code = 'InfrastructureSanitations' limit 1;
update field_options set code = 'SanitationGender' where code = 'SanitationGenders' limit 1;
update field_options set code = 'InfrastructureStatus' where code = 'InfrastructureStatuses' limit 1;
update field_options set code = 'InfrastructureWater' where code = 'InfrastructureWaters' limit 1;
update field_options set code = 'StaffPositionGrade' where code = 'StaffPositionGrades' limit 1;
update field_options set code = 'StaffPositionStep' where code = 'StaffPositionSteps' limit 1;
update field_options set code = 'StaffPositionTitle' where code = 'StaffPositionTitles' limit 1;
update field_options set code = 'QualificationLevel' where code = 'QualificationLevels' limit 1;
update field_options set code = 'QualificationSpecialisation' where code = 'QualificationSpecialisations' limit 1;
update field_options set code = 'QualityVisitType' where code = 'QualityVisitTypes' limit 1;
update field_options set code = 'SalaryAdditionType' where code = 'SalaryAdditionTypes' limit 1;
update field_options set code = 'SalaryDeductionType' where code = 'SalaryDeductionTypes' limit 1;
update field_options set code = 'TrainingAchievementType' where code = 'TrainingAchievementTypes' limit 1;
update field_options set code = 'TrainingCourseType' where code = 'TrainingCourseTypes' limit 1;
update field_options set code = 'TrainingFieldStudy' where code = 'TrainingFieldStudies' limit 1;
update field_options set code = 'TrainingLevel' where code = 'TrainingLevels' limit 1;
update field_options set code = 'TrainingModeDelivery' where code = 'TrainingModeDeliveries' limit 1;
update field_options set code = 'TrainingNeedCategory' where code = 'TrainingNeedCategories' limit 1;
update field_options set code = 'TrainingPriority' where code = 'TrainingPriorities' limit 1;
update field_options set code = 'TrainingProvider' where code = 'TrainingProviders' limit 1;
update field_options set code = 'TrainingRequirement' where code = 'TrainingRequirements' limit 1;
update field_options set code = 'TrainingResultType' where code = 'TrainingResultTypes' limit 1;
update field_options set code = 'TrainingStatus' where code = 'TrainingStatuses' limit 1;
update field_options set code = 'ContactType' where code = 'ContactTypes' limit 1;
update field_options set code = 'EmploymentType' where code = 'EmploymentTypes' limit 1;
update field_options set code = 'ExtracurricularType' where code = 'ExtracurricularTypes' limit 1;
update field_options set code = 'IdentityType' where code = 'IdentityTypes' limit 1;
update field_options set code = 'Language' where code = 'Languages' limit 1;
update field_options set code = 'LicenseType' where code = 'LicenseTypes' limit 1;
update field_options set code = 'SpecialNeedType' where code = 'SpecialNeedTypes' limit 1;
update field_options set code = 'InfrastructureOwnership' where code = 'InfrastructureOwnerships' limit 1;
update field_options set code = 'InfrastructureCondition' where code = 'InfrastructureConditions' limit 1;
update field_options set code = 'Country' where code = 'Countries' limit 1;

-- May 28th 1737hrs
-- changing student and staff id to security user id (rollback)
DROP TABLE assessment_item_results;
RENAME TABLE z1407_assessment_item_results TO assessment_item_results;
DROP TABLE assessment_results;
RENAME TABLE z1407_assessment_results TO assessment_results;
DROP TABLE institution_site_class_students;
RENAME TABLE z1407_institution_site_class_students TO institution_site_class_students;
DROP TABLE institution_site_section_students;
RENAME TABLE z1407_institution_site_section_students TO institution_site_section_students;
DROP TABLE institution_site_student_absences;
RENAME TABLE z1407_institution_site_student_absences TO institution_site_student_absences;
DROP TABLE institution_site_students;
RENAME TABLE z1407_institution_site_students TO institution_site_students;
DROP TABLE student_activities;
RENAME TABLE z1407_student_activities TO student_activities;
DROP TABLE student_attachments;
RENAME TABLE z1407_student_attachments TO student_attachments;
DROP TABLE student_attendances;
RENAME TABLE z1407_student_attendances TO student_attendances;
DROP TABLE student_bank_accounts;
RENAME TABLE z1407_student_bank_accounts TO student_bank_accounts;
DROP TABLE student_behaviours;
RENAME TABLE z1407_student_behaviours TO student_behaviours;
DROP TABLE student_custom_value_history;
RENAME TABLE z1407_student_custom_value_history TO student_custom_value_history;
DROP TABLE student_custom_values;
RENAME TABLE z1407_student_custom_values TO student_custom_values;
DROP TABLE student_details_custom_values;
RENAME TABLE z1407_student_details_custom_values TO student_details_custom_values;
DROP TABLE student_extracurriculars;
RENAME TABLE z1407_student_extracurriculars TO student_extracurriculars;
DROP TABLE student_fees;
RENAME TABLE z1407_student_fees TO student_fees;
DROP TABLE student_guardians;
RENAME TABLE z1407_student_guardians TO student_guardians;
DROP TABLE student_health_allergies;
RENAME TABLE z1407_student_health_allergies TO student_health_allergies;
DROP TABLE student_health_consultations;
RENAME TABLE z1407_student_health_consultations TO student_health_consultations;
DROP TABLE student_health_families;
RENAME TABLE z1407_student_health_families TO student_health_families;
DROP TABLE student_health_histories;
RENAME TABLE z1407_student_health_histories TO student_health_histories;
DROP TABLE student_health_immunizations;
RENAME TABLE z1407_student_health_immunizations TO student_health_immunizations;
DROP TABLE student_health_medications;
RENAME TABLE z1407_student_health_medications TO student_health_medications;
DROP TABLE student_health_tests;
RENAME TABLE z1407_student_health_tests TO student_health_tests;
DROP TABLE student_healths;
RENAME TABLE z1407_student_healths TO student_healths;
DROP TABLE institution_site_class_staff;
RENAME TABLE z1407_institution_site_class_staff TO institution_site_class_staff;
DROP TABLE institution_site_quality_rubrics;
RENAME TABLE z1407_institution_site_quality_rubrics TO institution_site_quality_rubrics;
DROP TABLE institution_site_quality_visits;
RENAME TABLE z1407_institution_site_quality_visits TO institution_site_quality_visits;
DROP TABLE institution_site_section_staff;
RENAME TABLE z1407_institution_site_section_staff TO institution_site_section_staff;
DROP TABLE institution_site_sections;
RENAME TABLE z1407_institution_site_sections TO institution_site_sections;
DROP TABLE institution_site_staff;
RENAME TABLE z1407_institution_site_staff TO institution_site_staff;
DROP TABLE institution_site_staff_absences;
RENAME TABLE z1407_institution_site_staff_absences TO institution_site_staff_absences;
DROP TABLE staff_activities;
RENAME TABLE z1407_staff_activities TO staff_activities;
DROP TABLE staff_attachments;
RENAME TABLE z1407_staff_attachments TO staff_attachments;
DROP TABLE staff_attendances;
RENAME TABLE z1407_staff_attendances TO staff_attendances;
DROP TABLE staff_bank_accounts;
RENAME TABLE z1407_staff_bank_accounts TO staff_bank_accounts;
DROP TABLE staff_behaviours;
RENAME TABLE z1407_staff_behaviours TO staff_behaviours;
DROP TABLE staff_custom_value_history;
RENAME TABLE z1407_staff_custom_value_history TO staff_custom_value_history;
DROP TABLE staff_custom_values;
RENAME TABLE z1407_staff_custom_values TO staff_custom_values;
DROP TABLE staff_details_custom_values;
RENAME TABLE z1407_staff_details_custom_values TO staff_details_custom_values;
DROP TABLE staff_employments;
RENAME TABLE z1407_staff_employments TO staff_employments;
DROP TABLE staff_extracurriculars;
RENAME TABLE z1407_staff_extracurriculars TO staff_extracurriculars;
DROP TABLE staff_health_allergies;
RENAME TABLE z1407_staff_health_allergies TO staff_health_allergies;
DROP TABLE staff_health_consultations;
RENAME TABLE z1407_staff_health_consultations TO staff_health_consultations;
DROP TABLE staff_health_families;
RENAME TABLE z1407_staff_health_families TO staff_health_families;
DROP TABLE staff_health_histories;
RENAME TABLE z1407_staff_health_histories TO staff_health_histories;
DROP TABLE staff_health_immunizations;
RENAME TABLE z1407_staff_health_immunizations TO staff_health_immunizations;
DROP TABLE staff_health_medications;
RENAME TABLE z1407_staff_health_medications TO staff_health_medications;
DROP TABLE staff_health_tests;
RENAME TABLE z1407_staff_health_tests TO staff_health_tests;
DROP TABLE staff_healths;
RENAME TABLE z1407_staff_healths TO staff_healths;
DROP TABLE staff_leaves;
RENAME TABLE z1407_staff_leaves TO staff_leaves;
DROP TABLE staff_licenses;
RENAME TABLE z1407_staff_licenses TO staff_licenses;
DROP TABLE staff_memberships;
RENAME TABLE z1407_staff_memberships TO staff_memberships;
DROP TABLE staff_qualifications;
RENAME TABLE z1407_staff_qualifications TO staff_qualifications;
DROP TABLE staff_salaries;
RENAME TABLE z1407_staff_salaries TO staff_salaries;
DROP TABLE staff_training;
RENAME TABLE z1407_staff_training TO staff_training;
DROP TABLE staff_training_needs;
RENAME TABLE z1407_staff_training_needs TO staff_training_needs;
DROP TABLE staff_training_self_studies;
RENAME TABLE z1407_staff_training_self_studies TO staff_training_self_studies;
DROP TABLE training_session_trainees;
RENAME TABLE z1407_training_session_trainees TO training_session_trainees;