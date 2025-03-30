<?php

namespace App\Http\Controllers\BaseApi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use App\Services\PermissionService;

class CrudApiController extends Controller
{
    protected $allowedResources = [
        'staff-leave-entitlements' => \App\Models\StaffLeaveEntitlements::class,
        'staff-leave-policies' => \App\Models\StaffLeavePolicies::class,
        'staff-leave-policy-types' => \App\Models\StaffLeavePolicyTypes::class,
        'alerts-logs' => \App\Models\AlertsLogs::class,
        'workflows-filters' => \App\Models\WorkflowsFilters::class,
        'workflows' => \App\Models\Workflows::class,
        'workflow-transitions' => \App\Models\WorkflowTransitions::class,
        'workflow-steps-roles' => \App\Models\WorkflowStepsRoles::class,
        'workflow-steps-params' => \App\Models\WorkflowStepsParams::class,
        'workflow-steps' => \App\Models\WorkflowSteps::class,
        'workflow-statuses-steps' => \App\Models\WorkflowStatusesSteps::class,
        'workflow-statuses' => \App\Models\WorkflowStatuses::class,
        'workflow-rules' => \App\Models\WorkflowRules::class,
        'workflow-rule-events' => \App\Models\WorkflowRuleEvents::class,
        'workflow-models' => \App\Models\WorkflowModels::class,
        'workflow-comments' => \App\Models\WorkflowComments::class,
        'workflow-actions' => \App\Models\WorkflowActions::class,
        'webhooks' => \App\Models\Webhooks::class,
        'webhook-events' => \App\Models\WebhookEvents::class,
        'utility-telephone-types' => \App\Models\UtilityTelephoneTypes::class,
        'utility-telephone-conditions' => \App\Models\UtilityTelephoneConditions::class,
        'utility-internet-types' => \App\Models\UtilityInternetTypes::class,
        'utility-internet-conditions' => \App\Models\UtilityInternetConditions::class,
        'utility-internet-bandwidths' => \App\Models\UtilityInternetBandwidths::class,
        'utility-electricity-types' => \App\Models\UtilityElectricityTypes::class,
        'utility-electricity-conditions' => \App\Models\UtilityElectricityConditions::class,
        'user-special-needs-services' => \App\Models\UserSpecialNeedsServices::class,
        'user-special-needs-referrals' => \App\Models\UserSpecialNeedsReferrals::class,
        'user-special-needs-plans' => \App\Models\UserSpecialNeedsPlans::class,
        'user-special-needs-diagnostics' => \App\Models\UserSpecialNeedsDiagnostics::class,
        'user-special-needs-devices' => \App\Models\UserSpecialNeedsDevices::class,
        'user-special-needs-assessments' => \App\Models\UserSpecialNeedsAssessments::class,
        'user-nationalities' => \App\Models\UserNationalities::class,
        'user-languages' => \App\Models\UserLanguages::class,
        'user-insurances' => \App\Models\UserInsurances::class,
        'user-identities' => \App\Models\UserIdentities::class,
        'user-healths' => \App\Models\UserHealths::class,
        'user-health-tests' => \App\Models\UserHealthTests::class,
        'user-health-medications' => \App\Models\UserHealthMedications::class,
        'user-health-immunizations' => \App\Models\UserHealthImmunizations::class,
        'user-health-histories' => \App\Models\UserHealthHistories::class,
        'user-health-families' => \App\Models\UserHealthFamilies::class,
        'user-health-consultations' => \App\Models\UserHealthConsultations::class,
        'user-health-allergies' => \App\Models\UserHealthAllergies::class,
        'user-employments' => \App\Models\UserEmployments::class,
        'user-demographics' => \App\Models\UserDemographics::class,
        'user-contacts' => \App\Models\UserContacts::class,
        'user-comments' => \App\Models\UserComments::class,
        'user-body-masses' => \App\Models\UserBodyMasses::class,
        'user-bank-accounts' => \App\Models\UserBankAccounts::class,
        'user-awards' => \App\Models\UserAwards::class,
        'user-attachments-roles' => \App\Models\UserAttachmentsRoles::class,
        'user-attachments' => \App\Models\UserAttachments::class,
        'user-activities' => \App\Models\UserActivities::class,
        'trip-types' => \App\Models\TripTypes::class,
        'transport-statuses' => \App\Models\TransportStatuses::class,
        'transport-features' => \App\Models\TransportFeatures::class,
        'transfer-logs' => \App\Models\TransferLogs::class,
        'training-specialisations' => \App\Models\TrainingSpecialisations::class,
        'training-sessions-trainees' => \App\Models\TrainingSessionsTrainees::class,
        'training-sessions' => \App\Models\TrainingSessions::class,
        'training-session-trainers' => \App\Models\TrainingSessionTrainers::class,
        'training-session-trainee-results' => \App\Models\TrainingSessionTraineeResults::class,
        'training-session-results' => \App\Models\TrainingSessionResults::class,
        'training-session-evaluators' => \App\Models\TrainingSessionEvaluators::class,
        'training-result-types' => \App\Models\TrainingResultTypes::class,
        'training-requirements' => \App\Models\TrainingRequirements::class,
        'training-providers' => \App\Models\TrainingProviders::class,
        'training-priorities' => \App\Models\TrainingPriorities::class,
        'training-need-sub-standards' => \App\Models\TrainingNeedSubStandards::class,
        'training-need-standards' => \App\Models\TrainingNeedStandards::class,
        'training-need-competencies' => \App\Models\TrainingNeedCompetencies::class,
        'training-need-categories' => \App\Models\TrainingNeedCategories::class,
        'training-mode-deliveries' => \App\Models\TrainingModeDeliveries::class,
        'training-levels' => \App\Models\TrainingLevels::class,
        'training-field-of-studies' => \App\Models\TrainingFieldOfStudies::class,
        'training-courses-target-populations' => \App\Models\TrainingCoursesTargetPopulations::class,
        'training-courses-specialisations' => \App\Models\TrainingCoursesSpecialisations::class,
        'training-courses-result-types' => \App\Models\TrainingCoursesResultTypes::class,
        'training-courses-providers' => \App\Models\TrainingCoursesProviders::class,
        'training-courses-prerequisites' => \App\Models\TrainingCoursesPrerequisites::class,
        'training-courses' => \App\Models\TrainingCourses::class,
        'training-course-types' => \App\Models\TrainingCourseTypes::class,
        'training-course-categories' => \App\Models\TrainingCourseCategories::class,
        'themes' => \App\Models\Themes::class,
        'textbooks' => \App\Models\Textbooks::class,
        'textbook-statuses' => \App\Models\TextbookStatuses::class,
        'textbook-dimensions' => \App\Models\TextbookDimensions::class,
        'textbook-conditions' => \App\Models\TextbookConditions::class,
        'system-updates' => \App\Models\SystemUpdates::class,
        'system-processes' => \App\Models\SystemProcesses::class,
        'system-patches' => \App\Models\SystemPatches::class,
        'system-errors' => \App\Models\SystemErrors::class,
        'system-authentications' => \App\Models\SystemAuthentications::class,
        'survey-table-rows' => \App\Models\SurveyTableRows::class,
        'survey-table-columns' => \App\Models\SurveyTableColumns::class,
        'survey-statuses' => \App\Models\SurveyStatuses::class,
        'survey-status-periods' => \App\Models\SurveyStatusPeriods::class,
        'survey-rules' => \App\Models\SurveyRules::class,
        'survey-responses' => \App\Models\SurveyResponses::class,
        'survey-questions' => \App\Models\SurveyQuestions::class,
        'survey-question-choices' => \App\Models\SurveyQuestionChoices::class,
        'survey-forms-questions' => \App\Models\SurveyFormsQuestions::class,
        'survey-forms-filters' => \App\Models\SurveyFormsFilters::class,
        'survey-forms' => \App\Models\SurveyForms::class,
        'survey-filter-institution-types' => \App\Models\SurveyFilterInstitutionTypes::class,
        'survey-filter-institution-providers' => \App\Models\SurveyFilterInstitutionProviders::class,
        'survey-filter-areas' => \App\Models\SurveyFilterAreas::class,
        'summary-student-attendances' => \App\Models\SummaryStudentAttendances::class,
        'summary-student-assessments' => \App\Models\SummaryStudentAssessments::class,
        'summary-programme-sector-specialization-genders' => \App\Models\SummaryProgrammeSectorSpecializationGenders::class,
        'summary-programme-sector-qualification-genders' => \App\Models\SummaryProgrammeSectorQualificationGenders::class,
        'summary-programme-sector-genders' => \App\Models\SummaryProgrammeSectorGenders::class,
        'summary-isced-sectors' => \App\Models\SummaryIscedSectors::class,
        'summary-institutions' => \App\Models\SummaryInstitutions::class,
        'summary-institution-student-subject-results' => \App\Models\SummaryInstitutionStudentSubjectResults::class,
        'summary-institution-student-absences' => \App\Models\SummaryInstitutionStudentAbsences::class,
        'summary-institution-room-types' => \App\Models\SummaryInstitutionRoomTypes::class,
        'summary-institution-nationalities' => \App\Models\SummaryInstitutionNationalities::class,
        'summary-institution-grades' => \App\Models\SummaryInstitutionGrades::class,
        'summary-institution-grade-nationalities' => \App\Models\SummaryInstitutionGradeNationalities::class,
        'summary-grade-status-genders' => \App\Models\SummaryGradeStatusGenders::class,
        'summary-grade-gender-ages' => \App\Models\SummaryGradeGenderAges::class,
        'summary-assessment-item-results' => \App\Models\SummaryAssessmentItemResults::class,
        'summary-area-provider-grade-subject-results' => \App\Models\SummaryAreaProviderGradeSubjectResults::class,
        'summary-area-institution-grade-attendances' => \App\Models\SummaryAreaInstitutionGradeAttendances::class,
        'student-withdraw-reasons' => \App\Models\StudentWithdrawReasons::class,
        'student-visit-types' => \App\Models\StudentVisitTypes::class,
        'student-visit-purpose-types' => \App\Models\StudentVisitPurposeTypes::class,
        'student-transfer-reasons' => \App\Models\StudentTransferReasons::class,
        'student-statuses' => \App\Models\StudentStatuses::class,
        'student-status-updates' => \App\Models\StudentStatusUpdates::class,
        'student-risks-criterias' => \App\Models\StudentRisksCriterias::class,
        'student-report-cards' => \App\Models\StudentReportCards::class,
        'student-report-card-processes' => \App\Models\StudentReportCardProcesses::class,
        'student-report-card-email-processes' => \App\Models\StudentReportCardEmailProcesses::class,
        'student-profile-templates' => \App\Models\StudentProfileTemplates::class,
        'student-profile-security-roles' => \App\Models\StudentProfileSecurityRoles::class,
        'student-meal-marked-records' => \App\Models\StudentMealMarkedRecords::class,
        'student-mark-type-statuses' => \App\Models\StudentMarkTypeStatuses::class,
        'student-mark-type-status-grades' => \App\Models\StudentMarkTypeStatusGrades::class,
        'student-guardians' => \App\Models\StudentGuardians::class,
        'student-fees' => \App\Models\StudentFees::class,
        'student-extracurriculars' => \App\Models\StudentExtracurriculars::class,
        'student-custom-table-rows' => \App\Models\StudentCustomTableRows::class,
        'student-custom-table-columns' => \App\Models\StudentCustomTableColumns::class,
        'student-custom-table-cells' => \App\Models\StudentCustomTableCells::class,
        'student-custom-forms-fields' => \App\Models\StudentCustomFormsFields::class,
        'student-custom-forms' => \App\Models\StudentCustomForms::class,
        'student-custom-filters' => \App\Models\StudentCustomFilters::class,
        'student-custom-fields' => \App\Models\StudentCustomFields::class,
        'student-custom-field-values' => \App\Models\StudentCustomFieldValues::class,
        'student-custom-field-options' => \App\Models\StudentCustomFieldOptions::class,
        'student-behaviours' => \App\Models\StudentBehaviours::class,
        'student-behaviour-classifications' => \App\Models\StudentBehaviourClassifications::class,
        'student-behaviour-categories' => \App\Models\StudentBehaviourCategories::class,
        'student-behaviour-attachments' => \App\Models\StudentBehaviourAttachments::class,
        'student-attendance-types' => \App\Models\StudentAttendanceTypes::class,
        'student-attendance-per-day-periods' => \App\Models\StudentAttendancePerDayPeriods::class,
        'student-attendance-marked-records' => \App\Models\StudentAttendanceMarkedRecords::class,
        'student-attendance-mark-types' => \App\Models\StudentAttendanceMarkTypes::class,
        'student-attachment-types' => \App\Models\StudentAttachmentTypes::class,
        'student-admission-custom-field-values' => \App\Models\StudentAdmissionCustomFieldValues::class,
        'student-absence-reasons' => \App\Models\StudentAbsenceReasons::class,
        'staff-types' => \App\Models\StaffTypes::class,
        'staff-trainings' => \App\Models\StaffTrainings::class,
        'staff-training-self-study-results' => \App\Models\StaffTrainingSelfStudyResults::class,
        'staff-training-self-study-attachments' => \App\Models\StaffTrainingSelfStudyAttachments::class,
        'staff-training-self-studies' => \App\Models\StaffTrainingSelfStudies::class,
        'staff-training-needs' => \App\Models\StaffTrainingNeeds::class,
        'staff-training-categories' => \App\Models\StaffTrainingCategories::class,
        'staff-training-applications' => \App\Models\StaffTrainingApplications::class,
        'staff-statuses' => \App\Models\StaffStatuses::class,
        'staff-salary-transactions' => \App\Models\StaffSalaryTransactions::class,
        'staff-salaries' => \App\Models\StaffSalaries::class,
        'staff-report-cards' => \App\Models\StaffReportCards::class,
        'staff-report-card-processes' => \App\Models\StaffReportCardProcesses::class,
        'staff-report-card-email-processes' => \App\Models\StaffReportCardEmailProcesses::class,
        'staff-qualifications-subjects' => \App\Models\StaffQualificationsSubjects::class,
        'staff-qualifications-specialisations' => \App\Models\StaffQualificationsSpecialisations::class,
        'staff-qualifications' => \App\Models\StaffQualifications::class,
        'staff-profile-templates' => \App\Models\StaffProfileTemplates::class,
        'staff-position-titles-grades' => \App\Models\StaffPositionTitlesGrades::class,
        'staff-position-titles' => \App\Models\StaffPositionTitles::class,
        'staff-position-grades' => \App\Models\StaffPositionGrades::class,
        'staff-position-categories' => \App\Models\StaffPositionCategories::class,
        'staff-payslips' => \App\Models\StaffPayslips::class,
        'staff-memberships' => \App\Models\StaffMemberships::class,
        'staff-licenses-classifications' => \App\Models\StaffLicensesClassifications::class,
        'staff-licenses' => \App\Models\StaffLicenses::class,
        'staff-leave-types' => \App\Models\StaffLeaveTypes::class,
        'staff-extracurriculars' => \App\Models\StaffExtracurriculars::class,
        'staff-employment-statuses' => \App\Models\StaffEmploymentStatuses::class,
        'staff-duties' => \App\Models\StaffDuties::class,
        'staff-custom-table-rows' => \App\Models\StaffCustomTableRows::class,
        'staff-custom-table-columns' => \App\Models\StaffCustomTableColumns::class,
        'staff-custom-table-cells' => \App\Models\StaffCustomTableCells::class,
        'staff-custom-forms-fields' => \App\Models\StaffCustomFormsFields::class,
        'staff-custom-forms' => \App\Models\StaffCustomForms::class,
        'staff-custom-fields' => \App\Models\StaffCustomFields::class,
        'staff-custom-field-values' => \App\Models\StaffCustomFieldValues::class,
        'staff-custom-field-options' => \App\Models\StaffCustomFieldOptions::class,
        'staff-change-types' => \App\Models\StaffChangeTypes::class,
        'staff-behaviours' => \App\Models\StaffBehaviours::class,
        'staff-behaviour-categories' => \App\Models\StaffBehaviourCategories::class,
        'staff-behaviour-attachments' => \App\Models\StaffBehaviourAttachments::class,
        'staff-attachment-types' => \App\Models\StaffAttachmentTypes::class,
        'special-needs-service-types' => \App\Models\SpecialNeedsServiceTypes::class,
        'special-needs-service-classification' => \App\Models\SpecialNeedsServiceClassification::class,
        'special-needs-referrer-types' => \App\Models\SpecialNeedsReferrerTypes::class,
        'special-needs-plan-types' => \App\Models\SpecialNeedsPlanTypes::class,
        'special-needs-diagnostics-types' => \App\Models\SpecialNeedsDiagnosticsTypes::class,
        'special-needs-diagnostics-degree' => \App\Models\SpecialNeedsDiagnosticsDegree::class,
        'special-needs-device-types' => \App\Models\SpecialNeedsDeviceTypes::class,
        'special-need-types' => \App\Models\SpecialNeedTypes::class,
        'special-need-difficulties' => \App\Models\SpecialNeedDifficulties::class,
        'single-logout' => \App\Models\SingleLogout::class,
        'shift-options' => \App\Models\ShiftOptions::class,
        'security-users' => \App\Models\SecurityUsers::class,
        'security-user-sessions' => \App\Models\SecurityUserSessions::class,
        'security-user-password-requests' => \App\Models\SecurityUserPasswordRequests::class,
        'security-user-logins' => \App\Models\SecurityUserLogins::class,
        'security-user-codes' => \App\Models\SecurityUserCodes::class,
        'security-roles' => \App\Models\SecurityRoles::class,
        'security-role-functions' => \App\Models\SecurityRoleFunctions::class,
        'security-rest-sessions' => \App\Models\SecurityRestSessions::class,
        'security-groups' => \App\Models\SecurityGroups::class,
        'security-group-users' => \App\Models\SecurityGroupUsers::class,
        'security-group-institutions' => \App\Models\SecurityGroupInstitutions::class,
        'security-group-areas' => \App\Models\SecurityGroupAreas::class,
        'security-functions' => \App\Models\SecurityFunctions::class,
        'scholarships-scholarship-attachment-types' => \App\Models\ScholarshipsScholarshipAttachmentTypes::class,
        'scholarships-field-of-studies' => \App\Models\ScholarshipsFieldOfStudies::class,
        'scholarships' => \App\Models\Scholarships::class,
        'scholarship-semesters' => \App\Models\ScholarshipSemesters::class,
        'scholarship-recipients' => \App\Models\ScholarshipRecipients::class,
        'scholarship-recipient-payment-structures' => \App\Models\ScholarshipRecipientPaymentStructures::class,
        'scholarship-recipient-payment-structure-estimates' => \App\Models\ScholarshipRecipientPaymentStructureEstimates::class,
        'scholarship-recipient-disbursements' => \App\Models\ScholarshipRecipientDisbursements::class,
        'scholarship-recipient-collections' => \App\Models\ScholarshipRecipientCollections::class,
        'scholarship-recipient-activity-statuses' => \App\Models\ScholarshipRecipientActivityStatuses::class,
        'scholarship-recipient-activities' => \App\Models\ScholarshipRecipientActivities::class,
        'scholarship-recipient-academic-standings' => \App\Models\ScholarshipRecipientAcademicStandings::class,
        'scholarship-payment-frequencies' => \App\Models\ScholarshipPaymentFrequencies::class,
        'scholarship-loans' => \App\Models\ScholarshipLoans::class,
        'scholarship-institution-choice-types' => \App\Models\ScholarshipInstitutionChoiceTypes::class,
        'scholarship-institution-choice-statuses' => \App\Models\ScholarshipInstitutionChoiceStatuses::class,
        'scholarship-funding-sources' => \App\Models\ScholarshipFundingSources::class,
        'scholarship-financial-assistances' => \App\Models\ScholarshipFinancialAssistances::class,
        'scholarship-financial-assistance-types' => \App\Models\ScholarshipFinancialAssistanceTypes::class,
        'scholarship-disbursement-categories' => \App\Models\ScholarshipDisbursementCategories::class,
        'scholarship-attachment-types' => \App\Models\ScholarshipAttachmentTypes::class,
        'scholarship-applications' => \App\Models\ScholarshipApplications::class,
        'scholarship-application-institution-choices' => \App\Models\ScholarshipApplicationInstitutionChoices::class,
        'scholarship-application-attachments' => \App\Models\ScholarshipApplicationAttachments::class,
        'salary-deduction-types' => \App\Models\SalaryDeductionTypes::class,
        'salary-addition-types' => \App\Models\SalaryAdditionTypes::class,
        'rubric-templates' => \App\Models\RubricTemplates::class,
        'rubric-template-options' => \App\Models\RubricTemplateOptions::class,
        'rubric-statuses' => \App\Models\RubricStatuses::class,
        'rubric-status-roles' => \App\Models\RubricStatusRoles::class,
        'rubric-status-programmes' => \App\Models\RubricStatusProgrammes::class,
        'rubric-status-periods' => \App\Models\RubricStatusPeriods::class,
        'rubric-sections' => \App\Models\RubricSections::class,
        'rubric-criterias' => \App\Models\RubricCriterias::class,
        'rubric-criteria-options' => \App\Models\RubricCriteriaOptions::class,
        'room-types' => \App\Models\RoomTypes::class,
        'room-custom-field-values' => \App\Models\RoomCustomFieldValues::class,
        'risks' => \App\Models\Risks::class,
        'risk-criterias' => \App\Models\RiskCriterias::class,
        'reports' => \App\Models\Reports::class,
        'report-queries' => \App\Models\ReportQueries::class,
        'report-progress' => \App\Models\ReportProgress::class,
        'report-cards' => \App\Models\ReportCards::class,
        'report-card-subjects' => \App\Models\ReportCardSubjects::class,
        'report-card-processes' => \App\Models\ReportCardProcesses::class,
        'report-card-excluded-security-roles' => \App\Models\ReportCardExcludedSecurityRoles::class,
        'report-card-email-processes' => \App\Models\ReportCardEmailProcesses::class,
        'report-card-comment-codes' => \App\Models\ReportCardCommentCodes::class,
        'quality-visit-types' => \App\Models\QualityVisitTypes::class,
        'qualification-titles' => \App\Models\QualificationTitles::class,
        'qualification-specialisations' => \App\Models\QualificationSpecialisations::class,
        'qualification-levels' => \App\Models\QualificationLevels::class,
        'profile-templates' => \App\Models\ProfileTemplates::class,
        'phinxlog' => \App\Models\Phinxlog::class,
        'outcome-templates' => \App\Models\OutcomeTemplates::class,
        'outcome-periods' => \App\Models\OutcomePeriods::class,
        'outcome-grading-types' => \App\Models\OutcomeGradingTypes::class,
        'outcome-grading-options' => \App\Models\OutcomeGradingOptions::class,
        'outcome-criterias' => \App\Models\OutcomeCriterias::class,
        'openemis-temps' => \App\Models\OpenemisTemps::class,
        'notices' => \App\Models\Notices::class,
        'nationalities' => \App\Models\Nationalities::class,
        'moodle-api-log' => \App\Models\MoodleApiLog::class,
        'moodle-api-created-users' => \App\Models\MoodleApiCreatedUsers::class,
        'messaging-security-roles' => \App\Models\MessagingSecurityRoles::class,
        'messaging' => \App\Models\Messaging::class,
        'message-recipients' => \App\Models\MessageRecipients::class,
        'meal-target-types' => \App\Models\MealTargetTypes::class,
        'meal-status-types' => \App\Models\MealStatusTypes::class,
        'meal-received' => \App\Models\MealReceived::class,
        'meal-ratings' => \App\Models\MealRatings::class,
        'meal-programmes' => \App\Models\MealProgrammes::class,
        'meal-programme-types' => \App\Models\MealProgrammeTypes::class,
        'meal-nutritions' => \App\Models\MealNutritions::class,
        'meal-nutritional-records' => \App\Models\MealNutritionalRecords::class,
        'meal-institution-programmes' => \App\Models\MealInstitutionProgrammes::class,
        'meal-implementers' => \App\Models\MealImplementers::class,
        'meal-food-records' => \App\Models\MealFoodRecords::class,
        'meal-benefits' => \App\Models\MealBenefits::class,
        'manuals' => \App\Models\Manuals::class,
        'locales' => \App\Models\Locales::class,
        'locale-contents' => \App\Models\LocaleContents::class,
        'locale-content-translations' => \App\Models\LocaleContentTranslations::class,
        'license-types' => \App\Models\LicenseTypes::class,
        'license-classifications' => \App\Models\LicenseClassifications::class,
        'languages' => \App\Models\Languages::class,
        'language-proficiencies' => \App\Models\LanguageProficiencies::class,
        'land-types' => \App\Models\LandTypes::class,
        'land-custom-field-values' => \App\Models\LandCustomFieldValues::class,
        'labels' => \App\Models\Labels::class,
        'insurance-types' => \App\Models\InsuranceTypes::class,
        'insurance-providers' => \App\Models\InsuranceProviders::class,
        'institutions' => \App\Models\Institutions::class,
        'institution-visit-requests' => \App\Models\InstitutionVisitRequests::class,
        'institution-units' => \App\Models\InstitutionUnits::class,
        'institution-types' => \App\Models\InstitutionTypes::class,
        'institution-trips' => \App\Models\InstitutionTrips::class,
        'institution-trip-passengers' => \App\Models\InstitutionTripPassengers::class,
        'institution-trip-days' => \App\Models\InstitutionTripDays::class,
        'institution-transport-providers' => \App\Models\InstitutionTransportProviders::class,
        'institution-textbooks' => \App\Models\InstitutionTextbooks::class,
        'institution-surveys' => \App\Models\InstitutionSurveys::class,
        'institution-survey-table-cells' => \App\Models\InstitutionSurveyTableCells::class,
        'institution-survey-answers' => \App\Models\InstitutionSurveyAnswers::class,
        'institution-subjects-rooms' => \App\Models\InstitutionSubjectsRooms::class,
        'institution-subjects' => \App\Models\InstitutionSubjects::class,
        'institution-subject-students' => \App\Models\InstitutionSubjectStudents::class,
        'institution-subject-staff' => \App\Models\InstitutionSubjectStaff::class,
        'institution-students-tmp' => \App\Models\InstitutionStudentsTmp::class,
        'institution-students-report-cards-comments' => \App\Models\InstitutionStudentsReportCardsComments::class,
        'institution-students-report-cards' => \App\Models\InstitutionStudentsReportCards::class,
        'institution-students-gpa' => \App\Models\InstitutionStudentsGpa::class,
        'institution-students' => \App\Models\InstitutionStudents::class,
        'institution-student-withdraw' => \App\Models\InstitutionStudentWithdraw::class,
        'institution-student-visits' => \App\Models\InstitutionStudentVisits::class,
        'institution-student-visit-requests' => \App\Models\InstitutionStudentVisitRequests::class,
        'institution-student-transfers' => \App\Models\InstitutionStudentTransfers::class,
        'institution-student-surveys' => \App\Models\InstitutionStudentSurveys::class,
        'institution-student-survey-table-cells' => \App\Models\InstitutionStudentSurveyTableCells::class,
        'institution-student-survey-answers' => \App\Models\InstitutionStudentSurveyAnswers::class,
        'institution-student-risks' => \App\Models\InstitutionStudentRisks::class,
        'institution-student-enrolment' => \App\Models\InstitutionStudentEnrolment::class,
        'institution-student-admission' => \App\Models\InstitutionStudentAdmission::class,
        'institution-student-absences' => \App\Models\InstitutionStudentAbsences::class,
        'institution-student-absence-details' => \App\Models\InstitutionStudentAbsenceDetails::class,
        'institution-student-absence-days' => \App\Models\InstitutionStudentAbsenceDays::class,
        'institution-statuses' => \App\Models\InstitutionStatuses::class,
        'institution-statistics' => \App\Models\InstitutionStatistics::class,
        'institution-staff-transfers' => \App\Models\InstitutionStaffTransfers::class,
        'institution-staff-surveys' => \App\Models\InstitutionStaffSurveys::class,
        'institution-staff-survey-table-cells' => \App\Models\InstitutionStaffSurveyTableCells::class,
        'institution-staff-survey-answers' => \App\Models\InstitutionStaffSurveyAnswers::class,
        'institution-staff-shifts' => \App\Models\InstitutionStaffShifts::class,
        'institution-staff-releases' => \App\Models\InstitutionStaffReleases::class,
        'institution-staff-position-profiles' => \App\Models\InstitutionStaffPositionProfiles::class,
        'institution-staff-leave-archived' => \App\Models\InstitutionStaffLeaveArchived::class,
        'institution-staff-leave' => \App\Models\InstitutionStaffLeave::class,
        'institution-staff-duties' => \App\Models\InstitutionStaffDuties::class,
        'institution-staff-attendances' => \App\Models\InstitutionStaffAttendances::class,
        'institution-staff-attendance-activities' => \App\Models\InstitutionStaffAttendanceActivities::class,
        'institution-staff-appraisals' => \App\Models\InstitutionStaffAppraisals::class,
        'institution-staff' => \App\Models\InstitutionStaff::class,
        'institution-shifts' => \App\Models\InstitutionShifts::class,
        'institution-shift-periods' => \App\Models\InstitutionShiftPeriods::class,
        'institution-sectors' => \App\Models\InstitutionSectors::class,
        'institution-schedule-timetables' => \App\Models\InstitutionScheduleTimetables::class,
        'institution-schedule-timetable-customizes' => \App\Models\InstitutionScheduleTimetableCustomizes::class,
        'institution-schedule-timeslots' => \App\Models\InstitutionScheduleTimeslots::class,
        'institution-schedule-terms' => \App\Models\InstitutionScheduleTerms::class,
        'institution-schedule-non-curriculum-lessons' => \App\Models\InstitutionScheduleNonCurriculumLessons::class,
        'institution-schedule-lessons' => \App\Models\InstitutionScheduleLessons::class,
        'institution-schedule-lesson-rooms' => \App\Models\InstitutionScheduleLessonRooms::class,
        'institution-schedule-lesson-details' => \App\Models\InstitutionScheduleLessonDetails::class,
        'institution-schedule-intervals' => \App\Models\InstitutionScheduleIntervals::class,
        'institution-schedule-curriculum-lessons' => \App\Models\InstitutionScheduleCurriculumLessons::class,
        'institution-scanned' => \App\Models\InstitutionScanned::class,
        'institution-rooms' => \App\Models\InstitutionRooms::class,
        'institution-risks' => \App\Models\InstitutionRisks::class,
        'institution-report-cards' => \App\Models\InstitutionReportCards::class,
        'institution-report-card-processes' => \App\Models\InstitutionReportCardProcesses::class,
        'institution-repeater-surveys' => \App\Models\InstitutionRepeaterSurveys::class,
        'institution-repeater-survey-table-cells' => \App\Models\InstitutionRepeaterSurveyTableCells::class,
        'institution-repeater-survey-answers' => \App\Models\InstitutionRepeaterSurveyAnswers::class,
        'institution-quality-visits' => \App\Models\InstitutionQualityVisits::class,
        'institution-quality-rubrics' => \App\Models\InstitutionQualityRubrics::class,
        'institution-quality-rubric-answers' => \App\Models\InstitutionQualityRubricAnswers::class,
        'institution-providers' => \App\Models\InstitutionProviders::class,
        'institution-program-grade-subjects' => \App\Models\InstitutionProgramGradeSubjects::class,
        'institution-positions' => \App\Models\InstitutionPositions::class,
        'institution-ownerships' => \App\Models\InstitutionOwnerships::class,
        'institution-outcome-subject-comments' => \App\Models\InstitutionOutcomeSubjectComments::class,
        'institution-outcome-results' => \App\Models\InstitutionOutcomeResults::class,
        'institution-meal-students' => \App\Models\InstitutionMealStudents::class,
        'institution-meal-programmes' => \App\Models\InstitutionMealProgrammes::class,
        'institution-localities' => \App\Models\InstitutionLocalities::class,
        'institution-lands' => \App\Models\InstitutionLands::class,
        'institution-incomes' => \App\Models\InstitutionIncomes::class,
        'institution-grades' => \App\Models\InstitutionGrades::class,
        'institution-genders' => \App\Models\InstitutionGenders::class,
        'institution-floors' => \App\Models\InstitutionFloors::class,
        'institution-fees' => \App\Models\InstitutionFees::class,
        'institution-fee-types' => \App\Models\InstitutionFeeTypes::class,
        'institution-expenditures' => \App\Models\InstitutionExpenditures::class,
        'institution-custom-table-rows' => \App\Models\InstitutionCustomTableRows::class,
        'institution-custom-table-columns' => \App\Models\InstitutionCustomTableColumns::class,
        'institution-custom-table-cells' => \App\Models\InstitutionCustomTableCells::class,
        'institution-custom-forms-filters' => \App\Models\InstitutionCustomFormsFilters::class,
        'institution-custom-forms-fields' => \App\Models\InstitutionCustomFormsFields::class,
        'institution-custom-forms' => \App\Models\InstitutionCustomForms::class,
        'institution-custom-fields' => \App\Models\InstitutionCustomFields::class,
        'institution-custom-field-values' => \App\Models\InstitutionCustomFieldValues::class,
        'institution-custom-field-options' => \App\Models\InstitutionCustomFieldOptions::class,
        'institution-curriculars' => \App\Models\InstitutionCurriculars::class,
        'institution-curricular-students' => \App\Models\InstitutionCurricularStudents::class,
        'institution-curricular-staff' => \App\Models\InstitutionCurricularStaff::class,
        'institution-courses' => \App\Models\InstitutionCourses::class,
        'institution-contact-persons' => \App\Models\InstitutionContactPersons::class,
        'institution-competency-results' => \App\Models\InstitutionCompetencyResults::class,
        'institution-competency-period-comments' => \App\Models\InstitutionCompetencyPeriodComments::class,
        'institution-competency-item-comments' => \App\Models\InstitutionCompetencyItemComments::class,
        'institution-committees' => \App\Models\InstitutionCommittees::class,
        'institution-committee-types' => \App\Models\InstitutionCommitteeTypes::class,
        'institution-committee-meeting' => \App\Models\InstitutionCommitteeMeeting::class,
        'institution-committee-attachments' => \App\Models\InstitutionCommitteeAttachments::class,
        'institution-classes-secondary-staff' => \App\Models\InstitutionClassesSecondaryStaff::class,
        'institution-classes-custom-field-values' => \App\Models\InstitutionClassesCustomFieldValues::class,
        'institution-classes' => \App\Models\InstitutionClasses::class,
        'institution-class-subjects' => \App\Models\InstitutionClassSubjects::class,
        'institution-class-students' => \App\Models\InstitutionClassStudents::class,
        'institution-class-grades' => \App\Models\InstitutionClassGrades::class,
        'institution-class-attendance-records' => \App\Models\InstitutionClassAttendanceRecords::class,
        'institution-cases' => \App\Models\InstitutionCases::class,
        'institution-case-records' => \App\Models\InstitutionCaseRecords::class,
        'institution-case-links' => \App\Models\InstitutionCaseLinks::class,
        'institution-case-comments' => \App\Models\InstitutionCaseComments::class,
        'institution-buses-transport-features' => \App\Models\InstitutionBusesTransportFeatures::class,
        'institution-buses' => \App\Models\InstitutionBuses::class,
        'institution-buildings' => \App\Models\InstitutionBuildings::class,
        'institution-budgets' => \App\Models\InstitutionBudgets::class,
        'institution-bank-accounts' => \App\Models\InstitutionBankAccounts::class,
        'institution-attachments' => \App\Models\InstitutionAttachments::class,
        'institution-attachment-types' => \App\Models\InstitutionAttachmentTypes::class,
        'institution-associations' => \App\Models\InstitutionAssociations::class,
        'institution-association-student' => \App\Models\InstitutionAssociationStudent::class,
        'institution-association-staff' => \App\Models\InstitutionAssociationStaff::class,
        'institution-assets' => \App\Models\InstitutionAssets::class,
        'institution-activities' => \App\Models\InstitutionActivities::class,
        'inserted-records' => \App\Models\InsertedRecords::class,
        'infrastructure-wash-waters' => \App\Models\InfrastructureWashWaters::class,
        'infrastructure-wash-water-types' => \App\Models\InfrastructureWashWaterTypes::class,
        'infrastructure-wash-water-quantities' => \App\Models\InfrastructureWashWaterQuantities::class,
        'infrastructure-wash-water-qualities' => \App\Models\InfrastructureWashWaterQualities::class,
        'infrastructure-wash-water-proximities' => \App\Models\InfrastructureWashWaterProximities::class,
        'infrastructure-wash-water-functionalities' => \App\Models\InfrastructureWashWaterFunctionalities::class,
        'infrastructure-wash-water-accessibilities' => \App\Models\InfrastructureWashWaterAccessibilities::class,
        'infrastructure-wash-wastes' => \App\Models\InfrastructureWashWastes::class,
        'infrastructure-wash-waste-types' => \App\Models\InfrastructureWashWasteTypes::class,
        'infrastructure-wash-waste-functionalities' => \App\Models\InfrastructureWashWasteFunctionalities::class,
        'infrastructure-wash-sewages' => \App\Models\InfrastructureWashSewages::class,
        'infrastructure-wash-sewage-types' => \App\Models\InfrastructureWashSewageTypes::class,
        'infrastructure-wash-sewage-functionalities' => \App\Models\InfrastructureWashSewageFunctionalities::class,
        'infrastructure-wash-sanitations' => \App\Models\InfrastructureWashSanitations::class,
        'infrastructure-wash-sanitation-uses' => \App\Models\InfrastructureWashSanitationUses::class,
        'infrastructure-wash-sanitation-types' => \App\Models\InfrastructureWashSanitationTypes::class,
        'infrastructure-wash-sanitation-quantities' => \App\Models\InfrastructureWashSanitationQuantities::class,
        'infrastructure-wash-sanitation-qualities' => \App\Models\InfrastructureWashSanitationQualities::class,
        'infrastructure-wash-sanitation-accessibilities' => \App\Models\InfrastructureWashSanitationAccessibilities::class,
        'infrastructure-wash-hygienes' => \App\Models\InfrastructureWashHygienes::class,
        'infrastructure-wash-hygiene-types' => \App\Models\InfrastructureWashHygieneTypes::class,
        'infrastructure-wash-hygiene-soapash-availabilities' => \App\Models\InfrastructureWashHygieneSoapashAvailabilities::class,
        'infrastructure-wash-hygiene-quantities' => \App\Models\InfrastructureWashHygieneQuantities::class,
        'infrastructure-wash-hygiene-educations' => \App\Models\InfrastructureWashHygieneEducations::class,
        'infrastructure-utility-telephones' => \App\Models\InfrastructureUtilityTelephones::class,
        'infrastructure-utility-internets' => \App\Models\InfrastructureUtilityInternets::class,
        'infrastructure-utility-electricities' => \App\Models\InfrastructureUtilityElectricities::class,
        'infrastructure-statuses' => \App\Models\InfrastructureStatuses::class,
        'infrastructure-projects-needs' => \App\Models\InfrastructureProjectsNeeds::class,
        'infrastructure-projects' => \App\Models\InfrastructureProjects::class,
        'infrastructure-project-funding-sources' => \App\Models\InfrastructureProjectFundingSources::class,
        'infrastructure-ownerships' => \App\Models\InfrastructureOwnerships::class,
        'infrastructure-needs' => \App\Models\InfrastructureNeeds::class,
        'infrastructure-need-types' => \App\Models\InfrastructureNeedTypes::class,
        'infrastructure-levels' => \App\Models\InfrastructureLevels::class,
        'infrastructure-custom-forms-filters' => \App\Models\InfrastructureCustomFormsFilters::class,
        'infrastructure-custom-forms-fields' => \App\Models\InfrastructureCustomFormsFields::class,
        'infrastructure-custom-forms' => \App\Models\InfrastructureCustomForms::class,
        'infrastructure-custom-fields' => \App\Models\InfrastructureCustomFields::class,
        'infrastructure-custom-field-options' => \App\Models\InfrastructureCustomFieldOptions::class,
        'infrastructure-conditions' => \App\Models\InfrastructureConditions::class,
        'industries' => \App\Models\Industries::class,
        'income-types' => \App\Models\IncomeTypes::class,
        'income-sources' => \App\Models\IncomeSources::class,
        'import-mapping' => \App\Models\ImportMapping::class,
        'idp-saml' => \App\Models\IdpSaml::class,
        'idp-oauth' => \App\Models\IdpOauth::class,
        'idp-google' => \App\Models\IdpGoogle::class,
        'identity-types' => \App\Models\IdentityTypes::class,
        'historical-staff-positions' => \App\Models\HistoricalStaffPositions::class,
        'historical-staff-leave' => \App\Models\HistoricalStaffLeave::class,
        'health-test-types' => \App\Models\HealthTestTypes::class,
        'health-relationships' => \App\Models\HealthRelationships::class,
        'health-immunization-types' => \App\Models\HealthImmunizationTypes::class,
        'health-consultation-types' => \App\Models\HealthConsultationTypes::class,
        'health-conditions' => \App\Models\HealthConditions::class,
        'health-allergy-types' => \App\Models\HealthAllergyTypes::class,
        'guidance-types' => \App\Models\GuidanceTypes::class,
        'guardian-relations' => \App\Models\GuardianRelations::class,
        'gpa-grading-types' => \App\Models\GpaGradingTypes::class,
        'gpa-grading-options' => \App\Models\GpaGradingOptions::class,
        'genders' => \App\Models\Genders::class,
        'food-types' => \App\Models\FoodTypes::class,
        'floor-types' => \App\Models\FloorTypes::class,
        'floor-custom-field-values' => \App\Models\FloorCustomFieldValues::class,
        'field-types' => \App\Models\FieldTypes::class,
        'field-options' => \App\Models\FieldOptions::class,
        'feeders-institutions' => \App\Models\FeedersInstitutions::class,
        'fee-types' => \App\Models\FeeTypes::class,
        'extracurricular-types' => \App\Models\ExtracurricularTypes::class,
        'external-data-source-attributes' => \App\Models\ExternalDataSourceAttributes::class,
        'expenditure-types' => \App\Models\ExpenditureTypes::class,
        'examinations' => \App\Models\Examinations::class,
        'examination-subjects' => \App\Models\ExaminationSubjects::class,
        'examination-student-subjects' => \App\Models\ExaminationStudentSubjects::class,
        'examination-student-subject-results' => \App\Models\ExaminationStudentSubjectResults::class,
        'examination-grading-types' => \App\Models\ExaminationGradingTypes::class,
        'examination-grading-options' => \App\Models\ExaminationGradingOptions::class,
        'examination-centres-examinations-subjects-students' => \App\Models\ExaminationCentresExaminationsSubjectsStudents::class,
        'examination-centres-examinations-subjects' => \App\Models\ExaminationCentresExaminationsSubjects::class,
        'examination-centres-examinations-students' => \App\Models\ExaminationCentresExaminationsStudents::class,
        'examination-centres-examinations-invigilators' => \App\Models\ExaminationCentresExaminationsInvigilators::class,
        'examination-centres-examinations-institutions' => \App\Models\ExaminationCentresExaminationsInstitutions::class,
        'examination-centres-examinations' => \App\Models\ExaminationCentresExaminations::class,
        'examination-centres' => \App\Models\ExaminationCentres::class,
        'examination-centre-special-needs' => \App\Models\ExaminationCentreSpecialNeeds::class,
        'examination-centre-rooms-examinations-students' => \App\Models\ExaminationCentreRoomsExaminationsStudents::class,
        'examination-centre-rooms-examinations-invigilators' => \App\Models\ExaminationCentreRoomsExaminationsInvigilators::class,
        'examination-centre-rooms-examinations' => \App\Models\ExaminationCentreRoomsExaminations::class,
        'examination-centre-rooms' => \App\Models\ExaminationCentreRooms::class,
        'employment-status-types' => \App\Models\EmploymentStatusTypes::class,
        'email-templates' => \App\Models\EmailTemplates::class,
        'email-processes' => \App\Models\EmailProcesses::class,
        'email-process-attachments' => \App\Models\EmailProcessAttachments::class,
        'education-systems' => \App\Models\EducationSystems::class,
        'education-subjects-field-of-studies' => \App\Models\EducationSubjectsFieldOfStudies::class,
        'education-subjects' => \App\Models\EducationSubjects::class,
        'education-stages' => \App\Models\EducationStages::class,
        'education-programmes-next-programmes' => \App\Models\EducationProgrammesNextProgrammes::class,
        'education-programmes' => \App\Models\EducationProgrammes::class,
        'education-programme-orientations' => \App\Models\EducationProgrammeOrientations::class,
        'education-levels' => \App\Models\EducationLevels::class,
        'education-level-isced' => \App\Models\EducationLevelIsced::class,
        'education-grades-subjects' => \App\Models\EducationGradesSubjects::class,
        'education-grades-gpa' => \App\Models\EducationGradesGpa::class,
        'education-grades-cumulative-gpa' => \App\Models\EducationGradesCumulativeGpa::class,
        'education-grades' => \App\Models\EducationGrades::class,
        'education-field-of-studies' => \App\Models\EducationFieldOfStudies::class,
        'education-cycles' => \App\Models\EducationCycles::class,
        'education-certifications' => \App\Models\EducationCertifications::class,
        'demographic-types' => \App\Models\DemographicTypes::class,
        'deleted-records' => \App\Models\DeletedRecords::class,
        'data-management-logs' => \App\Models\DataManagementLogs::class,
        'data-management-copy' => \App\Models\DataManagementCopy::class,
        'data-management-connections' => \App\Models\DataManagementConnections::class,
        'data-dictionary' => \App\Models\DataDictionary::class,
        'custom-table-rows' => \App\Models\CustomTableRows::class,
        'custom-table-columns' => \App\Models\CustomTableColumns::class,
        'custom-table-cells' => \App\Models\CustomTableCells::class,
        'custom-records' => \App\Models\CustomRecords::class,
        'custom-modules' => \App\Models\CustomModules::class,
        'custom-forms-filters' => \App\Models\CustomFormsFilters::class,
        'custom-forms-fields' => \App\Models\CustomFormsFields::class,
        'custom-forms' => \App\Models\CustomForms::class,
        'custom-fields' => \App\Models\CustomFields::class,
        'custom-field-values' => \App\Models\CustomFieldValues::class,
        'custom-field-types' => \App\Models\CustomFieldTypes::class,
        'custom-field-options' => \App\Models\CustomFieldOptions::class,
        'curricular-types' => \App\Models\CurricularTypes::class,
        'curricular-positions' => \App\Models\CurricularPositions::class,
        'countries' => \App\Models\Countries::class,
        'counsellings' => \App\Models\Counsellings::class,
        'contact-types' => \App\Models\ContactTypes::class,
        'contact-options' => \App\Models\ContactOptions::class,
        'config-product-lists' => \App\Models\ConfigProductLists::class,
        'config-items' => \App\Models\ConfigItems::class,
        'config-item-options' => \App\Models\ConfigItemOptions::class,
        'config-attachments' => \App\Models\ConfigAttachments::class,
        'competency-templates' => \App\Models\CompetencyTemplates::class,
        'competency-periods' => \App\Models\CompetencyPeriods::class,
        'competency-items-periods' => \App\Models\CompetencyItemsPeriods::class,
        'competency-items' => \App\Models\CompetencyItems::class,
        'competency-grading-types' => \App\Models\CompetencyGradingTypes::class,
        'competency-grading-options' => \App\Models\CompetencyGradingOptions::class,
        'competency-criterias' => \App\Models\CompetencyCriterias::class,
        'comment-types' => \App\Models\CommentTypes::class,
        'class-profiles' => \App\Models\ClassProfiles::class,
        'class-profile-templates' => \App\Models\ClassProfileTemplates::class,
        'class-profile-processes' => \App\Models\ClassProfileProcesses::class,
        'case-types' => \App\Models\CaseTypes::class,
        'case-priorities' => \App\Models\CasePriorities::class,
        'calendar-types' => \App\Models\CalendarTypes::class,
        'calendar-events' => \App\Models\CalendarEvents::class,
        'calendar-event-dates' => \App\Models\CalendarEventDates::class,
        'bus-types' => \App\Models\BusTypes::class,
        'building-types' => \App\Models\BuildingTypes::class,
        'building-custom-field-values' => \App\Models\BuildingCustomFieldValues::class,
        'budget-types' => \App\Models\BudgetTypes::class,
        'behaviour-classifications' => \App\Models\BehaviourClassifications::class,
        'banks' => \App\Models\Banks::class,
        'bank-branches' => \App\Models\BankBranches::class,
        'backup-logs' => \App\Models\BackupLogs::class,
        'authentication-types' => \App\Models\AuthenticationTypes::class,
        'asset-types' => \App\Models\AssetTypes::class,
        'asset-statuses' => \App\Models\AssetStatuses::class,
        'asset-models' => \App\Models\AssetModels::class,
        'asset-makes' => \App\Models\AssetMakes::class,
        'asset-conditions' => \App\Models\AssetConditions::class,
        'assessments' => \App\Models\Assessments::class,
        'assessment-periods' => \App\Models\AssessmentPeriods::class,
        'assessment-period-excluded-security-roles' => \App\Models\AssessmentPeriodExcludedSecurityRoles::class,
        'assessment-items-grading-types' => \App\Models\AssessmentItemsGradingTypes::class,
        'assessment-items' => \App\Models\AssessmentItems::class,
        'assessment-item-student-exemptions' => \App\Models\AssessmentItemStudentExemptions::class,
        'assessment-item-results-archived' => \App\Models\AssessmentItemResultsArchived::class,
        'assessment-item-results' => \App\Models\AssessmentItemResults::class,
        'assessment-grading-types' => \App\Models\AssessmentGradingTypes::class,
        'assessment-grading-options' => \App\Models\AssessmentGradingOptions::class,
        'area-administrative-levels' => \App\Models\AreaAdministrativeLevels::class,
        'appraisal-types' => \App\Models\AppraisalTypes::class,
        'appraisal-text-answers' => \App\Models\AppraisalTextAnswers::class,
        'appraisal-sliders' => \App\Models\AppraisalSliders::class,
        'appraisal-slider-answers' => \App\Models\AppraisalSliderAnswers::class,
        'appraisal-score-answers' => \App\Models\AppraisalScoreAnswers::class,
        'appraisal-periods-types' => \App\Models\AppraisalPeriodsTypes::class,
        'appraisal-periods' => \App\Models\AppraisalPeriods::class,
        'appraisal-numbers' => \App\Models\AppraisalNumbers::class,
        'appraisal-number-answers' => \App\Models\AppraisalNumberAnswers::class,
        'appraisal-forms-criterias-scores-links' => \App\Models\AppraisalFormsCriteriasScoresLinks::class,
        'appraisal-forms-criterias-scores' => \App\Models\AppraisalFormsCriteriasScores::class,
        'appraisal-forms-criterias' => \App\Models\AppraisalFormsCriterias::class,
        'appraisal-forms' => \App\Models\AppraisalForms::class,
        'appraisal-dropdown-options' => \App\Models\AppraisalDropdownOptions::class,
        'appraisal-dropdown-answers' => \App\Models\AppraisalDropdownAnswers::class,
        'appraisal-criterias' => \App\Models\AppraisalCriterias::class,
        'api-securities-scopes' => \App\Models\ApiSecuritiesScopes::class,
        'api-securities' => \App\Models\ApiSecurities::class,
        'api-scopes' => \App\Models\ApiScopes::class,
        'api-credentials-scopes' => \App\Models\ApiCredentialsScopes::class,
        'api-credentials' => \App\Models\ApiCredentials::class,
        'api-authorizations' => \App\Models\ApiAuthorizations::class,
        'alerts-roles' => \App\Models\AlertsRoles::class,
        'alerts' => \App\Models\Alerts::class,
        'alert-rules' => \App\Models\AlertRules::class,
        'alert-logs' => \App\Models\AlertLogs::class,
        'academic-periods' => \App\Models\AcademicPeriods::class,
        'academic-period-levels' => \App\Models\AcademicPeriodLevels::class,
        'absence-types' => \App\Models\AbsenceTypes::class,
        'area-levels' => \App\Models\AreaLevels::class,
        'area-administratives' => \App\Models\AreaAdministratives::class,
        'areas' => \App\Models\Areas::class,
        //...
        //...
    ];

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
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
            return response()->json(['error' => 'Forbidden'], 403);
        }
        // POCOR-8966 start

