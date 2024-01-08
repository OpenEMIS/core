<?php

namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use Cake\Http\Client;
use Cake\Network\Response;

class DirectoriesTable extends ControllerActionTable
{
    // public $InstitutionStudent;

    // these constants are being used in AdvancedPositionSearchBehavior as well
    // remember to check AdvancedPositionSearchBehavior if these constants are being modified
    const ALL = 0;
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    const STUDENTNOTINSCHOOL = 5;
    const STAFFNOTINSCHOOL = 6;

    private $dashboardQuery;

    public function initialize(array $config)
    {
        $this->table('security_users');
        $this->entityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserNationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $this->hasMany('ExaminationCentreRoomsExaminationsInvigilators', ['className' => 'examination_centre_rooms_examinations_invigilators', 'foreignKey' => 'invigilator_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', ['className' => 'examination_centre_rooms_examinations_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentresExaminationsInvigilators', ['className' => 'examination_centres_examinations_invigilators', 'foreignKey' => 'invigilator_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentresExaminationsStudents', ['className' => 'examination_centres_examinations_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('HistoricalStaffLeave', ['className' => 'historical_staff_leave', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionAssociationStaff', ['className' => 'institution_association_staff', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionAssociationStudent', ['className' => 'institution_association_student', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionCases', ['className' => 'institution_cases', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionClassStudents', ['className' => 'institution_class_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionClassesSecondaryStaff', ['className' => 'institution_classes_secondary_staff', 'foreignKey' => 'secondary_staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionCompetencyItemComments', ['className' => 'institution_competency_item_comments', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionCompetencyPeriodComments', ['className' => 'institution_competency_period_comments', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionCompetencyResults', ['className' => 'institution_competency_results', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('CounsellingsCounselor', ['className' => 'counsellings', 'foreignKey' => 'counselor_id', 'dependent' => true]);
        $this->hasMany('CounsellingsRequester', ['className' => 'counsellings', 'foreignKey' => 'requester_id', 'dependent' => true]);
        $this->hasMany('CounsellingsStudent', ['className' => 'counsellings', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionCurricularStaff', ['className' => 'institution_curricular_staff', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionCurricularStudents', ['className' => 'institution_curricular_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionMealStudents', ['className' => 'institution_meal_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionOutcomeResults', ['className' => 'institution_outcome_results', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionOutcomeSubjectComments', ['className' => 'institution_outcome_subject_comments', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionPositions', ['className' => 'institution_positions', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionQualityRubrics', ['className' => 'institution_quality_rubrics', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionQualityVisits', ['className' => 'institution_quality_visits', 'foreignKey' => 'staff_id', 'dependent' => true]);
//        $this->hasMany('InstitutionStaff', ['className' => 'institution_staff', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAppraisalsAssignee', ['className' => 'institution_staff_appraisals', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAppraisals', ['className' => 'institution_staff_appraisals', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAttendanceActivities', ['className' => 'institution_staff_attendance_activities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffAttendances', ['className' => 'institution_staff_attendances', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffDuties', ['className' => 'institution_staff_duties', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffLeave', ['className' => 'institution_staff_leave', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffPositionProfiles', ['className' => 'institution_staff_position_profiles', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffReleasesAssignee', ['className' => 'institution_staff_releases', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffReleases', ['className' => 'institution_staff_releases', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffShifts', ['className' => 'institution_staff_shifts', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffTransfersAssignee', ['className' => 'institution_staff_transfers', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStaffTransfers', ['className' => 'institution_staff_transfers', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAbsenceDays', ['className' => 'institution_student_absence_days', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAbsenceDetails', ['className' => 'institution_student_absence_details', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAbsences', ['className' => 'institution_student_absences', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAdmissionAssignee', ['className' => 'institution_student_admission', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentAdmission', ['className' => 'institution_student_admission', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentRisks', ['className' => 'institution_student_risks', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentSurveys', ['className' => 'institution_student_surveys', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentTransfersAssignee', ['className' => 'institution_student_transfers', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentTransfers', ['className' => 'institution_student_transfers', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitRequestsAssignee', ['className' => 'institution_student_visit_requests', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitRequestsEvaluator', ['className' => 'institution_student_visit_requests', 'foreignKey' => 'evaluator_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitRequestsStudent', ['className' => 'institution_student_visit_requests', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitsEvaluator', ['className' => 'institution_student_visits', 'foreignKey' => 'evaluator_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentVisitsStudent', ['className' => 'institution_student_visits', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentWithdrawAssignee', ['className' => 'institution_student_withdraw', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentWithdraw', ['className' => 'institution_student_withdraw', 'foreignKey' => 'student_id', 'dependent' => true]);
//        $this->hasMany('InstitutionStudents', ['className' => 'institution_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentsReportCardsCommentsStaff', ['className' => 'institution_students_report_cards_comments', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentsReportCardsCommentsStudent', ['className' => 'institution_students_report_cards_comments', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentsTmp', ['className' => 'institution_students_tmp', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionSubjectStaff', ['className' => 'institution_subject_staff', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'institution_subject_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionTripPassengers', ['className' => 'institution_trip_passengers', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionVisitRequestsAssignee', ['className' => 'institution_visit_requests', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('MoodleApiCreatedUsers', ['className' => 'moodle_api_created_users', 'foreignKey' => 'core_user_id', 'dependent' => true]);
        $this->hasMany('ReportCardEmailProcesses', ['className' => 'report_card_email_processes', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ReportCardProcesses', ['className' => 'report_card_processes', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplicationAttachments', ['className' => 'scholarship_application_attachments', 'foreignKey' => 'applicant_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplicationInstitutionChoices', ['className' => 'scholarship_application_institution_choices', 'foreignKey' => 'applicant_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplications', ['className' => 'scholarship_applications', 'foreignKey' => 'applicant_id', 'dependent' => true]);
        $this->hasMany('ScholarshipApplicationsAssignee', ['className' => 'scholarship_applications', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('ScholarshipFinancialAssistancesModified', ['className' => 'scholarship_financial_assistances', 'foreignKey' => 'modified_user_id', 'dependent' => true]);
        $this->hasMany('ScholarshipFinancialAssistancesCreated', ['className' => 'scholarship_financial_assistances', 'foreignKey' => 'created_user_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientAcademicStandings', ['className' => 'scholarship_recipient_academic_standings', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientActivities', ['className' => 'scholarship_recipient_activities', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientCollections', ['className' => 'scholarship_recipient_collections', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientDisbursements', ['className' => 'scholarship_recipient_disbursements', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientPaymentStructureEstimates', ['className' => 'scholarship_recipient_payment_structure_estimates', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipientPaymentStructures', ['className' => 'scholarship_recipient_payment_structures', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('ScholarshipRecipients', ['className' => 'scholarship_recipients', 'foreignKey' => 'recipient_id', 'dependent' => true]);
        $this->hasMany('SecurityGroupUsers', ['className' => 'security_group_users', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('SecurityUserPasswordRequests', ['className' => 'security_user_password_requests', 'foreignKey' => 'user_id', 'dependent' => true]);
        $this->hasMany('StaffCustomFieldValues', ['className' => 'staff_custom_field_values', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffCustomTableCells', ['className' => 'staff_custom_table_cells', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffEmploymentStatuses', ['className' => 'staff_employment_statuses', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffExtracurriculars', ['className' => 'staff_extracurriculars', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffMemberships', ['className' => 'staff_memberships', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffPayslips', ['className' => 'staff_payslips', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffQualifications', ['className' => 'staff_qualifications', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffReportCardEmailProcesses', ['className' => 'staff_report_card_email_processes', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffReportCardProcesses', ['className' => 'staff_report_card_processes', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffReportCards', ['className' => 'staff_report_cards', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffSalaries', ['className' => 'staff_salaries', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingApplicationsAssignee', ['className' => 'staff_training_applications', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingApplications', ['className' => 'staff_training_applications', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingNeedsAssignee', ['className' => 'staff_training_needs', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingNeeds', ['className' => 'staff_training_needs', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffTrainingSelfStudies', ['className' => 'staff_training_self_studies', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('StaffTrainings', ['className' => 'staff_trainings', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StudentCustomFieldValues', ['className' => 'student_custom_field_values', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentCustomTableCells', ['className' => 'student_custom_table_cells', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentExtracurriculars', ['className' => 'student_extracurriculars', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('StudentFees', ['className' => 'student_fees', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentGuardiansGuardian', ['className' => 'student_guardians', 'foreignKey' => 'guardian_id', 'dependent' => true]);
        $this->hasMany('StudentGuardians', ['className' => 'student_guardians', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentReportCardEmailProcesses', ['className' => 'student_report_card_email_processes', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentReportCardProcesses', ['className' => 'student_report_card_processes', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentReportCards', ['className' => 'student_report_cards', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StudentStatusUpdates', ['className' => 'student_status_updates', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('TrainingCourses', ['className' => 'training_courses', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionResults', ['className' => 'training_session_results', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionTraineeResults', ['className' => 'training_session_trainee_results', 'foreignKey' => 'trainee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionTrainers', ['className' => 'training_session_trainers', 'foreignKey' => 'trainer_id', 'dependent' => true]);
        $this->hasMany('TrainingSessions', ['className' => 'training_sessions', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('TrainingSessionsTrainees', ['className' => 'training_sessions_trainees', 'foreignKey' => 'trainee_id', 'dependent' => true]);
        $this->hasMany('UserActivities', ['className' => 'user_activities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserAttachments', ['className' => 'user_attachments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserAwards', ['className' => 'user_awards', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserBankAccounts', ['className' => 'user_bank_accounts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserBodyMasses', ['className' => 'user_body_masses', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserComments', ['className' => 'user_comments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserContacts', ['className' => 'user_contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserDemographics', ['className' => 'user_demographics', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserEmployments', ['className' => 'user_employments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthAllergies', ['className' => 'user_health_allergies', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthConsultations', ['className' => 'user_health_consultations', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthFamilies', ['className' => 'user_health_families', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthHistories', ['className' => 'user_health_histories', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthImmunizations', ['className' => 'user_health_immunizations', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthMedications', ['className' => 'user_health_medications', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealthTests', ['className' => 'user_health_tests', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserHealths', ['className' => 'user_healths', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserIdentities', ['className' => 'user_identities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserInsurances', ['className' => 'user_insurances', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserLanguages', ['className' => 'user_languages', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserNationalities', ['className' => 'user_nationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
//        $this->hasMany('UserSpecialNeedsAssessments', ['className' => 'user_special_needs_assessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsDevices', ['className' => 'user_special_needs_devices', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsPlans', ['className' => 'user_special_needs_plans', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsReferrals', ['className' => 'user_special_needs_referrals', 'foreignKey' => 'referrer_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsReferrals', ['className' => 'user_special_needs_referrals', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('UserSpecialNeedsServices', ['className' => 'user_special_needs_services', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('StudentBehaviours', ['className' => 'student_behaviours', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'staff_behaviours', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $this->hasMany('StaffLicensesAssignee', ['className' => 'staff_licenses', 'foreignKey' => 'assignee_id', 'dependent' => true]);
        $this->hasMany('StaffLicenses', ['className' => 'staff_licenses', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('InstitutionStudentsReportCards', ['className' => 'institution_students_report_cards', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionTextbooks', ['className' => 'institution_textbooks', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'examination_student_subject_results', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ExaminationCentresExaminationsSubjectsStudents', ['className' => 'examination_centres_examinations_subjects_students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('ExaminationStudentSubjects', ['className' => 'examination_student_subjects', 'foreignKey' => 'student_id', 'dependent' => true]);
        $this->hasMany('InstitutionAssets', ['className' => 'institution_assets', 'foreignKey' => 'user_id', 'dependent' => true]);

        $this->addBehavior('User.User');
        $this->addBehavior('Security.UserCascade'); // for cascade delete on user related tables
        $this->addBehavior('User.AdvancedIdentitySearch');
        $this->addBehavior('User.AdvancedContactNumberSearch');
        $this->addBehavior('User.AdvancedPositionSearch');
        $this->addBehavior('User.AdvancedSpecificNameTypeSearch');
        $this->addBehavior('User.MoodleCreateUser');
        $this->addBehavior('Directory.Merge');

        //specify order of advanced search fields
        $advancedSearchFieldOrder = [
            'user_type', 'first_name', 'middle_name', 'third_name', 'last_name',
            'openemis_no', 'gender_id', 'contact_number', 'birthplace_area_id', 'address_area_id', 'position',
            'identity_type', 'identity_number'
        ];
        $this->addBehavior('AdvanceSearch', [
            'include' => [
                'openemis_no'
            ],
            'order' => $advancedSearchFieldOrder,
            'showOnLoad' => 1,
            'customFields' => ['user_type']
        ]);

        $this->addBehavior('HighChart', [
            'user_gender' => [
                '_function' => 'getNumberOfUsersByGender'
            ]
        ]);
        $this->addBehavior('Configuration.Pull');
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportUsers']);
        $this->addBehavior('ControllerAction.Image');

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Directory.Directories.id']);
        $this->toggle('search', false);
        $this->setDeleteStrategy('restrict'); //POCOR-7083
    }

    public function beforeFind(Event $event, Query $query, ArrayObject $options, $primary)
    {
        if ($primary) {
            $schema = $this->schema();
            $fields = $schema->columns();
            foreach ($fields as $key => $field) {
                if ($schema->column($field)['type'] == 'binary') {
                    unset($fields[$key]);
                }
            }
            return $query->select($fields);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['AdvanceSearch.getCustomFilter'] = 'getCustomFilter';
        $events['AdvanceSearch.onModifyConditions'] = 'onModifyConditions';
        $events['Model.AreaAdministrative.afterDelete'] = 'areaAdminstrativeAfterDelete';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('postal_code')
            ->add('postal_code', 'ruleCustomPostalCode', [
                'rule' => ['validateCustomPattern', 'postal_code'],
                'provider' => 'table',
                'last' => true
            ])
            ->notEmpty('nationality');
        $BaseUsers = TableRegistry::get('User.Users');
        return $BaseUsers->setUserValidation($validator, $this);
    }

    public function validationNotEmptyNationality(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator->add('nationality');
        return $validator;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // POCOR-4035 Check when the submit is not save then will not add the validation.
        $submit = isset($data['submit']) ? $data['submit'] : 'save';

        if ($submit == 'save') {
            $nationalityValidation = 'AddByAssociation';
            $identityValidation = 'AddByAssociation';
        } else {
            $nationalityValidation = false;
            $identityValidation = false;
        }

        $options['associated']['Nationalities'] = [
            'validate' => $nationalityValidation
        ];
        $options['associated']['Identities'] = [
            'validate' => $identityValidation
        ];
        // end POCOR-4035
    }

    public function onModifyConditions(Event $events, $key, $value)
    {
        if ($key == 'user_type') {
            $conditions = [];
            switch ($value) {
                case self::STUDENT:
                    $conditions = [$this->aliasField('is_student') => 1];
                    break;

                case self::STAFF:
                    $conditions = [$this->aliasField('is_staff') => 1];
                    break;

                case self::GUARDIAN:
                    $conditions = [$this->aliasField('is_guardian') => 1];
                    break;

                case self::OTHER:
                    $conditions = [
                        $this->aliasField('is_student') => 0,
                        $this->aliasField('is_staff') => 0,
                        $this->aliasField('is_guardian') => 0
                    ];
                    break;
            }
            return $conditions;
        }
    }


    public function areaAdminstrativeAfterDelete(Event $event, $areaAdministrative)
    {
        $subqueryOne = $this->AddressAreas
            ->find()
            ->select(1)
            ->where(function ($exp, $q) {
                return $exp->equalFields($this->AddressAreas->aliasField('id'), $this->aliasField('address_area_id'));
            });

        $query = $this->find()
            ->select('id')
            ->where(function ($exp, $q) use ($subqueryOne) {
                return $exp->notExists($subqueryOne);
            });


        foreach ($query as $row) {
            $this->updateAll(
                ['address_area_id' => null],
                ['id' => $row->id]
            );
        }

        $subqueryTwo = $this->BirthplaceAreas
            ->find()
            ->select(1)
            ->where(function ($exp, $q) {
                return $exp->equalFields($this->BirthplaceAreas->aliasField('id'), $this->aliasField('birthplace_area_id'));
            });


        $query = $this->find()
            ->select('id')
            ->where(function ($exp, $q) use ($subqueryTwo) {
                return $exp->notExists($subqueryTwo);
            });


        foreach ($query as $row) {
            $this->updateAll(
                ['birthplace_area_id' => null],
                ['id' => $row->id]
            );
        }

    }

    public function getCustomFilter(Event $event)
    {
        $filters['user_type'] = [
            'label' => __('User Type'),
            'options' => [
                self::STAFF => __('Staff'),
                self::STUDENT => __('Students'),
                self::GUARDIAN => __('Guardians'),
                self::OTHER => __('Others')
            ]
        ];
        return $filters;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $options)
    {
        if (!$this->isAdvancedSearchEnabled()) {
            $event->stopPropagation();
            return [];
        } else {
            $this->behaviors()->get('AdvanceSearch')->config([
                'showOnLoad' => 0,
            ]);
        }

        $conditions = [];

        $notSuperAdminCondition = [
            $this->aliasField('super_admin') => 0
        ];
        $conditions = array_merge($conditions, $notSuperAdminCondition);

        // POCOR-2547 sort list of staff and student by name
        $orders = [];

        if (!isset($this->request->query['sort'])) {
            $orders = [
                $this->aliasField('first_name'),
                $this->aliasField('last_name')
            ];
        }

        $query->where($conditions)
            ->order($orders);
        $options['auto_search'] = true;
        //POCOR-6248 starts
        $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');
        if ($userType == self::STAFF || $userType == self::STUDENT) {
            $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
            $UserIdentities = TableRegistry::get('User.Identities');
            $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
            $ConfigItem = $ConfigItemTable
                ->find()
                ->where([
                    $ConfigItemTable->aliasField('code') => 'directory_identity_number',
                    $ConfigItemTable->aliasField('value') => 1
                ])
                ->first();
            if (!empty($ConfigItem)) {
                //value_selection
                //get data from Identity Type table
                $typesIdentity = $this->getIdentityTypeData($ConfigItem->value_selection);
                if (!empty($typesIdentity)) {
                    $query
                        ->select([
                            'identity_type' => $IdentityTypes->aliasField('name'),
                            // for POCOR-6561 changed $typesIdentity->identity_type to $typesIdentity->id below
                            $typesIdentity->id => $UserIdentities->aliasField('number')
                        ])
                        ->leftJoin(
                            [$UserIdentities->alias() => $UserIdentities->table()],
                            [
                                $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('id'),
                                $UserIdentities->aliasField('identity_type_id = ') . $typesIdentity->id
                            ]
                        )
                        ->leftJoin(
                            [$IdentityTypes->alias() => $IdentityTypes->table()],
                            [
                                $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id'),
                                $IdentityTypes->aliasField('id = ') . $typesIdentity->id
                            ]
                        );
                }
            }
        }//POCOR-6248 ends
        $this->dashboardQuery = clone $query;
    }

    public function findStudentsInSchool(Query $query, array $options)
    {
        $institutionIds = (array_key_exists('institutionIds', $options)) ? $options['institutionIds'] : [];
        if (!empty($institutionIds)) {
            $query
                ->join([
                    [
                        'type' => 'INNER',
                        'table' => 'institution_students',
                        'alias' => 'InstitutionStudents',
                        'conditions' => [
                            'InstitutionStudents.institution_id' . ' IN (' . $institutionIds . ')',
                            'InstitutionStudents.student_id = ' . $this->aliasField('id')
                        ]
                    ]
                ])
                ->group('InstitutionStudents.student_id');
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStudentsNotInSchool(Query $query, array $options)
    {
        $InstitutionStudentTable = TableRegistry::get('Institution.Students');
        $allInstitutionStudents = $InstitutionStudentTable->find()
            ->select([
                $InstitutionStudentTable->aliasField('student_id')
            ])
            ->where([
                $InstitutionStudentTable->aliasField('student_id') . ' = ' . $this->aliasField('id')
            ])
            ->bufferResults(false);
        $query->where(['NOT EXISTS (' . $allInstitutionStudents->sql() . ')', $this->aliasField('is_student') => 1]);
        return $query;
    }

    public function findStaffInSchool(Query $query, array $options)
    {
        $institutionIds = (array_key_exists('institutionIds', $options)) ? $options['institutionIds'] : [];
        if (!empty($institutionIds)) {
            $query->join([
                [
                    'type' => 'INNER',
                    'table' => 'institution_staff',
                    'alias' => 'InstitutionStaff',
                    'conditions' => [
                        'InstitutionStaff.institution_id' . ' IN (' . $institutionIds . ')',
                        'InstitutionStaff.staff_id = ' . $this->aliasField('id')
                    ]
                ]
            ]);
        } else {
            // return nothing if $institutionIds is empty
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findStaffNotInSchool(Query $query, array $options)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $allInstitutionStaff = $InstitutionStaffTable->find()
            ->select([
                $InstitutionStaffTable->aliasField('staff_id')
            ])
            ->where([
                $InstitutionStaffTable->aliasField('staff_id') . ' = ' . $this->aliasField('id')
            ])
            ->bufferResults(false);
        $query->where(['NOT EXISTS (' . $allInstitutionStaff->sql() . ')', $this->aliasField('is_staff') => 1]);
        return $query;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'add') {
            if ($this->controller->name != 'Students') {
                $this->field('user_type', ['type' => 'select', 'after' => 'photo_content']);
            } else {
                $this->request->query['user_type'] = self::GUARDIAN;
            }
            $userType = isset($this->request->data[$this->alias()]['user_type']) ? $this->request->data[$this->alias()]['user_type'] : $this->request->query('user_type');
            $this->field('openemis_no', ['user_type' => $userType]);
            switch ($userType) {
                case self::STUDENT:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts']]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Student.Students',
                        'behavior' => 'Student',
                        'fieldKey' => 'student_custom_field_id',
                        'tableColumnKey' => 'student_custom_table_column_id',
                        'tableRowKey' => 'student_custom_table_row_id',
                        'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
                        'formKey' => 'student_custom_form_id',
                        'filterKey' => 'student_custom_filter_id',
                        'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
                        'recordKey' => 'student_id',
                        'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::STAFF:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' => ['Identities', 'Nationalities', 'Contacts']]);
                    $this->addBehavior('CustomField.Record', [
                        'model' => 'Staff.Staff',
                        'behavior' => 'Staff',
                        'fieldKey' => 'staff_custom_field_id',
                        'tableColumnKey' => 'staff_custom_table_column_id',
                        'tableRowKey' => 'staff_custom_table_row_id',
                        'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
                        'formKey' => 'staff_custom_form_id',
                        'filterKey' => 'staff_custom_filter_id',
                        'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
                        'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
                        'recordKey' => 'staff_id',
                        'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
                        'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                    ]);
                    break;
                case self::GUARDIAN:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Guardian', 'roleFields' => ['Identities', 'Nationalities']]);
                    break;
                case self::OTHER:
                    $this->addBehavior('User.Mandatory', ['userRole' => 'Other', 'roleFields' => ['Identities', 'Nationalities']]);
                    break;
            }
            $this->field('nationality_id', ['visible' => false]);
            $this->field('identity_type_id', ['visible' => false]);
        }
        if ($this->action == 'edit') {
            $this->hideOtherInformationSection($this->controller->name, 'edit');
            $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');
            $this->field('openemis_no', ['user_type' => $userType]);
            $this->addCustomUserBehavior($userType);
        }
        if ($this->action == 'view') {
            $encodedParam = $this->request->params['pass'][1];
            $securityUserId = $this->ControllerAction->paramsDecode($encodedParam)['id'];
            $userInfo = TableRegistry::get('Security.Users')->get($securityUserId);
            if ($userInfo->is_student) {
                $userType = self::STUDENT;
                $this->addCustomUserBehavior($userType);
            } elseif ($userInfo->is_staff) {
                $userType = self::STAFF;
                $this->addCustomUserBehavior($userType);
            } elseif ($userInfo->is_guardian) {
                $userType = self::GUARDIAN;
                $this->addCustomUserBehavior($userType);
            }

        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Directory', 'Overview', 'General');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188

    }

    // POCOR-5684
    public function onGetIdentityNumber(Event $event, Entity $entity)
    {

        // Case 1: if user has only one identity, show the same,
        // Case 2: if user has more than one identity and also has more than one nationality, and no one is linked to any nationality, then, check, if any nationality has default identity, then show that identity else show the first identity.
        // Case 3: if user has more than one identity (no one is linked to nationality), show the first

        $users_ids = TableRegistry::get('user_identities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();

        $users_ids = TableRegistry::get('user_identities');
        $user_id_data = $users_ids->find()
            ->select(['number'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            return $entity->identity_number = $user_id_data->number;
        } else {
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::get('nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('user_identities');
                $user_id_data_nat = $users_ids->find()
                    ->select(['number'])
                    ->where([
                        $users_ids->aliasField('security_user_id') => $entity->id,
                        $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                    ])
                    ->first();
                if ($user_id_data_nat != null) {
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }

            if (count($nationality_based_ids) > 0) {
                // Case 2 - returning value
                return $entity->identity_number = $nationality_based_ids[0]['number'];
            } else {
                // Case 3 - returning value, return again from Case 1
                return $entity->identity_number = $user_id_data->number;
            }
        }
    }

    // POCOR-5684
    public function onGetIdentityTypeID(Event $event, Entity $entity)
    {
        $users_ids = TableRegistry::get('user_identities');
        $user_identities = $users_ids->find()
            ->select(['number', 'nationality_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->all();

        $users_ids = TableRegistry::get('user_identities');
        $user_id_data = $users_ids->find()
            ->select(['number', 'identity_type_id'])
            ->where([
                $users_ids->aliasField('security_user_id') => $entity->id,
            ])
            ->first();

        if (count($user_identities) == 1) {
            // Case 1
            $users_id_type = TableRegistry::get('identity_types');
            $user_id_name = $users_id_type->find()
                ->select(['name'])
                ->where([
                    $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
                ])
                ->first();
            return $entity->identity_type_id = $user_id_name->name;
        } else {
            // Case 2 or 3

            // Get all nationalities, which has any default identity
            $nationalities = TableRegistry::get('nationalities');
            $nationalities_ids = $nationalities->find('all',
                [
                    'fields' => [
                        'id',
                        'name',
                        'identity_type_id'
                    ],
                    'conditions' => [
                        'identity_type_id !=' => 'NULL'
                    ]
                ]
            )->all();

            $nat_ids = [];
            foreach ($nationalities_ids as $item) {
                array_push($nat_ids, ['nationality_id' => $item->id, 'identity_type_id' => $item->identity_type_id]);
            }

            $nationality_based_ids = [];
            foreach ($nat_ids as $nat_id) {
                $users_ids = TableRegistry::get('user_identities');
                $user_id_data_nat = $users_ids->find()
                    ->select(['number', 'identity_type_id'])
                    ->where([
                        $users_ids->aliasField('security_user_id') => $entity->id,
                        $users_ids->aliasField('identity_type_id') => $nat_id['identity_type_id']
                    ])
                    ->first();
                if ($user_id_data_nat != null) {
                    array_push($nationality_based_ids, $user_id_data_nat);
                }
            }
            if (count($nationality_based_ids) > 0) {
                // Case 2 - returning value
                $users_id_type = TableRegistry::get('identity_types');
                $user_id_name = $users_id_type->find()
                    ->select(['name'])
                    ->where([
                        $users_id_type->aliasField('id') => $nationality_based_ids[0]['identity_type_id'],
                    ])
                    ->first();
                return $entity->identity_type_id = $user_id_name->name;
            } else {
                // Case 3 - returning value, return again from Case 1
                $users_id_type = TableRegistry::get('identity_types');
                $user_id_name = $users_id_type->find()
                    ->select(['name'])
                    ->where([
                        $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
                    ])
                    ->first();
                return $entity->identity_type_id = $user_id_name->name;
            }
        }
    }

    // POCOR-5684
    // public function onGetIdentityNumber(Event $event, Entity $entity)
    // {
    //     // Get user identity number
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['number'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();
    //     return $entity->identity_number = $user_id_data->number;
    // }

    // // POCOR-5684
    // public function onGetIdentityTypeID(Event $event, Entity $entity)
    // {
    //     // Get User Identity Type id
    //     $users_ids = TableRegistry::get('user_identities');
    //     $user_id_data = $users_ids->find()
    //     ->select(['identity_type_id'])
    //     ->where([
    //         $users_ids->aliasField('security_user_id') => $entity->id,
    //     ])
    //     ->first();

    //     // Get Identity Type Name
    //     $users_id_type = TableRegistry::get('identity_types');
    //     $user_id_name = $users_id_type->find()
    //     ->select(['name'])
    //     ->where([
    //         $users_id_type->aliasField('id') => $user_id_data->identity_type_id,
    //     ])
    //     ->first();
    //     return $entity->identity_type_id = $user_id_name->name;
    // }

    private function addCustomUserBehavior($userType)
    {
        switch ($userType) {
            case self::STUDENT:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' => ['Identities', 'Nationalities', 'Contacts']]);
                $this->addBehavior('CustomField.Record', [
                    'model' => 'Student.Students',
                    'behavior' => 'Student',
                    'fieldKey' => 'student_custom_field_id',
                    'tableColumnKey' => 'student_custom_table_column_id',
                    'tableRowKey' => 'student_custom_table_row_id',
                    'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
                    'formKey' => 'student_custom_form_id',
                    'filterKey' => 'student_custom_filter_id',
                    'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
                    'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
                    'recordKey' => 'student_id',
                    'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
                    'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                ]);
                break;
            case self::STAFF:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Staff', 'roleFields' => ['Identities', 'Nationalities', 'Contacts']]);
                $this->addBehavior('CustomField.Record', [
                    'model' => 'Staff.Staff',
                    'behavior' => 'Staff',
                    'fieldKey' => 'staff_custom_field_id',
                    'tableColumnKey' => 'staff_custom_table_column_id',
                    'tableRowKey' => 'staff_custom_table_row_id',
                    'fieldClass' => ['className' => 'StaffCustomField.StaffCustomFields'],
                    'formKey' => 'staff_custom_form_id',
                    'filterKey' => 'staff_custom_filter_id',
                    'formFieldClass' => ['className' => 'StaffCustomField.StaffCustomFormsFields'],
                    'formFilterClass' => ['className' => 'StaffCustomField.StaffCustomFormsFilters'],
                    'recordKey' => 'staff_id',
                    'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
                    'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                ]);
                break;
            case self::GUARDIAN:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Guardian', 'roleFields' => ['Identities', 'Nationalities']]);
                break;
            case self::OTHER:
                $this->addBehavior('User.Mandatory', ['userRole' => 'Other', 'roleFields' => ['Identities', 'Nationalities']]);
                break;
        }

        return;
    }

    public function hideOtherInformationSection($controller, $action)
    {
        if (($action == "add") || ($action == "edit")) { //hide "other information" section on add/edit guardian because there wont be any custom field.
            if (($controller == "Students") || ($controller == "Directories")) {
                $this->field('other_information_section', ['visible' => false]);
            }
        }
    }

    public function addBeforeAction(Event $event)
    {
        if (!isset($this->request->data[$this->alias()]['user_type'])) {
            $this->request->data[$this->alias()]['user_type'] = $this->request->query('user_type');
        }
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // need to find out order values because recordbehavior changes it
        $allOrderValues = [];
        foreach ($this->fields as $key => $value) {
            $allOrderValues[] = (array_key_exists('order', $value) && !empty($value['order'])) ? $value['order'] : 0;
        }
        $highestOrder = max($allOrderValues);

        $userType = $this->request->query('user_type');

        $openemisNo = $this->getUniqueOpenemisId();

        $this->fields['openemis_no']['value'] = $openemisNo;
        $this->fields['openemis_no']['attr']['value'] = $openemisNo;
        // pr($this->request->data[$this->alias()]['username']);
        if (!isset($this->request->data[$this->alias()]['username'])) {
            $this->request->data[$this->alias()]['username'] = $openemisNo;
        } elseif ($this->request->data[$this->alias()]['username'] == $this->request->data[$this->alias()]['openemis_no']) {
            $this->request->data[$this->alias()]['username'] = $openemisNo;
        } elseif (empty($this->request->data[$this->alias()]['username'])) {
            $entity->invalid('username', $openemisNo, true);
            $this->request->data[$this->alias()]['username'] = $openemisNo;
        }
        $this->field('username', ['order' => ++$highestOrder, 'visible' => true]);

        if (!isset($this->request->data[$this->alias()]['password'])) {
            $UsersTable = TableRegistry::get('User.Users');

            // Read the number of length of password from system config
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $this->request->data[$this->alias()]['password'] = $ConfigItems->getAutoGeneratedPassword();
        }
        $this->field('password', ['order' => ++$highestOrder, 'visible' => true, 'attr' => ['autocomplete' => 'off']]);
        $this->field('nationality', ['attr' => ['required' => true]]);//POCOR-5987
        $this->field('identity_number', ['visible' => true]);
        $this->setFieldOrder([
            'information_section', 'photo_content', 'user_type', 'openemis_no', 'first_name', 'middle_name',
            'third_name', 'last_name', 'preferred_name', 'gender_id', 'date_of_birth', 'nationality_id',
            'identity_type_id', 'location_section', 'address', 'postal_code', 'address_area_section', 'address_area_id',
            'birthplace_area_section', 'birthplace_area_id', 'other_information_section', 'contact_type', 'contact_value', 'nationality',
            'identity_type', 'identity_number',
            'username', 'password'
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['advance_search'] = [
            'type' => 'button',
            'attr' => [
                'class' => 'btn btn-default btn-xs',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'title' => __('Advanced Search'),
                'id' => 'search-toggle',
                'escape' => false,
                'ng-click' => 'toggleAdvancedSearch()'
            ],
            'url' => '#',
            'label' => '<i class="fa fa-search-plus"></i>',
        ];
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (isset($buttons['view'])) {
            // history button need to check permission ??
            if ($this->AccessControl->check(['DirectoryHistories', 'index'])) {
                $userId = $entity->id;

                $icon = '<i class="fa fa-history"></i>';
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'DirectoryHistories',
                    'action' => 'index'
                ];

                $buttons['history'] = $buttons['view'];
                $buttons['history']['label'] = $icon . __('History');
                $buttons['history']['url'] = $this->ControllerAction->setQueryString($url, [
                    'security_user_id' => $userId,
                    'user_type' => $this->alias()
                ]);
            }
            // end history button
        }

        return $buttons;
    }

    public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request)
    {
        $options = [
            self::STUDENT => __('Student'),
            self::STAFF => __('Staff'),
            self::GUARDIAN => __('Guardian'),
            self::OTHER => __('Others')
        ];
        $attr['options'] = $options;
        $attr['onChangeReload'] = 'changeUserType';
        if (!$this->request->query('user_type')) {
            $this->request->query['user_type'] = key($options);
        }
        return $attr;
    }

    public function addOnChangeUserType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        unset($this->request->query['user_type']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                if (array_key_exists('user_type', $data[$this->alias()])) {
                    $this->request->query['user_type'] = $data[$this->alias()]['user_type'];
                }
            }

            if (isset($data[$this->alias()]['custom_field_values'])) {
                unset($data[$this->alias()]['custom_field_values']);
            }

            //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
            $options['associated'] = [
                'Identities' => ['validate' => false],
                'Nationalities' => ['validate' => false],
                'SpecialNeeds' => ['validate' => false],
                'Contacts' => ['validate' => false]
            ];
        }
    }

    public function onUpdateFieldPassword(Event $event, array $attr, $action, Request $request)
    {
        // setting the tooltip message
        $tooltipMessagePassword = $this->getMessage('Users.tooltip_message_password');

        $attr['attr']['label']['escape'] = false; //disable the htmlentities (on LabelWidget) so can show html on label.
        $attr['attr']['label']['class'] = 'tooltip-desc'; //css class for label
        $attr['attr']['label']['text'] = __(Inflector::humanize($attr['field'])) . $this->tooltipMessage($tooltipMessagePassword);

        return $attr;
    }

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions)
    {
        $userType = $requestData[$this->alias()]['user_type'];
        $type = [
            'is_student' => '0',
            'is_staff' => '0',
            'is_guardian' => '0'
            // 'is_student' => intval(0),
            // 'is_staff' => intval(0),
            // 'is_guardian' => intval(0)
            // 'is_student' => 0,
            // 'is_staff' => 0,
            // 'is_guardian' => 0
        ];
        switch ($userType) {
            case self::STUDENT:
                $type['is_student'] = 1;
                break;
            case self::STAFF:
                $type['is_staff'] = 1;
                break;
            case self::GUARDIAN:
                $type['is_guardian'] = 1;
                break;
        }
        $directoryEntity = array_merge($requestData[$this->alias()], $type);
        $requestData[$this->alias()] = $directoryEntity;
    }

    public function indexAfterAction(Event $event)
    {
        $this->fields = [];
        $this->controller->set('ngController', 'AdvancedSearchCtrl');

        $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');

        switch ($userType) {
            case self::ALL:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::STUDENT:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                $this->field('student_status', ['order' => 52]);
                break;
            case self::STAFF:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::GUARDIAN:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
            case self::OTHER:
                $this->field('institution', ['order' => 50]);
                $this->field('date_of_birth', ['order' => 51]);
                break;
        }
        $this->fields['date_of_birth']['type'] = 'date';
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index') {
            $userType = $this->Session->read('Directories.advanceSearch.belongsTo.user_type');
            //POCOR-6248 starts
            if ($userType == self::STAFF || $userType == self::STUDENT) {
                $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
                $ConfigItem = $ConfigItemTable
                    ->find()
                    ->where([
                        $ConfigItemTable->aliasField('type') => 'Columns for Directory List Page'
                    ])
                    ->all();
                foreach ($ConfigItem as $item) {
                    if ($item->code == 'directory_photo') {
                        $this->field('photo_name', ['visible' => false]);
                        if ($item->value == 1) {
                            $this->field('photo_content', ['visible' => true]);
                        } else {
                            $this->field('photo_content', ['visible' => false]);
                        }
                    }
                    if ($item->code == 'directory_openEMIS_ID') {
                        if ($item->value == 1) {
                            $this->field('openemis_no', ['visible' => true, 'before' => 'name']);
                        } else {
                            $this->field('openemis_no', ['visible' => false, 'before' => 'name']);
                        }
                    }
                    if ($item->code == 'directory_name') {
                        if ($item->value == 1) {
                            $this->field('name', ['visible' => true, 'before' => 'institution']);
                        } else {
                            $this->field('name', ['visible' => false, 'before' => 'institution']);
                        }
                    }
                    if ($item->code == 'directory_institution') {
                        if ($item->value == 1) {
                            $this->field('institution', ['visible' => true, 'before' => 'date_of_birth']);
                        } else {
                            $this->field('institution', ['visible' => false, 'before' => 'date_of_birth']);
                        }
                    }
                    if ($item->code == 'directory_date_of_birth') {
                        if ($item->value == 1) {
                            $this->field('date_of_birth', ['visible' => true, 'before' => 'student_status']);
                        } else {
                            $this->field('date_of_birth', ['visible' => false, 'before' => 'student_status']);
                        }
                    }
                    if ($item->code == 'directory_identity_number') {
                        if ($item->value == 1) {
                            if (!empty($item->value_selection)) {
                                //get data from Identity Type table
                                $typesIdentity = $this->getIdentityTypeData($item->value_selection);
                                if (isset($typesIdentity)) { //POCOR-6679
                                    $this->field($typesIdentity->identity_type, ['visible' => true, 'after' => 'date_of_birth']);
                                }
                            }
                        } else {
                            $typesIdentity = $this->getIdentityTypeData($item->value_selection); //POCOR-6679
                            $this->field($typesIdentity->identity_type, ['visible' => false, 'after' => 'date_of_birth']);
                        }
                    }
                }
            }
            $this->field('student_status', ['visible' => false]);
            //POCOR-6248 ends

            switch ($userType) {
                case self::ALL:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
                case self::STUDENT:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth', 'student_status']);
                    break;
                case self::STAFF:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
                case self::GUARDIAN:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                case self::OTHER:
                    $this->setFieldOrder(['photo_content', 'openemis_no', 'name', 'institution', 'date_of_birth']);
                    break;
            }
        }
    }

    //POCOR-6248 starts
    public function getIdentityTypeData($value_selection)
    {
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $typesIdentity = $IdentityTypes
            ->find()
            ->select([
                'id' => $IdentityTypes->aliasField('id'),
                'identity_type' => $IdentityTypes->aliasField('name')
            ])
            ->where([
                $IdentityTypes->aliasField('id') => $value_selection
            ])
            ->first();
        return $typesIdentity;
    }//POCOR-6248 ends

    public function onGetStudentStatus(Event $event, Entity $entity)
    {
        return __($entity->student_status_name);
    }

    public function getNumberOfUsersByGender($params = [])
    {
        $query = isset($params['query']) ? $params['query'] : null;
        if (!is_null($query)) {
            $userRecords = clone $query;
        } else {
            $userRecords = $this->find();
        }
        $genderCount = $userRecords
            ->contain(['Genders'])
            ->select([
                'count' => $userRecords->func()->count($this->aliasField('id')),
                'gender' => 'Genders.name'
            ])
            ->group('gender', true)
            ->bufferResults(false);

        // Creating the data set
        $dataSet = [];
        foreach ($genderCount as $value) {
            //Compile the dataset
            if (is_null($value['gender'])) {
                $value['gender'] = 'Not Defined';
            }
            $dataSet[] = [__($value['gender']), $value['count']];
        }
        $params['dataSet'] = $dataSet;
        return $params;
    }

    private function setSessionAfterAction($event, $entity)
    {
        $this->Session->write('Directory.Directories.id', $entity->id);
        $this->Session->write('Directory.Directories.name', $entity->name);

        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();
            $this->Session->write('AccessControl.Institutions.ids', $institutionIds);
        }

        $isStudent = $entity->is_student;
        $isStaff = $entity->is_staff;
        $isGuardian = $entity->is_guardian;
        $isSet = false;
        $this->Session->delete('Directory.Directories.is_student');
        $this->Session->delete('Directory.Directories.is_staff');
        $this->Session->delete('Directory.Directories.is_guardian');
        if ($isStudent) {
            $this->Session->write('Directory.Directories.is_student', true);
            $this->Session->write('Student.Students.id', $entity->id);
            $this->Session->write('Student.Students.name', $entity->name);
            $isSet = true;
            $isSet = true;
        }

        if ($isStaff) {
            $this->Session->write('Directory.Directories.is_staff', true);
            $this->Session->write('Staff.Staff.id', $entity->id);
            $this->Session->write('Staff.Staff.name', $entity->name);
            $isSet = true;
        }

        if ($isGuardian) {
            $this->Session->write('Directory.Directories.is_guardian', true);
            $this->Session->write('Guardian.Guardians.id', $entity->id);
            $this->Session->write('Guardian.Guardians.name', $entity->name);
            $isSet = true;
        }

        return $isSet;
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MainNationalities' => [
                'fields' => [
                    'MainNationalities.id',
                    'MainNationalities.name'
                ]
            ],
            'MainIdentityTypes' => [
                'fields' => [
                    'MainIdentityTypes.id',
                    'MainIdentityTypes.name'
                ]
            ],
            'Genders' => [
                'fields' => [
                    'Genders.id',
                    'Genders.name'
                ]
            ]
        ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $isSet = $this->setSessionAfterAction($event, $entity);

        if ($isSet) {
            $reload = $this->Session->read('Directory.Directories.reload');
            if (!isset($reload)) {
                $urlParams = $this->url('edit');
                $event->stopPropagation();
                return $this->controller->redirect($urlParams);
            }
        }

        $this->setupTabElements($entity);

        if ($entity->is_student) {
            /*$this->fields['gender_id']['type'] = 'readonly';
            $this->fields['gender_id']['attr']['value'] = $entity->has('gender') ? $entity->gender->name : '';
            $this->fields['gender_id']['value'] = $entity->has('gender') ? $entity->gender->id : '';*/
        }

        $this->fields['nationality_id']['type'] = 'readonly';
        if (!empty($entity->main_nationality)) {
            $this->fields['nationality_id']['attr']['value'] = $entity->main_nationality->name;
        }

        $this->fields['identity_type_id']['type'] = 'readonly';
        if (!empty($entity->main_identity_type)) {
            $this->fields['identity_type_id']['attr']['value'] = $entity->main_identity_type->name;
        }

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        //POCOR-6332 commented due to this function some error was occuring
        $isSet = $this->setSessionAfterAction($event, $entity);
        if ($isSet) {
            $reload = $this->Session->read('Directory.Directories.reload');
            if (!isset($reload)) {
                $urlParams = $this->url('view');
                $event->stopPropagation();
                return $this->controller->redirect($urlParams);
            }
        }

        $this->setupTabElements($entity);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-8059::start
        if ($entity->isNew()) {
            $entity->preferred_language = 'en';
        }else{
            if(!empty($entity->date_of_death)){
                $dob = $entity->date_of_birth->i18nFormat('yyyy-MM-dd');
                $dod = $entity->date_of_death->i18nFormat('yyyy-MM-dd');
                if($dob > $dod){
                    $entity->dod_range = "greater";
                }

                if(isset($entity->dod_range)){
                    $event->stopPropagation();
                    $this->Alert->warning('general.dodmsg' , ['reset' => true]);
                    $url = $this->url('edit');
                    return $this->controller->redirect($url);
                }
            }
        }
        //POCOR-8059 :: end
        if (!$entity->isNew() && $entity->dirty('gender_id') && !$entity->is_student) {
            $entity->errors('gender_id', __('Gender is not editable in Directories'));
            return false;
        }
    }

    private function setupTabElements($entity)
    {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

        $options = [
            // 'userRole' => 'Student',
            // 'action' => $this->action,
            // 'id' => $id,
            // 'userId' => $entity->id
        ];

        $tabElements = $this->controller->getUserTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function onGetInstitution(Event $event, Entity $entity)
    {
        $userId = $entity->id;
        $isStudent = $entity->is_student;
        $isStaff = $entity->is_staff;
        $isGuardian = $entity->is_guardian;

        $studentInstitutions = [];
        if ($isStudent) {
            $InstitutionStudentTable = TableRegistry::get('Institution.Students');
            /**POCOR-6902 starts - modified query to fetch correct institution name*/
            $studentInstitutions = $InstitutionStudentTable->find()
                ->matching('StudentStatuses', function ($q) {
                    return $q->where(['StudentStatuses.code' => 'CURRENT']);
                })
                ->matching('Institutions')
                ->where([
                    $InstitutionStudentTable->aliasField('student_id') => $userId
                ])
                ->select(['id' => $InstitutionStudentTable->aliasField('institution_id'), 'name' => 'Institutions.name', 'student_status_name' => 'StudentStatuses.name'])
                ->first();
            /**POCOR-6902 ends*/

            $value = '';
            $name = '';
            if (!empty($studentInstitutions)) {
                $value = $studentInstitutions->student_status_name;
                $name = $studentInstitutions->name;
            }
            $entity->student_status_name = $value;

            return $name;
        }

        $staffInstitutions = [];
        if ($isStaff) {
            $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
            $today = date('Y-m-d');
            $staffInstitutions = $InstitutionStaffTable->find('list', [
                'keyField' => 'id',
                'valueField' => 'institutionName'
            ])
                ->find('inDateRange', ['start_date' => $today, 'end_date' => $today])
                ->matching('Institutions')
                ->where([$InstitutionStaffTable->aliasField('staff_id') => $userId])
                ->select(['id' => 'Institutions.id', 'institutionName' => 'Institutions.name'])
                ->group(['Institutions.id'])
                ->order(['Institutions.name'])
                ->toArray();
            return implode('<BR>', $staffInstitutions);
        }
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

//POCOR-7083 :: Start
    public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {

        //$institutionStudents = $this->institutionstudents;
        //print_r($institutionStudents->exists([$institutionStudents->aliasField($institutionStudents->foreignKey()) => $entity->id]));
        //POCOR-7179[START] delete custom field becouse when user is created from directory it insert value in custom field
        //POCOR-7179[END]
        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
//            Inflector::humanize(Inflector::underscore());
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        } else {
            TableRegistry::get('student_custom_field_values')->deleteAll(['student_id' => $entity->id]);
            $user = TableRegistry::get('security_users')
                ->find()->where(['id' => $$entity->id])->first();
            // echo "<pre>";print_r($entity);die;
            if (TableRegistry::get('security_users')->delete($entity)) {
                $this->Alert->success('general.delete.success', ['reset' => true]);
                return $this->controller->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'index']);
            }

        }
    }

    private function checkUsersChildRecords($entity)
    {
        $result = false;
        $securityUserId = $entity->id ?? 0;


        // First count child records and after that delete main record if there is no any child record found
        // Records to delete from tables-
        // institution_class_students (student_id),
        // user_activities (security_user_id),
        // student_custom_field_values (student_id),
        // institution_competency_results (student_id)
        // institution_student_absences (student_id),
        // institution_student_absence_days (student_id)
        // institution_student_absence_details (student_id),
        // institution_students (student_id)
        // student_risks_criterias
        // institution_student_risks (student_id)
        // institution_subject_students (student_id)
        // user_special_needs_devices (security_user_id)
        // user_special_needs_referrals (security_user_id)
        // user_special_needs_services (security_user_id)
        // institution_cases (assignee_id)
        // institution_staff_shifts (staff_id)
        // institution_student_admission (student_id)
        // institution_student_surveys (student_id)
        // institution_student_survey_answers (institution_student_survey_id)
        // institution_subject_students (student_id)
        // security_group_users  (security_user_id)
        // student_status_updates (security_user_id)


        if ($securityUserId) {
            // count all institution_class_students
            $institutionClassStudents = TableRegistry::get('institution_class_students')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all user activities
            $userActivities = TableRegistry::get('user_activities')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            // count all student_custom_field_values
            $studentCustomFieldValues = TableRegistry::get('student_custom_field_values')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_competency_results
            $institutionCompetencyResults = TableRegistry::get('institution_competency_results')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_absences
            $institutionStudentAbsences = TableRegistry::get('institution_student_absences')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_absence_days
            $institutionStudentAbsenceDays = TableRegistry::get('institution_student_absence_days')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_absence_details
            $institutionStudentAbsenceDetails = TableRegistry::get('institution_student_absence_details')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_students
            $institutionStudents = TableRegistry::get('institution_students')
                ->find()->where(['student_id' => $securityUserId])->count();

            // student_risks_criterias
            $students = TableRegistry::get('institution_student_risks');
            $query = $students->find()->select(['id'])->where(['student_id =' => $securityUserId]);

            $studentRiskIds = [];
            foreach ($query as $s) {
                $studentRiskIds[] = $s->id;
            }

            $studentRisksCriterias = 0;
            if (count($studentRiskIds)) {
                $studentRisksCriterias = TableRegistry::get('student_risks_criterias')
                    ->find()->where(['institution_student_risk_id IN' => $securityUserId])->count();
            }

            // count all institution_student_risks
            $institutionStudentRisks = TableRegistry::get('institution_student_risks')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_subject_students
            $institutionSubjectStudents = TableRegistry::get('institution_subject_students')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all user_special_needs_devices
            $userSpecialNeedsDevices = TableRegistry::get('user_special_needs_devices')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            // count all user_special_needs_referrals
            $userSpecialNeedsReferrals = TableRegistry::get('user_special_needs_referrals')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            // count all user_special_needs_services
            $userSpecialNeedsServices = TableRegistry::get('user_special_needs_services')
                ->find()->where(['security_user_id' => $securityUserId])->count();
            // count all user_special_needs_services
            $userSpecialNeedsAssessments = TableRegistry::get('user_special_needs_assessments')
                ->find()->where(['security_user_id' => $securityUserId])->count();


            // count all institution_cases
            $institutionCases = TableRegistry::get('institution_cases')
                ->find()->where(['assignee_id' => $securityUserId])->count();

            // count all institution_staff_shifts
            $institutionStaffShifts = TableRegistry::get('institution_staff_shifts')
                ->find()->where(['staff_id' => $securityUserId])->count();

            //// POCOR-7179[START]
            $userNationalities = TableRegistry::get('user_nationalities')
                ->find()->where(['security_user_id' => $securityUserId])->count();
            // POCOR-7179[END]

            //POCOR-7540 start
            // count all institution_student_admission
            $institutionStudentAdmission = TableRegistry::get('institution_student_admission')
                ->find()->where(['student_id' => $securityUserId])->count();

            // count all institution_student_surveys and institution_student_survey_answers
            $institutionStudentSurveys = TableRegistry::get('institution_student_surveys')
                ->find()->where(['student_id' => $securityUserId])->toArray();


            $institutionStudentSurveysIds = [];
            foreach ($institutionStudentSurveys as $s) {
                $institutionStudentSurveysIds[] = $s->id;
            }

            $institutionStudentSurveyAnswers = 0;
            if (count($institutionStudentSurveysIds)) {
                $institutionStudentSurveyAnswers = TableRegistry::get('institution_student_survey_answers')
                    ->find()->where(['institution_student_survey_id IN' => $institutionStudentSurveysIds])->count();
            }

            //count all security_group_users
            $securityGroupUsers = TableRegistry::get('security_group_users')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            //count all student_status_updates
            $studentStatusUpdates = TableRegistry::get(' student_status_updates')
                ->find()->where(['security_user_id' => $securityUserId])->count();

            //POCOR-7540 end

            if ($institutionClassStudents ||
                $userActivities ||
                $studentCustomFieldValues ||
                $institutionCompetencyResults ||
                $institutionStudentAbsences ||
                $institutionStudentAbsenceDays ||
                $institutionStudentAbsenceDetails ||
                $institutionStudents ||
                count($studentRiskIds) ||
                $studentRisksCriterias ||
                $institutionStudentRisks ||
                $institutionSubjectStudents ||
                $userSpecialNeedsDevices ||
                $userSpecialNeedsReferrals ||
                $userSpecialNeedsServices ||
                $userSpecialNeedsAssessments ||
                $institutionCases ||
                $institutionStaffShifts || $userNationalities ||
                $institutionStudentAdmission ||//POCOR-7540
                count($institutionStudentSurveys) ||//POCOR-7540
                $institutionStudentSurveyAnswers ||//POCOR-7540
                $securityGroupUsers ||//POCOR-7540
                $studentStatusUpdates//POCOR-7540
            ) {
                $result = true;
            }
        }

        return $result;
    }
    //POCOR-7083 :: end

    //POCOR-7224-HINDOL[start]
    /**
     * @param $security_user_id
     * @return mixed
     * @author Dr Khindol Madraimov khindol.madraimov@gmail.com
     */
    private static function getPendingTransfer($security_user_id)
    {
        $institutions = TableRegistry::get('institutions');
        $prev_institutions = TableRegistry::get('Institution.Institutions');
        $transfers = TableRegistry::get('Institution.InstitutionStudentTransfers');
        $doneStatus = $transfers::DONE;
        $pendingTransfer = $transfers->find()
            ->select([
                'id' => $transfers->aliasField('id'),
                'institution_id' => $transfers->aliasField('institution_id'),
                'previous_institution_id' => $transfers->aliasField('previous_institution_id'),
                'student_id' => $transfers->aliasField('student_id'),
                'institution_name' => $institutions->aliasField('name'),
                'institution_code' => $institutions->aliasField('code'),
                'previous_institution_name' => $prev_institutions->aliasField('name'),
                'previous_institution_code' => $prev_institutions->aliasField('code'),
                'academic_period_id' => $transfers->aliasField('academic_period_id'),
            ])
            ->InnerJoin([$institutions->alias() => $institutions->table()], [
                $institutions->aliasField('id =') . $transfers->aliasField('institution_id')])
            ->InnerJoin([$prev_institutions->alias() => $prev_institutions->table()], [
                $prev_institutions->aliasField('id =') . $transfers->aliasField('previous_institution_id')
            ])
            ->matching('Statuses', function ($q) use ($doneStatus) {
                return $q->where([
                    'Statuses.category <> ' => $doneStatus,
                ]);
            })
            ->where([
                $transfers->aliasField('student_id') => $security_user_id,
            ])
            ->first();
        return $pendingTransfer;
    }

    /**
     * @param Table $institutions
     * @param $security_user_id
     * @return mixed
     */
    private static function getPendingWithdraw($security_user_id)
    {
        $institutions = TableRegistry::get('institutions');
        $withdraws = TableRegistry::get('Institution.StudentWithdraw');
        $doneStatus = $withdraws::DONE;
        $pendingWithdraw = $withdraws->find()
            ->select([
                'institution_id' => $withdraws->aliasField('institution_id'),
                'student_id' => $withdraws->aliasField('student_id'),
                'institution_name' => $institutions->aliasField('name'),
                'institution_code' => $institutions->aliasField('code'),
                'academic_period_id' => $withdraws->aliasField('academic_period_id'),
            ])
            ->InnerJoin([$institutions->alias() => $institutions->table()], [
                $institutions->aliasField('id =') . $withdraws->aliasField('institution_id')
            ])
            ->matching('Statuses', function ($q) use ($doneStatus) {
                return $q->where([
                    'Statuses.category <> ' => $doneStatus,
                ]);
            })
            ->where([
                $withdraws->aliasField('student_id') => $security_user_id,
            ])->first();
        return $pendingWithdraw;
    }

    /**
     * @param $security_user_id
     * @return mixed
     * @author Dr Khindol Madraimov khindol.madraimov@gmail.com
     */
    private static function getStudent($security_user_id)
    {
        $institutionStudents = TableRegistry::get('institution_students');
        $studentStatuses = TableRegistry::get('Student.StudentStatuses');
        $institutions = TableRegistry::get('institutions');
        $statuses = $studentStatuses->findCodeList();
        $studentStatusCurrent = $statuses['CURRENT'];

        $student = $institutionStudents
            ->find()
            ->select([
                'institution_id' => $institutionStudents->aliasField('institution_id'),
                'student_id' => $institutionStudents->aliasField('student_id'),
                'student_status_id' => $institutionStudents->aliasField('student_status_id'),
                'institution_name' => $institutions->aliasField('name'),
                'institution_code' => $institutions->aliasField('code'),
                'academic_period_id' => $institutionStudents->aliasField('academic_period_id'),
                'academic_period_year' => $institutionStudents->aliasField('start_year'),
                'education_grade_id' => $institutionStudents->aliasField('education_grade_id')
            ])
            ->InnerJoin([$institutions->alias() => $institutions->table()], [
                $institutions->aliasField('id =') . $institutionStudents->aliasField('institution_id')
            ])
            ->where([
                $institutionStudents->aliasField('student_id') => $security_user_id,
                $institutionStudents->aliasField('student_status_id') => $studentStatusCurrent
            ])->first();
        return $student;
    }

    private static function getCountInternalSearch($conditions = [], $identityNumber, $identityCondition = [], $userTypeCondition = [])
    {
        $security_users = TableRegistry::get('security_users');
        $userIdentities = TableRegistry::get('user_identities');
        $genders = TableRegistry::get('genders');
        $mainIdentityTypes = TableRegistry::get('identity_types');
        $mainNationalities = TableRegistry::get('nationalities');
        if ($identityNumber == '') {
            $security_users_result = $security_users
                ->find()
                ->select([
                    $security_users->aliasField('id'),
                    $security_users->aliasField('openemis_no'),
                    $security_users->aliasField('first_name'),
                    $security_users->aliasField('middle_name'),
                    $security_users->aliasField('third_name'),
                    $security_users->aliasField('last_name'),
                    $security_users->aliasField('address_area_id'),
                    $security_users->aliasField('birthplace_area_id'),
                    $security_users->aliasField('gender_id'),
                    $security_users->aliasField('date_of_birth'),
                    $security_users->aliasField('nationality_id'),
                    $security_users->aliasField('identity_number'),
                    $security_users->aliasField('super_admin'),
                    $security_users->aliasField('status'),
                    $security_users->aliasField('is_student'),
                    $security_users->aliasField('is_staff'),
                    $security_users->aliasField('is_guardian'),
                    'Genders_id' => $genders->aliasField('id'),
                    'Genders_name' => $genders->aliasField('name'),
                    'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id' => $mainNationalities->aliasField('id'),
                    'MainNationalities_name' => $mainNationalities->aliasField('name'),
                ])
                ->LeftJoin(['Identities' => 'user_identities'], [
                    'Identities.security_user_id' => $security_users->aliasField('id'),
                ])
                ->LeftJoin([$genders->alias() => $genders->table()], [
                    $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                ])
                ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
                    $mainIdentityTypes->aliasField('id =') . $security_users->aliasField('identity_type_id')
                ])
                ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
                    $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                ])
                ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $conditions])
                ->group([$security_users->aliasField('id')])
                ->count();
        } else {
            //POCOR-5672 start new changes searching users by identity number
            $get_result_by_identity_users_result = $security_users
                ->find()
                ->select([
                    $security_users->aliasField('id'),
                    $security_users->aliasField('openemis_no'),
                    $security_users->aliasField('first_name'),
                    $security_users->aliasField('middle_name'),
                    $security_users->aliasField('third_name'),
                    $security_users->aliasField('last_name'),
                    $security_users->aliasField('address_area_id'),
                    $security_users->aliasField('birthplace_area_id'),
                    $security_users->aliasField('gender_id'),
                    $security_users->aliasField('date_of_birth'),
                    $security_users->aliasField('nationality_id'),
                    $security_users->aliasField('identity_number'),
                    $security_users->aliasField('super_admin'),
                    $security_users->aliasField('status'),
                    $security_users->aliasField('is_student'),
                    $security_users->aliasField('is_staff'),
                    $security_users->aliasField('is_guardian'),
                    'Genders_id' => $genders->aliasField('id'),
                    'Genders_name' => $genders->aliasField('name'),
                    'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id' => $mainNationalities->aliasField('id'),
                    'MainNationalities_name' => $mainNationalities->aliasField('name'),
                ])
                ->InnerJoin([$userIdentities->alias() => $userIdentities->table()], [
                    $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
                    $identityCondition
                ])
                ->LeftJoin([$genders->alias() => $genders->table()], [
                    $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                ])
                ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
                    $mainIdentityTypes->aliasField('id =') . $security_users->aliasField('identity_type_id')
                ])
                ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
                    $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                ])
                ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $userTypeCondition])
                ->group([$security_users->aliasField('id')])
                ->count();
            if ($get_result_by_identity_users_result == 0) {
                $security_users_result = $security_users
                    ->find()
                    ->select([
                        $security_users->aliasField('id'),
                        $security_users->aliasField('openemis_no'),
                        $security_users->aliasField('first_name'),
                        $security_users->aliasField('middle_name'),
                        $security_users->aliasField('third_name'),
                        $security_users->aliasField('last_name'),
                        $security_users->aliasField('address_area_id'),
                        $security_users->aliasField('birthplace_area_id'),
                        $security_users->aliasField('gender_id'),
                        $security_users->aliasField('date_of_birth'),
                        $security_users->aliasField('nationality_id'),
                        $security_users->aliasField('identity_number'),
                        $security_users->aliasField('super_admin'),
                        $security_users->aliasField('status'),
                        $security_users->aliasField('is_student'),
                        $security_users->aliasField('is_staff'),
                        $security_users->aliasField('is_guardian'),
                        'Genders_id' => $genders->aliasField('id'),
                        'Genders_name' => $genders->aliasField('name'),
                        'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                        'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                        'MainNationalities_id' => $mainNationalities->aliasField('id'),
                        'MainNationalities_name' => $mainNationalities->aliasField('name'),
                    ])
                    ->InnerJoin([$userIdentities->alias() => $userIdentities->table()], [
                        $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
                        $identityCondition
                    ])
                    ->LeftJoin([$genders->alias() => $genders->table()], [
                        $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                    ])
                    ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
                        $mainIdentityTypes->aliasField('id =') . $security_users->aliasField('identity_type_id')
                    ])
                    ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
                        $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                    ])
                    ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $conditions])
                    ->group([$security_users->aliasField('id')])
                    ->count();
            } else {
                $security_users_result = $get_result_by_identity_users_result;
            }
        }
        //POCOR-5672 ends
        return $security_users_result;
    }

    private static function getStudentCustomData($student_id = null)
    {
        $studentCustomFieldValues = TableRegistry::get('student_custom_field_values');
        $studentCustomFieldOptions = TableRegistry::get('student_custom_field_options');
        $studentCustomFields = TableRegistry::get('student_custom_fields');
        $studentCustomData = $studentCustomFieldValues->find()
            ->select([
                'id' => $studentCustomFieldValues->aliasField('id'),
                'custom_id' => 'studentCustomField.id',
                'student_id' => $studentCustomFieldValues->aliasField('student_id'),
                'student_custom_field_id' => $studentCustomFieldValues->aliasField('student_custom_field_id'),
                'text_value' => $studentCustomFieldValues->aliasField('text_value'),
                'number_value' => $studentCustomFieldValues->aliasField('number_value'),
                'decimal_value' => $studentCustomFieldValues->aliasField('decimal_value'),
                'textarea_value' => $studentCustomFieldValues->aliasField('textarea_value'),
                'date_value' => $studentCustomFieldValues->aliasField('date_value'),
                'time_value' => $studentCustomFieldValues->aliasField('time_value'),
                'option_value_text' => $studentCustomFieldOptions->aliasField('name'),
                'name' => 'studentCustomField.name',
                'field_type' => 'studentCustomField.field_type',
            ])->leftJoin(
                ['studentCustomField' => 'student_custom_fields'],
                [
                    'studentCustomField.id = ' . $studentCustomFieldValues->aliasField('student_custom_field_id')
                ])
            ->leftJoin(
                [$studentCustomFieldOptions->alias() => $studentCustomFieldOptions->table()],
                [
                    $studentCustomFieldOptions->aliasField('student_custom_field_id = ') . $studentCustomFieldValues->aliasField('student_custom_field_id'),
                    $studentCustomFieldOptions->aliasField('id = ') . $studentCustomFieldValues->aliasField('number_value')
                ])
            ->where([
                $studentCustomFieldValues->aliasField('student_id') => $student_id,
            ])->hydrate(false)->toArray();
        $custom_field = array();
        $count = 0;
        if (!empty($studentCustomData)) {
            foreach ($studentCustomData as $val) {
                $custom_field['custom_field'][$count]["id"] = (!empty($val['custom_id']) ? $val['custom_id'] : '');
                $custom_field['custom_field'][$count]["name"] = (!empty($val['name']) ? $val['name'] : '');
                $fieldTypes[$count] = (!empty($val['field_type']) ? $val['field_type'] : '');
                $fieldType = $fieldTypes[$count];
                if ($fieldType == 'TEXT') {
                    $custom_field['custom_field'][$count]["text_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                } else if ($fieldType == 'CHECKBOX') {
                    $custom_field['custom_field'][$count]["checkbox_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'NUMBER') {
                    $custom_field['custom_field'][$count]["number_value"] = (!empty($val['number_value']) ? $val['number_value'] : '');
                } else if ($fieldType == 'DECIMAL') {
                    $custom_field['custom_field'][$count]["decimal_value"] = (!empty($val['decimal_value']) ? $val['decimal_value'] : '');
                } else if ($fieldType == 'TEXTAREA') {
                    $custom_field['custom_field'][$count]["textarea_value"] = (!empty($val['textarea_value']) ? $val['textarea_value'] : '');
                } else if ($fieldType == 'DROPDOWN') {
                    $custom_field['custom_field'][$count]["dropdown_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'DATE') {
                    $custom_field['custom_field'][$count]["date_value"] = date('Y-m-d', strtotime($val->date_value));
                } else if ($fieldType == 'TIME') {
                    $custom_field['custom_field'][$count]["time_value"] = date('h:i A', strtotime($val->time_value));
                } else if ($fieldType == 'COORDINATES') {
                    $custom_field['custom_field'][$count]["cordinate_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                }
                $count++;
            }
        }
        return $custom_field;
    }//POCOR-7072 ends

    /**
     * @param $requestDataParams
     * @return array
     */
    public static function getUserInternalSearch($requestDataParams)
    {

        $institutionId = (array_key_exists('institution_id', $requestDataParams)) ? $requestDataParams['institution_id'] : null;
        $userTypeId = (array_key_exists('user_type_id', $requestDataParams)) ? $requestDataParams['user_type_id'] : null;
        $firstName = (array_key_exists('first_name', $requestDataParams)) ? $requestDataParams['first_name'] : null;
        $lastName = (array_key_exists('last_name', $requestDataParams)) ? $requestDataParams['last_name'] : null;
        $openemisNo = (array_key_exists('openemis_no', $requestDataParams)) ? $requestDataParams['openemis_no'] : null;
        $identityNumber = (array_key_exists('identity_number', $requestDataParams)) ? $requestDataParams['identity_number'] : null;
        $dateOfBirth = (array_key_exists('date_of_birth', $requestDataParams)) ? $requestDataParams['date_of_birth'] : null;
        $identityTypeId = (array_key_exists('identity_type_id', $requestDataParams)) ? $requestDataParams['identity_type_id'] : null;
        $nationalityId = (array_key_exists('nationality_id', $requestDataParams)) ? $requestDataParams['nationality_id'] : null;
        $limit = (array_key_exists('limit', $requestDataParams)) ? $requestDataParams['limit'] : 10;
        $page = (array_key_exists('page', $requestDataParams)) ? $requestDataParams['page'] : 1;
        $get_user_id = (array_key_exists('id', $requestDataParams)) ? $requestDataParams['id'] : null;

        $conditions = [];
        $security_users = TableRegistry::get('security_users');
        $userIdentities = TableRegistry::get('user_identities');
        $genders = TableRegistry::get('genders');
        $mainIdentityTypes = TableRegistry::get('identity_types');
        $mainNationalities = TableRegistry::get('nationalities');
        $areaAdministratives = TableRegistry::get('area_administratives');
        $birthAreaAdministratives = TableRegistry::get('area_administratives');

        if (!empty($firstName)) {
            $conditions[$security_users->aliasField('first_name') . ' LIKE'] = $firstName . '%';
        }
        if (!empty($lastName)) {
            $conditions[$security_users->aliasField('last_name') . ' LIKE'] = $lastName . '%';
        }
        if (!empty($openemisNo)) {
            $conditions[$security_users->aliasField('openemis_no') . ' LIKE'] = $openemisNo . '%';
        }
        if (!empty($dateOfBirth)) {
            $conditions[$security_users->aliasField('date_of_birth')] = date_create($dateOfBirth)->format('Y-m-d');
        }

        if (!empty($userTypeId)) {
            //POCOR-7192 comment user_type condition starts
            /*if($userTypeId ==1){
                $conditions[$security_users->aliasField('is_student')] = 1;
            }else if($userTypeId ==2){
                $conditions[$security_users->aliasField('is_staff')] = 1;
            }else if($userTypeId ==3){
                $conditions[$security_users->aliasField('is_guardian')] = 1;
            }*///POCOR-7192 Ends
        }

        //it is user for getting single user data
        if (!empty($get_user_id)) {
            $conditions[$security_users->aliasField('id')] = $get_user_id;
        }
        $totalCount = 0;
        if ($identityNumber == '') {
            $security_users_result = $security_users
                ->find()
                ->select([
                    $security_users->aliasField('id'),
                    $security_users->aliasField('username'),
                    $security_users->aliasField('password'),
                    $security_users->aliasField('openemis_no'),
                    $security_users->aliasField('first_name'),
                    $security_users->aliasField('middle_name'),
                    $security_users->aliasField('third_name'),
                    $security_users->aliasField('last_name'),
                    $security_users->aliasField('preferred_name'),
                    $security_users->aliasField('email'),
                    $security_users->aliasField('address'),
                    $security_users->aliasField('postal_code'),
                    $security_users->aliasField('date_of_death'),
                    $security_users->aliasField('external_reference'),
                    $security_users->aliasField('last_login'),
                    $security_users->aliasField('photo_name'),
                    $security_users->aliasField('photo_content'),
                    $security_users->aliasField('preferred_language'),
                    $security_users->aliasField('address_area_id'),
                    $security_users->aliasField('birthplace_area_id'),
                    $security_users->aliasField('gender_id'),
                    $security_users->aliasField('date_of_birth'),
                    $security_users->aliasField('nationality_id'),
                    $security_users->aliasField('identity_number'),
                    $security_users->aliasField('super_admin'),
                    $security_users->aliasField('status'),
                    $security_users->aliasField('is_student'),
                    $security_users->aliasField('is_staff'),
                    $security_users->aliasField('is_guardian'),
                    'Genders_id' => $genders->aliasField('id'),
                    'Genders_name' => $genders->aliasField('name'),
                    'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id' => $mainNationalities->aliasField('id'),
                    'MainNationalities_name' => $mainNationalities->aliasField('name'),
                    'area_name' => $areaAdministratives->aliasField('name'),
                    'area_code' => $areaAdministratives->aliasField('code'),
                    'birth_area_name' => 'birthAreaAdministratives.name',
                    'birth_area_code' => 'birthAreaAdministratives.code',
                    'MainIdentityTypes_number' => $userIdentities->aliasField('number'),
                ])
                ->LeftJoin([$userIdentities->alias() => $userIdentities->table()], [
                    $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id')
                ])
                ->LeftJoin([$genders->alias() => $genders->table()], [
                    $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                ])
                ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
                    $mainIdentityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')
                ])
                ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
                    $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                ])
                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table()], [
                    $areaAdministratives->aliasField('id =') . $security_users->aliasField('address_area_id')
                ])
                ->LeftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->table()], [
                    'birthAreaAdministratives.id =' . $security_users->aliasField('birthplace_area_id')
                ])
                ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $conditions])
                ->group([$security_users->aliasField('id')])
                ->limit($limit)
                ->page($page)
                ->toArray();

            $totalCount = self::getCountInternalSearch($conditions, $identityNumber);
        } else {
            //POCOR-5672 start new changes searching users by identity number
            $userTypeCondition = [];
            if (!empty($userTypeId)) {
                //POCOR-7192 comment user_type condition starts
                /*if($userTypeId ==1){
                    $userTypeCondition[$security_users->aliasField('is_student')] = 1;
                }else if($userTypeId ==2){
                    $userTypeCondition[$security_users->aliasField('is_staff')] = 1;
                }else if($userTypeId ==3){
                    $userTypeCondition[$security_users->aliasField('is_guardian')] = 1;
                }*///POCOR-7192 ends
            }
            $identityCondition = [];
            if (!empty($identityTypeId) && !empty($identityNumber) && !empty($nationalityId)) {
                $identityCondition[$userIdentities->aliasField('identity_type_id')] = $identityTypeId;
                $identityCondition[$userIdentities->aliasField('nationality_id')] = $nationalityId;
                $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
            } else if (!empty($identityTypeId) && !empty($identityNumber) && empty($nationalityId)) {
                $identityCondition[$userIdentities->aliasField('identity_type_id')] = $identityTypeId;
                $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
            } else if (empty($identityTypeId) && !empty($identityNumber) && empty($nationalityId)) {
                $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
            }

            $get_result_by_identity_users_result = $security_users
                ->find()
                ->select([
                    $security_users->aliasField('id'),
                    $security_users->aliasField('username'),
                    $security_users->aliasField('password'),
                    $security_users->aliasField('openemis_no'),
                    $security_users->aliasField('first_name'),
                    $security_users->aliasField('middle_name'),
                    $security_users->aliasField('third_name'),
                    $security_users->aliasField('last_name'),
                    $security_users->aliasField('preferred_name'),
                    $security_users->aliasField('email'),
                    $security_users->aliasField('address'),
                    $security_users->aliasField('postal_code'),
                    $security_users->aliasField('date_of_death'),
                    $security_users->aliasField('external_reference'),
                    $security_users->aliasField('last_login'),
                    $security_users->aliasField('photo_name'),
                    $security_users->aliasField('photo_content'),
                    $security_users->aliasField('preferred_language'),
                    $security_users->aliasField('address_area_id'),
                    $security_users->aliasField('birthplace_area_id'),
                    $security_users->aliasField('gender_id'),
                    $security_users->aliasField('date_of_birth'),
                    $security_users->aliasField('nationality_id'),
                    $security_users->aliasField('identity_number'),
                    $security_users->aliasField('super_admin'),
                    $security_users->aliasField('status'),
                    $security_users->aliasField('is_student'),
                    $security_users->aliasField('is_staff'),
                    $security_users->aliasField('is_guardian'),
                    'Genders_id' => $genders->aliasField('id'),
                    'Genders_name' => $genders->aliasField('name'),
                    'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id' => $mainNationalities->aliasField('id'),
                    'MainNationalities_name' => $mainNationalities->aliasField('name'),
                    'area_name' => $areaAdministratives->aliasField('name'),
                    'area_code' => $areaAdministratives->aliasField('code'),
                    'birth_area_name' => 'birthAreaAdministratives.name',
                    'birth_area_code' => 'birthAreaAdministratives.code',
                    'MainIdentityTypes_number' => $userIdentities->aliasField('number'),
                ])
                ->InnerJoin([$userIdentities->alias() => $userIdentities->table()], [
                    $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
                    $identityCondition
                    //$userIdentities->aliasField('number') ." LIKE '" . $identityNumber . "%'"
                ])
                ->LeftJoin([$genders->alias() => $genders->table()], [
                    $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                ])
                ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
                    $mainIdentityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')
                ])
                ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
                    $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                ])
                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table()], [
                    $areaAdministratives->aliasField('id =') . $security_users->aliasField('address_area_id')
                ])
                ->LeftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->table()], [
                    'birthAreaAdministratives.id =' . $security_users->aliasField('birthplace_area_id')
                ])
                ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $userTypeCondition])
                ->group([$security_users->aliasField('id')])
                ->limit($limit)
                ->page($page)
                ->toArray();
            if (empty($get_result_by_identity_users_result)) {
                $security_users_result = $security_users
                    ->find()
                    ->select([
                        $security_users->aliasField('id'),
                        $security_users->aliasField('username'),
                        $security_users->aliasField('password'),
                        $security_users->aliasField('openemis_no'),
                        $security_users->aliasField('first_name'),
                        $security_users->aliasField('middle_name'),
                        $security_users->aliasField('third_name'),
                        $security_users->aliasField('last_name'),
                        $security_users->aliasField('preferred_name'),
                        $security_users->aliasField('email'),
                        $security_users->aliasField('address'),
                        $security_users->aliasField('postal_code'),
                        $security_users->aliasField('date_of_death'),
                        $security_users->aliasField('external_reference'),
                        $security_users->aliasField('last_login'),
                        $security_users->aliasField('photo_name'),
                        $security_users->aliasField('photo_content'),
                        $security_users->aliasField('preferred_language'),
                        $security_users->aliasField('address_area_id'),
                        $security_users->aliasField('birthplace_area_id'),
                        $security_users->aliasField('gender_id'),
                        $security_users->aliasField('date_of_birth'),
                        $security_users->aliasField('nationality_id'),
                        $security_users->aliasField('identity_number'),
                        $security_users->aliasField('super_admin'),
                        $security_users->aliasField('status'),
                        $security_users->aliasField('is_student'),
                        $security_users->aliasField('is_staff'),
                        $security_users->aliasField('is_guardian'),
                        'Genders_id' => $genders->aliasField('id'),
                        'Genders_name' => $genders->aliasField('name'),
                        'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                        'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                        'MainNationalities_id' => $mainNationalities->aliasField('id'),
                        'MainNationalities_name' => $mainNationalities->aliasField('name'),
                        'area_name' => $areaAdministratives->aliasField('name'),
                        'area_code' => $areaAdministratives->aliasField('code'),
                        'birth_area_name' => 'birthAreaAdministratives.name',
                        'birth_area_code' => 'birthAreaAdministratives.code',
                        'MainIdentityTypes_number' => $userIdentities->aliasField('number'),
                    ])
                    ->InnerJoin([$userIdentities->alias() => $userIdentities->table()], [
                        $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
                        $identityCondition
                    ])
                    ->LeftJoin([$genders->alias() => $genders->table()], [
                        $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                    ])
                    ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
                        $mainIdentityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')
                    ])
                    ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
                        $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                    ])
                    ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table()], [
                        $areaAdministratives->aliasField('id =') . $security_users->aliasField('address_area_id')
                    ])
                    ->LeftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->table()], [
                        'birthAreaAdministratives.id =' . $security_users->aliasField('birthplace_area_id')
                    ])
                    ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $conditions])
                    ->group([$security_users->aliasField('id')])
                    ->limit($limit)
                    ->page($page)
                    ->toArray();
            } else {
                $security_users_result = $get_result_by_identity_users_result;
            }

            $totalCount = self::getCountInternalSearch($conditions, $identityNumber, $identityCondition, $userTypeCondition);//POCOR-5672 ends
        }
        $institutions = TableRegistry::get('institutions');
        $institutionsTbl = $institutions
            ->find()
            ->select([
                'institution_name' => $institutions->aliasField('name'),
                'institution_code' => $institutions->aliasField('code')
            ])->where([
                $institutions->aliasField('id') => $institutionId
            ])->first();

        $institutionStudents = TableRegistry::get('institution_students');
        $institutionStaff = TableRegistry::get('institution_staff');

        $user_internal_search_result = [];
        foreach ($security_users_result AS $security_user) {
            $MainNationalities_id = !empty($security_user['MainNationalities_id']) ? $security_user['MainNationalities_id'] : '';
            $MainNationalities_name = !empty($security_user['MainNationalities_name']) ? $security_user['MainNationalities_name'] : '';
            $MainIdentityTypes_id = !empty($security_user['MainIdentityTypes_id']) ? $security_user['MainIdentityTypes_id'] : '';
            $MainIdentityTypes_name = !empty($security_user['MainIdentityTypes_name']) ? $security_user['MainIdentityTypes_name'] : '';
            $identity_number = !empty($security_user['MainIdentityTypes_number']) ? $security_user['MainIdentityTypes_number'] : '';
            $security_user_id = $security_user['id'];

            $UserNeeds = TableRegistry::get('user_special_needs_assessments');
            $SpecialNeeds = $UserNeeds->find()
                ->where([$UserNeeds->aliasField('security_user_id') => $security_user_id])
                ->count();
            $has_special_needs = ($SpecialNeeds == 1) ? true : false;

            $is_same_school = $is_diff_school = $academic_period_id = $academic_period_year = 0;
            $is_pending_transfer = $is_pending_withdraw = $education_grade_id = $institution_id = 0;
            $institution_code = $institution_name =
            $pending_transfer_institution_name = $pending_transfer_institution_code =
            $pending_transfer_prev_institution_code = $pending_transfer_prev_institution_name =
            $pending_withdraw_institution_name = $pending_withdraw_institution_code = '';
            $CustomDataArray = [];
//            Log::write('debug', 'getUserInternalSearch');
//            Log::write('debug', '$userTypeId');
//            Log::write('debug', $userTypeId);
            if (!empty($userTypeId)) {
                if ($security_user['is_student'] == 1) {
                    $account_type = 'Student';
                } else if ($security_user['is_staff'] == 1) {
                    $account_type = 'Staff';
                } else if ($security_user['is_guardian'] == 1) {
                    $account_type = 'Guardian';
                } else {
                    $account_type = 'Others';
                }
                if ($userTypeId == self::STUDENT) {
//                    Log::write('debug', '$userTypeId == self::STUDENT');
                    //POCOR-7224-HINDOL
                    //$account_type = 'Student';
                    $student = self::getStudent($security_user_id);
                    if (!empty($student)) {
                        $institution_id = $student->institution_id;
                        $institution_name = $student->institution_name;
                        $institution_code = $student->institution_code;
                        $academic_period_id = $student->academic_period_id;
                        $academic_period_year = $student->academic_period_year;
                        $education_grade_id = $student->education_grade_id;
                        if ($student->institution_id == $institutionId) {
                            $is_same_school = 1;
                        } else {
                            $is_diff_school = 1;
                        }
                    }

                    $pendingTransfer = self::getPendingTransfer($security_user_id);
//                    Log::write('debug', '$pendingTransfer');
//                    Log::write('debug', $pendingTransfer);
                    $pendingWithdraw = self::getPendingWithdraw($security_user_id);
//                    Log::write('debug', '$pendingWithdraw');
//                    Log::write('debug', $pendingWithdraw);

                    if ($pendingTransfer) {
                        $is_pending_transfer = 1;
                        $pending_transfer_institution_name = $pendingTransfer->institution_name;
                        $pending_transfer_institution_code = $pendingTransfer->institution_code;
                        $pending_transfer_prev_institution_code = $pendingTransfer->previous_institution_code;
                        $pending_transfer_prev_institution_name = $pendingTransfer->previous_institution_name;
                    }
                    if ($pendingWithdraw) {
                        $is_pending_withdraw = 1;
                        $pending_withdraw_institution_name = $pendingWithdraw->institution_name;
                        $pending_withdraw_institution_code = $pendingWithdraw->institution_code;
                    }
//
                    //get student custom data
                    $CustomDataArray = self::getStudentCustomData($security_user_id);
                }
                if ($userTypeId == self::STAFF) {
                    //$account_type = 'Staff';
                    $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
                    $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

                    $institutionStaffTbl = $institutionStaff
                        ->find()
                        ->select([
                            'institution_id' => $institutionStaff->aliasField('institution_id'),
                            'staff_id' => $institutionStaff->aliasField('staff_id'),
                            'institution_position_id' => $institutionStaff->aliasField('institution_position_id'),
                            'staff_status_id' => $institutionStaff->aliasField('staff_status_id'),
                            'institution_name' => $institutions->aliasField('name'),
                            'institution_code' => $institutions->aliasField('code')
                        ])
                        ->InnerJoin([$institutions->alias() => $institutions->table()], [
                            $institutions->aliasField('id =') . $institutionStaff->aliasField('institution_id')
                        ])
                        ->where([
                            $institutionStaff->aliasField('staff_id') => $security_user_id,
                            $institutionStaff->aliasField('staff_status_id') => $assignedStatus,
                            $institutionStaff->aliasField('institution_id') => $institutionId
                        ])->toArray();

                    if (!empty($institutionStaffTbl)) {
                        $positionArray = [];
                        $is_same_school = 1;
                        foreach ($institutionStaffTbl as $skey => $sval) {
                            $institution_id = $sval->institution_id;
                            $institution_name = $sval->institution_name;
                            $institution_code = $sval->institution_code;
                            $positionArray[$skey] = $sval->institution_position_id;
                        }
                    } else {
                        $institutionStaffTbl = $institutionStaff
                            ->find()
                            ->select([
                                'institution_id' => $institutionStaff->aliasField('institution_id'),
                                'staff_id' => $institutionStaff->aliasField('staff_id'),
                                'institution_position_id' => $institutionStaff->aliasField('institution_position_id'),
                                'staff_status_id' => $institutionStaff->aliasField('staff_status_id'),
                                'institution_name' => $institutions->aliasField('name'),
                                'institution_code' => $institutions->aliasField('code')
                            ])
                            ->InnerJoin([$institutions->alias() => $institutions->table()], [
                                $institutions->aliasField('id =') . $institutionStaff->aliasField('institution_id')
                            ])
                            ->where([
                                $institutionStaff->aliasField('staff_id') => $security_user_id,
                                $institutionStaff->aliasField('staff_status_id') => $assignedStatus
                            ])->toArray();
                        if (empty($institutionStaffTbl)) {
                            $is_diff_school = 0;
                        } else {
                            $is_diff_school = 1;
                        }
                        $positionArray = [];
                        foreach ($institutionStaffTbl as $skey => $sval) {
                            $institution_id = $sval->institution_id;
                            $institution_name = $sval->institution_name;
                            $institution_code = $sval->institution_code;
                            $positionArray[$skey] = $sval->institution_position_id;
                        }
                    }
                    //get staff custom data
                    $CustomDataArray = self::getStaffCustomData($security_user_id);
                }
                if ($userTypeId == self::GUARDIAN) {
                    //$account_type = 'Guardian';
                }
                if ($userTypeId == self::OTHER) {
                    //$account_type = 'Others';
                }
            }

            $user_internal_search_result[] = [
                'id' => $security_user_id,
                'username' => $security_user['username'],
                'password' => $security_user['password'],
                'openemis_no' => $security_user['openemis_no'],
                'first_name' => $security_user['first_name'],
                'middle_name' => $security_user['middle_name'],
                'third_name' => $security_user['third_name'],
                'last_name' => $security_user['last_name'],
                'preferred_name' => $security_user['preferred_name'],
                'email' => $security_user['email'],
                'address' => $security_user['address'],
                'postal_code' => $security_user['postal_code'],
                'gender_id' => $security_user['gender_id'],
                'external_reference' => $security_user['external_reference'],
                'last_login' => $security_user['last_login'],
                'photo_name' => $security_user['photo_name'],
                'photo_content' => $security_user['photo_content'],
                'preferred_language' => $security_user['preferred_language'],
                'address_area_id' => $security_user['address_area_id'],
                'birthplace_area_id' => $security_user['birthplace_area_id'],
                'super_admin' => $security_user['super_admin'],
                'status' => $security_user['status'],
                'is_student' => $security_user['is_student'],
                'is_staff' => $security_user['is_staff'],
                'is_guardian' => $security_user['is_guardian'],
                'name' => $security_user['first_name'] . " " . $security_user['last_name'],
                'date_of_birth' => $security_user['date_of_birth']->format('Y-m-d'),
                'gender' => $security_user['Genders_name'],
                'nationality_id' => $MainNationalities_id,
                'nationality' => $MainNationalities_name,
                'identity_type_id' => $MainIdentityTypes_id,
                'identity_type' => $MainIdentityTypes_name,
                'identity_number' => $identity_number,
                'has_special_needs' => $has_special_needs,
                'area_name' => $security_user['area_name'],
                'area_code' => $security_user['area_code'],
                'birth_area_name' => $security_user['birth_area_name'],
                'birth_area_code' => $security_user['birth_area_code'],
                'is_same_school' => $is_same_school,
                'is_diff_school' => $is_diff_school,
                'is_pending_withdraw' => $is_pending_withdraw,
                'is_pending_transfer' => $is_pending_transfer,
                'current_enrol_institution_id' => $institution_id,
                'current_enrol_institution_name' => $institution_name,
                'current_enrol_institution_code' => $institution_code,
                'pending_withdraw_institution_code' => $pending_withdraw_institution_code,
                'pending_withdraw_institution_name' => $pending_withdraw_institution_name,
                'pending_transfer_prev_institution_code' => $pending_transfer_prev_institution_code,
                'pending_transfer_prev_institution_name' => $pending_transfer_prev_institution_name,
                'pending_transfer_institution_code' => $pending_transfer_institution_code,
                'pending_transfer_institution_name' => $pending_transfer_institution_name,
                'current_enrol_academic_period_id' => $academic_period_id,
                'current_enrol_academic_period_year' => $academic_period_year,
                'current_enrol_education_grade_id' => $education_grade_id,
                'institution_name' => $institutionsTbl->institution_name,
                'institution_code' => $institutionsTbl->institution_code,
                'positions' => $positionArray,
                'account_type' => $account_type,
                'custom_data' => $CustomDataArray];
        }
        $userInternalSearch = ['data' => $user_internal_search_result, 'total' => $totalCount];
        return $userInternalSearch;
    }

    //POCOR-7224-HINDOL[end]
    private static function getStaffCustomData($staff_id = null)
    {
        $staffCustomFieldValues = TableRegistry::get('staff_custom_field_values');
        $staffCustomFieldOptions = TableRegistry::get('staff_custom_field_options');
        $staffCustomFields = TableRegistry::get('staff_custom_fields');
        $staffCustomData = $staffCustomFieldValues->find()
            ->select([
                'id' => $staffCustomFieldValues->aliasField('id'),
                'custom_id' => 'staffCustomField.id',
                'staff_id' => $staffCustomFieldValues->aliasField('staff_id'),
                'staff_custom_field_id' => $staffCustomFieldValues->aliasField('staff_custom_field_id'),
                'text_value' => $staffCustomFieldValues->aliasField('text_value'),
                'number_value' => $staffCustomFieldValues->aliasField('number_value'),
                'decimal_value' => $staffCustomFieldValues->aliasField('decimal_value'),
                'textarea_value' => $staffCustomFieldValues->aliasField('textarea_value'),
                'date_value' => $staffCustomFieldValues->aliasField('date_value'),
                'time_value' => $staffCustomFieldValues->aliasField('time_value'),
                'option_value_text' => $staffCustomFieldOptions->aliasField('name'),
                'name' => 'staffCustomField.name',
                'field_type' => 'staffCustomField.field_type',
            ])->leftJoin(
                ['staffCustomField' => 'staff_custom_fields'],
                [
                    'staffCustomField.id = ' . $staffCustomFieldValues->aliasField('staff_custom_field_id')
                ])
            ->leftJoin(
                [$staffCustomFieldOptions->alias() => $staffCustomFieldOptions->table()],
                [
                    $staffCustomFieldOptions->aliasField('staff_custom_field_id = ') . $staffCustomFieldValues->aliasField('staff_custom_field_id'),
                    $staffCustomFieldOptions->aliasField('id = ') . $staffCustomFieldValues->aliasField('number_value')
                ])
            ->where([
                $staffCustomFieldValues->aliasField('staff_id') => $staff_id,
            ])->hydrate(false)->toArray();
        $custom_field = array();
        $count = 0;
        if (!empty($staffCustomData)) {
            foreach ($staffCustomData as $val) {
                $custom_field['custom_field'][$count]["id"] = (!empty($val['custom_id']) ? $val['custom_id'] : '');
                $custom_field['custom_field'][$count]["name"] = (!empty($val['name']) ? $val['name'] : '');
                $fieldTypes[$count] = (!empty($val['field_type']) ? $val['field_type'] : '');
                $fieldType = $fieldTypes[$count];
                if ($fieldType == 'TEXT') {
                    $custom_field['custom_field'][$count]["text_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                } else if ($fieldType == 'CHECKBOX') {
                    $custom_field['custom_field'][$count]["checkbox_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'NUMBER') {
                    $custom_field['custom_field'][$count]["number_value"] = (!empty($val['number_value']) ? $val['number_value'] : '');
                } else if ($fieldType == 'DECIMAL') {
                    $custom_field['custom_field'][$count]["decimal_value"] = (!empty($val['decimal_value']) ? $val['decimal_value'] : '');
                } else if ($fieldType == 'TEXTAREA') {
                    $custom_field['custom_field'][$count]["textarea_value"] = (!empty($val['textarea_value']) ? $val['textarea_value'] : '');
                } else if ($fieldType == 'DROPDOWN') {
                    $custom_field['custom_field'][$count]["dropdown_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'DATE') {
                    $custom_field['custom_field'][$count]["date_value"] = date('Y-m-d', strtotime($val->date_value));
                } else if ($fieldType == 'TIME') {
                    $custom_field['custom_field'][$count]["time_value"] = date('h:i A', strtotime($val->time_value));
                } else if ($fieldType == 'COORDINATES') {
                    $custom_field['custom_field'][$count]["cordinate_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                }
                $count++;
            }
        }
        return $custom_field;
    }

}
