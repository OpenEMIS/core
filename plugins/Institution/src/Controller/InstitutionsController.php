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
use PHPExcel_IOFactory;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Security; //POCOR-5672
use Cake\Utility\Text;//POCOR-5672
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\Network\Session;

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
        'InstitutionCurricular',//POCOR-6673
        'InstitutionCurricularStudents', //POCOR-6673

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
        'InstitutionStudentAbsencesArchived',

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
        'ReportCardGenerate',

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

       $data =  $this->loadModel('Calendars');


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
            'ImportAssessmentItemResults'      => ['className' => 'Institution.ImportAssessmentItemResults', 'actions' => ['add']],
            'ImportAssessmentItemResults'      => ['className' => 'Institution.ImportAssessmentItemResults', 'actions' => ['add']],
            'InstitutionStatistics'              => ['className' => 'Institution.InstitutionStatistics', 'actions' => ['index', 'add']],
            'InstitutionStandards'              => ['className' => 'Institution.InstitutionStandards', 'actions' => ['index', 'add', 'remove']],
            'ImportStudentCurriculars'  => ['className' => 'Institution.ImportStudentCurriculars', 'actions' => ['add']],//POCOR-6673
        ];

        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->loadComponent('Training.Training');
        $this->loadComponent('Institution.CreateUsers');
        $this->attachAngularModules();

        $this->attachAngularModulesForDirectory();
        $this->loadModel('Institution.StaffBodyMasses');
        //POCOR-5672 it is used for removing csrf token mismatch condition in save student Api 
        if ($this->request->action == 'saveStudentData' || $this->request->action == 'saveStaffData' || $this->request->action == 'saveGuardianData' || $this->request->action == 'saveDirectoryData') {
            $this->eventManager()->off($this->Csrf);
        }//POCOR-5672 ends
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
    // POCOR-6150 start
    public function InfrastructureNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureNeeds']);
    }
    // POCOR-6150 end

    public function InstitutionBuildings()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBuildings']);
    }
    // POCOR-6151 starts
    public function InfrastructureProjects()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureProjects']);
    }
    // POCOR-6151 ends
    public function InstitutionFloors()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionFloors']);
    }
    // POCOR-6152 starts
    public function InstitutionAssets()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAssets']);
    }
    // POCOR-6152 ends
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
    // POCOR-6160 start
    public function BankAccounts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBankAccounts']);
    }
    // POCOR-6160 ends
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
    // POCOR-6154
    public function StudentBehaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentBehaviours']);
    }
    // POCOR-6154
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

    public function InstitutionTrips()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTrips']);
    }

    //POCOR-6673
    public function InstitutionCurriculars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionCurriculars']);
    }
    //POCOR-6673
    public function InstitutionCurricularStudents()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionCurricularStudents']);
    }

    public function changePageHeaderTrips($model, $modelAlias, $userType)
    {
        $session = $this->request->session();
        $institutionId = 0;
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        if (!empty($institutionId)) {
            if($this->request->param('action') == 'InstitutionTrips') {
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Trips');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Trips'));
                $this->set('contentHeader', $header);

            }elseif($this->request->param('action') == 'InstitutionCurriculars') { //POCOR-6673
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Curriculars');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Curriculars'));
                $this->set('contentHeader', $header);
            }
        }
    }

    public function AssessmentItemResultsArchived($pass = '')
    {
        if($pass=='excel'){
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.AssessmentItemResultsArchived']);
        }else{
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
        $this->set('_excel', $this->AccessControl->check(['Institutions', 'AssessmentItemResultsArchived', 'excel'], $roles));
        // $url = $this->ControllerAction->url('index');
        // $url['plugin'] = 'Institution';
        // $url['controller'] = 'Institutions';
        // $url['action'] = 'AssessmentItemResultsArchived';

        $url = Router::url([
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'AssessmentItemResultsArchived',
            'excel',
            'queryString' => $queryString
        ]);

        $Assessments = TableRegistry::get('Assessment.Assessments');
        $hasTemplate = $Assessments->checkIfHasTemplate($assessmentId);
        if ($hasTemplate) {
            $queryString = $this->request->query('queryString');
            $customUrl = Router::url([
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'reportCardGenerate',
                            'add',
                            'queryString' => $queryString
                        ]);

            $this->set('reportCardGenerate',$customUrl);
           
            $exportPDF_Url = $this->ControllerAction->url('index');
            $exportPDF_Url['plugin'] = 'CustomExcel';
            $exportPDF_Url['controller'] = 'CustomExcels';
            $exportPDF_Url['action'] = 'exportPDF';
            $exportPDF_Url[0] = 'AssessmentResults';
            $this->set('exportPDF', Router::url($exportPDF_Url));
        }

        $this->set('excelUrl', $url);
        $this->set('ngController', 'InstitutionsAssessmentArchiveCtrl');
        }
        // $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.AssessmentItemResultsArchived']);
    }

    public function InstitutionTransportProviders()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTransportProviders']);
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

    public function Distribution()
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
    }//POCOR-6822 Starts
    public function ClassReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ClassProfiles']);
    }//POCOR-6822 Ends
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

    //POCOR-6122 add export button in calendar
    public function InstitutionCalendars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Calendars']);
    }
    //POCOR-6122 add export button in calendar

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
    //POCOR-6148 add Export button on Institutions > Infrastructures > WASH > Waste
    public function InfrastructureWashWastes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashWastes']);
    }
    //POCOR-6146 added Export button in Infratucture > Wash > Sanitation
    public function InfrastructureWashSanitations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashSanitations']);
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

    //POCOR-6149 Add expor button on Add Export button function - Institutions > Infrastructures > WASH > Sewage
    public function InfrastructureWashSewages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashSewages']);
    }

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
            } else if($this->request->param('action') == 'InfrastructureWashWastes'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Waste');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Waste'));
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

            }else if($this->request->param('action') == 'InstitutionAssets'){ //POCOR-6152 Header breadcrumbs
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Assets');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Assets'));
            } else if($this->request->param('action') == 'InfrastructureWashSewages'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Sewage');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Sewage'));
            // POCOR-6150 start
            }else if($this->request->param('action') == 'InfrastructureNeeds'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Needs');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Needs'));
                $this->set('contentHeader', $header);
            }

            // POCOR-6150 end

            // POCOR-6151 start
            else if($this->request->param('action') == 'InfrastructureProjects'){
                $institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Projects');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->alias())));
                $this->Navigation->addCrumb(__('Projects'));
                $this->set('contentHeader', $header);
            }// POCOR-6151 end

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

    public function InstitutionStudentAbsencesArchived($pass='')
    {
        if($pass=='excel'){
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentAbsencesPeriodDetailsArchive']);
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
        $TransferLogs = TableRegistry::get('TransferLogs');
        $TransferLogsData = $TransferLogs
        ->find()
        ->select([
            'TransferLogs.academic_period_id'
        ])
        ->first();
        
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
        if(empty($TransferLogsData)){
            $is_button_accesible = 0;
        }else{
            $is_button_accesible = 1;
        }
        
        // issue
        $excelUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InstitutionStudentAbsencesArchived',
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
        $this->set('ngController', 'InstitutionStudentAttendancesArchiveCtrl as $ctrl');
        }
        // $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStudentAbsencesArchived']);
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
        $TransferLogs = TableRegistry::get('TransferLogs');
        $TransferLogsData = $TransferLogs
        ->find()
        ->select([
            'TransferLogs.academic_period_id'
        ])
        ->first();

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
        if(empty($TransferLogsData)){
            $is_button_accesible = 0;
        }else{
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

        // Start POCOR-5188
        $manualTable = TableRegistry::get('Manuals');
        $ManualContent =   $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Import Student Admission',
                $manualTable->aliasField('module') => 'Institutions',
                $manualTable->aliasField('category') => 'Students',
                ])->first();
        
        if (!empty($ManualContent['url'])) {
            $this->set('is_manual_exist', ['status'=>'success', 'url'=>$ManualContent['url']]);
        }else{
            $this->set('is_manual_exist', []);
        }
        // End POCOR-5188

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
            $queryString = $this->request->query('queryString');
            $customUrl = Router::url([
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'reportCardGenerate',
                            'add',
                            'queryString' => $queryString
                        ]);

            $this->set('reportCardGenerate',$customUrl);
           
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

    public function ReportCardGenerate(){
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardGenerate']);
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

    public function InstitutionStaffAttendancesArchive($pass = '')
    {

        if ($pass == 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStaffAttendancesArchive']);
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

            $TransferLogs = TableRegistry::get('TransferLogs');
            $TransferLogsData = $TransferLogs
            ->find()
            ->select([
                'TransferLogs.academic_period_id'
            ])
            ->first();

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
            if(empty($TransferLogsData)){
                $is_button_accesible = 0;
            }else{
                $is_button_accesible = 1;
            }

            $excelUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'InstitutionStaffAttendancesArchive',
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
            $this->set('ngController', 'InstitutionStaffAttendancesArchiveCtrl as $ctrl');
        }
        // $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStaffAttendancesArchive']);
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

            $TransferLogs = TableRegistry::get('TransferLogs');
            $TransferLogsData = $TransferLogs
            ->find()
            ->select([
                'TransferLogs.academic_period_id'
            ])
            ->first();

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
            if(empty($TransferLogsData)){
                $is_button_accesible = 0;
            }else{
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

            // Start POCOR-5188
            $manualTable = TableRegistry::get('Manuals');
            $ManualContent =   $manualTable->find()->select(['url'])->where([
                    $manualTable->aliasField('function') => 'Import Staff Attendances',
                    $manualTable->aliasField('module') => 'Institutions',
                    $manualTable->aliasField('category') => 'Staff',
                    ])->first();
            
            if (!empty($ManualContent['url'])) {
                $this->set('is_manual_exist', ['status'=>'success', 'url'=>$ManualContent['url']]);
            }else{
                $this->set('is_manual_exist', []);
            }
            // End POCOR-5188
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        //for api purpose POCOR-5672 starts
        if($this->request->params['action'] == 'getEducationGrade'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getEducationGrade';
        }
        if($this->request->params['action'] == 'getClassOptions'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getClassOptions';
        }
        if($this->request->params['action'] == 'getPositionType'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getPositionType';
        }
        if($this->request->params['action'] == 'getFTE'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getFTE';
        }
        if($this->request->params['action'] == 'getShifts'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getShifts';
        }
        if($this->request->params['action'] == 'getPositions'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getPositions';
        }
        if($this->request->params['action'] == 'getStaffType'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStaffType';
        }
        if($this->request->params['action'] == 'studentCustomFields'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'studentCustomFields';
        }
        if($this->request->params['action'] == 'staffCustomFields'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'staffCustomFields';
        }
        if($this->request->params['action'] == 'saveStudentData'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveStudentData';
        }
        if($this->request->params['action'] == 'saveStaffData'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveStaffData';
        }
        if($this->request->params['action'] == 'saveGuardianData'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveGuardianData';
        }
        if($this->request->params['action'] == 'saveDirectoryData'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveDirectoryData';
        }
        if($this->request->params['action'] == 'getStudentTransferReason'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStudentTransferReason';
        }
        if($this->request->params['action'] == 'checkStudentAdmissionAgeValidation'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'checkStudentAdmissionAgeValidation';
        }
        if($this->request->params['action'] == 'getStartDateFromAcademicPeriod'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStartDateFromAcademicPeriod';
        }
        if($this->request->params['action'] == 'checkUserAlreadyExistByIdentity'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'checkUserAlreadyExistByIdentity';
        }
        if($this->request->params['action'] == 'checkConfigurationForExternalSearch'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'checkConfigurationForExternalSearch';
        }
        if($this->request->params['action'] == 'getStaffPosititonGrades'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStaffPosititonGrades';
        }
        if($this->request->params['action'] == 'getCspdData'){ //POCOR-6930 starts
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getCspdData';
        }
        if($this->request->params['action'] == 'getConfigurationForExternalSourceData'){ //POCOR-6930 starts
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getConfigurationForExternalSourceData';
        }//POCOR-6930 ends
        //for api purpose POCOR-5672 ends
        return $events;
    }
    //POCOR-5672 starts
    public function isActionIgnored(Event $event, $action)
    {
        $pass = $this->request->pass;
        if (isset($pass[0]) && $pass[0] == 'downloadFile') {
            return true;
        }
    }//POCOR-5672 ends

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
                 $header = $name .' - '.__('Institution Data Completeness');//POCOR-6022
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
            case 'InstitutionStudentAbsencesArchived':
                $this->Angular->addModules([
                    'institution.student.attendances.archive.ctrl',
                    'institution.student.attendances.archive.svc'
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
            case 'AssessmentItemResultsArchived':
                $this->Angular->addModules([
                    'alert.svc',
                    'institutions.results.archive.ctrl',
                    'institutions.results.archive.svc'
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

            case 'InstitutionStaffAttendancesArchive':
                $this->Angular->addModules([
                    'institution.staff.attendances.archive.ctrl',
                    'institution.staff.attendances.archive.svc'
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
                'StudentAssociations' => __('Associations'),
                'StudentCurriculars' => __('Curriculars') //POCOR-6673 in student tab breadcrumb
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
            //Start: POCOR-7048
            elseif ($model->alias() == 'InstitutionMaps') {
                $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
                $institutionName = $session->read('Institution.Institutions.name');
                $this->Navigation->addCrumb('InstitutionMaps', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'InstitutionMaps','view']);
                $header = __($institutionName) ;
                $this->set('contentHeader', $header);
            }
            //End: POCOR-7048
            else {
                $this->Navigation->addCrumb($crumbTitle, $crumbOptions);
                $header = $this->activeObj->name;
            }

            $persona = false;
            $requestQuery = $this->request->query;
           // echo '<pre>'; print_r($model->alias());die;
            if (isset($params['pass'][1])) {
                if ($model->table() == 'security_users' && !$isDownload) {
                    if (count(explode('.', $params['pass'][1])) != 2) {
                    } else {
                    $ids = empty($this->ControllerAction->paramsDecode($params['pass'][1])['id']) ? $session->read('Student.Students.id') : $this->ControllerAction->paramsDecode($params['pass'][1])['id'];
                    $persona = $model->get($ids);
                    }
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
            } elseif($model->alias() == 'InstitutionStatistics'){
                $header .= ' - '. __('Statistics');
            }elseif ($model->alias() == 'StudentCurriculars') { //POCOR-6673
                $header .= ' - '. __('Curriculars');
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
            ->where([$ConfigItem->aliasField('visible') => 1,$ConfigItem->aliasField('value') => 1,$ConfigItem->aliasField('type') => 'Institution Data Completeness'])//POCOR-6022
            ->toArray();

        $typeOptions = array_keys($typeList);
        $totalProfileComplete = count($data);
        $typeListDisable = $ConfigItem
            ->find('list', [
                'keyField' => 'name',
                'valueField' => 'name'
            ])
            ->order('type')
            ->where([$ConfigItem->aliasField('visible') => 1,$ConfigItem->aliasField('value') => 0,$ConfigItem->aliasField('type') => 'Institution Data Completeness'])//POCOR-6022
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
            'Assesments' => ['text' => __('Assessments')],
            'ExaminationResults' => ['text' => __('Examinations')],
            'ReportCards' => ['text' => __('Report Cards')],
            'Awards' => ['text' => __('Awards')],
            'Extracurriculars' => ['text' => __('Extracurriculars')], 
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Associations')],
            'Curriculars' => ['text' => __('Curriculars')] //POCOR-6673 for student tab section
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        // Programme will use institution controller, other will be still using student controller
        foreach ($studentTabElements as $key => $tab) {
            if (in_array($key, ['Programmes', 'Textbooks', 'Risks','Associations','Curriculars'])) {
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
            } else {
                $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
            }
        }
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

    public function getInstitutionPositions($institutionId, $fte, $startDate, $endDate, $openemisNo, $staffUserPriId = '')
    {
        if ($endDate == 'null') {
            $endDate = null;
        }
        if ($startDate == 'null') {
            $startDate = null;
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
        $ActiveStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
        $ActStatus = array_values($ActiveStatusId);
        $ActiveStatusId = $ActStatus[0];
        // Filter by Inactive status
        $InactiveStatus = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'INACTIVE');
        $InactiveStatusId = array_values($InactiveStatus[0]);
        $positionConditions = [];
        $positionConditions[$StaffTable->Positions->aliasField('institution_id')] = $institutionId;
        $positionConditions[$StaffTable->Positions->aliasField('status_id')] = $ActiveStatusId;//POCOR-7016
        /* START : POCOR-6450
        if (!empty($activeStatusId)) {
            $positionConditions[$StaffTable->Positions->aliasField('status_id').' IN '] = $activeStatusId;
        }
        END : POCOR-6450 */
        // START : POCOR-6450
        $SecurityUsers = TableRegistry::get('Security.SecurityUsers');
        $SecurityUsersData = $SecurityUsers->find()
                            ->where([$SecurityUsers->aliasField('openemis_no') => $openemisNo])
                            ->first();
        $staffUserPriId = $SecurityUsersData->id;
        $expectedStaffStatuses = $this->getSpecificInstitutionStaff($institutionId, $staffUserPriId);
        if ( !empty($expectedStaffStatuses) ) {
            $positionConditions[$StaffTable->Positions->aliasField('staff_position_title_id').' NOT IN '] = $expectedStaffStatuses;
        }
        // END : POCOR-6450
        /**
         * @ticket POCOR-6522
         * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
         */
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $staff_min_role = $SecurityGroupUsers->find()
                            ->contain('SecurityRoles')
                            ->order(['SecurityRoles.order'])
                            ->where([$SecurityGroupUsers->aliasField('security_user_id') => $this->Auth->user()['id']])
                            ->first();
        if ( $this->Auth->user()['super_admin'] != 1 && isset($staff_min_role->security_role->order) && $staff_min_role->security_role->order > 0) {
            $positionConditions['SecurityRoles.order >= '] = $staff_min_role->security_role->order;
        }
        /**
         * END
         * @ticket POCOR-6522
         */
        if ($selectedFTE > 0) {
            $InsStaffTable = TableRegistry::get('Institution.Staff');//POCOR-5069
            $StaffPositionGradesTbl = TableRegistry::get('staff_position_grades');//POCOR-5069
            $staffPositionsOptions = $StaffTable->Positions
                ->find()
                ->innerJoinWith('StaffPositionTitles.SecurityRoles')//POCOR-5069 starts
                //->innerJoinWith('StaffPositionGrades')
                /*->innerJoin([$InsStaffTable->alias() => $InsStaffTable->table()], [
                    $InsStaffTable->aliasField('institution_position_id = ') . $StaffTable->Positions->aliasField('id'),
                ])
                ->innerJoin([$StaffPositionGradesTbl->alias() => $StaffPositionGradesTbl->table()], [
                    $StaffPositionGradesTbl->aliasField('id = ') . $InsStaffTable->aliasField('staff_position_grade_id'),
                ])*///POCOR-5069 ends
                ->where($positionConditions)
                ->select(['security_role_id' => 'SecurityRoles.id', 'type' => 'StaffPositionTitles.type'/*, 'grade_name' => $StaffPositionGradesTbl->aliasField('name')*/])//POCOR-5069
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
            $name = $position->name /*. ' - ' . $position->grade_name*/;//POCOR-5069
            $type = __($types[$position->type]);
            $options[] = ['value' => $position->id, 'group' => $type, 'name' => $name, 'disabled' => in_array($position->id, $excludePositions)];
        }
        $this->response->body(json_encode($options, JSON_UNESCAPED_UNICODE));
        $this->response->type('json');
        return $this->response;
    }

    /**
     * Get staff details of specific institution
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6522
     */
    private function getSpecificInstitutionStaff($institution_id, $staff_id)
    {
        $StaffStatusesTable = TableRegistry::get('Staff.StaffStatuses');
        $institutionPositionsTable = TableRegistry::get('Institution.InstitutionPositions');
        $StaffTable = TableRegistry::get('Institution.Staff');
        $alreadyAssignedStaffs = $StaffTable->find()->select([
            'institution_position_id' => $StaffTable->aliasField('institution_position_id'),
            'status_id' => $institutionPositionsTable->aliasField('status_id'),
            'staff_position_title_id' => $institutionPositionsTable->aliasField('staff_position_title_id')
        ])->innerJoin([$institutionPositionsTable->alias() => $institutionPositionsTable->table()], [
            $institutionPositionsTable->aliasField('id = ') . $StaffTable->aliasField('institution_position_id'),
        ])->where([
            $StaffTable->aliasField('institution_id') => $institution_id,
            $StaffTable->aliasField('staff_id') => $staff_id,
            $StaffTable->aliasField('staff_status_id') => $StaffStatusesTable->getIdByCode('ASSIGNED'),
        ])
        ->hydrate(false)->toArray();
        $expectedStaffStatuses = [];
        foreach ($alreadyAssignedStaffs AS $staff) {
            $expectedStaffStatuses[$staff['staff_position_title_id']] = $staff['staff_position_title_id'];
        }
        return $expectedStaffStatuses;
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
                    //POCOR-6692
                    'inProcess' => $reportCardProcesses->find()->where([
                                'report_card_id' => $reportCardId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                            ])->count(),
                    /*'inCompleted' => $institutionStudentsReportCards->find()->where([
                                'report_card_id' => $reportCardId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                                'status' => 3
                            ])->count()*/
                ])
                ->where(['academic_period_id' => $academicPeriodId,
                        $institutionClasses->aliasField('id IN ') => $ids
                        ])

                ->formatResults(function (ResultSetInterface $results) use ($reportCardId, $institutionId, $academicPeriodId) {
                    return $results->map(function ($row) use ($reportCardId, $institutionId, $academicPeriodId) {
                        $institutionStudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
                        $inCompleted = $institutionStudentsReportCards->find()->where([
                            $institutionStudentsReportCards->aliasField('report_card_id') => $reportCardId,
                            $institutionStudentsReportCards->aliasField('academic_period_id') => $academicPeriodId,
                            $institutionStudentsReportCards->aliasField('institution_id') => $institutionId,
                            $institutionStudentsReportCards->aliasField('institution_class_id') => $row['id'],
                            $institutionStudentsReportCards->aliasField('status') => 3
                        ])->count();
                        $row['inCompleted'] = $inCompleted;
                        return $row;
                    });
                
            });

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
        //$data['percentage'] = 0; //POCOR-6627 - commented line;it was adding extra data in totalProfileComplete
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
        //POCOR-6022 start
        //Land
        $institutionLand  = TableRegistry::get('institution_lands');
		$institutionLandData = $institutionLand->find()
				->select([
					'created' => 'institution_lands.created',
					'modified' => 'institution_lands.modified',
				])
				->where([$institutionLand->aliasField('institution_id') => $institutionId])
                ->order(['institution_lands.modified'=>'desc'])
				->limit(1)
				->first();
        
        //Room
        $institutionRoom  = TableRegistry::get('institution_rooms');
		$institutionRoomData = $institutionRoom->find()
				->select([
					'created' => 'institution_rooms.created',
					'modified' => 'institution_rooms.modified',
				])
				->where([$institutionRoom->aliasField('institution_id') => $institutionId])
                ->order(['institution_rooms.modified'=>'desc'])
				->limit(1)
				->first();
      
        //Building
        $institutionBuilding  = TableRegistry::get('institution_buildings');
        $institutionBuildingData = $institutionBuilding->find()
                ->select([
                      'created' => 'institution_buildings.created',
                      'modified' => 'institution_buildings.modified',
                ])
                ->where([$institutionBuilding->aliasField('institution_id') => $institutionId])
                ->order(['institution_buildings.modified'=>'desc'])
                ->limit(1)
                ->first();
                  
        //Floor
        $institutionFloor  = TableRegistry::get('institution_floors');
        $institutionFloorData = $institutionFloor->find()
                ->select([
                      'created' => 'institution_floors.created',
                      'modified' => 'institution_floors.modified',
                ])
                ->where([$institutionFloor->aliasField('institution_id') => $institutionId])
                ->order(['institution_floors.modified'=>'desc'])
                ->limit(1)
                ->first();
		 //POCOR-6022 ends 
        $data[16]['feature'] = 'Infrastructures Overview';
	
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
            ->where([$ConfigItem->aliasField('visible') => 1,$ConfigItem->aliasField('value') => 1,$ConfigItem->aliasField('type') => 'Institution Data Completeness'])//POCOR-6022
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
                    if(!empty($institutionLandData && $institutionBuildingData && $institutionFloorData  && $institutionRoomData)) {
                        $profileComplete = $profileComplete + 1;
                        $data[$key]['complete'] = 'yes';
                        //POCOR-6022 start
                        $modifiedDate1=($institutionLandData->modified)?date("F j,Y",strtotime($institutionLandData->modified)):date("F j,Y",strtotime($institutionLandData->created));
                        $modifiedDate2=($institutionBuildingData->modified)?date("F j,Y",strtotime($institutionBuildingData->modified)):date("F j,Y",strtotime($institutionBuildingData->created));
                        $modifiedDate3=($institutionFloorData->modified)?date("F j,Y",strtotime($institutionFloorData->modified)):date("F j,Y",strtotime($institutionFloorData->created));
                        $modifiedDate4=($institutionRoomData->modified)?date("F j,Y",strtotime($institutionRoomData->modified)):date("F j,Y",strtotime($institutionRoomData->created));
                        $date1=($modifiedDate1 > $modifiedDate2 ? $modifiedDate1 :$modifiedDate2);
                        $date2=($date1> $modifiedDate3 ? $date1 :$modifiedDate3);
                        $modifiedDate=($date2> $modifiedDate4 ? $date2 :$modifiedDate4);
                        $data[$key]['modifiedDate'] =$modifiedDate;
                        //POCOR-6022 ends
                        } 
                        else {
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

    /*POCOR-6286 starts*/ 
    public function InstitutionProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionsProfile']); }
    public function StaffProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffProfiles']); }
    public function StudentProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentProfiles']); }
    /*POCOR-6286 ends*/
    /*POCOR-6966 starts*/ 
    public function ClassesProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ClassesProfiles']); }
    /*POCOR-6966 ends*/ 

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
        $requestData = $requestData['params'];
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
        $requestData = $requestData['params'];
        $academic_period = $requestData['academic_period'];
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
    //POCOR-5069 starts
    public function getStaffPosititonGrades()
    {
        $staff_position_grades = TableRegistry::get('staff_position_grades');
        $staff_position_grades_result = $staff_position_grades
            ->find()
            ->select(['id','name'])
            ->where(['visible' => 1])
            ->toArray();
        foreach($staff_position_grades_result AS $result){
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);die;
    }//POCOR-5069 ends

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
        $fte = $requestData['params']['fte'];
        $startDate = $requestData['params']['startDate'];
        $institutionId = $requestData['params']['institution_id'];
        $openemisNo = $requestData['params']['openemis_no'];
        $endDate = null;
        if ($endDate == 'null') {
            $endDate = null;
        }
        $result = $this->getInstitutionPositions($institutionId, $fte, $startDate, $endDate, $openemisNo);
        echo $result; die;
    }

    public function checkStudentAdmissionAgeValidation()
    {
        $requestData = $this->request->input('json_decode', true);
        $dateOfBirth = $requestData['params']['date_of_birth'];
        $educationGradeId = $requestData['params']['education_grade_id'];
        
        $dobYear =  date('Y', strtotime($dateOfBirth));
        $currentYear =  date('Y', strtotime(date('Y-m-d')));
        $yearDiff = $currentYear - $dobYear;

        $ConfigItemTable = TableRegistry::get('config_items');
        $ConfigItemAgePlus = $ConfigItemTable->find('all',['conditions' =>['code' => 'admission_age_plus']])->first();
        $ConfigItemAgeMinus = $ConfigItemTable->find('all',['conditions' =>['code' => 'admission_age_minus']])->first();  
        $EducationGradesTable = TableRegistry::get('education_grades'); 
        $EducationGrades = $EducationGradesTable->find('all',['conditions' =>['id' => $educationGradeId]])->first();    
        $maxAge = ($EducationGrades->admission_age + $ConfigItemAgePlus->value);    
        $minAge = $EducationGrades->admission_age - $ConfigItemAgeMinus->value;
        if($minAge < 0){ $minAge =  0; } 
        if($yearDiff > $maxAge || $yearDiff < $minAge ){   
            $result_array[] = array("max_age" => $maxAge, "min_age" => $minAge, "validation_error" => 1);
        }else{
            $result_array[] = array("max_age" => $maxAge, "min_age" => $minAge, "validation_error" => 0);
        }
        echo json_encode($result_array);die;
    }

    public function getStartDateFromAcademicPeriod()
    {
        $requestData = $this->request->input('json_decode', true);
        $academicPeriodId = $requestData['params']['academic_period_id'];

        $AcademicPeriodsTable = TableRegistry::get('academic_periods');
        $academic_periods_result = $AcademicPeriodsTable
            ->find()
            ->where(['id' => $academicPeriodId])
            ->toArray();
        foreach($academic_periods_result AS $result){
            $result_array[] = array("id" => $result['id'], "name" => $result['name'], "start_date" => date('Y-m-d',strtotime($result['start_date'])), "start_year" => $result['start_year'], "end_date" => date('Y-m-d',strtotime($result['end_date'])), "end_year" => $result['end_year']);
        }
        echo json_encode($result_array);die;
   }

    public function getStudentTransferReason()
    {
        $student_transfer_reasons = TableRegistry::get('student_transfer_reasons');
        $student_transfer_reasons_result = $student_transfer_reasons
            ->find()
            ->select(['id','name'])
            ->where(['visible' => 1])
            ->order([$student_transfer_reasons->aliasField('order ASC')])
            ->toArray();
        foreach($student_transfer_reasons_result AS $result){
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);die;
    }

    public function studentCustomFields()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        $studentId = (array_key_exists('student_id', $requestData))? $requestData['student_id']: '';
        $studentCustomForms =  TableRegistry::get('student_custom_forms');
        $studentCustomFormsFields =  TableRegistry::get('student_custom_forms_fields');
        $studentCustomFields =  TableRegistry::get('student_custom_fields');
        $studentCustomFieldOptions =  TableRegistry::get('student_custom_field_options');
        $studentCustomFieldValues =  TableRegistry::get('student_custom_field_values');

        //POCOR-7123[START]
        $custom_modules_table  = TableRegistry::get('custom_modules');
        $custom_modules_data = $custom_modules_table
            ->find()
            ->where([$custom_modules_table->aliasField('code') => 'Student'])
            ->first();
        //POCOR-7123[END]

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
                                $studentCustomForms->aliasField('custom_module_id') => $custom_modules_data->id //POCOR-7123
                            ])
                            ->group([$studentCustomFormsFields->aliasField('section')])
                            ->toArray();

        $remove_field_type = ['FILE','COORDINATES','TABLE'];  
        $i= 0;    
        $fieldsArr = [];              
        foreach ($SectionData as $skey => $sval) {
            //$SectionArr[$skey][$sval->section] = $sval->section;
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
            
            foreach ($CustomFieldsData as $ckey => $cval) {
                $fieldsArr[$i]['student_custom_form_id'] = $cval->student_custom_form_id;
                $fieldsArr[$i]['student_custom_field_id'] = $cval->student_custom_field_id;
                $fieldsArr[$i]['section'] = $cval->section;
                $fieldsArr[$i]['name'] = $cval->name;
                $fieldsArr[$i]['order'] = $cval->order;
                $fieldsArr[$i]['description'] = $cval->description;
                $fieldsArr[$i]['field_type'] = $cval->field_type;
                $fieldsArr[$i]['is_mandatory'] = $cval->is_mandatory;
                $fieldsArr[$i]['is_unique'] = $cval->is_unique;
                $fieldsArr[$i]['params'] = $cval->params;

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
                    $fieldsArr[$i]['option'] = $OptionDataArr;
                }
                //get student custom field values
                if($studentId != ''){
                    $studentCustomFieldValuesData = $studentCustomFieldValues->find()
                            ->select([
                                'text_value'=>$studentCustomFieldValues->aliasField('text_value'),
                                'number_value'=>$studentCustomFieldValues->aliasField('number_value'),
                                'decimal_value'=>$studentCustomFieldValues->aliasField('decimal_value'),
                                'textarea_value'=>$studentCustomFieldValues->aliasField('textarea_value'),
                                'date_value'=>$studentCustomFieldValues->aliasField('date_value'),
                                'time_value'=>$studentCustomFieldValues->aliasField('time_value'),
                                'student_custom_field_id'=>$studentCustomFieldValues->aliasField('student_custom_field_id'),
                                'student_id'=>$studentCustomFieldValues->aliasField('student_id')
                            ])
                            ->where([
                                $studentCustomFieldValues->aliasField('student_custom_field_id') => $cval->student_custom_field_id,
                                $studentCustomFieldValues->aliasField('student_id') => $studentId
                            ])->toArray();
                    if(!empty($studentCustomFieldValuesData)){
                        if($cval->field_type == 'TEXT'){
                            $fieldsArr[$i]['values'] = $studentCustomFieldValuesData[0]->text_value;
                        } else if($cval->field_type == 'DECIMAL'){
                            $fieldsArr[$i]['values'] = $studentCustomFieldValuesData[0]->decimal_value;
                        } else if($cval->field_type == 'NUMBER'){
                            $fieldsArr[$i]['values'] = $studentCustomFieldValuesData[0]->number_value;
                        } else if($cval->field_type == 'TEXTAREA'){
                            $fieldsArr[$i]['values'] = $studentCustomFieldValuesData[0]->textarea_value;
                        } else if($cval->field_type == 'DATE'){
                            $fieldsArr[$i]['values'] = date('Y-m-d', strtotime($studentCustomFieldValuesData[0]->date_value));
                        } else if($cval->field_type == 'TIME'){
                            $fieldsArr[$i]['values'] = date('H:i:s', strtotime($studentCustomFieldValuesData[0]->time_value));
                        } else if($cval->field_type == 'DROPDOWN'){
                            $DropdownValDataArr =[];
                            foreach ($studentCustomFieldValuesData as $SV_key => $SV_value) {
                                $DropdownValDataArr[$SV_key]['dropdown_val'] = $SV_value->number_value;
                            }
                            $fieldsArr[$i]['values'] = $DropdownValDataArr;
                        }  else if($cval->field_type == 'CHECKBOX'){
                            $CheckboxValDataArr =[];
                            foreach ($studentCustomFieldValuesData as $SV_key => $SV_value) {
                                $CheckboxValDataArr[$SV_key]['checkbox_val'] = $SV_value->number_value;
                            }
                            $fieldsArr[$i]['values'] = $CheckboxValDataArr;
                        }
                    }else{
                        $fieldsArr[$i]['values'] = '';    
                    }
                }else{
                    $fieldsArr[$i]['values'] = '';  
                }
                $i++;
            }
            //$SectionArr[$skey][$sval->section] = $fieldsArr;
            $SectionArr = $fieldsArr;
        }
        echo json_encode($SectionArr);die;
    }

    public function staffCustomFields()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        $staffId = (array_key_exists('staff_id', $requestData))? $requestData['staff_id']: '';
        $staffCustomForms =  TableRegistry::get('staff_custom_forms');
        $staffCustomFormsFields =  TableRegistry::get('staff_custom_forms_fields');
        $staffCustomFields =  TableRegistry::get('staff_custom_fields');
        $staffCustomFieldOptions =  TableRegistry::get('staff_custom_field_options');
        $staffCustomFieldValues =  TableRegistry::get('staff_custom_field_values');

        //POCOR-7123[START]
        $custom_modules_table  = TableRegistry::get('custom_modules');
        $custom_modules_data = $custom_modules_table
            ->find()
            ->where([$custom_modules_table->aliasField('code') => 'Staff'])
            ->first();
        //POCOR-7123[END]

        $SectionData = $staffCustomForms->find()
                            ->select([
                                'staff_custom_form_id'=>$staffCustomFormsFields->aliasField('staff_custom_form_id'),
                                'staff_custom_field_id'=>$staffCustomFormsFields->aliasField('staff_custom_field_id'),
                                'section'=>$staffCustomFormsFields->aliasField('section'),
                            ])
                            ->LeftJoin([$staffCustomFormsFields->alias() => $staffCustomFormsFields->table()], [
                                $staffCustomFormsFields->aliasField('staff_custom_form_id =') . $staffCustomForms->aliasField('id'),
                            ])
                            ->where([
                                $staffCustomForms->aliasField('custom_module_id') => $custom_modules_data->id
                            ])
                            ->group([$staffCustomFormsFields->aliasField('section')])
                            ->toArray();
                           
        $remove_field_type = ['FILE','COORDINATES','TABLE'];  
        $i= 0;    
        $fieldsArr = [];              
        foreach ($SectionData as $skey => $sval) {
            //$SectionArr[$skey][$sval->section] = $sval->section;
            $CustomFieldsData = $staffCustomFormsFields->find()
                            ->select([
                                'staff_custom_form_id'=>$staffCustomFormsFields->aliasField('staff_custom_form_id'),
                                'staff_custom_field_id'=>$staffCustomFormsFields->aliasField('staff_custom_field_id'),
                                'section'=>$staffCustomFormsFields->aliasField('section'),
                                'name'=>$staffCustomFormsFields->aliasField('name'),
                                'order'=>$staffCustomFormsFields->aliasField('order'),
                                'description'=>$staffCustomFields->aliasField('description'),
                                'field_type'=>$staffCustomFields->aliasField('field_type'),
                                'is_mandatory'=>$staffCustomFields->aliasField('is_mandatory'),
                                'is_unique'=>$staffCustomFields->aliasField('is_unique'),
                                'params'=>$staffCustomFields->aliasField('params'),
                            ])
                            ->LeftJoin([$staffCustomFields->alias() => $staffCustomFields->table()], [
                                $staffCustomFields->aliasField('id =') . $staffCustomFormsFields->aliasField('staff_custom_field_id'),
                            ])
                            ->where([
                                $staffCustomFormsFields->aliasField('section') => $sval->section,
                                $staffCustomFields->aliasField('field_type NOT IN') => $remove_field_type
                            ])->toArray();
            
            foreach ($CustomFieldsData as $ckey => $cval) {
                $fieldsArr[$i]['staff_custom_form_id'] = $cval->staff_custom_form_id;
                $fieldsArr[$i]['staff_custom_field_id'] = $cval->staff_custom_field_id;
                $fieldsArr[$i]['section'] = $cval->section;
                $fieldsArr[$i]['name'] = $cval->name;
                $fieldsArr[$i]['order'] = $cval->order;
                $fieldsArr[$i]['description'] = $cval->description;
                $fieldsArr[$i]['field_type'] = $cval->field_type;
                $fieldsArr[$i]['is_mandatory'] = $cval->is_mandatory;
                $fieldsArr[$i]['is_unique'] = $cval->is_unique;
                $fieldsArr[$i]['params'] = $cval->params;

                if($cval->field_type == 'DROPDOWN' || $cval->field_type == 'CHECKBOX'){
                    $OptionData = $staffCustomFieldOptions->find()
                                    ->select([
                                        'option_id'=>$staffCustomFieldOptions->aliasField('id'),
                                        'option_name'=>$staffCustomFieldOptions->aliasField('name'),
                                        'is_default'=>$staffCustomFieldOptions->aliasField('is_default'),
                                        'visible'=>$staffCustomFieldOptions->aliasField('visible'),
                                        'option_order'=>$staffCustomFieldOptions->aliasField('order')
                                    ])
                                    ->where([
                                        $staffCustomFieldOptions->aliasField('staff_custom_field_id') => $cval->staff_custom_field_id
                                    ])->toArray();
                    $OptionDataArr =[];
                    foreach ($OptionData as $opkey => $opval) {
                        $OptionDataArr[$opkey]['option_id'] = $opval->option_id;
                        $OptionDataArr[$opkey]['option_name'] = $opval->option_name;
                        $OptionDataArr[$opkey]['is_default'] = $opval->is_default;
                        $OptionDataArr[$opkey]['visible'] = $opval->visible;
                        $OptionDataArr[$opkey]['option_order'] = $opval->option_order;
                    }
                    $fieldsArr[$i]['option'] = $OptionDataArr;
                }
                //get staff custom field values
                if($staffId != ''){
                    $staffCustomFieldValuesData = $staffCustomFieldValues->find()
                            ->select([
                                'text_value'=>$staffCustomFieldValues->aliasField('text_value'),
                                'number_value'=>$staffCustomFieldValues->aliasField('number_value'),
                                'decimal_value'=>$staffCustomFieldValues->aliasField('decimal_value'),
                                'textarea_value'=>$staffCustomFieldValues->aliasField('textarea_value'),
                                'date_value'=>$staffCustomFieldValues->aliasField('date_value'),
                                'time_value'=>$staffCustomFieldValues->aliasField('time_value'),
                                'staff_custom_field_id'=>$staffCustomFieldValues->aliasField('staff_custom_field_id'),
                                'staff_id'=>$staffCustomFieldValues->aliasField('staff_id')
                            ])
                            ->where([
                                $staffCustomFieldValues->aliasField('staff_custom_field_id') => $cval->staff_custom_field_id,
                                $staffCustomFieldValues->aliasField('staff_id') => $staffId
                            ])->toArray();
                    if(!empty($staffCustomFieldValuesData)){
                        if($cval->field_type == 'TEXT'){
                            $fieldsArr[$i]['values'] = $staffCustomFieldValuesData[0]->text_value;
                        } else if($cval->field_type == 'DECIMAL'){
                            $fieldsArr[$i]['values'] = $staffCustomFieldValuesData[0]->decimal_value;
                        } else if($cval->field_type == 'NUMBER'){
                            $fieldsArr[$i]['values'] = $staffCustomFieldValuesData[0]->number_value;
                        } else if($cval->field_type == 'TEXTAREA'){
                            $fieldsArr[$i]['values'] = $staffCustomFieldValuesData[0]->textarea_value;
                        } else if($cval->field_type == 'DATE'){
                            $fieldsArr[$i]['values'] = date('Y-m-d', strtotime($staffCustomFieldValuesData[0]->date_value));
                        } else if($cval->field_type == 'TIME'){
                            $fieldsArr[$i]['values'] = date('H:i:s', strtotime($staffCustomFieldValuesData[0]->time_value));
                        } else if($cval->field_type == 'DROPDOWN'){
                            $DropdownValDataArr =[];
                            foreach ($staffCustomFieldValuesData as $SV_key => $SV_value) {
                                $DropdownValDataArr[$SV_key]['dropdown_val'] = $SV_value->number_value;
                            }
                            $fieldsArr[$i]['values'] = $DropdownValDataArr;
                        }  else if($cval->field_type == 'CHECKBOX'){
                            $CheckboxValDataArr =[];
                            foreach ($staffCustomFieldValuesData as $SV_key => $SV_value) {
                                $CheckboxValDataArr[$SV_key]['checkbox_val'] = $SV_value->number_value;
                            }
                            $fieldsArr[$i]['values'] = $CheckboxValDataArr;
                        }
                    }else{
                        $fieldsArr[$i]['values'] = '';    
                    }
                }else{
                    $fieldsArr[$i]['values'] = '';  
                }
                $i++;
            }
            //$SectionArr[$skey][$sval->section] = $fieldsArr;
            $SectionArr = $fieldsArr;
        }
        echo json_encode($SectionArr);die;
    }

    public function saveStudentData()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        /*$requestData = json_decode('{"institution_id":"6","login_user_id":"1","openemis_no":"152227233311111222","first_name":"AMARTAA","middle_name":"","third_name":"","last_name":"Fenicott","preferred_name":"","gender_id":"1","date_of_birth":"2011-01-01","identity_number":"1231122","nationality_id":"2","username":"kkk111","password":"sdsd","postal_code":"12233","address":"sdsdsds","birthplace_area_id":"2","address_area_id":"2","identity_type_id":"160","education_grade_id":"59","academic_period_id":"30", "start_date":"01-01-2021","end_date":"31-12-2021","institution_class_id":"524","student_status_id":1,"custom":[{"student_custom_field_id":17,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":27,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":29,"text_value":"test.jpg","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":28,"text_value":"","number_value":2,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":3,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":26,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":4,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":8,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":9,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":30,"text_value":"{\"latitude\":\"11.1\",\"longitude\":\"2.22\"}","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":18,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"}]}', true);*/
        if(!empty($requestData)){
            $openemisNo = (array_key_exists('openemis_no', $requestData))? $requestData['openemis_no']: null;
            $firstName = (array_key_exists('first_name', $requestData))? $requestData['first_name']: null;
            $middleName = (array_key_exists('middle_name', $requestData))? $requestData['middle_name']: null;
            $thirdName = (array_key_exists('third_name', $requestData))? $requestData['third_name']: null;
            $lastName = (array_key_exists('last_name', $requestData))? $requestData['last_name']: null;
            $preferredName = (array_key_exists('preferred_name', $requestData))? $requestData['preferred_name']: null;
            $genderId = (array_key_exists('gender_id', $requestData))? $requestData['gender_id']: null;
            $dateOfBirth = (array_key_exists('date_of_birth', $requestData))? date('Y-m-d', strtotime($requestData['date_of_birth'])): null;
            $identityNumber = (array_key_exists('identity_number', $requestData))? $requestData['identity_number']: null;
            $nationalityId = (array_key_exists('nationality_id', $requestData))? $requestData['nationality_id']: null;
            $nationalityName = (array_key_exists('nationality_name', $requestData))? $requestData['nationality_name']: null;
            $username = (array_key_exists('username', $requestData))? $requestData['username']: null;
            $password = (array_key_exists('password', $requestData))? password_hash($requestData['password'],  PASSWORD_DEFAULT) : null;
            $address  = (array_key_exists('address', $requestData))? $requestData['address'] : null;
            $postalCode = (array_key_exists('postal_code', $requestData))? $requestData['postal_code'] : null;
            $birthplaceAreaId = (array_key_exists('birthplace_area_id', $requestData))? $requestData['birthplace_area_id'] : null;
            $addressAreaId = (array_key_exists('address_area_id', $requestData))? $requestData['address_area_id'] : null;
            $identityTypeId = (array_key_exists('identity_type_id', $requestData))? $requestData['identity_type_id'] : null;
            $identityTypeName = (array_key_exists('identity_type_name', $requestData))? $requestData['identity_type_name'] : null;
            
            $institutionClassId = (array_key_exists('institution_class_id', $requestData))? $requestData['institution_class_id'] : null;
            $educationGradeId = (array_key_exists('education_grade_id', $requestData))? $requestData['education_grade_id'] : null;
            $academicPeriodId = (array_key_exists('academic_period_id', $requestData))? $requestData['academic_period_id'] : null;
            $startDate = (array_key_exists('start_date', $requestData))? date('Y-m-d', strtotime($requestData['start_date'])) : null;
            $endDate = (array_key_exists('end_date', $requestData))? date('Y-m-d', strtotime($requestData['end_date'])) : null;
            
            //$institutionId = $this->request->session()->read('Institution.Institutions.id');
            $institutionId = (array_key_exists('institution_id', $requestData))? $requestData['institution_id'] : null;
            $studentStatusId = (array_key_exists('student_status_id', $requestData))? $requestData['student_status_id'] : null;
            $userId = !empty($this->request->session()->read('Auth.User.id')) ? $this->request->session()->read('Auth.User.id') : 1;
            $custom = (array_key_exists('custom', $requestData))? $requestData['custom'] : "";
            $photoContent = (array_key_exists('photo_base_64', $requestData))? $requestData['photo_base_64'] : null;
            $photoName = (array_key_exists('photo_name', $requestData))? $requestData['photo_name'] : null;
            //when student transfer in other institution starts
            $isDiffSchool = (array_key_exists('is_diff_school', $requestData))? $requestData['is_diff_school'] : 0;
            $studentId = (array_key_exists('student_id', $requestData))? $requestData['student_id'] : 0;
            $previousInstitutionId = (array_key_exists('previous_institution_id', $requestData))? $requestData['previous_institution_id'] : 0;
            $previousAcademicPeriodId = (array_key_exists('previous_academic_period_id', $requestData))? $requestData['previous_academic_period_id'] : 0;
            $previousEducationGradeId = (array_key_exists('previous_education_grade_id', $requestData))? $requestData['previous_education_grade_id'] : 0;
            $studentTransferReasonId = (array_key_exists('student_transfer_reason_id', $requestData))? $requestData['student_transfer_reason_id'] : 0;
            $comment = (array_key_exists('comment', $requestData))? $requestData['comment'] : '';
            //when student transfer in other institution end
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
            //get nationality data
            $nationalities = '';
            if(!empty($nationalityName)){
                $nationalitiesTbl = TableRegistry::get('nationalities');
                $nationalities = $nationalitiesTbl->find()
                    ->where([
                        $nationalitiesTbl->aliasField('name') => $nationalityName,
                    ])
                    ->first();
                
                if(empty($nationalities)){
                    $orderNationalities = $nationalitiesTbl->find()
                        ->order([$nationalitiesTbl->aliasField('order DESC')])
                        ->first();
                    
                    $entityNationality = [
                        'name' => $nationalityName,
                        'order' => !empty($orderNationalities->order) ? $orderNationalities->order + 1 : 0,
                        'visible' => 1,
                        'editable' => 1,
                        'identity_type_id' => null,
                        'default' => 0,
                        'international_code' => '',
                        'national_code' => '',
                        'external_validation' => 0,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in nationalities table if doesn't exist in table
                    $entityNationalityData = $nationalitiesTbl->newEntity($entityNationality);
                    $NationalitiesResult = $nationalitiesTbl->save($entityNationalityData);
                    if($NationalitiesResult){
                        $nationalities->id = $NationalitiesResult->id;
                    } 
                }
            }
            //transfer student in other institution
            if($isDiffSchool == 1){
                $workflows = TableRegistry::get('workflows');
                $workflowSteps = TableRegistry::get('workflow_steps');
                $workflowResults = $workflows->find()
                            ->select(['workflowSteps_id'=>$workflowSteps->aliasField('id')])
                            ->LeftJoin([$workflowSteps->alias() => $workflowSteps->table()], [
                                $workflowSteps->aliasField('workflow_id =') . $workflows->aliasField('id'),
                                $workflowSteps->aliasField('name')=> 'Open'
                            ])
                            ->where([
                                $workflows->aliasField('name') => 'Student Transfer - Receiving'
                            ])
                            ->first();
                $InstitutionStudentTransfers = TableRegistry::get('institution_student_transfers');
                $entityTransferData = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'requested_date' => null,
                    'student_id' => $studentId,
                    'status_id' => $workflowResults->workflowSteps_id,
                    'assignee_id' => $this->Auth->user('id'), //POCOR-7080
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'education_grade_id' => $educationGradeId,
                    'institution_class_id' => $institutionClassId,
                    'previous_institution_id' => $previousInstitutionId,
                    'previous_academic_period_id' => $previousAcademicPeriodId,
                    'previous_education_grade_id' => $previousEducationGradeId,
                    'student_transfer_reason_id' => $studentTransferReasonId,
                    'comment' => $comment,
                    'all_visible' => 1,
                    'modified_user_id' => null,
                    'modified' => null,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ];
                $entity1 = $InstitutionStudentTransfers->newEntity($entityTransferData);
                try{
                    $InstitutionStudentTransferResult = $InstitutionStudentTransfers->save($entity1);
                    unset($entity1);
                    unset($InstitutionStudentTransferResult);
                    die('success');
                }catch (Exception $e) {
                    return null;
                }
            }else{
                $SecurityUsers = TableRegistry::get('security_users');
                $CheckStudentExist = $SecurityUsers->find()
                                ->where([
                                    $SecurityUsers->aliasField('openemis_no') => $openemisNo
                                ])->first();

                $SecurityUsers = TableRegistry::get('security_users');
                if(!empty($CheckStudentExist)){
                    $existStudentId = $CheckStudentExist->id;
                    $entityData = [
                        'id'=> $existStudentId,
                        'openemis_no' => $openemisNo,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'third_name' => $thirdName,
                        'last_name' => $lastName,
                        'preferred_name' => $preferredName,
                        'gender_id' => $genderId,
                        'date_of_birth' => $dateOfBirth,
                        'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                        'preferred_language' => $pref_lang->value,
                        'username' => $username,
                        'password' => $password,
                        'address' => $address,
                        'address_area_id' => $addressAreaId,
                        'birthplace_area_id' => $birthplaceAreaId,
                        'postal_code' => $postalCode,
                        'photo_name' => $photoName,
                        'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                        'is_student' => 1,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                }else{
                    $entityData = [
                        'openemis_no' => $openemisNo,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'third_name' => $thirdName,
                        'last_name' => $lastName,
                        'preferred_name' => $preferredName,
                        'gender_id' => $genderId,
                        'date_of_birth' => $dateOfBirth,
                        'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                        'preferred_language' => $pref_lang->value,
                        'username' => $username,
                        'password' => $password,
                        'address' => $address,
                        'address_area_id' => $addressAreaId,
                        'birthplace_area_id' => $birthplaceAreaId,
                        'postal_code' => $postalCode,
                        'photo_name' => $photoName,
                        'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                        'is_student' => 1,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                }
                //save in security_users table
                $entity = $SecurityUsers->newEntity($entityData);
                try{
                    $SecurityUserResult = $SecurityUsers->save($entity);
                    unset($entity);
                }catch (Exception $e) {
                    return null;
                }
                     
                if($SecurityUserResult){
                    $user_record_id=$SecurityUserResult->id;
                    if(!empty($nationalityId) || !empty($nationalityName)){
                        if(!empty($nationalities->id)){
                            $UserNationalities = TableRegistry::get('user_nationalities');
                            $checkexistingNationalities = $UserNationalities->find()
                                ->where([
                                    $UserNationalities->aliasField('nationality_id') => $nationalities->id,
                                    $UserNationalities->aliasField('security_user_id') => $user_record_id,
                                ])->first();
                            if(empty($checkexistingNationalities)){
                                $primaryKey = $UserNationalities->primaryKey();
                                $hashString = [];
                                foreach ($primaryKey as $key) {
                                    if($key == 'nationality_id'){
                                        $hashString[] = $nationalities->id;
                                    }
                                    if($key == 'security_user_id'){
                                        $hashString[] = $user_record_id;
                                    }
                                }
                     
                                $entityNationalData = [
                                    'id' => Security::hash(implode(',', $hashString), 'sha256'),
                                    'preferred' => 1,
                                    'nationality_id' => $nationalities->id,
                                    'security_user_id' => $user_record_id,
                                    'created_user_id' => $userId,
                                    'created' => date('Y-m-d H:i:s')
                                ];
                                //save in user_nationalities table
                                $entityNationalData = $UserNationalities->newEntity($entityNationalData);
                                $UserNationalitiesResult = $UserNationalities->save($entityNationalData);  
                            }
                        }
                    }

                    if(!empty($nationalities->id) && !empty($identityTypeId) && !empty($identityNumber)){
                        $identityTypesTbl = TableRegistry::get('identity_types');
                        $identityTypes = $identityTypesTbl->find()
                            ->where([
                                $identityTypesTbl->aliasField('name') => $identityTypeName,
                            ])
                            ->first();
                        if(!empty($identityTypes)){
                            $UserIdentities = TableRegistry::get('user_identities');
                            $checkexistingIdentities = $UserIdentities->find()
                                ->where([
                                    $UserIdentities->aliasField('nationality_id') => $nationalities->id,
                                    $UserIdentities->aliasField('identity_type_id') => $identityTypeId,
                                    $UserIdentities->aliasField('number') => $identityNumber,
                                ])->first();
                            if(empty($checkexistingIdentities)){
                                $entityIdentitiesData = [
                                    'identity_type_id' => $identityTypes->id,
                                    'number' => $identityNumber,
                                    'nationality_id' => $nationalities->id,
                                    'security_user_id' => $user_record_id,
                                    'created_user_id' => $userId,
                                    'created' => date('Y-m-d H:i:s')
                                ];
                                //save in user_identities table
                                $entityIdentitiesData = $UserIdentities->newEntity($entityIdentitiesData);
                                $UserIdentitiesResult = $UserIdentities->save($entityIdentitiesData);
                            }
                        }
                    }

                    if(!empty($educationGradeId) && !empty($academicPeriodId) && !empty($institutionId)){
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
                            'created' => date('Y-m-d H:i:s')
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
                    if(!empty($educationGradeId) && !empty($institutionId) && !empty($academicPeriodId) && !empty($institutionClassId) && !empty($workflowResults)){
                        $institutionStudentAdmission = TableRegistry::get('institution_student_admission');
                        $entityAdmissionData = [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'student_id' => $user_record_id,
                            'status_id' => $workflowResults->workflowSteps_id, 
                            'assignee_id' => $this->Auth->user('id'), //POCOR7080
                            'institution_id' => $institutionId,
                            'academic_period_id' => $academicPeriodId,
                            'education_grade_id' => $educationGradeId,
                            'institution_class_id' => $institutionClassId,
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s')
                        ];
                        //save in institution_student_admission table
                        $entityAdmissionData = $institutionStudentAdmission->newEntity($entityAdmissionData);
                        $InstitutionAdmissionResult = $institutionStudentAdmission->save($entityAdmissionData);
                    }

                    if(!empty($educationGradeId) && !empty($institutionId) && !empty($academicPeriodId) && !empty($institutionClassId)){
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
                            'created' => date('Y-m-d H:i:s')
                        ];
                        //save in institution_class_students table
                        $entityClassData = $institutionClassStudents->newEntity($entityAdmissionData);
                        $InstitutionClassResult = $institutionClassStudents->save($entityClassData);
                    }

                    if(!empty($educationGradeId) && !empty($institutionId) && !empty($academicPeriodId) && !empty($institutionClassId)){
                        $institutionClassSubjects = TableRegistry::get('institution_class_subjects');
                        $institutionSubjects = TableRegistry::get('institution_subjects');
                        $educationGradesSubjects = TableRegistry::get('education_grades_subjects');//POCOR-7197
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
                            ])//POCOR-7197 starts
                            ->InnerJoin([$educationGradesSubjects->alias() => $educationGradesSubjects->table()], [
                                $institutionSubjects->aliasField('education_grade_id =') . $educationGradesSubjects->aliasField('education_grade_id'),
                                $institutionSubjects->aliasField('education_subject_id =') . $educationGradesSubjects->aliasField('education_subject_id')
                            ])//POCOR-7197 ends
                            ->where([
                                $institutionClassSubjects->aliasField('institution_class_id') => $institutionClassId,
                                $institutionSubjects->aliasField('academic_period_id') => $academicPeriodId,//POCOR-7197
                                $educationGradesSubjects->aliasField('auto_allocation !=') => 0//POCOR-7197
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
                                    'created' => date('Y-m-d H:i:s')
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
                        //if student custom field values already exist in student_custom_field_values table the delete the old values and insert the new ones.
                        $studentCustomFieldValues =  TableRegistry::get('student_custom_field_values');
                        $StudentCustomFieldValuesCount = $studentCustomFieldValues
                                                            ->find()
                                                            ->where([$studentCustomFieldValues->aliasField('student_id') => $user_record_id])
                                                            ->count();
                        if($StudentCustomFieldValuesCount > 0){
                            $studentCustomFieldValues->deleteAll(['student_id' => $user_record_id]);
                        }
                        
                        foreach ($custom as $skey => $sval) {
                            $entityCustomData = [
                                'id' => Text::uuid(),
                                'text_value' => $sval['text_value'],
                                'number_value' => $sval['number_value'],
                                'decimal_value' => $sval['decimal_value'],
                                'textarea_value' => $sval['textarea_value'],
                                'date_value' => $sval['date_value'],
                                'time_value' => $sval['time_value'],
                                'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                                'student_custom_field_id' => $sval['student_custom_field_id'],
                                'student_id' => $user_record_id,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in student_custom_field_values table
                            $entityCustomData = $studentCustomFieldValues->newEntity($entityCustomData);
                            $studentCustomFieldsResult = $studentCustomFieldValues->save($entityCustomData);
                            unset($studentCustomFieldsResult);
                            unset($entityCustomData);
                        }
                    }

                    try{
                        //for sending webhook while student update / create
                        $InstitutionStudents = TableRegistry::get('Institution.Students');
                        $bodyData = $InstitutionStudents->find('all',
                                        [ 'contain' => [
                                            'Institutions',
                                            'EducationGrades',
                                            'AcademicPeriods',
                                            'StudentStatuses',
                                            'Users',
                                            'Users.Genders',
                                            'Users.MainNationalities',
                                            'Users.Identities.IdentityTypes',
                                            'Users.AddressAreas',
                                            'Users.BirthplaceAreas',
                                            'Users.Contacts.ContactTypes'
                                        ],
                                    ])->where([
                                        $InstitutionStudents->aliasField('student_id') => $user_record_id
                                    ]);
                        
                        if (!empty($bodyData)) { 
                            foreach ($bodyData as $key => $value) { 
                                $user_id = $value->user->id;
                                $openemis_no = $value->user->openemis_no;
                                $first_name = $value->user->first_name;
                                $middle_name = $value->user->middle_name;
                                $third_name = $value->user->third_name;
                                $last_name = $value->user->last_name;
                                $preferred_name = $value->user->preferred_name;
                                $gender = $value->user->gender->name;
                                $nationality = $value->user->main_nationality->name;
                                // POCOR-6283 start
                                $dateOfBirth = $value->user->date_of_birth; 
                                $address = $value->user->address;
                                $postalCode = $value->user->postal_code;
                                $addressArea = $value->user->address_area->name;
                                $birthplaceArea = $value->user->birthplace_area->name;
                                $role = $value->user->is_student;
                                
                                $contactValue = $contactType = [];
                                if(!empty($value->user['contacts'])) {
                                    foreach ($value->user['contacts'] as $key => $contact) {
                                        $contactValue[] = $contact->value;
                                        $contactType[] = $contact->contact_type->name;
                                    }
                                }
                                
                                $identityNumber = $identityType = [];
                                if(!empty($value->user['identities'])) {
                                    foreach ($value->user['identities'] as $key => $identity) {
                                        $identityNumber[] = $identity->number;
                                        $identityType[] = $identity->identity_type->name;
                                    }
                                }
                                
                                $username = $value->user->username;
                                $institution_id = $value->institution->id;
                                $institutionName = $value->institution->name;
                                $institutionCode = $value->institution->code;
                                $educationGrade = $value->education_grade->name;
                                $academicCode = $value->academic_period->code;
                                $academicGrade = $value->academic_period->name;
                                $studentStatus = $value->student_status->name;
                                $startDate=$value->start_date;
                                $endDate=$value->end_date;
                            }

                            $securityGroupUsers = $this->assignStudentRoleGroup($institutionId, $user_id);//POCOR-7146
                        }
                        $bodys = array();
                        $bodys = [   
                            'security_users_id' => !empty($user_id) ? $user_id : NULL,
                            'security_users_openemis_no' => !empty($openemis_no) ? $openemis_no : NULL,
                            'security_users_first_name' =>  !empty($first_name) ? $first_name : NULL,
                            'security_users_middle_name' => !empty($middle_name) ? $middle_name : NULL,
                            'security_users_third_name' => !empty($third_name) ? $third_name : NULL,
                            'security_users_last_name' => !empty($last_name) ? $last_name : NULL,
                            'security_users_preferred_name' => !empty($preferred_name) ? $preferred_name : NULL,
                            'security_users_gender' => !empty($gender) ? $gender : NULL,
                            'security_users_date_of_birth' => !empty($dateOfBirth) ? date("d-m-Y", strtotime($dateOfBirth)) : NULL,
                            'security_users_address' => !empty($address) ? $address : NULL,
                            'security_users_postal_code' => !empty($postalCode) ? $postalCode : NULL,
                            'area_administrative_name_birthplace' => !empty($addressArea) ? $addressArea : NULL,
                            'area_administrative_name_address' => !empty($birthplaceArea) ? $birthplaceArea : NULL,
                            'contact_type_name' => !empty($contactType) ? $contactType : NULL,
                            'user_contact_type_value' => !empty($contactValue) ? $contactValue : NULL,
                            'nationality_name' => !empty($nationality) ? $nationality : NULL,
                            'identity_type_name' => !empty($identityType) ? $identityType : NULL,
                            'user_identities_number' => !empty($identityNumber) ? $identityNumber : NULL,
                            'security_user_username' => !empty($username) ? $username : NULL,
                            'institutions_id' => !empty($institution_id) ? $institution_id : NULL,
                            'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
                            'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
                            'academic_period_code' => !empty($academicCode) ? $academicCode : NULL,
                            'academic_period_name' => !empty($academicGrade) ? $academicGrade : NULL,
                            'education_grade_name' => !empty($educationGrade) ? $educationGrade : NULL,
                            'student_status_name' => !empty($studentStatus) ? $studentStatus : NULL,
                            'institution_students_start_date' => !empty($startDate) ? date("d-m-Y", strtotime($startDate)) : NULL,
                            'institution_students_end_date' => !empty($endDate) ? date("d-m-Y", strtotime($endDate)) : NULL,
                            'role_name' => ($role == 1) ? 'student' : NULL  
                        ];

                        //POCOR-7078 start
                        $studentCustomFieldValues = TableRegistry::get('student_custom_field_values');
                        $studentCustomFieldOptions = TableRegistry::get('student_custom_field_options');
                        $studentCustomFields = TableRegistry::get('student_custom_fields');
                        $studentCustomData = $studentCustomFieldValues->find()
                            ->select([
                                    'id' => $studentCustomFieldValues->aliasField('id'),
                                    'custom_id' => 'studentCustomField.id',
                                    'student_id'=> $studentCustomFieldValues->aliasField('student_id'),
                                    'student_custom_field_id' => $studentCustomFieldValues->aliasField('student_custom_field_id'),
                                    'text_value' => $studentCustomFieldValues->aliasField('text_value'),
                                    'number_value' => $studentCustomFieldValues->aliasField('number_value'),
                                    'decimal_value' => $studentCustomFieldValues->aliasField('decimal_value'),
                                    'textarea_value'=> $studentCustomFieldValues->aliasField('textarea_value'),
                                    'date_value' => $studentCustomFieldValues->aliasField('date_value'),
                                    'time_value' => $studentCustomFieldValues->aliasField('time_value'),
                                    'option_value_text' => $studentCustomFieldOptions->aliasField('name'),
                                    'name' => 'studentCustomField.name',
                                    'field_type' => 'studentCustomField.field_type',
                                ])->leftJoin(
                                ['studentCustomField' => 'student_custom_fields'],
                                [
                                    'studentCustomField.id = '.$studentCustomFieldValues->aliasField('student_custom_field_id')
                                ])
                                ->leftJoin(
                                [$studentCustomFieldOptions->alias() => $studentCustomFieldOptions->table()],
                                [
                                    $studentCustomFieldOptions->aliasField('student_custom_field_id = ') . $studentCustomFieldValues->aliasField('student_custom_field_id'),
                                    $studentCustomFieldOptions->aliasField('id = ') . $studentCustomFieldValues->aliasField('number_value')
                                ])
                                ->where([
                                $studentCustomFieldValues->aliasField('student_id') => $user_id,
                                ])->hydrate(false)->toArray();
                        $custom_field = array();
                        $count = 0;
                        if(!empty($studentCustomData)){
                            foreach ($studentCustomData as $val) {
                                $custom_field['custom_field'][$count]["id"] = (!empty($val['custom_id']) ? $val['custom_id'] : '');
                                $custom_field['custom_field'][$count]["name"]= (!empty($val['name']) ? $val['name'] : '');
                                $fieldTypes[$count] = (!empty($val['field_type']) ? $val['field_type'] : '');
                                $fieldType = $fieldTypes[$count];
                                if($fieldType == 'TEXT'){
                                    $custom_field['custom_field'][$count]["text_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                                }else if ($fieldType == 'CHECKBOX') {
                                    $custom_field['custom_field'][$count]["checkbox_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                                }else if ($fieldType == 'NUMBER') {
                                    $custom_field['custom_field'][$count]["number_value"] = (!empty($val['number_value']) ? $val['number_value'] : '');
                                }else if ($fieldType == 'DECIMAL') {
                                    $custom_field['custom_field'][$count]["decimal_value"] = (!empty($val['decimal_value']) ? $val['decimal_value'] : '');
                                }else if ($fieldType == 'TEXTAREA') {
                                    $custom_field['custom_field'][$count]["textarea_value"] = (!empty($val['textarea_value']) ? $val['textarea_value'] : '');
                                }else if ($fieldType == 'DROPDOWN') {
                                    $custom_field['custom_field'][$count]["dropdown_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                                }else if ($fieldType == 'DATE') {
                                    $custom_field['custom_field'][$count]["date_value"] = date('Y-m-d', strtotime($val->date_value));
                                }else if ($fieldType == 'TIME') {
                                    $custom_field['custom_field'][$count]["time_value"] = date('h:i A', strtotime($val->time_value));
                                }else if ($fieldType == 'COORDINATES') {
                                    $custom_field['custom_field'][$count]["cordinate_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                                }
                                $count++;
                            }
                        }
                        $getClassData = $this->institutionClassStudentData($institutionClassId);//POCOR-6995
                        $body = array_merge($bodys, $custom_field, $getClassData);//POCOR-7078 end
                        if (!empty($body)) {
                            $Webhooks = TableRegistry::get('Webhook.Webhooks');
                            if (!empty($studentId)) {
                                $Webhooks->triggerShell('student_update', ['username' => ''], $body);
                            }else{
                                $Webhooks->triggerShell('student_create', ['username' => ''], $body);
                            }
                        }
                        
                        die('success');        
                    }catch(Exception $e){
                        return $e;
                    }
                }else{
                    return false;
                }
            }
        }
        return true;
    }

    public function saveStaffData()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        /*$requestData = json_decode('{"login_user_id":"1","openemis_no":"152227233311111222","first_name":"AMARTAA","middle_name":"","third_name":"","last_name":"Fenicott","preferred_name":"","gender_id":"1","date_of_birth":"2011-01-01","identity_number":"1231122","nationality_id":"2","username":"kkk111","password":"sdsd","postal_code":"12233","address":"sdsdsds","birthplace_area_id":"2","address_area_id":"2","identity_type_id":"160","academic_period_id":"30","start_date":"01-01-2021","end_date":"31-12-2021","staff_type_id":"1","institution_position_id":1,"fte":1,"custom":[{"staff_custom_field_id":17,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":27,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":29,"text_value":"test.jpg","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":28,"text_value":"","number_value":2,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":31,"text_value":"","number_value":3,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":26,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":31,"text_value":"","number_value":4,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":8,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":9,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":30,"text_value":"{\"latitude\":\"11.1\",\"longitude\":\"2.22\"}","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"staff_custom_field_id":18,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"}]}', true);*/
        if(!empty($requestData)){
            $openemisNo = (array_key_exists('openemis_no', $requestData))? $requestData['openemis_no']: null;
            $firstName = (array_key_exists('first_name', $requestData))? $requestData['first_name']: null;
            $middleName = (array_key_exists('middle_name', $requestData))? $requestData['middle_name']: null;
            $thirdName = (array_key_exists('third_name', $requestData))? $requestData['third_name']: null;
            $lastName = (array_key_exists('last_name', $requestData))? $requestData['last_name']: null;
            $preferredName = (array_key_exists('preferred_name', $requestData))? $requestData['preferred_name']: null;
            $genderId = (array_key_exists('gender_id', $requestData))? $requestData['gender_id']: null;
            $dateOfBirth = (array_key_exists('date_of_birth', $requestData))? date('Y-m-d', strtotime($requestData['date_of_birth'])): null;
            $identityNumber = (array_key_exists('identity_number', $requestData))? $requestData['identity_number']: null;
            $nationalityId = (array_key_exists('nationality_id', $requestData))? $requestData['nationality_id']: null;
            $nationalityName = (array_key_exists('nationality_name', $requestData))? $requestData['nationality_name']: null;
            $username = (array_key_exists('username', $requestData))? $requestData['username']: null;
            $password = (array_key_exists('password', $requestData))? password_hash($requestData['password'],  PASSWORD_DEFAULT) : null;
            $address  = (array_key_exists('address', $requestData))? $requestData['address'] : null;
            $postalCode = (array_key_exists('postal_code', $requestData))? $requestData['postal_code'] : null;
            $birthplaceAreaId = (array_key_exists('birthplace_area_id', $requestData))? $requestData['birthplace_area_id'] : null;
            $addressAreaId = (array_key_exists('address_area_id', $requestData))? $requestData['address_area_id'] : null;
            $identityTypeId = (array_key_exists('identity_type_id', $requestData))? $requestData['identity_type_id'] : null;
            $identityTypeName = (array_key_exists('identity_type_name', $requestData))? $requestData['identity_type_name'] : null;
            
            $institutionPositionId = (array_key_exists('institution_position_id', $requestData))? $requestData['institution_position_id'] : null;
            $fte = (array_key_exists('fte', $requestData))? $requestData['fte'] : null;
            $startDate = (array_key_exists('start_date', $requestData))? date('Y-m-d', strtotime($requestData['start_date'])) : NULL;
            $endDate = (array_key_exists('end_date', $requestData) && !empty($requestData['end_date']))? date('Y-m-d', strtotime($requestData['end_date'])) : '';
            
            $is_homeroom = (array_key_exists('is_homeroom', $requestData))? $requestData['is_homeroom'] : 0; //POCOR-5070
            //$institutionId = $this->request->session()->read('Institution.Institutions.id');
            $institutionId = (array_key_exists('institution_id', $requestData))? $requestData['institution_id'] : null;
            $staffTypeId = (array_key_exists('staff_type_id', $requestData))? $requestData['staff_type_id'] : null;
            $userId = !empty($this->request->session()->read('Auth.User.id')) ? $this->request->session()->read('Auth.User.id') : 1;
            $photoContent = (array_key_exists('photo_base_64', $requestData))? $requestData['photo_base_64'] : null;
            $photoName = (array_key_exists('photo_name', $requestData))? $requestData['photo_name'] : null;
            $custom = (array_key_exists('custom', $requestData))? $requestData['custom'] : "";
            $shiftIds = (array_key_exists('shift_ids', $requestData))? $requestData['shift_ids'] : "";

            //when staff transfer in other institution starts
            $isSameSchool = (array_key_exists('is_same_school', $requestData))? $requestData['is_same_school'] : 0;
            $isDiffSchool = (array_key_exists('is_diff_school', $requestData))? $requestData['is_diff_school'] : 0;
            $staffId = (array_key_exists('staff_id', $requestData))? $requestData['staff_id'] : 0;
            $previousInstitutionId = (array_key_exists('previous_institution_id', $requestData))? $requestData['previous_institution_id'] : 0;
            $comment = (array_key_exists('comment', $requestData))? $requestData['comment'] : '';
            $staff_position_grade_id = (array_key_exists('staff_position_grade_id', $requestData))? $requestData['staff_position_grade_id'] : '';//POCOR-7238
            //when staff transfer in other institution end
            
            //get academic period data
            $academicPeriods = TableRegistry::get('academic_periods');
            $periods = $academicPeriods->find()
                           ->where(['current'=> 1])
                           ->first();
            $startYear = $endYear = '';
            if(!empty($periods)){
                $startYear = $periods->start_year;
                if($endDate == NULL || $endDate == ''){
                    $endYear = NULL;
                }else{
                    $endYear = $periods->end_year;
                }
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
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $statuses = $StaffStatuses->findCodeList();
            //get nationality data
            $nationalities = '';
            if(!empty($nationalityName)){
                $nationalitiesTbl = TableRegistry::get('nationalities');
                $nationalities = $nationalitiesTbl->find()
                    ->where([
                        $nationalitiesTbl->aliasField('name') => $nationalityName,
                    ])
                    ->first();
                if(empty($nationalities)){
                    $orderNationalities = $nationalitiesTbl->find()
                        ->order([$nationalitiesTbl->aliasField('order DESC')])
                        ->first();
                    
                    $entityNationality = [
                        'name' => $nationalityName,
                        'order' => !empty($orderNationalities->order) ? $orderNationalities->order + 1 : 0,
                        'visible' => 1,
                        'editable' => 1,
                        'identity_type_id' => null,
                        'default' => 0,
                        'international_code' => '',
                        'national_code' => '',
                        'external_validation' => 0,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in nationalities table if doesn't exist in table
                    $entityNationalityData = $nationalitiesTbl->newEntity($entityNationality);
                    $NationalitiesResult = $nationalitiesTbl->save($entityNationalityData);
                    if($NationalitiesResult){
                        $nationalities->id = $NationalitiesResult->id;
                    } 
                }
            }
            if($isSameSchool == 1){
                $SecurityUsers = TableRegistry::get('security_users');
                $CheckStaffExist = $SecurityUsers->find()
                                ->where([
                                    $SecurityUsers->aliasField('openemis_no') => $openemisNo
                                ])->first();

                $SecurityUsers = TableRegistry::get('security_users');
                if(!empty($CheckStaffExist)){
                    $existStaffId = $CheckStaffExist->id;
                    $entityData = [
                        'id'=> $existStaffId,
                        'openemis_no' => $openemisNo,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'third_name' => $thirdName,
                        'last_name' => $lastName,
                        'preferred_name' => $preferredName,
                        'gender_id' => $genderId,
                        'date_of_birth' => $dateOfBirth,
                        'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                        'preferred_language' => $pref_lang->value,
                        'username' => $username,
                        'password' => $password,
                        'address' => $address,
                        'address_area_id' => $addressAreaId,
                        'birthplace_area_id' => $birthplaceAreaId,
                        'postal_code' => $postalCode,
                        'photo_name' => $photoName,
                        'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                        'is_staff' => 1,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s'),
                    ];

                    //save in security_users table
                    $entity = $SecurityUsers->newEntity($entityData);
                    try{
                        $SecurityUserResult = $SecurityUsers->save($entity);
                        unset($entity);
                    }catch (Exception $e) {
                        return null;
                    }

                    if($SecurityUserResult){
                        $user_record_id=$SecurityUserResult->id;
                        if(!empty($nationalityId) || !empty($nationalityName)){
                            if(!empty($nationalities->id)){
                                $UserNationalities = TableRegistry::get('user_nationalities');
                                $checkexistingNationalities = $UserNationalities->find()
                                    ->where([
                                        $UserNationalities->aliasField('nationality_id') => $nationalities->id,
                                        $UserNationalities->aliasField('security_user_id') => $user_record_id,
                                    ])->first();
                                if(empty($checkexistingNationalities)){
                                    $primaryKey = $UserNationalities->primaryKey();
                                    $hashString = [];
                                    foreach ($primaryKey as $key) {
                                        if($key == 'nationality_id'){
                                            $hashString[] = $nationalities->id;
                                        }
                                        if($key == 'security_user_id'){
                                            $hashString[] = $user_record_id;
                                        }
                                    }
                         
                                    $entityNationalData = [
                                        'id' => Security::hash(implode(',', $hashString), 'sha256'),
                                        'preferred' => 1,
                                        'nationality_id' => $nationalities->id,
                                        'security_user_id' => $user_record_id,
                                        'created_user_id' => $userId,
                                        'created' => date('Y-m-d H:i:s')
                                    ];
                                    //save in user_nationalities table
                                    $entityNationalData = $UserNationalities->newEntity($entityNationalData);
                                    $UserNationalitiesResult = $UserNationalities->save($entityNationalData);  
                                }
                            }
                        }

                        if(!empty($nationalities->id) && !empty($identityTypeId) && !empty($identityNumber)){
                            $identityTypesTbl = TableRegistry::get('identity_types');
                            $identityTypes = $identityTypesTbl->find()
                                ->where([
                                    $identityTypesTbl->aliasField('name') => $identityTypeName,
                                ])
                                ->first();
                            if(!empty($identityTypes)){
                                $UserIdentities = TableRegistry::get('user_identities');
                                $checkexistingIdentities = $UserIdentities->find()
                                    ->where([
                                        $UserIdentities->aliasField('nationality_id') => $nationalities->id,
                                        $UserIdentities->aliasField('identity_type_id') => $identityTypeId,
                                        $UserIdentities->aliasField('number') => $identityNumber,
                                    ])->first();
                                if(empty($checkexistingIdentities)){
                                    $UserIdentities = TableRegistry::get('user_identities');
                                    $entityIdentitiesData = [
                                        'identity_type_id' => $identityTypes->id,
                                        'number' => $identityNumber,
                                        'nationality_id' => $nationalities->id,
                                        'security_user_id' => $user_record_id,
                                        'created_user_id' => $userId,
                                        'created' => date('Y-m-d H:i:s')
                                    ];
                                    //save in user_identities table
                                    $entityIdentitiesData = $UserIdentities->newEntity($entityIdentitiesData);
                                    $UserIdentitiesResult = $UserIdentities->save($entityIdentitiesData);
                                }
                            }
                        }
                    }
                }

                if(!empty($institutionId)){
                    //get id from `institution_positions` table
                    $InstitutionPositions = TableRegistry::get('institution_positions');
                    $InstitutionPositionsTbl = $InstitutionPositions->find()
                                            ->where([
                                                $InstitutionPositions->aliasField('id') => $institutionPositionId,
                                            ])
                                            ->first();

                    //POCOR-7188[START]
                    $staffPositionTitles = TableRegistry::get('staff_position_titles');
                    $staffPositionTitlesTbl = $staffPositionTitles->find()
                                            ->where([
                                                $staffPositionTitles->aliasField('id') => $InstitutionPositionsTbl->staff_position_title_id,
                                            ])
                                            ->first();
                    //POCOR-7188[END]

                    $SecurityGroupUsers = TableRegistry::get('security_group_users');
                    if(!empty($InstitutionPositionsTbl)){
                        $SecurityRoles = TableRegistry::get('security_roles');
                        $SecurityRolesTbl = $SecurityRoles->find()
                                                        ->where([
                                                            $SecurityRoles->aliasField('id') => $staffPositionTitlesTbl->security_role_id
                                                        ])->first();
                        if($is_homeroom == 1){
                            $roleArr = ['HOMEROOM_TEACHER', $SecurityRolesTbl->code];
                        }else{
                            $roleArr = [$SecurityRolesTbl->code];
                        }
                        $SecurityRolesTbl = $SecurityRoles->find()
                                                        ->where([
                                                            $SecurityRoles->aliasField('code IN') => $roleArr
                                                        ])->toArray();
                        //POCOR-7182
                        $institutionsTbl = TableRegistry::get('institutions');
                        $institutionsSecurityGroupId = $institutionsTbl->find()
                        ->where([$institutionsTbl->aliasField('id') => $institutionId])
                        ->first();  
                        //POCOR-7182                         
                        if(!empty($SecurityRolesTbl)){
                            $SecurityGroupUsersTbl = TableRegistry::get('security_group_users');
                            $SecurityGroupInstitutions = TableRegistry::get('security_group_institutions');//POCOR-7309
                            foreach ($SecurityRolesTbl as $rolekey => $roleval) {
                                //POCOR-7238 starts
                                $countSecurityGroupUsers = $SecurityGroupUsersTbl->find()
                                                            ->LeftJoin(//POCOR-7309
                                                            [$SecurityGroupInstitutions->alias() => $SecurityGroupInstitutions->table()],
                                                            [
                                                                $SecurityGroupInstitutions->aliasField('security_group_id = ') . $SecurityGroupUsers->aliasField('security_group_id'),
                                                                $SecurityGroupInstitutions->aliasField('institution_id = ') . $institutionsSecurityGroupId->security_group_id,

                                                            ])//POCOR-7309
                                                            ->where([
                                                                $SecurityGroupInstitutions->aliasField('security_group_id') => $institutionsSecurityGroupId->security_group_id,
                                                                $SecurityGroupUsersTbl->aliasField('security_user_id') => $staffId,
                                                                $SecurityGroupUsersTbl->aliasField('security_role_id') => $roleval->id
                                                            ])->count();
                                if(empty($countSecurityGroupUsers)){
                                    $entityGroupData = [
                                        'id' => Text::uuid(),
                                        'security_group_id' =>$institutionsSecurityGroupId->security_group_id, // $institutionId POCOR-7182
                                        'security_user_id' => $staffId,
                                        'security_role_id' => $roleval->id, //// initial was $roleval->id then changed to $staffPositionTitlesTbl->security_role_id POCOR-7188[END]
                                        'created_user_id' => $userId,
                                        'created' => date('Y-m-d H:i:s')
                                    ];
                                    //save in security_group_users table
                                    $entityGroupData = $SecurityGroupUsers->newEntity($entityGroupData);
                                    $entityGroupResult = $SecurityGroupUsers->save($entityGroupData);
                                    unset($entityGroupResult);
                                    unset($entityGroupData);
                                }//POCOR-7238 ends
                            }
                        }
                    }                        
                    //get id from `security_group_users` table
                    $SecurityRoles = TableRegistry::get('security_roles');//POCOR-7238
                    $SecurityGroupInstitutions = TableRegistry::get('security_group_institutions');//POCOR-7309
                    $SecurityGroupUsersTbl = $SecurityGroupUsers->find()
                                        ->InnerJoin(//POCOR-7238
                                        [$SecurityRoles->alias() => $SecurityRoles->table()],
                                        [
                                            $SecurityRoles->aliasField('id = ') . $SecurityGroupUsers->aliasField('security_role_id')
                                        ])//POCOR-7238
                                        ->LeftJoin(//POCOR-7309
                                        [$SecurityGroupInstitutions->alias() => $SecurityGroupInstitutions->table()],
                                        [
                                            $SecurityGroupInstitutions->aliasField('security_group_id = ') . $SecurityGroupUsers->aliasField('security_group_id'),
                                            $SecurityGroupInstitutions->aliasField('institution_id = ') . $institutionId,

                                        ])//POCOR-7309
                                        ->where([
                                            $SecurityGroupInstitutions->aliasField('institution_id') => $institutionId,
                                            $SecurityGroupUsers->aliasField('security_user_id') => $staffId,
                                            $SecurityGroupUsers->aliasField('security_role_id') => $staffPositionTitlesTbl->security_role_id,//POCOR-7238 
                                            $SecurityRoles->aliasField('code !=') => 'HOMEROOM_TEACHER'//POCOR-7238
                                        ])->first();
                    $InstitutionStaffs = TableRegistry::get('institution_staff');
                    $entityStaffsData = [
                        'FTE' => $fte,
                        'start_date' => $startDate,
                        'start_year' => $startYear,
                        'end_date' => $endDate,
                        'end_year' => $endYear,
                        'staff_id' => $staffId,
                        'staff_type_id' => $staffTypeId,
                        'staff_status_id' => $statuses['ASSIGNED'],
                        'is_homeroom' => $is_homeroom, //POCOR-5070
                        'institution_id' => $institutionId,
                        'institution_position_id' => $institutionPositionId,
                        'security_group_user_id' => (!empty($SecurityGroupUsersTbl))? $SecurityGroupUsersTbl->id : null,
                        'staff_position_grade_id' => $staff_position_grade_id,//POCOR-7238 
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in institution_staff table
                    $entityStaffsData = $InstitutionStaffs->newEntity($entityStaffsData);
                    $InstitutionStaffsResult = $InstitutionStaffs->save($entityStaffsData);
                }
                if(!empty($shiftIds)){
                    $InstitutionStaffShifts = TableRegistry::get('institution_staff_shifts');
                    foreach ($shiftIds as $shkey => $shval) {
                        $entityShiftData = [
                            'staff_id' => $staffId,
                            'shift_id' => $shval,
                            'created' => date('Y-m-d H:i:s')
                        ];
                        //save in institution_staff_shifts table
                        $entityShiftData = $InstitutionStaffShifts->newEntity($entityShiftData);
                        $staffShiftResult = $InstitutionStaffShifts->save($entityShiftData);
                        unset($staffShiftResult);
                        unset($entityShiftData);
                    }
                }

                if(!empty($custom)){
                    //if staff custom field values already exist in `staff_custom_field_values` table the delete the old values and insert the new ones.
                    $staffCustomFieldValues =  TableRegistry::get('staff_custom_field_values');
                    $StaffCustomFieldValuesCount = $staffCustomFieldValues
                                                        ->find()
                                                        ->where([$staffCustomFieldValues->aliasField('staff_id') => $staffId])
                                                        ->count();
                    if($StaffCustomFieldValuesCount > 0){
                        $staffCustomFieldValues->deleteAll(['staff_id' => $staffId]);
                    }
                    foreach ($custom as $skey => $sval) {
                        $entityCustomData = [
                            'id' => Text::uuid(),
                            'text_value' => $sval['text_value'],
                            'number_value' => $sval['number_value'],
                            'decimal_value' => $sval['decimal_value'],
                            'textarea_value' => $sval['textarea_value'],
                            'date_value' => $sval['date_value'],
                            'time_value' => $sval['time_value'],
                            'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                            'staff_custom_field_id' => $sval['staff_custom_field_id'],
                            'staff_id' => $staffId,
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s')
                        ];
                        //save in staff_custom_field_values table
                        $entityCustomData = $staffCustomFieldValues->newEntity($entityCustomData);
                        $staffCustomFieldsResult = $staffCustomFieldValues->save($entityCustomData);
                        unset($staffCustomFieldsResult);
                        unset($entityCustomData);
                    }
                }

                try{
                    die('success');
                }catch (Exception $e) {
                    return null;
                }
            }else if($isDiffSchool == 1){
                $workflows = TableRegistry::get('workflows');
                $workflowSteps = TableRegistry::get('workflow_steps');
                $workflowResults = $workflows->find()
                            ->select(['workflowSteps_id'=>$workflowSteps->aliasField('id')])
                            ->LeftJoin([$workflowSteps->alias() => $workflowSteps->table()], [
                                $workflowSteps->aliasField('workflow_id =') . $workflows->aliasField('id'),
                                $workflowSteps->aliasField('name')=> 'Open'
                            ])
                            ->where([
                                $workflows->aliasField('name') => 'Staff Transfer - Receiving'
                            ])
                            ->first();

                $institutionStaffTransfers = TableRegistry::get('institution_staff_transfers');
                $entityTransferData = [
                    'staff_id' => $staffId,
                    'new_institution_id' => $institutionId,
                    'previous_institution_id' => $previousInstitutionId,
                    'status_id' => $workflowResults->workflowSteps_id,
                    'assignee_id' => $this->Auth->user('id'), //POCOR-7080
                    'new_institution_position_id' => $institutionPositionId,
                    'new_staff_type_id' => $staffTypeId,
                    'new_FTE' => $fte,
                    'new_start_date' => $startDate,
                    'new_end_date' => $endDate,
                    'previous_institution_staff_id' => '',
                    'previous_staff_type_id' => '',
                    'previous_FTE' => '',
                    'previous_end_date' => '',
                    'previous_effective_date' => '',
                    'comment' => $comment,
                    'transfer_type' => 0,
                    'all_visible' => 0,
                    'modified_user_id' => '',
                    'modified' => '',
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s'),
                ];
                //save in `institution_staff_transfers` table
                $entity = $institutionStaffTransfers->newEntity($entityTransferData);
                try{
                    $StaffTransfersResult = $institutionStaffTransfers->save($entity);
                    unset($entity);
                    die('success');
                }catch (Exception $e) {
                    return null;
                }
            }else{
                $SecurityUsers = TableRegistry::get('security_users');
                $CheckStaffExist = $SecurityUsers->find()
                                ->where([
                                    $SecurityUsers->aliasField('openemis_no') => $openemisNo
                                ])->first();

                $SecurityUsers = TableRegistry::get('security_users');
                if(!empty($CheckStaffExist)){
                    $existStaffId = $CheckStaffExist->id;
                    $entityData = [
                        'id'=> $existStaffId,
                        'openemis_no' => $openemisNo,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'third_name' => $thirdName,
                        'last_name' => $lastName,
                        'preferred_name' => $preferredName,
                        'gender_id' => $genderId,
                        'date_of_birth' => $dateOfBirth,
                        'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                        'preferred_language' => $pref_lang->value,
                        'username' => $username,
                        'password' => $password,
                        'address' => $address,
                        'address_area_id' => $addressAreaId,
                        'birthplace_area_id' => $birthplaceAreaId,
                        'postal_code' => $postalCode,
                        'photo_name' => $photoName,
                        'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                        'is_staff' => 1,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s'),
                    ];
                }else{
                    $entityData = [
                        'openemis_no' => $openemisNo,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'third_name' => $thirdName,
                        'last_name' => $lastName,
                        'preferred_name' => $preferredName,
                        'gender_id' => $genderId,
                        'date_of_birth' => $dateOfBirth,
                        'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                        'preferred_language' => $pref_lang->value,
                        'username' => $username,
                        'password' => $password,
                        'address' => $address,
                        'address_area_id' => $addressAreaId,
                        'birthplace_area_id' => $birthplaceAreaId,
                        'postal_code' => $postalCode,
                        'photo_name' => $photoName,
                        'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                        'is_staff' => 1,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s'),
                    ];
                }
                //save in security_users table
                $entity = $SecurityUsers->newEntity($entityData);
                try{
                    $SecurityUserResult = $SecurityUsers->save($entity);
                    unset($entity);
                }catch (Exception $e) {
                    return null;
                }
                if($SecurityUserResult){
                    $user_record_id=$SecurityUserResult->id;
                    if(!empty($nationalityId) || !empty($nationalityName)){
                        if(!empty($nationalities->id)){
                            $UserNationalities = TableRegistry::get('user_nationalities');
                            $checkexistingNationalities = $UserNationalities->find()
                                ->where([
                                    $UserNationalities->aliasField('nationality_id') => $nationalities->id,
                                    $UserNationalities->aliasField('security_user_id') => $user_record_id,
                                ])->first();
                            if(empty($checkexistingNationalities)){
                                $primaryKey = $UserNationalities->primaryKey();
                                $hashString = [];
                                foreach ($primaryKey as $key) {
                                    if($key == 'nationality_id'){
                                        $hashString[] = $nationalities->id;
                                    }
                                    if($key == 'security_user_id'){
                                        $hashString[] = $user_record_id;
                                    }
                                }
                     
                                $entityNationalData = [
                                    'id' => Security::hash(implode(',', $hashString), 'sha256'),
                                    'preferred' => 1,
                                    'nationality_id' => $nationalities->id,
                                    'security_user_id' => $user_record_id,
                                    'created_user_id' => $userId,
                                    'created' => date('Y-m-d H:i:s')
                                ];
                                //save in user_nationalities table
                                $entityNationalData = $UserNationalities->newEntity($entityNationalData);
                                $UserNationalitiesResult = $UserNationalities->save($entityNationalData);      
                            }
                        }
                    }

                    if(!empty($nationalities->id) && !empty($identityTypeId) && !empty($identityNumber)){
                        $identityTypesTbl = TableRegistry::get('identity_types');
                        $identityTypes = $identityTypesTbl->find()
                            ->where([
                                $identityTypesTbl->aliasField('name') => $identityTypeName,
                            ])
                            ->first();
                        if(!empty($identityTypes)){
                            $UserIdentities = TableRegistry::get('user_identities');
                            $checkexistingIdentities = $UserIdentities->find()
                                ->where([
                                    $UserIdentities->aliasField('nationality_id') => $nationalities->id,
                                    $UserIdentities->aliasField('identity_type_id') => $identityTypeId,
                                    $UserIdentities->aliasField('number') => $identityNumber,
                                ])->first();
                            if(empty($checkexistingIdentities)){
                                $UserIdentities = TableRegistry::get('user_identities');
                                $entityIdentitiesData = [
                                    'identity_type_id' => $identityTypes->id,
                                    'number' => $identityNumber,
                                    'nationality_id' => $nationalities->id,
                                    'security_user_id' => $user_record_id,
                                    'created_user_id' => $userId,
                                    'created' => date('Y-m-d H:i:s')
                                ];
                                //save in user_identities table
                                $entityIdentitiesData = $UserIdentities->newEntity($entityIdentitiesData);
                                $UserIdentitiesResult = $UserIdentities->save($entityIdentitiesData);
                            }
                        }
                    }

                    if(!empty($institutionId)){
                        //get id from `institution_positions` table
                        $InstitutionPositions = TableRegistry::get('institution_positions');
                        $InstitutionPositionsTbl = $InstitutionPositions->find()
                                                ->where([
                                                    $InstitutionPositions->aliasField('id') => $institutionPositionId,
                                                ])
                                                ->first();

                         //POCOR-7188[START]
                        $staffPositionTitles = TableRegistry::get('staff_position_titles');
                        $staffPositionTitlesTbl = $staffPositionTitles->find()
                                                ->where([
                                                    $staffPositionTitles->aliasField('id') => $InstitutionPositionsTbl->staff_position_title_id,
                                                ])
                                                ->first();
                        //POCOR-7188[END]
                        $staffId = $user_record_id; //POCOR-7238
                        $SecurityGroupUsers = TableRegistry::get('security_group_users');
                        if(!empty($InstitutionPositionsTbl)){
                            $SecurityRoles = TableRegistry::get('security_roles');
                            $SecurityRolesTbl = $SecurityRoles->find()
                                                            ->where([
                                                                $SecurityRoles->aliasField('id') => $staffPositionTitlesTbl->security_role_id
                                                            ])->first();
                            if($is_homeroom == 1){
                                $roleArr = ['HOMEROOM_TEACHER', $SecurityRolesTbl->code];
                            }else{
                                $roleArr = [$SecurityRolesTbl->code];
                            }
                            $SecurityRolesTbl = $SecurityRoles->find()
                                                            ->where([
                                                                $SecurityRoles->aliasField('code IN') => $roleArr
                                                            ])->toArray();
                            //POCOR-7182
                            $institutionsTbl = TableRegistry::get('institutions');
                            $institutionsSecurityGroupId = $institutionsTbl->find()
                            ->where([$institutionsTbl->aliasField('id') => $institutionId])
                            ->first();  
                            //POCOR-7182                         
                            if(!empty($SecurityRolesTbl)){
                                $SecurityGroupUsersTbl = TableRegistry::get('security_group_users');
                                $SecurityGroupInstitutions = TableRegistry::get('security_group_institutions');//POCOR-7309
                                foreach ($SecurityRolesTbl as $rolekey => $roleval) {
                                    //POCOR-7238 starts
                                    $countSecurityGroupUsers = $SecurityGroupUsersTbl->find()
                                                                ->LeftJoin(//POCOR-7309
                                                                [$SecurityGroupInstitutions->alias() => $SecurityGroupInstitutions->table()],
                                                                [
                                                                    $SecurityGroupInstitutions->aliasField('security_group_id = ') . $SecurityGroupUsers->aliasField('security_group_id'),
                                                                    $SecurityGroupInstitutions->aliasField('institution_id = ') . $institutionsSecurityGroupId->security_group_id,

                                                                ])//POCOR-7309
                                                                ->where([
                                                                    $SecurityGroupInstitutions->aliasField('security_group_id') => $institutionsSecurityGroupId->security_group_id,
                                                                    $SecurityGroupUsersTbl->aliasField('security_user_id') => $staffId,
                                                                    $SecurityGroupUsersTbl->aliasField('security_role_id') => $roleval->id
                                                                ])->count();
                                    if(empty($countSecurityGroupUsers)){
                                        $entityGroupData = [
                                            'id' => Text::uuid(),
                                            'security_group_id' =>$institutionsSecurityGroupId->security_group_id, // $institutionId POCOR-7182
                                            'security_user_id' => $staffId,
                                            'security_role_id' => $roleval->id, //// initial was $roleval->id then changed to $staffPositionTitlesTbl->security_role_id POCOR-7188[END]
                                            'created_user_id' => $userId,
                                            'created' => date('Y-m-d H:i:s')
                                        ];
                                        //save in security_group_users table
                                        $entityGroupData = $SecurityGroupUsers->newEntity($entityGroupData);
                                        $entityGroupResult = $SecurityGroupUsers->save($entityGroupData);
                                        unset($entityGroupResult);
                                        unset($entityGroupData);
                                    }//POCOR-7238 ends
                                }
                            }
                        }                        
                        //get id from `security_group_users` table
                        $SecurityRoles = TableRegistry::get('security_roles');//POCOR-7238
                        $SecurityGroupInstitutions = TableRegistry::get('security_group_institutions');//POCOR-7309
                        $SecurityGroupUsersTbl = $SecurityGroupUsers->find()
                                            ->InnerJoin(//POCOR-7238
                                            [$SecurityRoles->alias() => $SecurityRoles->table()],
                                            [
                                                $SecurityRoles->aliasField('id = ') . $SecurityGroupUsers->aliasField('security_role_id')
                                            ])//POCOR-7238
                                            ->LeftJoin(//POCOR-7309
                                            [$SecurityGroupInstitutions->alias() => $SecurityGroupInstitutions->table()],
                                            [
                                                $SecurityGroupInstitutions->aliasField('security_group_id = ') . $SecurityGroupUsers->aliasField('security_group_id'),
                                                $SecurityGroupInstitutions->aliasField('institution_id = ') . $institutionId,

                                            ])//POCOR-7309
                                            ->where([
                                                $SecurityGroupInstitutions->aliasField('institution_id') => $institutionId,
                                                $SecurityGroupUsers->aliasField('security_user_id') => $staffId,
                                                $SecurityGroupUsers->aliasField('security_role_id') => $staffPositionTitlesTbl->security_role_id,//POCOR-7238 
                                                $SecurityRoles->aliasField('code !=') => 'HOMEROOM_TEACHER'//POCOR-7238
                                            ])->first();
                                                
                        $InstitutionStaffs = TableRegistry::get('institution_staff');
                        $entityStaffsData = [
                            'FTE' => $fte,
                            'start_date' => $startDate,
                            'start_year' => $startYear,
                            'end_date' => $endDate,
                            'end_year' => $endYear,
                            'staff_id' => $staffId,
                            'staff_type_id' => $staffTypeId,
                            'staff_status_id' => $statuses['ASSIGNED'],
                            'is_homeroom' => $is_homeroom, //POCOR-5070
                            'institution_id' => $institutionId,
                            'institution_position_id' => $institutionPositionId,
                            'security_group_user_id' => (!empty($SecurityGroupUsersTbl))? $SecurityGroupUsersTbl->id : null,
                            'staff_position_grade_id' => $staff_position_grade_id,//POCOR-7238 
                            'created_user_id' => $userId,
                            'created' => date('Y-m-d H:i:s')
                        ];
                        //save in institution_staff table
                        $entityStaffsData = $InstitutionStaffs->newEntity($entityStaffsData);
                        $InstitutionStaffsResult = $InstitutionStaffs->save($entityStaffsData);
                    }
                    if(!empty($shiftIds)){
                        $InstitutionStaffShifts = TableRegistry::get('institution_staff_shifts');
                        foreach ($shiftIds as $shkey => $shval) {
                            $entityShiftData = [
                                'staff_id' => $user_record_id,
                                'shift_id' => $shval,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in institution_staff_shifts table
                            $entityShiftData = $InstitutionStaffShifts->newEntity($entityShiftData);
                            $staffShiftResult = $InstitutionStaffShifts->save($entityShiftData);
                            unset($staffShiftResult);
                            unset($entityShiftData);
                        }
                    }
                    if(!empty($custom)){
                        //if staff custom field values already exist in `staff_custom_field_values` table the delete the old values and insert the new ones.
                        $staffCustomFieldValues =  TableRegistry::get('staff_custom_field_values');
                        $StaffCustomFieldValuesCount = $staffCustomFieldValues
                                                            ->find()
                                                            ->where([$staffCustomFieldValues->aliasField('staff_id') => $user_record_id])
                                                            ->count();

                        if($StaffCustomFieldValuesCount > 0){
                            $staffCustomFieldValues->deleteAll(['staff_id' => $user_record_id]);
                        }

                        foreach ($custom as $skey => $sval) {
                            $entityCustomData = [
                                'id' => Text::uuid(),
                                'text_value' => $sval['text_value'],
                                'number_value' => $sval['number_value'],
                                'decimal_value' => $sval['decimal_value'],
                                'textarea_value' => $sval['textarea_value'],
                                'date_value' => $sval['date_value'],
                                'time_value' => $sval['time_value'],
                                'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                                'staff_custom_field_id' => $sval['staff_custom_field_id'],
                                'staff_id' => $user_record_id,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in staff_custom_field_values table
                            $entityCustomData = $staffCustomFieldValues->newEntity($entityCustomData);
                            $staffCustomFieldsResult = $staffCustomFieldValues->save($entityCustomData);
                            unset($staffCustomFieldsResult);
                            unset($entityCustomData);
                        }
                    }
                    try{
                        die('success');        
                    }catch(Exception $e){
                        return $e;
                    }
                }else{
                    return false;
                }
            }
        }
        return true;
    }

    public function saveGuardianData()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        /*$requestData = json_decode('{"guardian_relation_id":"1","student_id":"1161","login_user_id":"1","openemis_no":"152227434344","first_name":"GuardianPita","middle_name":"","third_name":"","last_name":"GuardianPita","preferred_name":"","gender_id":"1","date_of_birth":"1989-01-01","identity_number":"555555","nationality_id":"2","username":"pita123","password":"pita123","postal_code":"12233","address":"sdsdsds","birthplace_area_id":"2","address_area_id":"2","identity_type_id":"160",}', true);*/
        if(!empty($requestData)){
            $studentOpenemisNo = (array_key_exists('student_openemis_no', $requestData))? $requestData['student_openemis_no']: null;
            $openemisNo = (array_key_exists('openemis_no', $requestData))? $requestData['openemis_no']: null;
            $firstName = (array_key_exists('first_name', $requestData))? $requestData['first_name']: null;
            $middleName = (array_key_exists('middle_name', $requestData))? $requestData['middle_name']: null;
            $thirdName = (array_key_exists('third_name', $requestData))? $requestData['third_name']: null;
            $lastName = (array_key_exists('last_name', $requestData))? $requestData['last_name']: null;
            $preferredName = (array_key_exists('preferred_name', $requestData))? $requestData['preferred_name']: null;
            $genderId = (array_key_exists('gender_id', $requestData))? $requestData['gender_id']: null;
            $dateOfBirth = (array_key_exists('date_of_birth', $requestData))? date('Y-m-d', strtotime($requestData['date_of_birth'])): null;
            $identityNumber = (array_key_exists('identity_number', $requestData))? $requestData['identity_number']: null;
            $nationalityId = (array_key_exists('nationality_id', $requestData))? $requestData['nationality_id']: null;
            $nationalityName = (array_key_exists('nationality_name', $requestData))? $requestData['nationality_name']: null;
            $username = (array_key_exists('username', $requestData))? $requestData['username']: null;
            $password = (array_key_exists('password', $requestData))? password_hash($requestData['password'],  PASSWORD_DEFAULT) : null;
            $address  = (array_key_exists('address', $requestData))? $requestData['address'] : null;
            $postalCode = (array_key_exists('postal_code', $requestData))? $requestData['postal_code'] : null;
            $birthplaceAreaId = (array_key_exists('birthplace_area_id', $requestData))? $requestData['birthplace_area_id'] : null;
            $addressAreaId = (array_key_exists('address_area_id', $requestData))? $requestData['address_area_id'] : null;
            $identityTypeId = (array_key_exists('identity_type_id', $requestData))? $requestData['identity_type_id'] : null;
            $identityTypeName = (array_key_exists('identity_type_name', $requestData))? $requestData['identity_type_name'] : null;
            
            $guardianRelationId = (array_key_exists('guardian_relation_id', $requestData))? $requestData['guardian_relation_id'] : null;
            $studentId = (array_key_exists('student_id', $requestData))? $requestData['student_id'] : null;
            $photoContent = (array_key_exists('photo_base_64', $requestData))? $requestData['photo_base_64'] : null;
            $photoName = (array_key_exists('photo_name', $requestData))? $requestData['photo_name'] : null;
            
            $userId = !empty($this->request->session()->read('Auth.User.id')) ? $this->request->session()->read('Auth.User.id') : 1;
            $contactType = (array_key_exists('contact_type', $requestData))? $requestData['contact_type'] : null;
            $contactValue = (array_key_exists('contact_value', $requestData))? $requestData['contact_value'] : null;
            
            //get prefered language
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $pref_lang = $ConfigItems->find()
                    ->where([
                        $ConfigItems->aliasField('code') => 'language',
                        $ConfigItems->aliasField('type') => 'System'
                    ])
                    ->first();
            //get nationality data
            $nationalities = '';
            if(!empty($nationalityName)){
                $nationalitiesTbl = TableRegistry::get('nationalities');
                $nationalities = $nationalitiesTbl->find()
                    ->where([
                        $nationalitiesTbl->aliasField('name') => $nationalityName,
                    ])->first();
                if(empty($nationalities)){
                    $orderNationalities = $nationalitiesTbl->find()
                        ->order([$nationalitiesTbl->aliasField('order DESC')])
                        ->first();
                    
                    $entityNationality = [
                        'name' => $nationalityName,
                        'order' => !empty($orderNationalities->order) ? $orderNationalities->order + 1 : 0,
                        'visible' => 1,
                        'editable' => 1,
                        'identity_type_id' => null,
                        'default' => 0,
                        'international_code' => '',
                        'national_code' => '',
                        'external_validation' => 0,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in nationalities table if doesn't exist in table
                    $entityNationalityData = $nationalitiesTbl->newEntity($entityNationality);
                    $NationalitiesResult = $nationalitiesTbl->save($entityNationalityData);
                    if($NationalitiesResult){
                        $nationalities->id = $NationalitiesResult->id;
                    } 
                }
            }

            $SecurityUsers = TableRegistry::get('security_users');
            $CheckGaurdianExist = $SecurityUsers->find()
                            ->where([
                                $SecurityUsers->aliasField('openemis_no') => $openemisNo
                            ])->first();

            $SecurityUsers = TableRegistry::get('security_users');
            if(!empty($CheckGaurdianExist)){
                $existGaurdianId = $CheckGaurdianExist->id;
                $entityData = [
                    'id' => !empty($existGaurdianId) ? $existGaurdianId : '',
                    'openemis_no' => $openemisNo,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'third_name' => $thirdName,
                    'last_name' => $lastName,
                    'preferred_name' => $preferredName,
                    'gender_id' => $genderId,
                    'date_of_birth' => $dateOfBirth,
                    'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                    'preferred_language' => $pref_lang->value,
                    'username' => $username,
                    'password' => $password,
                    'address' => $address,
                    'address_area_id' => $addressAreaId,
                    'birthplace_area_id' => $birthplaceAreaId,
                    'postal_code' => $postalCode,
                    'photo_name' => $photoName,
                    'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                    'is_guardian' => 1,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s'),
                ];
            }else{
                $entityData = [
                    'openemis_no' => $openemisNo,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'third_name' => $thirdName,
                    'last_name' => $lastName,
                    'preferred_name' => $preferredName,
                    'gender_id' => $genderId,
                    'date_of_birth' => $dateOfBirth,
                    'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                    'preferred_language' => $pref_lang->value,
                    'username' => $username,
                    'password' => $password,
                    'address' => $address,
                    'address_area_id' => $addressAreaId,
                    'birthplace_area_id' => $birthplaceAreaId,
                    'postal_code' => $postalCode,
                    'photo_name' => $photoName,
                    'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                    'is_guardian' => 1,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s'),
                ];
            }
            //save in security_users table
            $entity = $SecurityUsers->newEntity($entityData);
            try{
                $SecurityUserResult = $SecurityUsers->save($entity);
                unset($entity);
            }catch (Exception $e) {
                return null;
            }
            if($SecurityUserResult){
                $user_record_id=$SecurityUserResult->id;
                if(!empty($nationalityId) || !empty($nationalityName)){
                    if(!empty($nationalities->id)){
                        $UserNationalities = TableRegistry::get('user_nationalities');
                        $checkexistingNationalities = $UserNationalities->find()
                            ->where([
                                $UserNationalities->aliasField('nationality_id') => $nationalities->id,
                                $UserNationalities->aliasField('security_user_id') => $user_record_id,
                            ])->first();
                        if(empty($checkexistingNationalities)){
                            $primaryKey = $UserNationalities->primaryKey();
                            $hashString = [];
                            foreach ($primaryKey as $key) {
                                if($key == 'nationality_id'){
                                    $hashString[] = $nationalities->id;
                                }
                                if($key == 'security_user_id'){
                                    $hashString[] = $user_record_id;
                                }
                            }
                 
                            $entityNationalData = [
                                'id' => Security::hash(implode(',', $hashString), 'sha256'),
                                'preferred' => 1,
                                'nationality_id' => $nationalities->id,
                                'security_user_id' => $user_record_id,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in user_nationalities table
                            $entityNationalData = $UserNationalities->newEntity($entityNationalData);
                            $UserNationalitiesResult = $UserNationalities->save($entityNationalData);
                        }
                    }
                }

                if(!empty($nationalities->id) && !empty($identityTypeId) && !empty($identityNumber)){
                    $identityTypesTbl = TableRegistry::get('identity_types');
                    $identityTypes = $identityTypesTbl->find()
                        ->where([
                            $identityTypesTbl->aliasField('name') => $identityTypeName,
                        ])
                        ->first();
                    if(!empty($identityTypes)){
                        $UserIdentities = TableRegistry::get('user_identities');
                        $checkexistingIdentities = $UserIdentities->find()
                            ->where([
                                $UserIdentities->aliasField('nationality_id') => $nationalities->id,
                                $UserIdentities->aliasField('identity_type_id') => $identityTypeId,
                                $UserIdentities->aliasField('number') => $identityNumber,
                            ])->first();
                        if(empty($checkexistingIdentities)){
                            $entityIdentitiesData = [
                                'identity_type_id' => $identityTypes->id,
                                'number' => $identityNumber,
                                'nationality_id' => $nationalities->id,
                                'security_user_id' => $user_record_id,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in user_identities table
                            $entityIdentitiesData = $UserIdentities->newEntity($entityIdentitiesData);
                            $UserIdentitiesResult = $UserIdentities->save($entityIdentitiesData);
                        }
                    }
                }                

                if(!empty($contactType) && !empty($contactValue)){
                    $UserContacts = TableRegistry::get('user_contacts');
                    $entityContactData = [
                        'contact_type_id' => $contactType,
                        'value' => $contactValue,
                        'preferred' => 1,
                        'security_user_id' => $user_record_id,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in user_identities table
                    $entityContactData = $UserContacts->newEntity($entityContactData);
                    $UserContactResult = $UserContacts->save($entityContactData);
                }
                //if relationship id and staudent openemis_no is not empty
                if(!empty($guardianRelationId) && !empty($studentOpenemisNo)){
                    $SecurityUsers = TableRegistry::get('security_users');
                    $StudentData = $SecurityUsers->find()
                                    ->where([
                                        $SecurityUsers->aliasField('openemis_no') => $studentOpenemisNo
                                    ])->first();
                    //get id from `security_group_users` table
                    $StudentGuardians = TableRegistry::get('student_guardians');
                    $entityGuardiansData = [
                        'id' => Text::uuid(),
                        'student_id' => $StudentData->id,
                        'guardian_id' => $user_record_id,
                        'guardian_relation_id' => $guardianRelationId,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];

                    $entityGuardiansData = $StudentGuardians->newEntity($entityGuardiansData);
                    if ($StudentGuardians->save($entityGuardiansData)) {
                        try{
                            die('success');        
                        }catch(Exception $e){
                            return $e;
                        }
                    }
                }
            }else{
                return false;
            }
        }
        return true;
    }

    public function saveDirectoryData()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        /*{"user_type": "1","openemis_no": "152227233311111222","first_name": "AMARTAA","middle_name": "","third_name": "","last_name": "Fenicott","preferred_name": "","gender_id": "1","date_of_birth": "2011-01-01","identity_number": "1231122","nationality_id": "2","username": "kkk111","password": "sdsd","postal_code": "12233","address": "sdsdsds","birthplace_area_id": "2","address_area_id": "2","identity_type_id": "160","contact_type": "1","contact_value": "254232","photo_name":"index.jpg","photo_content":"base64_encode(string)","custom": [{"custom_field_id": 17,"text_value": "yes","number_value": "","decimal_value": "","textarea_value": "","time_value": "","file": "", "created_user_id": 1,"created": "22-01-20 08:59:35" }, {"custom_field_id": 27,"text_value": "yes","number_value": "",   "decimal_value": "","textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 29,"text_value": "test.jpg","number_value": "","decimal_value": "","textarea_value": "","time_value": "","file": "base64_encode(string)","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 28,"text_value": "","number_value": 2,    "decimal_value": "","textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 31,"text_value": "","number_value": 3,"decimal_value": "","textarea_value": "","time_value": "", "file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 26,"text_value": "yes","number_value": "","decimal_value": "", "textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 31,"text_value": "", "number_value": 4,"decimal_value": "","textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 8,"text_value": "yes","number_value": "","decimal_value": "","textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 9,"text_value": "yes","number_value": "","decimal_value": "", "textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 30,"text_value": "{\"latitude\":\"11.1\",\"longitude\":\"2.22\"}","number_value": "","decimal_value": "","textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}, {"custom_field_id": 18,"text_value": "yes","number_value": "","decimal_value": "","textarea_value": "","time_value": "","file": "","created_user_id": 1,"created": "22-01-20 08:59:35"}]}*/
        if(!empty($requestData)){
            $userType = (array_key_exists('user_type', $requestData))? $requestData['user_type']: null;
            $openemisNo = (array_key_exists('openemis_no', $requestData))? $requestData['openemis_no']: null;
            $firstName = (array_key_exists('first_name', $requestData))? $requestData['first_name']: null;
            $middleName = (array_key_exists('middle_name', $requestData))? $requestData['middle_name']: null;
            $thirdName = (array_key_exists('third_name', $requestData))? $requestData['third_name']: null;
            $lastName = (array_key_exists('last_name', $requestData))? $requestData['last_name']: null;
            $preferredName = (array_key_exists('preferred_name', $requestData))? $requestData['preferred_name']: null;
            $genderId = (array_key_exists('gender_id', $requestData))? $requestData['gender_id']: null;
            $dateOfBirth = (array_key_exists('date_of_birth', $requestData))? date('Y-m-d', strtotime($requestData['date_of_birth'])): null;
            $identityTypeId = (array_key_exists('identity_type_id', $requestData))? $requestData['identity_type_id'] : null;
            $identityNumber = (array_key_exists('identity_number', $requestData))? $requestData['identity_number']: null;
            $identityTypeName = (array_key_exists('identity_type_name', $requestData))? $requestData['identity_type_name'] : null;
            $nationalityId = (array_key_exists('nationality_id', $requestData))? $requestData['nationality_id']: null;
            $nationalityName = (array_key_exists('nationality_name', $requestData))? $requestData['nationality_name']: null;
            $username = (array_key_exists('username', $requestData))? $requestData['username']: null;
            $password = (array_key_exists('password', $requestData))? password_hash($requestData['password'],  PASSWORD_DEFAULT) : null;
            $address  = (array_key_exists('address', $requestData))? $requestData['address'] : null;
            $postalCode = (array_key_exists('postal_code', $requestData))? $requestData['postal_code'] : null;
            $birthplaceAreaId = (array_key_exists('birthplace_area_id', $requestData))? $requestData['birthplace_area_id'] : null;
            $addressAreaId = (array_key_exists('address_area_id', $requestData))? $requestData['address_area_id'] : null;
            $custom = (array_key_exists('custom', $requestData))? $requestData['custom'] : "";
            $photoContent = (array_key_exists('photo_base_64', $requestData))? $requestData['photo_base_64'] : null;
            $photoName = (array_key_exists('photo_name', $requestData))? $requestData['photo_name'] : null;
            $contactType = (array_key_exists('contact_type', $requestData))? $requestData['contact_type'] : null;
            $contactValue = (array_key_exists('contact_value', $requestData))? $requestData['contact_value'] : null;

            $userId = !empty($this->request->session()->read('Auth.User.id')) ? $this->request->session()->read('Auth.User.id') : 1;
            //get prefered language
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $pref_lang = $ConfigItems->find()
                    ->where([
                        $ConfigItems->aliasField('code') => 'language',
                        $ConfigItems->aliasField('type') => 'System'
                    ])
                    ->first();
            //get nationality data
            $nationalities = '';
            if(!empty($nationalityName)){
                $nationalitiesTbl = TableRegistry::get('nationalities');
                $nationalities = $nationalitiesTbl->find()
                    ->where([
                        $nationalitiesTbl->aliasField('name') => $nationalityName,
                    ])
                    ->first();
                if(empty($nationalities)){
                    $orderNationalities = $nationalitiesTbl->find()
                        ->order([$nationalitiesTbl->aliasField('order DESC')])
                        ->first();
                    
                    $entityNationality = [
                        'name' => $nationalityName,
                        'order' => !empty($orderNationalities->order) ? $orderNationalities->order + 1 : 0,
                        'visible' => 1,
                        'editable' => 1,
                        'identity_type_id' => null,
                        'default' => 0,
                        'international_code' => '',
                        'national_code' => '',
                        'external_validation' => 0,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in nationalities table if doesn't exist in table
                    $entityNationalityData = $nationalitiesTbl->newEntity($entityNationality);
                    $NationalitiesResult = $nationalitiesTbl->save($entityNationalityData);
                    if($NationalitiesResult){
                        $nationalities->id = $NationalitiesResult->id;
                    } 
                }
            }
        
            $StudVal = $StaffVal= $GaurdianVal = 0;        
            if($userType == 1){ 
                $StudVal = 1; 
            }else if($userType == 2){ 
                $StaffVal  = 1;
            }else if($userType == 3){ 
                $GaurdianVal = 1;
            }      

            $SecurityUsers = TableRegistry::get('security_users');
            $CheckUserExist = $SecurityUsers->find()
                            ->where([
                                $SecurityUsers->aliasField('openemis_no') => $openemisNo
                            ])->first();
            $SecurityUsers = TableRegistry::get('security_users');
            if(!empty($CheckUserExist)){
                $existUserId = $CheckUserExist->id;
                $entityData = [
                    'id' => !empty($existUserId) ? $existUserId : '',
                    'openemis_no' => $openemisNo,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'third_name' => $thirdName,
                    'last_name' => $lastName,
                    'preferred_name' => $preferredName,
                    'gender_id' => $genderId,
                    'date_of_birth' => $dateOfBirth,
                    'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                    'preferred_language' => $pref_lang->value,
                    'username' => $username,
                    'password' => $password,
                    'address' => $address,
                    'address_area_id' => $addressAreaId,
                    'birthplace_area_id' => $birthplaceAreaId,
                    'postal_code' => $postalCode,
                    'photo_name' => $photoName,
                    'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                    'is_student' => $StudVal,
                    'is_staff' => $StaffVal,
                    'is_guardian' => $GaurdianVal,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ];
            }else{
                $entityData = [
                    'openemis_no' => $openemisNo,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'third_name' => $thirdName,
                    'last_name' => $lastName,
                    'preferred_name' => $preferredName,
                    'gender_id' => $genderId,
                    'date_of_birth' => $dateOfBirth,
                    'nationality_id' => !empty($nationalities->id) ? $nationalities->id : '',
                    'preferred_language' => $pref_lang->value,
                    'username' => $username,
                    'password' => $password,
                    'address' => $address,
                    'address_area_id' => $addressAreaId,
                    'birthplace_area_id' => $birthplaceAreaId,
                    'postal_code' => $postalCode,
                    'photo_name' => $photoName,
                    'photo_content' => !empty($photoContent) ? file_get_contents($photoContent) : '',
                    'is_student' => $StudVal,
                    'is_staff' => $StaffVal,
                    'is_guardian' => $GaurdianVal,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ];
            }                
            //save in security_users table
            $entity = $SecurityUsers->newEntity($entityData);
            try{
                $SecurityUserResult = $SecurityUsers->save($entity);
                unset($entity);
            }catch (Exception $e) {
                return null;
            }

            if($SecurityUserResult){
                $user_record_id=$SecurityUserResult->id;
                if(!empty($nationalityId) || !empty($nationalityName)){
                    if(!empty($nationalities->id)){
                        if(!empty($nationalities->id)){
                            $UserNationalities = TableRegistry::get('user_nationalities');
                            $checkexistingNationalities = $UserNationalities->find()
                                ->where([
                                    $UserNationalities->aliasField('nationality_id') => $nationalities->id,
                                    $UserNationalities->aliasField('security_user_id') => $user_record_id,
                                ])->first();
                            if(empty($checkexistingNationalities)){
                                $primaryKey = $UserNationalities->primaryKey();
                                $hashString = [];
                                foreach ($primaryKey as $key) {
                                    if($key == 'nationality_id'){
                                        $hashString[] = $nationalities->id;
                                    }
                                    if($key == 'security_user_id'){
                                        $hashString[] = $user_record_id;
                                    }
                                }
                     
                                $entityNationalData = [
                                    'id' => Security::hash(implode(',', $hashString), 'sha256'),
                                    'preferred' => 1,
                                    'nationality_id' => $nationalities->id,
                                    'security_user_id' => $user_record_id,
                                    'created_user_id' => $userId,
                                    'created' => date('Y-m-d H:i:s')
                                ];
                                //save in user_nationalities table
                                $entityNationalData = $UserNationalities->newEntity($entityNationalData);
                                $UserNationalitiesResult = $UserNationalities->save($entityNationalData);
                            }
                        }
                    }
                }

                if(!empty($nationalities->id) && !empty($identityTypeId) && !empty($identityNumber)){
                    $identityTypesTbl = TableRegistry::get('identity_types');
                    $identityTypes = $identityTypesTbl->find()
                        ->where([
                            $identityTypesTbl->aliasField('name') => $identityTypeName,
                        ])
                        ->first();
                    if(!empty($identityTypes)){
                        $UserIdentities = TableRegistry::get('user_identities');
                        $checkexistingIdentities = $UserIdentities->find()
                            ->where([
                                $UserIdentities->aliasField('nationality_id') => $nationalities->id,
                                $UserIdentities->aliasField('identity_type_id') => $identityTypeId,
                                $UserIdentities->aliasField('number') => $identityNumber,
                            ])->first();
                        if(empty($checkexistingIdentities)){
                            $entityIdentitiesData = [
                                'identity_type_id' => $identityTypes->id,
                                'number' => $identityNumber,
                                'nationality_id' => $nationalities->id,
                                'security_user_id' => $user_record_id,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in user_identities table
                            $entityIdentitiesData = $UserIdentities->newEntity($entityIdentitiesData);
                            $UserIdentitiesResult = $UserIdentities->save($entityIdentitiesData);
                        }
                    }
                }

                if(!empty($contactType) && !empty($contactValue)){
                    $UserContacts = TableRegistry::get('user_contacts');
                    $entityContactData = [
                        'contact_option_id' => $contactType,
                        'contact_type_id' => $contactType,
                        'value' => $contactValue,
                        'preferred' => 1,
                        'security_user_id' => $user_record_id,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in user_contacts table
                    $entityContactData = $UserContacts->newEntity($entityContactData);
                    $UserContactResult = $UserContacts->save($entityContactData);
                }

                if(!empty($custom)){
                    if($userType == 1){ //for student
                        //if student custom field values already exist in student_custom_field_values table the delete the old values and insert the new ones.
                        $studentCustomFieldValues =  TableRegistry::get('student_custom_field_values');
                        $StudentCustomFieldValuesCount = $studentCustomFieldValues
                                                            ->find()
                                                            ->where([$studentCustomFieldValues->aliasField('student_id') => $user_record_id])
                                                            ->count();
                        if($StudentCustomFieldValuesCount > 0){
                            $studentCustomFieldValues->deleteAll(['student_id' => $user_record_id]);
                        }
                    }else if($userType == 2){ //for staff
                        //if staff custom field values already exist in `staff_custom_field_values` table the delete the old values and insert the new ones.
                        $staffCustomFieldValues =  TableRegistry::get('staff_custom_field_values');
                        $StaffCustomFieldValuesCount = $staffCustomFieldValues
                                                            ->find()
                                                            ->where([$staffCustomFieldValues->aliasField('staff_id') => $user_record_id])
                                                            ->count();
                        if($StaffCustomFieldValuesCount > 0){
                            $staffCustomFieldValues->deleteAll(['staff_id' => $user_record_id]);
                        }
                    }
                    
                    foreach ($custom as $skey => $sval) {
                        if($userType == 1){ 
                            $entityCustomData = [
                                'id' => Text::uuid(),
                                'text_value' => $sval['text_value'],
                                'number_value' => $sval['number_value'],
                                'decimal_value' => $sval['decimal_value'],
                                'textarea_value' => $sval['textarea_value'],
                                'date_value' => $sval['date_value'],
                                'time_value' => $sval['time_value'],
                                'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                                'student_custom_field_id' => $sval['custom_field_id'],
                                'student_id' => $user_record_id,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in student_custom_field_values table
                            $entityCustomData = $studentCustomFieldValues->newEntity($entityCustomData);
                            $customFieldsResult = $studentCustomFieldValues->save($entityCustomData);
                        }else if($userType == 2){ 
                            $entityCustomData = [
                                'id' => Text::uuid(),
                                'text_value' => $sval['text_value'],
                                'number_value' => $sval['number_value'],
                                'decimal_value' => $sval['decimal_value'],
                                'textarea_value' => $sval['textarea_value'],
                                'date_value' => $sval['date_value'],
                                'time_value' => $sval['time_value'],
                                'file' => !empty($sval['file']) ? file_get_contents($sval['file']) : '',
                                'staff_custom_field_id' => $sval['custom_field_id'],
                                'staff_id' => $user_record_id,
                                'created_user_id' => $userId,
                                'created' => date('Y-m-d H:i:s')
                            ];
                            //save in staff_custom_field_values table
                            $entityCustomData = $staffCustomFieldValues->newEntity($entityCustomData);
                            $customFieldsResult = $staffCustomFieldValues->save($entityCustomData);
                        }
                        unset($customFieldsResult);
                        unset($entityCustomData);
                    }
                }
                try{
                    die('success');        
                }catch(Exception $e){
                    return $e;
                }
            }else{
                return false;
            }
            return $data;
        }
    }

    public function checkUserAlreadyExistByIdentity()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        if(!empty($requestData)){
            $identityTypeId = (array_key_exists('identity_type_id', $requestData))? $requestData['identity_type_id'] : null;
            $identityNumber = (array_key_exists('identity_number', $requestData))? $requestData['identity_number']: null;
            $nationalityId = (array_key_exists('nationality_id', $requestData))? $requestData['nationality_id']: null;
            $UserIdentities = TableRegistry::get('user_identities');//POCOR-7390
            if(!empty($identityTypeId) && !empty($identityNumber) && !empty($nationalityId)){
                $CheckUserExist = $UserIdentities->find()
                                    ->where([
                                        $UserIdentities->aliasField('identity_type_id') => $identityTypeId,
                                        $UserIdentities->aliasField('number') => $identityNumber,
                                        $UserIdentities->aliasField('nationality_id') => $nationalityId
                                    ])->count();
                if($CheckUserExist > 0){
                    echo json_encode(['user_exist' => 1, 'status_code' => 200 ,'message' => __('User already exist with this nationality, identity type & identity number. Kindly select user from below list.')]); 
                }else{
                    echo json_encode(['user_exist' => 0, 'status_code' => 200 , 'message' => '']); 
                }
            }else if(!empty($identityTypeId) && !empty($identityNumber) && empty($nationalityId)){//POCOR-7390 starts
                $CheckUserExist = $UserIdentities->find()
                                    ->where([
                                        $UserIdentities->aliasField('identity_type_id') => $identityTypeId,
                                        $UserIdentities->aliasField('number') => $identityNumber
                                    ])->count();
                if($CheckUserExist > 0){
                    echo json_encode(['user_exist' => 1, 'status_code' => 200 ,'message' => __('This identity has already existed in the system.')]); 
                }else{
                    echo json_encode(['user_exist' => 0, 'status_code' => 200 , 'message' => '']); 
                }//POCOR-7390 ends
            }else{
                echo json_encode(['user_exist' => 0, 'status_code' => 400 ,'message' => __('Invalid data.')]); 
            }
        }else{
           echo json_encode(['user_exist' => 0, 'status_code' => 400 ,'message' => __('Invalid data.')]); 
        }
        die;
    }
    //POCOR-7123 starts
    public function checkConfigurationForExternalSearch()
    {
        $this->autoRender = false;
        $configItems = TableRegistry::get('config_items');
        $configItemsResult = $configItems
            ->find()
            ->select(['id','value'])
            ->where(['code' => 'external_data_source_type', 'type' => 'External Data Source', 'name' => 'Type'])
            ->toArray();
        foreach($configItemsResult AS $result){
            if($result['value'] == "None"){
                $result_array[] = array("value" => $result['value'], "showExternalSearch" => false);
            }else{
                $result_array[] = array("value" => $result['value'], "showExternalSearch" => true);
            }
        }
        echo json_encode($result_array);die;
    }//POCOR-7123 ends

    public function customFieldsUseJustForExample()
    {
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
                    'created' => date('Y-m-d H:i:s')
                ];
                //save in student_custom_field_values table
                $entityCustomData = $studentCustomFieldValues->newEntity($entitySubjectsData);
                $studentCustomFieldsResult = $studentCustomFieldValues->save($entityCustomData);
                unset($studentCustomFieldsResult);
            }
        }
    }

    /*POCOR-6264 starts*/
    public function Lands()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Lands']);
    }
    /*POCOR-6264 ends*/

    //  POCOR-6130 export
    public function StudentUserExport()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentUserExport']);
    }

    public function InstitutionBuses(){
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBuses']);
    }

    public function Distributions(){
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionDistributions']);
    }

    public function ViewReport()
    {
        ini_set('memory_limit', '-1');
        $data = $_GET;
        $explode_data = explode("/", $data['file_path']);
        if (!empty($this->request->param('institutionId'))) {
            $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
        } else {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
        }

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
        $this->Navigation->addCrumb($data['module']);
        $header = __('Reports') . ' - ' .$data['module'];

        $inputFileName = WWW_ROOT. 'export/'.end($explode_data);

        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        if ($data['module'] == 'InstitutionStatistics' ) {
             $highestRow = $sheet->getHighestRow() + 1;
        }
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= 1; $row++){
            $rowHeader = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
        }

        $rowHeaderNew = $this->array_flatten($rowHeader);
        for ($row = 2; $row <= $highestRow -1; $row++){
            //  Read a row of data into an array
            $rowData[] = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
            if($this->isEmptyRow(reset($rowData))) { continue; }
            //  Insert row data array into your database of choice here
        }
        foreach($rowData as $newKey => $newDataVal){
            foreach($newDataVal as $kay2 => $new_data_arr){
                if(isset($new_data_arr)){
                    $newArr2[] = array_combine($rowHeaderNew, $new_data_arr);
                }
            }
        }
        $this->set('rowHeader', $rowHeader);
        $this->set('newArr2', $newArr2);

        $this->set('contentHeader', $header);
    }

    function array_flatten($array) {
        if (!is_array($array)) {
            return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result = array_merge($result, array($key => $value));
            }
        }
        return $result;
    }

    function isEmptyRow($row) {
        foreach($row as $cell){
            if (null !== $cell) return false;
        }
        return true;
    }

    /**
     * Get the Feature options of the Institutions Standard Report
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6493
     */
    public function getInstitutionStatisticStandardReportFeature() : array
    {
        // Start POCOR-6871
        $options = [
            'Institution.InstitutionStandardMarksEntered'  => __('Marks Entered by Staff'),//POCOR-6630
            'Institution.InstitutionStaffPositionProfile'  => __('Staff Career'),//POCOR-6581 //POCOR-6715 //POCOR-6886(changed report name from Staff Absences to Staff Career as per client suggestion)
            'Institution.InstitutionStandardStaffSpecialNeeds'  => __('Staff Special Needs'),
            'Institution.InstitutionStandardStaffTrainings'  => __('Staff Training'),
            'Institution.InstitutionStandardStudentAbsences'  => __('Student Absences'),//POCOR-6631
            'Institution.InstitutionStandardStudentAbsenceType'  => __('Student Absence Type'),//POCOR-6632
            'Institution.StudentAttendanceSummary'  => __('Student Attendance Summary Report'),//POCOR-6872
            'Institution.StudentHealths'  => __('Student Health'),
            'Institution.InstitutionStandards' => __('Students') . ' ' . __('Overview'),
            'Institution.StudentSpecialNeeds'  => __('Student Special Needs'),
        ];
        // End POCOR-6871
        return $options;
    }

    /**
     * POCOR-6995 
     * show Institution Class data in webhook
    **/ 
    private function institutionClassStudentData($institutionClassId) 
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $bodyData = $InstitutionClasses->find('all',
                        [ 'contain' => [
                            'Institutions',
                            'EducationGrades',
                            'Staff',
                            'AcademicPeriods',
                            'InstitutionShifts',
                            'InstitutionShifts.ShiftOptions',
                            'ClassesSecondaryStaff.SecondaryStaff',
                            'Students',
                            'Students.Genders'
                        ],
                        ])->where([
                            $InstitutionClasses->aliasField('id') => $institutionClassId
                        ]);

            $grades = $gradeId = $secondaryTeachers = $students = [];

            if (!empty($bodyData)) {
                foreach ($bodyData as $key => $value) {
                    $capacity = $value->capacity;
                    $shift = $value->institution_shift->shift_option->name;
                    $academicPeriod = $value->academic_period->name;
                    $homeRoomteacher = $value->staff->openemis_no;
                    $institutionId = $value->institution->id;
                    $institutionName = $value->institution->name;
                    $institutionCode = $value->institution->code;
                    $institutionClassId = $institutionClassId;
                    $institutionClassName = $value->name;

                    if(!empty($value->education_grades)) {
                        foreach ($value->education_grades as $key => $gradeOptions) {
                            $grades[] = $gradeOptions->name;
                            $gradeId[] = $gradeOptions->id;
                        }
                    }

                    if(!empty($value->classes_secondary_staff)) {
                        foreach ($value->classes_secondary_staff as $key => $secondaryStaffs) {
                            $secondaryTeachers[] = $secondaryStaffs->secondary_staff->openemis_no;
                        }
                    }

                    $maleStudents = 0;
                    $femaleStudents = 0;
                    if(!empty($value->students)) {
                        foreach ($value->students as $key => $studentsData) {
                            $students[] = $studentsData->openemis_no;
                            if($studentsData->gender->code == 'M') {
                                $maleStudents = $maleStudents + 1;
                            }
                            if($studentsData->gender->code == 'F') {
                                $femaleStudents = $femaleStudents + 1;
                            }
                        }
                    }

                }
            }

            $body = array();

            $body = [
                'institution_Class' => 
                [
                    'institutions_id' => !empty($institutionId) ? $institutionId : NULL,
                    'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
                    'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
                    'institutions_classes_id' => $institutionClassId,
                    'institutions_classes_name' => $institutionClassName,
                    'academic_periods_name' => !empty($academicPeriod) ? $academicPeriod : NULL,
                    'shift_options_name' => !empty($shift) ? $shift : NULL,
                    'institutions_classes_capacity' => !empty($capacity) ? $capacity : NULL,
                    'education_grades_id' => !empty($gradeId) ? $gradeId :NULL,
                    'education_grades_name' => !empty($grades) ? $grades : NULL,
                    'institution_classes_total_male_students' => !empty($maleStudents) ? $maleStudents : 0,
                    'institution_classes_total_female_studentss' => !empty($femaleStudents) ? $femaleStudents : 0,
                    'total_students' => !empty($students) ? count($students) : 0,
                    'institution_classes_staff_openemis_no' => !empty($homeRoomteacher) ? $homeRoomteacher : NULL,
                    'institution_classes_secondary_staff_openemis_no' => !empty($secondaryTeachers) ? $secondaryTeachers : NULL,
                    'institution_class_students_openemis_no' => !empty($students) ? $students : NULL
                ],
            ];

            return $body;
    }

    /**
     * POCOR-7146
     * assign Role and group to student while creating student
    **/ 
    private function assignStudentRoleGroup($institutionId, $user_id) 
    {
        $securityRolesTbl = TableRegistry::get('security_roles');
        $securityRoles = $securityRolesTbl->find()
                                ->where([
                                    $securityRolesTbl->aliasField('code') => 'STUDENT',
                                ])->first();
        //get student institution
        $institutionTbl = TableRegistry::get('institutions');
        $institutions = $institutionTbl->find()
                                ->where([
                                    $institutionTbl->aliasField('id') => $institutionId
                                ])->first();
        if(!empty($institutions) && $institutions->security_group_id !=''){
             $securityGroupInstitutionsTbl = TableRegistry::get('security_group_institutions');
             $securityGroupInstitutions = $securityGroupInstitutionsTbl->find()
                                     ->where([
                                         $securityGroupInstitutionsTbl->aliasField('security_group_id') => $institutions->security_group_id,
                                         $securityGroupInstitutionsTbl->aliasField('institution_id') => $institutions->id
                                     ])
                                     ->first();
             //save security group for institution
             if(empty($securityGroupInstitutions)){
                 $security_group_ins_data = [
                             'security_group_id' => $institutions->security_group_id,
                             'institution_id' => $institutionId,
                             'created_user_id' => 1,
                             'created' => new Time('NOW')
                     ];
                 $securityGroupInstitutionsEntity = $securityGroupInstitutionsTbl->newEntity($security_group_ins_data);
                 $securityGroupInstitutionsTbl->save($securityGroupInstitutionsEntity);
             }
             //check user already exist or not
             $securityGroupUsersTbl = TableRegistry::get('security_group_users');
             $checkSecurityGroupUser = $securityGroupUsersTbl->find()
                                     ->where([
                                         $securityGroupUsersTbl->aliasField('security_user_id') => $user_id,
                                         $securityGroupUsersTbl->aliasField('security_role_id') => $securityRoles->id
                                     ])
                                     ->first();
             //check user_id with role is available or not in `security_group_users` group                       
             if(empty($checkSecurityGroupUser)){
                 $securityGroupUsers = $securityGroupUsersTbl->find()
                                         ->where([
                                             $securityGroupUsersTbl->aliasField('security_group_id') => $institutions->security_group_id,
                                             $securityGroupUsersTbl->aliasField('security_user_id') => $user_id,
                                             $securityGroupUsersTbl->aliasField('security_role_id') => $securityRoles->id,
                                         ])
                                         ->first();
                 if(empty($securityGroupUsers)){
                     //save user in security_group_users table first time 
                     $id = Text::uuid();
                     $security_group_data = [
                                 'id' => $id,
                                 'security_group_id' => $institutions->security_group_id,
                                 'security_user_id' => $user_id,
                                 'security_role_id' => $securityRoles->id,
                                 'created_user_id' => 1,
                                 'created' => new Time('NOW')
                         ];
                     $securityGroupUsersEntity = $securityGroupUsersTbl->newEntity($security_group_data);
                     $securityGroupUsersTbl->save($securityGroupUsersEntity);
                 }                        
             }else{
                 //update user's security_group_id in security_group_users table 
                 $InstitutionStudentsTbl = TableRegistry::get('institution_students');
                 $InstitutionStudentTransfersTbl = TableRegistry::get('institution_student_transfers');
                 $InstitutionStudents = $InstitutionStudentsTbl
                                         ->find()
                                         ->select([
                                             $InstitutionStudentsTbl->aliasField('student_id'),
                                             $InstitutionStudentTransfersTbl->aliasField('institution_id'),
                                             $InstitutionStudentTransfersTbl->aliasField('previous_institution_id')
                                         ])
                                         ->leftJoin([$InstitutionStudentTransfersTbl->alias() => $InstitutionStudentTransfersTbl->table()],
                                             [
                                                 $InstitutionStudentTransfersTbl->aliasField('student_id').'='.$InstitutionStudentsTbl->aliasField('student_id'),
                                                 $InstitutionStudentTransfersTbl->aliasField('institution_id')=>$institutions->id
                                             ]
                                         )
                                         ->where([
                                             $InstitutionStudentsTbl->aliasField('student_id') => $checkSecurityGroupUser->security_user_id,
                                             $InstitutionStudentsTbl->aliasField('institution_id') => $institutions->id,
                                             $InstitutionStudentsTbl->aliasField('student_status_id') => 1//for enrolled status
                                         ])
                                         ->first();
                 
                 if(!empty($InstitutionStudents)){
                     if(!empty($InstitutionStudents->institution_student_transfers['previous_institution_id'])){
                         $PreviousInstitutions = $institutionTbl->find()
                                 ->where([
                                     $institutionTbl->aliasField('id') => $InstitutionStudents->institution_student_transfers['previous_institution_id']
                                 ])
                                 ->first();
                                
                         if($PreviousInstitutions->security_group_id == $checkSecurityGroupUser->security_group_id){
                             $securityGroupUsersTbl->updateAll(
                                 [
                                     'security_group_id' => $institutions->security_group_id,
                                     'created' => new Time('NOW')
                                 ],
                                 [
                                     'security_group_id' => $PreviousInstitutions->security_group_id,
                                     'security_user_id' => $checkSecurityGroupUser->security_user_id,
                                     'security_role_id' => $checkSecurityGroupUser->security_role_id
                                 ]
                             );
                         }
                     }
                 }
             }
        }
    }

    /**
     * Get User Data from CSPD api
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6930
    **/ 
    public function getCspdData()
    {
        error_reporting(0);
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        //$requestData['identity_number'] = 9791048083;
        if(!empty($requestData)){
            $national_no = (array_key_exists('identity_number', $requestData))? $requestData['identity_number'] : null;
            if(!empty($national_no)){
                $externalDataSourceAttributesTbl = TableRegistry::get('external_data_source_attributes');
                $externalDataSourceAttributesData = $externalDataSourceAttributesTbl
                            ->find()
                            ->select(['id','external_data_source_type','attribute_field','attribute_name','value'])
                            ->where([
                                $externalDataSourceAttributesTbl->aliasField('external_data_source_type') => 'Jordan CSPD'
                            ])->hydrate(false)->toArray();
                $config_Array = [];
                foreach ($externalDataSourceAttributesData as $ex_key => $ex_val) {
                    $config_Array[$ex_val['attribute_field']] = trim($ex_val['value']);
                }
                
                if(!empty($config_Array['username']) && !empty($config_Array['password']) && !empty($config_Array['url'])){
                    $soapUrl = $config_Array['url'];
                    $soapUser = $config_Array['username'];  
                    $soapPassword = $config_Array['password']; 
                    // xml post structure
                    $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
                       <soapenv:Header>
                            <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                                <wsse:UsernameToken wsu:Id="UsernameToken-459">
                                    <wsse:Username>'.$soapUser.'</wsse:Username>
                                    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'.$soapPassword.'</wsse:Password>
                                </wsse:UsernameToken>
                            </wsse:Security>
                        </soapenv:Header>
                       <soapenv:Body>
                          <tem:gePersonal>
                             <!--Optional:-->
                             <tem:nationalNo>'.$national_no.'</tem:nationalNo>
                          </tem:gePersonal>
                       </soapenv:Body>
                    </soapenv:Envelope>
                    ';// data from the form, e.g. some ID number
                    $response = $this->CreateUsers->getResponseForCspd($soapUrl,$soapUser,$soapPassword,$xml_post_string);
                    if(empty($response)){
                        echo json_encode(['status_code' => 200 ,'message' => __('Response is empty.')]); 
                    }else{
                        $result_Array = [];
                        foreach ($externalDataSourceAttributesData as $ex_key => $ex_val) {
                            if(in_array($ex_val['attribute_field'],['username','password','url'])){
                                unset($ex_val['attribute_field']);
                            }else{
                                $dataVal = array_shift(array_shift(array_shift(array_shift($this->CreateUsers->XMLtoArray($response)))));
                                if(!empty($dataVal)){
                                    $value = 'a:'.$ex_val['value'];
                                    if($ex_val['attribute_field'] == 'first_name_mapping'){
                                        $fieldKey = 'first_name';
                                    }else if($ex_val['attribute_field'] == 'middle_name_mapping'){
                                        $fieldKey = 'middle_name';
                                    }else if($ex_val['attribute_field'] == 'third_name_mapping'){
                                        $fieldKey = 'third_name';
                                    }else if($ex_val['attribute_field'] == 'last_name_mapping'){
                                        $fieldKey = 'last_name';
                                    }else if($ex_val['attribute_field'] == 'gender_mapping'){
                                        $fieldKey = 'gender_name';
                                        $genders_types = TableRegistry::get('genders');
                                        $genders_types_result = $genders_types
                                               ->find()
                                               ->select(['id','name'])
                                               ->where([$genders_types->aliasField('name') => $dataVal[$value]])
                                               ->first();
                                        $result_Array['gender_id'] = $genders_types_result->id;
                                    }else if($ex_val['attribute_field'] == 'date_of_birth_mapping'){
                                        $fieldKey = 'date_of_birth';
                                        $dataVal[$value] = date('Y-m-d', strtotime($dataVal[$value]));
                                    }else if($ex_val['attribute_field'] == 'identity_type_mapping'){
                                        $identity_types = TableRegistry::get('identity_types');
                                        $identity_types_result = $identity_types
                                               ->find()
                                               ->select(['id','name'])
                                               ->where([$identity_types->aliasField('default') => 1])
                                               ->first();
                                        $result_Array['identity_type_id'] = $identity_types_result->id;
                                        $dataVal[$value] = $identity_types_result->name;
                                        $fieldKey = 'identity_type_name';
                                    }else if($ex_val['attribute_field'] == 'identity_number_mapping'){
                                        $fieldKey = 'identity_number';
                                    }else if($ex_val['attribute_field'] == 'address_mapping'){
                                        $fieldKey = 'address';
                                    }else if($ex_val['attribute_field'] == 'postal_mapping'){
                                        $fieldKey = 'postal_code';
                                    }else if($ex_val['attribute_field'] == 'nationality_mapping'){
                                        $nationalitiesTbl = TableRegistry::get('nationalities');
                                        $nationalities = $nationalitiesTbl->find()
                                            ->select(['id','name'])
                                            ->where([
                                                $nationalitiesTbl->aliasField('name') => $dataVal[$value],
                                                $nationalitiesTbl->aliasField('visible') => 1,
                                            ])
                                            ->first();
                                        $result_Array['nationality_id'] = (!empty($nationalities)) ? $nationalities->id : '';
                                        $dataVal[$value] = (!empty($nationalities)) ? $nationalities->name : $dataVal[$value];
                                        $fieldKey = 'nationality_name';
                                    }
                                    $result_Array[$fieldKey] = $dataVal[$value];
                                }else{
                                    echo json_encode(['status_code' => 400 ,'message' => __('Invalid data.')]); 
                                }
                            }
                        }
                        //get guardians details
                        $guardian_relations = TableRegistry::get('guardian_relations');
                        $guardian_relations_result = $guardian_relations
                               ->find()
                               ->where([$guardian_relations->aliasField('international_code !=') => ''])
                               ->hydrate(false)
                               ->toArray();
                        if(!empty($guardian_relations_result)){
                            foreach ($guardian_relations_result as $gkey => $gval) {
                                if(!empty($gval['international_code']) || !empty($gval['national_code'])){
                                    $dataVal = array_shift(array_shift(array_shift(array_shift($this->CreateUsers->XMLtoArray($response)))));
                                    if(!empty($dataVal)){
                                        $value = 'a:'.$gval['international_code'];
                                        if($gval['name'] == 'Father'){
                                            $relationsfieldKey = 'father_national_no';
                                        }else if($gval['name'] == 'Mother'){
                                            $relationsfieldKey = 'mother_national_no';
                                        }
                                        $result_Array[$relationsfieldKey] = $dataVal[$value];
                                    }
                                }
                            }
                        }
                        echo json_encode(['status_code' => 200,'message' => __('Get user details successfully.') ,'data' => $result_Array]); 
                        die;
                    }
                }else{
                    echo json_encode(['status_code' => 400 ,'message' => __('Invalid data.')]); 
                }
            }else{
               echo json_encode(['status_code' => 400 ,'message' => __('Invalid data.')]); 
            }
        }
    }

    /**
     * Get Configuration For External Source Data
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     * @return array
     * @ticket POCOR-6930
    **/ 
    public function getConfigurationForExternalSourceData()
    {
        $this->autoRender = false;
        //get Configuration For External Source Data from config_items table
        $configItemsTbl = TableRegistry::get('config_items');
        $configItemsResult = $configItemsTbl
            ->find()
            ->where(['visible' => 1, 'code'=> 'external_data_source_type', 'type'=> 'External Data Source'])
            ->hydrate(false)
            ->toArray();

        if (!empty($configItemsResult)) {
            foreach ($configItemsResult as $k => $val) {
                $result_array[] = array("id" => $val['id'], "name" => $val['name'], "code" => $val['code'], "type" => $val['type'], "value" => $val['value']);
            }
        }
        echo json_encode($result_array);die;
    }


    //POCOR-7231 :: Start
    public function Addguardian()
    {
        $requestDataa = $this->paramsDecode($this->request->query('queryString1'));
        $StudentID = $this->paramsEncode(['id' => $requestDataa['institution_id']]);
        $StudentID1 = $this->paramsEncode(['id' => $requestDataa['student_id']]);
        $UsersTable = TableRegistry::get('User.Users');
        $InstitutionTable = TableRegistry::get('Institution.Institutions');
        $UserData = $UsersTable->find('all',['conditions'=>['id'=>$requestDataa['student_id']]])->first();
        $InstitutionData = $InstitutionTable->find('all',['conditions'=>['id'=>$requestDataa['institution_id']]])->first();
        $queryStng = $this->paramsEncode(['id' => $UserData->id]);
        $this->set('InstitutionData', $InstitutionData);
        $this->set('UserData', $UserData);
        $this->set('StudentID', $StudentID);
        $this->set('StudentID1', $StudentID1);
        $this->set('queryStng', $queryStng);
        $this->set('ngController', 'DirectoryaddguardianCtrl as $ctrl');
    }

    private function attachAngularModulesForDirectory()
    {
        $action = $this->request->pass[0];
        if($action == '' || $this->request->params['action'] != 'Directories'){
            $action = $this->request->params['action'];
        }
        switch ($action) {
            case 'add':
                $this->Angular->addModules([
                    'directory.directoryadd.ctrl',
                    'directory.directoryadd.svc'
                ]);
                break;
            case 'Addguardian':
                $this->Angular->addModules([
                    'directory.directoryaddguardian.ctrl',
                    'directory.directoryaddguardian.svc'
                ]);
                break;
        }
    }
    //POCOR-7231 :: END

    //POCOR-6673
    public function getCurricularsTabElements($options = [])
    {
        $queryString = $this->request->query('queryString');
        $tabElements = [
            'InstitutionCurriculars' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'InstitutionCurriculars', 'view', 'queryString' => $queryString],
                'text' => __('Curriculars')
            ],
            'InstitutionCurricularStudents' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'InstitutionCurricularStudents', 'index', 'queryString' => $queryString],
                'text' => __('Students')
            ]
        ];
        return $tabElements;
    }

    //POCOR-6673
    public function StudentCurriculars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentCurriculars']);
    }

}
