<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AcademicPeriodsControllerDeleteTest extends AppTestCase
{
    public $fixtures = [
        'app.absence_types',
        'app.academic_period_levels',
        'app.academic_periods',
        'app.alert_logs',
        'app.alerts_roles',
        'app.alerts',
        'app.api_authorizations',
        'app.area_administrative_levels',
        'app.area_administratives',
        'app.area_levels',
        'app.areas',
        'app.assessment_grading_options',
        'app.assessment_grading_types',
        'app.assessment_item_results',
        'app.assessment_items',
        'app.assessment_items_grading_types',
        'app.assessment_periods',
        'app.assessments',
        'app.authentication_type_attributes',
        'app.bank_branches',
        'app.banks',
        'app.comment_types',
        'app.competencies',
        'app.competency_sets',
        'app.competency_sets_competencies',
        'app.config_attachments',
        'app.config_item_options',
        'app.config_items',
        'app.config_product_lists',
        'app.contact_options',
        'app.contact_types',
        'app.countries',
        'app.custom_field_options',
        'app.custom_field_types',
        'app.custom_field_values',
        'app.custom_fields',
        'app.custom_forms',
        'app.custom_forms_fields',
        'app.custom_forms_filters',
        'app.custom_modules',
        'app.custom_records',
        'app.custom_table_cells',
        'app.custom_table_columns',
        'app.custom_table_rows',
        'app.db_patches',
        'app.deleted_records',
        'app.education_certifications',
        'app.education_cycles',
        'app.education_field_of_studies',
        'app.education_grades',
        'app.education_grades_subjects',
        'app.education_level_isced',
        'app.education_levels',
        'app.education_programme_orientations',
        'app.education_programmes',
        'app.education_programmes_next_programmes',
        'app.education_subjects',
        'app.education_systems',
        'app.employment_types',
        'app.examinations',
        'app.examination_centre_students',
        'app.extracurricular_types',
        'app.fee_types',
        'app.field_option_values',
        'app.field_options',
        'app.genders',
        'app.guardian_relations',
        'app.health_allergy_types',
        'app.health_conditions',
        'app.health_consultation_types',
        'app.health_immunization_types',
        'app.health_relationships',
        'app.health_test_types',
        'app.identity_types',
        'app.import_mapping',
        'app.infrastructure_conditions',
        'app.infrastructure_custom_field_options',
        'app.infrastructure_custom_field_values',
        'app.infrastructure_custom_fields',
        'app.infrastructure_custom_forms',
        'app.infrastructure_custom_forms_fields',
        'app.infrastructure_custom_forms_filters',
        'app.infrastructure_levels',
        'app.infrastructure_ownerships',
        'app.infrastructure_types',
        'app.institution_activities',
        'app.institution_attachments',
        'app.institution_bank_accounts',
        'app.institution_class_grades',
        'app.institution_class_students',
        'app.institution_class_subjects',
        'app.institution_classes',
        'app.institution_custom_field_options',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.institution_custom_forms',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.institution_custom_table_cells',
        'app.institution_custom_table_columns',
        'app.institution_custom_table_rows',
        'app.institution_fee_types',
        'app.institution_fees',
        'app.institution_genders',
        'app.institution_grades',
        'app.institution_infrastructures',
        'app.institution_localities',
        'app.institution_network_connectivities',
        'app.institution_ownerships',
        'app.institution_positions',
        'app.institution_providers',
        'app.institution_quality_rubric_answers',
        'app.institution_quality_rubrics',
        'app.institution_quality_visits',
        'app.institution_repeater_survey_answers',
        'app.institution_repeater_survey_table_cells',
        'app.institution_repeater_surveys',
        'app.institution_rooms',
        'app.institution_sectors',
        'app.institution_shifts',
        'app.institution_staff',
        'app.institution_staff_absences',
        'app.institution_staff_assignments',
        'app.institution_staff_leave',
        'app.institution_staff_position_profiles',
        'app.institution_statuses',
        'app.institution_student_absences',
        'app.institution_student_admission',
        'app.institution_student_withdraw',
        'app.institution_student_survey_answers',
        'app.institution_student_survey_table_cells',
        'app.institution_student_surveys',
        'app.institution_students',
        'app.institution_subject_staff',
        'app.institution_subject_students',
        'app.institution_subjects',
        'app.institution_survey_answers',
        'app.institution_survey_table_cells',
        'app.institution_surveys',
        'app.institution_types',
        'app.institutions',
        'app.labels',
        'app.languages',
        'app.license_types',
        'app.nationalities',
        'app.notices',
        'app.qualification_institutions',
        'app.qualification_levels',
        'app.qualification_specialisation_subjects',
        'app.qualification_specialisations',
        'app.quality_visit_types',
        'app.report_progress',
        'app.room_custom_field_values',
        'app.room_statuses',
        'app.room_types',
        'app.rubric_criteria_options',
        'app.rubric_criterias',
        'app.rubric_sections',
        'app.rubric_status_periods',
        'app.rubric_status_programmes',
        'app.rubric_status_roles',
        'app.rubric_statuses',
        'app.rubric_template_options',
        'app.rubric_templates',
        'app.salary_addition_types',
        'app.salary_deduction_types',
        'app.security_functions',
        'app.security_group_areas',
        'app.security_group_institutions',
        'app.security_group_users',
        'app.security_groups',
        'app.security_rest_sessions',
        'app.security_role_functions',
        'app.security_roles',
        'app.security_users',
        'app.shift_options',
        'app.special_need_difficulties',
        'app.special_need_types',
        'app.staff_absence_reasons',
        'app.staff_appraisals',
        'app.staff_appraisal_types',
        'app.staff_appraisals_competencies',
        'app.staff_behaviour_categories',
        'app.staff_behaviours',
        'app.staff_change_types',
        'app.staff_custom_field_options',
        'app.staff_custom_field_values',
        'app.staff_custom_fields',
        'app.staff_custom_forms',
        'app.staff_custom_forms_fields',
        'app.staff_custom_table_cells',
        'app.staff_custom_table_columns',
        'app.staff_custom_table_rows',
        'app.staff_employments',
        'app.staff_extracurriculars',
        'app.staff_leave_types',
        'app.staff_licenses',
        'app.staff_memberships',
        'app.staff_position_grades',
        'app.staff_position_titles',
        'app.staff_qualifications',
        'app.staff_salaries',
        'app.staff_salary_additions',
        'app.staff_salary_deductions',
        'app.staff_statuses',
        'app.staff_training_categories',
        'app.staff_training_needs',
        'app.staff_training_self_studies',
        'app.staff_training_self_study_attachments',
        'app.staff_training_self_study_results',
        'app.staff_trainings',
        'app.staff_types',
        'app.student_absence_reasons',
        'app.student_behaviour_categories',
        'app.student_behaviours',
        'app.student_custom_field_options',
        'app.student_custom_field_values',
        'app.student_custom_fields',
        'app.student_custom_forms',
        'app.student_custom_forms_fields',
        'app.student_custom_table_cells',
        'app.student_custom_table_columns',
        'app.student_custom_table_rows',
        'app.student_withdraw_reasons',
        'app.student_extracurriculars',
        'app.student_fees',
        'app.student_guardians',
        'app.student_statuses',
        'app.student_transfer_reasons',
        'app.survey_forms',
        'app.survey_forms_questions',
        'app.survey_question_choices',
        'app.survey_questions',
        'app.survey_responses',
        'app.survey_rules',
        'app.survey_status_periods',
        'app.survey_statuses',
        'app.survey_table_columns',
        'app.survey_table_rows',
        'app.system_processes',
        'app.training_achievement_types',
        'app.training_course_types',
        'app.training_courses',
        'app.training_courses_prerequisites',
        'app.training_courses_providers',
        'app.training_courses_result_types',
        'app.training_courses_specialisations',
        'app.training_courses_target_populations',
        'app.training_field_of_studies',
        'app.training_levels',
        'app.training_mode_deliveries',
        'app.training_need_categories',
        'app.training_priorities',
        'app.training_providers',
        'app.training_requirements',
        'app.training_result_types',
        'app.training_session_results',
        'app.training_session_trainee_results',
        'app.training_session_trainers',
        'app.training_sessions',
        'app.training_sessions_trainees',
        'app.training_specialisations',
        'app.translations',
        'app.user_activities',
        'app.user_attachments',
        'app.user_awards',
        'app.user_bank_accounts',
        'app.user_comments',
        'app.user_contacts',
        'app.user_health_allergies',
        'app.user_health_consultations',
        'app.user_health_families',
        'app.user_health_histories',
        'app.user_health_immunizations',
        'app.user_health_medications',
        'app.user_health_tests',
        'app.user_healths',
        'app.user_identities',
        'app.user_languages',
        'app.user_nationalities',
        'app.user_special_needs',
        'app.workflow_actions',
        'app.workflow_comments',
        'app.workflow_models',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.workflow_steps',
        'app.workflow_steps_roles',
        'app.workflow_transitions',
        'app.workflows',
        'app.workflows_filters'
    ];

    private $testingId = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/AcademicPeriods/Periods/');
    }

    public function testDelete() {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->testingId,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertFalse($exists);
    }
}
