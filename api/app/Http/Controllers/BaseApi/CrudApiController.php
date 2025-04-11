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

        // Check Permissions
        if (!$this->permissionService->checkPermission($model, $action)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

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

    protected function handleGetRequest(Request $request, $model, array $segments)
    {
        try {
            $query = $model::query();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }

        // Default pagination values.
        $page   = $request->input('page', 1);
        $limit  = $request->input('limit', 20);

        // Retrieve _fields and _scope from query parameters.
        $fields     = $request->query('_fields');
        $scopeParam = $request->query('_scope');
        $conditionsParam = $request->input('_conditions');

        // Initialize ordering arrays.
        $orderColumnsPath    = [];
        $orderDirectionsPath = [];

        // If there's an odd number of segments, treat the first segment as a potential record identifier.
        if (count($segments) % 2 === 1) {
            $possibleId = array_shift($segments);
            if ($this->isValidIdentifier($possibleId)) {
                try {
                    $possibleIdField = $this->getPossibleIdField($model);
                    $record = $model::where($possibleIdField, $possibleId)->first();
                    return response()->json($record);
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 404);
                }
            } else {
                array_unshift($segments, $possibleId);
            }
        }

        // Process segments in pairs.
        for ($i = 0; $i < count($segments); $i += 2) {
            $key   = strtolower($segments[$i]);
            $value = $segments[$i + 1] ?? null;
            if ($value === null) {
                continue;
            }
            if ($key === 'limit') {
                $limit = $value;
                continue;
            }
            if ($key === 'page') {
                $page = $value;
                continue;
            }
            if ($key === 'orderby') {
                $orderColumnsPath[] = $value;
                continue;
            }
            if ($key === 'order') {
                $orderDirectionsPath[] = strtolower($value);
                continue;
            }
            if ($key === '_fields') {
                // Override query parameter value if provided in segments.
                $fields = $value;
                continue;
            }
            if ($key === '_scope') {
                $scopeParam = $value;
                continue;
            }
            if ($key === '_conditions') {
                $conditionsParam = $value;
                continue;
            }
            // Apply other keys as filters with a LIKE clause.
            try {
                $query->where($key, 'like', '%' . $value . '%');
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 404);
            }

        }
        if ($conditionsParam) {
            // Parse the custom _conditions format.
            try {
                // Remove the surrounding brackets (if any).
                $trimmed = trim($conditionsParam, '[]');

                // Split the string by semicolon to get each key-value pair.
                $pairs = explode(';', $trimmed);
                $conditions = [];

                // Process each key-value pair.
                foreach ($pairs as $pair) {
                    // Split by colon, limiting to 2 parts in case values contain colons.
                    $parts = explode(':', $pair, 2);
                    if (count($parts) === 2) {
                        $conditions[$parts[0]] = $parts[1];
                    }
                }
            } catch (\Exception $e) {
                // Return a 500 error if something goes wrong during parsing.
                return response()->json(['error' => $e->getMessage()], 500);
            }

            // If conditions were parsed successfully, apply each condition to the query.
            if (is_array($conditions)) {
                foreach ($conditions as $field => $value) {
                    // Handle array values (for WHERE IN queries).
                    if (is_array($value)) {
                        try {
                            $query->whereIn($field, $value);
                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 404);
                        }
                    }
                    // Handle values starting with '>' for greater-than comparisons.
                    elseif (strpos($value, '>') === 0) {
                        try {
                            $query->where($field, '>', substr($value, 1));
                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 404);
                        }
                    }
                    // Handle values starting with '<' for less-than comparisons.
                    elseif (strpos($value, '<') === 0) {
                        try {
                            $query->where($field, '<', substr($value, 1));
                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 404);
                        }
                    }
                    // Default: use a LIKE query.
                    else {
                        try {
                            $query->where($field, 'like', '%' . $value . '%');
                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 404);
                        }
                    }
                }
            }
        }

        // Process _fields regardless of segments.
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

        // Process _scope regardless of segments.
        if ($scopeParam) {
            // Allow multiple scopes separated by commas.
            $scopes = array_map('trim', explode(',', $scopeParam));
            foreach ($scopes as $scopeMethod) {
                $methodName = 'scope' . ucfirst($scopeMethod);
                // Use the provided model class (replace $model if necessary) to check for the scope.
                if (method_exists($model, $methodName)) {
                    try {
                        $query->{$scopeMethod}();
                    } catch (\Exception $e) {
                        return response()->json(['error' => $e->getMessage()], 404);
                    }
                }
            }
        }

        // Handle ordering if provided.
        if (!empty($orderColumnsPath)) {
            foreach ($orderColumnsPath as $index => $column) {
                $direction = $orderDirectionsPath[$index] ?? 'asc';
                try {
                    $query->orderBy($column, $direction);
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 404);
                }
            }
        }

        // Apply pagination.
        try {
            $results = $query->paginate($limit, ['*'], 'page', $page);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
        return response()->json(
//            [
//            'sql' => $query->toSql(),
//            'bindings' => $query->getBindings(),
//            'data' => $results
//        ]
            $results
        );
    }


    /**
     * Handle POST requests to create a new record.
     *
     * @param \Illuminate\Http\Request $request
     * @param $model // Fully qualified model class.
     * @return \Illuminate\Http\JsonResponse
     */
    public function handlePostRequest(Request $request, $model)
    {
        $data = $request->all();

        // Check if the request payload is an array of records
        if (isset($data[0]) && is_array($data)) {
            $records = [];
            \DB::beginTransaction();
            try {
                foreach ($data as $recordData) {
                    $records[] = $model::create($recordData);
                }
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollBack();
                return $this->sendServerErrorResponse($e->getMessage());
            }
            return $this->sendCreateSuccessResponse('Successful.', $records);
        } else {
            // Otherwise, treat it as a single record
            try {
                $record = $model::create($data);
            } catch (\Exception $e) {
                return $this->sendServerErrorResponse($e->getMessage());
            }
            return $this->sendCreateSuccessResponse('Successful.', $record);
        }
    }


    /**
     * Check if a field is unique in the model's table.
     *
     * @param  $model  // The model instance or class name.
     * @param  string  $field  The field to check.
     * @return bool
     */
    function isFieldUnique($model, $field)
    {
        // If a model class name is provided, instantiate it.
        if (is_string($model)) {
            $model = new $model;
        }

        // Get the table name from the model.
        try {
            // Query the database for unique indexes on the given column.
            $table = $model->getTable();
        } catch (\Exception $e) {
            // You might want to log the error here or handle it differently.
            return false;
        }

        try {
            // Query the database for unique indexes on the given column.
            $indexes = \DB::select("SHOW INDEX FROM {$table} WHERE Column_name = ? AND Non_unique = 0", [$field]);
        } catch (\Exception $e) {
            // You might want to log the error here or handle it differently.
            return false;
        }

        return count($indexes) > 0;
    }

    /**
     * Handle PUT requests to update an existing record.
     *
     * If one segment is provided and it is a valid identifier, update by ID.
     * If two segments are provided, treat the first as a unique field and the second as its value.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handlePutRequest(Request $request, $model, array $segments)
    {
//        Log::info('segments: '.json_encode($segments));
        if (empty($segments)) {
            return response()->json(['error' => 'Missing identifier for update'], 400);
        }
//        Log::info('request: '.json_encode($request->all()));
        // Determine the record to update based on the provided segments.
        if (count($segments) === 1 && $this->isValidIdentifier($segments[0])) {
            // Case 1: Update by numeric (or UUID) ID.
            try {
                $possibleIdField = $this->getPossibleIdField($model);
//                Log::info('possibleIdField: '.json_encode($possibleIdField));
                $record = $model::where($possibleIdField, $segments[0])->first();
//                Log::info('$record: '.json_encode($record));
//                $record = $model::findOrFail($segments[0]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 404);
            }
        } elseif (count($segments) >= 2) {
            // Case 2: Update by multiple unique fields.
            $conditions = [];
            for ($i = 0; $i < count($segments); $i += 2) {
                $field = $segments[$i];
                $value = $segments[$i + 1];

//                if (!$this->isFieldUnique($model, $field)) {
//                    return response()->json(['error' => __('The specified field is not unique.')], 404);
//                }

                $conditions[$field] = $value;
            }

            try {
                $record = $model::where($conditions)->first();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 404);
            }

            if (!$record) {
                return response()->json(['error' => __('Record not found.')], 404);
            }
        } else {
            return response()->json(['error' => 'Invalid update identifier'], 400);
        }
        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }
        // Retrieve update data from the request.
        $data = $request->all();
//        Log::info('data: '.json_encode($data) .'; record ' . json_encode($record));
        // Update the record using the model's update method.
        try {
            $record->update($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return $this->sendSuccessResponse('Record updated successfully.', $record);

    }

    /**
     * Handle DELETE requests to remove an existing record.
     *
     * If one valid identifier is provided, delete by ID.
     * If two segments are provided, treat the first as a unique field and the second as its value.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Handle DELETE requests to remove existing records.
     *
     * Supports deletion by:
     * - A single valid identifier (ID or UUID).
     * - A unique field/value pair.
     * - Multiple IDs (comma-separated) for bulk deletion.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $model Fully qualified model class.
     * @param array $segments Remaining URL segments.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleDeleteRequest(Request $request, $model, array $segments)
    {
        if (is_string($model)) {
            $model = new $model;
        }

        // Check if segments are provided.
        if (empty($segments)) {
            return response()->json(['error' => 'Missing identifier for delete'], 400);
        }

        // Case 1: Bulk deletion by comma-separated IDs (e.g., /appraisal-types/47,48)

        if (count($segments) === 1
            && strpos($segments[0], ',') !== false) {
            $ids = explode(',', $segments[0]);
//            Log::info('segments: '.json_encode($ids));

            // Validate each ID.
            foreach ($ids as $id) {
                if (!$this->isValidIdentifier($id)) {
                    return response()->json(['error' => "Invalid identifier: $id"], 400);
                }
            }

            // Use a transaction to ensure atomicity.
            \DB::beginTransaction();
            try {
                $possibleIdField = $this->getPossibleIdField($model);
                foreach ($ids as $id) {
//                    $record = $model::findOrFail($id);
                    $possibleId = $id;
                    $record = $model->where($possibleIdField, $possibleId)->delete();
//                    Log::info('record: '.json_encode($record));

                }
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 403);
            }

            return response()->json(['message' => 'Records deleted successfully'], 204);
        }

        // Case 2: Deletion by a single identifier.
        if (count($segments) === 1 && $this->isValidIdentifier($segments[0])) {
            try {
                $possibleId = $segments[0];
                $possibleIdField = $this->getPossibleIdField($model);
//                Log::info('possibleIdField: '.$possibleIdField);
//                Log::info('possibleId: '.$possibleId);
                $record = $model::where($possibleIdField, $possibleId)->first();

//                Log::info('record: '.json_encode($record));

//                $record = $model::findOrFail($segments[0]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 404);
            }
            try {
                if($record){
                    $record->delete();
                }
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 403);
            }
            return response()->json(['message' => 'Deleted successfully'], 204);
        }

        // Case 3: Deletion by unique field (e.g., /resource/field/value)
        if (count($segments) >= 2) {
            // Case 2: Update by multiple unique fields.
            $conditions = [];
            for ($i = 0; $i < count($segments); $i += 2) {
                $field = $segments[$i];
                $value = $segments[$i + 1];

//                if (!$this->isFieldUnique($model, $field)) {
//                    return response()->json(['error' => __('The specified field is not unique.')], 404);
//                }

                $conditions[$field] = $value;
            }

            try {
                $record = $model::where($conditions)->first();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 404);
            }

            if (!$record) {
                return response()->json(['error' => __('Record not found.')], 404);
            }
            try {
                $model->where($conditions)->delete();
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 403);
            }

            return response()->json(['message' => 'Deleted successfully'], 204);
        }

        return response()->json(['error' => 'Invalid delete identifier'], 400);
    }




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
