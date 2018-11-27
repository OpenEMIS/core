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

class InstitutionsController extends AppController
{
    use OptionsTrait;
    use UtilityTrait;
    public $activeObj = null;

    private $features = [
        // general
        'InstitutionAttachments',

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
        'StaffTrainingNeeds',
        'StaffTrainingApplications',
        'StaffTrainingResults',
        'StaffTransferIn',
        'StaffTransferOut',
        // 'StaffPositionProfiles',

        // attendances
        'InstitutionStaffAttendances',
        'InstitutionStudentAbsences',
        'StudentAttendances',

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
            'ImportInstitutionSurveys'  => ['className' => 'Institution.ImportInstitutionSurveys', 'actions' => ['add']],
            'ImportStudentAdmission'    => ['className' => 'Institution.ImportStudentAdmission', 'actions' => ['add']],
            'ImportStaff'               => ['className' => 'Institution.ImportStaff', 'actions' => ['add']],
            'ImportInstitutionTextbooks'=> ['className' => 'Institution.ImportInstitutionTextbooks', 'actions' => ['add']],
            'ImportOutcomeResults'      => ['className' => 'Institution.ImportOutcomeResults', 'actions' => ['add']],
            'ImportStaffLeave'          => ['className' => 'Institution.ImportStaffLeave', 'actions' => ['add']],
            'ImportInstitutionPositions'=> ['className' => 'Institution.ImportInstitutionPositions', 'actions' => ['add']],
            'ImportStudentBodyMasses'   => ['className' => 'Institution.ImportStudentBodyMasses', 'actions' => ['add']]
        ];

        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->loadComponent('Training.Training');
        $this->loadComponent('Institution.CreateUsers');
        $this->attachAngularModules();
    }

    // CAv4
    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAttachments']);
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
    public function ReportCardStatuses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardStatuses']);
    }
    public function InstitutionStudentsReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStudentsReportCards']);
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
    public function StudentTransferOut()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentTransferOut']);
    }
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
    // End

    // AngularJS
    public function StudentAttendances()
    {
        $_edit = $this->AccessControl->check(['Institutions', 'StudentAttendances', 'edit']);
        // $_excel = $this->AccessControl->check(['Institutions', 'StudentAttendances', 'excel']);
        // $_import = $this->AccessControl->check(['Institutions', 'ImportStudentAttendances', 'add']);
        $_excel = false;
        $_import = false;
        
        if (!empty($this->request->param('institutionId'))) {
            $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
        } else {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
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

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
        $this->Navigation->addCrumb($crumbTitle);

        $this->set('_edit', $_edit);
        $this->set('_excel', $_excel);
        $this->set('_import', $_import);
        $this->set('excelUrl', Router::url($excelUrl));
        $this->set('importUrl', Router::url($importUrl));
        $this->set('institution_id', $institutionId);
        $this->set('ngController', 'InstitutionStudentAttendancesCtrl as $ctrl');
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
                        $classRecord = $ClassTable->get($classId, ['fields' => ['staff_id', 'secondary_staff_id']]);
                        if ($userId != $classRecord->staff_id && $userId != $classRecord->secondary_staff_id) {
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

    public function InstitutionStaffAttendances()
    {
        $_edit = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'edit']);
        $_history = $this->AccessControl->check(['Staff', 'InstitutionStaffAttendanceActivities', 'index']);
        if (!empty($this->request->param('institutionId'))) {
            $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
        } else {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
        }
        $this->set('_edit', $_edit);
        $this->set('_history', $_history);
        $this->set('institution_id', $institutionId);
        $this->set('ngController', 'InstitutionStaffAttendancesCtrl as $ctrl');
    }
    
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
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
            case 'StudentAttendances':
                $this->Angular->addModules([
                    'institution.student.attendances.ctrl',
                    'institution.student.attendances.svc'
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
                'StudentRisks' => __('Risks')
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
            } else {
                $this->Navigation->addCrumb($crumbTitle, $crumbOptions);
                $header = $this->activeObj->name;
            }

            $persona = false;
            $requestQuery = $this->request->query;
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

            //Students By Grade for current year, excludes transferred and withdrawn students
            $params = [
                'conditions' => ['institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN']]]
            ];

            $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_stage', $params);

            //Students By Year, excludes transferred and withdrawn students
            $params = [
                'conditions' => ['institution_id' => $id, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN']]]
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

        $this->set('highChartDatas', $highChartDatas);
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
            'Results' => ['text' => __('Assessments')],
            'ExaminationResults' => ['text' => __('Examinations')],
            'ReportCards' => ['text' => __('Report Cards')],
            'Awards' => ['text' => __('Awards')],
            'Extracurriculars' => ['text' => __('Extracurriculars')],
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')]
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        // Programme will use institution controller, other will be still using student controller
        foreach ($studentTabElements as $key => $tab) {
            if (in_array($key, ['Programmes', 'Textbooks', 'Risks'])) {
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
}
