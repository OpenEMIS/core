<?php

namespace AcademicPeriod\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;
use Cake\I18n\Date;
//use Cake\I18n\FrozenDate;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use Cake\Datasource\ConnectionManager;

class AcademicPeriodsTable extends ControllerActionTable
{
    private $_fieldOrder = ['visible', 'current', 'editable', 'code', 'name', 'start_date', 'end_date', 'academic_period_level_id'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('Parents', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Levels', ['className' => 'AcademicPeriod.AcademicPeriodLevels', 'foreignKey' => 'academic_period_level_id']);


        // reference to itself
        $this->hasMany('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'parent_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('AssessmentAssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Assessments', ['className' => 'Assessment.Assessments', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CalendarEvents', ['className' => 'Institution.CalendarEvents', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassProfileProcesses', ['className' => 'ReportCard.ClassProfileProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassProfileTemplates', ['className' => 'ProfileTemplate.ClassProfileTemplates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ClassProfiles', ['className' => 'Institution.ClassProfiles', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyCriterias', ['className' => 'Competency.CompetencyCriterias', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyItems', ['className' => 'Competency.CompetencyItems', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyItemsPeriods', ['className' => 'Competency.CompetencyItemsPeriods', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyPeriods', ['className' => 'Competency.CompetencyPeriods', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('CompetencyTemplates', ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('EducationSystems', ['className' => 'Education.EducationSystems', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
//        $this->hasMany('ExaminationCentres', ['className' => 'Examination.ExaminationCentres', 'dependent' => true, 'cascadeCallbacks' => true]); // POCOR-9403
//        $this->hasMany('ExaminationCentresExaminations', ['className' => 'Examination.ExaminationCentresExaminations', 'dependent' => true, 'cascadeCallbacks' => true]); // POCOR-9403
        $this->hasMany('ExaminationCentresExaminationsStudents', ['className' => 'Examination.ExaminationCentresExaminationsStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Examinations', ['className' => 'Examination.Examinations', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('FeedersInstitutions', ['className' => 'Institution.FeederIncomingInstitutions', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureUtilityElectricities', ['className' => 'Institution.InfrastructureUtilityElectricities', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureUtilityInternets', ['className' => 'Institution.InfrastructureUtilityInternets', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureUtilityTelephones', ['className' => 'Institution.InfrastructureUtilityTelephones', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashHygienes', ['className' => 'Institution.InfrastructureWashHygienes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashSanitations', ['className' => 'Institution.InfrastructureWashSanitations', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashSewages', ['className' => 'Institution.InfrastructureWashSewages', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashWastes', ['className' => 'Institution.InfrastructureWashWastes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InfrastructureWashWaters', ['className' => 'Institution.InfrastructureWashWaters', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAssessmentItemResults', ['className' => 'Institution.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAssociationStudent', ['className' => 'Student.InstitutionAssociationStudent', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionAssociations', ['className' => 'Institution.InstitutionAssociations', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionBudgets', ['className' => 'Institution.InstitutionBudgets', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
//        $this->hasMany('InstitutionBuildings', ['className' => 'Institution.InstitutionBuildings', 'dependent' => true]); // POCOR-9403
        $this->hasMany('InstitutionClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCompetencyItemComments', ['className' => 'Institution.InstitutionCompetencyItemComments', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCompetencyPeriodComments', ['className' => 'Institution.InstitutionCompetencyPeriodComments', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionCompetencyResults', ['className' => 'Institution.InstitutionCompetencyResults', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionExpenditures', ['className' => 'Institution.InstitutionExpenditures', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionFees', ['className' => 'Institution.InstitutionFees', 'dependent' => true, 'cascadeCallbacks' => true]);
//        $this->hasMany('InstitutionFloors', ['className' => 'Institution.InstitutionFloors', 'dependent' => true]); // POCOR-9403
        $this->hasMany('InstitutionIncomes', ['className' => 'Institution.InstitutionIncomes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionInstitutionSubjects', ['className' => 'Institution.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
//        $this->hasMany('InstitutionLands', ['className' => 'Institution.InstitutionLands', 'dependent' => true]); // POCOR-9403
        $this->hasMany('InstitutionMealProgrammes', ['className' => 'Institution.InstitutionDistributions', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionMealStudents', ['className' => 'Institution.InstitutionMealStudents', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionOutcomeResults', ['className' => 'Institution.InstitutionOutcomeResults', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionOutcomeSubjectComments', ['className' => 'Student.StudentOutcomes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionQualityRubrics', ['className' => 'Institution.InstitutionRubrics', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionQualityVisits', ['className' => 'Quality.InstitutionQualityVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRepeaterSurveys', ['className' => 'InstitutionRepeater.RepeaterSurveys', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionReportCardProcesses', ['className' => 'ReportCard.InstitutionReportCardProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionReportCards', ['className' => 'Institution.InstitutionReportCards', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
//        $this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true]); // POCOR-9403
        $this->hasMany('InstitutionRubrics', ['className' => 'Institution.InstitutionRubrics', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleIntervals', ['className' => 'Schedule.ScheduleIntervals', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleTerms', ['className' => 'Schedule.ScheduleTerms', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleTimetableCustomizes', ['className' => 'Schedule.ScheduleTimetableCustomizes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionScheduleTimetables', ['className' => 'Schedule.ScheduleTimetables', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaffAttendances', ['className' => 'Institution.InstitutionStaffAttendances', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaffDuties', ['className' => 'Institution.InstitutionStaffDuties', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStaffLeave', ['className' => 'Institution.StaffLeave', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentAbsenceDetails', ['className' => 'Institution.InstitutionStudentAbsenceDetails', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentRisks', ['className' => 'Institution.InstitutionStudentRisks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentSurveys', ['className' => 'Student.StudentSurveys', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentTransfers', ['className' => 'Institution.InstitutionStudentTransfers', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentTransfers', ['className' => 'Institution.InstitutionStudentTransfers', 'foreignKey' => 'previous_academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentVisitRequests', ['className' => 'Student.StudentVisitRequests', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentVisits', ['className' => 'Student.StudentVisits', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentsReportCards', ['className' => 'Institution.InstitutionStudentsReportCards', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentsReportCardsComments', ['className' => 'Institution.InstitutionStudentsReportCardsComments', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionTextbooks', ['className' => 'Institution.InstitutionTextbooks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionTripPassengers', ['className' => 'Institution.InstitutionTripPassengers', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionTrips', ['className' => 'Institution.InstitutionTrips', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionVisitRequests', ['className' => 'Quality.VisitRequests', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('MealProgrammes', ['className' => 'Meal.MealProgrammes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('OutcomeCriterias', ['className' => 'Outcome.OutcomeCriterias', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('OutcomePeriods', ['className' => 'Outcome.OutcomePeriods', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('OutcomeTemplates', ['className' => 'Outcome.OutcomeTemplates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ProfileTemplates', ['className' => 'ProfileTemplate.ProfileTemplates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Programmes', ['className' => 'Student.Programmes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RepeaterSurveys', ['className' => 'InstitutionRepeater.RepeaterSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ReportCardEmailProcesses', ['className' => 'ReportCard.ReportCardEmailProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ReportCardProcesses', ['className' => 'ReportCard.ReportCardProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ReportCards', ['className' => 'ReportCard.ReportCardEmail', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Risks', ['className' => 'Risk.Risks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('RubricStatusPeriods', ['className' => 'Rubric.RubricStatusPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipRecipientAcademicStandings', ['className' => 'Scholarship.RecipientAcademicStandings', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipRecipientCollections', ['className' => 'Scholarship.RecipientCollections', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ScholarshipRecipientPaymentStructures', ['className' => 'Scholarship.RecipientPaymentStructures', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Scholarships', ['className' => 'Scholarship.Scholarships', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffBehaviours', ['className' => 'Institution.StaffBehaviours', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffExtracurriculars', ['className' => 'Student.StudentExtracurriculars', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffProfileTemplates', ['className' => 'ProfileTemplate.StaffProfileTemplates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffReportCardEmailProcesses', ['className' => 'ReportCard.StudentReportCardEmailProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffReportCardProcesses', ['className' => 'ReportCard.StaffReportCardProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StaffReportCards', ['className' => 'Staff.Profiles', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAttendanceMarkTypes', ['className' => 'Attendance.StudentAttendanceMarkTypes', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAttendanceMarkedRecords', ['className' => 'Attendance.StudentAttendanceMarkedRecords', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentAttendances', ['className' => 'Institution.StudentAttendances', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentClasses', ['className' => 'Student.StudentClasses', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentExtracurriculars', ['className' => 'Report.StaffExtracurriculars', 'dependent' => true, 'cascadeCallbacks' => false]); //POCOR-6762
        $this->hasMany('StudentFees', ['className' => 'Institution.StudentFees', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentMarkTypeStatuses', ['className' => 'Attendance.StudentMarkTypeStatuses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentMealMarkedRecords', ['className' => 'Meal.StudentMealMarkedRecords', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentProfileTemplates', ['className' => 'ProfileTemplate.StudentTemplates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentPromotion', ['className' => 'Institution.StudentPromotion', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentReportCardEmailProcesses', ['className' => 'ReportCard.StudentReportCardEmailProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentReportCardProcesses', ['className' => 'ReportCard.StudentReportCardProcesses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentReportCards', ['className' => 'Institution.InstitutionStudentsProfileTemplates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentStatusUpdates', ['className' => 'Institution.StudentStatusUpdates', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentSurveys', ['className' => 'Student.StudentSurveys', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransfer', ['className' => 'Institution.StudentTransfer', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferIn', ['className' => 'Institution.StudentTransferIn', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentTransferOut', ['className' => 'Institution.StudentTransferOut', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('StudentWithdraw', ['className' => 'Institution.StudentWithdraw', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Students', ['className' => 'Institution.Students', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SummaryAssessmentItemResults', ['className' => 'Report.Performance', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SurveyStatusPeriods', ['className' => 'Survey.SurveyStatusPeriods', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Textbooks', ['className' => 'Textbook.Textbooks', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TransferLogs', ['className' => 'Archive.TransferLogs', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UndoStudentStatus', ['className' => 'Institution.UndoStudentStatus', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserBodyMasses', ['className' => 'User.UserBodyMasses', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserSpecialNeedsReferrals', ['className' => 'SpecialNeeds.SpecialNeedsReferrals', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('UserSpecialNeedsServices', ['className' => 'SpecialNeeds.SpecialNeedsServices', 'foreignKey' => 'academic_period_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('WithdrawRequests', ['className' => 'Institution.WithdrawRequests', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Tree');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index'],
            'Results' => ['index'],
            'StudentExaminationResults' => ['index'],
            'OpenEMIS_Classroom' => ['index', 'view'],
            'InstitutionStaffAttendances' => ['index', 'view'],
            'StudentAttendances' => ['index', 'view'],
            'ScheduleTimetable' => ['index']
        ]);

        $this->addBehavior('Institution.Calendar');
        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'academic_period_create',
                'entity_delete' => 'academic_period_delete',
                'entity_update' => 'academic_period_update',
                'table_alias' => 'AcademicPeriod.AcademicPeriods',
                'contain' => ['Parents', 'Levels']
            ]
        ); // for webhook
        //$this->getSchema()->setColumn('order', ['accessible' => true]);

    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $additionalParameters = ['editable = 1 AND visible > 0'];
        //POCOR-5917 starts
        $validator->setProvider('custom', $this);
        return $validator
            ->add('end_date', [
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', false]
                ]
            ])
            ->add('current', 'ruleValidateNeeded', [
                'rule' => ['validateNeeded', 'current', $additionalParameters],
            ]) //POCOR-8284 -- start
            ->add('name', [
                'ruleUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                    'message' => __('This field has to be unique')
                ]
            ])
            ->add('code', [
                'ruleUnique' => [
                    'rule' => 'validateUnique',
                    'provider' => 'table',
                    'message' => __('This field has to be unique')
                ] //POCOR-8284 -- ends
            ]);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        $entity->start_year = date("Y", strtotime($entity->start_date));
        $entity->end_year = date("Y", strtotime($entity->end_date));
        //POCOR-5917 starts
        if (!$entity->isNew()) { //when edit academic period
            $acedmicPeriodData = $this->find()->where([$this->aliasField('id') => $entity->id])->first();
            $entity->old_end_date = (new Date($acedmicPeriodData->end_date))->format('Y-m-d');
            $entity->old_end_year = $acedmicPeriodData->end_year;
        }
        //POCOR-5917 ends
        if ($entity->current == 1) {
            $entity->editable = 1;
            $entity->visible = 1;
            // Adding condition on updateAll(), only change the one which is not the current academic period.
            $where = [];

            if (!$entity->isNew()) {
                $where['id <> '] = $entity->id; // same with $where = [0 => 'id <> ' . $entity->id];
            }
            $this->updateAll(['current' => 0], $where);

            //POCOR-8645 start
            $query = $this->query();
            $updateResult = $query->update()
                ->set(['current' => $entity->current])
                ->where(['id' => $entity->parent_id])
                ->execute();
            //POCOR-8645 end
        }
        //POCOR-8645 start
        else {
            $condition = [
                'parent_id' => $entity->parent_id,
                'current' => 1
            ];
            if (!$entity->isNew()) {
                $condition['id != '] = $entity->id;
            }
            $academicPeriodChildData = $this->find()->where($condition)->first();
            if (empty($academicPeriodChildData)) {
                $query = $this->query();
                $updateResult = $query->update()
                    ->set(['current' => $entity->current])
                    ->where(['id' => $entity->parent_id])
                    ->execute();
            }
        }
        //POCOR-8645 end

    }


    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        //        $entity = $this->find()->select(['current'])->where($ids)->first();
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        // die silently when a non super_admin wants to delete
        if (!$this->AccessControl->isAdmin()) {
            $event->stopPropagation();
            $this->controller->redirect($this->url('index'));
        }

        // do not allow for deleting of current
        if (!empty($entity) && $entity->current == 1) {
            $event->stopPropagation();
            $this->Alert->warning('general.currentNotDeletable');
            $this->controller->redirect($this->url('index'));
        }
        //  do not allow for deleting if have associate record. //POCOR-8507
        if ($this->hasAssociatedRecords($this, $entity, $extra)) {
            $this->Alert->error('general.delete.restrictDeleteBecauseAssociation', ['reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('remove'));
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (!$this->AccessControl->isAdmin()) {
            if (isset($buttons['remove'])) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData)
    {


        //POCOR-5917 starts
        if (isset($entity->old_end_date) && !empty($entity->old_end_date) && isset($entity->old_end_year) && !empty($entity->old_end_year)) { //when edit academic period
            $academic_end_date = (new Date($entity->old_end_date))->format('Y-m-d');
            $academic_end_year = $entity->old_end_year;
            $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');

            $institutionStudentsData = $institutionStudents
                ->find()
                ->where([
                    $institutionStudents->aliasField('end_date') => $academic_end_date,
                    $institutionStudents->aliasField('end_year') => $academic_end_year,
                    $institutionStudents->aliasField('student_status_id') => 1
                ])->toArray();
            if (!empty($institutionStudentsData)) {

                foreach ($institutionStudentsData as $key => $val) {
                    $institution_students_end_date = (new Date($entity->end_date))->format('Y-m-d');
                    $institution_students_end_year = $entity->end_year;
                    $institutionStudentsEntity = $this->patchEntity($val, ['end_date' => $institution_students_end_date, 'end_year' => $institution_students_end_year], ['validate' => false]);

                    $institutionStudents->save($institutionStudentsEntity);
                }
            }
        }
        //POCOR-5917 ends
        //POCOR-6825[START] : this functionality is moved to Administrations > Data management >Copy

        // $canCopy = $this->checkIfCanCopy($entity);

        // $shells = ['Infrastructure', 'Shift'];
        // if ($canCopy) {
        //     // only trigger shell to copy data if is not empty
        //     if ($entity->has('copy_data_from') && !empty($entity->copy_data_from)) {
        //         $copyFrom = $entity->copy_data_from;
        //         $copyTo = $entity->id;
        //         foreach ($shells as $shell) {
        //             $this->triggerCopyShell($shell, $copyFrom, $copyTo);
        //         }
        //     }
        // }

        //POCOR-6825[END]
        if ($entity->getDirty('current')) { //check whether default value has been changed
            if ($entity->current) {
                $this->triggerUpdateInstitutionShiftTypeShell($entity->id);
            }
        }

        $broadcaster = $this;
        $listeners = [];
        $listeners[] = TableRegistry::getTableLocator()->get('Institution.InstitutionLands');
        $listeners[] = TableRegistry::getTableLocator()->get('Institution.InstitutionBuildings');
        $listeners[] = TableRegistry::getTableLocator()->get('Institution.InstitutionFloors');
        $listeners[] = TableRegistry::getTableLocator()->get('Institution.InstitutionRooms');

        if (!empty($listeners)) {
            $this->dispatchEventToModels('Model.AcademicPeriods.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $parentId = !is_null($this->request->getQuery('parent')) ? $this->request->getQuery('parent') : null;
        if ($parentId != null) {
            $query->where([$this->aliasField('parent_id') => $parentId]);
        } else {
            $query->where([$this->aliasField('parent_id') . ' IS NULL']);
        }
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {

        $this->addAfterSave($event, $entity, $requestData);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //        $this->log('before', 'debug');
        $this->field('academic_period_level_id');
        $this->fields['start_year']['visible'] = false;
        $this->fields['end_year']['visible'] = false;
        $this->fields['school_days']['visible'] = false;
        $this->fields['lft']['visible'] = false;
        $this->fields['rght']['visible'] = false;
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        //        $this->log('after', 'debug');
        $this->field('current');
        //        $this->field('copy_data_from', [
        //            'type' => 'hidden',
        //            'value' => 0,
        //            'after' => 'current'
        //        ]);
        $this->field('editable');
        foreach ($this->_fieldOrder as $key => $value) {
            if (!in_array($value, array_keys($this->fields))) {
                unset($this->_fieldOrder[$key]);
            }
        }
        $this->setFieldOrder($this->_fieldOrder);
    }

    public function editBeforeQuery(EventInterface $event, Query $query)
    {
        $query->contain('Levels');
    }

    public function editAfterAction(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
        $this->request->getData($this->getAlias())['current'] = $entity->current;
        $this->field('visible');

        // set academic_period_level_id to not editable to prevent any classes/subjects to not in Year level
        $this->fields['academic_period_level_id']['type'] = 'readonly';
        $this->fields['academic_period_level_id']['value'] = $entity->academic_period_level_id;
        $this->fields['academic_period_level_id']['attr']['value'] = $entity->level->name;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('academic_order', ['visible' => false]);
        $toolbarElements = [
            ['name' => 'AcademicPeriod.breadcrumb', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $this->fields['parent_id']['visible'] = false;

        $parentId = !is_null($this->request->getQuery('parent')) ? $this->request->getQuery('parent') : 0;
        if ($parentId != 0) {
            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();
            $this->controller->set('crumbs', $crumbs);
        } else {
            $results = $this
                ->find('all')
                ->select([$this->aliasField('id')])
                ->where([$this->aliasField('parent_id') => 0])
                ->all();

            if ($results->count() == 1) {
                $parentId = $results
                    ->first()
                    ->id;

                $action = $this->url('index');
                $action['?']['parent'] = $parentId; //POCOR-8074-4
                return $this->controller->redirect($action);
            }
        }
    }

    public function indexBeforePaginate(EventInterface $event, ServerRequest $request, Query $query, ArrayObject $options)
    {
        $parentId = !is_null($this->request->getQuery('parent')) ? $this->request->getQuery('parent') : 0;
        $query->where([$this->aliasField('parent_id') => $parentId]);
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        //Setup fields
        $this->_fieldOrder = ['academic_period_level_id', 'code', 'name'];

        $this->fields['parent_id']['type'] = 'hidden';
        $parentId = $this->request->getQuery('parent');

        if (is_null($parentId)) {
            $this->fields['parent_id']['attr']['value'] = -1;
        } else {
            $this->fields['parent_id']['attr']['value'] = $parentId;

            $crumbs = $this
                ->find('path', ['for' => $parentId])
                ->order([$this->aliasField('lft')])
                ->toArray();

            $parentPath = '';
            foreach ($crumbs as $crumb) {
                $parentPath .= $crumb->name;
                $parentPath .= $crumb === end($crumbs) ? '' : ' > ';
            }

            $this->fields['parent']['type'] = 'readonly';
            $this->fields['parent']['attr']['value'] = $parentPath;

            array_unshift($this->_fieldOrder, 'parent');
        }
    }

    public function triggerUpdateInstitutionShiftTypeShell($params)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateInstitutionShiftType ' . $params;
        $logs = ROOT . DS . 'logs' . DS . 'UpdateInstitutionShiftType.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function onGetCurrent(EventInterface $event, Entity $entity)
    {
        return $entity->current == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    // For PHPOE-1916
    public function onGetEditable(EventInterface $event, Entity $entity)
    {
        return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    // End PHPOE-1916

    public function onGetName(EventInterface $event, Entity $entity)
    {
        return $event->getSubject()->HtmlField->link($entity->name, [
            'plugin' => $this->controller->getPlugin(),
            'controller' => $this->controller->getName(),
            'action' => $this->alias,
            'index',
            'parent' => $entity->id
        ]);
    }

    public function onUpdateFieldAcademicPeriodLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $parentId = !is_null($this->request->getQuery('parent')) ? $this->request->getQuery('parent') : 0;
        $results = $this
            ->find()
            ->select([$this->aliasField('academic_period_level_id')])
            ->where([$this->aliasField('id') => $parentId])
            ->all();

        $attr['type'] = 'select';
        if (!$results->isEmpty()) {
            $data = $results->first();
            $levelId = $data->academic_period_level_id;

            $levelResults = $this->Levels
                ->find()
                ->select([$this->Levels->aliasField('level')])
                ->where([$this->Levels->aliasField('id') => $levelId])
                ->all();

            if (!$levelResults->isEmpty()) {
                $levelData = $levelResults->first();
                $level = $levelData->level;

                $levelOptions = $this->Levels
                    ->find('list')
                    ->where([$this->Levels->aliasField('level >') => $level])
                    ->toArray();
                $attr['options'] = $levelOptions;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCurrent(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        $attr['onChangeReload'] = 'changeCurrent';

        return $attr;
    }

    public function onUpdateFieldCopyDataFrom(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('academic_period_level_id', $request->getData()[$this->getAlias()])) {
                    $academicPeriodLevelId = $request->getData()[$this->getAlias()]['academic_period_level_id'];
                    $level = $this->Levels
                        ->find()
                        ->order([$this->Levels->aliasField('level ASC')])
                        ->first();
                    $current = $request->getQuery('current');

                    if (!is_null($current) && $current == 1) {
                        $where = [$this->aliasField('academic_period_level_id') => $level->id];
                        if (array_key_exists('id', $request->getData()[$this->getAlias()]) && !empty($request->getData()[$this->getAlias()]['id'])) {
                            $currentAcademicPeriodId = $request->getData()[$this->getAlias()]['id'];
                            $currentAcademicPeriodOrder = $this->get($currentAcademicPeriodId)->order;
                            $where[$this->aliasField('id <>')] = $currentAcademicPeriodId;
                            $where[$this->aliasField('order >')] = $currentAcademicPeriodOrder;
                        }

                        $copyDataFromOptions = $this
                            ->find('list')
                            ->find('order')
                            ->where($where)
                            ->toArray();

                        $attr['type'] = 'select';
                        $attr['options'] = $copyDataFromOptions;
                        $attr['select'] = false;
                    }
                }
            }
        }

        return $attr;
    }

    public function onUpdateFieldEditable(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData()[$this->getAlias()]['current'])) {
            if ($request->getData()[$this->getAlias()]['current'] == 1) {
                $attr['type'] = 'hidden';
            }
        }
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    public function onUpdateFieldVisible(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($request->getData()[$this->getAlias()]['current'])) {
            if ($request->getData()[$this->getAlias()]['current'] == 1) {
                $attr['type'] = 'hidden';
            }
        }
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    public function addEditOnChangeCurrent(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        //unset($request->getQuery('current'));

        $queryParams = $request->getQuery();
        unset($queryParams['current']);
        $request = $request->withQueryParams($queryParams);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('current', $request->getData($this->getAlias()))) {
                    $currentValue = $request->getData($this->getAlias())['current'];
                    $request = $request->withQueryParams(['current' => $currentValue]);
                }
            }
        }
    }

    public function getYearList($params = [])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $withLevels = isset($params['withLevels']) ? $params['withLevels'] : false;
        $isEditable = isset($params['isEditable']) ? $params['isEditable'] : null;

        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();

        $data = $this
            ->find('list')
            ->find('years')
            // ->find('editable', ['isEditable' => $isEditable])
            ->where($conditions)
            ->toArray();

        if (!$withLevels) {
            $list = $data;
        } else {
            $list[$level->name] = $data;
        }

        return $list;
    }

    public function getArchivedYearList($academicPeriod, $params = [])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $withLevels = isset($params['withLevels']) ? $params['withLevels'] : false;
        $isEditable = isset($params['isEditable']) ? $params['isEditable'] : null;
        // POCOR-7895: start
        if (empty($academicPeriod)) {
            $academicPeriod = [-1];
        }
        // POCOR-7895: end
        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();
        $where = [
            $this->aliasField('current !=') => 1,
            $this->aliasField('id IN') => $academicPeriod
        ];


        $data = $this
            ->find('list')
            ->where($where)
            ->toArray();

        if (!$withLevels) {
            $list = $data;
        } else {
            $list[$level->name] = $data;
        }

        return $list;
    }

    public function findSchoolAcademicPeriod(Query $query, array $options)
    {
        $query
            ->find('visible')
            ->find('years')
            ->find('editable', ['isEditable' => true])
            ->find('order')
            ->where([
                $this->aliasField('parent_id') . ' <> ' => 0
            ]);

        return $query;
    }

    public function findSchoolAcademicPeriodArchive(Query $query, array $options)
    {
        $currentYear = date('Y');
        $query
            ->find('years')
            ->where([
                $this->aliasField('start_year <> ') => $currentYear
            ]);
        // echo "<pre>";print_r($query->sql());die;
        return $query;
    }

    public function findAcademicPeriodArchive(Query $query, array $options)
    {
        $currentYear = date('Y');
        return $query
            ->where([$this->aliasField('current <>') => 1, $this->aliasField('start_year <') => $currentYear])
            ->formatResults(function ($results) {
                $results = $results->toArray();
                $returnArr = [];
                foreach ($results as $result) {
                    $returnArr[] = ['id' => $result['id'], 'name' => $result['name']];
                }
                return $returnArr;
            });
    }

    /**
     * @param Query $query
     * @param array $options
     * @return Query
     */
    public function findAcademicPeriodStaffAttendanceArchived(Query $query, array $options)
    {
        //        $this->log('findAcademicPeriodStaffAttendanceArchived', 'debug');
        //        $this->log($options, 'debug');
        $academicPeriodStaffAttendanceArrayId = [0];
        $academicPeriodStaffAttendanceArray = ArchiveConnections::getArchiveYears(
            'institution_staff_attendances',
            ['institution_id' => $options['institution_id']]
        );
        $academicPeriodStaffLeaveArray = ArchiveConnections::getArchiveYears(
            'institution_staff_leave',
            ['institution_id' => $options['institution_id']]
        );
        $academicPeriodStaffAttendanceArray = array_unique(
            array_merge(
                $academicPeriodStaffAttendanceArray,
                $academicPeriodStaffLeaveArray
            )
        );
        if (sizeof($academicPeriodStaffAttendanceArray) > 0) {
            $academicPeriodStaffAttendanceArrayId = $academicPeriodStaffAttendanceArray;
        }
        //        $this->log('$academicPeriodStaffAttendanceArchived', 'debug');
        //        $this->log("$academicPeriodStaffAttendanceArray", 'debug');
        $where = [
            $this->aliasField('current !=') => 1,
            $this->aliasField('id IN') => $academicPeriodStaffAttendanceArrayId
        ];
        return $query->where($where);
    }

    public function getList($params = [])
    {
        //POCOR-8480 starts
        if (!is_array($params)) {
            $params = [];
        } //POCOR-8480 ends
        $withLevels = isset($params['withLevels']) ? $params['withLevels'] : true;
        $withSelect = isset($params['withSelect']) ? $params['withSelect'] : false;
        $isEditable = isset($params['isEditable']) ? $params['isEditable'] : null;
        $restrictLevel = isset($params['restrictLevel']) ? $params['restrictLevel'] : null;

        if (!$withLevels) {
            $where = [
                $this->aliasField('current') => 1,
                $this->aliasField('parent_id') . ' <> ' => 0
            ];

            if (!empty($restrictLevel)) {
                $where['academic_period_level_id IN '] = $restrictLevel;
            }

            // get the current period
            $data = $this->find('list')
                ->find('visible')
                ->find('order')
                ->where($where)
                ->toArray();

            // get all other periods
            $where[$this->aliasField('current')] = 0;
            $data += $this->find('list')
                ->find('visible')
                ->find('editable', ['isEditable' => $isEditable])
                ->find('order')
                ->where($where)
                ->toArray();
        } else {
            $where = [
                $this->aliasField('parent_id') . ' <> ' => 0,
            ];

            if (!empty($restrictLevel)) {
                $where['academic_period_level_id IN '] = $restrictLevel;
            }

            // get the current period
            $data = $this->find()
                ->find('visible')
                ->find('editable', ['isEditable' => $isEditable])
                ->contain(['Levels'])
                ->where($where)
                ->order([$this->aliasField('academic_period_level_id'), $this->aliasField('order')])
                ->toArray();

            $levelName = "";
            $list = [];

            foreach ($data as $key => $obj) {
                if ($levelName != $obj->level->name) {
                    $levelName = __($obj->level->name);
                }

                $list[$levelName][$obj->id] = __($obj->name);
            }

            $data = $list;
        }

        if ($withSelect) {
            $data = ['' => '-- ' . __('Select Period') . ' --'] + $data;
        }

        return $data;
    }

    public function findEditable(Query $query, array $options)
    {
        $isEditable = isset($options['isEditable']) ? $options['isEditable'] : null;
        if (is_null($isEditable)) {
            return $query;
        } else {
            return $query->where([$this->aliasField('editable') => (bool)$isEditable]);
        }
    }

    public function getDate($dateObject)
    {
        if (is_object($dateObject)) {
            return $dateObject->toDateString();
        }
        return false;
    }

    public function getWorkingDaysOfWeek()
    {
        // $weekdays = [
        //  0 => __('Sunday'),
        //  1 => __('Monday'),
        //  2 => __('Tuesday'),
        //  3 => __('Wednesday'),
        //  4 => __('Thursday'),
        //  5 => __('Friday'),
        //  6 => __('Saturday'),
        // ];

        $weekdays = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
        $week = [];
        for ($i = 0; $i < $daysPerWeek; $i++) {
            $week[] = $weekdays[$firstDayOfWeek++];
            $firstDayOfWeek = $firstDayOfWeek % 7;
        }
        return $week;
    }

    public function getAttendanceWeeks($id)
    {
        // $weekdays = array(
        //  0 => 'sunday',
        //  1 => 'monday',
        //  2 => 'tuesday',
        //  3 => 'wednesday',
        //  4 => 'thursday',
        //  5 => 'friday',
        //  6 => 'saturday',
        //  //7 => 'sunday'
        // );

        $period = $this->findById($id)->first();
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');

        // If First of week is sunday changed the value to 7, because sunday with the '0' value unable to be displayed
        if ($firstDayOfWeek == 0) {
            $firstDayOfWeek = 7;
        }

        $daysPerWeek = $ConfigItems->value('days_per_week');

        // If last day index is '0'-valued-sunday it will change the value to '7' so it will be displayed.
        $lastDayIndex = ($firstDayOfWeek - 1); // last day index always 1 day before the starting date.
        if ($lastDayIndex == 0) {
            $lastDayIndex = 7;
        }

        $startDate = $period->start_date;

        $weekIndex = 1;
        $weeks = [];

        do {
            $endDate = $startDate->copy()->next($lastDayIndex);
            if ($endDate->greaterThan($period->end_date)) {
                $endDate = $period->end_date;
            }
            $weeks[$weekIndex++] = [$startDate, $endDate];
            $startDate = $endDate->copy()->addDay(); //POCOR-9544: FrozenDate is immutable — capture addDay() return value to prevent week overlap
        } while ($endDate->lessThan($period->end_date));

        return $weeks;
    }

    public function getDateFrom($id)
    {
        $period = $this->findById($id)->first();
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');

        // If First of week is sunday changed the value to 7, because sunday with the '0' value unable to be displayed
        if ($firstDayOfWeek == 0) {
            $firstDayOfWeek = 7;
        }

        $daysPerWeek = $ConfigItems->value('days_per_week');

        // If last day index is '0'-valued-sunday it will change the value to '7' so it will be displayed.
        $lastDayIndex = ($firstDayOfWeek - 1); // last day index always 1 day before the starting date.
        if ($lastDayIndex == 0) {
            $lastDayIndex = 7;
        }

        $startDate = $period->start_date;

        $weekIndex = 1;
        $weeks = [];

        $endDate = clone $startDate;

        while ($endDate <= $period->end_date) {
            $weeks[$weekIndex++] = [$startDate];
            $startDate = clone $endDate;
            $startDate = $startDate->addDay();
            $endDate = clone $startDate;
        }

        // do {
        //     $endDate = $startDate->copy();
        //     if ($endDate->gt($period->end_date)) {
        //         $endDate = $period->end_date;
        //     }
        //     $weeks[$weekIndex++] = [$startDate];
        //     $startDate = $endDate->copy();
        //     $startDate->addDay();
        // } while ($endDate->lt($period->end_date));

        return $weeks;
    }

    public function getEditable($academicPeriodId)
    {
        try {
            return $this->get($academicPeriodId)->editable;
        } catch (RecordNotFoundException $e) {
            return false;
        }
    }

    public function getAvailableAcademicPeriods($list = true, $order = 'DESC')
    {
        if ($list) {
            $query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        } else {
            $query = $this->find();
        }
        $result = $query->where([
            $this->aliasField('editable') => 1,
            $this->aliasField('visible') . ' >' => 0,
            $this->aliasField('parent_id') . ' >' => 0
        ])
            ->order($this->aliasField('name') . ' ' . $order);
        if ($result) {
            return $result->toArray();
        } else {
            return false;
        }
    }

    //POCOR-6347 starts
    public function getAvailableAcademicPeriodsById($id, $list = true, $order = 'DESC')
    {
        if ($list) {
            $query = $this->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        } else {
            $query = $this->find();
        }
        $result = $query->where([
            $this->aliasField('editable') => 1,
            $this->aliasField('visible') . ' >' => 0,
            $this->aliasField('parent_id') . ' >' => 0,
            $this->aliasField('id') => $id
        ])
            ->order($this->aliasField('name') . ' ' . $order);
        if ($result) {
            return $result->toArray();
        } else {
            return false;
        }
    } //POCOR-6347 ends

    public function getCurrent()
    {
        $query = $this->find()
            ->select([$this->aliasField('id')])
            ->where([
                $this->aliasField('editable') => 1,
                $this->aliasField('visible') . ' > 0',
                $this->aliasField('current') => 1,
                $this->aliasField('parent_id') . ' > 0',
            ])
            ->order(['start_date DESC']);
        $countQuery = $query->count();
        if ($countQuery > 0) {
            $result = $query->first();
            return $result->id;
        } else {
            $query = $this->find()
                ->select([$this->aliasField('id')])
                ->where([
                    $this->aliasField('editable') => 1,
                    $this->aliasField('visible') . ' > 0',
                    $this->aliasField('parent_id') . ' > 0',
                ])
                ->order(['start_date DESC']);
            $countQuery = $query->count();
            if ($countQuery > 0) {
                $result = $query->first();
                return $result->id;
            } else {
                return 0;
            }
        }
    }

    public function generateMonthsByDates($startDate, $endDate)
    {
        $result = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        // $stampToday = strtotime(date('Y-m-d'));

        $stampFirstDayOfMonth = strtotime('01-' . date('m', $stampStartDay) . '-' . date('Y', $stampStartDay));
        // while($stampFirstDayOfMonth <= $stampEndDay && $stampFirstDayOfMonth <= $stampToday){
        while ($stampFirstDayOfMonth <= $stampEndDay) {
            $monthString = date('F', $stampFirstDayOfMonth);
            $monthNumber = date('m', $stampFirstDayOfMonth);
            $year = date('Y', $stampFirstDayOfMonth);

            $result[] = [
                'month' => ['inNumber' => $monthNumber, 'inString' => $monthString],
                'year' => $year
            ];

            $stampFirstDayOfMonth = strtotime('+1 month', $stampFirstDayOfMonth);
        }

        return $result;
    }

    public function generateDaysOfMonth($year, $month, $startDate, $endDate)
    {
        $days = [];
        $stampStartDay = strtotime($startDate);
        $stampEndDay = strtotime($endDate);
        // $stampToday = strtotime(date('Y-m-d'));

        $stampFirstDayOfMonth = strtotime($year . '-' . $month . '-01');
        $stampFirstDayNextMonth = strtotime('+1 month', $stampFirstDayOfMonth);

        if ($stampFirstDayOfMonth <= $stampStartDay) {
            $tempStamp = $stampStartDay;
        } else {
            $tempStamp = $stampFirstDayOfMonth;
        }
        // while($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth && $tempStamp < $stampToday){
        while ($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth) {

            $weekDay = date('l', $tempStamp);
            $date = date('Y-m-d', $tempStamp);
            $day = date('d', $tempStamp);

            $dateObj = new Date($tempStamp);
            $dayFormat = __($dateObj->format('l')) . ' (' . $this->formatDate($dateObj) . ') ';

            $days[] = [
                'weekDay' => $weekDay,
                'date' => $date,
                'day' => $day,
                'dayFormat' => $dayFormat
            ];

            $tempStamp = strtotime('+1 day', $tempStamp);
        }

        return $days;
    }

    public function findYears(Query $query, array $options)
    {
        $level = $this->Levels
            ->find()
            ->order([$this->Levels->aliasField('level ASC')])
            ->first();

        return $query
            ->find('visible')
            ->find('order')
            ->where([$this->aliasField('academic_period_level_id') => $level->id]);
    }

    public function findWeeklist(Query $query, array $options)
    {
        $model = $this;

        $query->formatResults(function (ResultSetInterface $results) use ($model) {
            return $results->map(function ($row) use ($model) {
                $academicPeriodId = $row->id;

                $todayDate = date("Y-m-d");
                $weekOptions = [];

                $weeks = $model->getAttendanceWeeks($academicPeriodId);
                $weekStr = __('Week') . ' %d (%s - %s)';
                $currentWeek = null;

                foreach ($weeks as $index => $dates) {
                    $startDay = $dates[0]->format('Y-m-d');
                    $endDay = $dates[1]->format('Y-m-d');
                    $weekAttr = [];
                    if ($todayDate >= $startDay && $todayDate <= $endDay) {
                        $weekStr = __('Current Week') . ' %d (%s - %s)';
                        $weekAttr['current'] = true;
                        $currentWeek = $index;
                    } else {
                        $weekStr = __('Week') . ' %d (%s - %s)';
                    }

                    $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                    $weekAttr['start_day'] = $startDay;
                    $weekAttr['end_day'] = $endDay;
                    $weekOptions[$index] = $weekAttr;
                }

                $row->weeks = $weekOptions;

                return $row;
            });
        });
    }

    //POCOR-6825[START] : unwanted method for this model
    // private function checkIfCanCopy(Entity $entity)
    // {
    //     $canCopy = false;

    //     $level = $this->Levels
    //         ->find()
    //         ->order([$this->Levels->aliasField('level ASC')])
    //         ->first();

    //     // if is year level and set to current
    //     if ($entity->academic_period_level_id == $level->id && $entity->current == 1) {
    //         $canCopy = true;
    //     }

    //     return $canCopy;
    // }

    public function triggerCopyShell($shellName, $copyFrom, $copyTo)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $shellName . ' ' . $copyFrom . ' ' . $copyTo;
        $logs = ROOT . DS . 'logs' . DS . $shellName . '_copy.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function getLatest()
    {
        $query = $this->find()
            ->select([$this->aliasField('id')])
            ->where([
                $this->aliasField('editable') => 1,
                $this->aliasField('visible') . ' > 0',
                $this->aliasField('parent_id') . ' > 0',
                $this->aliasField('academic_period_level_id') => 1
            ])
            ->order(['start_date DESC']);
        $countQuery = $query->count();
        if ($countQuery > 0) {
            $result = $query->first();
            return $result->id;
        } else {
            return 0;
        }
    }

    public function getAcademicPeriodId($startDate, $endDate)
    {
        // get the academic period id from startDate and endDate (e.g. delete the absence records not showing the academic period id)
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');

        $academicPeriod = $this->find()
            ->where([
                $this->aliasField('start_date') . ' <= ' => $startDate,
                $this->aliasField('end_date') . ' >= ' => $endDate,
                $this->aliasField('code') . ' <> ' => 'all'
            ])
            ->first();

        $academicPeriodId = $academicPeriod->id;

        return $academicPeriodId;
    }

    public function getAcademicPeriodIdByDate($date)
    {
        // get the academic period id from date
        $date = $date->format('Y-m-d');

        $academicPeriod = $this->find()
            ->where([
                $this->aliasField('start_date') . ' <= ' => $date,
                $this->aliasField('end_date') . ' >= ' => $date,
                $this->aliasField('code') . ' <> ' => 'all'
            ])
            ->first();

        $academicPeriodId = $academicPeriod->id;

        return $academicPeriodId;
    }

    public function getMealWeeksForPeriod($academicPeriodId)
    {
        $model = $this;
        $query = $this->AcademicPeriods->find()
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->all();


        $todayDate = date("Y-m-d");
        $weekOptions = [];
        $selectedIndex = 0;

        $weeks = $model->getAttendanceWeeks($academicPeriodId);

        $weekStr = __('Week') . ' %d (%s - %s)';
        $currentWeek = null;

        foreach ($weeks as $index => $dates) {
            $startDay = $dates[0]->format('Y-m-d');
            $endDay = $dates[1]->format('Y-m-d');
            $weekAttr = [];
            if ($todayDate >= $startDay && $todayDate <= $endDay) {
                $weekStr = __('Current Week') . ' %d (%s - %s)';
                // $weekAttr['selected'] = true;
                $currentWeek = $index;
            } else {
                $weekStr = __('Week') . ' %d (%s - %s)';
            }

            $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
            $weekAttr['start_day'] = $startDay;
            $weekAttr['end_day'] = $endDay;
            $weekAttr['id'] = $index;
            $weekOptions[] = $weekAttr;

            if ($todayDate >= $startDay && $todayDate <= $endDay) {
                end($weekOptions);
                $selectedIndex = key($weekOptions);
            }
        }

        $weekOptions[$selectedIndex]['selected'] = true;


        return $weekOptions;
    }

    public function findWeeksForPeriod(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $model = $this;

        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($model) {
                return $results->map(function ($row) use ($model) {
                    $academicPeriodId = $row->id;

                    $todayDate = date("Y-m-d");
                    $weekOptions = [];
                    $selectedIndex = 0;

                    $weeks = $model->getAttendanceWeeks($academicPeriodId);
                    $weekStr = __('Week') . ' %d (%s - %s)';
                    $currentWeek = null;

                    foreach ($weeks as $index => $dates) {
                        $startDay = $dates[0]->format('Y-m-d');
                        $endDay = $dates[1]->format('Y-m-d');
                        $weekAttr = [];
                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            $weekStr = __('Current Week') . ' %d (%s - %s)';
                            // $weekAttr['selected'] = true;
                            $currentWeek = $index;
                        } else {
                            $weekStr = __('Week') . ' %d (%s - %s)';
                        }

                        $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                        $weekAttr['start_day'] = $startDay;
                        $weekAttr['end_day'] = $endDay;
                        $weekAttr['id'] = $index;
                        $weekOptions[] = $weekAttr;

                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            end($weekOptions);
                            $selectedIndex = key($weekOptions);
                        }
                    }

                    $weekOptions[$selectedIndex]['selected'] = true;
                    $row->weeks = $weekOptions;

                    return $row;
                });
            });
    }

    /**
     * POCOR-7908
     * @param Query $query
     * @param array $options
     * @return array|Query
     *
     */
    public function findWeeksForPeriodMeal(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $model = $this;
        $todayDate = date("Y-m-d");
        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($model, $todayDate) {
                return $results->map(function ($row) use ($model, $todayDate) {
                    $academicPeriodId = $row->id;
                    $weekOptions = [];
                    $selectedIndex = 0;
                    $weeks = $model->getAttendanceWeeks($academicPeriodId);
                    $currentIndex = null; // Initialize a variable to store the current week index

                    foreach ($weeks as $index => $dates) {
                        $startDay = $dates[0]->format('Y-m-d');
                        $endDay = $dates[1]->format('Y-m-d');
                        $weekAttr = [];

                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            $weekStr = __('Current Week') . ' %d (%s - %s)';
                            // Store the index of the current week
                            $currentIndex = $index;
                            end($weekOptions);
                            $selectedIndex = key($weekOptions) + 1;
                        } else {
                            $weekStr = __('Week') . ' %d (%s - %s)';
                        }

                        $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                        $weekAttr['start_day'] = $startDay;
                        $weekAttr['end_day'] = $endDay;
                        $weekAttr['id'] = $index;
                        $weekOptions[] = $weekAttr;

                        // Check if the current week is found and break the loop
                        if ($currentIndex !== null) {
                            break;
                        }
                    }


                    $weekOptions[$selectedIndex]['selected'] = true;
                    $row->weeks = $weekOptions;

                    return $row;
                });
            });
    }

    public function findWeeksForPeriodStaffAttendanceArchived(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $institutionId = $options['institution_id'];
        $model = $this;
        $distinctDateValues = ArchiveConnections::getArchiveDays(
            'institution_staff_attendances',
            [
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId
            ]
        );
        $distinctLeaveDateValues = ArchiveConnections::getArchiveLeaveDays(
            'institution_staff_leave',
            [
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId
            ]
        );
        //        $this->log('$distinctDateValues', 'debug');
        //        $this->log($distinctDateValues, 'debug');
        //        $this->log('$distinctLeaveDateValues', 'debug');
        //        $this->log($distinctLeaveDateValues, 'debug');
        $mergedArray = array_unique(
            array_merge(
                $distinctDateValues,
                $distinctLeaveDateValues
            )
        );
        //        $this->log('$mergedArray', 'debug');
        //        $this->log($mergedArray, 'debug');
        // Convert the strings back to DateTime objects
        $finalArray = array_map(function ($dateString) {
            return new Date($dateString);
        }, $mergedArray);
        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($model, $finalArray) {
                return $results->map(function ($row) use ($model, $finalArray) {
                    $academicPeriodId = $row->id;

                    $todayDate = date("Y-m-d");
                    $weekOptions = [];
                    $selectedIndex = 0;

                    $weeks = $model->getAttendanceWeeks($academicPeriodId);
                    $weekStr = __('Week') . ' %d (%s - %s)';
                    $currentWeek = null;

                    foreach ($weeks as $index => $dates) {
                        $startDay = $dates[0]->format('Y-m-d');
                        $endDay = $dates[1]->format('Y-m-d');
                        $weekAttr = [];
                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            $weekStr = __('Current Week') . ' %d (%s - %s)';
                            // $weekAttr['selected'] = true;
                            $currentWeek = $index;
                        } else {
                            $weekStr = __('Week') . ' %d (%s - %s)';
                        }
                        $weekAttr['name'] = sprintf($weekStr, $index, $this->formatDate($dates[0]), $this->formatDate($dates[1]));
                        $weekAttr['start_day'] = $startDay;
                        $weekAttr['end_day'] = $endDay;
                        $weekAttr['id'] = $index;

                        foreach ($finalArray as $distinctDateValue) {
                            if ($distinctDateValue >= $dates[0] && $distinctDateValue <= $dates[1]) {
                                $weekOptions[] = $weekAttr;
                            }
                        }

                        $uniqueWeekOptions = [];
                        $ids = [];

                        foreach ($weekOptions as $subArray) {
                            $id = $subArray['id'];
                            if (!in_array($id, $ids)) {
                                $ids[] = $id;
                                $uniqueWeekOptions[] = $subArray;
                            }
                        }


                        //                        $this->log('$uniqueWeekOptions', 'debug');
                        //
                        //                        $this->log($uniqueWeekOptions, 'debug');

                        if ($todayDate >= $startDay && $todayDate <= $endDay) {
                            end($uniqueWeekOptions);
                            $selectedIndex = key($uniqueWeekOptions);
                        }
                    }
                    $uniqueWeekOptions[$selectedIndex]['selected'] = true;
                    $row->weeks = $uniqueWeekOptions;

                    return $row;
                });
            });
    }

    public function findPeriodHasClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $currentYearId = $this->getCurrent();

        return $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name')
            ])
            ->find('years')
            ->matching('InstitutionClasses', function ($q) use ($institutionId) {
                return $q->where(['InstitutionClasses.institution_id' => $institutionId]);
            })
            ->group([$this->aliasField('id')])
            ->formatResults(function (ResultSetInterface $results) use ($currentYearId) {
                return $results->map(function ($row) use ($currentYearId) {
                    if ($row->id == $currentYearId) {
                        $row->selected = true;
                    }
                    return $row;
                });
            });
    }

    /**
     * @param Query $query
     * @param array $options
     * @return Query
     * @throws \Exception
     */
    public function findPeriodHasClassArchived(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $institutionClassIds = $this->getInstitutionClasses($institutionId);
        $academicPeriodArrayOne =
            ArchiveConnections::getArchiveYears(
                'institution_class_attendance_records',
                ['institution_class_id IN' => $institutionClassIds]
            );
        $academicPeriodArrayTwo =
            ArchiveConnections::getArchiveYears(
                'institution_student_absences',
                ['institution_id' => $institutionId]
            );
        $academicPeriodArrayThree =
            ArchiveConnections::getArchiveYears(
                'institution_student_absence_details',
                ['institution_id' => $institutionId]
            );
        $academicPeriodArrayFour =
            ArchiveConnections::getArchiveYears(
                'student_attendance_marked_records',
                ['institution_id' => $institutionId]
            );

        $academicPeriodWithArchiveArrayId = [0];
        $academicPeriodWithArchiveArray = array_unique(
            array_merge(
                $academicPeriodArrayOne,
                $academicPeriodArrayTwo,
                $academicPeriodArrayThree,
                $academicPeriodArrayFour
            )
        );
        if (sizeof($academicPeriodWithArchiveArray) > 0) {
            $academicPeriodWithArchiveArrayId = $academicPeriodWithArchiveArray;
        }
        //        $this->log('$academicPeriodWithArchiveArrayId', 'debug');
        //        $this->log($academicPeriodWithArchiveArrayId, 'debug');
        $where = [
            $this->aliasField('current !=') => 1,
            $this->aliasField('id IN') => $academicPeriodWithArchiveArrayId
        ];
        return $query->where($where);
    }

    /**
     * @param $institutionId
     * @return array
     */
    private function getInstitutionClasses($institutionId)
    {
        $tableClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $distinctClasses = $tableClasses->find('all')
            ->where(['institution_id' => $institutionId])
            ->select(['id'])
            ->distinct(['id'])
            ->toArray();
        $distinctClassValues = array_column($distinctClasses, 'id');
        $institutionClassIds = array_unique($distinctClassValues);
        return $institutionClassIds;
    }


    public function findWorkingDayOfWeek(Query $query, array $options)
    {
        $workingDayOfWeek = $this->getWorkingDaysOfWeek();
        \Cake\Log\Log::debug('@AcademicPeriodsTable::findWorkingDayOfWeek rawDays=' . json_encode($workingDayOfWeek)); //[TEMP-LOG]

        $dayOfWeek = [];
        foreach ($workingDayOfWeek as $index => $day) {
            $dayOfWeek[] = [
                'day_of_week' => $index + 1,
                'day' => $day
            ];
        }

        \Cake\Log\Log::debug('@AcademicPeriodsTable::findWorkingDayOfWeek output=' . json_encode($dayOfWeek)); //[TEMP-LOG]
        return $query->formatResults(function (ResultSetInterface $results) use ($dayOfWeek) {
            return $dayOfWeek;
        });
    }

    public function findDaysForPeriodWeek_old(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $current_week_number_selected = $options['current_week_number_selected']; // POCOR-6723
        $weekId = $options['week_id'];
        $institutionId = $options['institution_id'];

        // pass true if you need school closed data
        if (isset($options['school_closed_required'])) {
            $schoolClosedRequired = $options['school_closed_required'];
        } else {
            $schoolClosedRequired = false;
        }

        $model = $this;

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $weeks = $model->getAttendanceWeeks($academicPeriodId);
        $week = $weeks[$weekId];

        if (isset($options['exclude_all']) && $options['exclude_all']) {
            $dayOptions = [];
        } else {
            $dayOptions[] = [
                'id' => -1,
                'name' => __('All Days'),
                'date' => -1
            ];
        }

        $schooldays = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            // sunday should be '7' in order to be displayed
            $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
        }

        $firstDayOfWeek = $week[0];
        $today = null;
        $i = 0;

        // foreach ($week as $firstDayOfWeek) {
        //     if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
        //         if ($schoolClosedRequired == false) {
        //             $schoolClosed = false;
        //         } else {
        //             $schoolClosed = $this->isSchoolClosed($firstDayOfWeek, $institutionId);
        //             //POCOR-7787 start
        //             if ($schoolClosed) {
        //                 $connection = ConnectionManager::get('default');
        //                 $sql = "SELECT institution_shift_periods.period_id
        //                         FROM calendar_event_dates
        //                         INNER JOIN calendar_events ON calendar_events.id = calendar_event_dates.calendar_event_id
        //                         INNER JOIN institution_shifts ON calendar_events.academic_period_id = institution_shifts.academic_period_id
        //                                 AND calendar_events.institution_id = institution_shifts.institution_id
        //                                 AND calendar_events.institution_shift_id = institution_shifts.shift_option_id
        //                         INNER JOIN calendar_types ON calendar_types.id = calendar_events.calendar_type_id
        //                         INNER JOIN institution_shift_periods ON institution_shift_periods.institution_shift_period_id = institution_shifts.id
        //                         WHERE calendar_event_dates.date = '" . $firstDayOfWeek->format('Y-m-d') . "'
        //                         AND calendar_types.is_attendance_required = 0";

        //                 $result = $connection->execute($sql)->fetchAll('assoc');
        //                 $closedPeriods = [];
        //                 foreach ($result as $data) {
        //                     $closedPeriods[] = $data['period_id'];
        //                 }
        //             }
        //             //POCOR-7787 end
        //         }
        //         $suffix = $schoolClosed ? __('School Closed') : '';

        //         $data = [
        //             'id' => $firstDayOfWeek->dayOfWeek,
        //             'day' => __($firstDayOfWeek->format('l')),
        //             'name' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ') ' . $suffix,
        //             'date' => $firstDayOfWeek->format('Y-m-d'),
        //             'current_week_number_selected' => $current_week_number_selected, //POCOR-6723
        //             'day_number' => $firstDayOfWeek->isToday() //POCOR-6723
        //         ];

        //         if ($schoolClosed) {
        //             $data['closed'] = true;
        //             $data['periods'] = $closedPeriods; //POCOR-7787
        //         }

        //         $dayOptions[] = $data;

        //         if (is_null($today) || $firstDayOfWeek->isToday()) {
        //             end($dayOptions);
        //             $today = key($dayOptions);
        //         }
        //     }
        // }
        do {
            if (in_array($firstDayOfWeek->dayOfWeek, $schooldays)) {
                if ($schoolClosedRequired == false) {
                    $schoolClosed = false;
                } else {
                    $schoolClosed = $this->isSchoolClosed($firstDayOfWeek, $institutionId);
                    //POCOR-7787 start
                    if ($schoolClosed) {
                        $connection = ConnectionManager::get('default');
                        $sql = "SELECT institution_shift_periods.period_id  FROM calendar_event_dates
                            INNER JOIN calendar_events ON calendar_events.id = calendar_event_dates.calendar_event_id
                            INNER JOIN institution_shifts ON calendar_events.academic_period_id = institution_shifts.academic_period_id
                                    AND calendar_events.institution_id = institution_shifts.institution_id
                                    AND calendar_events.institution_shift_id = institution_shifts.shift_option_id
                            INNER JOIN calendar_types ON calendar_types.id = calendar_events.calendar_type_id
                            INNER JOIN institution_shift_periods ON institution_shift_periods.institution_shift_period_id = institution_shifts.id
                            WHERE calendar_event_dates.date = '" . $firstDayOfWeek->format('Y-m-d') . "' AND calendar_types.is_attendance_required = 0";

                        $result = $connection->execute($sql)->fetchAll('assoc');
                        $closedPeriods = [];
                        foreach ($result as $data) {
                            $closedPeriods[] = $data['period_id'];
                        }
                    }
                    //POCOR-7787 end
                }
                $suffix = $schoolClosed ? __('School Closed') : '';

                $data = [
                    'id' => $firstDayOfWeek->dayOfWeek,
                    'day' => __($firstDayOfWeek->format('l')),
                    'name' => __($firstDayOfWeek->format('l')) . ' (' . $this->formatDate($firstDayOfWeek) . ') ' . $suffix,
                    'date' => $firstDayOfWeek->format('Y-m-d'),
                    'current_week_number_selected' => $current_week_number_selected, //POCOR-6723
                    'day_number' => $firstDayOfWeek->isToday() //POCOR-6723
                ];

                if ($schoolClosed) {
                    $data['closed'] = true;
                    $data['periods'] = $closedPeriods; //POCOR-7787
                }

                $dayOptions[] = $data;

                if (is_null($today) || $firstDayOfWeek->isToday()) {
                    end($dayOptions);
                    $today = key($dayOptions);
                }
                if ($i == 7) {
                    break; // Exit the loop when $i reaches 7
                }
                $i++;
            }
            $firstDayOfWeek->addDay();
        } while ($firstDayOfWeek <= $week[1]);

        if (!is_null($today)) {
            $dayOptions[$today]['selected'] = true;
            $dayOptions[$today]['current_week_number_selected'] = $current_week_number_selected; //POCOR-6723
            $dayOptions[$today]['day_number'] = __($firstDayOfWeek->format('N')); //POCOR-6723
        }

        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($dayOptions) {
                return $dayOptions;
            });
    }

    public function findDaysForPeriodWeek(Query $query, array $options)
    {
        $academicPeriodId = $options['academic_period_id'];
        $currentWeekNumberSelected = $options['current_week_number_selected'];
        $weekId = $options['week_id'];
        $institutionId = $options['institution_id'];
        $schoolClosedRequired = $options['school_closed_required'] ?? false;

        $model = $this;
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = (int)$ConfigItems->value('first_day_of_week');
        $daysPerWeek = (int)$ConfigItems->value('days_per_week');
        $weeks = $model->getAttendanceWeeks($academicPeriodId);
        $week = $weeks[$weekId];

        $dayOptions = isset($options['exclude_all']) && $options['exclude_all'] ? [] : [
            [
                'id' => -1,
                'name' => __('All Days'),
                'date' => -1
            ]
        ];

        $schooldays = array_map(function ($i) use ($firstDayOfWeek) {
            return 1 + ($firstDayOfWeek + 6 + $i) % 7;
        }, range(0, $daysPerWeek - 1));

        $firstDayOfWeekDate = $week[0];
        $today = null;
        $i = 0;

        $connection = ConnectionManager::get('default');
        $schoolClosedDates = [];
        if ($schoolClosedRequired) {
            $sql = "SELECT calendar_event_dates.date, institution_shift_periods.period_id
                    FROM calendar_event_dates
                    INNER JOIN calendar_events ON calendar_events.id = calendar_event_dates.calendar_event_id
                    INNER JOIN institution_shifts ON calendar_events.academic_period_id = institution_shifts.academic_period_id
                            AND calendar_events.institution_id = institution_shifts.institution_id
                            AND calendar_events.institution_shift_id = institution_shifts.shift_option_id
                    INNER JOIN calendar_types ON calendar_types.id = calendar_events.calendar_type_id
                    INNER JOIN institution_shift_periods ON institution_shift_periods.institution_shift_period_id = institution_shifts.id
                    WHERE calendar_event_dates.date BETWEEN ? AND ?
                    AND calendar_types.is_attendance_required = 0";
            $stmt = $connection->execute($sql, [$week[0]->format('Y-m-d'), $week[1]->format('Y-m-d')]);
            $schoolClosedDates = $stmt->fetchAll('assoc');
        }

        do {
            if (in_array($firstDayOfWeekDate->dayOfWeek, $schooldays)) {
                $schoolClosed = false;
                $closedPeriods = [];
                if ($schoolClosedRequired) {
                    foreach ($schoolClosedDates as $data) {
                        if ($data['date'] == $firstDayOfWeekDate->format('Y-m-d')) {
                            $schoolClosed = true;
                            $closedPeriods[] = $data['period_id'];
                        }
                    }
                }

                $schoolClosed = $this->isSchoolClosed($firstDayOfWeekDate, $institutionId); //POCOR-8745
                $suffix = $schoolClosed ? __('School Closed') : '';

                $data = [
                    'id' => $firstDayOfWeekDate->dayOfWeek,
                    'day' => __($firstDayOfWeekDate->format('l')),
                    'name' => __($firstDayOfWeekDate->format('l')) . ' (' . $this->formatDate($firstDayOfWeekDate) . ') ' . $suffix,
                    'date' => $firstDayOfWeekDate->format('Y-m-d'),
                    'current_week_number_selected' => $currentWeekNumberSelected,
                    'day_number' => $firstDayOfWeekDate->isToday()
                ];

                if ($schoolClosed) {
                    $data['closed'] = true;
                    $data['periods'] = $closedPeriods;
                }

                $dayOptions[] = $data;

                if (is_null($today) || $firstDayOfWeekDate->isToday()) {
                    end($dayOptions);
                    $today = key($dayOptions);
                }

                if ($i++ == 7) {
                    break;
                }
            }

            $firstDayOfWeekDate = $firstDayOfWeekDate->addDay();
        } while ($firstDayOfWeekDate <= $week[1]);

        if (!is_null($today)) {
            $dayOptions[$today]['selected'] = true;
            $dayOptions[$today]['current_week_number_selected'] = $currentWeekNumberSelected;
            $dayOptions[$today]['day_number'] = __($firstDayOfWeekDate->format('N'));
        }

        return $query
            ->where([$this->aliasField('id') => $academicPeriodId])
            ->formatResults(function (ResultSetInterface $results) use ($dayOptions) {
                return $dayOptions;
            });
    }


    /**
     * POCOR-7908
     * @param Query $query
     * @param array $options
     *
     */
    public function findDaysForPeriodWeekMeal(Query $query, array $options)
    {
        $firstDay = new Date($options['week_start_day']);
        $lastDay = new Date($options['week_end_day']);
        $institutionId = $options['institution_id'];
        $today = null;
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $schooldays = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
        }
        $todayDate = new Date();
        do {
            if (in_array($firstDay->dayOfWeek, $schooldays)) { {
                    $schoolClosed = $this->isSchoolClosed($firstDay, $institutionId);
                }
                $suffix = $schoolClosed ? __('School Closed') : '';

                $data = [
                    'id' => $firstDay->dayOfWeek,
                    'day' => __($firstDay->format('l')),
                    'name' => __($firstDay->format('l')) . ' (' . $this->formatDate($firstDay) . ') ' . $suffix,
                    'date' => $firstDay->format('Y-m-d'),
                    'day_number' => $firstDay->isToday() //POCOR-6723
                ];

                if (is_null($today) || $firstDay->isToday()) {
                    end($dayOptions);
                    $today = key($dayOptions);
                }

                if ($firstDay->gte($todayDate)) {
                    $data['selected'] = true;
                    $data['day_number'] = __($firstDay->format('N')); //POCOR-6723
                    $dayOptions[] = $data;
                    $today = null;
                    break;
                } else {
                    $dayOptions[] = $data;
                }
            }
            $firstDay->addDay();
        } while ($firstDay->lte($lastDay));

        if (!is_null($today)) {
            $dayOptions[$today]['selected'] = true;
            $dayOptions[$today]['day_number'] = __($firstDay->format('N')); //POCOR-6723
        }

        $query
            ->select(['id'])
            ->limit(1)
            ->formatResults(function (ResultSetInterface $results) use ($dayOptions) {
                return $dayOptions;
            });
    }

    public function findDaysForPeriodWeekArchive(Query $query, array $options)
    {
        $firstDay = new Date($options['start_date']);
        $lastDay = new Date($options['end_date']);
        $institutionId = $options['institution_id'];
        $today = null;

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
        $daysPerWeek = $ConfigItems->value('days_per_week');
        $schooldays = [];
        for ($i = 0; $i < $daysPerWeek; ++$i) {
            // sunday should be '7' in order to be displayed
            $schooldays[] = 1 + ($firstDayOfWeek + 6 + $i) % 7;
        }
        do {
            if (in_array($firstDay->dayOfWeek, $schooldays)) { {
                    // echo "<pre>";print_r($this->isSchoolClosed($firstDay, $institutionId));die;
                    // $schoolClosed = $this->isSchoolClosed($firstDay, $institutionId);
                    $schoolClosed = false;
                }
                $suffix = $schoolClosed ? __('School Closed') : '';

                $data = [
                    'id' => $firstDay->dayOfWeek,
                    'day' => __($firstDay->format('l')),
                    'name' => __($firstDay->format('l')) . ' (' . $this->formatDate($firstDay) . ') ' . $suffix,
                    'date' => $firstDay->format('Y-m-d'),
                    'day_number' => $firstDay->isToday() //POCOR-6723
                ];

                $dayOptions[] = $data;
                if (is_null($today) || $firstDay->isToday()) {
                    end($dayOptions);
                    $today = key($dayOptions);
                }
            }
            $firstDay->addDay();
        } while ($firstDay->lte($lastDay));
        // echo json_encode($dayOptions);die;
        if (!is_null($today)) {
            $dayOptions[$today]['selected'] = true;
            $dayOptions[$today]['day_number'] = __($firstDay->format('N')); //POCOR-6723
        }

        return $query
            ->select(['id'])
            ->limit(1)
            ->formatResults(function (ResultSetInterface $results) use ($dayOptions) {
                return $dayOptions;
            });
    }

    public function getNextAcademicPeriodId($id)
    {
        $selectedPeriod = $id;
        $periodLevelId = $this->get($selectedPeriod)->academic_period_level_id;
        $startDate = $this->get($selectedPeriod)->start_date->format('Y-m-d');

        $where = [
            $this->aliasField('id <>') => $selectedPeriod,
            $this->aliasField('academic_period_level_id') => $periodLevelId,
            $this->aliasField('start_date >=') => $startDate
        ];
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $nextAcademicPeriodId = $AcademicPeriods
            ->find('visible')
            // ->find('editable', ['isEditable' => true]) V4
            ->where($where)
            ->order([$this->aliasField('order') => 'DESC'])
            ->all() // Execute the query and get a ResultSet
            ->extract('id') // Extract the 'id' values
            ->first(); // Get the first extracted value

        return $nextAcademicPeriodId;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'visible':
                return __('Visible');
            case 'current':
                return __('Current');
            case 'editable':
                return __('Editable');
            case 'code':
                return __('Code');
            case 'name':
                return __('Name');
            case 'start_date':
                return __('Start Date');
            case 'end_date':
                return __('End Date');
            case 'academic_period_level_id':
                return __('Academic Period Level');

            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
