<?php
namespace Institution\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use Cake\I18n\Date;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use App\Model\Traits\OptionsTrait;
use Institution\Controller\AppController;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\Utility\Security;
use Cake\Utility\Text;

class InstitutionsController extends AppController
{
    use OptionsTrait;
    use UtilityTrait;
    public $activeObj = null;

    private $features = [
        // general
        'InstitutionAttachments',
        'InstitutionMaps',

        // academic
        'InstitutionShifts',
        'InstitutionGrades',
        'InstitutionClasses',
        'InstitutionSubjects',
        'InstitutionTextbooks',

        // students
        'Programmes',
        'Students',
        'StudentUser',
        'StudentAccount',
        'StudentTransferIn',
        'StudentTransferOut',
        // 'Textbooks',
        // 'StudentIndexes',


        // staff
        'Staff',
        'StaffUser',
        'StaffAccount',
        'StaffLeave',
        'StaffAppraisals',
        'Associations',
        'StaffTrainingNeeds',
        'StaffTrainingApplications',
        'StaffTrainingResults',
        'StaffTransferIn',
        'StaffTransferOut',
        'StaffDuties',
        'StaffSalaries',
        // 'StaffPositionProfiles',

        // attendances
        'InstitutionStaffAttendances',
        'InstitutionStudentAbsences',
        'StudentAttendances',

        'StudentArchive',
        'AssessmentsArchive',

        'StudentMeals',


        // behaviours
        'StaffBehaviours',
        'StudentBehaviours',

        // competencies
        'StudentCompetencies',
        'StudentCompetencyComments',
        'InstitutionCompetencyResults',

        // assessments
        'Results',
        'AssessmentResults',
        // 'InstitutionAssessments',

        // indexes
        // 'Indexes',
        // 'InstitutionStudentIndexes',

        // examinations
        'ExaminationResults',
        'InstitutionExaminations',
        'InstitutionExaminationsUndoRegistration',
        'InstitutionExaminationStudents',

        // positions
        'InstitutionPositions',

        // finance
        'InstitutionBankAccounts',
        'InstitutionFees',
        'StudentFees',

        // infrastructures
        'InstitutionLands',
        'InstitutionBuildings',
        'InstitutionFloors',
        'InstitutionRooms',

        // survey
        'InstitutionSurveys',
        'InstitutionRubrics',
        'InstitutionRubricAnswers',

        // visits
        'VisitRequests',
        'InstitutionQualityVisits',

        // cases
        'InstitutionCases',

        // report card
        'ReportCardComments',
        'ReportCardStatuses',
        'InstitutionStudentsReportCards',

        // outcomes
        'StudentOutcomes',
        'ImportOutcomeResults',

        //assessment
        'ImportAssessmentItemResults',

        // misc
        // 'IndividualPromotion',
        // 'CourseCatalogue',
    ];

    public function initialize()
    {
        // Start: remove this logic after upgrading to v3.4.x
        $version = \Cake\Core\Configure::version();
        if (strpos($version, '3.4') !== false) {
            $msg = 'To change ResultsExport $response->type to $response->withType and $response->download to $response->withDownload';
            pr($msg);
            die;
        }
        // End

        parent::initialize();
        // $this->ControllerAction->model('Institution.Institutions', [], ['deleteStrategy' => 'restrict']);
        $this->ControllerAction->models = [
            'Infrastructures'   => ['className' => 'Institution.InstitutionInfrastructures', 'options' => ['deleteStrategy' => 'restrict']],
            'Staff'             => ['className' => 'Institution.Staff'],
            'StaffSalaries'     => ['className' => 'Institution.StaffSalaries'],
            'StaffAccount'      => ['className' => 'Institution.StaffAccount', 'actions' => ['view', 'edit']],

            'StudentAccount'    => ['className' => 'Institution.StudentAccount', 'actions' => ['view', 'edit']],
            'AttendanceExport'  => ['className' => 'Institution.AttendanceExport', 'actions' => ['excel']],
            'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
            'Promotion'         => ['className' => 'Institution.StudentPromotion', 'actions' => ['add']],
            'Undo'              => ['className' => 'Institution.UndoStudentStatus', 'actions' => ['view', 'add']],
            'ClassStudents'     => ['className' => 'Institution.InstitutionClassStudents', 'actions' => ['excel']],

            'BankAccounts'      => ['className' => 'Institution.InstitutionBankAccounts'],

            // Quality
            'Rubrics'           => ['className' => 'Institution.InstitutionRubrics', 'actions' => ['index', 'view', 'remove']],
            'RubricAnswers'     => ['className' => 'Institution.InstitutionRubricAnswers', 'actions' => ['view', 'edit']],

            'ImportInstitutions'        => ['className' => 'Institution.ImportInstitutions', 'actions' => ['add']],
            'ImportStaffAttendances'    => ['className' => 'Institution.ImportStaffAttendances', 'actions' => ['add']],
            'ImportStudentAttendances'  => ['className' => 'Institution.ImportStudentAttendances', 'actions' => ['add']],
            'ImportStudentMeals'  => ['className' => 'Institution.ImportStudentMeals', 'actions' => ['add']],
            'ImportInstitutionSurveys'  => ['className' => 'Institution.ImportInstitutionSurveys', 'actions' => ['add']],
            'ImportStudentAdmission'    => ['className' => 'Institution.ImportStudentAdmission', 'actions' => ['add']],
            'ImportStaff'               => ['className' => 'Institution.ImportStaff', 'actions' => ['add']],
            'ImportStaffSalaries'       => ['className' => 'Institution.ImportStaffSalaries', 'actions' => ['add']],
            'ImportInstitutionTextbooks'=> ['className' => 'Institution.ImportInstitutionTextbooks', 'actions' => ['add']],
            'ImportOutcomeResults'      => ['className' => 'Institution.ImportOutcomeResults', 'actions' => ['add']],
            'ImportCompetencyResults'   => ['className' => 'Institution.ImportCompetencyResults', 'actions' => ['add']],
            'ImportStaffLeave'          => ['className' => 'Institution.ImportStaffLeave', 'actions' => ['add']],
            'ImportInstitutionPositions'=> ['className' => 'Institution.ImportInstitutionPositions', 'actions' => ['add']],
            'ImportStudentBodyMasses'   => ['className' => 'Institution.ImportStudentBodyMasses', 'actions' => ['add']],
            'ImportStudentGuardians'   => ['className' => 'Institution.ImportStudentGuardians', 'actions' => ['add']],
            'ImportStudentExtracurriculars'   => ['className' => 'Institution.ImportStudentExtracurriculars', 'actions' => ['add']],
            'StudentArchive'  => ['className' => 'Institution.StudentArchive', 'actions' => ['add']],
            'AssessmentsArchive'  => ['className' => 'Institution.AssessmentsArchive', 'actions' => ['index']],
            'ImportAssessmentItemResults'      => ['className' => 'Institution.ImportAssessmentItemResults', 'actions' => ['add']]
        ];

        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->loadComponent('Training.Training');
        $this->loadComponent('Institution.CreateUsers');
        $this->attachAngularModules();
        $this->loadModel('Institution.StaffBodyMasses');
    }