//        Log::info("User authorized for {$model}:{$action}");

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
        $order = $this->parseOrderParams($request, $segments);

        $query = $this->parseSelectParams($request, $segments, $query, $model);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applyOrder($query, $order, $model);
        $query = $this->applyInstitutionFilter($query, $model);

        return $this->paginateResults($query, $pagination['limit'], $pagination['page']);
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

        if ($this->isBatchRequest($data)) {
            return $this->handleBatchCreate($model, $data);
        }

        return $this->handleSingleCreate($model, $data);
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
        $excludedParams = ['order', 'orderby', '_fields', '_conditions', 'page', 'limit'];

        // Parse conditions from the query parameter
        $conditionsParam = $request->input('_conditions');
        if ($conditionsParam) {
            $filters = array_merge($filters, $this->parseConditions($conditionsParam));
        }

        // Parse other filters from query parameters
        foreach ($request->all() as $key => $value) {
            if (!in_array($key, $excludedParams)) {
                $filters[$key] = $value;
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
                    $filters[$key] = $value;
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
                $conditions[$parts[0]] = $parts[1];
            }
        }

        return $conditions;
    }

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

        return $query;
    }


    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
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
                $query->where($field, 'like', '%' . $value . '%');
            }
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
        $hasOrderParam = !empty($order['columns']);

        if (!$hasOrderParam && in_array('order', $model->getFillable())) {
            // Default ordering by 'order' field if no order params are provided
            $query->orderBy('order', 'asc');
        } else {
            foreach ($order['columns'] as $index => $column) {
                $direction = $order['directions'][$index] ?? 'asc';
                $query->orderBy($column, $direction);
            }
        }

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

        return [
            'columns' => $orderColumns,
            'directions' => $orderDirections,
        ];
    }


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

    /**
     * Paginate the results of the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @param int $page
     * @return \Illuminate\Http\JsonResponse
     */
    private function paginateResults($query, $limit, $page)
    {
        try {
            $results = $query->paginate($limit, ['*'], 'page', $page);
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
            $records = [];
            foreach ($data as $recordData) {
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
        try {
            $record = $model::create($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }

        return $this->successResponse('Record created successfully.', $record, 201);
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
        return response()->json(['message' => $message, 'data' => $data], $statusCode);
    }

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
}
