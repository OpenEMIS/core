<?php

namespace App\Http\Controllers\BaseApi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Services\PermissionService;
use App\Services\Security\SuperAdminProbeGuard; //POCOR-9710
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache; //POCOR-9660: cache allowed orderby columns

class CrudApiController extends Controller
{
    protected $allowedResources = [
        // POCOR-9694: OpenEMIS Runtime abstraction tables — read-only resources for the Runtime UI
        'tasks' => \App\Models\Api5\Tasks::class, //POCOR-9694
        'task-jobs' => \App\Models\Api5\TaskJobs::class, //POCOR-9694
        'task-failures' => \App\Models\Api5\TaskFailures::class, //POCOR-9694
        'department-staff' => \App\Models\Api5\DepartmentStaff::class, // POCOR_8030
        'institution-departments' => \App\Models\Api5\InstitutionDepartments::class, // POCOR_8030
        'institution-infrastructure-attachments' => \App\Models\InstitutionInfrastructureAttachments::class,
        'infrastructure-attachment-types' => \App\Models\InfrastructureAttachmentTypes::class,
        'staff-leave-entitlements' => \App\Models\Api5\StaffLeaveEntitlements::class,
        'staff-leave-policies' => \App\Models\Api5\StaffLeavePolicies::class,
        'staff-leave-policy-types' => \App\Models\Api5\StaffLeavePolicyTypes::class,
        'workflows-filters' => \App\Models\Api5\WorkflowsFilters::class,
        'workflows' => \App\Models\Api5\Workflows::class,
        'workflow-transitions' => \App\Models\Api5\WorkflowTransitions::class,
        'workflow-steps-roles' => \App\Models\Api5\WorkflowStepsRoles::class,
        'workflow-steps-params' => \App\Models\Api5\WorkflowStepsParams::class,
        'workflow-steps' => \App\Models\Api5\WorkflowSteps::class,
        'workflow-statuses-steps' => \App\Models\Api5\WorkflowStatusesSteps::class,
        'workflow-statuses' => \App\Models\Api5\WorkflowStatuses::class,
        'workflow-rules' => \App\Models\Api5\WorkflowRules::class,
        'workflow-rule-events' => \App\Models\Api5\WorkflowRuleEvents::class,
        'workflow-models' => \App\Models\Api5\WorkflowModels::class,
        'workflow-comments' => \App\Models\Api5\WorkflowComments::class,
        'workflow-actions' => \App\Models\Api5\WorkflowActions::class,
        'webhooks' => \App\Models\Api5\Webhooks::class,
        'webhook-events' => \App\Models\Api5\WebhookEvents::class,
        'utility-telephone-types' => \App\Models\Api5\UtilityTelephoneTypes::class,
        'utility-telephone-conditions' => \App\Models\Api5\UtilityTelephoneConditions::class,
        'utility-internet-types' => \App\Models\Api5\UtilityInternetTypes::class,
        'utility-internet-conditions' => \App\Models\Api5\UtilityInternetConditions::class,
        'utility-internet-bandwidths' => \App\Models\Api5\UtilityInternetBandwidths::class,
        'utility-electricity-types' => \App\Models\Api5\UtilityElectricityTypes::class,
        'utility-electricity-conditions' => \App\Models\Api5\UtilityElectricityConditions::class,
        'user-special-needs-services' => \App\Models\Api5\UserSpecialNeedsServices::class,
        'user-special-needs-referrals' => \App\Models\Api5\UserSpecialNeedsReferrals::class,
        'user-special-needs-plans' => \App\Models\Api5\UserSpecialNeedsPlans::class,
        'user-special-needs-diagnostics' => \App\Models\Api5\UserSpecialNeedsDiagnostics::class,
        'user-special-needs-devices' => \App\Models\Api5\UserSpecialNeedsDevices::class,
        'user-special-needs-assessments' => \App\Models\Api5\UserSpecialNeedsAssessments::class,
        'user-nationalities' => \App\Models\Api5\UserNationalities::class,
        'user-languages' => \App\Models\Api5\UserLanguages::class,
        'user-insurances' => \App\Models\Api5\UserInsurances::class,
        'user-identities' => \App\Models\Api5\UserIdentities::class,
        'user-healths' => \App\Models\Api5\UserHealths::class,
        'user-health-tests' => \App\Models\Api5\UserHealthTests::class,
        'user-health-medications' => \App\Models\Api5\UserHealthMedications::class,
        'user-health-immunizations' => \App\Models\Api5\UserHealthImmunizations::class,
        'user-health-histories' => \App\Models\Api5\UserHealthHistories::class,
        'user-health-families' => \App\Models\Api5\UserHealthFamilies::class,
        'user-health-consultations' => \App\Models\Api5\UserHealthConsultations::class,
        'user-health-allergies' => \App\Models\Api5\UserHealthAllergies::class,
        'user-employments' => \App\Models\Api5\UserEmployments::class,
        'user-demographics' => \App\Models\Api5\UserDemographics::class,
        'user-contacts' => \App\Models\Api5\UserContacts::class,
        'user-comments' => \App\Models\Api5\UserComments::class,
        'user-body-masses' => \App\Models\Api5\UserBodyMasses::class,
        'user-bank-accounts' => \App\Models\Api5\UserBankAccounts::class,
        'user-awards' => \App\Models\Api5\UserAwards::class,
        'user-attachments-roles' => \App\Models\Api5\UserAttachmentsRoles::class,
        'user-attachments' => \App\Models\Api5\UserAttachments::class,
        'user-activities' => \App\Models\Api5\UserActivities::class,
        'trip-types' => \App\Models\Api5\TripTypes::class,
        'transport-statuses' => \App\Models\Api5\TransportStatuses::class,
        'transport-features' => \App\Models\Api5\TransportFeatures::class,
        'transfer-logs' => \App\Models\Api5\TransferLogs::class,
        'training-specialisations' => \App\Models\Api5\TrainingSpecialisations::class,
        'training-sessions-trainees' => \App\Models\Api5\TrainingSessionsTrainees::class,
        'training-sessions' => \App\Models\Api5\TrainingSessions::class,
        'training-session-trainers' => \App\Models\Api5\TrainingSessionTrainers::class,
        'training-session-trainee-results' => \App\Models\Api5\TrainingSessionTraineeResults::class,
        'training-session-results' => \App\Models\Api5\TrainingSessionResults::class,
        'training-session-evaluators' => \App\Models\Api5\TrainingSessionEvaluators::class,
        'training-result-types' => \App\Models\Api5\TrainingResultTypes::class,
        'training-requirements' => \App\Models\Api5\TrainingRequirements::class,
        'training-providers' => \App\Models\Api5\TrainingProviders::class,
        'training-priorities' => \App\Models\Api5\TrainingPriorities::class,
        'training-need-sub-standards' => \App\Models\Api5\TrainingNeedSubStandards::class,
        'training-need-standards' => \App\Models\Api5\TrainingNeedStandards::class,
        'training-need-competencies' => \App\Models\Api5\TrainingNeedCompetencies::class,
        'training-need-categories' => \App\Models\Api5\TrainingNeedCategories::class,
        'training-mode-deliveries' => \App\Models\Api5\TrainingModeDeliveries::class,
        'training-levels' => \App\Models\Api5\TrainingLevels::class,
        'training-field-of-studies' => \App\Models\Api5\TrainingFieldOfStudies::class,
        'training-courses-target-populations' => \App\Models\Api5\TrainingCoursesTargetPopulations::class,
        'training-courses-specialisations' => \App\Models\Api5\TrainingCoursesSpecialisations::class,
        'training-courses-result-types' => \App\Models\Api5\TrainingCoursesResultTypes::class,
        'training-courses-providers' => \App\Models\Api5\TrainingCoursesProviders::class,
        'training-courses-prerequisites' => \App\Models\Api5\TrainingCoursesPrerequisites::class,
        'training-courses' => \App\Models\Api5\TrainingCourses::class,
        'training-course-types' => \App\Models\Api5\TrainingCourseTypes::class,
        'training-course-categories' => \App\Models\Api5\TrainingCourseCategories::class,
        'themes' => \App\Models\Api5\Themes::class,
        'textbooks' => \App\Models\Api5\Textbooks::class,
        'textbook-statuses' => \App\Models\Api5\TextbookStatuses::class,
        'textbook-dimensions' => \App\Models\Api5\TextbookDimensions::class,
        'textbook-conditions' => \App\Models\Api5\TextbookConditions::class,
        'system-updates' => \App\Models\Api5\SystemUpdates::class,
        'system-processes' => \App\Models\Api5\SystemProcesses::class,
        'system-patches' => \App\Models\Api5\SystemPatches::class,
        'system-errors' => \App\Models\Api5\SystemErrors::class,
        'system-authentications' => \App\Models\Api5\SystemAuthentications::class,
        'survey-table-rows' => \App\Models\Api5\SurveyTableRows::class,
        'survey-table-columns' => \App\Models\Api5\SurveyTableColumns::class,
        'survey-statuses' => \App\Models\Api5\SurveyStatuses::class,
        'survey-status-periods' => \App\Models\Api5\SurveyStatusPeriods::class,
        'survey-rules' => \App\Models\Api5\SurveyRules::class,
        'survey-responses' => \App\Models\Api5\SurveyResponses::class,
        'survey-questions' => \App\Models\Api5\SurveyQuestions::class,
        'survey-question-choices' => \App\Models\Api5\SurveyQuestionChoices::class,
        'survey-forms-questions' => \App\Models\Api5\SurveyFormsQuestions::class,
        'survey-forms-filters' => \App\Models\Api5\SurveyFormsFilters::class,
        'survey-forms' => \App\Models\Api5\SurveyForms::class,
        'survey-filter-institution-types' => \App\Models\Api5\SurveyFilterInstitutionTypes::class,
        'survey-filter-institution-providers' => \App\Models\Api5\SurveyFilterInstitutionProviders::class,
        'survey-filter-areas' => \App\Models\Api5\SurveyFilterAreas::class,
        'summary-student-attendances' => \App\Models\Api5\SummaryStudentAttendances::class,
        'summary-student-assessments' => \App\Models\Api5\SummaryStudentAssessments::class,
        'summary-programme-sector-specialization-genders' => \App\Models\Api5\SummaryProgrammeSectorSpecializationGenders::class,
        'summary-programme-sector-qualification-genders' => \App\Models\Api5\SummaryProgrammeSectorQualificationGenders::class,
        'summary-programme-sector-genders' => \App\Models\Api5\SummaryProgrammeSectorGenders::class,
        'summary-isced-sectors' => \App\Models\Api5\SummaryIscedSectors::class,
        'summary-institutions' => \App\Models\Api5\SummaryInstitutions::class,
        'summary-institution-student-subject-results' => \App\Models\Api5\SummaryInstitutionStudentSubjectResults::class,
        'summary-institution-student-absences' => \App\Models\Api5\SummaryInstitutionStudentAbsences::class,
        'summary-institution-room-types' => \App\Models\Api5\SummaryInstitutionRoomTypes::class,
        'summary-institution-nationalities' => \App\Models\Api5\SummaryInstitutionNationalities::class,
        'summary-institution-grades' => \App\Models\Api5\SummaryInstitutionGrades::class,
        'summary-institution-grade-nationalities' => \App\Models\Api5\SummaryInstitutionGradeNationalities::class,
        'summary-grade-status-genders' => \App\Models\Api5\SummaryGradeStatusGenders::class,
        'summary-grade-gender-ages' => \App\Models\Api5\SummaryGradeGenderAges::class,
        'summary-assessment-item-results' => \App\Models\Api5\SummaryAssessmentItemResults::class,
        'summary-area-provider-grade-subject-results' => \App\Models\Api5\SummaryAreaProviderGradeSubjectResults::class,
        'summary-area-institution-grade-attendances' => \App\Models\Api5\SummaryAreaInstitutionGradeAttendances::class,
        'student-withdraw-reasons' => \App\Models\Api5\StudentWithdrawReasons::class,
        'student-visit-types' => \App\Models\Api5\StudentVisitTypes::class,
        'student-visit-purpose-types' => \App\Models\Api5\StudentVisitPurposeTypes::class,
        'student-transfer-reasons' => \App\Models\Api5\StudentTransferReasons::class,
        'student-statuses' => \App\Models\Api5\StudentStatuses::class,
        'student-status-updates' => \App\Models\Api5\StudentStatusUpdates::class,
        'student-risks-criterias' => \App\Models\Api5\StudentRisksCriterias::class,
        'student-report-cards' => \App\Models\Api5\StudentReportCards::class,
        'student-report-card-processes' => \App\Models\Api5\StudentReportCardProcesses::class,
        'student-report-card-email-processes' => \App\Models\Api5\StudentReportCardEmailProcesses::class,
        'student-profile-templates' => \App\Models\Api5\StudentProfileTemplates::class,
        'student-profile-security-roles' => \App\Models\Api5\StudentProfileSecurityRoles::class,
        'student-meal-marked-records' => \App\Models\Api5\StudentMealMarkedRecords::class,
        'student-mark-type-statuses' => \App\Models\Api5\StudentMarkTypeStatuses::class,
        'student-mark-type-status-grades' => \App\Models\Api5\StudentMarkTypeStatusGrades::class,
        'student-guardians' => \App\Models\Api5\StudentGuardians::class,
        'student-fees' => \App\Models\Api5\StudentFees::class,
        'student-extracurriculars' => \App\Models\Api5\StudentExtracurriculars::class,
        'student-custom-table-rows' => \App\Models\Api5\StudentCustomTableRows::class,
        'student-custom-table-columns' => \App\Models\Api5\StudentCustomTableColumns::class,
        'student-custom-table-cells' => \App\Models\Api5\StudentCustomTableCells::class,
        'student-custom-forms-fields' => \App\Models\Api5\StudentCustomFormsFields::class,
        'student-custom-forms' => \App\Models\Api5\StudentCustomForms::class,
        'student-custom-filters' => \App\Models\Api5\StudentCustomFilters::class,
        'student-custom-fields' => \App\Models\Api5\StudentCustomFields::class,
        'student-custom-field-values' => \App\Models\Api5\StudentCustomFieldValues::class,
        'student-custom-field-options' => \App\Models\Api5\StudentCustomFieldOptions::class,
        'student-behaviours' => \App\Models\Api5\StudentBehaviours::class,
        'student-behaviour-classifications' => \App\Models\Api5\StudentBehaviourClassifications::class,
        'student-behaviour-categories' => \App\Models\Api5\StudentBehaviourCategories::class,
        'student-behaviour-attachments' => \App\Models\Api5\StudentBehaviourAttachments::class,
        'student-attendance-types' => \App\Models\Api5\StudentAttendanceTypes::class,
        'student-attendance-per-day-periods' => \App\Models\Api5\StudentAttendancePerDayPeriods::class,
        'student-attendance-marked-records' => \App\Models\Api5\StudentAttendanceMarkedRecords::class,
        'student-attendance-mark-types' => \App\Models\Api5\StudentAttendanceMarkTypes::class,
        'student-attachment-types' => \App\Models\Api5\StudentAttachmentTypes::class,
        'student-admission-custom-field-values' => \App\Models\Api5\StudentAdmissionCustomFieldValues::class,
        'student-absence-reasons' => \App\Models\Api5\StudentAbsenceReasons::class,
        'staff-types' => \App\Models\Api5\StaffTypes::class,
        'staff-trainings' => \App\Models\Api5\StaffTrainings::class,
        'staff-training-self-study-results' => \App\Models\Api5\StaffTrainingSelfStudyResults::class,
        'staff-training-self-study-attachments' => \App\Models\Api5\StaffTrainingSelfStudyAttachments::class,
        'staff-training-self-studies' => \App\Models\Api5\StaffTrainingSelfStudies::class,
        'staff-training-needs' => \App\Models\Api5\StaffTrainingNeeds::class,
        'staff-training-categories' => \App\Models\Api5\StaffTrainingCategories::class,
        'staff-training-applications' => \App\Models\Api5\StaffTrainingApplications::class,
        'staff-statuses' => \App\Models\Api5\StaffStatuses::class,
        'staff-salary-transactions' => \App\Models\Api5\StaffSalaryTransactions::class,
        'staff-salaries' => \App\Models\Api5\StaffSalaries::class,
        'staff-report-cards' => \App\Models\Api5\StaffReportCards::class,
        'staff-report-card-processes' => \App\Models\Api5\StaffReportCardProcesses::class,
        'staff-report-card-email-processes' => \App\Models\Api5\StaffReportCardEmailProcesses::class,
        'staff-qualifications-subjects' => \App\Models\Api5\StaffQualificationsSubjects::class,
        'staff-qualifications-specialisations' => \App\Models\Api5\StaffQualificationsSpecialisations::class,
        'staff-qualifications' => \App\Models\Api5\StaffQualifications::class,
        'staff-profile-templates' => \App\Models\Api5\StaffProfileTemplates::class,
        'staff-position-titles-grades' => \App\Models\Api5\StaffPositionTitlesGrades::class,
        'staff-position-titles' => \App\Models\Api5\StaffPositionTitles::class,
        'staff-position-grades' => \App\Models\Api5\StaffPositionGrades::class,
        'staff-position-categories' => \App\Models\Api5\StaffPositionCategories::class,
        'staff-payslips' => \App\Models\Api5\StaffPayslips::class,
        'staff-memberships' => \App\Models\Api5\StaffMemberships::class,
        'staff-licenses-classifications' => \App\Models\Api5\StaffLicensesClassifications::class,
        'staff-licenses' => \App\Models\Api5\StaffLicenses::class,
        'staff-leave-types' => \App\Models\Api5\StaffLeaveTypes::class,
        'staff-extracurriculars' => \App\Models\Api5\StaffExtracurriculars::class,
        'staff-employment-statuses' => \App\Models\Api5\StaffEmploymentStatuses::class,
        'staff-duties' => \App\Models\Api5\StaffDuties::class,
        'staff-custom-table-rows' => \App\Models\Api5\StaffCustomTableRows::class,
        'staff-custom-table-columns' => \App\Models\Api5\StaffCustomTableColumns::class,
        'staff-custom-table-cells' => \App\Models\Api5\StaffCustomTableCells::class,
        'staff-custom-forms-fields' => \App\Models\Api5\StaffCustomFormsFields::class,
        'staff-custom-forms' => \App\Models\Api5\StaffCustomForms::class,
        'staff-custom-fields' => \App\Models\Api5\StaffCustomFields::class,
        'staff-custom-field-values' => \App\Models\Api5\StaffCustomFieldValues::class,
        'staff-custom-field-options' => \App\Models\Api5\StaffCustomFieldOptions::class,
        'staff-change-types' => \App\Models\Api5\StaffChangeTypes::class,
        'staff-behaviours' => \App\Models\Api5\StaffBehaviours::class,
        'staff-behaviour-categories' => \App\Models\Api5\StaffBehaviourCategories::class,
        'staff-behaviour-attachments' => \App\Models\Api5\StaffBehaviourAttachments::class,
        'staff-attachment-types' => \App\Models\Api5\StaffAttachmentTypes::class,
        'special-needs-service-types' => \App\Models\Api5\SpecialNeedsServiceTypes::class,
        'special-needs-service-classification' => \App\Models\Api5\SpecialNeedsServiceClassification::class,
        'special-needs-referrer-types' => \App\Models\Api5\SpecialNeedsReferrerTypes::class,
        'special-needs-plan-types' => \App\Models\Api5\SpecialNeedsPlanTypes::class,
        'special-needs-diagnostics-types' => \App\Models\Api5\SpecialNeedsDiagnosticsTypes::class,
        'special-needs-diagnostics-degree' => \App\Models\Api5\SpecialNeedsDiagnosticsDegree::class,
        'special-needs-device-types' => \App\Models\Api5\SpecialNeedsDeviceTypes::class,
        'special-need-types' => \App\Models\Api5\SpecialNeedTypes::class,
        'special-need-difficulties' => \App\Models\Api5\SpecialNeedDifficulties::class,
        'single-logout' => \App\Models\Api5\SingleLogout::class,
        'shift-options' => \App\Models\Api5\ShiftOptions::class,
        'security-users' => \App\Models\Api5\SecurityUsers::class,
        'security-user-sessions' => \App\Models\Api5\SecurityUserSessions::class,
        'security-user-password-requests' => \App\Models\Api5\SecurityUserPasswordRequests::class,
        'security-user-logins' => \App\Models\Api5\SecurityUserLogins::class,
        'security-user-codes' => \App\Models\Api5\SecurityUserCodes::class,
        'security-roles' => \App\Models\Api5\SecurityRoles::class,
        'security-role-functions' => \App\Models\Api5\SecurityRoleFunctions::class,
        'security-rest-sessions' => \App\Models\Api5\SecurityRestSessions::class,
        'security-groups' => \App\Models\Api5\SecurityGroups::class,
        'security-group-users' => \App\Models\Api5\SecurityGroupUsers::class,
        'security-group-institutions' => \App\Models\Api5\SecurityGroupInstitutions::class,
        'security-group-areas' => \App\Models\Api5\SecurityGroupAreas::class,
        'security-functions' => \App\Models\Api5\SecurityFunctions::class,
        'scholarships-scholarship-attachment-types' => \App\Models\Api5\ScholarshipsScholarshipAttachmentTypes::class,
        'scholarships-field-of-studies' => \App\Models\Api5\ScholarshipsFieldOfStudies::class,
        'scholarships' => \App\Models\Api5\Scholarships::class,
        'scholarship-semesters' => \App\Models\Api5\ScholarshipSemesters::class,
        'scholarship-recipients' => \App\Models\Api5\ScholarshipRecipients::class,
        'scholarship-recipient-payment-structures' => \App\Models\Api5\ScholarshipRecipientPaymentStructures::class,
        'scholarship-recipient-payment-structure-estimates' => \App\Models\Api5\ScholarshipRecipientPaymentStructureEstimates::class,
        'scholarship-recipient-disbursements' => \App\Models\Api5\ScholarshipRecipientDisbursements::class,
        'scholarship-recipient-collections' => \App\Models\Api5\ScholarshipRecipientCollections::class,
        'scholarship-recipient-activity-statuses' => \App\Models\Api5\ScholarshipRecipientActivityStatuses::class,
        'scholarship-recipient-activities' => \App\Models\Api5\ScholarshipRecipientActivities::class,
        'scholarship-recipient-academic-standings' => \App\Models\Api5\ScholarshipRecipientAcademicStandings::class,
        'scholarship-payment-frequencies' => \App\Models\Api5\ScholarshipPaymentFrequencies::class,
        'scholarship-loans' => \App\Models\Api5\ScholarshipLoans::class,
        'scholarship-institution-choice-types' => \App\Models\Api5\ScholarshipInstitutionChoiceTypes::class,
        'scholarship-institution-choice-statuses' => \App\Models\Api5\ScholarshipInstitutionChoiceStatuses::class,
        'scholarship-funding-sources' => \App\Models\Api5\ScholarshipFundingSources::class,
        'scholarship-financial-assistances' => \App\Models\Api5\ScholarshipFinancialAssistances::class,
        'scholarship-financial-assistance-types' => \App\Models\Api5\ScholarshipFinancialAssistanceTypes::class,
        'scholarship-disbursement-categories' => \App\Models\Api5\ScholarshipDisbursementCategories::class,
        'scholarship-attachment-types' => \App\Models\Api5\ScholarshipAttachmentTypes::class,
        'scholarship-applications' => \App\Models\Api5\ScholarshipApplications::class,
        'scholarship-application-institution-choices' => \App\Models\Api5\ScholarshipApplicationInstitutionChoices::class,
        'scholarship-application-attachments' => \App\Models\Api5\ScholarshipApplicationAttachments::class,
        'salary-deduction-types' => \App\Models\Api5\SalaryDeductionTypes::class,
        'salary-addition-types' => \App\Models\Api5\SalaryAdditionTypes::class,
        'rubric-templates' => \App\Models\Api5\RubricTemplates::class,
        'rubric-template-options' => \App\Models\Api5\RubricTemplateOptions::class,
        'rubric-statuses' => \App\Models\Api5\RubricStatuses::class,
        'rubric-status-roles' => \App\Models\Api5\RubricStatusRoles::class,
        'rubric-status-programmes' => \App\Models\Api5\RubricStatusProgrammes::class,
        'rubric-status-periods' => \App\Models\Api5\RubricStatusPeriods::class,
        'rubric-sections' => \App\Models\Api5\RubricSections::class,
        'rubric-criterias' => \App\Models\Api5\RubricCriterias::class,
        'rubric-criteria-options' => \App\Models\Api5\RubricCriteriaOptions::class,
        'room-types' => \App\Models\Api5\RoomTypes::class,
        'room-custom-field-values' => \App\Models\Api5\RoomCustomFieldValues::class,
        'risks' => \App\Models\Api5\Risks::class,
        'risk-criterias' => \App\Models\Api5\RiskCriterias::class,
        'reports' => \App\Models\Api5\Reports::class,
        'report-queries' => \App\Models\Api5\ReportQueries::class,
        'report-progress' => \App\Models\Api5\ReportProgress::class,
        'report-cards' => \App\Models\Api5\ReportCards::class,
        'report-card-subjects' => \App\Models\Api5\ReportCardSubjects::class,
        'report-card-processes' => \App\Models\Api5\ReportCardProcesses::class,
        'report-card-excluded-security-roles' => \App\Models\Api5\ReportCardExcludedSecurityRoles::class,
        'report-card-email-processes' => \App\Models\Api5\ReportCardEmailProcesses::class,
        'report-card-comment-codes' => \App\Models\Api5\ReportCardCommentCodes::class,
        'quality-visit-types' => \App\Models\Api5\QualityVisitTypes::class,
        'qualification-titles' => \App\Models\Api5\QualificationTitles::class,
        'qualification-specialisations' => \App\Models\Api5\QualificationSpecialisations::class,
        'qualification-levels' => \App\Models\Api5\QualificationLevels::class,
        'profile-templates' => \App\Models\Api5\ProfileTemplates::class,
        'phinxlog' => \App\Models\Api5\Phinxlog::class,
        'outcome-templates' => \App\Models\Api5\OutcomeTemplates::class,
        'outcome-periods' => \App\Models\Api5\OutcomePeriods::class,
        'outcome-grading-types' => \App\Models\Api5\OutcomeGradingTypes::class,
        'outcome-grading-options' => \App\Models\Api5\OutcomeGradingOptions::class,
        'outcome-criterias' => \App\Models\Api5\OutcomeCriterias::class,
        'openemis-temps' => \App\Models\Api5\OpenemisTemps::class,
        'notices' => \App\Models\Api5\Notices::class,
        'nationalities' => \App\Models\Api5\Nationalities::class,
        'moodle-api-log' => \App\Models\Api5\MoodleApiLog::class,
        'moodle-api-created-users' => \App\Models\Api5\MoodleApiCreatedUsers::class,
        'messaging-security-roles' => \App\Models\Api5\MessagingSecurityRoles::class,
        'messaging' => \App\Models\Api5\Messaging::class,
        'message-recipients' => \App\Models\Api5\MessageRecipients::class,
        'meal-target-types' => \App\Models\Api5\MealTargetTypes::class,
        'meal-status-types' => \App\Models\Api5\MealStatusTypes::class,
        'meal-received' => \App\Models\Api5\MealReceived::class,
        'meal-ratings' => \App\Models\Api5\MealRatings::class,
        'meal-programmes' => \App\Models\Api5\MealProgrammes::class,
        'meal-programme-types' => \App\Models\Api5\MealProgrammeTypes::class,
        'meal-nutritions' => \App\Models\Api5\MealNutritions::class,
        'meal-nutritional-records' => \App\Models\Api5\MealNutritionalRecords::class,
        'meal-institution-programmes' => \App\Models\Api5\MealInstitutionProgrammes::class,
        'meal-implementers' => \App\Models\Api5\MealImplementers::class,
        'meal-food-records' => \App\Models\Api5\MealFoodRecords::class,
        'meal-benefits' => \App\Models\Api5\MealBenefits::class,
        'manuals' => \App\Models\Api5\Manuals::class,
        'locales' => \App\Models\Api5\Locales::class,
        'locale-contents' => \App\Models\Api5\LocaleContents::class,
        'locale-content-translations' => \App\Models\Api5\LocaleContentTranslations::class,
        'license-types' => \App\Models\Api5\LicenseTypes::class,
        'license-classifications' => \App\Models\Api5\LicenseClassifications::class,
        'languages' => \App\Models\Api5\Languages::class,
        'language-proficiencies' => \App\Models\Api5\LanguageProficiencies::class,
        'land-types' => \App\Models\Api5\LandTypes::class,
        'land-custom-field-values' => \App\Models\Api5\LandCustomFieldValues::class,
        'labels' => \App\Models\Api5\Labels::class,
        'insurance-types' => \App\Models\Api5\InsuranceTypes::class,
        'insurance-providers' => \App\Models\Api5\InsuranceProviders::class,
        'institutions' => \App\Models\Api5\Institutions::class,
        //POCOR-9610: start - Expose external registrations integration resources through shared Api5 CRUD routing
        'institution-registrations' => \App\Models\Api5\InstitutionRegistrations::class,
        'institution-accreditations' => \App\Models\Api5\InstitutionAccreditations::class,
        //POCOR-9610: end
        'institution-visit-requests' => \App\Models\Api5\InstitutionVisitRequests::class,
        'institution-units' => \App\Models\Api5\InstitutionUnits::class,
        'institution-types' => \App\Models\Api5\InstitutionTypes::class,
        'institution-trips' => \App\Models\Api5\InstitutionTrips::class,
        'institution-trip-passengers' => \App\Models\Api5\InstitutionTripPassengers::class,
        'institution-trip-days' => \App\Models\Api5\InstitutionTripDays::class,
        'institution-transport-providers' => \App\Models\Api5\InstitutionTransportProviders::class,
        'institution-textbooks' => \App\Models\Api5\InstitutionTextbooks::class,
        'institution-surveys' => \App\Models\Api5\InstitutionSurveys::class,
        'institution-survey-table-cells' => \App\Models\Api5\InstitutionSurveyTableCells::class,
        'institution-survey-answers' => \App\Models\Api5\InstitutionSurveyAnswers::class,
        'institution-subjects-rooms' => \App\Models\Api5\InstitutionSubjectsRooms::class,
        'institution-subjects' => \App\Models\Api5\InstitutionSubjects::class,
        'institution-subject-students' => \App\Models\Api5\InstitutionSubjectStudents::class,
        'institution-subject-staff' => \App\Models\Api5\InstitutionSubjectStaff::class,
        'institution-students-tmp' => \App\Models\Api5\InstitutionStudentsTmp::class,
        'institution-students-report-cards-comments' => \App\Models\Api5\InstitutionStudentsReportCardsComments::class,
        'institution-students-report-cards' => \App\Models\Api5\InstitutionStudentsReportCards::class,
        'institution-students-gpa' => \App\Models\Api5\InstitutionStudentsGpa::class,
        'institution-students' => \App\Models\Api5\InstitutionStudents::class,
        'institution-student-withdraw' => \App\Models\Api5\InstitutionStudentWithdraw::class,
        'institution-student-visits' => \App\Models\Api5\InstitutionStudentVisits::class,
        'institution-student-visit-requests' => \App\Models\Api5\InstitutionStudentVisitRequests::class,
        'institution-student-transfers' => \App\Models\Api5\InstitutionStudentTransfers::class,
        'institution-student-surveys' => \App\Models\Api5\InstitutionStudentSurveys::class,
        'institution-student-survey-table-cells' => \App\Models\Api5\InstitutionStudentSurveyTableCells::class,
        'institution-student-survey-answers' => \App\Models\Api5\InstitutionStudentSurveyAnswers::class,
        'institution-student-risks' => \App\Models\Api5\InstitutionStudentRisks::class,
        'institution-student-enrolment' => \App\Models\Api5\InstitutionStudentEnrolment::class,
        'institution-student-admission' => \App\Models\Api5\InstitutionStudentAdmission::class,
        'institution-student-absences' => \App\Models\Api5\InstitutionStudentAbsences::class,
        'institution-student-absence-details' => \App\Models\Api5\InstitutionStudentAbsenceDetails::class,
        'institution-student-absence-days' => \App\Models\Api5\InstitutionStudentAbsenceDays::class,
        'institution-statuses' => \App\Models\Api5\InstitutionStatuses::class,
        'institution-statistics' => \App\Models\Api5\InstitutionStatistics::class,
        'institution-staff-transfers' => \App\Models\Api5\InstitutionStaffTransfers::class,
        'institution-staff-surveys' => \App\Models\Api5\InstitutionStaffSurveys::class,
        'institution-staff-survey-table-cells' => \App\Models\Api5\InstitutionStaffSurveyTableCells::class,
        'institution-staff-survey-answers' => \App\Models\Api5\InstitutionStaffSurveyAnswers::class,
        'institution-staff-shifts' => \App\Models\Api5\InstitutionStaffShifts::class,
        'institution-staff-releases' => \App\Models\Api5\InstitutionStaffReleases::class,
        'institution-staff-position-profiles' => \App\Models\Api5\InstitutionStaffPositionProfiles::class,
        'institution-staff-leave-archived' => \App\Models\Api5\InstitutionStaffLeaveArchived::class,
        'institution-staff-leave' => \App\Models\Api5\InstitutionStaffLeave::class,
        'institution-staff-duties' => \App\Models\Api5\InstitutionStaffDuties::class,
        'institution-staff-attendances' => \App\Models\Api5\InstitutionStaffAttendances::class,
        'institution-staff-attendance-activities' => \App\Models\Api5\InstitutionStaffAttendanceActivities::class,
        'institution-staff-appraisals' => \App\Models\Api5\InstitutionStaffAppraisals::class,
        'institution-staff' => \App\Models\Api5\InstitutionStaff::class,
        'institution-shifts' => \App\Models\Api5\InstitutionShifts::class,
        'institution-shift-periods' => \App\Models\Api5\InstitutionShiftPeriods::class,
        'institution-sectors' => \App\Models\Api5\InstitutionSectors::class,
        'institution-schedule-timetables' => \App\Models\Api5\InstitutionScheduleTimetables::class,
        'institution-schedule-timetable-customizes' => \App\Models\Api5\InstitutionScheduleTimetableCustomizes::class,
        'institution-schedule-timeslots' => \App\Models\Api5\InstitutionScheduleTimeslots::class,
        'institution-schedule-terms' => \App\Models\Api5\InstitutionScheduleTerms::class,
        'institution-schedule-non-curriculum-lessons' => \App\Models\Api5\InstitutionScheduleNonCurriculumLessons::class,
        'institution-schedule-lessons' => \App\Models\Api5\InstitutionScheduleLessons::class,
        'institution-schedule-lesson-rooms' => \App\Models\Api5\InstitutionScheduleLessonRooms::class,
        'institution-schedule-lesson-details' => \App\Models\Api5\InstitutionScheduleLessonDetails::class,
        'institution-schedule-intervals' => \App\Models\Api5\InstitutionScheduleIntervals::class,
        'institution-schedule-curriculum-lessons' => \App\Models\Api5\InstitutionScheduleCurriculumLessons::class,
        'institution-scanned' => \App\Models\Api5\InstitutionScanned::class,
        'institution-rooms' => \App\Models\Api5\InstitutionRooms::class,
        'institution-risks' => \App\Models\Api5\InstitutionRisks::class,
        'institution-report-cards' => \App\Models\Api5\InstitutionReportCards::class,
        'institution-report-card-processes' => \App\Models\Api5\InstitutionReportCardProcesses::class,
        'institution-repeater-surveys' => \App\Models\Api5\InstitutionRepeaterSurveys::class,
        'institution-repeater-survey-table-cells' => \App\Models\Api5\InstitutionRepeaterSurveyTableCells::class,
        'institution-repeater-survey-answers' => \App\Models\Api5\InstitutionRepeaterSurveyAnswers::class,
        'institution-quality-visits' => \App\Models\Api5\InstitutionQualityVisits::class,
        'institution-quality-rubrics' => \App\Models\Api5\InstitutionQualityRubrics::class,
        'institution-quality-rubric-answers' => \App\Models\Api5\InstitutionQualityRubricAnswers::class,
        'institution-providers' => \App\Models\Api5\InstitutionProviders::class,
        'institution-program-grade-subjects' => \App\Models\Api5\InstitutionProgramGradeSubjects::class,
        'institution-positions' => \App\Models\Api5\InstitutionPositions::class,
        'institution-ownerships' => \App\Models\Api5\InstitutionOwnerships::class,
        'institution-outcome-subject-comments' => \App\Models\Api5\InstitutionOutcomeSubjectComments::class,
        'institution-outcome-results' => \App\Models\Api5\InstitutionOutcomeResults::class,
        'institution-meal-students' => \App\Models\Api5\InstitutionMealStudents::class,
        'institution-meal-programmes' => \App\Models\Api5\InstitutionMealProgrammes::class,
        'institution-localities' => \App\Models\Api5\InstitutionLocalities::class,
        'institution-lands' => \App\Models\Api5\InstitutionLands::class,
        'institution-incomes' => \App\Models\Api5\InstitutionIncomes::class,
        'institution-grades' => \App\Models\Api5\InstitutionGrades::class,
        'institution-genders' => \App\Models\Api5\InstitutionGenders::class,
        'institution-floors' => \App\Models\Api5\InstitutionFloors::class,
        'institution-fees' => \App\Models\Api5\InstitutionFees::class,
        'institution-fee-types' => \App\Models\Api5\InstitutionFeeTypes::class,
        'institution-expenditures' => \App\Models\Api5\InstitutionExpenditures::class,
        'institution-custom-table-rows' => \App\Models\Api5\InstitutionCustomTableRows::class,
        'institution-custom-table-columns' => \App\Models\Api5\InstitutionCustomTableColumns::class,
        'institution-custom-table-cells' => \App\Models\Api5\InstitutionCustomTableCells::class,
        'institution-custom-forms-filters' => \App\Models\Api5\InstitutionCustomFormsFilters::class,
        'institution-custom-forms-fields' => \App\Models\Api5\InstitutionCustomFormsFields::class,
        'institution-custom-forms' => \App\Models\Api5\InstitutionCustomForms::class,
        'institution-custom-fields' => \App\Models\Api5\InstitutionCustomFields::class,
        'institution-custom-field-values' => \App\Models\Api5\InstitutionCustomFieldValues::class,
        'institution-custom-field-options' => \App\Models\Api5\InstitutionCustomFieldOptions::class,
        'institution-curriculars' => \App\Models\Api5\InstitutionCurriculars::class,
        'institution-curricular-students' => \App\Models\Api5\InstitutionCurricularStudents::class,
        'institution-curricular-staff' => \App\Models\Api5\InstitutionCurricularStaff::class,
        'institution-courses' => \App\Models\Api5\InstitutionCourses::class,
        'institution-contact-persons' => \App\Models\Api5\InstitutionContactPersons::class,
        'institution-competency-results' => \App\Models\Api5\InstitutionCompetencyResults::class,
        'institution-competency-period-comments' => \App\Models\Api5\InstitutionCompetencyPeriodComments::class,
        'institution-competency-item-comments' => \App\Models\Api5\InstitutionCompetencyItemComments::class,
        'institution-committees' => \App\Models\Api5\InstitutionCommittees::class,
        'institution-committee-types' => \App\Models\Api5\InstitutionCommitteeTypes::class,
        'institution-committee-meeting' => \App\Models\Api5\InstitutionCommitteeMeeting::class,
        'institution-committee-attachments' => \App\Models\Api5\InstitutionCommitteeAttachments::class,
        'institution-classes-secondary-staff' => \App\Models\Api5\InstitutionClassesSecondaryStaff::class,
        'institution-classes-custom-field-values' => \App\Models\Api5\InstitutionClassesCustomFieldValues::class,
        'institution-classes' => \App\Models\Api5\InstitutionClasses::class,
        'institution-class-subjects' => \App\Models\Api5\InstitutionClassSubjects::class,
        'institution-class-students' => \App\Models\Api5\InstitutionClassStudents::class,
        'institution-class-grades' => \App\Models\Api5\InstitutionClassGrades::class,
        'institution-class-attendance-records' => \App\Models\Api5\InstitutionClassAttendanceRecords::class,
        'institution-cases' => \App\Models\Api5\InstitutionCases::class,
        'institution-case-records' => \App\Models\Api5\InstitutionCaseRecords::class,
        'institution-case-links' => \App\Models\Api5\InstitutionCaseLinks::class,
        'institution-case-comments' => \App\Models\Api5\InstitutionCaseComments::class,
        'institution-buses-transport-features' => \App\Models\Api5\InstitutionBusesTransportFeatures::class,
        'institution-buses' => \App\Models\Api5\InstitutionBuses::class,
        'institution-buildings' => \App\Models\Api5\InstitutionBuildings::class,
        'institution-budgets' => \App\Models\Api5\InstitutionBudgets::class,
        'institution-bank-accounts' => \App\Models\Api5\InstitutionBankAccounts::class,
        'institution-attachments' => \App\Models\Api5\InstitutionAttachments::class,
        'institution-attachment-types' => \App\Models\Api5\InstitutionAttachmentTypes::class,
        'institution-associations' => \App\Models\Api5\InstitutionAssociations::class,
        'institution-association-student' => \App\Models\Api5\InstitutionAssociationStudent::class,
        'institution-association-staff' => \App\Models\Api5\InstitutionAssociationStaff::class,
        'institution-assets' => \App\Models\Api5\InstitutionAssets::class,
        'institution-activities' => \App\Models\Api5\InstitutionActivities::class,
        'inserted-records' => \App\Models\Api5\InsertedRecords::class,
        'infrastructure-wash-waters' => \App\Models\Api5\InfrastructureWashWaters::class,
        'infrastructure-wash-water-types' => \App\Models\Api5\InfrastructureWashWaterTypes::class,
        'infrastructure-wash-water-quantities' => \App\Models\Api5\InfrastructureWashWaterQuantities::class,
        'infrastructure-wash-water-qualities' => \App\Models\Api5\InfrastructureWashWaterQualities::class,
        'infrastructure-wash-water-proximities' => \App\Models\Api5\InfrastructureWashWaterProximities::class,
        'infrastructure-wash-water-functionalities' => \App\Models\Api5\InfrastructureWashWaterFunctionalities::class,
        'infrastructure-wash-water-accessibilities' => \App\Models\Api5\InfrastructureWashWaterAccessibilities::class,
        'infrastructure-wash-wastes' => \App\Models\Api5\InfrastructureWashWastes::class,
        'infrastructure-wash-waste-types' => \App\Models\Api5\InfrastructureWashWasteTypes::class,
        'infrastructure-wash-waste-functionalities' => \App\Models\Api5\InfrastructureWashWasteFunctionalities::class,
        'infrastructure-wash-sewages' => \App\Models\Api5\InfrastructureWashSewages::class,
        'infrastructure-wash-sewage-types' => \App\Models\Api5\InfrastructureWashSewageTypes::class,
        'infrastructure-wash-sewage-functionalities' => \App\Models\Api5\InfrastructureWashSewageFunctionalities::class,
        'infrastructure-wash-sanitations' => \App\Models\Api5\InfrastructureWashSanitations::class,
        'infrastructure-wash-sanitation-uses' => \App\Models\Api5\InfrastructureWashSanitationUses::class,
        'infrastructure-wash-sanitation-types' => \App\Models\Api5\InfrastructureWashSanitationTypes::class,
        'infrastructure-wash-sanitation-quantities' => \App\Models\Api5\InfrastructureWashSanitationQuantities::class,
        'infrastructure-wash-sanitation-qualities' => \App\Models\Api5\InfrastructureWashSanitationQualities::class,
        'infrastructure-wash-sanitation-accessibilities' => \App\Models\Api5\InfrastructureWashSanitationAccessibilities::class,
        'infrastructure-wash-hygienes' => \App\Models\Api5\InfrastructureWashHygienes::class,
        'infrastructure-wash-hygiene-types' => \App\Models\Api5\InfrastructureWashHygieneTypes::class,
        'infrastructure-wash-hygiene-soapash-availabilities' => \App\Models\Api5\InfrastructureWashHygieneSoapashAvailabilities::class,
        'infrastructure-wash-hygiene-quantities' => \App\Models\Api5\InfrastructureWashHygieneQuantities::class,
        'infrastructure-wash-hygiene-educations' => \App\Models\Api5\InfrastructureWashHygieneEducations::class,
        'infrastructure-utility-telephones' => \App\Models\Api5\InfrastructureUtilityTelephones::class,
        'infrastructure-utility-internets' => \App\Models\Api5\InfrastructureUtilityInternets::class,
        'infrastructure-utility-electricities' => \App\Models\Api5\InfrastructureUtilityElectricities::class,
        'infrastructure-statuses' => \App\Models\Api5\InfrastructureStatuses::class,
        'infrastructure-projects-needs' => \App\Models\Api5\InfrastructureProjectsNeeds::class,
        'infrastructure-projects' => \App\Models\Api5\InfrastructureProjects::class,
        'infrastructure-project-funding-sources' => \App\Models\Api5\InfrastructureProjectFundingSources::class,
        'infrastructure-ownerships' => \App\Models\Api5\InfrastructureOwnerships::class,
        'infrastructure-needs' => \App\Models\Api5\InfrastructureNeeds::class,
        'infrastructure-need-types' => \App\Models\Api5\InfrastructureNeedTypes::class,
        'infrastructure-levels' => \App\Models\Api5\InfrastructureLevels::class,
        'infrastructure-custom-forms-filters' => \App\Models\Api5\InfrastructureCustomFormsFilters::class,
        'infrastructure-custom-forms-fields' => \App\Models\Api5\InfrastructureCustomFormsFields::class,
        'infrastructure-custom-forms' => \App\Models\Api5\InfrastructureCustomForms::class,
        'infrastructure-custom-fields' => \App\Models\Api5\InfrastructureCustomFields::class,
        'infrastructure-custom-field-options' => \App\Models\Api5\InfrastructureCustomFieldOptions::class,
        'infrastructure-conditions' => \App\Models\Api5\InfrastructureConditions::class,
        'industries' => \App\Models\Api5\Industries::class,
        'income-types' => \App\Models\Api5\IncomeTypes::class,
        'income-sources' => \App\Models\Api5\IncomeSources::class,
        'import-mapping' => \App\Models\Api5\ImportMapping::class,
        'idp-saml' => \App\Models\Api5\IdpSaml::class,
        'idp-oauth' => \App\Models\Api5\IdpOauth::class,
        'idp-google' => \App\Models\Api5\IdpGoogle::class,
        'identity-types' => \App\Models\Api5\IdentityTypes::class,
        'historical-staff-positions' => \App\Models\Api5\HistoricalStaffPositions::class,
        'historical-staff-leave' => \App\Models\Api5\HistoricalStaffLeave::class,
        'health-test-types' => \App\Models\Api5\HealthTestTypes::class,
        'health-relationships' => \App\Models\Api5\HealthRelationships::class,
        'health-immunization-types' => \App\Models\Api5\HealthImmunizationTypes::class,
        'health-consultation-types' => \App\Models\Api5\HealthConsultationTypes::class,
        'health-conditions' => \App\Models\Api5\HealthConditions::class,
        'health-allergy-types' => \App\Models\Api5\HealthAllergyTypes::class,
        'guidance-types' => \App\Models\Api5\GuidanceTypes::class,
        'guardian-relations' => \App\Models\Api5\GuardianRelations::class,
        'gpa-grading-types' => \App\Models\Api5\GpaGradingTypes::class,
        'gpa-grading-options' => \App\Models\Api5\GpaGradingOptions::class,
        'genders' => \App\Models\Api5\Genders::class,
        'food-types' => \App\Models\Api5\FoodTypes::class,
        'floor-types' => \App\Models\Api5\FloorTypes::class,
        'floor-custom-field-values' => \App\Models\Api5\FloorCustomFieldValues::class,
        'field-types' => \App\Models\Api5\FieldTypes::class,
        'field-options' => \App\Models\Api5\FieldOptions::class,
        'feeders-institutions' => \App\Models\Api5\FeedersInstitutions::class,
        'fee-types' => \App\Models\Api5\FeeTypes::class,
        'extracurricular-types' => \App\Models\Api5\ExtracurricularTypes::class,
        'external-data-source-attributes' => \App\Models\Api5\ExternalDataSourceAttributes::class,
        'expenditure-types' => \App\Models\Api5\ExpenditureTypes::class,
        'examinations' => \App\Models\Api5\Examinations::class,
        'examination-subjects' => \App\Models\Api5\ExaminationSubjects::class,
        'examination-student-subjects' => \App\Models\Api5\ExaminationStudentSubjects::class,
        'examination-student-subject-results' => \App\Models\Api5\ExaminationStudentSubjectResults::class,
        'examination-grading-types' => \App\Models\Api5\ExaminationGradingTypes::class,
        'examination-grading-options' => \App\Models\Api5\ExaminationGradingOptions::class,
        'examination-centres-examinations-subjects-students' => \App\Models\Api5\ExaminationCentresExaminationsSubjectsStudents::class,
        'examination-centres-examinations-subjects' => \App\Models\Api5\ExaminationCentresExaminationsSubjects::class,
        'examination-centres-examinations-students' => \App\Models\Api5\ExaminationCentresExaminationsStudents::class,
        'examination-centres-examinations-invigilators' => \App\Models\Api5\ExaminationCentresExaminationsInvigilators::class,
        'examination-centres-examinations-institutions' => \App\Models\Api5\ExaminationCentresExaminationsInstitutions::class,
        'examination-centres-examinations' => \App\Models\Api5\ExaminationCentresExaminations::class,
        'examination-centres' => \App\Models\Api5\ExaminationCentres::class,
        'examination-centre-special-needs' => \App\Models\Api5\ExaminationCentreSpecialNeeds::class,
        'examination-centre-rooms-examinations-students' => \App\Models\Api5\ExaminationCentreRoomsExaminationsStudents::class,
        'examination-centre-rooms-examinations-invigilators' => \App\Models\Api5\ExaminationCentreRoomsExaminationsInvigilators::class,
        'examination-centre-rooms-examinations' => \App\Models\Api5\ExaminationCentreRoomsExaminations::class,
        'examination-centre-rooms' => \App\Models\Api5\ExaminationCentreRooms::class,
        'employment-status-types' => \App\Models\Api5\EmploymentStatusTypes::class,
        'email-templates' => \App\Models\Api5\EmailTemplates::class,
        'email-processes' => \App\Models\Api5\EmailProcesses::class,
        'email-process-attachments' => \App\Models\Api5\EmailProcessAttachments::class,
        'education-systems' => \App\Models\Api5\EducationSystems::class,
        'education-subjects-field-of-studies' => \App\Models\Api5\EducationSubjectsFieldOfStudies::class,
        'education-subjects' => \App\Models\Api5\EducationSubjects::class,
        'education-stages' => \App\Models\Api5\EducationStages::class,
        'education-programmes-next-programmes' => \App\Models\Api5\EducationProgrammesNextProgrammes::class,
        'education-programmes' => \App\Models\Api5\EducationProgrammes::class,
        'education-programme-orientations' => \App\Models\Api5\EducationProgrammeOrientations::class,
        'education-levels' => \App\Models\Api5\EducationLevels::class,
        'education-level-isced' => \App\Models\Api5\EducationLevelIsced::class,
        'education-grades-subjects' => \App\Models\Api5\EducationGradesSubjects::class,
        'education-grades-gpa' => \App\Models\Api5\EducationGradesGpa::class,
        'education-grades-cumulative-gpa' => \App\Models\Api5\EducationGradesCumulativeGpa::class,
        'education-grades' => \App\Models\Api5\EducationGrades::class,
        'education-field-of-studies' => \App\Models\Api5\EducationFieldOfStudies::class,
        'education-cycles' => \App\Models\Api5\EducationCycles::class,
        'education-certifications' => \App\Models\Api5\EducationCertifications::class,
        'demographic-types' => \App\Models\Api5\DemographicTypes::class,
        'deleted-records' => \App\Models\Api5\DeletedRecords::class,
        'data-management-logs' => \App\Models\Api5\DataManagementLogs::class,
        'data-management-copy' => \App\Models\Api5\DataManagementCopy::class,
        'data-management-connections' => \App\Models\Api5\DataManagementConnections::class,
        'data-dictionary' => \App\Models\Api5\DataDictionary::class,
        'custom-table-rows' => \App\Models\Api5\CustomTableRows::class,
        'custom-table-columns' => \App\Models\Api5\CustomTableColumns::class,
        'custom-table-cells' => \App\Models\Api5\CustomTableCells::class,
        'custom-records' => \App\Models\Api5\CustomRecords::class,
        'custom-modules' => \App\Models\Api5\CustomModules::class,
        'custom-forms-filters' => \App\Models\Api5\CustomFormsFilters::class,
        'custom-forms-fields' => \App\Models\Api5\CustomFormsFields::class,
        'custom-forms' => \App\Models\Api5\CustomForms::class,
        'custom-fields' => \App\Models\Api5\CustomFields::class,
        'custom-field-values' => \App\Models\Api5\CustomFieldValues::class,
        'custom-field-types' => \App\Models\Api5\CustomFieldTypes::class,
        'custom-field-options' => \App\Models\Api5\CustomFieldOptions::class,
        'curricular-types' => \App\Models\Api5\CurricularTypes::class,
        'curricular-positions' => \App\Models\Api5\CurricularPositions::class,
        'countries' => \App\Models\Api5\Countries::class,
        'counsellings' => \App\Models\Api5\Counsellings::class,
        'contact-types' => \App\Models\Api5\ContactTypes::class,
        'contact-options' => \App\Models\Api5\ContactOptions::class,
        'config-product-lists' => \App\Models\Api5\ConfigProductLists::class,
        'config-items' => \App\Models\Api5\ConfigItems::class,
        'config-item-options' => \App\Models\Api5\ConfigItemOptions::class,
        'config-attachments' => \App\Models\Api5\ConfigAttachments::class,
        'competency-templates' => \App\Models\Api5\CompetencyTemplates::class,
        'competency-periods' => \App\Models\Api5\CompetencyPeriods::class,
        'competency-items-periods' => \App\Models\Api5\CompetencyItemsPeriods::class,
        'competency-items' => \App\Models\Api5\CompetencyItems::class,
        'competency-grading-types' => \App\Models\Api5\CompetencyGradingTypes::class,
        'competency-grading-options' => \App\Models\Api5\CompetencyGradingOptions::class,
        'competency-criterias' => \App\Models\Api5\CompetencyCriterias::class,
        'comment-types' => \App\Models\Api5\CommentTypes::class,
        'class-profiles' => \App\Models\Api5\ClassProfiles::class,
        'class-profile-templates' => \App\Models\Api5\ClassProfileTemplates::class,
        'class-profile-processes' => \App\Models\Api5\ClassProfileProcesses::class,
        'case-types' => \App\Models\Api5\CaseTypes::class,
        'case-priorities' => \App\Models\Api5\CasePriorities::class,
        'calendar-types' => \App\Models\Api5\CalendarTypes::class,
        'calendar-events' => \App\Models\Api5\CalendarEvents::class,
        'calendar-event-dates' => \App\Models\Api5\CalendarEventDates::class,
        'bus-types' => \App\Models\Api5\BusTypes::class,
        'building-types' => \App\Models\Api5\BuildingTypes::class,
        'building-custom-field-values' => \App\Models\Api5\BuildingCustomFieldValues::class,
        'budget-types' => \App\Models\Api5\BudgetTypes::class,
        'behaviour-classifications' => \App\Models\Api5\BehaviourClassifications::class,
        'banks' => \App\Models\Api5\Banks::class,
        'bank-branches' => \App\Models\Api5\BankBranches::class,
        'backup-logs' => \App\Models\Api5\BackupLogs::class,
        'authentication-types' => \App\Models\Api5\AuthenticationTypes::class,
        'asset-types' => \App\Models\Api5\AssetTypes::class,
        'asset-statuses' => \App\Models\Api5\AssetStatuses::class,
        'asset-models' => \App\Models\Api5\AssetModels::class,
        'asset-makes' => \App\Models\Api5\AssetMakes::class,
        'asset-conditions' => \App\Models\Api5\AssetConditions::class,
        'assessments' => \App\Models\Api5\Assessments::class,
        'assessment-periods' => \App\Models\Api5\AssessmentPeriods::class,
        'assessment-period-excluded-security-roles' => \App\Models\Api5\AssessmentPeriodExcludedSecurityRoles::class,
        'assessment-items-grading-types' => \App\Models\Api5\AssessmentItemsGradingTypes::class,
        'assessment-items' => \App\Models\Api5\AssessmentItems::class,
        'assessment-item-student-exemptions' => \App\Models\Api5\AssessmentItemStudentExemptions::class,
        'assessment-item-results-archived' => \App\Models\Api5\AssessmentItemResultsArchived::class,
        'assessment-item-results' => \App\Models\Api5\AssessmentItemResults::class,
        'assessment-grading-types' => \App\Models\Api5\AssessmentGradingTypes::class,
        'assessment-grading-options' => \App\Models\Api5\AssessmentGradingOptions::class,
        'area-administrative-levels' => \App\Models\Api5\AreaAdministrativeLevels::class,
        'appraisal-types' => \App\Models\Api5\AppraisalTypes::class,
        'appraisal-text-answers' => \App\Models\Api5\AppraisalTextAnswers::class,
        'appraisal-sliders' => \App\Models\Api5\AppraisalSliders::class,
        'appraisal-slider-answers' => \App\Models\Api5\AppraisalSliderAnswers::class,
        'appraisal-score-answers' => \App\Models\Api5\AppraisalScoreAnswers::class,
        'appraisal-periods-types' => \App\Models\Api5\AppraisalPeriodsTypes::class,
        'appraisal-periods' => \App\Models\Api5\AppraisalPeriods::class,
        'appraisal-numbers' => \App\Models\Api5\AppraisalNumbers::class,
        'appraisal-number-answers' => \App\Models\Api5\AppraisalNumberAnswers::class,
        'appraisal-forms-criterias-scores-links' => \App\Models\Api5\AppraisalFormsCriteriasScoresLinks::class,
        'appraisal-forms-criterias-scores' => \App\Models\Api5\AppraisalFormsCriteriasScores::class,
        'appraisal-forms-criterias' => \App\Models\Api5\AppraisalFormsCriterias::class,
        'appraisal-forms' => \App\Models\Api5\AppraisalForms::class,
        'appraisal-dropdown-options' => \App\Models\Api5\AppraisalDropdownOptions::class,
        'appraisal-dropdown-answers' => \App\Models\Api5\AppraisalDropdownAnswers::class,
        'appraisal-criterias' => \App\Models\Api5\AppraisalCriterias::class,
        'api-securities-scopes' => \App\Models\Api5\ApiSecuritiesScopes::class,
        'api-securities' => \App\Models\Api5\ApiSecurities::class,
        'api-scopes' => \App\Models\Api5\ApiScopes::class,
        'api-credentials-scopes' => \App\Models\Api5\ApiCredentialsScopes::class,
        'api-credentials' => \App\Models\Api5\ApiCredentials::class,
        'api-authorizations' => \App\Models\Api5\ApiAuthorizations::class,
        'alerts-roles' => \App\Models\Api5\AlertsRoles::class,
        'alerts' => \App\Models\Api5\Alerts::class,
        'alert-rules' => \App\Models\Api5\AlertRules::class,
        'alert-logs' => \App\Models\Api5\AlertLogs::class,
        'academic-periods' => \App\Models\Api5\AcademicPeriods::class,
        'academic-period-levels' => \App\Models\Api5\AcademicPeriodLevels::class,
        'absence-types' => \App\Models\Api5\AbsenceTypes::class,
        'area-levels' => \App\Models\Api5\AreaLevels::class,
        'area-administratives' => \App\Models\Api5\AreaAdministratives::class,
        'areas' => \App\Models\Api5\Areas::class,
        //...
        //...
    ];