    // CAv4
    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAttachments']);
    }

	public function Profiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Profiles']);
    }

    public function StaffAppraisals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAppraisals']);
    }

    public function Surveys()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionSurveys']);
    }

    public function Institutions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Institutions']);
    }

    public function Positions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionPositions']);
    }

    public function StaffDuties()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStaffDuties']);
    }

    public function Shifts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionShifts']);
    }
    public function Fees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionFees']);
    }
    public function InstitutionLands()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionLands']);
    }
    public function InstitutionBuildings()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBuildings']);
    }
    public function InstitutionFloors()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionFloors']);
    }
    public function InstitutionRooms()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionRooms']);
    }
    public function StudentFees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentFees']);
    }
    public function Budget()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBudgets']);
    }
    public function Income()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionIncomes']);
    }
    public function Expenditure()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExpenditures']);
    }
    public function StaffPositionProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffPositionProfiles']);
    }
    public function Assessments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAssessments']);
    }
    public function AssessmentResults()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.AssessmentResults']);
    }
    public function StudentProgrammes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Programmes']);
    }
    //POCOR-5671
    public function StudentTransition()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Transition']);
    }
     //POCOR-5671
    public function Exams()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExaminations']);
    }
    public function UndoExaminationRegistration()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExaminationsUndoRegistration']);
    }
    public function ExaminationStudents()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExaminationStudents']);
    }
    public function ExaminationResults()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ExaminationResults']);
    }
    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionContacts']);
    }
    public function InstitutionContactPersons()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionContactPersons']);
    }
    public function IndividualPromotion()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.IndividualPromotion']);
    }
    public function StudentUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentUser']);
    }
    public function StaffUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffUser']);
    }
    public function StaffTrainingResults()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingResults']);
    }
    public function StaffTrainingNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingNeeds']);
    }
    public function StaffTrainingApplications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingApplications']);
    }
    public function CourseCatalogue()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.CourseCatalogue']);
    }
    public function StaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffLeave']);
    }
    public function VisitRequests()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Quality.VisitRequests']);
    }
    public function Visits()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Quality.InstitutionQualityVisits']);
    }
    public function Programmes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionGrades']);
    }
    public function StaffBehaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffBehaviours']);
    }
    public function Textbooks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTextbooks']);
    }
    public function InstitutionCompetencyResults()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionCompetencyResults']);
    }
    public function StudentSurveys()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSurveys']);
    }
    public function StudentTextbooks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Textbooks']);
    }
    public function Risks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Risks']);
    }
    public function StudentRisks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentRisks']);
    }
    public function InstitutionStudentRisks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStudentRisks']);
    }
    public function Cases()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Cases.InstitutionCases']);
    }
    public function ReportCardComments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardComments']);
    }

    public function AssessmentItemResultsArchived()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.AssessmentItemResultsArchived']);
    }

    // public function AssessmentsArchive()
    // {
    //     if (!empty($this->request->param('institutionId'))) {
    //         $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
    //     } else {
    //         $session = $this->request->session();
    //         $institutionId = $session->read('Institution.Institutions.id');
    //     }

    //     $backUrl = [
    //         'plugin' => 'Institution',
    //         'controller' => 'Institutions',
    //         'action' => 'Assessments',
    //         'institutionId' => $institutionId,
    //         'index',
    //         $this->ControllerAction->paramsEncode(['id' => $timetableId])
    //     ];
    //     $this->set('backUrl', Router::url($backUrl));

    //     $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
    //         $this->Navigation->addCrumb($crumbTitle);
    //     $this->set('institution_id', $institutionId);
    //     $this->set('ngController', 'InstitutionAssessmentsArchiveCtrl as $ctrl');
    // }

    public function Distributions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionDistributions']);
    }
    public function ReportCardStatuses()
    {
        $classId = $this->request->query['class_id'];
        $academicPeriodId = $this->request->query['academic_period_id'];
        $reportCardId = $this->request->query['report_card_id'];

        if(!empty($classId) && $classId == 'all'){
            return $this->redirect(['action' => 'ReportCardStatusProgress',
                    'class_id' => $classId,
                    'academic_period_id' => $academicPeriodId,
                    'report_card_id' => $reportCardId
                ]);
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardStatuses']);
        }
    }

    public function ReportCardStatusProgress()
    {
        $classId = $this->request->query['class_id'];
        $academicPeriodId = $this->request->query['academic_period_id'];
        $reportCardId = $this->request->query['report_card_id'];

        if(!empty($classId) && $classId <> 'all'){
            return $this->redirect(['action' => 'ReportCardStatuses',
                    'class_id' => $classId,
                    'academic_period_id' => $academicPeriodId,
                    'report_card_id' => $reportCardId
                ]);
        } else {
           $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardStatusProgress']);
            $this->render('report_status_progress');
        }
    }

    public function InstitutionStudentsReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStudentsReportCards']);
    }
	public function InstitutionReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionReportCards']);
    }
    public function StaffTransferIn()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTransferIn']);
    }
    public function StaffTransferOut()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTransferOut']);
    }
    public function StudentAdmission()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentAdmission']);
    }
    public function BulkStudentAdmission()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.BulkStudentAdmission']);
    }
    public function StudentTransferIn()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentTransferIn']);
    }
    //POCOR-5677 start
    public function BulkStudentTransferIn()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.BulkStudentTransferIn']);
    }
    //POCOR-5677 ends
    public function StudentTransferOut()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentTransferOut']);
    }
    //POCOR-6028 start
    public function BulkStudentTransferOut()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.BulkStudentTransferOut']);
    }
    //POCOR-6028 ends
    public function Transfer()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentTransfer']);
    }
    public function WithdrawRequests()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.WithdrawRequests']);
    }
    public function StudentWithdraw()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentWithdraw']);
    }
    public function StudentAbsences()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStudentAbsences']);
    }
    public function FeederOutgoingInstitutions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.FeederOutgoingInstitutions']);
    }
    public function FeederIncomingInstitutions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.FeederIncomingInstitutions']);
    }

    public function HistoricalStaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Historical.HistoricalStaffLeave']);
    }

    public function StaffReleaseIn()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffReleaseIn']);
    }

    public function StaffRelease()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffRelease']);
    }

    public function StudentStatusUpdates()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentStatusUpdates']);
    }
    // End

    public function ScheduleTimetableOverview()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Schedule.ScheduleTimetables']);
    }

    public function ScheduleIntervals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Schedule.ScheduleIntervals']);
    }

    public function ScheduleTerms()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Schedule.ScheduleTerms']);
    }

    public function Committees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTestCommittees']);
    }

    public function CommitteeAttachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.CommitteeAttachments']);
    }
    // Timetable - END

    //POCOR-5669 added InstitutionMaps
    public function InstitutionMaps()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionMaps']);
    }
    //POCOR-5669 added InstitutionMaps

    //POCOR-5683 added InstitutionStatusUpdate
    public function InstitutionStatus()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStatus']);

        /*$institutionId = $this->request->pass[1];

        $backUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institution',
            'action' => 'view',
            'institutionId' => $institutionId,
            'view'
        ];*/
    }

    //POCOR-5182 added StaffSalaries
    public function StaffSalaries()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffSalaries']);
    }
    //POCOR-5182 added StaffSalaries

    //POCOR-6145 added Export button in Infratucture > Wash > Waters
    public function InfrastructureWashWaters()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashWaters']);
    }
    //PCOOR-6146 add export button in Institutions > Infrastructures > WASH > Hygiene
    public function InfrastructureWashHygienes(){
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashHygienes']);
    }

    //POCOR-6144 added Export button in Infratucture > Utilitie > Internet
    public function InfrastructureUtilityInternets()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureUtilityInternets']);
    }

    //POCOR-6143 added Export button in Infratucture > Utilitie > Electricity
    public function InfrastructureUtilityElectricities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureUtilityElectricities']);
    }
    //POCOR-6143 added Export button in Infratucture > Utilitie > Electricity

    public function changeUtilitiesHeader($model, $modelAlias, $userType)
    {
        $session = $this->request->session();
        $institutionId = 0;
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        if (!empty($institutionId)) {
            if($this->request->param('action') == 'InfrastructureUtilityElectricities') {
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Electricity');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Electricity'));
                $this->set('contentHeader', $header);

            } else if($this->request->param('action') == 'InfrastructureUtilityInternets'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Internet');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Internet'));
                $this->set('contentHeader', $header);
            } else if($this->request->param('action') == 'InfrastructureWashWaters'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Water');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Water'));
                $this->set('contentHeader', $header);
            } else if($this->request->param('action') == 'InfrastructureWashSanitations'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Sanitation');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Sanitation'));
                $this->set('contentHeader', $header);
            } else if($this->request->param('action') == 'InfrastructureWashHygienes'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Hygiene');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Hygiene'));
                $this->set('contentHeader', $header);
            }
        }

    }

    //PCOOR-6146 add export button in Institutions > Infrastructures > WASH > Hygiene

    // AngularJS
    public function ScheduleTimetable($action = 'view')
    {

        $timetableId = $this->ControllerAction->paramsDecode($this->request->query('timetableId'))['id'];

        $session = $this->request->session();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');

        $backUrl = [
            'plugin' => $this->plugin,
            'controller' => $this->name,
            'action' => 'ScheduleTimetableOverview',
            'institutionId' => $institutionId,
            'view',
            $this->ControllerAction->paramsEncode(['id' => $timetableId])
        ];

        $academicPeriodId = TableRegistry::get('AcademicPeriod.AcademicPeriods')
                ->getCurrent();

        $this->set('_action', $action);
        $this->set('_back', Router::url($backUrl));

        $this->set('timetable_id', $timetableId);
        $this->set('institutionDefaultId', $institutionId);
        $this->set('academicPeriodId', $academicPeriodId);
        $this->set('ngController', 'TimetableCtrl as $ctrl');
        $this->render('timetable');
    }

    public function InstitutionStudentAbsencesArchived()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStudentAbsencesArchived']);
    }

    public function StudentAttendances($pass='')
    {
        if($pass=='excel'){
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentAttendances']);
        }else{

        $_edit = $this->AccessControl->check(['Institutions', 'StudentAttendances', 'edit']);

        $_excel = $this->AccessControl->check(['Institutions', 'StudentAttendances', 'excel']);
        $_import = $this->AccessControl->check(['Institutions', 'ImportStudentAttendances', 'add']);

        $_excel = true;

        if (!empty($this->request->param('institutionId'))) {
            $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
        } else {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
        }

        $securityFunctions = TableRegistry::get('SecurityFunctions');
        $securityFunctionsData = $securityFunctions
        ->find()
        ->select([
            'SecurityFunctions.id'
        ])
        ->where([
            'SecurityFunctions.name' => 'Student Attendance Archive'
        ])
        ->first();
        $permission_id = $_SESSION['Permissions']['Institutions']['Institutions']['view'][0];

        $securityRoleFunctions = TableRegistry::get('SecurityRoleFunctions');
        $securityRoleFunctionsData = $securityRoleFunctions
        ->find()
        ->select([
            'SecurityRoleFunctions._view'
        ])
        ->where([
            'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
            'SecurityRoleFunctions.security_role_id' => $permission_id,
        ])
        ->first();
        $is_button_accesible = 0;
        if( (!empty($securityRoleFunctionsData) && $securityRoleFunctionsData->_view == 1) ){
            $is_button_accesible = 1;
        }
        if($this->Auth->user('super_admin') == 1){
            $is_button_accesible = 1;
        }

        // issue
        $excelUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentAttendances',
            'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
            'excel'
        ];

        $importUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'ImportStudentAttendances',
            'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
            'add'
        ];

        // $archiveUrl = [
        //     'plugin' => 'Institution',
        //     'controller' => 'Institutions',
        //     'action' => 'StudentArchive',
        //     'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
        //     'add'
        // ];

        $archiveUrl = $this->ControllerAction->url('index');
        $archiveUrl['plugin'] = 'Institution';
        $archiveUrl['controller'] = 'Institutions';
        $archiveUrl['action'] = 'InstitutionStudentAbsencesArchived';

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
        $this->Navigation->addCrumb($crumbTitle);

        $this->set('_edit', $_edit);
        $this->set('_excel', $_excel);
        $this->set('_import', $_import);
        $this->set('_archive', $_archive);
        $this->set('excelUrl', Router::url($excelUrl));
        $this->set('importUrl', Router::url($importUrl));
        $this->set('archiveUrl', Router::url($archiveUrl));
        $this->set('is_button_accesible', $is_button_accesible);
        $this->set('institution_id', $institutionId);
        $this->set('ngController', 'InstitutionStudentAttendancesCtrl as $ctrl');
        }
    }

    public function StudentMeals($pass='')
    {
        if($pass=='excel'){
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentMeals']);
        }
        else{
            $_edit = $this->AccessControl->check(['Institutions', 'StudentMeals', 'edit']);
            $_excel = $this->AccessControl->check(['Institutions', 'StudentMeals', 'excel']);
            $_import = $this->AccessControl->check(['Institutions', 'ImportStudentMeals', 'add']);

            $_excel = true;

            if (!empty($this->request->param('institutionId'))) {
                $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
            } else {
                $session = $this->request->session();
                $institutionId = $session->read('Institution.Institutions.id');
            }

            $excelUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentMeals',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
                'excel'
            ];

            $importUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'ImportStudentMeals',
            'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
            'add'
        ];

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
                $this->Navigation->addCrumb($crumbTitle);

            $this->set('_edit', $_edit);
            $this->set('_excel', $_excel);
            $this->set('_import', $_import);
            $this->set('excelUrl', Router::url($excelUrl));
            $this->set('importUrl', Router::url($importUrl));
            $this->set('institution_id', $institutionId);
            $this->set('ngController', 'InstitutionStudentMealsCtrl as $ctrl');
        }

    }

    public function StudentArchive(){
            if (!empty($this->request->param('institutionId'))) {
                $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
            } else {
                $session = $this->request->session();
                $institutionId = $session->read('Institution.Institutions.id');
            }

            $archiveUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentArchive',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
                'add'
            ];

            $backUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentAttendances',
                'institutionId' => $institutionId,
                'index',
                $this->ControllerAction->paramsEncode(['id' => $timetableId])
            ];
            $this->set('backUrl', Router::url($backUrl));

            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
                $this->Navigation->addCrumb($crumbTitle);

            $this->set('archiveUrl', Router::url($archiveUrl));
            $this->set('institution_id', $institutionId);
            $this->set('ngController', 'InstitutionStudentArchiveCtrl as $ctrl');

    }

    public function Results()
    {
        $classId = $this->ControllerAction->getQueryString('class_id');
        $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
        $institutionId = $this->ControllerAction->getQueryString('institution_id');
        $academicPeriodId = $this->ControllerAction->getQueryString('academic_period_id');
        $roles = [];

        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
        }

        $this->set('_roles', $roles);

        // POCOR-3983 check institution status
        $Institutions = TableRegistry::get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);
        if ($isActive) {
            $_edit = $this->AccessControl->check(['Institutions', 'Results', 'edit'], $roles);
        } else {
            $_edit = false;
        }
        // end POCOR-3983

        $this->set('_edit', $_edit);
        $this->set('_excel', $this->AccessControl->check(['Institutions', 'Assessments', 'excel'], $roles));
        $url = $this->ControllerAction->url('index');
        $url['plugin'] = 'Institution';
        $url['controller'] = 'Institutions';
        $url['action'] = 'resultsExport';

        $Assessments = TableRegistry::get('Assessment.Assessments');
        $hasTemplate = $Assessments->checkIfHasTemplate($assessmentId);

        if ($hasTemplate) {
            $customUrl = $this->ControllerAction->url('index');
            $customUrl['plugin'] = 'CustomExcel';
            $customUrl['controller'] = 'CustomExcels';
            $customUrl['action'] = 'export';
            $customUrl[0] = 'AssessmentResults';
            $this->set('customExcel', Router::url($customUrl));

            $exportPDF_Url = $this->ControllerAction->url('index');
            $exportPDF_Url['plugin'] = 'CustomExcel';
            $exportPDF_Url['controller'] = 'CustomExcels';
            $exportPDF_Url['action'] = 'exportPDF';
            $exportPDF_Url[0] = 'AssessmentResults';
            $this->set('exportPDF', Router::url($exportPDF_Url));
        }

        $this->set('excelUrl', Router::url($url));
        $this->set('ngController', 'InstitutionsResultsCtrl');
    }

    public function Comments()
    {
        // POCOR-3983 check institution status
        $institutionId = $this->ControllerAction->getQueryString('institution_id');

        $Institutions = TableRegistry::get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);
        if ($isActive) {
            $_edit = $this->AccessControl->check(['Institutions', 'Comments', 'edit']);
        } else {
            $_edit = false;
        }
        // end POCOR-3983

        $this->set('_edit', $_edit);
        $this->set('ngController', 'InstitutionCommentsCtrl as InstitutionCommentsController');
    }
    // End

    public function resultsExport()
    {
        $classId = $this->ControllerAction->getQueryString('class_id');
        $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
        $institutionId = $this->ControllerAction->getQueryString('institution_id');
        $userId = $this->Auth->user('id');

        $settings = [
            'class_id' => $classId,
            'assessment_id' => $assessmentId,
            'institution_id' => $institutionId,
            'user_id' => $userId,
            'AccessControl' => $this->AccessControl,
            'download' => false,
            'purge' => false
        ];

        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        $results = $ClassStudents->generateXLXS($settings);
        $fileName = $results['file'];
        $filePath = $results['path'] . $fileName;

        $response = $this->response;
        $response->body(function () use ($filePath) {
            $content = file_get_contents($filePath);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return $content;
        });

        // Syntax will change in v3.4.x
        $pathInfo = pathinfo($fileName);
        $response->type($pathInfo['extension']);
        $response->download($fileName);

        return $response;
    }

    public function StudentCompetencies($subaction = 'index')
    {
        if ($subaction == 'edit') {
            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
            $session = $this->request->session();
            $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentCompetencies',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];
            $this->Navigation->addCrumb($crumbTitle, $indexUrl);
            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'StudentCompetencies', $action], $roles)) {
                    $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'StudentCompetencies'];
                    return $this->redirect($url);
                }
            }
            $tabElements = $this->getCompetencyTabElements();
            $queryString = $this->ControllerAction->getQueryString();
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'StudentCompetencies';
            $viewUrl[0] = 'view';

            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('classId', $queryString['class_id']);
            $this->set('competencyTemplateId', $queryString['competency_template_id']);
            $this->set('queryString', $queryString);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'StudentCompetencies');
            $this->render('student_competency_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentCompetencies']);
        }
    }

    public function StudentCompetencyComments($subaction = 'index')
    {
        if ($subaction == 'edit') {
            $session = $this->request->session();
            $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentCompetencies',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];
            $this->Navigation->addCrumb('Student Competencies', $indexUrl);

            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'StudentCompetencyComments', $action], $roles)) {
                    $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'StudentCompetencies'];
                    return $this->redirect($url);
                }
            }

            $tabElements = $this->getCompetencyTabElements();
            $queryString = $this->ControllerAction->getQueryString();
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'StudentCompetencyComments';
            $viewUrl[0] = 'view';
            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('classId', $queryString['class_id']);
            $this->set('competencyTemplateId', $queryString['competency_template_id']);
            $this->set('queryString', $queryString);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'StudentCompetencyComments');
            $this->render('student_competency_comments_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentCompetencyComments']);
        }
    }

    public function StudentOutcomes($subaction = 'index')
    {
        if ($subaction == 'edit') {
            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
            $session = $this->request->session();
            $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentOutcomes',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];
            $this->Navigation->addCrumb($crumbTitle, $indexUrl);
            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'StudentOutcomes', $action], $roles)) {
                    $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'StudentOutcomes'];
                    return $this->redirect($url);
                }
            }
            $queryString = $this->ControllerAction->getQueryString();
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'StudentOutcomes';
            $viewUrl[0] = 'view';

            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('classId', $queryString['class_id']);
            $this->set('outcomeTemplateId', $queryString['outcome_template_id']);
            $this->set('queryString', $queryString);
            $this->set('selectedAction', 'StudentOutcomes');
            $this->render('student_outcome_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentOutcomes']);
        }
    }

    public function Classes($subaction = 'index', $classId = null)
    {
        if ($subaction == 'edit') {
            $session = $this->request->session();
            $roles = [];
            $classId = $this->ControllerAction->paramsDecode($classId);
            $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'AllClasses', $action], $roles)) {
                    if ($AccessControl->check(['Institutions', 'Classes', $action], $roles)) {
                        $ClassTable = TableRegistry::get('Institution.InstitutionClasses');

                        $classResults = $ClassTable
                            ->find('byAccess', [
                                'accessControl' => $AccessControl,
                                'userId' => $userId,
                                'permission' => $subaction,
                                'controller' => $this
                            ])
                            ->where([$ClassTable->aliasField('id') => $classId['id']])
                            ->all();

                        if ($classResults->isEmpty()) {
                            $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'Classes'];
                            return $this->redirect($url);
                        }
                    } else {
                        $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'Classes'];
                        return $this->redirect($url);
                    }
                }
            }
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'Classes';
            $viewUrl[0] = 'view';

            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('classId', $classId['id']);
            $this->set('institutionId', $institutionId);
            $this->render('institution_classes_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionClasses']);
        }
    }

    public function Subjects($subaction = 'index', $institutionSubjectId = null)
    {
        if ($subaction == 'edit') {
            $session = $this->request->session();
            $roles = [];
            $institutionSubjectId = $this->ControllerAction->paramsDecode($institutionSubjectId);
            $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'AllSubjects', $action], $roles)) {
                    if ($AccessControl->check(['Institutions', 'Subjects', $action], $roles)) {
                        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
                        $subjectRecord = $InstitutionSubjects->get($institutionSubjectId, ['contain' => ['Teachers']])->toArray();
                        if (in_array($userId, array_column($subjectRecord['teachers']), 'id')) {
                            $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'index'];
                            return $this->redirect($url);
                        }
                    } else {
                        $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'index'];
                        return $this->redirect($url);
                    }
                }
            }
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'Subjects';
            $viewUrl[0] = 'view';
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Subjects',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];
            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];
            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('institutionSubjectId', $institutionSubjectId['id']);
            $this->set('institutionId', $institutionId);
            $this->render('institution_subjects_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionSubjects']);
        }
    }

    public function Students($pass = 'index')
    {
        if ($pass == 'add') {
            $session = $this->request->session();
            $roles = [];

            if (!$this->AccessControl->isAdmin() && $session->check('Institution.Institutions.id')) {
                $userId = $this->Auth->user('id');
                $institutionId = $session->read('Institution.Institutions.id');
                $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
            }

            $this->set('ngController', 'InstitutionsStudentsCtrl as InstitutionStudentController');
            $this->set('_createNewStudent', $this->AccessControl->check(['Institutions', 'getUniqueOpenemisId'], $roles));
            $externalDataSource = false;
            $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
            $externalSourceType = $ConfigItemTable->find()->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])->first();
            if (!empty($externalSourceType) && $externalSourceType['value'] != 'None') {
                $externalDataSource = true;
            }
            $this->set('externalDataSource', $externalDataSource);

            $this->render('studentAdd');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Students']);
        }
    }

    public function Staff($pass = 'index')
    {
        if ($pass == 'add') {
            $session = $this->request->session();
            $roles = [];

            if (!$this->AccessControl->isAdmin() && $session->check('Institution.Institutions.id')) {
                $userId = $this->Auth->user('id');
                $institutionId = $session->read('Institution.Institutions.id');
                $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
            }
            $this->set('ngController', 'InstitutionsStaffCtrl as InstitutionStaffController');
            $this->set('_createNewStaff', $this->AccessControl->check(['Institutions', 'getUniqueOpenemisId'], $roles));
            $externalDataSource = false;
            $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
            $externalSourceType = $ConfigItemTable->find()->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])->first();
            if (!empty($externalSourceType) && $externalSourceType['value'] != 'None') {
                $externalDataSource = true;
            }
            $this->set('externalDataSource', $externalDataSource);
            $this->render('staffAdd');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Staff']);
        }
    }

    // Assosiation feature
    public function Associations($subaction = 'index', $associationId = null)
    {
        if ($subaction == 'add') {
            $session = $this->request->session();
            $roles = [];
            $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'Associations';
            $viewUrl[0] = 'view';

            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Associations',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $academicPeriodId = TableRegistry::get('AcademicPeriod.AcademicPeriods')
            ->getCurrent();
            $academicPeriodOptions =TableRegistry::get('AcademicPeriod.AcademicPeriods')
                ->getYearList();


            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('academicPeriodId', $academicPeriodId);
            $this->set('academicPeriodName', $academicPeriodOptions[$academicPeriodId]);
            $this->set('institutionId', $institutionId);
            $this->render('institution_associations');
        }else if ($subaction == 'edit') {
            $session = $this->request->session();
            $roles = [];
            $associationId = $this->ControllerAction->paramsDecode($associationId);
            $institutionId = !empty($this->request->param('institutionId')) ? $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'Associations';
            $viewUrl[0] = 'view';

            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Associations',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];

            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('classId', $associationId['id']);
            $this->set('institutionId', $institutionId);
            $this->render('institution_associations_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAssociations']);
        }
    }

    public function StudentAssociations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.InstitutionAssociationStudent']);
    }

    public function InstitutionStaffAttendancesArchive()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStaffAttendancesArchive']);
    }

    public function InstitutionStaffAttendances($pass = 'index')
    {
        if ($pass == 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAttendances']);
        } else {
            $_edit = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'edit']);
            $_history = $this->AccessControl->check(['Staff', 'InstitutionStaffAttendanceActivities', 'index']);
            $_excel = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'excel']);
			$_ownView = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'ownview']);
            $_ownEdit = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'ownedit']);
            $_otherView = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'otherview']);
            $_otherEdit = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'otheredit']);
            $_permissionStaffId = $this->Auth->user('id');

            if (!empty($this->request->param('institutionId'))) {
                $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
            } else {
                $session = $this->request->session();
                $institutionId = $session->read('Institution.Institutions.id');
            }

            $securityFunctions = TableRegistry::get('SecurityFunctions');
            $securityFunctionsData = $securityFunctions
            ->find()
            ->select([
                'SecurityFunctions.id'
            ])
            ->where([
                'SecurityFunctions.name' => 'Student Attendance Archive'
            ])
            ->first();
            $permission_id = $_SESSION['Permissions']['Institutions']['Institutions']['view'][0];

            $securityRoleFunctions = TableRegistry::get('SecurityRoleFunctions');
            $securityRoleFunctionsData = $securityRoleFunctions
            ->find()
            ->select([
                'SecurityRoleFunctions._view'
            ])
            ->where([
                'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
                'SecurityRoleFunctions.security_role_id' => $permission_id,
            ])
            ->first();
            $is_button_accesible = 0;
            if( (!empty($securityRoleFunctionsData) && $securityRoleFunctionsData->_view == 1) ){
                $is_button_accesible = 1;
            }
            if($this->Auth->user('super_admin') == 1){
                $is_button_accesible = 1;
            }

            $excelUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'InstitutionStaffAttendances',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
                'excel'
            ];
            $_import = $this->AccessControl->check(['Institutions', 'ImportStaffAttendances', 'add']);

            $importUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'ImportStaffAttendances',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
                'add'
            ];

            $archiveUrl = $this->ControllerAction->url('index');
            $archiveUrl['plugin'] = 'Institution';
            $archiveUrl['controller'] = 'Institutions';
            $archiveUrl['action'] = 'InstitutionStaffAttendancesArchive';


            $this->set('importUrl', Router::url($importUrl));
            $this->set('_import', $_import);
            $this->set('_edit', $_edit);
			$this->set('_ownEdit', $_ownEdit);
            $this->set('_ownView', $_ownView);
            $this->set('_otherEdit', $_otherEdit);
            $this->set('_otherView', $_otherView);
            $this->set('_permissionStaffId', $_permissionStaffId);
            $this->set('_excel', $_excel);
            $this->set('_history', $_history);
            $this->set('_archive', $_archive);
            $this->set('archiveUrl', Router::url($archiveUrl));
            $this->set('is_button_accesible', $is_button_accesible);
            $this->set('institution_id', $institutionId);
            $this->set('excelUrl', Router::url($excelUrl));
            $this->set('ngController', 'InstitutionStaffAttendancesCtrl as $ctrl');
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        //for api purpose starts
        if($this->request->params['action'] == 'getEducationGrade'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getEducationGrade';
        }
        if($this->request->params['action'] == 'getClassOptions'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getClassOptions';
        }//for api purpose ends
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        $pass = $this->request->pass;
        if (isset($pass[0]) && $pass[0] == 'downloadFile') {
            return true;
        }
    }

    public function changeUserHeader($model, $modelAlias, $userType)
    {
        $session = $this->request->session();
        // add the student name to the header
        $id = 0;
        if ($session->check('Staff.Staff.id')) {
            $id = $session->read('Staff.Staff.id');
        }
        if (!empty($id)) {
            $Users = TableRegistry::get('Security.Users');
            $entity = $Users->get($id);
            $name = $entity->name;
            $crumb = Inflector::humanize(Inflector::underscore($modelAlias));
            $header = $name . ' - ' . __($crumb);
            $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
            $this->Navigation->addCrumb('Staff', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff']);
            $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $userType, 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
            $this->Navigation->addCrumb($crumb);
            $this->set('contentHeader', $header);
        }
    }

    private function checkInstitutionAccess($id, $event)
    {
        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();

            if (!array_key_exists($id, $institutionIds)) {
                $this->Alert->error('security.noAccess');
                $url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index'];
                $event->stopPropagation();

                return $this->redirect($url);
            }
        }
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $session = $this->request->session();
        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $action = $this->request->params['action'];
        $header = __('Institutions');

        if (($action == 'StudentUser' || $action == 'StaffUser') && (empty($this->ControllerAction->paramsPass()) || $this->ControllerAction->paramsPass()[0] == 'view' )) {
            $session->delete('Guardian.Guardians.id');
            $session->delete('Guardian.Guardians.name');
        }
        // this is to cater for back links
        $query = $this->request->query;

        try {
            if ($this->ControllerAction->getQueryString('institution_id')) {
                $institutionId = $this->ControllerAction->getQueryString('institution_id');
                //check for permission
                $this->checkInstitutionAccess($institutionId, $event);
                if ($event->isStopped()) {
                    return false;
                }
                $session->write('Institution.Institutions.id', $institutionId);
            } elseif (array_key_exists('institution_id', $query)) {
                //check for permission
                $this->checkInstitutionAccess($query['institution_id'], $event);
                if ($event->isStopped()) {
                    return false;
                }
                $session->write('Institution.Institutions.id', $query['institution_id']);
            }
        } catch (SecurityException $ex) {
            return;
        }

        if ($action == 'Institutions' && isset($this->request->pass[0]) && $this->request->pass[0] == 'index') {
            if ($session->check('Institution.Institutions.search.key')) {
                $search = $session->read('Institution.Institutions.search.key');
                $session->delete('Institution.Institutions.id');
                $session->write('Institution.Institutions.search.key', $search);
            } else {
                $session->delete('Institution.Institutions.id');
            }
        } elseif ($action == 'StudentUser') {
            $session->write('Student.Students.id', $this->ControllerAction->paramsDecode($this->request->pass[1])['id']);
        } elseif ($action == 'StaffUser') {
            $session->write('Staff.Staff.id', $this->ControllerAction->paramsDecode($this->request->pass[1])['id']);
        }

        if (($session->check('Institution.Institutions.id')
            || $this->request->param('institutionId'))
            || $action == 'dashboard'
            || ($action == 'Institutions' && isset($this->request->pass[0]) && in_array($this->request->pass[0], ['view', 'edit']))) {
            $id = 0;

            if (isset($this->request->pass[0]) && (in_array($action, ['dashboard']))) {
                $id = $this->request->pass[0];
                $id = $this->ControllerAction->paramsDecode($id)['id'];
                $this->checkInstitutionAccess($id, $event);
                if ($event->isStopped()) {
                    return false;
                }
                $session->write('Institution.Institutions.id', $id);
            } elseif ($action == 'Institutions' && isset($this->request->pass[0]) && (in_array($this->request->pass[0], ['view', 'edit']))) {
                $id = $this->request->pass[1];
                $id = $this->ControllerAction->paramsDecode($id)['id'];
                $this->checkInstitutionAccess($id, $event);
                if ($event->isStopped()) {
                    return false;
                }
                $session->write('Institution.Institutions.id', $id);
            } elseif ($this->request->param('institutionId')) {
                $id = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];

               // Remove writing to session once model has been converted to institution plugin
                $session->write('Institution.Institutions.id', $id);
            } elseif ($session->check('Institution.Institutions.id')) {
                $id = $session->read('Institution.Institutions.id');
            }

            $indexPage = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index'];
            if (!empty($id)) {
                if ($this->Institutions->exists([$this->Institutions->primaryKey() => $id])) {
                    $this->activeObj = $this->Institutions->get($id);
                    $name = $this->activeObj->name;
                    $session->write('Institution.Institutions.name', $name);
                    if ($action == 'view') {
                        $header = $name .' - '.__('Overview');
                    } elseif ($action == 'Results') {
                        // POCOR-4066 - add class name to header
                        $classId = $this->ControllerAction->getQueryString('class_id');
                        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
                        if ($InstitutionClasses->exists([$InstitutionClasses->primaryKey() => $classId])) {
                            $classEntity = $InstitutionClasses->get($classId);
                            $header = $classEntity->name .' - '.__('Assessments');
                        } else {
                            $header = $name .' - '.__('Assessments');
                        }
                        // End
                    } else {
                        $header = $name .' - '.__(Inflector::humanize(Inflector::underscore($action)));
                    }
                    $crumb = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'dashboard',
                        'institutionId' => $this->ControllerAction->paramsEncode(['id' => $id]),
                        $this->ControllerAction->paramsEncode(['id' => $id])
                    ];
                    $this->Navigation->addCrumb($name, $crumb);
                } else {
                    return $this->redirect($indexPage);
                }
            } else {
                return $this->redirect($indexPage);
            }
        }
        if($action == 'dashboard') {
            $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($this->Auth->user('id'), $id);
            $havePermission = $this->AccessControl->check(['Institutions', 'InstitutionProfileCompletness', 'view'], $roles);
            if($havePermission) {
                 $header = $name .' - '.__('Institution Completeness');
            } else {
                 $header = $name .' - '.__('Dashboard');
            }

        }
        $this->set('contentHeader', $header);
    }

    public function getUniqueOpenemisId()
    {
        $this->autoRender = false;
        return new Response(['body' => $this->CreateUsers->getUniqueOpenemisId()]);
    }

    public function getAutoGeneratedPassword()
    {
        $this->autoRender = false;
        return new Response(['body' => $this->CreateUsers->getAutoGeneratedPassword()]);
    }

    private function attachAngularModules()
    {
        $action = $this->request->action;
        switch ($action) {
            case 'Associations':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'kd-angular-multi-select',
                            'institution.associations.ctrl',
                            'institution.associations.svc'
                        ]);
                    }
                    if ($this->request->param('pass')[0] == 'add') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'kd-angular-multi-select',
                            'institutionadd.associations.ctrl',
                            'institutionadd.associations.svc'
                        ]);
                    }
                }
                break;
            case 'StudentAttendances':
                $this->Angular->addModules([
                    'institution.student.attendances.ctrl',
                    'institution.student.attendances.svc'
                ]);
                break;
            case 'StudentMeals':
                $this->Angular->addModules([
                    'institution.student.meals.ctrl',
                    'institution.student.meals.svc'
                ]);
                break;
            case 'Results':
                $this->Angular->addModules([
                    'alert.svc',
                    'institutions.results.ctrl',
                    'institutions.results.svc'
                ]);
                break;
            case 'Surveys':
                $this->Angular->addModules([
                    'relevancy.rules.ctrl'
                ]);
                $this->set('ngController', 'RelevancyRulesCtrl as RelevancyRulesController');
                break;
            case 'Students':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'add') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institutions.students.ctrl',
                            'institutions.students.svc'
                        ]);
                    }
                }
                break;
            case 'Staff':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'add') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institutions.staff.ctrl',
                            'institutions.staff.svc'
                        ]);
                    }
                }
                break;
            case 'Comments':
                $this->Angular->addModules([
                    'alert.svc',
                    'institutions.comments.ctrl',
                    'institutions.comments.svc'
                ]);
                // no break
            case 'Classes':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'kd-angular-multi-select',
                            'institution.class.students.ctrl',
                            'institution.class.students.svc'
                        ]);
                    }
                }
                break;
            case 'Subjects':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'kd-angular-multi-select',
                            'institution.subject.students.ctrl',
                            'institution.subject.students.svc'
                        ]);
                    }
                }
                break;
            case 'StudentCompetencies':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institution.student.competencies.ctrl',
                            'institution.student.competencies.svc'
                        ]);
                    }
                }
                break;
            case 'StudentCompetencyComments':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institution.student.competency_comments.ctrl',
                            'institution.student.competency_comments.svc'
                        ]);
                    }
                }
                break;
            case 'StudentOutcomes':
                if (isset($this->request->pass[0])) {
                    if ($this->request->param('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institution.student.outcomes.ctrl',
                            'institution.student.outcomes.svc'
                        ]);
                    }
                }
                break;

            case 'InstitutionStaffAttendances':
                $this->Angular->addModules([
                    'institution.staff.attendances.ctrl',
                    'institution.staff.attendances.svc'
                ]);
                break;

            case 'ScheduleTimetable':
                $this->Angular->addModules([
                    'timetable.ctrl',
                    'timetable.svc'
                ]);
                break;

            case 'StudentArchive':
                $this->Angular->addModules([
                    'institution.student.archive.ctrl',
                    'institution.student.archive.svc'
                ]);
                break;
            case 'AssessmentsArchive':
                $this->Angular->addModules([
                    'institution.assessments.archive.ctrl',
                    'institution.assessments.archive.svc'
                ]);
                break;
        }
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        if (!is_null($this->activeObj)) {
            $session = $this->request->session();
            try {
                $institutionId = $this->ControllerAction->paramsDecode($this->request->params('institutionId'));
            } catch (Exception $e) {
                $institutionId = $session->read('Institution.Institutions.id');
            }

            $action = false;
            $params = $this->request->params;
            // do not hyperlink breadcrumb for Infrastructures and Rooms
            if (isset($params['pass'][0]) && !in_array($model->alias, ['Infrastructures', 'Rooms'])) {
                $action = $params['pass'][0];
            }
            $isDownload = $action == 'downloadFile' ? true : false;

            $alias = $model->alias;
            $crumbTitle = Inflector::humanize(Inflector::underscore($alias));
            $crumbOptions = [];
            if ($action) {
                $crumbOptions = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias, 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])];
            }

            // POCOR-3983 to disable add/edit/remove action on the model depend when inactive
            $this->getStatusPermission($model);

            $studentModels = [
                'StudentProgrammes' => __('Programmes'),
                'StudentRisks' => __('Risks'),
                'StudentTextbooks' => __('Textbox'),
                'StudentAssociations' => __('Associations')
            ];
            if (array_key_exists($alias, $studentModels)) {
                // add Students and student name
                if ($session->check('Student.Students.name')) {
                    $studentName = $session->read('Student.Students.name');
                    $studentId = $session->read('Student.Students.id');

                    // Breadcrumb
                    $this->Navigation->addCrumb('Students', ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Students', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
                    $this->Navigation->addCrumb($studentName, ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $studentId])]);
                    $this->Navigation->addCrumb($studentModels[$alias]);

                    // header name
                    $header = $studentName;
                }
            } elseif ($model->alias() == 'CommitteeAttachments') {
                $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
                $institutionName = $session->read('Institution.Institutions.name');
                $this->Navigation->addCrumb('Committees', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'Committees']);
                $this->Navigation->addCrumb('Attachments');
                $header = __($institutionName) ;
                $this->set('contentHeader', $header);
            }
            else {
                $this->Navigation->addCrumb($crumbTitle, $crumbOptions);
                $header = $this->activeObj->name;
            }

            $persona = false;
            $requestQuery = $this->request->query;
           // echo '<pre>'; print_r($model->alias());die;
            if (isset($params['pass'][1])) {
                if ($model->table() == 'security_users' && !$isDownload) {
                    $ids = empty($this->ControllerAction->paramsDecode($params['pass'][1])['id']) ? $session->read('Student.Students.id') : $this->ControllerAction->paramsDecode($params['pass'][1])['id'];
                    $persona = $model->get($ids);
                }
            } elseif (isset($requestQuery['user_id'])) {
                // POCOR-4577 - to check if Users association existed in model - for staff leave import
                if ($model->association('Users')) {
                    $persona = $model->Users->get($requestQuery['user_id']);
                } else {
                    $Users = TableRegistry::get('Security.Users');
                    $persona = $Users->get($requestQuery['user_id']);
                }
            }

            if (is_object($persona) && get_class($persona)=='User\Model\Entity\User') {
                $header = $persona->name . ' - ' . $model->getHeader($alias);
                $model->addBehavior('Institution.InstitutionUserBreadcrumbs');
            } elseif ($model->alias() == 'IndividualPromotion') {
                $header .= ' - '. __('Individual Promotion / Repeat');
            } elseif ($model->alias() == 'StudentRisks') {
                $header .= ' - '. __('Risks');
            } elseif ($model->alias() == 'Indexes') {
                $header .= ' - '. __('Risks');
                $this->Navigation->substituteCrumb($model->getHeader($alias), __('Risks'));
            } elseif ($model->alias() == 'InstitutionStudentRisks') {
                $header .= ' - '. __('Institution Student Risks');
                $this->Navigation->substituteCrumb($model->getHeader($alias), __('Institution Student Risks'));
            }elseif ($model->alias() == 'InstitutionAssociationStudent') {
                $header .= ' - '. __('Associations');
            } else {
                 $header .= ' - ' . $model->getHeader($alias);
            }
           
            $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
            $event = $model->eventManager()->dispatch($event);

            if ($model->hasField('institution_id')) {
                if (!in_array($model->alias(), ['StudentTransferIn', 'StudentTransferOut'])) {
                    $model->fields['institution_id']['type'] = 'hidden';
                    $model->fields['institution_id']['value'] = $institutionId;
                }

                if (count($this->request->pass) > 1 && isset($this->request->pass[1])) {
                    $modelIds = $this->request->pass[1]; // id of the sub model
                    $primaryKey = $model->primaryKey();
                    $modelIds = $this->ControllerAction->paramsDecode($modelIds);
                    $params = [];
                    if (is_array($primaryKey)) {
                        foreach ($primaryKey as $key) {
                            $params[$model->aliasField($key)] = $modelIds[$key];
                        }
                    } else {
                        $params[$primaryKey] = $modelIds[$primaryKey];
                    }

                    $exists = false;

                    if (in_array($model->alias(), ['StaffTransferOut', 'StudentTransferOut'])) {
                        $params[$model->aliasField('previous_institution_id')] = $institutionId;
                        $exists = $model->exists($params);
                    } elseif (in_array($model->alias(), ['InstitutionShifts'])) { //this is to show information for the occupier
                        $params['OR'] = [
                            $model->aliasField('institution_id') => $institutionId,
                            $model->aliasField('location_institution_id') => $institutionId
                        ];
                        $exists = $model->exists($params);
                    } elseif (in_array($model->alias(), ['FeederOutgoingInstitutions'])) {
                        $params[$model->aliasField('feeder_institution_id')] = $institutionId;
                        $exists = $model->exists($params);
                    } else {
                        $checkExists = function ($model, $params) {
                            return $model->exists($params);
                        };

                        $event = $model->dispatchEvent('Model.isRecordExists', [], $this);
                        if (is_callable($event->result)) {
                            $checkExists = $event->result;
                        }
                        $params[$model->aliasField('institution_id')] = $institutionId;
                        $exists = $checkExists($model, $params);
                    }

                    /**
                     * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                     */

                    // replaced 'action' => $alias to 'action' => $model->alias, since only the name changes but not url
                    if (!$exists && !$isDownload) {
                        $this->Alert->warning('general.notExists');
                        return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
                    }
                }
            }

            $this->set('contentHeader', $header);
        } else {
            if ($model->alias() == 'ImportInstitutions') {
                $this->Navigation->addCrumb($model->getHeader($model->alias()));
                $header = __('Institutions') . ' - ' . $model->getHeader($model->alias());
                $this->set('contentHeader', $header);
            } elseif ($this->request->param('action') != 'Institutions') {
                $this->Alert->warning('general.notExists');
                $event->stopPropagation();
                return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
            }
        }
    }

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->session();

        if (!$this->request->is('ajax')) {
            if ($model->hasField('institution_id')) {
                if (!$session->check('Institution.Institutions.id')) {
                    $this->Alert->error('general.notExists');
                // should redirect
                } else {
                    if (!in_array($model->alias(), ['Programmes', 'StaffTransferIn', 'StaffTransferOut', 'StudentTransferIn', 'StudentTransferOut'])) {
                        $institutionId = $this->request->param('institutionId');
                        try {
                            $institutionId = $this->ControllerAction->paramsDecode($institutionId)['id'];
                        } catch (Exception $e) {
                            $institutionId = $session->read('Institution.Institutions.id');
                        }
                        $query->where([$model->aliasField('institution_id') => $institutionId]);
                    }
                }
            }
        }
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public function excel($id = 0)
    {
        TableRegistry::get('Institution.Institutions')->excel($id);
        $this->autoRender = false;
    }

    public function dashboard($id)
    {
        $id = $this->ControllerAction->paramsDecode($id)['id'];
        // $this->ControllerAction->model->action = $this->request->action;
        $Institutions = TableRegistry::get('Institution.Institutions');
        $classification = $Institutions->get($id)->classification;

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $currentPeriod = $AcademicPeriods->getCurrent();
        if (empty($currentPeriod)) {
            $this->Alert->warning('Institution.Institutions.academicPeriod');
        }

        // $highChartDatas = ['{"chart":{"type":"column","borderWidth":1},"xAxis":{"title":{"text":"Position Type"},"categories":["Non-Teaching","Teaching"]},"yAxis":{"title":{"text":"Total"}},"title":{"text":"Number Of Staff"},"subtitle":{"text":"For Year 2015-2016"},"series":[{"name":"Male","data":[0,2]},{"name":"Female","data":[0,1]}]}'];
        $highChartDatas = [];

        $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
        $InstitutionStaff = TableRegistry::get('Institution.Staff');

        if ($classification == $Institutions::ACADEMIC) {
            // only show student charts if institution is academic
            $InstitutionStudents = TableRegistry::get('Institution.Students');
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $statuses = $StudentStatuses->findCodeList();

			$params = [
                'conditions' => ['institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN'],
                    $statuses['PROMOTED'], $statuses['REPEATED']]]
            ];
            $highChartDatas[] = $InstitutionStudents->getHighChart('student_attendance', $params);

            $params = [
                'conditions' => ['institution_id' => $id, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('staff_attendance', $params);

            //Students By Grade for current year, excludes transferred ,withdrawn, promoted, repeated students
            $params = [
                'conditions' => ['institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN'],
                    $statuses['PROMOTED'], $statuses['REPEATED']]]
            ];

            $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_stage', $params);

            //Students By Year, excludes transferred withdrawn,promoted,repeated students
            $params = [
                'conditions' => ['institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN'],
                    $statuses['PROMOTED'], $statuses['REPEATED'], $statuses['GRADUATED']]]
            ];

            $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_year', $params);

            //Staffs By Position Type for current year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $id, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_type', $params);

            //Staffs By Year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $id, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_year', $params);
        } elseif ($classification == $Institutions::NON_ACADEMIC) {
            //Staffs By Position Title for current year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $id, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_position', $params);

            //Staffs By Year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $id, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_year', $params);
        }

         if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $institutionId = $id;
            $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
            $isActive = $Institutions->isActive($institutionId);
                if ($isActive) {
                    $this->set('haveProfilePermission',$this->AccessControl->check(['Institutions', 'InstitutionProfileCompletness', 'view'], $roles));
                } else {
                    $this->set('haveProfilePermission',false);
                }
        } else {
            $this->set('haveProfilePermission',true);
        }
        $profileData = $this->getInstituteProfileCompletnessData($id);
        $this->set('instituteprofileCompletness',$profileData);
        $this->set('instituteName', $this->activeObj->name);
        $this->set('highChartDatas', $highChartDatas);
        $indexDashboard = 'dashboard';
        $this->set('mini_dashboard', [
                'name' => $indexDashboard,
                'data' => [
                    'model' => 'staff',
                    'modelCount' => 25,
                    'modelArray' => []]
                ]);

            // $this->controller->viewVars['indexElements']['mini_dashboard'] = [
            //     'name' => $indexDashboard,
            //     'data' => [
            //         'model' => 'staff',
            //         'modelCount' => 25,
            //         'modelArray' => [],
            //     ],
            //     'options' => [],
            //     'order' => 1
            // ];

    }

    /**
     * Get intitute profile completness data
     * @return array
     */

     public function getInstituteProfileCompletnessDataBAK ($institutionId) {

        $data = array();
        $profileComplete = 0;
        // $totalProfileCount = 28;
        // check in config item

/********************************************* */
        //Overview
        $institutions = TableRegistry::get('institutions');
		$institutionsData = $institutions->find()
				->select([
					'created' => 'institutions.created',
					'modified' => 'institutions.modified',
				])
				->where([$institutions->aliasField('id') => $institutionId])
                ->order(['institutions.modified'=>'desc'])
				->limit(1)
				->first();
				;
        $data[0]['feature'] = 'Overview';
		if(!empty($institutionsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[0]['complete'] = 'yes';
            $data[0]['profileComplete'] = $profileComplete;
            $data[0]['modifiedDate'] = date("F j,Y",strtotime($institutionsData->modified));
		} else {
            $data[0]['complete'] = 'no';
            $data[0]['profileComplete'] = 0;
            $data[0]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Events
        $calendarEvents = TableRegistry::get('calendar_events');
		$calendarEventsData = $calendarEvents->find()
				->select([
					'created' => 'calendar_events.created',
					'modified' => 'calendar_events.modified',
				])
				->where([$calendarEvents->aliasField('institution_id') => $institutionId])
                ->order(['calendar_events.modified'=>'desc'])
				->limit(1)
				->first();
		$data[1]['feature'] = 'Calendar';		;
		if(!empty($calendarEventsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[1]['complete'] = 'yes';
            $data[1]['profileComplete'] = $profileComplete;
            $data[1]['modifiedDate'] = date("F j,Y",strtotime($calendarEventsData->modified));
		} else {
            $data[1]['complete'] = 'no';
            $data[1]['profileComplete'] = 0;
            $data[1]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Contacts
        $institutionContactPersons = TableRegistry::get('institution_contact_persons');
		$institutionContactPersonsData = $institutionContactPersons->find()
				->select([
					'created' => 'institution_contact_persons.created',
					'modified' => 'institution_contact_persons.modified',
				])
				->where([$institutionContactPersons->aliasField('institution_id') => $institutionId])
                ->order(['institution_contact_persons.modified'=>'desc'])
				->limit(1)
				->first();

		$data[2]['feature'] = 'Contacts';
		if(!empty($institutionContactPersonsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[2]['complete'] = 'yes';
            $data[2]['profileComplete'] = $profileComplete;
            $data[2]['modifiedDate'] = date("F j,Y",strtotime($institutionContactPersonsData->modified));
		} else {
            $data[2]['complete'] = 'no';
            $data[2]['profileComplete'] = 0;
            $data[2]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Shifts
        $institutionShifts = TableRegistry::get('institution_shifts');
		$institutionShiftsData = $institutionShifts->find()
				->select([
					'created' => 'institution_shifts.created',
					'modified' => 'institution_shifts.modified',
				])
				->where([$institutionShifts->aliasField('institution_id') => $institutionId])
				->order(['institution_shifts.modified'=>'desc'])
                ->limit(1)
				->first();
		$data[3]['feature'] = 'Shifts';
		if(!empty($institutionShiftsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[3]['complete'] = 'yes';
            $data[3]['profileComplete'] = $profileComplete;
		    $data[3]['modifiedDate'] = ($institutionShiftsData->modified)?date("F j,Y",strtotime($institutionShiftsData->modified)):date("F j,Y",strtotime($institutionShiftsData->created));
		} else {
            $data[3]['complete'] = 'no';
            $data[3]['profileComplete'] = 0;
            $data[3]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Programmes
        $institutionProgrammes = TableRegistry::get('institution_grades');
		$institutionProgrammesData = $institutionProgrammes->find()
				->select([
					'created' => 'institution_grades.created',
					'modified' => 'institution_grades.modified',
				])
				->where([$institutionProgrammes->aliasField('institution_id') => $institutionId])
                ->order(['institution_grades.modified'=>'desc'])
				->limit(1)
				->first();

		$data[4]['feature'] = 'Programmes';
		if(!empty($institutionProgrammesData)) {
			$profileComplete = $profileComplete + 1;
		    $data[4]['complete'] = 'yes';
            $data[4]['profileComplete'] = $profileComplete;
		    $data[4]['modifiedDate'] = ($institutionProgrammesData->modified)?date("F j,Y",strtotime($institutionProgrammesData->modified)):date("F j,Y",strtotime($institutionProgrammesData->created));
		} else {
            $data[4]['complete'] = 'no';
            $data[4]['profileComplete'] = 0;
            $data[4]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
         //Classes
        $institutionClasses = TableRegistry::get('institution_classes');
		$institutionClassesData = $institutionClasses->find()
				->select([
					'created' => 'institution_classes.created',
					'modified' => 'institution_classes.modified',
				])
				->where([$institutionClasses->aliasField('institution_id') => $institutionId])
                ->order(['institution_classes.modified'=>'desc'])
				->limit(1)
				->first();

		$data[5]['feature'] = 'Classes';
		if(!empty($institutionClassesData)) {
			$profileComplete = $profileComplete + 1;
		    $data[5]['complete'] = 'yes';
            $data[5]['profileComplete'] = $profileComplete;
		    $data[5]['modifiedDate'] = ($institutionClassesData->modified)?date("F j,Y",strtotime($institutionClassesData->modified)):date("F j,Y",strtotime($institutionClassesData->created));
		} else {
            $data[5]['complete'] = 'no';
            $data[5]['profileComplete'] = 0;
            $data[5]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
         //Subjects
        $institutionSubjects = TableRegistry::get('institution_subjects');
		$institutionSubjectsData = $institutionSubjects->find()
				->select([
					'created' => 'institution_subjects.created',
					'modified' => 'institution_subjects.modified',
				])
				->where([$institutionSubjects->aliasField('institution_id') => $institutionId])
                ->order(['institution_subjects.modified'=>'desc'])
				->limit(1)
				->first();

		$data[6]['feature'] = 'Subjects';
		if(!empty($institutionSubjectsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[6]['complete'] = 'yes';
            $data[6]['profileComplete'] = $profileComplete;
		    $data[6]['modifiedDate'] = ($institutionSubjectsData->modified)?date("F j,Y",strtotime($institutionSubjectsData->modified)):date("F j,Y",strtotime($institutionSubjectsData->created));
		} else {
            $data[6]['complete'] = 'no';
            $data[6]['profileComplete'] = 0;
            $data[6]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
         //Textbooks
        $institutionTextbooks = TableRegistry::get('institution_textbooks');
		$institutionTextbooksData = $institutionTextbooks->find()
				->select([
					'created' => 'institution_textbooks.created',
					'modified' => 'institution_textbooks.modified',
				])
				->where([$institutionTextbooks->aliasField('institution_id') => $institutionId])
                ->order(['institution_textbooks.modified'=>'desc'])
				->limit(1)
				->first();

		$data[7]['feature'] = 'Textbooks';
		if(!empty($institutionTextbooksData)) {
			$profileComplete = $profileComplete + 1;
		    $data[7]['complete'] = 'yes';
            $data[7]['profileComplete'] = $profileComplete;
		    $data[7]['modifiedDate'] = ($institutionTextbooksData->modified)?date("F j,Y",strtotime($institutionTextbooksData->modified)):date("F j,Y",strtotime($institutionSubjectsData->created));
		} else {
            $data[7]['complete'] = 'no';
            $data[7]['profileComplete'] = 0;
            $data[7]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
         //Students
        $institutionStudents = TableRegistry::get('institution_students');
		$institutionStudentsData = $institutionStudents->find()
				->select([
					'created' => 'institution_students.created',
					'modified' => 'institution_students.modified',
				])
				->where([$institutionStudents->aliasField('institution_id') => $institutionId])
                ->order(['institution_students.modified'=>'desc'])
				->limit(1)
				->first();

		$data[8]['feature'] = 'Students';
		if(!empty($institutionStudentsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[8]['complete'] = 'yes';
            $data[8]['profileComplete'] = $profileComplete;
		    $data[8]['modifiedDate'] = ($institutionStudentsData->modified)?date("F j,Y",strtotime($institutionStudentsData->modified)):date("F j,Y",strtotime($institutionSubjectsData->created));
		} else {
            $data[8]['complete'] = 'no';
            $data[8]['profileComplete'] = 0;
            $data[8]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Staff
        $institutionStaff = TableRegistry::get('institution_staff');
		$institutionStaffData = $institutionStaff->find()
				->select([
					'created' => 'institution_staff.created',
					'modified' => 'institution_staff.modified',
				])
				->where([$institutionStaff->aliasField('institution_id') => $institutionId])
                ->order(['institution_staff.modified'=>'desc'])
				->limit(1)
				->first();

		$data[9]['feature'] = 'Staff';
		if(!empty($institutionStaffData)) {
			$profileComplete = $profileComplete + 1;
		    $data[9]['complete'] = 'yes';
            $data[9]['profileComplete'] = $profileComplete;
		    $data[9]['modifiedDate'] = ($institutionStaffData->modified)?date("F j,Y",strtotime($institutionStaffData->modified)):date("F j,Y",strtotime($institutionStaffData->created));
		} else {
            $data[9]['complete'] = 'no';
            $data[9]['profileComplete'] = 0;
            $data[9]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Attendance
        $institutionAttendance = TableRegistry::get('institution_staff_attendances');
		$institutionAttendanceData = $institutionAttendance->find()
				->select([
					'created' => 'institution_staff_attendances.created',
					'modified' => 'institution_staff_attendances.modified',
				])
				->where([$institutionAttendance->aliasField('institution_id') => $institutionId])
                ->order(['institution_staff_attendances.modified'=>'desc'])
				->limit(1)
				->first();

		$data[10]['feature'] = 'Attendance';
		if(!empty($institutionAttendanceData)) {
			$profileComplete = $profileComplete + 1;
		    $data[10]['complete'] = 'yes';
            $data[10]['profileComplete'] = $profileComplete;
		    $data[10]['modifiedDate'] = ($institutionAttendanceData->modified)?date("F j,Y",strtotime($institutionAttendanceData->modified)):date("F j,Y",strtotime($institutionAttendanceData->created));
		} else {
            $data[10]['complete'] = 'no';
            $data[10]['profileComplete'] = 0;
            $data[10]['modifiedDate'] = 'Not updated';
        }

/********************************************* */
        //Behaviour
        $institutionBehaviour = TableRegistry::get('staff_behaviours');
		$institutionBehaviourData = $institutionBehaviour->find()
				->select([
					'created' => 'staff_behaviours.created',
					'modified' => 'staff_behaviours.modified',
				])
				->where([$institutionBehaviour->aliasField('institution_id') => $institutionId])
                ->order(['staff_behaviours.modified'=>'desc'])
				->limit(1)
				->first();

		$data[11]['feature'] = 'Behaviour';
		if(!empty($institutionBehaviourData)) {
			$profileComplete = $profileComplete + 1;
		    $data[11]['complete'] = 'yes';
            $data[11]['profileComplete'] = $profileComplete;
		    $data[11]['modifiedDate'] = ($institutionBehaviourData->modified)?date("F j,Y",strtotime($institutionBehaviourData->modified)):date("F j,Y",strtotime($institutionBehaviourData->created));;
		} else {
            $data[11]['complete'] = 'no';
            $data[11]['profileComplete'] = 0;
            $data[11]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Positions
        $institutionPositions = TableRegistry::get('institution_positions');
		$institutionPositionsData = $institutionPositions->find()
				->select([
					'created' => 'institution_positions.created',
					'modified' => 'institution_positions.modified',
				])
				->where([$institutionPositions->aliasField('institution_id') => $institutionId])
                ->order(['institution_positions.modified'=>'desc'])
				->limit(1)
				->first();

		$data[12]['feature'] = 'Positions';
		if(!empty($institutionPositionsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[12]['complete'] = 'yes';
            $data[12]['profileComplete'] = $profileComplete;
		    $data[12]['modifiedDate'] = ($institutionPositionsData->modified)?date("F j,Y",strtotime($institutionPositionsData->modified)):date("F j,Y",strtotime($institutionPositionsData->created));
		} else {
            $data[12]['complete'] = 'no';
            $data[12]['profileComplete'] = 0;
            $data[12]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Bank Accounts
        $institutionBankAccounts  = TableRegistry::get('institution_bank_accounts');
		$institutionBankAccountsData = $institutionBankAccounts->find()
				->select([
					'created' => 'institution_bank_accounts.created',
					'modified' => 'institution_bank_accounts.modified',
				])
				->where([$institutionBankAccounts->aliasField('institution_id') => $institutionId])
                ->order(['institution_bank_accounts.modified'=>'desc'])
				->limit(1)
				->first();

		$data[13]['feature'] = 'Bank Accounts';
		if(!empty($institutionBankAccountsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[13]['complete'] = 'yes';
            $data[13]['profileComplete'] = $profileComplete;
		    $data[13]['modifiedDate'] = ($institutionBankAccountsData->modified)?date("F j,Y",strtotime($institutionBankAccountsData->modified)):date("F j,Y",strtotime($institutionBankAccountsData->created));
		} else {
            $data[13]['complete'] = 'no';
            $data[13]['profileComplete'] = 0;
            $data[13]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Institution Fees
        $institutionInstitutionFees = TableRegistry::get('institution_fees');
		$institutionInstitutionFeesData = $institutionInstitutionFees->find()
				->select([
					'created' => 'institution_fees.created',
					'modified' => 'institution_fees.modified',
				])
				->where([$institutionInstitutionFees->aliasField('institution_id') => $institutionId])
                ->order(['institution_fees.modified'=>'desc'])
				->limit(1)
				->first();

		$data[14]['feature'] = 'Institution Fees';
		if(!empty($institutionInstitutionFeesData)) {
			$profileComplete = $profileComplete + 1;
		    $data[14]['complete'] = 'yes';
            $data[14]['profileComplete'] = $profileComplete;
		    $data[14]['modifiedDate'] = ($institutionInstitutionFeesData->modified)?date("F j,Y",strtotime($institutionInstitutionFeesData->modified)):date("F j,Y",strtotime($institutionInstitutionFeesData->created));
		} else {
            $data[14]['complete'] = 'no';
            $data[14]['profileComplete'] = 0;
            $data[14]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        // Student Fees
        // $institutionStudentFees  = TableRegistry::get('student_fees');
		// $institutionStudentFeesData = $institutionStudentFees->find()
		// 		->select([
		// 			'created' => 'student_fees.created',
		// 			'modified' => 'student_fees.modified',
		// 		])
		// 		->where([$institutionStudentFees->aliasField('institution_id') => $institutionId])
		// 		->limit(1)
		// 		->first();

		// $data[15]['feature'] = 'Student Fees';
		// if(!empty($institutionStudentFeesData)) {
		// 	$profileComplete = $profileComplete + 1;
		//     $data[15]['complete'] = 'yes';
		//     $data[15]['modifiedDate'] = date("F j,Y",strtotime($institutionStudentFeesData->modified));
		// } else {
        //     $data[15]['complete'] = 'no';
        //     $data[15]['modifiedDate'] = 'Not updated';
        // }
/********************************************* */
        //Infrastructures Overview
        $institutionInfrastructuresOverview  = TableRegistry::get('institution_lands');
		$institutionInfrastructuresOverviewData = $institutionInfrastructuresOverview->find()
				->select([
					'created' => 'institution_lands.created',
					'modified' => 'institution_lands.modified',
				])
				->where([$institutionInfrastructuresOverview->aliasField('institution_id') => $institutionId])
                ->order(['institution_lands.modified'=>'desc'])
				->limit(1)
				->first();

		$data[16]['feature'] = 'Infrastructures Overview';
		if(!empty($institutionInfrastructuresOverviewData)) {
			$profileComplete = $profileComplete + 1;
		    $data[16]['complete'] = 'yes';
            $data[16]['profileComplete'] = $profileComplete;
		    $data[16]['modifiedDate'] = ($institutionInfrastructuresOverviewData->modified)?date("F j,Y",strtotime($institutionInfrastructuresOverviewData->modified)):date("F j,Y",strtotime($institutionInfrastructuresOverviewData->created));
		} else {
            $data[16]['complete'] = 'no';
            $data[16]['profileComplete'] = 0;
            $data[16]['modifiedDate'] = 'Not updated';
        }
 /********************************************* */
        // Infrastructures Needs
        $institutionInfrastructuresNeeds  = TableRegistry::get('infrastructure_needs');
		$institutionInfrastructuresNeedsData = $institutionInfrastructuresNeeds->find()
				->select([
					'created' => 'infrastructure_needs.created',
					'modified' => 'infrastructure_needs.modified',
				])
				->where([$institutionInfrastructuresNeeds->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_needs.modified'=>'desc'])
				->limit(1)
				->first();

		$data[17]['feature'] = 'Infrastructures Needs';
		if(!empty($institutionInfrastructuresNeedsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[17]['complete'] = 'yes';
            $data[17]['profileComplete'] = $profileComplete;
		    $data[17]['modifiedDate'] = ($institutionInfrastructuresNeedsData->modified)?date("F j,Y",strtotime($institutionInfrastructuresNeedsData->modified)):date("F j,Y",strtotime($institutionInfrastructuresNeedsData->created));
		} else {
            $data[17]['complete'] = 'no';
            $data[17]['profileComplete'] = 0;
            $data[17]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        // Wash Water
        $institutionWashWater  = TableRegistry::get('infrastructure_wash_waters');
		$institutionWashWaterData = $institutionWashWater->find()
				->select([
					'created' => 'infrastructure_wash_waters.created',
					'modified' => 'infrastructure_wash_waters.modified',
				])
				->where([$institutionWashWater->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_waters.modified'=>'desc'])
				->limit(1)
				->first();

		$data[18]['feature'] = 'Wash Water';
		if(!empty($institutionWashWaterData)) {
			$profileComplete = $profileComplete + 1;
		    $data[18]['complete'] = 'yes';
            $data[18]['profileComplete'] = $profileComplete;
		    $data[18]['modifiedDate'] = ($institutionWashWaterData->modified)?date("F j,Y",strtotime($institutionWashWaterData->modified)):date("F j,Y",strtotime($institutionWashWaterData->created));
		} else {
            $data[18]['complete'] = 'no';
            $data[18]['profileComplete'] = 0;
            $data[18]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        // Wash Hygiene
        $institutionWashHygiene  = TableRegistry::get('infrastructure_wash_hygienes');
		$institutionWashHygieneData = $institutionWashHygiene->find()
				->select([
					'created' => 'infrastructure_wash_hygienes.created',
					'modified' => 'infrastructure_wash_hygienes.modified',
				])
				->where([$institutionWashHygiene->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_hygienes.modified'=>'desc'])
				->limit(1)
				->first();

		$data[19]['feature'] = 'Wash Hygiene';
		if(!empty($institutionWashHygieneData)) {
			$profileComplete = $profileComplete + 1;
		    $data[19]['complete'] = 'yes';
            $data[19]['profileComplete'] = $profileComplete;
		    $data[19]['modifiedDate'] = ($institutionWashHygieneData->modified)?date("F j,Y",strtotime($institutionWashHygieneData->modified)):date("F j,Y",strtotime($institutionWashHygieneData->created));
		} else {
            $data[19]['complete'] = 'no';
            $data[19]['profileComplete'] = 0;
            $data[19]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        // Wash Waste
        $institutionWashWaste  = TableRegistry::get('infrastructure_wash_wastes');
		$institutionWashWasteData = $institutionWashWaste->find()
				->select([
					'created' => 'infrastructure_wash_wastes.created',
					'modified' => 'infrastructure_wash_wastes.modified',
				])
				->where([$institutionWashWaste->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_wastes.modified'=>'desc'])
				->limit(1)
				->first();

		$data[20]['feature'] = 'Wash Waste';
		if(!empty($institutionWashWasteData)) {
			$profileComplete = $profileComplete + 1;
		    $data[20]['complete'] = 'yes';
            $data[20]['profileComplete'] = $profileComplete;
		    $data[20]['modifiedDate'] = ($institutionWashWasteData->modified)?date("F j,Y",strtotime($institutionWashWasteData->modified)):date("F j,Y",strtotime($institutionWashWasteData->created));
		} else {
            $data[20]['complete'] = 'no';
            $data[20]['profileComplete'] = 0;
            $data[20]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        // Wash Sewage
        $institutionWashSewage  = TableRegistry::get('infrastructure_wash_sewages');
		$institutionWashSewageData = $institutionWashSewage->find()
				->select([
					'created' => 'infrastructure_wash_sewages.created',
					'modified' => 'infrastructure_wash_sewages.modified',
				])
				->where([$institutionWashSewage->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_sewages.modified'=>'desc'])
				->limit(1)
				->first();

		$data[21]['feature'] = 'Wash Sewage';
		if(!empty($institutionWashSewageData)) {
			$profileComplete = $profileComplete + 1;
		    $data[21]['complete'] = 'yes';
            $data[21]['profileComplete'] = $profileComplete;
		    $data[21]['modifiedDate'] = ($institutionWashSewageData->modified)?date("F j,Y",strtotime($institutionWashSewageData->modified)):date("F j,Y",strtotime($institutionWashSewageData->created));
		} else {
            $data[21]['complete'] = 'no';
            $data[21]['profileComplete'] = 0;
            $data[21]['modifiedDate'] = 'Not updated';
        }

/********************************************* */
        // Utilities Electricity
        $institutionUtilitiesElectricity  = TableRegistry::get('infrastructure_utility_electricities');
		$institutionUtilitiesElectricityData = $institutionUtilitiesElectricity->find()
				->select([
					'created' => 'infrastructure_utility_electricities.created',
					'modified' => 'infrastructure_utility_electricities.modified',
				])
				->where([$institutionUtilitiesElectricity->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_utility_electricities.modified'=>'desc'])
				->limit(1)
				->first();

		$data[22]['feature'] = 'Utilities Electricity';
		if(!empty($institutionUtilitiesElectricityData)) {
			$profileComplete = $profileComplete + 1;
		    $data[22]['complete'] = 'yes';
            $data[22]['profileComplete'] = $profileComplete;
		    $data[22]['modifiedDate'] = ($institutionUtilitiesElectricityData->modified)?date("F j,Y",strtotime($institutionUtilitiesElectricityData->modified)):date("F j,Y",strtotime($institutionUtilitiesElectricityData->created));
		} else {
            $data[22]['complete'] = 'no';
            $data[22]['profileComplete'] = 0;
            $data[22]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        // Utilities Internet
        $institutionUtilitiesInternet  = TableRegistry::get('infrastructure_utility_internets');
		$institutionUtilitiesInternetData = $institutionUtilitiesInternet->find()
				->select([
					'created' => 'infrastructure_utility_internets.created',
					'modified' => 'infrastructure_utility_internets.modified',
				])
				->where([$institutionUtilitiesInternet->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_utility_internets.modified'=>'desc'])
				->limit(1)
				->first();

		$data[23]['feature'] = 'Utilities Internet';
		if(!empty($institutionUtilitiesInternetData)) {
			$profileComplete = $profileComplete + 1;
		    $data[23]['complete'] = 'yes';
            $data[23]['profileComplete'] = $profileComplete;
		    $data[23]['modifiedDate'] = ($institutionUtilitiesInternetData->modified)?date("F j,Y",strtotime($institutionUtilitiesInternetData->modified)):date("F j,Y",strtotime($institutionUtilitiesInternetData->created));
		} else {
            $data[23]['complete'] = 'no';
            $data[23]['profileComplete'] = 0;
            $data[23]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Utilities Telephone
        $institutionUtilitiesTelephone  = TableRegistry::get('infrastructure_utility_telephones');
		$institutionUtilitiesTelephoneData = $institutionUtilitiesTelephone->find()
				->select([
					'created' => 'infrastructure_utility_telephones.created',
					'modified' => 'infrastructure_utility_telephones.modified',
				])
				->where([$institutionUtilitiesTelephone->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_utility_telephones.modified'=>'desc'])
				->limit(1)
				->first();

		$data[24]['feature'] = 'Utilities Telephone';
		if(!empty($institutionUtilitiesTelephoneData)) {
			$profileComplete = $profileComplete + 1;
		    $data[24]['complete'] = 'yes';
            $data[24]['profileComplete'] = $profileComplete;
		    $data[24]['modifiedDate'] = ($institutionUtilitiesTelephoneData->modified)?date("F j,Y",strtotime($institutionUtilitiesTelephoneData->modified)):date("F j,Y",strtotime($institutionUtilitiesTelephoneData->created));
		} else {
            $data[24]['complete'] = 'no';
            $data[24]['profileComplete'] = 0;
            $data[24]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        // Assets
        $institutionAssets  = TableRegistry::get('institution_assets');
		$institutionAssetsData = $institutionAssets->find()
				->select([
					'created' => 'institution_assets.created',
					'modified' => 'institution_assets.modified',
				])
				->where([$institutionAssets->aliasField('institution_id') => $institutionId])
                ->order(['institution_assets.modified'=>'desc'])
				->limit(1)
				->first();

		$data[25]['feature'] = 'Assets';
		if(!empty($institutionAssetsData)) {
			$profileComplete = $profileComplete + 1;
		    $data[25]['complete'] = 'yes';
            $data[25]['profileComplete'] = $profileComplete;
		    $data[25]['modifiedDate'] = ($institutionAssetsData->modified)?date("F j,Y",strtotime($institutionAssetsData->modified)):date("F j,Y",strtotime($institutionAssetsData->created));
		} else {
            $data[25]['complete'] = 'no';
            $data[25]['profileComplete'] = 0;
            $data[25]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Transport
        $institutionTransport  = TableRegistry::get('institution_buses');
		$institutionTransportData = $institutionTransport->find()
				->where([$institutionTransport->aliasField('institution_id') => $institutionId])
                ->order(['institution_buses.modified'=>'desc'])
				->limit(1)
				->first();

		$data[26]['feature'] = 'Transport';
		if(!empty($institutionTransportData)) {
			$profileComplete = $profileComplete + 1;
		    $data[26]['complete'] = 'yes';
            $data[26]['profileComplete'] = $profileComplete;
		    $data[26]['modifiedDate'] = ($institutionTransportData->modified)?date("F j,Y",strtotime($institutionTransportData->modified)):date("F j,Y",strtotime($institutionTransportData->created));
		} else {
            $data[26]['complete'] = 'no';
            $data[26]['profileComplete'] = 0;
            $data[26]['modifiedDate'] = 'Not updated';
        }
/********************************************* */
        //Committees
        $institutionCommittees  = TableRegistry::get('institution_committees');
		$institutionCommitteesData = $institutionCommittees->find()
				->select([
					'created' => 'institution_committees.created',
					'modified' => 'institution_committees.modified',
				])
				->where([$institutionCommittees->aliasField('institution_id') => $institutionId])
                ->order(['institution_committees.modified'=>'desc'])
				->limit(1)
				->first();

		$data[27]['feature'] = 'Committees';
		if(!empty($institutionCommitteesData)) {
			$profileComplete = $profileComplete + 1;
		    $data[27]['complete'] = 'yes';
            $data[27]['profileComplete'] = $profileComplete;
		    $data[27]['modifiedDate'] = ($institutionCommitteesData->modified)?date("F j,Y",strtotime($institutionCommitteesData->modified)):date("F j,Y",strtotime($institutionCommitteesData->created));
		} else {
            $data[27]['complete'] = 'no';
            $data[27]['profileComplete'] = 0;
            $data[27]['modifiedDate'] = 'Not updated';
        }
        // $percent = $profileComplete/$totalProfileCount * 100;
		// $institutionPercentage = round($percent);
        // $profilePercentage = 100/$totalProfileCount * $profileComplete;
		// $profilePercentage = round($profilePercentage);
		//$data['percentage'] = $profilePercentage;

        //Config validation
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $typeList = $ConfigItem
            ->find('list', [
                'keyField' => 'name',
                'valueField' => 'name'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1,$ConfigItem->aliasField('value') => 1,$ConfigItem->aliasField('type') => 'Institution Completeness'])
            ->toArray();

        $typeOptions = array_keys($typeList);
        $totalProfileComplete = count($data);
        $typeListDisable = $ConfigItem
            ->find('list', [
                'keyField' => 'name',
                'valueField' => 'name'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1,$ConfigItem->aliasField('value') => 0,$ConfigItem->aliasField('type') => 'Institution Completeness'])
            ->toArray();
            if ($typeListDisable) {
                $countList = count($typeListDisable);
                $profileComplete = $profileComplete - $countList;
            }

        foreach($data as $key => $featureData) {
            if (!in_array($featureData['feature'], $typeOptions)) {
                unset($data[$key]);
                $totalProfileComplete = count($data);
                }
        }

            $profilePercentage = 100/$totalProfileComplete * $profileComplete;
            $profilePercentage = round($profilePercentage);
            $data['percentage'] = $profilePercentage;
            return $data;
    }
    //autocomplete used for InstitutionSiteShift
    public function ajaxInstitutionAutocomplete()
    {
        $this->ControllerAction->autoRender = false;
        $data = [];
        $Institutions = TableRegistry::get('Institution.Institutions');
        if ($this->request->is(['ajax'])) {
            $term = trim($this->request->query['term']);
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $params['conditions'] = [$Institutions->aliasField('id').' IS NOT ' => $institutionId];
            if (!empty($term)) {
                $data = $Institutions->autocomplete($term, $params);
            }

            echo json_encode($data);
            die;
        }
    }

    public function getUserTabElements($options = [])
    {
        $encodedParam = $this->request->params['pass'][1]; //get the encoded param from URL

        $userRole = (array_key_exists('userRole', $options))? $options['userRole']: null;
        $action = (array_key_exists('action', $options))? $options['action']: 'add';
        $id = (array_key_exists('id', $options))? $options['id']: 0;
        $userId = (array_key_exists('userId', $options))? $options['userId']: 0;
        $type = 'Students';

        switch ($userRole) {
            case 'Staff':
                $pluralUserRole = 'Staff'; // inflector unable to handle
                $type = 'Staff';
                break;
            default:
                $pluralUserRole = Inflector::pluralize($userRole);
                break;
        }

        $url = ['plugin' => $this->plugin, 'controller' => $this->name];
        $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];

        $tabElements = [
            $pluralUserRole => ['text' => __('Academic')],
            $userRole.'User' => ['text' => __('Overview')],
            $userRole.'Account' => ['text' => __('Account')],

            // $userRole.'Nationality' => ['text' => __('Identities')],
        ];

        $studentTabElements = [
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => [
                'url' => [
                    'plugin' => $this->plugin,
                    'controller' => $this->name,
                    'action' => 'Nationalities',
                    $id
                ],
                'text' => __('Nationalities'),
                'urlModel' => 'Nationalities'
            ],
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'Guardians' => ['text' => __('Guardians')],
            'StudentTransport' => ['text' => __('Transport')]
        ];

        if ($type == 'Staff') {
            $studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
            unset($studentTabElements['Guardians']);
            unset($studentTabElements['StudentTransport']);   // Only Student has Transport tab
        }

        $tabElements = array_merge($tabElements, $studentTabElements);

        if ($action == 'add') {
            $tabElements[$pluralUserRole]['url'] = array_merge($url, ['action' => $pluralUserRole, 'add']);
            $tabElements[$userRole.'User']['url'] = array_merge($url, ['action' => $userRole.'User', 'add']);
            $tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'add']);
        } else {
            unset($tabElements[$pluralUserRole]);
            // $tabElements[$pluralUserRole]['url'] = array_merge($url, ['action' => $pluralUserRole, 'view']);
            $tabElements[$userRole.'User']['url'] = array_merge($url, ['action' => $userRole.'User', 'view']);
            $tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'view']);

            // $tabElements[$userRole.'Account']['url'] = array_merge($url, ['action' => $userRole.'Account', 'view']);

            $securityUserId = $this->ControllerAction->paramsDecode($encodedParam)['id'];

            foreach ($studentTabElements as $key => $value) {
                $urlModel = (array_key_exists('urlModel', $value))? $value['urlModel'] : $key;

                $tempParam = [];
                $tempParam['action'] = $urlModel;
                $tempParam[] = 'index';

                $url = $this->ControllerAction
                        ->setQueryString(
                            array_merge($studentUrl, $tempParam),
                            ['security_user_id' => $securityUserId]
                        );

                if ($key == 'Comments') {
                    $institutionId = $this->request->session()->read('Institution.Institutions.id');

                    $url = [
                        'plugin' => 'Institution',
                        'institutionId' => $this->paramsEncode(['id' => $institutionId]),
                        'controller' => $userRole.'Comments',
                        'action' => 'index'
                    ];
                    $url = $this->ControllerAction->setQueryString($url, ['security_user_id' => $securityUserId]);
                }

                $tabElements[$key]['url'] = $url;
            }
        }

        foreach ($tabElements as $key => $tabElement) {
            $params = [];
            switch ($key) {
                case $userRole.'User':
                    $params = [$this->ControllerAction->paramsEncode(['id' => $userId]), 'id' => $id];
                    break;
                case $userRole.'Account':
                    $params = [$this->ControllerAction->paramsEncode(['id' => $userId]), 'id' => $id];
                    break;
            }
            $tabElements[$key]['url'] = array_merge($tabElements[$key]['url'], $params);
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        $session = $this->request->session();
        $session->write('Institution.'.$type.'.tabElements', $tabElements);

        return $tabElements;
    }

    public function getAcademicTabElements($options = [])
    {
        $id = (array_key_exists('id', $options))? $options['id']: 0;
        $type = (array_key_exists('type', $options))? $options['type']: null;

        $tabElements = [];
        $studentTabElements = [
            'Programmes' => ['text' => __('Programmes')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Absences' => ['text' => __('Absences')],
            'Behaviours' => ['text' => __('Behaviours')],
            'Outcomes' => ['text' => __('Outcomes')],
            'Competencies' => ['text' => __('Competencies')],
            'Results' => ['text' => __('Assessments')],
            'ExaminationResults' => ['text' => __('Examinations')],
            'ReportCards' => ['text' => __('Report Cards')],
            'Awards' => ['text' => __('Awards')],
            'Extracurriculars' => ['text' => __('Extracurriculars')],
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Associations')]
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        // Programme will use institution controller, other will be still using student controller
        foreach ($studentTabElements as $key => $tab) {
            if (in_array($key, ['Programmes', 'Textbooks', 'Risks','Associations'])) {
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            } else {
                $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
            }
        }
        //echo '<pre>';print_r($tabElements);die;
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getCareerTabElements($options = [])
    {
        $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
            $options['institution_id'] = $institutionId;
        }
        return TableRegistry::get('Staff.Staff')->getCareerTabElements($options);
    }

    public function getTrainingTabElements($options = [])
    {
        $tabElements = [];
        $trainingUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        $trainingTabElements = [
            'StaffTrainingNeeds' => ['text' => __('Needs')],
            'StaffTrainingApplications' => ['text' => __('Applications')],
            'StaffTrainingResults' => ['text' => __('Results')],
            'Courses' => ['text' => __('Courses')]
        ];

        $tabElements = array_merge($tabElements, $trainingTabElements);

        foreach ($trainingTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($trainingUrl, ['action' => $key, 'index']);

            if ($key == 'Courses') {
                $trainingUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
                $tabElements[$key]['url'] = array_merge($trainingUrl, ['action' => $key, 'index']);
            }
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getProfessionalTabElements($options = [])
    {
        $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        $tabElements = TableRegistry::get('Staff.Staff')->getProfessionalTabElements($options);
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getCompetencyTabElements($options = [])
    {
        $queryString = $this->request->query('queryString');
        $tabElements = [
            'StudentCompetencies' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentCompetencies', 'view', 'queryString' => $queryString],
                'text' => __('Items')
            ],
            'StudentCompetencyComments' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentCompetencyComments', 'view', 'queryString' => $queryString],
                'text' => __('Periods')
            ]
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getInstitutionPositions($institutionId, $fte, $startDate, $endDate)
    {
        if ($endDate == 'null') {
            $endDate = null;
        }

        $this->autoRender= false;
        $StaffTable = TableRegistry::get('Institution.Staff');
        $positionTable = TableRegistry::get('Institution.InstitutionPositions');

        $userId = $this->Auth->user('id');

        $selectedFTE = empty($fte) ? 0 : $fte;
        $excludePositions = $StaffTable->find();

        $startDate = new Date($startDate);

        $excludePositions = $excludePositions
            ->select([
                'position_id' => $StaffTable->aliasField('institution_position_id'),
            ])
            ->where([
                $StaffTable->aliasField('institution_id') => $institutionId,
            ])
            ->group($StaffTable->aliasField('institution_position_id'))
            ->having([
                'OR' => [
                    'SUM('.$StaffTable->aliasField('FTE') .') >= ' => 1,
                    'SUM('.$StaffTable->aliasField('FTE') .') > ' => (1-$selectedFTE),
                ]
            ])
            ->hydrate(false);

        if (!empty($endDate)) {
            $endDate = new Date($endDate);
            $excludePositions = $excludePositions->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate]);
        } else {
            $orCondition = [
                $StaffTable->aliasField('end_date') . ' >= ' => $startDate,
                $StaffTable->aliasField('end_date') . ' IS NULL'
            ];
            $excludePositions = $excludePositions->where([
                    'OR' => $orCondition
                ]);
        }

        if ($this->AccessControl->isAdmin()) {
            $userId = null;
            $roles = [];
        } else {
            $roles = $StaffTable->Institutions->getInstitutionRoles($userId, $institutionId);
        }

        // Filter by active status
        $activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
        $positionConditions = [];
        $positionConditions[$StaffTable->Positions->aliasField('institution_id')] = $institutionId;
        if (!empty($activeStatusId)) {
            $positionConditions[$StaffTable->Positions->aliasField('status_id').' IN '] = $activeStatusId;
        }

        if ($selectedFTE > 0) {
            $staffPositionsOptions = $StaffTable->Positions
                ->find()
                ->innerJoinWith('StaffPositionTitles.SecurityRoles')
                ->innerJoinWith('StaffPositionGrades')
                ->where($positionConditions)
                ->select(['security_role_id' => 'SecurityRoles.id', 'type' => 'StaffPositionTitles.type', 'grade_name' => 'StaffPositionGrades.name'])
                ->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order'])
                ->autoFields(true)
                ->toArray();
        } else {
            $staffPositionsOptions = [];
        }

        // Filter by role previlege
        $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
        $roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
        $roleOptions = array_keys($roleOptions);
        $staffPositionRoles = $this->array_column($staffPositionsOptions, 'security_role_id');
        $staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));

        // POCOR-4269 same staff cant add to same position regardsless the FTE
        $openemisNo = $this->request->params['pass'][3];
        $positionHeldByStaff = $StaffTable->find()
            ->select([
                'position_id' => $StaffTable->aliasField('institution_position_id'),
            ])
            ->contain(['Users'])
            ->where([
                $StaffTable->aliasField('institution_id') => $institutionId,
                'Users.openemis_no' => $openemisNo
            ])
            ->hydrate(false)
            ->toArray();
        // end POCOR-4269

        // Adding the opt group
        $types = $this->getSelectOptions('Staff.position_types');
        $options = [];
        $excludePositions = array_column($excludePositions->toArray(), 'position_id');

        // POCOR-4269 if staff already held some position that position is unavailable anymore.
        if (!empty($positionHeldByStaff)) {
            foreach ($positionHeldByStaff as $value) {
                $positionId = $value['position_id'];
                if (!in_array($positionId, $excludePositions)) {
                    $excludePositions[] = $positionId;
                }
            }
        }
        // end POCOR-4269

        foreach ($staffPositionsOptions as $position) {
            $name = $position->name . ' - ' . $position->grade_name;

            $type = __($types[$position->type]);
            $options[] = ['value' => $position->id, 'group' => $type, 'name' => $name, 'disabled' => in_array($position->id, $excludePositions)];
        }

        $this->response->body(json_encode($options, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');

        return $this->response;
    }

    public function getStatusPermission($model)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $isActive = $this->Institutions->isActive($institutionId);

        // institution status is INACTIVE
        if (!$isActive) {
            if (in_array($model->alias(), $this->features)) { // check the feature list
                // off the import action
                if ($model->behaviors()->has('ImportLink')) {
                    $model->removeBehavior('ImportLink');
                }

                if ($model instanceof \App\Model\Table\ControllerActionTable) {
                    // CAv4 off the add/edit/remove action
                    $model->toggle('add', false);
                    $model->toggle('edit', false);
                    $model->toggle('remove', false);
                } elseif ($model instanceof \App\Model\Table\AppTable) {
                    // CAv3 hide button and redirect when user change the Url
                    $model->addBehavior('ControllerAction.HideButton');
                }
            }
        }
    }

    public function ajaxGetReportCardStatusProgress()
    {
        $this->autoRender = false;
        $dataSet = [];

        if (isset($this->request->query['ids'])) {
            $ids = $this->request->query['ids'];

            $academicPeriodId = $this->request->query('academic_period_id');
            $reportCardId = $this->request->query('report_card_id');
            $institutionId = $this->request->query('institution_id');

            $institutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $reportCardProcesses = TableRegistry::get('ReportCard.ReportCardProcesses');
            $institutionStudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');

            if (!empty($ids)) {

                $results = $institutionClasses
                ->find()
                ->select([
                    'id','name','institution_id',

                    'inProcess' => $reportCardProcesses->find()->where([
                                'report_card_id' => $reportCardId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                            ])->count(),
                    'inCompleted' => $institutionStudentsReportCards->find()->where([
                                'report_card_id' => $reportCardId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                                'status' => 3
                            ])->count()
                ])
                ->where(['academic_period_id' => $academicPeriodId,
                        $institutionClasses->aliasField('id IN ') => $ids
                        ])->all();

                if (!$results->isEmpty()) {
                    foreach ($results as $key => $entity) {

                        $total = $entity->inCompleted + $entity->inProcess;
                        if ($entity->inCompleted > 0 && $entity->inProcess > 0) {
                            $data['percent'] = intval(($entity->inCompleted / $total) * 100);
                            if ($data['percent'] > 100) {
                                $data['percent'] = 100;
                            }
                        } elseif ($entity->inCompleted == $total && $entity->inProcess == 0) {
                            // if only the status is complete, than percent will be 100, total record can still be 0 if the shell excel generation is slow, and percent should not be 100.
                            $data['percent'] = 100;
                            $data['modified'] = 'Completed';
                            $data['expiry_date'] = '100%';
                        } else {
                            $data['percent'] = 0;
                            $data['modified'] = 'In Progress';
                            $data['expiry_date'] = null;
                        }

                        $dataSet[$entity->id] = $data;
                    }
                }
            }
        }

        echo json_encode($dataSet);
        die;
    }
    // Delete commitee meeting
    public function deleteCommiteeMeetingById() {
        if (isset($this->request->query['meetingId'])) {
            $meetingId = $this->request->query['meetingId'];

            $users_table = TableRegistry::get('institution_committee_meeting');
            $users = $users_table->get($meetingId);
            $users_table->delete($users);
            echo "Meeting deleted successfully.";
            die;
        }
    }
    /**
     * Get intitute profile completness data
     * @return array
     */
     public function getInstituteProfileCompletnessData ($institutionId) {
        $data = array();
        $data['percentage'] = 0;
        $profileComplete = 0;
        //Overview
        $institutions = TableRegistry::get('institutions');
		$institutionsData = $institutions->find()
				->select([
					'created' => 'institutions.created',
					'modified' => 'institutions.modified',
				])
				->where([$institutions->aliasField('id') => $institutionId])
                ->order(['institutions.modified'=>'desc'])
				->limit(1)
				->first();
         //Events
        $calendarEvents = TableRegistry::get('calendar_events');
		$calendarEventsData = $calendarEvents->find()
				->select([
					'created' => 'calendar_events.created',
					'modified' => 'calendar_events.modified',
				])
				->where([$calendarEvents->aliasField('institution_id') => $institutionId])
                ->order(['calendar_events.modified'=>'desc'])
				->limit(1)
				->first();
        //Contacts
        $institutionContactPersons = TableRegistry::get('institution_contact_persons');
		$institutionContactPersonsData = $institutionContactPersons->find()
				->select([
					'created' => 'institution_contact_persons.created',
					'modified' => 'institution_contact_persons.modified',
				])
				->where([$institutionContactPersons->aliasField('institution_id') => $institutionId])
                ->order(['institution_contact_persons.modified'=>'desc'])
				->limit(1)
				->first();
        //Shifts
        $institutionShifts = TableRegistry::get('institution_shifts');
		$institutionShiftsData = $institutionShifts->find()
				->select([
					'created' => 'institution_shifts.created',
					'modified' => 'institution_shifts.modified',
				])
				->where([$institutionShifts->aliasField('institution_id') => $institutionId])
				->order(['institution_shifts.modified'=>'desc'])
                ->limit(1)
				->first();
        //Programmes
        $institutionProgrammes = TableRegistry::get('institution_grades');
		$institutionProgrammesData = $institutionProgrammes->find()
				->select([
					'created' => 'institution_grades.created',
					'modified' => 'institution_grades.modified',
				])
				->where([$institutionProgrammes->aliasField('institution_id') => $institutionId])
                ->order(['institution_grades.modified'=>'desc'])
				->limit(1)
				->first();
        //Classes
        $institutionClasses = TableRegistry::get('institution_classes');
		$institutionClassesData = $institutionClasses->find()
				->select([
					'created' => 'institution_classes.created',
					'modified' => 'institution_classes.modified',
				])
				->where([$institutionClasses->aliasField('institution_id') => $institutionId])
                ->order(['institution_classes.modified'=>'desc'])
				->limit(1)
				->first();
         //Subjects
        $institutionSubjects = TableRegistry::get('institution_subjects');
		$institutionSubjectsData = $institutionSubjects->find()
				->select([
					'created' => 'institution_subjects.created',
					'modified' => 'institution_subjects.modified',
				])
				->where([$institutionSubjects->aliasField('institution_id') => $institutionId])
                ->order(['institution_subjects.modified'=>'desc'])
				->limit(1)
				->first();
        //Textbooks
        $institutionTextbooks = TableRegistry::get('institution_textbooks');
		$institutionTextbooksData = $institutionTextbooks->find()
				->select([
					'created' => 'institution_textbooks.created',
					'modified' => 'institution_textbooks.modified',
				])
				->where([$institutionTextbooks->aliasField('institution_id') => $institutionId])
                ->order(['institution_textbooks.modified'=>'desc'])
				->limit(1)
				->first();
        //Students
        $institutionStudents = TableRegistry::get('institution_students');
		$institutionStudentsData = $institutionStudents->find()
				->select([
					'created' => 'institution_students.created',
					'modified' => 'institution_students.modified',
				])
				->where([$institutionStudents->aliasField('institution_id') => $institutionId])
                ->order(['institution_students.modified'=>'desc'])
				->limit(1)
				->first();
         //Staff
        $institutionStaff = TableRegistry::get('institution_staff');
		$institutionStaffData = $institutionStaff->find()
				->select([
					'created' => 'institution_staff.created',
					'modified' => 'institution_staff.modified',
				])
				->where([$institutionStaff->aliasField('institution_id') => $institutionId])
                ->order(['institution_staff.modified'=>'desc'])
				->limit(1)
				->first();

        //Attendance
        $institutionAttendance = TableRegistry::get('institution_staff_attendances');
		$institutionAttendanceData = $institutionAttendance->find()
				->select([
					'created' => 'institution_staff_attendances.created',
					'modified' => 'institution_staff_attendances.modified',
				])
				->where([$institutionAttendance->aliasField('institution_id') => $institutionId])
                ->order(['institution_staff_attendances.modified'=>'desc'])
				->limit(1)
				->first();

         //Behaviour
        $institutionBehaviour = TableRegistry::get('staff_behaviours');
		$institutionBehaviourData = $institutionBehaviour->find()
				->select([
					'created' => 'staff_behaviours.created',
					'modified' => 'staff_behaviours.modified',
				])
				->where([$institutionBehaviour->aliasField('institution_id') => $institutionId])
                ->order(['staff_behaviours.modified'=>'desc'])
				->limit(1)
				->first();

        //Positions
        $institutionPositions = TableRegistry::get('institution_positions');
		$institutionPositionsData = $institutionPositions->find()
				->select([
					'created' => 'institution_positions.created',
					'modified' => 'institution_positions.modified',
				])
				->where([$institutionPositions->aliasField('institution_id') => $institutionId])
                ->order(['institution_positions.modified'=>'desc'])
				->limit(1)
				->first();

        //Bank Accounts
        $institutionBankAccounts  = TableRegistry::get('institution_bank_accounts');
		$institutionBankAccountsData = $institutionBankAccounts->find()
				->select([
					'created' => 'institution_bank_accounts.created',
					'modified' => 'institution_bank_accounts.modified',
				])
				->where([$institutionBankAccounts->aliasField('institution_id') => $institutionId])
                ->order(['institution_bank_accounts.modified'=>'desc'])
				->limit(1)
				->first();

        //Institution Fees
        $institutionInstitutionFees = TableRegistry::get('institution_fees');
		$institutionInstitutionFeesData = $institutionInstitutionFees->find()
				->select([
					'created' => 'institution_fees.created',
					'modified' => 'institution_fees.modified',
				])
				->where([$institutionInstitutionFees->aliasField('institution_id') => $institutionId])
                ->order(['institution_fees.modified'=>'desc'])
				->limit(1)
				->first();

         //Infrastructures Overview
        $institutionInfrastructuresOverview  = TableRegistry::get('institution_lands');
		$institutionInfrastructuresOverviewData = $institutionInfrastructuresOverview->find()
				->select([
					'created' => 'institution_lands.created',
					'modified' => 'institution_lands.modified',
				])
				->where([$institutionInfrastructuresOverview->aliasField('institution_id') => $institutionId])
                ->order(['institution_lands.modified'=>'desc'])
				->limit(1)
				->first();

        // Infrastructures Needs
        $institutionInfrastructuresNeeds  = TableRegistry::get('infrastructure_needs');
		$institutionInfrastructuresNeedsData = $institutionInfrastructuresNeeds->find()
				->select([
					'created' => 'infrastructure_needs.created',
					'modified' => 'infrastructure_needs.modified',
				])
				->where([$institutionInfrastructuresNeeds->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_needs.modified'=>'desc'])
				->limit(1)
				->first();

        // Wash Water
        $institutionWashWater  = TableRegistry::get('infrastructure_wash_waters');
		$institutionWashWaterData = $institutionWashWater->find()
				->select([
					'created' => 'infrastructure_wash_waters.created',
					'modified' => 'infrastructure_wash_waters.modified',
				])
				->where([$institutionWashWater->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_waters.modified'=>'desc'])
				->limit(1)
				->first();

        // Wash Hygiene
        $institutionWashHygiene  = TableRegistry::get('infrastructure_wash_hygienes');
		$institutionWashHygieneData = $institutionWashHygiene->find()
				->select([
					'created' => 'infrastructure_wash_hygienes.created',
					'modified' => 'infrastructure_wash_hygienes.modified',
				])
				->where([$institutionWashHygiene->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_hygienes.modified'=>'desc'])
				->limit(1)
				->first();

        // Wash Waste
        $institutionWashWaste  = TableRegistry::get('infrastructure_wash_wastes');
		$institutionWashWasteData = $institutionWashWaste->find()
				->select([
					'created' => 'infrastructure_wash_wastes.created',
					'modified' => 'infrastructure_wash_wastes.modified',
				])
				->where([$institutionWashWaste->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_wastes.modified'=>'desc'])
				->limit(1)
				->first();

         // Wash Sewage
        $institutionWashSewage  = TableRegistry::get('infrastructure_wash_sewages');
		$institutionWashSewageData = $institutionWashSewage->find()
				->select([
					'created' => 'infrastructure_wash_sewages.created',
					'modified' => 'infrastructure_wash_sewages.modified',
				])
				->where([$institutionWashSewage->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_wash_sewages.modified'=>'desc'])
				->limit(1)
				->first();

        // Utilities Electricity
        $institutionUtilitiesElectricity  = TableRegistry::get('infrastructure_utility_electricities');
		$institutionUtilitiesElectricityData = $institutionUtilitiesElectricity->find()
				->select([
					'created' => 'infrastructure_utility_electricities.created',
					'modified' => 'infrastructure_utility_electricities.modified',
				])
				->where([$institutionUtilitiesElectricity->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_utility_electricities.modified'=>'desc'])
				->limit(1)
				->first();

         // Utilities Internet
        $institutionUtilitiesInternet  = TableRegistry::get('infrastructure_utility_internets');
		$institutionUtilitiesInternetData = $institutionUtilitiesInternet->find()
				->select([
					'created' => 'infrastructure_utility_internets.created',
					'modified' => 'infrastructure_utility_internets.modified',
				])
				->where([$institutionUtilitiesInternet->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_utility_internets.modified'=>'desc'])
				->limit(1)
				->first();

         //Utilities Telephone
        $institutionUtilitiesTelephone  = TableRegistry::get('infrastructure_utility_telephones');
		$institutionUtilitiesTelephoneData = $institutionUtilitiesTelephone->find()
				->select([
					'created' => 'infrastructure_utility_telephones.created',
					'modified' => 'infrastructure_utility_telephones.modified',
				])
				->where([$institutionUtilitiesTelephone->aliasField('institution_id') => $institutionId])
                ->order(['infrastructure_utility_telephones.modified'=>'desc'])
				->limit(1)
				->first();

         // Assets
        $institutionAssets  = TableRegistry::get('institution_assets');
		$institutionAssetsData = $institutionAssets->find()
				->select([
					'created' => 'institution_assets.created',
					'modified' => 'institution_assets.modified',
				])
				->where([$institutionAssets->aliasField('institution_id') => $institutionId])
                ->order(['institution_assets.modified'=>'desc'])
				->limit(1)
				->first();

        //Transport
        $institutionTransport  = TableRegistry::get('institution_buses');
		$institutionTransportData = $institutionTransport->find()
				->where([$institutionTransport->aliasField('institution_id') => $institutionId])
                ->order(['institution_buses.modified'=>'desc'])
				->limit(1)
				->first();

        //Committees
        $institutionCommittees  = TableRegistry::get('institution_committees');
		$institutionCommitteesData = $institutionCommittees->find()
				->select([
					'created' => 'institution_committees.created',
					'modified' => 'institution_committees.modified',
				])
				->where([$institutionCommittees->aliasField('institution_id') => $institutionId])
                ->order(['institution_committees.modified'=>'desc'])
				->limit(1)
				->first();

        // config
        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
		$enabledTypeList = $ConfigItem
            ->find()
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1,$ConfigItem->aliasField('value') => 1,$ConfigItem->aliasField('type') => 'Institution Completeness'])
            ->toArray();

        foreach($enabledTypeList as $key => $enabled) {
                $data[$key]['feature'] = $enabled->name;
                 if ($enabled->name == 'Overview') {
                    if(!empty($institutionsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionsData->modified)?date("F j,Y",strtotime($institutionsData->modified)):date("F j,Y",strtotime($institutionsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Calendar') {
                    if(!empty($calendarEventsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($calendarEventsData->modified)?date("F j,Y",strtotime($calendarEventsData->modified)):date("F j,Y",strtotime($calendarEventsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Contacts') {
                    if(!empty($institutionContactPersonsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionContactPersonsData->modified)?date("F j,Y",strtotime($institutionContactPersonsData->modified)):date("F j,Y",strtotime($institutionContactPersonsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Shifts') {
                    if(!empty($institutionShiftsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionShiftsData->modified)?date("F j,Y",strtotime($institutionShiftsData->modified)):date("F j,Y",strtotime($institutionShiftsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Programmes') {
                    if(!empty($institutionProgrammesData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionProgrammesData->modified)?date("F j,Y",strtotime($institutionProgrammesData->modified)):date("F j,Y",strtotime($institutionProgrammesData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Classes') {
                    if(!empty($institutionClassesData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionClassesData->modified)?date("F j,Y",strtotime($institutionClassesData->modified)):date("F j,Y",strtotime($institutionClassesData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Subjects') {
                    if(!empty($institutionSubjectsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionSubjectsData->modified)?date("F j,Y",strtotime($institutionSubjectsData->modified)):date("F j,Y",strtotime($institutionSubjectsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Textbooks') {
                    if(!empty($institutionTextbooksData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionTextbooksData->modified)?date("F j,Y",strtotime($institutionTextbooksData->modified)):date("F j,Y",strtotime($institutionTextbooksData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Students') {
                    if(!empty($institutionStudentsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionStudentsData->modified)?date("F j,Y",strtotime($institutionStudentsData->modified)):date("F j,Y",strtotime($institutionStudentsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Staff') {
                    if(!empty($institutionStaffData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionStaffData->modified)?date("F j,Y",strtotime($institutionStaffData->modified)):date("F j,Y",strtotime($institutionStaffData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Attendance') {
                    if(!empty($institutionAttendanceData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionAttendanceData->modified)?date("F j,Y",strtotime($institutionAttendanceData->modified)):date("F j,Y",strtotime($institutionAttendanceData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Behaviour') {
                    if(!empty($institutionBehaviourData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionBehaviourData->modified)?date("F j,Y",strtotime($institutionBehaviourData->modified)):date("F j,Y",strtotime($institutionBehaviourData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Positions') {
                    if(!empty($institutionPositionsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionPositionsData->modified)?date("F j,Y",strtotime($institutionPositionsData->modified)):date("F j,Y",strtotime($institutionPositionsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Bank Accounts') {
                    if(!empty($institutionBankAccountsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionBankAccountsData->modified)?date("F j,Y",strtotime($institutionBankAccountsData->modified)):date("F j,Y",strtotime($institutionBankAccountsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Institution Fees') {
                    if(!empty($institutionInstitutionFeesData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionInstitutionFeesData->modified)?date("F j,Y",strtotime($institutionInstitutionFeesData->modified)):date("F j,Y",strtotime($institutionInstitutionFeesData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Infrastructures Overview') {
                    if(!empty($institutionInfrastructuresOverviewData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionInfrastructuresOverviewData->modified)?date("F j,Y",strtotime($institutionInfrastructuresOverviewData->modified)):date("F j,Y",strtotime($institutionInfrastructuresOverviewData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Infrastructures Needs') {
                    if(!empty($institutionInfrastructuresNeedsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionInfrastructuresNeedsData->modified)?date("F j,Y",strtotime($institutionInfrastructuresNeedsData->modified)):date("F j,Y",strtotime($institutionInfrastructuresNeedsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Wash Water') {
                    if(!empty($institutionWashWaterData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionWashWaterData->modified)?date("F j,Y",strtotime($institutionWashWaterData->modified)):date("F j,Y",strtotime($institutionWashWaterData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Wash Hygiene') {
                    if(!empty($institutionWashHygieneData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionWashHygieneData->modified)?date("F j,Y",strtotime($institutionWashHygieneData->modified)):date("F j,Y",strtotime($institutionWashHygieneData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Wash Waste') {
                    if(!empty($institutionWashWasteData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionWashWasteData->modified)?date("F j,Y",strtotime($institutionWashWasteData->modified)):date("F j,Y",strtotime($institutionWashWasteData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Wash Sewage') {
                    if(!empty($institutionWashSewageData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionWashSewageData->modified)?date("F j,Y",strtotime($institutionWashSewageData->modified)):date("F j,Y",strtotime($institutionWashSewageData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Utilities Electricity') {
                    if(!empty($institutionUtilitiesElectricityData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionUtilitiesElectricityData->modified)?date("F j,Y",strtotime($institutionUtilitiesElectricityData->modified)):date("F j,Y",strtotime($institutionUtilitiesElectricityData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Utilities Internet') {
                    if(!empty($institutionUtilitiesInternetData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionUtilitiesInternetData->modified)?date("F j,Y",strtotime($institutionUtilitiesInternetData->modified)):date("F j,Y",strtotime($institutionUtilitiesInternetData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Utilities Telephone') {
                    if(!empty($institutionUtilitiesTelephoneData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionUtilitiesTelephoneData->modified)?date("F j,Y",strtotime($institutionUtilitiesTelephoneData->modified)):date("F j,Y",strtotime($institutionUtilitiesTelephoneData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Assets') {
                    if(!empty($institutionAssetsData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionAssetsData->modified)?date("F j,Y",strtotime($institutionAssetsData->modified)):date("F j,Y",strtotime($institutionAssetsData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Transport') {
                    if(!empty($institutionTransportData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionTransportData->modified)?date("F j,Y",strtotime($institutionTransportData->modified)):date("F j,Y",strtotime($institutionTransportData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }
                if ($enabled->name == 'Committees') {
                    if(!empty($institutionCommitteesData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        $data[$key]['modifiedDate'] = ($institutionCommitteesData->modified)?date("F j,Y",strtotime($institutionCommitteesData->modified)):date("F j,Y",strtotime($institutionCommitteesData->created));
                    } else {
                        $data[$key]['complete'] = 'no';
                        $data[$key]['modifiedDate'] = 'Not updated';
                    }
                }

        }
        $totalProfileComplete = count($data);
        $profilePercentage = 100/$totalProfileComplete * $profileComplete;
        $profilePercentage = round($profilePercentage);
        $data['percentage'] = $profilePercentage;
        return $data;
     }

    public function getAcademicPeriod()
    {
        $academic_periods = TableRegistry::get('academic_periods');
        $academic_periods_result = $academic_periods
            ->find()
            ->select(['id','name'])
            ->where(['code !=' => 'All','visible' => 1])
            ->order([$academic_periods->aliasField('id DESC')])
            ->toArray();
        foreach($academic_periods_result AS $result){
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);die;
    }

    public function getEducationGrade()
    {
        $requestData = $this->request->input('json_decode', true);
       /*$inst = 'eyJpZCI6NiwiNWMzYTA5YmYyMmUxMjQxMWI2YWY0OGRmZTBiODVjMmQ5ZDExODFjZDM5MWUwODk1NzRjOGNmM2NhMWU1ZTRhZCI6InVtcWxsdHNiZmZmN2E4bWNlcXA5aGduYTltIn0.ZjhkNmI0ZmFkYjFhNDQ2YjMwM2FmODQwNWQxYWRjZTBjNzFmYzRiMjViNmY0NmRkZDNiZjI5YTM2MmYyZWYyOA';
        echo "<pre>"; print_r($this->paramsDecode($inst)); die;*/
        $institution_name = $this->request->session()->read('Institution.Institutions.name');
        $institutions = TableRegistry::get('institutions');
        $institution = $institutions
                        ->find()
                        ->select(['id','name'])
                        ->where(['name' => $institution_name])
                        ->first();
        //get instituiton                 
        $institution_id = 0;       
        if(!empty($institution)){
            $institution_id = $institution->id;
        }
        //get academic period
        $academic_period = $requestData['academic_periods'];
        $academic_periods = TableRegistry::get('academic_periods');
        $academic_periods_result = $academic_periods
            ->find()
            ->select(['id','name', 'start_date','end_date'])
            ->where(['id' => $academic_period])
            ->first();
       
        $startDate = date('Y-m-d', strtotime($academic_periods_result->start_date));
        $endDate = date('Y-m-d', strtotime($academic_periods_result->end_date));
        
        $institution_grades = TableRegistry::get('institution_grades');
        $institution_grades_result = $institution_grades
            ->find()
            ->select([
                $institution_grades->aliasField('id'),
                'EducationGrades.id',
                'EducationGrades.name'
            ])
            ->LeftJoin(['EducationGrades' => 'education_grades'],[
                'EducationGrades.id = '. $institution_grades->aliasField('education_grade_id')
            ])
            ->LeftJoin(['EducationProgrammes' => 'education_programmes'],[
                'EducationProgrammes.id = EducationGrades.education_programme_id'
            ])
            ->LeftJoin(['EducationCycles' => 'education_cycles'],[
                'EducationCycles.id = EducationProgrammes.education_cycle_id'
            ])
            ->LeftJoin(['EducationLevels' => 'education_levels'],[
                'EducationLevels.id = EducationCycles.education_level_id'
            ])
            ->LeftJoin(['EducationSystems' => 'education_systems'],[
                'EducationSystems.id = EducationLevels.education_system_id'
            ])
            ->where([
                $institution_grades->aliasField('institution_id') => $institution_id,
                'EducationSystems.academic_period_id' => $academic_period,
                'OR'=>[
                    'OR' => [
                        [
                            $institution_grades->aliasField('end_date') . ' IS NOT NULL',
                            $institution_grades->aliasField('start_date') . ' <=' => $startDate,
                            $institution_grades->aliasField('end_date') . ' >=' => $startDate
                        ],
                        [
                            $institution_grades->aliasField('end_date') . ' IS NOT NULL',
                            $institution_grades->aliasField('start_date') . ' <=' => $endDate,
                            $institution_grades->aliasField('end_date') . ' >=' => $endDate
                        ],
                        [
                            $institution_grades->aliasField('end_date') . ' IS NOT NULL',
                            $institution_grades->aliasField('start_date') . ' >=' => $startDate,
                            $institution_grades->aliasField('end_date') . ' <=' => $endDate
                        ]
                    ],
                    [
                        $institution_grades->aliasField('end_date') . ' IS NULL',
                        $institution_grades->aliasField('start_date') . ' <=' => $endDate
                    ]
                ]
        ])
        ->group([$institution_grades->aliasField('education_grade_id')])
        ->toArray();
        foreach($institution_grades_result AS $result){
            $result_array[] = array("id" => $result['id'], "education_grade_id" => $result->EducationGrades['id'], "name" => $result->EducationGrades['name']);
        }
        echo json_encode($result_array);die;
    }

    public function getClassOptions()
    {
        $requestData = $this->request->input('json_decode', true);
        $academic_period = $requestData['academic_periods'];
        $grade_id = $requestData['grade_id'];
        $institution_id = $this->request->session()->read('Institution.Institutions.id');

        $institution_classes = TableRegistry::get('institution_classes');
        $institution_classes_result = $institution_classes
            ->find()
            ->select([
                $institution_classes->aliasField('id'),
                $institution_classes->aliasField('name')
            ])
            ->InnerJoin(['InstitutionClassGrades' => 'institution_class_grades'],[
                'InstitutionClassGrades.institution_class_id = '. $institution_classes->aliasField('id'),
                'InstitutionClassGrades.education_grade_id = '. $grade_id,
            ])
            
            ->where([
                $institution_classes->aliasField('academic_period_id') => $academic_period,
                $institution_classes->aliasField('institution_id') => $institution_id
            ])
            ->group([$institution_classes->aliasField('id')])
            ->toArray();
    
        foreach($institution_classes_result AS $result){
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);die;
    }

    public function getPositionType()
    {
        $postype = [
            'Full-Time' => 'Full-Time',
            'Part-Time' => 'Part-Time'
        ];

        foreach($postype AS $result){
             $result_array[] = array("id" => $result, "name" => $result);
        }
        echo json_encode($result_array);die;
    }

    public function getFTE()
    {
        $ftetype = [
            '0.25' => '25%',
            '0.5' => '50%',
            '0.75' => '75%'
        ];

        foreach($ftetype AS $k=>$v){
           $result_array[] = array("id" => $k, "name" => $v);
        }
        echo json_encode($result_array);die;
    }

    public function getStaffType()
    {
        $staff_types = TableRegistry::get('staff_types');
        $staff_types_result = $staff_types
            ->find()
            ->select(['id','name'])
            ->where(['visible' => 1])
            ->toArray();
        foreach($staff_types_result AS $result){
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);die;
    }

    public function getShifts()
    {   //get current academic period
        $academic_periods = TableRegistry::get('academic_periods');
        $academic_periods_result = $academic_periods
            ->find()
            ->select(['id','name'])
            ->where(['current' => 1,'visible' => 1])
            ->first();

        $academic_period_id = !empty($academic_periods_result) ? $academic_periods_result->id : 0;
        $institutionId = $this->request->session()->read('Institution.Institutions.id');
        $shift =  TableRegistry::get('Institution.InstitutionShifts');
        $shiftData = $shift->find('all',
                            [ 'contain' => [
                                'ShiftOptions'                   
                            ],
                ])->where([
                    $shift->aliasField('academic_period_id') => $academic_period_id,
                    $shift->aliasField('institution_id') => $institutionId
                ])->toArray();
        
        if (!empty($shiftData)) {
            foreach ($shiftData as $k => $val) {
                $result_array[] = array("id" => $val['id'], "name" => $val->shift_option->name);
            }
        }
        echo json_encode($result_array);die;
    }

    public function getPositions()
    {
        $requestData = $this->request->input('json_decode', true);
        $fte = $requestData['fte'];
        $startDate = $requestData['startDate'];
        $institutionId = $this->request->session()->read('Institution.Institutions.id');
        $endDate = null;
        if ($endDate == 'null') {
            $endDate = null;
        }
        $result = $this->getInstitutionPositions($institutionId, $fte, $startDate, $endDate);
        echo $result; die;
    }

    public function postSaveStudentsData()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        /*$requestData = json_decode('{"login_user_id":"1","openemis_no":"152227233311111222","first_name":"AMARTAA","middle_name":"","third_name":"","last_name":"Fenicott","preferred_name":"","gender_id":"1","date_of_birth":"2011-01-01","identity_number":"1231122","nationality_id":"2","username":"kkk111","password":"sdsd","postal_code":"12233","address":"sdsdsds","birthplace_area_id":"2","address_area_id":"2","identity_type_id":"160","education_grade_id":"59","academic_period_id":"30", "start_date":"01-01-2021","end_date":"31-12-2021","institution_class_id":"524","student_status_id":1,"custom":[{"student_custom_field_id":17,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":27,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":29,"text_value":"test.jpg","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":28,"text_value":"","number_value":2,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":3,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":26,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":4,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":8,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":9,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":30,"text_value":"{\"latitude\":\"11.1\",\"longitude\":\"2.22\"}","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":18,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"}]}', true);*/
        
        if(!empty($requestData)){
            $openemisNo = (array_key_exists('openemis_no', $requestData))? $requestData['openemis_no']: null;
            $firstName = (array_key_exists('first_name', $requestData))? $requestData['first_name']: null;
            $middleName = (array_key_exists('middle_name', $requestData))? $requestData['middle_name']: null;
            $thirdName = (array_key_exists('third_name', $requestData))? $requestData['third_name']: null;
            $lastName = (array_key_exists('last_name', $requestData))? $requestData['last_name']: null;
            $preferredName = (array_key_exists('preferred_name', $requestData))? $requestData['preferred_name']: null;
            $genderId = (array_key_exists('gender_id', $requestData))? $requestData['gender_id']: null;
            $dateOfBirth = (array_key_exists('date_of_birth', $requestData))? date('y-m-d', strtotime($requestData['date_of_birth'])): null;
            $identityNumber = (array_key_exists('identity_number', $requestData))? $requestData['identity_number']: null;
            $nationalityId = (array_key_exists('nationality_id', $requestData))? $requestData['nationality_id']: null;
            $username = (array_key_exists('username', $requestData))? $requestData['username']: null;
            $password = (array_key_exists('password', $requestData))? password_hash($requestData['password'],  PASSWORD_DEFAULT) : null;
            $address  = (array_key_exists('address', $requestData))? $requestData['address '] : null;
            $postalCode = (array_key_exists('postal_code', $requestData))? $requestData['postal_code'] : null;
            $birthplaceAreaId = (array_key_exists('birthplace_area_id', $requestData))? $requestData['birthplace_area_id'] : null;
            $addressAreaId = (array_key_exists('address_area_id', $requestData))? $requestData['address_area_id'] : null;
            $identityTypeId = (array_key_exists('identity_type_id', $requestData))? $requestData['identity_type_id'] : null;
            
            $institutionClassId = (array_key_exists('institution_class_id', $requestData))? $requestData['institution_class_id'] : null;
            $educationGradeId = (array_key_exists('education_grade_id', $requestData))? $requestData['education_grade_id'] : null;
            $academicPeriodId = (array_key_exists('academic_period_id', $requestData))? $requestData['academic_period_id'] : null;
            $startDate = (array_key_exists('start_date', $requestData))? date('y-m-d', strtotime($requestData['start_date'])) : null;
            $endDate = (array_key_exists('end_date', $requestData))? date('y-m-d', strtotime($requestData['end_date'])) : null;
            
            $institutionId = $this->request->session()->read('Institution.Institutions.id');
            $studentStatusId = (array_key_exists('student_status_id', $requestData))? $requestData['student_status_id'] : null;
            $userId = (array_key_exists('login_user_id', $requestData))? $requestData['login_user_id'] : 1;
            $custom = (array_key_exists('custom', $requestData))? $requestData['custom'] : "";
            
            //get academic period data
            $academicPeriods = TableRegistry::get('academic_periods');
            $periods = $academicPeriods->find()
                        ->where([
                            $academicPeriods->aliasField('id') => $academicPeriodId,
                        ])
                        ->first();
            $startYear = $endYear = '';
            if(!empty($periods)){
                $startYear = $periods->start_year;
                $endYear = $periods->end_year;
            }

            //get prefered language
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $pref_lang = $ConfigItems->find()
                    ->where([
                        $ConfigItems->aliasField('code') => 'language',
                        $ConfigItems->aliasField('type') => 'System'
                    ])
                    ->first();
            //get Student Status List        
            $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
            $statuses = $StudentStatuses->findCodeList();
        
            $SecurityUsers = TableRegistry::get('security_users');
            $entityData = [
                'openemis_no' => $openemisNo,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'third_name' => $thirdName,
                'last_name' => $lastName,
                'preferred_name' => $preferredName,
                'gender_id' => $genderId,
                'date_of_birth' => $dateOfBirth,
                'nationality_id' => $nationalityId,
                'preferred_language' => $pref_lang->value,
                'username' => $username,
                'password' => $password,
                'address' => $address,
                'address_area_id' => $addressAreaId,
                'birthplace_area_id' => $birthplaceAreaId,
                'postal_code' => $postalCode,
                'is_student' => 1,
                'created_user_id' => $userId,
                'created' => date('y-m-d H:i:s'),
            ];
            //save in security_users table
            $entity = $SecurityUsers->newEntity($entityData);
            $SecurityUserResult = $SecurityUsers->save($entity);
            if($SecurityUserResult){
                $user_record_id=$SecurityUserResult->id;
                if(!empty($nationalityId)){
                    $UserNationalities = TableRegistry::get('user_nationalities');
                    $primaryKey = $UserNationalities->primaryKey();
                    $hashString = [];
                    foreach ($primaryKey as $key) {
                        if($key == 'nationality_id'){
                            $hashString[] = $nationalityId;
                        }
                        if($key == 'security_user_id'){
                            $hashString[] = $user_record_id;
                        }
                    }
         
                    $entityNationalData = [
                        'id' => Security::hash(implode(',', $hashString), 'sha256'),
                        'preferred' => 1,
                        'nationality_id' => $nationalityId,
                        'security_user_id' => $user_record_id,
                        'created_user_id' => $userId,
                        'created' => date('y-m-d H:i:s')
                    ];
                    //save in user_nationalities table
                    $entityNationalData = $UserNationalities->newEntity($entityNationalData);
                    $UserNationalitiesResult = $UserNationalities->save($entityNationalData);
                }

                if(!empty($nationalityId) && !empty($identityTypeId) && !empty($identityNumber)){
                    $UserIdentities = TableRegistry::get('user_identities');
                    $entityIdentitiesData = [
                        'identity_type_id' => $identityTypeId,
                        'number' => $identityNumber,
                        'nationality_id' => $nationalityId,
                        'security_user_id' => $user_record_id,
                        'created_user_id' => $userId,
                        'created' => date('y-m-d H:i:s')
                    ];
                    //save in user_identities table
                    $entityIdentitiesData = $UserIdentities->newEntity($entityIdentitiesData);
                    $UserIdentitiesResult = $UserIdentities->save($entityIdentitiesData);
                }

                if(!empty($educationGradeId) && !empty($academicPeriodId)){
                    $InstitutionStudents = TableRegistry::get('institution_students');
                    $entityStudentsData = [
                        'id' => Text::uuid(),
                        'student_status_id' => $studentStatusId,
                        'student_id' => $user_record_id,
                        'education_grade_id' => $educationGradeId,
                        'academic_period_id' => $academicPeriodId,
                        'start_date' => $startDate,
                        'start_year' => $startYear,
                        'end_date' => $endDate,
                        'end_year' => $endYear,
                        'institution_id' => $institutionId,
                        'created_user_id' => $userId,
                        'created' => date('y-m-d H:i:s')
                    ];
                    //save in institution_students table
                    $entityStudentsData = $InstitutionStudents->newEntity($entityStudentsData);
                    $InstitutionStudentsResult = $InstitutionStudents->save($entityStudentsData);
                }

                $workflows = TableRegistry::get('workflows');
                $workflowSteps = TableRegistry::get('workflow_steps');

                $workflowResults = $workflows->find()
                            ->select(['workflowSteps_id'=>$workflowSteps->aliasField('id')])
                            ->LeftJoin([$workflowSteps->alias() => $workflowSteps->table()], [
                                $workflowSteps->aliasField('workflow_id =') . $workflows->aliasField('id'),
                                $workflowSteps->aliasField('name')=> 'Approved'
                            ])
                            ->where([
                                $workflows->aliasField('name') => 'Student Admission'
                            ])
                            ->first();          
                if(!empty($educationGradeId) && !empty($academicPeriodId) && !empty($institutionClassId) && !empty($workflowResults)){
                    $institutionStudentAdmission = TableRegistry::get('institution_student_admission');
                    $entityAdmissionData = [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'student_id' => $user_record_id,
                        'status_id' => $workflowResults->workflowSteps_id, 
                        'assignee_id' => 0,
                        'institution_id' => $institutionId,
                        'academic_period_id' => $academicPeriodId,
                        'education_grade_id' => $educationGradeId,
                        'institution_class_id' => $institutionClassId,
                        'created_user_id' => $userId,
                        'created' => date('y-m-d H:i:s')
                    ];
                    //save in institution_student_admission table
                    $entityAdmissionData = $institutionStudentAdmission->newEntity($entityAdmissionData);
                    $InstitutionAdmissionResult = $institutionStudentAdmission->save($entityAdmissionData);
                }

                if(!empty($educationGradeId) && !empty($academicPeriodId) && !empty($institutionClassId)){
                    $institutionClassStudents = TableRegistry::get('institution_class_students');
                    $entityAdmissionData = [
                        'id' => Text::uuid(),
                        'student_id' => $user_record_id,
                        'institution_class_id' => $institutionClassId,
                        'education_grade_id' => $educationGradeId,
                        'academic_period_id' => $academicPeriodId,
                        'institution_id' => $institutionId,
                        'student_status_id' => $statuses['CURRENT'], 
                        'created_user_id' => $userId,
                        'created' => date('y-m-d H:i:s')
                    ];
                    //save in institution_class_students table
                    $entityClassData = $institutionClassStudents->newEntity($entityAdmissionData);
                    $InstitutionClassResult = $institutionClassStudents->save($entityClassData);
                }

                if(!empty($educationGradeId) && !empty($academicPeriodId) && !empty($institutionClassId)){
                    $institutionClassSubjects = TableRegistry::get('institution_class_subjects');
                    $institutionSubjects = TableRegistry::get('institution_subjects');
                    $SubjectsResult = $institutionClassSubjects
                        ->find()
                        ->select([
                            $institutionClassSubjects->aliasField('institution_class_id'),
                            $institutionClassSubjects->aliasField('institution_subject_id'),
                            'name' => $institutionSubjects->aliasField('name'),
                            'institution_id' => $institutionSubjects->aliasField('institution_id'),
                            'education_grade_id' => $institutionSubjects->aliasField('education_grade_id'),
                            'education_subject_id' => $institutionSubjects->aliasField('education_subject_id'),
                            'academic_period_id' => $institutionSubjects->aliasField('academic_period_id'),
                        ])
                        ->LeftJoin([$institutionSubjects->alias() => $institutionSubjects->table()], [
                            $institutionSubjects->aliasField('id =') . $institutionClassSubjects->aliasField('institution_subject_id')
                        ])
                        ->where([
                            $institutionClassSubjects->aliasField('institution_class_id') => $institutionClassId
                        ])
                        ->toArray();
                       
                    if(!empty($SubjectsResult)){
                        $institutionSubjectStudents = TableRegistry::get('institution_subject_students');
                        foreach ($SubjectsResult as $skey => $sval) {
                            $primaryKey = $institutionSubjectStudents->primaryKey();
                            $hashString = [];
                            foreach ($primaryKey as $key) {
                                if($key == 'student_id'){
                                    $hashString[] = $user_record_id;
                                }
                                if($key == 'institution_class_id'){
                                    $hashString[] = $institutionClassId;
                                }
                                if($key == 'academic_period_id'){
                                    $hashString[] = $academicPeriodId;
                                }
                                if($key == 'education_grade_id'){
                                    $hashString[] = $educationGradeId;
                                }
                                if($key == 'institution_id'){
                                    $hashString[] = $institutionId;
                                }
                                if($key == 'education_subject_id'){
                                    $hashString[] = $sval->education_subject_id;
                                }
                            }
                            
                            $entitySubjectsData = [
                                'id' => Security::hash(implode(',', $hashString), 'sha256'),
                                'student_id' => $user_record_id,
                                'institution_subject_id' => $sval->institution_subject_id,
                                'institution_class_id' => $institutionClassId,
                                'institution_id' => $institutionId,
                                'academic_period_id' => $academicPeriodId,
                                'education_subject_id' => $sval->education_subject_id,
                                'education_grade_id' => $educationGradeId,
                                'student_status_id' => $statuses['CURRENT'],
                                'created_user_id' => $userId,
                                'created' => date('y-m-d H:i:s')
                            ];
                            //save in institution_subject_students table
                            $entitySubjectsData = $institutionSubjectStudents->newEntity($entitySubjectsData);
                            $institutionSubjectStudentsResult = $institutionSubjectStudents->save($entitySubjectsData);

                            unset($entitySubjectsData);
                            unset($hashString);
                        }
                    }        
                }
                
                if(!empty($custom)){
                    $studentCustomFieldValues =  TableRegistry::get('student_custom_field_values');
                    foreach ($custom as $skey => $sval) {
                        $entityCustomData = [
                            'id' => Text::uuid(),
                            'text_value' => $sval->text_value,
                            'number_value' => $sval->number_value,
                            'decimal_value' => $sval->decimal_value,
                            'textarea_value' => $sval->textarea_value,
                            'time_value' => $sval->time_value,
                            'file' => $sval->file,
                            'student_custom_field_id' => $sval->student_custom_field_id,
                            'student_id' => $user_record_id,
                            'created_user_id' => $userId,
                            'created' => date('y-m-d H:i:s')
                        ];
                        //save in student_custom_field_values table
                        $entityCustomData = $studentCustomFieldValues->newEntity($entitySubjectsData);
                        $studentCustomFieldsResult = $studentCustomFieldValues->save($entityCustomData);
                        unset($studentCustomFieldsResult);
                    }
                }
            }else{
                return false;
            }
        }
        return true;
    }

    public function studentCustomFields()
    {
        $studentCustomForms =  TableRegistry::get('student_custom_forms');
        $studentCustomFormsFields =  TableRegistry::get('student_custom_forms_fields');
        $studentCustomFields =  TableRegistry::get('student_custom_fields');
        $studentCustomFieldOptions =  TableRegistry::get('student_custom_field_options');

        $SectionData = $studentCustomForms->find()
                            ->select([
                                'student_custom_form_id'=>$studentCustomFormsFields->aliasField('student_custom_form_id'),
                                'student_custom_field_id'=>$studentCustomFormsFields->aliasField('student_custom_field_id'),
                                'section'=>$studentCustomFormsFields->aliasField('section'),
                            ])
                            ->LeftJoin([$studentCustomFormsFields->alias() => $studentCustomFormsFields->table()], [
                                $studentCustomFormsFields->aliasField('student_custom_form_id =') . $studentCustomForms->aliasField('id'),
                            ])
                            ->where([
                                $studentCustomForms->aliasField('name') => 'Student Custom Fields'
                            ])
                            ->group([$studentCustomFormsFields->aliasField('section')])
                            ->toArray();
        $SectionArr = [];
        $remove_field_type = ['FILE','COORDINATES','TABLE'];                    
        foreach ($SectionData as $skey => $sval) {
            $SectionArr[$skey][$sval->section] = $sval->section;
            $CustomFieldsData = $studentCustomFormsFields->find()
                            ->select([
                                'student_custom_form_id'=>$studentCustomFormsFields->aliasField('student_custom_form_id'),
                                'student_custom_field_id'=>$studentCustomFormsFields->aliasField('student_custom_field_id'),
                                'section'=>$studentCustomFormsFields->aliasField('section'),
                                'name'=>$studentCustomFormsFields->aliasField('name'),
                                'order'=>$studentCustomFormsFields->aliasField('order'),
                                'description'=>$studentCustomFields->aliasField('description'),
                                'field_type'=>$studentCustomFields->aliasField('field_type'),
                                'is_mandatory'=>$studentCustomFields->aliasField('is_mandatory'),
                                'is_unique'=>$studentCustomFields->aliasField('is_unique'),
                                'params'=>$studentCustomFields->aliasField('params'),
                            ])
                            ->LeftJoin([$studentCustomFields->alias() => $studentCustomFields->table()], [
                                $studentCustomFields->aliasField('id =') . $studentCustomFormsFields->aliasField('student_custom_field_id'),
                            ])
                            ->where([
                                $studentCustomFormsFields->aliasField('section') => $sval->section,
                                $studentCustomFields->aliasField('field_type NOT IN') => $remove_field_type
                            ])->toArray();
            $fieldsArr = [];
            foreach ($CustomFieldsData as $ckey => $cval) {
                $fieldsArr[$ckey]['student_custom_form_id'] = $cval->student_custom_form_id;
                $fieldsArr[$ckey]['student_custom_field_id'] = $cval->student_custom_field_id;
                $fieldsArr[$ckey]['section'] = $cval->section;
                $fieldsArr[$ckey]['name'] = $cval->name;
                $fieldsArr[$ckey]['order'] = $cval->order;
                $fieldsArr[$ckey]['description'] = $cval->description;
                $fieldsArr[$ckey]['field_type'] = $cval->field_type;
                $fieldsArr[$ckey]['is_mandatory'] = $cval->is_mandatory;
                $fieldsArr[$ckey]['is_unique'] = $cval->is_unique;
                $fieldsArr[$ckey]['params'] = $cval->params;

                if($cval->field_type == 'DROPDOWN' || $cval->field_type == 'CHECKBOX'){
                    $OptionData = $studentCustomFieldOptions->find()
                                    ->select([
                                        'option_id'=>$studentCustomFieldOptions->aliasField('id'),
                                        'option_name'=>$studentCustomFieldOptions->aliasField('name'),
                                        'is_default'=>$studentCustomFieldOptions->aliasField('is_default'),
                                        'visible'=>$studentCustomFieldOptions->aliasField('visible'),
                                        'option_order'=>$studentCustomFieldOptions->aliasField('order')
                                    ])
                                    ->where([
                                        $studentCustomFieldOptions->aliasField('student_custom_field_id') => $cval->student_custom_field_id
                                    ])->toArray();
                    $OptionDataArr =[];
                    foreach ($OptionData as $opkey => $opval) {
                        $OptionDataArr[$opkey]['option_id'] = $opval->option_id;
                        $OptionDataArr[$opkey]['option_name'] = $opval->option_name;
                        $OptionDataArr[$opkey]['is_default'] = $opval->is_default;
                        $OptionDataArr[$opkey]['visible'] = $opval->visible;
                        $OptionDataArr[$opkey]['option_order'] = $opval->option_order;
                    }
                    $fieldsArr[$ckey]['option'] = $OptionDataArr;
                }
            }
            $SectionArr[$skey][$sval->section] = $fieldsArr;
        }
        echo json_encode($SectionArr);die;
    }

    public function customFieldsUseJustForExample()
    {
        /*$studentCustomFieldValues =  TableRegistry::get('student_custom_field_values');
        $SectionData = $studentCustomFieldValues->find()
                            ->where([
                                $studentCustomFieldValues->aliasField('student_id') => 13077
                            ])
                            ->toArray();
        
        $SectionArr = [];
        foreach ($SectionData as $skey => $sval) {
            $SectionArr[$skey]['student_custom_field_id'] = $sval->student_custom_field_id;
            $SectionArr[$skey]['text_value'] = !empty($sval->text_value) ? $sval->text_value : '';
            $SectionArr[$skey]['number_value'] = !empty($sval->number_value) ? $sval->number_value : '';
            $SectionArr[$skey]['decimal_value'] = !empty($sval->decimal_value) ? $sval->decimal_value : '';
            $SectionArr[$skey]['textarea_value'] = !empty($sval->textarea_value) ? $sval->textarea_value : '';
            $SectionArr[$skey]['time_value'] = !empty($sval->time_value) ? $sval->time_value : '';
            $SectionArr[$skey]['file'] = "";
            $SectionArr[$skey]['created_user_id'] = 1;
            $SectionArr[$skey]['created'] = date('y-m-d H:i:s');
        }

        $newarry['custom'] = $SectionArr;
        //echo "<pre>"; print_r($SectionArr); die;
        echo json_encode($newarry);die;*/

        $this->autoRender = false;
        $requestData = json_decode('{"login_user_id":"1","openemis_no":"152227233311111222","first_name":"AMARTAA","middle_name":"","third_name":"","last_name":"Fenicott","preferred_name":"","gender_id":"1","date_of_birth":"2011-01-01","identity_number":"1231122","nationality_id":"2","username":"kkk111","password":"sdsd","postal_code":"12233","address":"sdsdsds","birthplace_area_id":"2","address_area_id":"2","identity_type_id":"160","education_grade_id":"59","academic_period_id":"30", "start_date":"01-01-2021","end_date":"31-12-2021","institution_class_id":"524","student_status_id":1,"custom":[{"student_custom_field_id":17,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":27,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":29,"text_value":"test.jpg","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":28,"text_value":"","number_value":2,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":3,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":26,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":4,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":8,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":9,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":30,"text_value":"{\"latitude\":\"11.1\",\"longitude\":\"2.22\"}","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":18,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"}]}', true);
        
        $custom = $requestData['custom'];
        //echo "<pre>"; print_r($custom); die;
        if(!empty($custom)){
            $studentCustomFieldValues =  TableRegistry::get('student_custom_field_values');
            foreach ($custom as $skey => $sval) {
                $entityCustomData = [
                    'id' => Text::uuid(),
                    'text_value' => $sval->text_value,
                    'number_value' => $sval->number_value,
                    'decimal_value' => $sval->decimal_value,
                    'textarea_value' => $sval->textarea_value,
                    'time_value' => $sval->time_value,
                    'file' => $sval->file,
                    'student_custom_field_id' => $sval->student_custom_field_id,
                    'student_id' => $user_record_id,
                    'created_user_id' => $userId,
                    'created' => date('y-m-d H:i:s')
                ];
                //save in student_custom_field_values table
                $entityCustomData = $studentCustomFieldValues->newEntity($entitySubjectsData);
                $studentCustomFieldsResult = $studentCustomFieldValues->save($entityCustomData);
                unset($studentCustomFieldsResult);
            }
        }
    }
}
