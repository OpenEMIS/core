<?php
use Migrations\AbstractMigration;

class POCOR7395 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup Table
        $this->execute("CREATE TABLE `field_options` (
            `id` int NOT NULL,
            `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
            `category` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
            `table_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
            `order` int DEFAULT NULL,
            `modified_by` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_by` int NOT NULL,
            `created` datetime NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->execute("INSERT INTO `field_options` (`id`, `name`, `category`, `table_name`, `order`, `modified_by`, `modified`, `created_by`, `created`) VALUES
        (1, 'Localities', 'Institution', 'institution_localities', 1, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (2, 'Duties', 'Institution', 'staff_duties', 2, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (3, 'Ownerships', 'Institution', 'institution_ownerships', 3, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (4, 'Sectors', 'Institution', 'institution_sectors', 4, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (5, 'Providers', 'Institution', 'institution_providers', 5, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (6, 'Types', 'Institution', 'institution_types', 6, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (7, 'Unit', 'Institution', 'institution_units', 7, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (8, 'Course', 'Institution', 'institution_courses', 8, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (9, 'Shift Options', 'Institution', 'shift_options', 9, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (10, 'Textbook Conditions', 'Institution', 'textbook_conditions', 10, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (11, 'Report Card Comment Codes', 'Institution', 'report_card_comment_codes', 11, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (12, 'Institution Committee Types', 'Institution', 'institution_committee_types', 12, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (13, 'Institution Attachment Types', 'Institution', 'institution_attachment_types', 13, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (14, 'Student Absence Reasons', 'Student', 'student_absence_reasons', 14, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (15, 'Student Behaviour Categories', 'Student', 'student_behaviour_categories', 15, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (16, 'Student Transfer Reasons', 'Student', 'student_transfer_reasons', 16, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (17, 'Student Withdraw Reasons', 'Student', 'student_withdraw_reasons', 17, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (18, 'Guidance Types', 'Student', 'guidance_types', 18, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (19, 'Visit Purpose Types', 'Student', 'student_visit_purpose_types', 19, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (20, 'Student Attachment Types', 'Student', 'student_attachment_types', 20, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (21, 'Meal Types', 'Meals', 'meal_programme_types', 21, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (22, 'Meal Targets', 'Meals', 'meal_target_types', 22, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (23, 'Meal Nutritions', 'Meals', 'meal_nutritions', 23, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (24, 'Meal Implementers', 'Meals', 'meal_implementers', 24, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (25, 'Meal Benefit Types', 'Meals', 'meal_benefits', 25, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (26, 'Staff Behaviour Categories', 'Staff', 'staff_behaviour_categories', 26, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (27, 'Staff Leave Types', 'Staff', 'staff_leave_types', 27, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (28, 'Staff Types', 'Staff', 'staff_types', 28, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (29, 'Staff Training Categories', 'Staff', 'staff_training_categories', 29, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (30, 'Staff Attachment Types', 'Staff', 'staff_attachment_types', 30, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (31, 'Staff Position Categories', 'Staff', 'staff_position_categories', 31, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (32, 'Banks', 'Finance', 'banks', 32, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (33, 'Bank Branches', 'Finance', 'bank_branches', 33, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (34, 'Fee Types', 'Finance', 'fee_types', 34, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (35, 'Budget Types', 'Finance', 'budget_types', 35, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (36, 'Income Sources', 'Finance', 'income_sources', 36, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (37, 'Income Types', 'Finance', 'income_types', 37, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (38, 'Expenditure Types', 'Finance', 'expenditure_types', 38, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (39, 'Guardian Relations', 'Guardian', 'guardian_relations', 39, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (40, 'Staff Position Grades', 'Position', 'staff_position_grades', 40, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (41, 'Staff Position Titles', 'Position', 'staff_position_titles', 41, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (42, 'Qualification Levels', 'Qualification', 'qualification_levels', 42, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (43, 'Qualification Titles', 'Qualification', 'qualification_titles', 43, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (44, 'Qualification Specialisations', 'Qualification', 'qualification_specialisations', 44, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (45, 'Quality Visit Types', 'Quality', 'quality_visit_types', 45, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (46, 'Salary Addition Types', 'Salary', 'salary_addition_types', 46, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (47, 'Salary Deduction Types', 'Salary', 'salary_deduction_types', 47, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (48, 'Training Course Types', 'Training', 'training_course_types', 48, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (49, 'Training Field Studies', 'Training', 'training_field_of_studies', 49, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (50, 'Training Levels', 'Training', 'training_levels', 50, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (51, 'Training Mode Deliveries', 'Training', 'training_mode_deliveries', 51, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (52, 'Training Need Categories', 'Training', 'training_need_categories', 52, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (53, 'Training Need Competencies', 'Training', 'training_need_competencies', 53, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (54, 'Training Need Standards', 'Training', 'training_need_standards', 54, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (55, 'Training Need Sub Standards', 'Training', 'training_need_sub_standards', 55, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (56, 'Training Priorities', 'Training', 'training_priorities', 56, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (57, 'Training Providers', 'Training', 'training_providers', 57, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (58, 'Training Requirements', 'Training', 'training_requirements', 58, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (59, 'Training Specialisations', 'Training', 'training_specialisations', 59, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (60, 'Training Course Categories', 'Training', 'training_course_categories', 60, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (61, 'Contact Types', 'Others', 'contact_types', 61, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (62, 'Employment Status Types', 'Others', 'employment_status_types', 62, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (63, 'Extracurricular Types', 'Others', 'extracurricular_types', 63, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (64, 'Identity Types', 'Others', 'identity_types', 64, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (65, 'Languages', 'Others', 'languages', 65, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (66, 'License Types', 'Others', 'license_types', 66, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (67, 'License Classifications', 'Others', 'license_classifications', 67, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (68, 'Countries', 'Others', 'countries', 68, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (69, 'Nationalities', 'Others', 'nationalities', 69, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (70, 'Comment Types', 'Others', 'comment_types', 70, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (71, 'Behaviour Classifications', 'Others', 'behaviour_classifications', 71, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (72, 'Demographic Wealth Quantile Types', 'Others', 'demographic_types', 72, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (73, 'Infrastructure Ownerships', 'Infrastructure', 'infrastructure_ownerships', 73, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (74, 'Infrastructure Conditions', 'Infrastructure', 'infrastructure_conditions', 74, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (75, 'Infrastructure Need Types', 'Infrastructure', 'infrastructure_need_types', 75, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (76, 'Infrastructure Project Funding Sources', 'Infrastructure', 'infrastructure_project_funding_sources', 76, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (77, 'Infrastructure WASH Water Types', 'Infrastructure', 'infrastructure_wash_water_types', 77, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (78, 'Infrastructure WASH Water Functionalities', 'Infrastructure', 'infrastructure_wash_water_functionalities', 78, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (79, 'Infrastructure WASH Water Proximities', 'Infrastructure', 'infrastructure_wash_water_proximities', 79, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (80, 'Infrastructure WASH Water Quantities', 'Infrastructure', 'infrastructure_wash_water_quantities', 80, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (81, 'Infrastructure WASH Water Qualities', 'Infrastructure', 'infrastructure_wash_water_qualities', 81, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (82, 'Infrastructure WASH Water Accessibilities', 'Infrastructure', 'infrastructure_wash_water_accessibilities', 82, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (83, 'Infrastructure WASH Sanitation Types', 'Infrastructure', 'infrastructure_wash_sanitation_types', 83, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (84, 'Infrastructure WASH Sanitation Uses', 'Infrastructure', 'infrastructure_wash_sanitation_uses', 84, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (85, 'Infrastructure WASH Sanitation Qualities', 'Infrastructure', 'infrastructure_wash_sanitation_qualities', 85, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (86, 'Infrastructure WASH Sanitation Accessibilities', 'Infrastructure', 'infrastructure_wash_sanitation_accessibilities', 86, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (87, 'Infrastructure WASH Hygiene Types', 'Infrastructure', 'infrastructure_wash_hygiene_types', 87, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (88, 'Infrastructure WASH Hygiene Soap/Ash Availabilities', 'Infrastructure', 'infrastructure_wash_hygiene_soapash_availabilities', 88, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (89, 'Infrastructure WASH Hygiene Educations', 'Infrastructure', 'infrastructure_wash_hygiene_educations', 89, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (90, 'Infrastructure WASH Waste Types', 'Infrastructure', 'infrastructure_wash_waste_types', 90, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (91, 'Infrastructure WASH Waste Functionalities', 'Infrastructure', 'infrastructure_wash_waste_functionalities', 91, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (92, 'Infrastructure WASH Sewage Types', 'Infrastructure', 'infrastructure_wash_sewage_types', 92, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (93, 'Infrastructure WASH Sewage Functionalities', 'Infrastructure', 'infrastructure_wash_sewage_functionalities', 93, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (94, 'Utility Electricity Types', 'Infrastructure', 'utility_electricity_types', 94, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (95, 'Utility Electricity Conditions', 'Infrastructure', 'utility_electricity_conditions', 95, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (96, 'Utility Internet Types', 'Infrastructure', 'utility_internet_types', 96, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (97, 'Utility Internet Conditions', 'Infrastructure', 'utility_internet_conditions', 97, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (98, 'Utility Internet Bandwidths', 'Infrastructure', 'utility_internet_bandwidths', 98, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (99, 'Utility Telephone Types', 'Infrastructure', 'utility_telephone_types', 99, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (100, 'Utility Telephone Conditions', 'Infrastructure', 'utility_telephone_conditions', 100, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (101, 'Asset Types', 'Infrastructure', 'asset_types', 101, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (102, 'Asset Conditions', 'Infrastructure', 'asset_conditions', 102, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (103, 'Allergy Types', 'Health', 'health_allergy_types', 103, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (104, 'Conditions', 'Health', 'health_conditions', 104, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (105, 'Consultation Types', 'Health', 'health_consultation_types', 105, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (106, 'Vaccinations', 'Health', 'health_immunization_types', 106, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (107, 'Relationships', 'Health', 'health_relationships', 107, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (108, 'Test Types', 'Health', 'health_test_types', 108, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (109, 'Insurance Providers', 'Health', 'insurance_providers', 109, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (110, 'Insurance Types', 'Health', 'insurance_types', 110, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (111, 'Transport Features', 'Transport', 'transport_features', 111, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (112, 'Bus Types', 'Transport', 'bus_types', 112, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (113, 'Trip Types', 'Transport', 'trip_types', 113, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (114, 'Scholarship Funding Sources', 'Scholarship', 'scholarship_funding_sources', 114, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (115, 'Scholarship Attachment Types', 'Scholarship', 'scholarship_attachment_types', 115, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (116, 'Scholarship Payment Frequencies', 'Scholarship', 'scholarship_payment_frequencies', 116, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (117, 'Scholarship Recipient Activity Statuses', 'Scholarship', 'scholarship_recipient_activity_statuses', 117, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (118, 'Scholarship Disbursement Categories', 'Scholarship', 'scholarship_disbursement_categoriess', 118, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (119, 'Scholarship Semesters', 'Scholarship', 'scholarship_semesters', 119, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (120, 'Scholarship Institution Choices', 'Scholarship', 'scholarship_application_institution_choices', 120, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (121, 'Scholarship Financial Assistances', 'Scholarship', 'scholarship_financial_assistances', 121, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (122, 'Special Needs Types', 'Special Needs', 'special_need_types', 122, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (123, 'Special Needs Assessments Types', 'Special Needs', 'special_need_types', 123, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (124, 'Special Needs Difficulties', 'Special Needs', 'special_need_difficulties', 124, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (125, 'Special Needs Referrer Types', 'Special Needs', 'special_needs_referrer_types', 125, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (126, 'Special Needs Service Types', 'Special Needs', 'special_needs_service_types', 126, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (127, 'Special Needs Device Types', 'Special Needs', 'special_needs_device_types', 127, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (128, 'Plan Types', 'Special Needs', 'special_needs_plan_types', 128, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (129, 'Diagnostic Type Of Disability', 'Special Needs', 'special_needs_diagnostics_types', 129, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (130, 'Diagnostic Disability Degree', 'Special Needs', 'special_needs_diagnostics_degree', 130, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (131, 'Special Needs Service Classification', 'Special Needs', 'special_needs_service_classification', 131, NULL, NULL, 1, '2023-05-09 12:00:00'),
        (132, 'Language Proficiencies', 'Others', 'language_proficiencies', 132, NULL, NULL, 1, '2023-05-09 12:00:00')");
        
        //Insert security functions for User Group List
        $this->execute("ALTER TABLE `field_options` ADD PRIMARY KEY (`id`)");      
        $this->execute("ALTER TABLE `field_options`
        MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;
      COMMIT");      
    }

    public function down()
    {
        // Field Options
        $this->execute('DROP TABLE IF EXISTS `field_options`');
        $this->execute('RENAME TABLE `zz_7395_field_options` TO `field_options`');
    }
}