    //POCOR-9710: probe-detection + password carve-out for security-users.
    protected $probeGuard;

    public function __construct(PermissionService $permissionService, SuperAdminProbeGuard $probeGuard)
    {
        $this->permissionService = $permissionService;
        $this->probeGuard = $probeGuard;
    }
    /**
     * Common entry point for all CRUD operations.
     *
     * This method handles GET, POST, PUT, and DELETE requests based on a catch-all route.
     * It extracts the resource key from the URL, verifies it against allowed resources,
     * and delegates the request to the appropriate handler based on the HTTP method.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $any     The remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    public function common(Request $request, $any): \Illuminate\Http\JsonResponse
    {
        $segments = explode('/', $any);
        // Extract resource key (first segment)
        $resource = array_shift($segments);

        if (!isset($this->allowedResources[$resource])) {
            return response()->json(['error' => 'Invalid resource'], 404);
        }

        $model = $this->allowedResources[$resource];
        $method = $request->method();
        $action = $this->mapHttpMethodToAction($method);

        // Restrict summary resources
        if ($this->isSummaryResource($resource, $method, $segments)) {
            return response()->json(['error' => 'Operation not allowed on summary resources'], 405);
        }

        // Check Permissions // POCOR-8966 start
        $modelName = basename(str_replace('\\', '/', $model));
        if (!$this->permissionService->checkPermission($modelName, $action)) {
            Log::info("User not authorized for {$model}:{$action}"); // POCOR-9085
            return response()->json(['error' => 'Forbidden'], 403);
        }
        // POCOR-8966 end

//        Log::info("User authorized for {$model}:{$action}");

        //POCOR-9710: security-users single-target probe gate. Runs ONLY for
        //the security-users resource and only on GET / PUT / DELETE with a
        //single id segment. If the target id is a super_admin = 1 row and
        //the caller isn't super-admin, log the probe + return 404 — never
        //leak that the row exists. List GETs are not gated here; the
        //HidesSuperAdmins global scope filters the result naturally and the
        //two-count list-probe fingerprint runs after parseFilters in
        //handleGetRequest.
        if ($resource === 'security-users' && !SuperAdminProbeGuard::isSuperAdmin(auth()->user())) {
            if (in_array($action, ['view', 'edit', 'delete'], true)
                && $this->probeGuard->probesSingleSuperAdminTarget($model, $segments)
            ) {
                $this->probeGuard->logProbe($request, auth()->user(), [
                    'resource' => $resource,
                    'action'   => $action,
                    'target'   => $segments[0] ?? null,
                ]);
                return response()->json(['error' => 'Record not found'], 404);
            }
        }

        // Handle the request based on method
        return $this->handleRequestByMethod($request, $model, $segments, $method);

    }

    private function mapHttpMethodToAction($method): string
    {
        return match ($method) {
            'GET'    => 'view',
            'POST'   => 'add',
            'PUT'    => 'edit',
            'DELETE' => 'delete',
            default  => 'unknown',
        };
    }

    private function isSummaryResource($resource, $method, $segments): bool
    {
        if (str_starts_with($resource, 'summary') || str_starts_with($resource, 'data-dictionary')) {
            if (in_array($method, ['PUT', 'DELETE'])) {
                return true;
            }
            if ($method === 'GET' && count($segments) === 1) {
                return true;
            }
        }
        return false;
    }

    private function handleRequestByMethod($request, $model, $segments, $method): \Illuminate\Http\JsonResponse
    {
        return match ($method) {
            'GET'    => $this->handleGetRequest($request, $model, $segments),
            'POST'   => $this->handlePostRequest($request, $model),
            'PUT'    => $this->handlePutRequest($request, $model, $segments),
            'DELETE' => $this->handleDeleteRequest($request, $model, $segments),
            default  => response()->json(['error' => 'Invalid request'], 405),
        };
    }

    protected function getPossibleIdField($model)
    {
        if (is_string($model)) {
            $model = new $model;
        }

        $primaryKey = $model->getKeyName();
        $attributes = $model->getAttributes();

        if (array_key_exists('id', $attributes)) {
            return 'id';
        } elseif (!is_array($primaryKey)) {
            return $primaryKey;
        }

        return null; // Return null if neither condition is met
    }

    // POCOR-8966 start
    /**
     * Handle GET requests to retrieve records.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleGetRequest(Request $request, $model, array $segments)
    {
        try {
            $query = $this->initializeQuery($model);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }

        $pagination = $this->getPaginationParams($request, $segments);
        $filters = $this->parseFilters($request, $segments);
        //POCOR-9660: start - implicit id-list segment (e.g. /resource/4,5,6) → filter
        try {
            $implicitIdFilter = $this->parseImplicitIdFilter($model, $segments);
            if (!empty($implicitIdFilter)) {
                $filters = array_merge($filters, $implicitIdFilter);
            }
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
        //POCOR-9660: end
        //POCOR-9660+9697: surface invalid orderby/filter column as 400 instead of 500
        try {
            $order = $this->parseOrderParams($request, $segments);
            $query = $this->parseSelectParams($request, $segments, $query, $model);
            $query = $this->applyFilters($query, $filters, $model); //POCOR-9697: pass $model for allowlist
            $query = $this->applyOrder($query, $order, $model);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
        $query = $this->applyInstitutionFilter($query, $model);

        //POCOR-9710: list-probe fingerprint for security-users. The
        //HidesSuperAdmins scope filters the response naturally; here we just
        //add the audit log when the caller's filter targets super-admins
        //exclusively (two-count: scope-bypassed total == scope-bypassed
        //super_admin = 1 total, and total > 0).
        $modelClass = is_object($model) ? get_class($model) : $model;
        if ($modelClass === \App\Models\Api5\SecurityUsers::class
            && !SuperAdminProbeGuard::isSuperAdmin(auth()->user())
            && !empty($filters)
        ) {
            $probeQuery = (clone $query)->withoutGlobalScope('hideSuperAdmins');
            if ($this->probeGuard->probesOnlySuperAdmins($probeQuery)) {
                $this->probeGuard->logProbe($request, auth()->user(), [
                    'resource' => 'security-users',
                    'action'   => 'list',
                    'filters'  => $filters,
                ]);
            }
        }

        return $this->paginateResults($query, $pagination['limit'], $pagination['page'], $model, $segments);
    }

    /**
     * Handle POST requests to create a new record.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $model Fully qualified model class.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handlePostRequest(Request $request, $model)
    {
        $data = $request->all();

        //POCOR-9710: Q1 password carve-out. On security-users create, only
        //super-admin callers may set `password`; otherwise strip silently +
        //log. The setPasswordAttribute() mutator still hashes anything that
        //survives — this is defense-in-depth, not the only layer.
        $data = $this->maybeStripPasswordForSecurityUsers($request, $model, $data);

        if ($this->isBatchRequest($data)) {
            return $this->handleBatchCreate($model, $data);
        }

        return $this->handleSingleCreate($model, $data);
    }

    //POCOR-9710: shared helper for the two CRUD paths that mass-assign user
    //input into security_users. Batch payloads are arrays-of-objects, so the
    //strip walks every row.
    private function maybeStripPasswordForSecurityUsers(Request $request, $model, array $data): array
    {
        $modelClass = is_object($model) ? get_class($model) : $model;
        if ($modelClass !== \App\Models\Api5\SecurityUsers::class) {
            return $data;
        }
        $caller = auth()->user();
        if ($this->isBatchRequest($data)) {
            foreach ($data as $i => $row) {
                if (is_array($row)) {
                    $data[$i] = $this->probeGuard->stripPasswordIfNotSuperAdmin($row, $caller, $request);
                }
            }
            return $data;
        }
        return $this->probeGuard->stripPasswordIfNotSuperAdmin($data, $caller, $request);
    }

    /**
     * Handle PUT requests to update an existing record.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handlePutRequest(Request $request, $model, array $segments)
    {
        if (empty($segments)) {
            return $this->errorResponse('Missing identifier for update', 400);
        }

        $record = $this->findRecord($model, $segments);
        if (!$record) {
            return $this->errorResponse('Record not found', 404);
        }

        $data = $request->all();
        //POCOR-9710: Q1 password carve-out — applies symmetrically on update.
        $data = $this->maybeStripPasswordForSecurityUsers($request, $model, $data);
        $current_user_id = auth()->id(); // Assuming you have a way to get the current user ID
        if (is_string($model)) {
            $model = new $model;
        }
        $this->decodeBlobFields($data); // Decode base64 to binary before update

        //POCOR-9697: audit-trail integrity on update — always derive
        //modified_user_id from the JWT (never trust the request body) and
        //silent-strip any client-supplied created_user_id since it is
        //immutable. Logging mirrors the super_admin silent-strip: server-side
        //warning only, never echoed back to the caller (anti-fingerprinting).
        $fillable = $model->getFillable();
        if (in_array('created_user_id', $fillable) && array_key_exists('created_user_id', $data)) {
            Log::warning(
                'POCOR-9697: created_user_id supplied on update — silently stripped (immutable)',
                [
                    'endpoint'       => $request->path(),
                    'method'         => $request->method(),
                    'caller_id'      => $current_user_id,
                    'ip'             => $request->ip(),
                    'supplied_value' => $data['created_user_id'],
                ]
            );
            unset($data['created_user_id']);
        }
        if (in_array('modified_user_id', $fillable)) {
            if (array_key_exists('modified_user_id', $data) && (int) $data['modified_user_id'] !== (int) $current_user_id) {
                Log::warning(
                    'POCOR-9697: modified_user_id forgery attempt — overwritten with JWT user',
                    [
                        'endpoint'       => $request->path(),
                        'method'         => $request->method(),
                        'caller_id'      => $current_user_id,
                        'ip'             => $request->ip(),
                        'supplied_value' => $data['modified_user_id'],
                    ]
                );
            }
            $data['modified_user_id'] = $current_user_id; //POCOR-9697: always from JWT
            if (in_array('modified', $fillable) && !isset($data['modified'])) {
                $data['modified'] = Carbon::now();
            }
        }

        try {
            $record->update($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        return $this->successResponse('Record updated successfully.', $record);
    }

    /**
     * Handle DELETE requests to remove an existing record.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleDeleteRequest(Request $request, $model, array $segments)
    {
        if (empty($segments)) {
            return $this->errorResponse('Missing identifier for delete', 400);
        }

        if ($this->isBulkDeleteRequest($segments)) {
            return $this->handleBulkDelete($model, $segments);
        }

        $record = $this->findRecord($model, $segments);
        if (!$record) {
            return $this->errorResponse('Record not found', 404);
        }

        try {
//            Log::debug('Deleting record', ['model' => $model, 'record' => $record]);
            $record->delete();
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->successResponse('Deleted successfully', [], 204);
    }

    /**
     * Initialize a query for the given model.
     *
     * @param string $model Fully qualified model class.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function initializeQuery($model)
    {
        return $model::query();
    }

    /**
     * Get pagination parameters from the request and segments.
     *
     * This function retrieves pagination parameters (page and limit) from both the query parameters
     * and URL segments. If pagination parameters are found in the URL segments, they are removed
     * from the segments array.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $segments Remaining URL segments.
     * @return array Pagination parameters as an associative array.
     */
    private function getPaginationParams(Request $request, array &$segments)
    {
        $pagination = [
            'page' => $request->input('page', 1),
            'limit' => $request->input('limit', 20),
        ];

        // Check for pagination parameters in URL segments
        for ($i = 0; $i < count($segments); $i += 2) {
            $key = $segments[$i];
            $value = $segments[$i + 1] ?? null;
            if ($value !== null) {
                if ($key === 'page') {
                    $pagination['page'] = (int)$value;
                    // Unset page from segments
                    unset($segments[$i], $segments[$i + 1]);
                    // Reindex the array to maintain proper indexing
                    $segments = array_values($segments);
                    // Decrement $i by 2 to account for the removed elements
                    $i -= 2;
                } elseif ($key === 'limit') {
                    $pagination['limit'] = (int)$value;
                    // Unset limit from segments
                    unset($segments[$i], $segments[$i + 1]);
                    // Reindex the array to maintain proper indexing
                    $segments = array_values($segments);
                    // Decrement $i by 2 to account for the removed elements
                    $i -= 2;
                }
            }
        }

        return $pagination;
    }

