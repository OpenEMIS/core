TRUNCATE TABLE field_options;
INSERT field_options SELECT * FROM 1136_field_options;

TRUNCATE TABLE field_option_values;
INSERT field_option_values SELECT * FROM 1136_field_option_values;

RENAME TABLE 1136_institution_site_types TO institution_site_types;
RENAME TABLE 1136_institution_site_ownership TO institution_site_ownership;
RENAME TABLE 1136_institution_site_localities TO institution_site_localities;
RENAME TABLE 1136_institution_site_statuses TO institution_site_statuses;
RENAME TABLE 1136_assessment_result_types TO assessment_result_types;
RENAME TABLE 1136_employment_types TO employment_types;
RENAME TABLE 1136_extracurricular_types TO extracurricular_types;
RENAME TABLE 1136_languages TO languages;
RENAME TABLE 1136_identity_types TO identity_types;
RENAME TABLE 1136_license_types TO license_types;
RENAME TABLE 1136_special_need_types TO special_need_types;
RENAME TABLE 1136_quality_visit_types TO quality_visit_types;
RENAME TABLE 1136_health_relationships TO health_relationships;
RENAME TABLE 1136_health_conditions TO health_conditions;
RENAME TABLE 1136_health_immunizations TO health_immunizations;
RENAME TABLE 1136_health_allergy_types TO health_allergy_types;
RENAME TABLE 1136_health_test_types TO health_test_types;
RENAME TABLE 1136_health_consultation_types TO health_consultation_types;
RENAME TABLE 1136_salary_addition_types TO salary_addition_types;
RENAME TABLE 1136_salary_deduction_types TO salary_deduction_types;
RENAME TABLE 1136_training_course_types TO training_course_types;
RENAME TABLE 1136_training_field_studies TO training_field_studies;
RENAME TABLE 1136_training_levels TO training_levels;
RENAME TABLE 1136_training_mode_deliveries TO training_mode_deliveries;
RENAME TABLE 1136_training_priorities TO training_priorities;
RENAME TABLE 1136_training_providers TO training_providers;
RENAME TABLE 1136_training_requirements TO training_requirements;
RENAME TABLE 1136_training_statuses TO training_statuses;
RENAME TABLE 1136_student_categories TO student_categories;
RENAME TABLE 1136_student_behaviour_categories TO student_behaviour_categories;
RENAME TABLE 1136_staff_position_titles TO staff_position_titles;
RENAME TABLE 1136_staff_position_grades TO staff_position_grades;
RENAME TABLE 1136_staff_position_steps TO staff_position_steps;
RENAME TABLE 1136_qualification_specialisations TO qualification_specialisations;

-- replacing country from config options
INSERT INTO `config_items` (`id`, `name`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(null, 'country_id', 'Nationality', 'Default Country', '171', '1', 1, 1, 'Dropdown', 'database:Country', 108, '2014-04-02 16:48:25', 1, '0000-00-00 00:00:00');