    /**
     * Parse filters from the request and segments.
     *
     * This function retrieves filters from both the URL segments and query parameters,
     * excluding specific parameters like order, orderby, _fields, _conditions, page, and limit.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $segments Remaining URL segments.
     * @return array Parsed filters as an associative array.
     */
    private function parseFilters(Request $request, array &$segments)
    {
        $filters = [];
        $excludedParams = ['order',
            'orderby',
            '_fields',
            '_conditions',
            'page',
            'limit',
            '_scope',
            '_fields'];

        // Parse conditions from the query parameter
        $conditionsParam = $request->input('_conditions');
        if ($conditionsParam) {
            $filters = array_merge($filters, $this->parseConditions($conditionsParam));
        }

        // Parse other filters from query parameters
        // POCOR-9633: skip params starting with '_' — reserved for scope-consumed params (e.g., _date, _meal_programmes_id)
        foreach ($request->all() as $key => $value) {
            if (!in_array($key, $excludedParams) && !str_starts_with($key, '_')) {
                $filters[$key] = $this->normalizeGetFilterValue($key, $value); //POCOR-9660: normalize comma-separated id values
            }
        }

        // Parse conditions from the URL segments
        for ($i = 0; $i < count($segments); $i += 2) {
            $key = $segments[$i];
            $value = $segments[$i + 1] ?? null;
            if ($value !== null) {
                if ($key === '_conditions') {
                    // Parse conditions from the URL segment
                    $filters = array_merge($filters, $this->parseConditions($value));
                    // Unset _conditions from segments
                    unset($segments[$i], $segments[$i + 1]);
                    // Reindex the array to maintain proper indexing
                    $segments = array_values($segments);
                    // Decrement $i by 2 to account for the removed elements
                    $i -= 2;
                } else {
                    if (!in_array($key, $excludedParams) && !str_starts_with($key, '_')) { //POCOR-9633: skip _ prefixed scope-consumed params
                        $filters[$key] = $this->normalizeGetFilterValue($key, $value); //POCOR-9660: normalize comma-separated id values
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * Parse conditions from the _conditions parameter.
     *
     * This function parses a string of conditions formatted as key-value pairs separated by semicolons.
     * Each condition can specify different types of comparisons, such as equals, greater than, less than,
     * greater than or equal to, less than or equal to, and between.
     *
     * Examples of _conditions strings:
     * - "field:value" -> Equals condition.
     * - "field:>value" -> Greater than condition.
     * - "field:<value" -> Less than condition.
     * - "field:>=value" -> Greater than or equal to condition.
     * - "field:<=value" -> Less than or equal to condition.
     * - "field:BETWEENvalue1,value2" -> Between condition.
     *
     * @param string $conditionsParam The conditions parameter string.
     * @return array Parsed conditions as an associative array.
     */
    private function parseConditions($conditionsParam)
    {
        $conditions = [];
        $pairs = explode(';', trim($conditionsParam, '[]'));

        foreach ($pairs as $pair) {
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                //POCOR-9660: start - support IN(a,b,c) and comma-separated id lists in _conditions
                $field = trim($parts[0]);
                $value = trim($parts[1]);

                $inValues = $this->parseInConditionValues($value);
                if ($inValues !== null) {
                    $conditions[$field] = $inValues;
                } else {
                    $conditions[$field] = $this->normalizeGetFilterValue($field, $value);
                }
                //POCOR-9660: end
            }
        }

        return $conditions;
    }

    //POCOR-9660: start - new helper - normalize GET filter values (split id-lists into arrays)
    /**
     * Normalize GET filter values before they are applied to the query.
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function normalizeGetFilterValue($field, $value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if (($field === 'id' || str_ends_with($field, '_id')) && strpos($value, ',') !== false) { //POCOR-9660: match bare 'id' and any '*_id' field for multi-value filter
            $values = $this->splitAndTrimValues($value);

            if (!empty($values) && count(array_filter($values, [$this, 'isValidIdentifier'])) === count($values)) {
                return $values;
            }
        }

        return $value;
    }
    //POCOR-9660: end

    //POCOR-9660: start - new helper - split a CSV string, trim each item, and drop empty entries
    private function splitAndTrimValues(string $csv): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $csv)), fn ($v) => $v !== ''));
    }
    //POCOR-9660: end

    //POCOR-9660: start - new helper - parse IN(a,b,c) operator from _conditions value
    /**
     * Parse IN operator values from _conditions.
     *
     * @param string $value
     * @return array|null
     */
    private function parseInConditionValues($value)
    {
        if (!str_starts_with($value, 'IN')) {
            return null;
        }

        $listValue = trim(substr($value, 2), '()');
        if ($listValue === '' || preg_match('/^[A-Za-z]/', $listValue)) {
            return null;
        }

        $rawValues = array_map('trim', explode(',', $listValue));
        $values = $this->splitAndTrimValues($listValue);

        return count($values) > 1 && count($values) === count($rawValues) ? $values : null;
    }
    //POCOR-9660: end

    //POCOR-9660: start - new helper - parse implicit id-list segment "/resource/4,5,6"
    /**
     * Parse an implicit GET identifier segment such as "4,5,6".
     *
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return array
     */
    private function parseImplicitIdFilter($model, array &$segments)
    {
        if (count($segments) !== 1 || strpos($segments[0], ',') === false) {
            return [];
        }

        $idField = $this->getPossibleIdField($model);
        if (!$idField) {
            return [];
        }

        $rawValues = array_map('trim', explode(',', $segments[0]));
        $values = $this->splitAndTrimValues($segments[0]);

        if (count($values) < 2 || count($values) !== count($rawValues) || count(array_filter($values, [$this, 'isValidIdentifier'])) !== count($values)) {
            throw new \InvalidArgumentException('Invalid identifier list.');
        }

        $segments = [];

        return [$idField => $values];
    }
    //POCOR-9660: end

    /**
     * Parse select parameters from the request and segments.
     *
     * This function retrieves select parameters (_fields and _scope) from both the query parameters
     * and URL segments. It modifies the query to select specific fields and apply scopes.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $segments Remaining URL segments.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $model Fully qualified model class.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function parseSelectParams(Request $request, array &$segments, $query, $model)
    {
        $fields = $request->query('_fields');
        $scopeParam = $request->query('_scope');
//        Log::info("segments before:" . print_r($segments, true));
        // Process _fields from query parameters or segments
        if (!$fields) {
            for ($i = 0; $i < count($segments); $i += 2) {
                $key = $segments[$i];
                $value = $segments[$i + 1] ?? null;
                if ($value !== null && $key === '_fields') {
                    $fields = $value;
                    // Unset _fields from segments
                    unset($segments[$i], $segments[$i + 1]);
                    // Reindex the array to maintain proper indexing
                    $segments = array_values($segments);
                    // Decrement $i by 2 to account for the removed elements
                    $i -= 2;
                    break;
                }
            }
        }

        if ($fields) {
            $fieldsArray = array_map('trim', explode(',', $fields));
            if (!empty($fieldsArray)) {
                try {
                    $query->select($fieldsArray);
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 422);
                }
            }
        }

        // Process _scope from query parameters or segments
        if (!$scopeParam) {
            for ($i = 0; $i < count($segments); $i += 2) {
                $key = $segments[$i];
                $value = $segments[$i + 1] ?? null;
                if ($value !== null && $key === '_scope') {
                    $scopeParam = $value;
                    // Unset _scope from segments
                    unset($segments[$i], $segments[$i + 1]);
                    // Reindex the array to maintain proper indexing
                    $segments = array_values($segments);
                    // Decrement $i by 2 to account for the removed elements
                    $i -= 2;
                    break;
                }
            }
        }

        if ($scopeParam) {
            // Allow multiple scopes separated by commas.
            $scopes = array_map('trim', explode(',', $scopeParam));
            if (is_string($model)) {
                $model = new $model;
            }
            foreach ($scopes as $scopeMethod) {
                $methodName = 'scope' . ucfirst($scopeMethod);
                // Use the provided model class to check for the scope.
                if (method_exists($model, $methodName)) {
                    try {
                        $query->{$scopeMethod}();
                    } catch (\Exception $e) {
                        return response()->json(['error' => $e->getMessage()], 404);
                    }
                }
            }
        }
//        Log::info("segments after:" . print_r($segments, true));

        return $query;
    }


    /**
     * POCOR-9697: Compute the per-model set of columns a v5 read request may
     * filter on. Returns `$fillable` minus `$hidden`, so the read-side filter
     * surface is exactly the writable, non-hidden columns.
     *
     * Rationale: `$hidden` strips sensitive columns from the response body,
     * but without this allowlist `_conditions=hiddenfield:value` still executes
     * as a WHERE clause, allowing membership inference (super_admin) or a
     * binary-search oracle (password hash). Mirroring the published write
     * surface keeps the rule internally consistent — a client cannot read-
     * filter on any column they could not already POST/PUT to.
     *
     * @param mixed $model Fully qualified model class name or instance.
     * @return array<int,string> List of column names allowed in _conditions/filters.
     */
    private function getQueryableColumns($model): array
    {
        if (is_string($model)) {
            $model = new $model;
        }
        $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];
        $hidden   = method_exists($model, 'getHidden')   ? $model->getHidden()   : [];
        // Belt-and-braces diff in case a future model author lists a column in both arrays.
        return array_values(array_diff($fillable, $hidden));
    }

    //POCOR-9697: fields that are NEVER queryable across any model. Probes against these
    //are escalated to a higher log severity for SOC alerting (membership inference on
    //super_admin, binary-search oracle on password). Add credential-bearing columns here
    //as new models are introduced.
    private const SENSITIVE_FILTER_FIELDS = ['super_admin', 'password', 'remember_token', 'password_hash'];

    /**
     * Apply filters to the query.
     *
     * POCOR-9697: `$model` is now required so we can reject any filter key that
     * is not in `getQueryableColumns()`. Dropped keys cause a 400 with a generic
     * message — we never echo the field name back to the caller — and the
     * server logs the dropped keys for audit. Probes against
     * SENSITIVE_FILTER_FIELDS are logged at warning level so SOC tooling can alert.
     *
     * Rationale for 400 (vs the original silent-drop):
     *  - Silent-drop quietly swallowed legitimate typos (e.g. `studnet_id` instead
     *    of `student_id`), returning the wrong dataset and creating a long-lived
     *    DX trap.
     *  - 400 + generic message preserves the anti-fingerprinting property: the
     *    response body is identical for `super_admin`, `password`, or `hubabuba`,
     *    so the attacker cannot A/B test field existence.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param mixed $model Fully qualified model class name or instance (POCOR-9697).
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \InvalidArgumentException When one or more filter keys are not in the allowlist.
     *                                   Caller (handleGetRequest) translates this to HTTP 400.
     */
    private function applyFilters($query, array $filters, $model = null)
    {
        //POCOR-9697: build allowlist once per call; empty = no allowlist enforced (defensive default for legacy callers)
        $allowed = $model ? $this->getQueryableColumns($model) : [];
        $dropped = [];
        $sensitiveDropped = [];

        foreach ($filters as $field => $value) {
            //POCOR-9697: collect non-allowlist keys; we'll log and 400 after the loop so a
            //single request lists every offender once (anti-fingerprinting + DX clarity).
            if (!empty($allowed) && !in_array($field, $allowed, true)) {
                $dropped[] = $field;
                if (in_array($field, self::SENSITIVE_FILTER_FIELDS, true)) {
                    $sensitiveDropped[] = $field;
                }
                continue;
            }
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif (strpos($value, '>=') === 0) {
                $query->where($field, '>=', substr($value, 2));
            } elseif (strpos($value, '<=') === 0) {
                $query->where($field, '<=', substr($value, 2));
            } elseif (strpos($value, '>') === 0) {
                $query->where($field, '>', substr($value, 1));
            } elseif (strpos($value, '<') === 0) {
                $query->where($field, '<', substr($value, 1));
            } elseif (strpos($value, 'BETWEEN') === 0) {
                $range = explode(',', substr($value, 8));
                if (count($range) === 2) {
                    $query->whereBetween($field, $range);
                }
            } elseif (substr($field, -3) === '_id' && $this->isValidIdentifier($value)) {
                // If field ends with '_id' and value is numeric, use exact match
                $query->where($field, '=', $value);
            } elseif ($field === 'id'  && $this->isValidIdentifier($value)) {
                // If field is 'code', use exact match
                $query->where($field, '=', $value);
            } elseif ($field === 'code') {
                // If field is 'code', use exact match
                $query->where($field, '=', $value);
            } else {
                // Default to 'like' for other fields
               if (strpos($value, '*') !== false) {
                    $query->where($field, 'like', str_replace('*', '%', $value));
                } else {
                    $query->where($field, '=', $value);
                }
            }
        }

        //POCOR-9697: surface dropped filter keys as a single 400 with a generic
        //message. The field names appear in server logs only — never in the response.
        if (!empty($dropped)) {
            $modelName = is_string($model) ? $model : (is_object($model) ? get_class($model) : null);

            if (!empty($sensitiveDropped)) {
                //Probe against credential/escalation columns — surface for SOC alerting.
                Log::warning('POCOR-9697: SENSITIVE filter probe — possible enumeration attempt', [
                    'model' => $modelName,
                    'sensitive_fields' => $sensitiveDropped,
                    'all_dropped' => $dropped,
                    'ip' => request()->ip(),
                ]);
            } else {
                //Plain non-allowlist field (typo, deprecated column, ill-informed client).
                Log::info('POCOR-9697: filter dropped — field not queryable', [
                    'model' => $modelName,
                    'fields' => $dropped,
                ]);
            }

            throw new \InvalidArgumentException(
                'Request contains filter field names that are not present in this resource. Check API documentation for the fields of this resource.'
            );
        }

        return $query;
    }

    /**
     * Apply order to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $order
     * @param string $model Fully qualified model class.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyOrder($query, array $order, $model)
    {
        if (is_string($model)) {
            $model = new $model;
        }

        //POCOR-9660: start - validate orderby column + direction against schema-derived allowlist
        $allowedColumns = $this->getAllowedOrderColumns($model);
        $hasOrderParam = !empty($order['columns']);

        if (!$hasOrderParam && in_array('order', $allowedColumns, true)) {
            // Default ordering by 'order' field if no order params are provided
            $query->orderBy('order', 'asc');
        } else {
            foreach ($order['columns'] as $index => $column) {
                if (!in_array($column, $allowedColumns, true)) {
                    throw new \InvalidArgumentException("Invalid orderby column: {$column}");
                }

                $direction = $order['directions'][$index] ?? 'asc'; //POCOR-9660: already lowercased by parseOrderParams
                if (!in_array($direction, ['asc', 'desc'], true)) {
                    throw new \InvalidArgumentException("Invalid order direction: {$direction}");
                }

                $query->orderBy($column, $direction);
            }
        }
        //POCOR-9660: end

        return $query;
    }

    /**
     * Parse order parameters from the request and segments.
     *
     * This function retrieves order parameters (orderby and order) from both the query parameters
     * and URL segments. If order parameters are found in the URL segments, they are removed
     * from the segments array.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $segments Remaining URL segments.
     * @return array Order parameters as an associative array.
     */
    private function parseOrderParams(Request $request, array &$segments)
    {
        $orderColumns = [];
        $orderDirections = [];

        // Parse order parameters from query parameters
        $orderByParam = $request->input('orderby');
        $orderParam = $request->input('order');

        if ($orderByParam) {
            $orderColumns = array_merge($orderColumns, explode(',', $orderByParam));
        }

        if ($orderParam) {
            $orderDirections = array_merge($orderDirections, explode(',', $orderParam));
        }

        // Parse order parameters from URL segments
        for ($i = 0; $i < count($segments); $i += 2) {
            $key = $segments[$i];
            $value = $segments[$i + 1] ?? null;
            if ($value !== null) {
                if ($key === 'orderby') {
                    $orderColumns = array_merge($orderColumns, explode(',', $value));
                    // Unset orderby from segments
                    unset($segments[$i], $segments[$i + 1]);
                    // Reindex the array to maintain proper indexing
                    $segments = array_values($segments);
                    // Decrement $i by 2 to account for the removed elements
                    $i -= 2;
                } elseif ($key === 'order') {
                    $orderDirections = array_merge($orderDirections, explode(',', $value));
                    // Unset order from segments
                    unset($segments[$i], $segments[$i + 1]);
                    // Reindex the array to maintain proper indexing
                    $segments = array_values($segments);
                    // Decrement $i by 2 to account for the removed elements
                    $i -= 2;
                }
            }
        }

        //POCOR-9660: start - trim/normalize order columns and directions before validation
        $orderColumns = array_values(array_filter(array_map('trim', $orderColumns), function ($column) {
            return $column !== '';
        }));
        $orderDirections = array_values(array_filter(array_map(function ($direction) {
            return strtolower(trim($direction));
        }, $orderDirections), function ($direction) {
            return $direction !== '';
        }));
        //POCOR-9660: end

        return [
            'columns' => $orderColumns,
            'directions' => $orderDirections,
        ];
    }

    //POCOR-9660: start - new helper - cached schema-derived allowlist of sortable columns
    /**
     * Resolve the list of columns that may be used for ordering.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    private function getAllowedOrderColumns($model)
    {
        $cacheKey = 'crud_api_sortable_columns:v3:' . get_class($model) . ':' . $model->getTable(); //POCOR-9660: v3 excludes hidden columns

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($model) {
            try {
                $schemaColumns = $model->getConnection()
                    ->getSchemaBuilder()
                    ->getColumnListing($model->getTable());
            } catch (\Exception $e) {
                $schemaColumns = $model->getFillable();
            }

            $hidden = $model->getHidden(); //POCOR-9660: never reveal hidden columns (e.g. password) via sort order

            return array_values(array_filter(array_unique($schemaColumns), function ($column) use ($hidden) {
                return is_string($column)
                    && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)
                    && !in_array($column, $hidden, true); //POCOR-9660: exclude $hidden fields — sort order exposes values even when field is not returned
            }));
        });
    }
    //POCOR-9660: end


    /**
     * Apply institution filter to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $model Fully qualified model class.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyInstitutionFilter($query, $model)
    {
        if (is_string($model)) {
            $model = new $model;
        }
        $institutionIds = $this->permissionService->getInstitutionIds();
        $allowAllInstitutions = $this->permissionService->getAllowAllInstitutions();
        if (!$allowAllInstitutions) {
            if (in_array('institution_id', $model->getFillable())) {
                $query->whereIn('institution_id', $institutionIds);
            } elseif (in_array('institution_class_id', $model->getFillable())) {
                $query->join('institution_classes', 'institution_classes.id', '=', $model->getTable() . '.institution_class_id')
                    ->whereIn('institution_classes.institution_id', $institutionIds);
            }
        }

        return $query;
    }

    private function paginateResults($query, $limit, $page, $model, $segments)
    {
        if (count($segments) === 1 && $this->isValidIdentifier($segments[0])) {
            $record = $this->findRecord($model, $segments);
            if (!$record) {
                return $this->errorResponse('Record not found', 404);
            }

            // POCOR-9461: Apply afterFetchResults to single record if available
            if (method_exists($model, 'afterFetchResults')) {
                $record = $model::afterFetchResults(collect([$record]))->first();
            }

            return $this->successResponse('Record retrieved successfully.', $record);
        }

        // Proceed with pagination if no single valid identifier is found
        try {
            $results = $query->paginate($limit, ['*'], 'page', $page);

            // POCOR-9461: Apply afterFetchResults to the collection inside paginator
            if (method_exists($model, 'afterFetchResults')) {
                $updated = $model::afterFetchResults($results->getCollection());
                $results->setCollection($updated);
            }

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }

        return $this->successResponse('Data retrieved successfully.', $results);
    }



    /**
     * Check if the request is a batch create request.
     *
     * @param array $data
     * @return bool
     */
    private function isBatchRequest(array $data)
    {
        return isset($data[0]) && is_array($data);
    }

    /**
     * Handle batch create requests.
     *
     * @param string $model Fully qualified model class.
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleBatchCreate($model, array $data)
    {
        \DB::beginTransaction();
        try {
            $current_user_id = auth()->id(); // Assuming you have a way to get the current user ID
            if (is_string($model)) {
                $model = new $model;
            }
            $records = [];
            $fillable = $model->getFillable();
            foreach ($data as $recordData) {
                $this->decodeBlobFields($recordData); //  Decode base64 to binary
                //POCOR-9697: audit-trail integrity on batch create — always
                //derive created_user_id / modified_user_id from JWT; log any
                //forgery attempt without echoing the field name back.
                $recordData = $this->stampAuditFieldsOnCreate(
                    $recordData,
                    $fillable,
                    $current_user_id
                );
                $records[] = $model::create($recordData);
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }

        return $this->successResponse('Records created successfully.', $records, 201);
    }

    /**
     * Handle single create requests.
     *
     * @param string $model Fully qualified model class.
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleSingleCreate($model, array $data)
    {

        $current_user_id = auth()->id(); // Assuming you have a way to get the current user ID
        if (is_string($model)) {
            $model = new $model;
        }
        $this->decodeBlobFields($data); //  Decode base64 to binary

        //POCOR-9697: audit-trail integrity on single create — always derive
        //created_user_id and modified_user_id from the JWT user. Any
        //client-supplied value is silent-stripped with a server-side log so
        //ops can grep forgery attempts; the response never echoes the field
        //name (anti-fingerprinting).
        $data = $this->stampAuditFieldsOnCreate(
            $data,
            $model->getFillable(),
            $current_user_id
        );
        try {
            $record = $model::create($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        return $this->successResponse('Record created successfully.', $record, 201);
    }

    /**
     * POCOR-9697: stamp created_user_id / modified_user_id from the JWT user
     * on every create path, log any client-supplied value that differs, and
     * silent-strip the offending key before persist. Keeps the v5 audit trail
     * tamper-proof in the same way v4 already enforces it via UserRepository.
     *
     * @param array $data         Raw payload from the request.
     * @param array $fillable     Target model's $fillable allowlist.
     * @param int|null $currentUserId  Authenticated JWT user id.
     * @return array              Payload with audit fields overwritten.
     */
    private function stampAuditFieldsOnCreate(array $data, array $fillable, $currentUserId)
    {
        $request = request();
        foreach (['created_user_id', 'modified_user_id'] as $auditField) {
            if (!in_array($auditField, $fillable, true)) {
                continue;
            }
            if (array_key_exists($auditField, $data)
                && (int) $data[$auditField] !== (int) $currentUserId
            ) {
                Log::warning(
                    'POCOR-9697: ' . $auditField . ' forgery attempt — overwritten with JWT user',
                    [
                        'endpoint'       => $request ? $request->path() : null,
                        'method'         => $request ? $request->method() : null,
                        'caller_id'      => $currentUserId,
                        'ip'             => $request ? $request->ip() : null,
                        'supplied_value' => $data[$auditField],
                    ]
                );
            }
            $data[$auditField] = $currentUserId; //POCOR-9697: always from JWT
        }
        //POCOR-9697: keep the existing created / modified timestamp behaviour.
        if (in_array('created', $fillable, true) && !isset($data['created'])) {
            $data['created'] = Carbon::now();
        }
        if (in_array('modified', $fillable, true) && !isset($data['modified'])) {
            $data['modified'] = Carbon::now();
        }
        return $data;
    }

    /**
     * Check if the request is a bulk delete request.
     *
     * @param array $segments Remaining URL segments.
     * @return bool
     */
    private function isBulkDeleteRequest(array $segments)
    {
        return count($segments) === 1 && strpos($segments[0], ',') !== false;
    }

    /**
     * Handle bulk delete requests.
     *
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleBulkDelete($model, array $segments)
    {
        $ids = explode(',', $segments[0]);
        \DB::beginTransaction();
        try {
            $possibleIdField = $this->getPossibleIdField($model);
            foreach ($ids as $id) {
                if ($this->isValidIdentifier($id)) {
                    $model->where($possibleIdField, $id)->delete();
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->successResponse('Records deleted successfully.', [], 204);
    }

    /**
     * Find a record based on the segments.
     *
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    private function findRecord($model, array $segments)
    {
        if (count($segments) === 1 && $this->isValidIdentifier($segments[0])) {
            $possibleIdField = $this->getPossibleIdField($model);
            return $model::where($possibleIdField, $segments[0])->first();
        }

        $conditions = [];
        for ($i = 0; $i < count($segments); $i += 2) {
            $conditions[$segments[$i]] = $segments[$i + 1];
        }

        return $model::where($conditions)->first();
    }

    /**
     * Return a JSON error response.
     *
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function errorResponse($message, $statusCode)
    {
        return response()->json(['error' => $message], $statusCode);
    }

    // POCOR-9135 start
    /**
     * Return a JSON success response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function successResponse($message, $data, $statusCode = 200)
    {
        $safeData = $this->sanitizeForJson($data);
//        Log::info("Response data: " . print_r($safeData, true)); // POCOR-9135 // POCOR-9352
        return response()->json(['message' => $message, 'data' => $safeData], $statusCode);
    }

    private function sanitizeForJson($data)
    {
        // Handle Laravel model objects
        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray(); // Extract attributes to array
        }

        // Handle paginator and collection
        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator || $data instanceof \Illuminate\Support\Collection) {
            $data = $data->toArray();
        }

        if (is_array($data)) {
            // Is it a list or an associative array?
            if (array_keys($data) === range(0, count($data) - 1)) {
                foreach ($data as $index => $item) {
                    $data[$index] = $this->sanitizeForJson($item);
                }
            } else {
                foreach ($data as $key => $value) {
                    $data[$key] = $this->sanitizeForJson($value);
                }
            }
        } elseif (is_string($data)) {
            if (!mb_check_encoding($data, 'UTF-8')) {
                return base64_encode($data);
            }
        }

        return $data;
    }
// POCOR-9135 end
// POCOR-8966 end


    /**
     * Determine if a given string is a valid record identifier.
     *
     * Valid identifiers are numeric or match a UUID pattern.
     *
     * @param string $id
     * @return bool
     */
    protected function isValidIdentifier($id)
    {
        if (is_numeric($id)) {
            return true;
        }
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $id)) {
            return true;
        }
        if (preg_match('/^(?:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}|[A-Za-z0-9]{15})$/', $id)) {
            return true;
        }
        return false;
    }

    private function decodeBlobFields(array &$data)
    {

        foreach ($data as $key => $value) {
            // Detect *_content fields (e.g. document_content, photo_content)
            if (is_string($key) && str_ends_with($key, '_content') && is_string($value)) {
                $value = preg_replace('/^data:[^;]+;base64,/', '', $value);
                if (strlen($value) > 40 && base64_decode($value, true) !== false) {
                    if (preg_match('/^[A-Za-z0-9+\/=]+$/', $value) && strlen($value) % 4 === 0) {
                        $decoded = base64_decode($value, true);
                        if ($decoded !== false) {
                            $data[$key] = $decoded;
                        }
                    }
                }
//                if (strlen($value) > 40 && base64_decode($value, true) === false) {
//                    $data[$key] = "";
//                }
            }
        }
    }
}
