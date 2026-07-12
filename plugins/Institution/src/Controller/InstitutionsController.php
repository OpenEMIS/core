<?php

namespace Institution\Controller;

use App\Model\Traits\OptionsTrait;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Controller\Exception\SecurityException;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\Session;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Utility\Text;
use ControllerAction\Model\Traits\UtilityTrait;
use Exception;
use PHPExcel_IOFactory;
use Cake\Auth\DefaultPasswordHasher;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Cake\I18n\Time;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Filesystem\File;

//POCOR-5672

//POCOR-5672


class InstitutionsController extends AppController
{
    use OptionsTrait;
    use UtilityTrait;
    // POCOR-8231 start
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    public $savingDirectoryData = 0;
    public $savingGuardianData = 0;
    public $savingStudentData = 0;
    public $savingStaffData = 0;
    // POCOR-8231 end
    public $activeInstitution = null;

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
        'InstitutionCurriculars',//POCOR-6673
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
        'ArchivedStaffLeave',
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
//        'AssessmentsArchive',
        'AssessmentArchives',

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

        // appointments
        'InstitutionPositions',
        'InstitutionDepartments',

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

    public function initialize(): void
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
        $this->loadComponent('Cookie'); //POCOR-8551
        $data = $this->Calendars = $this->fetchTable('Calendars');
        // $this->viewBuilder()->setHelpers(['HtmlField']);

        // $this->ControllerAction->model('Institution.Institutions', [], ['deleteStrategy' => 'restrict']);
        $this->ControllerAction->models = [
            'Infrastructures' => ['className' => 'Institution.InstitutionInfrastructures', 'options' => ['deleteStrategy' => 'restrict']],
            'Staff' => ['className' => 'Institution.Staff'],
            'StaffSalaries' => ['className' => 'Institution.StaffSalaries'],
            'StaffAccount' => ['className' => 'Institution.StaffAccount', 'actions' => ['view', 'edit']],

            'StudentAccount' => ['className' => 'Institution.StudentAccount', 'actions' => ['view', 'edit']],
            'AttendanceExport' => ['className' => 'Institution.AttendanceExport', 'actions' => ['excel']],
            'StudentBehaviours' => ['className' => 'Institution.StudentBehaviours'],
            'Promotion' => ['className' => 'Institution.StudentPromotion', 'actions' => ['reconfirm', 'add']],
            'Undo' => ['className' => 'Institution.UndoStudentStatus', 'actions' => ['reconfirm', 'view', 'add']],
            'ClassStudents' => ['className' => 'Institution.InstitutionClassStudents', 'actions' => ['excel']],

            'BankAccounts' => ['className' => 'Institution.InstitutionBankAccounts'],

            // Quality
            'Rubrics' => ['className' => 'Institution.InstitutionRubrics', 'actions' => ['index', 'view', 'remove']],
            'RubricAnswers' => ['className' => 'Institution.InstitutionRubricAnswers', 'actions' => ['view', 'edit']],

            'ImportInstitutions' => ['className' => 'Institution.ImportInstitutions', 'actions' => ['add']],
            'ImportInstitutionAssets' => ['className' => 'Institution.ImportInstitutionAssets', 'actions' => ['add']],
            'ImportStaffAttendances' => ['className' => 'Institution.ImportStaffAttendances', 'actions' => ['add']],
            'ImportStudentAttendances' => ['className' => 'Institution.ImportStudentAttendances', 'actions' => ['add']],
            'ImportStudentMeals' => ['className' => 'Institution.ImportStudentMeals', 'actions' => ['add']],
            'ImportInstitutionSurveys' => ['className' => 'Institution.ImportInstitutionSurveys', 'actions' => ['add']],
            'ImportStudentAdmission' => ['className' => 'Institution.ImportStudentAdmission', 'actions' => ['add']],
            'ImportStaff' => ['className' => 'Institution.ImportStaff', 'actions' => ['add']],
            'ImportStaffSalaries' => ['className' => 'Institution.ImportStaffSalaries', 'actions' => ['add']],
            'ImportInstitutionTextbooks' => ['className' => 'Institution.ImportInstitutionTextbooks', 'actions' => ['add']],
            'ImportOutcomeResults' => ['className' => 'Institution.ImportOutcomeResults', 'actions' => ['add']],
            'ImportCompetencyResults' => ['className' => 'Institution.ImportCompetencyResults', 'actions' => ['add']],
            'ImportStaffLeave' => ['className' => 'Institution.ImportStaffLeave', 'actions' => ['add']],
            'ImportInstitutionPositions' => ['className' => 'Institution.ImportInstitutionPositions', 'actions' => ['add']],
            'ImportStudentBodyMasses' => ['className' => 'Institution.ImportStudentBodyMasses', 'actions' => ['add']],
            'ImportStudentGuardians' => ['className' => 'Institution.ImportStudentGuardians', 'actions' => ['add']],
            'ImportStudentExtracurriculars' => ['className' => 'Institution.ImportStudentExtracurriculars', 'actions' => ['add']],
            'StudentArchive' => ['className' => 'Institution.StudentArchive', 'actions' => ['add']],
//            'AssessmentsArchive' => ['className' => 'Institution.AssessmentsArchive', 'actions' => ['index']],
            'AssessmentArchives' => ['className' => 'Institution.AssessmentArchives', 'actions' => ['index']],
            'ImportAssessmentItemResults' => ['className' => 'Institution.ImportAssessmentItemResults', 'actions' => ['add']],
// POCOR-7339-HINDOL redundancy
//            'ImportAssessmentItemResults'      => ['className' => 'Institution.ImportAssessmentItemResults', 'actions' => ['add']],
            'InstitutionStatistics' => ['className' => 'Institution.InstitutionStatistics', 'actions' => ['index', 'add']],
            'InstitutionStandards' => ['className' => 'Institution.InstitutionStandards', 'actions' => ['index', 'add', 'remove']],
            'ImportStudentCurriculars' => ['className' => 'Institution.ImportStudentCurriculars', 'actions' => ['add']],//POCOR-6673
            // 'InfrastructureUtilityTelephones' => ['className' => 'Institution.InfrastructureUtilityTelephones', 'actions' => ['index', 'view', 'add', 'edit', 'remove']],
        ];

        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->loadComponent('Training.Training');
        $this->loadComponent('Institution.CreateUsers');
        $this->attachAngularModules();


        $this->attachAngularModulesForDirectory();
        $this->StaffBodyMasses = $this->fetchTable('Institution.StaffBodyMasses');
// POCOR-5672: Removing CSRF token mismatch condition for specific actions in the save APIs
        $csrfExemptActions = [
            'saveStudentData',
            'saveStaffData',
            'saveGuardianData',
            'saveDirectoryData',
            'saveAssessmentItemExemptions' // POCOR-8224
        ];

        $action = $this->request->getParam('action');
        if (in_array($action, $csrfExemptActions, true)) {
            $this->getEventManager()->off($this->Csrf);
        }
// End of POCOR-5672
    }

    private
    function attachAngularModules()
    {
        $action = $this->getRequest()->getParam('action');
        switch ($action) {
            case 'Associations':
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'kd-angular-multi-select',
                            'institution.associations.ctrl',
                            'institution.associations.svc'
                        ]);
                    }
                    if ($this->request->getParam('pass')[0] == 'add') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'kd-angular-multi-select',
                            'institutionadd.associations.ctrl',
                            'institutionadd.associations.svc'
                        ]);
                    }
                }
                break;
            case 'Departments':
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'kd-angular-multi-select',
                            'institution.departments.ctrl',
                            'institution.departments.svc'
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
            // POCOR-8224 start
            case 'AssessmentItemExemptions':
                $this->Angular->addModules([
                    'alert.svc',
                    'assessment.item.exemptions.ctrl',
                    'assessment.item.exemptions.svc'
                ]);
                break;
            // POCOR-8224 end
            case 'AssessmentItemResultsArchived':
                $this->Angular->addModules([
                    'alert.svc',
                    'institutions.results.archived.svc',
                    'institutions.results.archived.ctrl',
                ]);
                break;
            case 'Surveys':
                $this->Angular->addModules([
                    'relevancy.rules.ctrl'
                ]);
                $this->set('ngController', 'RelevancyRulesCtrl as RelevancyRulesController');
                break;
            case 'Students':
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'add') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institutions.students.ctrl',
                            'institutions.students.svc'
                        ]);
                    }
                }
                break;
            case 'Staff':
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'add') {
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
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
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
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
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
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institution.student.competencies.ctrl',
                            'institution.student.competencies.svc'
                        ]);
                    }
                }
                break;
            case 'StudentCompetencyComments':
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
                        $this->Angular->addModules([
                            'alert.svc',
                            'institution.student.competency_comments.ctrl',
                            'institution.student.competency_comments.svc'
                        ]);
                    }
                }
                break;
            case 'StudentOutcomes':
                if (isset($this->request->getParam('pass')[0])) {
                    if ($this->request->getParam('pass')[0] == 'edit') {
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

            case 'StaffAttendancesArchived':
                $this->Angular->addModules([
                    'staff.attendances.archived.ctrl',
                    'staff.attendances.archived.svc'
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

    private
    function attachAngularModulesForDirectory()
    {
        $action = $this->request->getParam('pass')[0];
        if ($action == '' || $this->request->getParam('action') != 'Directories') {
            $action = $this->request->getParam('action');
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
                    'directory.directoryadd.svc'
                ]);
                break;
        }
    }

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

    public function Departments($subaction = 'index', $encodedParams = null)
    {
        if ($subaction == 'edit') {
            $params = $this->ControllerAction->paramsDecode($encodedParams);
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            $departmentId = $params['id'];

            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'Departments';
            $viewUrl['0'] = 'view';
            $viewUrl['1'] = $encodedParams;

            $institutionParamsEncode = $this->ControllerAction->paramsEncode([
                'id' => $institutionId,
                'institution_id' => $institutionId,
            ]);
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Departments',
                '0' => 'index',
                '1' => $institutionParamsEncode
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
            $this->set('institutionId', $institutionId);

            // Start POCOR-7466
            $activeInstitution = $this->Institutions->get($institutionId);
            $institutionName = $activeInstitution->name;
            $this->Navigation->addCrumb(__('Departments'),
                ['plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Departments',
                    '0' => 'index',
                    '1' => $institutionParamsEncode]);
            $header = __($institutionName);
            $this->set('contentHeader', $header . ' - ' . __('Departments'));
            // END POCOR-7466

            $this->render('institution_departments_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionDepartments']);
        }
    }

    public function StaffDuties()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStaffDuties']);
    }

    public function Shifts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionShifts']);
    }

    //POCOR-9610: start - Institution Registrations and Accreditations CakePHP actions
    public function Registrations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionRegistrations']);
    }

    public function Accreditations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAccreditations']);
    }
    //POCOR-9610: end

    public function Fees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionFees']);
    }

    public function InstitutionLands()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionLands']);
    }

    public function InfrastructureNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureNeeds']);
    }

    public function InstitutionBuildings()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBuildings']);
    }

    public function InfrastructureProjects()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureProjects']);
    }

    public function InstitutionFloors()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionFloors']);
    }

    public function InstitutionAssets()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAssets']);
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

    public function BankAccounts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBankAccounts']);
    }

    public function Income()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionIncomes']);
    }

    public function Expenditure()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionExpenditures']);
    }
    //POCOR-8873 start
    public function Consumable()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionConsumables']);
    }

    public function Transactions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionConsumableTransactions']);
    }
    //POCOR-8873 end
    public function StaffPositionProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffPositionProfiles']);
    }

    public function Assessments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAssessments']);
    }

    public function AssessmentArchives()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionAssessmentArchives']);
    }

    public function AssessmentResults()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.AssessmentResults']);
    }

    public function StudentProgrammes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Programmes']);
    }

    public function StudentTransition()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Transition']);
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
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        $session = $this->request->getSession();
        $session->write('Institution.Institutions.id', $institutionId);
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

    public function ArchivedStaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.ArchivedStaffLeave']);
    }

    public function VisitRequests()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Quality.VisitRequests']);
    }

    public function Visits()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Quality.InstitutionQualityVisits']);
    }

    // POCOR-6154

    public function Programmes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionGrades']);
    }

    public function StaffBehaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffBehaviours']);
    }

    public function StaffBehaviourAttachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffBehaviourAttachments']);
    }

    public function StudentBehaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentBehaviours']);
    }

    public function StudentBehaviourAttachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentBehaviourAttachments']);
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

    public function InstitutionTrips()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTrips']);
    }

    public function InstitutionCurriculars()
    {

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionCurriculars']);

    }

    public function InstitutionCurricularStudents()
    {

        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionCurricularStudents']);
    }

    public function Messaging()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Messaging']);
    }

    public function MessageRecipients()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.MessageRecipients']);
    }

    public function changePageHeaderTrips($model, $modelAlias, $userType)
    {
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        if (!empty($institutionId)) {
            $activeInstitution = $this->Institutions->get($institutionId);
            $institutionName = $activeInstitution->name;
            if ($this->request->getParam('action') == 'InstitutionTrips') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Trips');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Trips'));
                $this->set('contentHeader', $header);

            } elseif ($this->request->getParam('action') == 'InstitutionCurriculars') { //POCOR-6673
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Curriculars');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Curriculars'));
                $this->set('contentHeader', $header);
            } elseif ($this->request->getParam('action') == 'Counsellings') {//POCOR-7485
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Counselling'));
            }
        }
    }

    /**
     * common function to get institution id
     * @return string|null
     *
     */
    public
    function getInstitutionID($debugString = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->getQueryString('institution_id');

        if (empty($institution_id) && $this->request->getQuery('institution_id') != null) {
            $institution_id =  $this->request->getQuery('institution_id');
        }
        if (!$institution_id) {
            $session = $this->request->getSession();
            $institution_id = $session->read('Institution.Institutions.id');
            
            //POCOR-9691[START]
            if(empty($institution_id)){
                $institution_id = $session->read('Institution.Institutions.primaryKey.id');
            }
            if(empty($institution_id)){
                $institution_id = $session->read('Institution.Institutions.primaryKey.institution_id');
            }
            //POCOR-9691[END]

        }
        // StaffBehaviours view: if still missing, decode pass[1] or load behaviour by id so view does not redirect to Dashboard
        if (!$institution_id && $this->request->getParam('action') == 'StaffBehaviours') {
            $pass = $this->request->getParam('pass');
            if (!empty($pass[1])) {
                try {
                    $decoded = $this->paramsDecode($pass[1]);
                    if (!empty($decoded['institution_id'])) {
                        $institution_id = $decoded['institution_id'];
                    } elseif (!empty($decoded['id'])) {
                        $StaffBehaviours = TableRegistry::getTableLocator()->get('Institution.StaffBehaviours');
                        $behaviour = $StaffBehaviours->get($decoded['id'], ['fields' => ['id', 'institution_id']]);
                        if ($behaviour && !empty($behaviour->institution_id)) {
                            $institution_id = $behaviour->institution_id;
                        }
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }
        if (!$institution_id && $debugString != "") {
            die($debugString . 'For Developer: You should put institution_id into query string first');
        }
        return $institution_id;
    }
    
    //POCOR-9620

    // public function AssessmentItemResultsArchived($pass = '')
    // {
    //     if ($pass == 'excel') {

    //         $classId = $this->ControllerAction->getQueryString('class_id');
    //         $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
    //         $institutionId = $this->ControllerAction->getQueryString('institution_id');
    //         $academicPeriodId = $this->ControllerAction->getQueryString('academic_period_id');
    //         $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.AssessmentItemResultsArchived']);
    //     } else {
    //         $queryString = $this->request->getQuery('queryString');
    //         $classId = $this->ControllerAction->getQueryString('class_id');

    //         $assessmentId = $this->ControllerAction->getQueryString('assessment_id');
    //         $institutionId = $this->ControllerAction->getQueryString('institution_id');
    //         $academicPeriodId = $this->ControllerAction->getQueryString('academic_period_id');
    //         $myClassName = $this->getInstitutionClassName($classId);
    //         $this->Navigation->addCrumb('Assessments', ['plugin' => $this->getPlugin(), 'controller' => 'Institutions', 'action' => 'Assessments', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])]);
    //         $this->Navigation->addCrumb('Assessment Archives',
    //             ['plugin' => $this->getPlugin(),
    //                 'controller' => 'Institutions',
    //                 'action' => 'AssessmentArchives',
    //                 'academic_period_id' => $academicPeriodId]);
    //         $this->Navigation->addCrumb("$myClassName");
    //         $roles = [];

    //         if (!$this->AccessControl->isAdmin()) {
    //             $userId = $this->Auth->user('id');
    //             $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
    //         }

    //         $this->set('_roles', $roles);

    //         // POCOR-3983 check institution status
    //         $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
    //         $isActive = $Institutions->isActive($institutionId);
    //         if ($isActive) {
    //             $_edit = $this->AccessControl->check(['Institutions', 'Results', 'edit'], $roles);
    //         } else {
    //             $_edit = false;
    //         }
    //         // end POCOR-3983

    //         $this->set('_edit', $_edit);
    //         $this->set('_excel', $this->AccessControl->check(['Institutions', 'AssessmentItemResultsArchived', 'excel'], $roles));

    //         $url = Router::url([
    //             'plugin' => 'Institution',
    //             'controller' => 'Institutions',
    //             'action' => 'AssessmentItemResultsArchived',
    //             'excel',
    //             'queryString' => $queryString
    //         ]);

    //         $Assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments');
    //         $hasTemplate = $Assessments->checkIfHasTemplate($assessmentId);
    //         if ($hasTemplate) {

    //             $customUrl = Router::url([
    //                 'plugin' => 'Institution',
    //                 'controller' => 'Institutions',
    //                 'action' => 'reportCardGenerate',
    //                 'add',
    //                 'queryString' => $queryString
    //             ]);

    //             $this->set('reportCardGenerate', $customUrl);

    //             $exportPDF_Url = $this->ControllerAction->url('index');
    //             $exportPDF_Url['plugin'] = 'CustomExcel';
    //             $exportPDF_Url['controller'] = 'CustomExcels';
    //             $exportPDF_Url['action'] = 'exportPDF';
    //             $exportPDF_Url[0] = 'AssessmentResults';
    //             $this->set('exportPDF', Router::url($exportPDF_Url));
    //         }

    //         $this->set('excelUrl', $url);
    //         $this->set('ngController', 'InstitutionsAssessmentArchiveCtrl');
    //     }
    // }
    
    //POCOR-9620

    /**
     * @param $classId
     * @return mixed
     */
    private
    function getInstitutionClassName($classId)
    {
        $classes_table = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $myClass = $classes_table->get($classId);
        $myClassName = $myClass->get('name');
        return $myClassName;
    }

    public function InstitutionTransportProviders()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTransportProviders']);
    }

    public function Distribution()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionDistributions']);
    }

    //POCOR-6822 Starts

    public function ReportCardStatuses()
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $classId = $this->request->getQuery('class_id');
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        $reportCardId = $this->request->getQuery('report_card_id');

        // POCOR-6822: Stay on Report Card Statuses for "All Classes" so all roles (not only super admin)
        // see the list; ReportCardStatusesTable uses institution_class_id IN (class ids) when class_id=all.
        // Previously redirecting to ReportCardStatusProgress caused redirect to Dashboard for non-super-admin.
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardStatuses']);
    }//POCOR-6822 Ends

    public function ReportCardStatusProgress()
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $classId = $this->request->getQuery('class_id');
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        $reportCardId = $this->request->getQuery('report_card_id');
        if (!empty($classId) && $classId <> 'all') {
            return $this->redirect(['action' => 'ReportCardStatuses',
                '0' => 'index',
                '1' => $encodedQueryString,
                '?' => [ //POCOR-8773
                    'class_id' => $classId,
                    'academic_period_id' => $academicPeriodId,
                    'report_card_id' => $reportCardId
                ]
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

    public function ClassReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ClassProfiles']);
    }

    public function StaffTransferIn()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTransferIn']);
    }

    //POCOR-5677 start

    public function StaffTransferOut()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTransferOut']);
    }

    //POCOR-5677 ends

    public function StudentAdmission()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentAdmission']);
    }

    //POCOR-6028 start

    public function BulkStudentAdmission()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.BulkStudentAdmission']);
    }

    //POCOR-6028 ends
    //POCOR-8434 start
    public function StudentEnrolment()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentEnrolment']);
    }

    public function BulkStudentEnrolment()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.BulkStudentEnrolment']);
    }
    //POCOR-8434 Ends

    public function StudentTransferIn()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentTransferIn']);
    }

    public function BulkStudentTransferIn()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.BulkStudentTransferIn']);
    }

    public function StudentTransferOut()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentTransferOut']);
    }

    public function BulkStudentTransferOut()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.BulkStudentTransferOut']);
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

    // End

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

    public function ScheduleTimetableOverview()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Schedule.ScheduleTimetables']);
    }
    // Timetable - END

    //POCOR-5669 added InstitutionMaps

    public function ScheduleIntervals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Schedule.ScheduleIntervals']);
    }
    //POCOR-5669 added InstitutionMaps

    //POCOR-6122 add export button in calendar

    public function ScheduleTerms()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Schedule.ScheduleTerms']);
    }
    //POCOR-6122 add export button in calendar

    //POCOR-5683 added InstitutionStatusUpdate

    public function Committees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionTestCommittees']);
    }

    //POCOR-5182 added StaffSalaries

    public function CommitteeAttachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.CommitteeAttachments']);
    }
    //POCOR-5182 added StaffSalaries


    //POCOR-6145 added Export button in Infratucture > Wash > Waters

    public function InstitutionMaps()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionMaps']);
    }

    //POCOR-6148 add Export button on Institutions > Infrastructures > WASH > Waste

    public function InstitutionCalendars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Calendars']);
    }

    //POCOR-6146 added Export button in Infratucture > Wash > Sanitation

    public function InstitutionStatus()
    {
        // $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        // $session = $this->request->getSession();
        // $session->write('Institution.Institutions.id', $institutionId);
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

    //PCOOR-6146 add export button in Institutions > Infrastructures > WASH > Hygiene

    public function StaffSalaries()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffSalaries']);
    }

    //POCOR-6144 added Export button in Infratucture > Utilitie > Internet

    public function InfrastructureWashWaters()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashWaters']);
    }

    //POCOR-6143 added Export button in Infratucture > Utilitie > Electricity

    public function InfrastructureWashWastes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashWastes']);
    }
    //POCOR-6143 added Export button in Infratucture > Utilitie > Electricity

    //POCOR-6149 Add expor button on Add Export button function - Institutions > Infrastructures > WASH > Sewage

    public function InfrastructureWashSanitations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashSanitations']);
    }

    public function InfrastructureWashHygienes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashHygienes']);
    }

    //PCOOR-6146 add export button in Institutions > Infrastructures > WASH > Hygiene

    // AngularJS

    public function InfrastructureUtilityInternets()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureUtilityInternets']);
    }

    public function InfrastructureUtilityTelephones()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureUtilityTelephones']);
    }

    public function InfrastructureUtilityElectricities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureUtilityElectricities']);
    }

    public function InfrastructureWashSewages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureWashSewages']);
    }

    public function changeUtilitiesHeader($model, $modelAlias, $userType)
    {
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        if (!empty($institutionId)) {
            $activeInstitution = $this->Institutions->get($institutionId);
            $institutionName = $activeInstitution->name;
            if ($this->request->getParam('action') == 'InfrastructureUtilityElectricities') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Electricity');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Electricity'));
                $this->set('contentHeader', $header);
            } else if ($this->request->getParam('action') == 'InfrastructureWashWastes') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Waste');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Waste'));
                $this->set('contentHeader', $header);
            } else if ($this->request->getParam('action') == 'InfrastructureUtilityInternets') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Internet');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Internet'));
                $this->set('contentHeader', $header);
            } else if ($this->request->getParam('action') == 'InfrastructureUtilityTelephones') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Internet');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Internet'));
                $this->set('contentHeader', $header);
            } else if ($this->request->getParam('action') == 'InfrastructureWashWaters') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Water');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Water'));
                $this->set('contentHeader', $header);
            } else if ($this->request->getParam('action') == 'InfrastructureWashSanitations') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Sanitation');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Sanitation'));
                $this->set('contentHeader', $header);
            } else if ($this->request->getParam('action') == 'InfrastructureWashHygienes') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Hygiene');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Hygiene'));
                $this->set('contentHeader', $header);

            } else if ($this->request->getParam('action') == 'InstitutionAssets') { //POCOR-6152 Header breadcrumbs
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Assets');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Assets'));
            } else if ($this->request->getParam('action') == 'InfrastructureWashSewages') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Sewage');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Sewage'));
                // POCOR-6150 start
            } else if ($this->request->getParam('action') == 'InfrastructureNeeds') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Needs');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Needs'));
                $this->set('contentHeader', $header);
            }  else if ($this->request->getParam('action') == 'InfrastructureAttachments') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Attachments');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Attachments'));
                $this->set('contentHeader', $header);
            }

            // POCOR-6150 end

            // POCOR-6151 start
            else if ($this->request->getParam('action') == 'InfrastructureProjects') {
                //$institutionName = $session->read('Institution.Institutions.name');
                $header = $institutionName . ' - ' . __('Projects');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Projects'));
                $this->set('contentHeader', $header);
            }// POCOR-6151 end

        }

    }

    public function ScheduleTimetable($action = 'view')
    {
        $url = $_SERVER['REQUEST_URI'];
        /*$startPos = strpos($url, '/Institution/Institutions/ScheduleTimetable/view/') + strlen('/Institution/Institutions/ScheduleTimetable/view/');
        $encodedPart = substr($url, $startPos);*/
        //POCOR-9483 start
        $viewNeedle = '/Institution/Institutions/ScheduleTimetable/view/';
        $startPos = strpos($url, $viewNeedle);
        if ($startPos !== false) {
            $encodedPart = substr($url, $startPos + strlen($viewNeedle));
        } else {
            $editNeedle = '/Institution/Institutions/ScheduleTimetable/edit/';
            $startPos = strpos($url, $editNeedle);
            if ($startPos !== false) {
                $encodedPart = substr($url, $startPos + strlen($editNeedle));
            } else {
                $encodedPart = $url;
            }
        }//POCOR-9483 end

        $timetableId = $this->getQueryString('timetable_id');
        $params = $this->getQueryString();
        if(empty($timetableId)) {
            $timetableId = $params['id'];
        }

        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        $params['id'] = $timetableId;
        $encodedQueryString = $this->ControllerAction->paramsEncode($params);
        $backUrl = [
            'plugin' => $this->getPlugin(),
            'controller' => $this->getName(),
            'action' => 'ScheduleTimetableOverview',
            '0' => 'view',
            '1' => $encodedQueryString,
        ];

        $academicPeriodId = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')
            ->getCurrent();
        $this->set('_action', $action);
        // POCOR-8985 start only
        $roles = [];
        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);

        }
        $edit = $this->AccessControl->check(['Institutions', 'ScheduleTimetableOverview', 'edit'], $roles);
        $this->set('_edit', $edit);
        // POCOR-8985 end
        $this->set('_back', Router::url($backUrl));

        $user = $this->getRequest()->getSession()->read('sbn');
        $pass = $this->getRequest()->getSession()->read('nbn');
        $pass = $this->paramsEncode([$pass]);
        $institutionName = $this->Institutions->get($institutionId)->name;

        $this->set('encodedPart', $encodedPart);
        $this->set('timetable_id', $timetableId);
        $this->set('institutionDefaultId', $institutionId);
        $this->set('academicPeriodId', $academicPeriodId);
        $this->set('institutionName', $institutionName);

        $this->set('user', $user);
        $this->set('pass', $pass);
        $this->set('ngController', 'TimetableCtrl as $ctrl');
        //POCOR-9589: inject baseCoreUrl so Angular SPA resolves api/v4/ correctly on any deployment path
        $baseCoreUrl = $this->getRequest()->getSession()->read('System.baseCoreUrl');
        $this->set('baseCoreUrl', $baseCoreUrl);
        $this->render('timetable');
    }

    public function InstitutionStudentAbsencesArchived($pass = '')
    {
        if ($pass == 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentAbsencesPeriodDetailsArchive']);
        }
        if ($pass != 'excel') {
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

            $_excel = true;
            // POCOR-7895: start
            $institutionClassIds = $this->getInstitutionClasses($institutionId);
            $where = ['institution_id' => $institutionId];
            $whereClasses = ['institution_class_id IN' => $institutionClassIds];
            // $table_name = 'institution_class_attendance_records';
            // $_archive_1 = ArchiveConnections::hasArchiveRecords($table_name, $whereClasses);
            // $table_name = 'institution_student_absences';
            // $_archive_2 = ArchiveConnections::hasArchiveRecords($table_name, $where);
            // $table_name = 'institution_student_absence_details';
            // $_archive_3 = ArchiveConnections::hasArchiveRecords($table_name, $where);
            // POCOR-7895: end
            $excelUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'InstitutionStudentAbsencesArchived',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
                'excel'
            ];
            // POCOR-7895: start
            if ($_excel) {
//                if ($_archive_1 or $_archive_2 or $_archive_3) {
//                    $_excel = $_archive_1;
//                } else {
                    $_excel = false;
                    $excelUrl = null;
//                }
            }
            // POCOR-7895: end
            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));

            $this->Navigation->addCrumb($crumbTitle);

            $this->set('_excel', $_excel);
            $this->set('excelUrl', Router::url($excelUrl));
            $this->set('institution_id', $institutionId);
            $this->set('ngController', 'InstitutionStudentAttendancesArchiveCtrl as $ctrl');
        }
        // $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStudentAbsencesArchived']);
    }

    /**
     * @param $institutionId
     * @return array
     * POCOR-7895
     */
    private
    function getInstitutionClasses($institutionId)
    {
        $tableClasses = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $distinctClasses = $tableClasses->find('all')
            ->where(['institution_id' => $institutionId])
            ->select(['id'])
            ->distinct(['id'])
            ->toArray();
        $distinctClassValues = array_column($distinctClasses, 'id');
        $institutionClassIds = array_unique($distinctClassValues);
        return $institutionClassIds;
    }

    public function StudentAttendances($pass = '')
    {
        $institutionId = $this->getInstitutionId();
        $institutionName = $this->Institutions->get($institutionId)->name;
        $url = $_SERVER['REQUEST_URI'];
        $startPos = strpos($url, '/Institution/Institutions/StudentAttendances/index') + strlen('/Institution/Institutions/StudentAttendances/index');
        $encodedPart = substr($url, $startPos);
        $endPos = ltrim($encodedPart,'/');
        $baseUrl = Router::fullBaseUrl();

        if ($pass == 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentAttendances']);
        } else {
            // POCOR-7895: refactured, removed unnecessary lines
            $_edit = $this->AccessControl->check(['Institutions', 'StudentAttendances', 'edit']);

            $_import = $this->AccessControl->check(['Institutions', 'ImportStudentAttendances', 'add']);

            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

            //POCOR-8148:Start Check if institution is active
            $Institutions = self::getDynamicTableInstance('Institution.Institutions');
            $data = $Institutions->find()
            ->where(['id' => $institutionId])
            ->first();
            //echo "<pre>";print_r($data);exit;
            $_isActive = 1;
            if ($data->offsetExists('date_closed') && !empty($data['date_closed'])) {
                $todayDate = new Date();
                $dateClosed = new Date($data['date_closed']);
                if ($dateClosed < $todayDate) {
                    $_isActive = 0;
                }
            }
            //POCOR-8148:End

            // issue
            //POCOR-9615: Fixed URL - removed redundant second encoding
            $excelUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentAttendances',
                0 => 'excel',
                1 => $this->ControllerAction->paramsEncode(['id' => $institutionId,'institution_id' => $institutionId]), //POCOR-8886

            ];

            $importUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'ImportStudentAttendances',
                0 => 'add',
                1 => $this->ControllerAction->paramsEncode(['id' => $institutionId,'institution_id' => $institutionId]), //POCOR-8886
            ];
            $archiveUrl = $this->ControllerAction->url('index');
            $archiveUrl['plugin'] = 'Institution';
            $archiveUrl['controller'] = 'Institutions';
            $archiveUrl['action'] = 'InstitutionStudentAbsencesArchived';
            $archiveUrl[] = $this->ControllerAction->paramsEncode(['institution_id' => $institutionId]);
            $_archive = $_excel = 1;

            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
            $this->Navigation->addCrumb($crumbTitle);

            $this->set('_edit', $_edit);
            $this->set('_isActive', $_isActive);//POCOR-8148
            $this->set('_excel', $_excel);
            $this->set('_import', $_import);
            $this->set('_archive', $_archive);
            $this->set('excelUrl', Router::url($excelUrl));
            $this->set('importUrl', Router::url($importUrl));
            $this->set('archiveUrl', Router::url($archiveUrl));
            $this->set('institution_id', $institutionId);
            $this->set('encoded_url', $endPos);
            $this->set('baseUrl', $baseUrl);
            $this->set('institutionName', $institutionName);

            $this->set('ngController', 'InstitutionStudentAttendancesCtrl as $ctrl');

            // Start POCOR-5188
            $manualTable = TableRegistry::getTableLocator()->get('Manuals');
            $ManualContent = $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Import Student Admission',
                $manualTable->aliasField('module') => 'Institutions',
                $manualTable->aliasField('category') => 'Students',
            ])->first();

            if (!empty($ManualContent['url'])) {
                $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
            } else {
                $this->set('is_manual_exist', []);
            }
            // End POCOR-5188

        }
    }

    // End

    public function StudentMeals($pass = '')
    {
        $baseUrl = Router::fullBaseUrl();
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionId();
        $institutionName = $this->Institutions->get($institutionId)->name;
        $encodedParams = $this->ControllerAction->paramsEncode(['id' => $institutionId]);
        $institutionDashborad = "{$this->plugin}/Institutions/{$encodedParams}/dashboard/{$encodedParams}";
        $institutionIndex = "Institutions/Institutions/index/";

        if ($pass == 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentMeals']);
        } else {
             //POCOR-8051 start
             $url = $_SERVER['REQUEST_URI'];
             $startPos = strpos($url, '/Institution/Institutions/StudentMeals/index/') + strlen('/Institution/Institutions/StudentMeals/index/');
             $encodedPart = substr($url, $startPos);

             //POCOR-8051 end

            $_edit = $this->AccessControl->check(['Institutions', 'StudentMeals', 'edit']);
            $_excel = $this->AccessControl->check(['Institutions', 'StudentMeals', 'excel']);
            $_import = $this->AccessControl->check(['Institutions', 'ImportStudentMeals', 'add']);

            $_excel = true;

            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

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

            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));

            $this->Navigation->addCrumb($crumbTitle);
            //POCOR-8500 do not remove it. used in angular start
            $user = $this->getRequest()->getSession()->read('sbn');
            $pass = $this->getRequest()->getSession()->read('nbn');
            $pass = $this->paramsEncode([$pass]);
            //end
            $this->set('_edit', $_edit);
            $this->set('_excel', $_excel);
            $this->set('_import', $_import);
            $this->set('excelUrl', Router::url($excelUrl));
            $this->set('importUrl', Router::url($importUrl));
            $this->set('institution_id', $institutionId);
            $this->set('meal_url', $encodedPart);
            $this->set('institutionName', $institutionName);
            $this->set('institutionDashborad', $institutionDashborad);
            $this->set('institutionIndexUrl', $institutionIndex);
            $this->set('baseUrl', $baseUrl);
            $this->set('user', $user);
            $this->set('pass', $pass);
            $this->set('ngController', 'InstitutionStudentMealsCtrl as $ctrl');
            //POCOR-9633: inject baseCoreUrl so Angular api.service.ts resolves api/v4/ and api/v5/ correctly
            $baseCoreUrl = $this->getRequest()->getSession()->read('System.baseCoreUrl');
            $this->set('baseCoreUrl', $baseCoreUrl);
        }

    }

    public function StudentArchive()
    {
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

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

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
        $this->Navigation->addCrumb($crumbTitle);

        $this->set('archiveUrl', Router::url($archiveUrl));
        $this->set('institution_id', $institutionId);
        $this->set('ngController', 'InstitutionStudentArchiveCtrl as $ctrl');

    }

    public function Results()
    {
        $classId = $this->getQueryString('class_id');
        $assessmentId = $this->getQueryString('assessment_id');
        $institutionId = $this->getQueryString('institution_id');
        $academicPeriodId = $this->getQueryString('academic_period_id');
        $roles = [];

        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
        }

        $this->set('_roles', $roles);

        // POCOR-3983 check institution status
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);
        if ($isActive) {
            $_edit = $this->AccessControl->check(['Institutions', 'Results', 'edit'], $roles);
        } else {
            $_edit = false;
        }
        //POCOR-9487[START]
        $securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
        $securityFunctionsData = $securityFunctions
            ->find()
            ->select([
                'SecurityFunctions.id'
            ])
            ->where([
                'SecurityFunctions.name' => 'Assessments',
                'SecurityFunctions.controller' => 'Institutions',
                'SecurityFunctions.module' => 'Institutions',
                'SecurityFunctions.category' => 'Students'
            ])
            ->first();
        //POCOR-9491
        $permission_id = $_SESSION['Permissions']['Institutions']['Institutions']['index'];
        if(!empty($permission_id)){
            $securityRoleFunctions =  TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');

            $securityRoleFunctionsData = $securityRoleFunctions
            ->find('all')
            ->where([
                'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
                'SecurityRoleFunctions.security_role_id IN ' => $permission_id,
            ])
            ->toArray();

        $roleIds = array_map(function($entity) {
                  return $entity->security_role_id;
                  }, $securityRoleFunctionsData);
        if(!empty($roleIds)){
            $SecurityRoleTable = TableRegistry::get('Security.SecurityRoles');
            $SecurityRoleTableData = $SecurityRoleTable
            ->find('all')
            ->where([
                $SecurityRoleTable->aliasField('id IN ') => $roleIds
            ])
            ->toArray();
        }
        $hasPrincipal = 0;
        $isEditable = 0;

        foreach ($SecurityRoleTableData as $role) {
            if ($role->code === 'PRINCIPAL') {
                $hasPrincipal = 1;
                $securityRoleFunctionsData1 = $securityRoleFunctions
                ->find()
                ->where([
                'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
                'SecurityRoleFunctions.security_role_id' => $role->id,
                ])
                ->first();
                if($securityRoleFunctionsData1->_edit == 1){
                    $isEditable = 1;
                }
            }
        }
        if($hasPrincipal == 1 &&  $isEditable == 1){
            $_edit = true;
        }
        }
        //POCOR-9491

        //POCOR-9487
        // $permission_id = $_SESSION['Permissions']['Institutions']['Institutions']['edit'];
        // if(!empty($permission_id)){
        //     $securityRoleFunctions =  TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');

        //     $securityRoleFunctionsData = $securityRoleFunctions
        //     ->find()
        //     ->where([
        //         'SecurityRoleFunctions.security_function_id' => $securityFunctionsData->id,
        //         'SecurityRoleFunctions.security_role_id IN ' => $permission_id,
        //     ])
        //     ->first();
        //     }
        // if(!empty($securityRoleFunctionsData)){
        //     $SecurityRoleTable = TableRegistry::get('Security.SecurityRoles');
        //     $SecurityRoleTableData = $SecurityRoleTable
        //     ->find()
        //     ->where([
        //         $SecurityRoleTable->aliasField('id') => $securityRoleFunctionsData->security_role_id
        //     ])
        //     ->first();
        // }
        // if ($SecurityRoleTableData->code == 'PRINCIPAL') {
        //     if($securityRoleFunctionsData->_edit == 1){
        //         $_edit = true;
        //     }
        // }
        //POCOR-9487['End']


        // end POCOR-3983
        $queryString = $this->request->getQuery('queryString');
        $this->set('_edit', $_edit);
        $this->set('queryString', $queryString);
        $this->set('_excel', $this->AccessControl->check(['Institutions', 'Assessments', 'excel'], $roles));
        $url = $this->ControllerAction->url('index');
        $url['plugin'] = 'Institution';
        $url['controller'] = 'Institutions';
        $url['action'] = 'resultsExport';
        $url['?'] = ['queryString' => $queryString];
        // POCOR-8224  start
        if ($isActive) {
            $_exempt = $this->AccessControl->check(['Institutions', 'AssessmentItemExemptions', 'edit'], $roles);
        } else {
            $_exempt = false;
        }
        $this->set('_exempt', $_exempt);
        if($_exempt){
            $exemptUrl = $this->ControllerAction->url('edit');
            unset($exemptUrl['?']);
            $exemptUrl['action'] = 'AssessmentItemExemptions';
            $exemptUrl['0'] = 'edit';
            $exemptUrl['1'] = $queryString;
            $this->set('exemptUrl', Router::url($exemptUrl));
        }
        // POCOR-8224 end

        $Assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $hasTemplate = $Assessments->checkIfHasTemplate($assessmentId);
        if ($hasTemplate) {

            $customUrl = Router::url([
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'reportCardGenerate',
                'add',
                '?' => ['queryString' => $queryString]
            ]);

            $this->set('reportCardGenerate', $customUrl);

            $exportPDF_Url = $this->ControllerAction->url('index');
            $exportPDF_Url['plugin'] = 'CustomExcel';
            $exportPDF_Url['controller'] = 'CustomExcels';
            $exportPDF_Url['action'] = 'exportPDF';
            $exportPDF_Url[0] = 'AssessmentResults';
            $exportPDF_Url['?'] = ['queryString' => $queryString];
            $this->set('exportPDF', Router::url($exportPDF_Url));
        }
        //POCOR-8146 Start
        $labelsTable = self::getDynamicTableInstance('labels');
        $labelsData = $labelsTable->find()->where([
            $labelsTable->aliasField('module') => 'Institution Assessments',
            $labelsTable->aliasField('field') => 'total_mark'])->first();
        $dynamicTotalMarkHeader = $labelsData->name;
        if(empty($dynamicTotalMarkHeader)) {
            $dynamicTotalMarkHeader = $labelsData->code;
        }
        $this->set('dynamicTotalMarkHeader', $dynamicTotalMarkHeader);
        //POCOR-8146 End
        $this->set('excelUrl', Router::url($url));
        $this->set('ngController', 'InstitutionsResultsCtrl');
        $this->render('results');

    }
    
    //POCOR-9620
    public function AssessmentItemResultsArchived()
    {
        $classId = $this->getQueryString('class_id');
        $assessmentId = $this->getQueryString('assessment_id');
        $institutionId = $this->getQueryString('institution_id');
        $academicPeriodId = $this->getQueryString('academic_period_id');
        $roles = [];
        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
        }
        $this->set('_roles', $roles);
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);
        $_edit = $isActive && $this->AccessControl->check(['Institutions', 'Results', 'edit'], $roles);
        $queryString = $this->request->getQuery('queryString');
        $this->set('_edit', $_edit);
        $this->set('queryString', $queryString);
        $this->set('_excel', $this->AccessControl->check(['Institutions', 'Assessments', 'excel'], $roles));
        $url = $this->ControllerAction->url('index');
        $url['plugin'] = 'Institution';
        $url['controller'] = 'Institutions';
        $url['action'] = 'resultsExport';
        $url['?'] = ['queryString' => $queryString];

        $labelsTable = self::getDynamicTableInstance('labels');
        $labelsData = $labelsTable->find()->where([
            $labelsTable->aliasField('module') => 'Institution Assessments',
            $labelsTable->aliasField('field') => 'total_mark'])->first();
        $dynamicTotalMarkHeader = $labelsData->name;
        if(empty($dynamicTotalMarkHeader)) {
            $dynamicTotalMarkHeader = $labelsData->code;
        }
        $this->set('dynamicTotalMarkHeader', $dynamicTotalMarkHeader);
        //POCOR-8146 End
        $this->set('excelUrl', Router::url($url));
        $this->set('ngController', 'InstitutionsResultsArchivedCtrl');
        $this->render('results_archived');

    }
    //POCOR-9620

    // POCOR-8224 start
    public function AssessmentItemExemptions($subaction = 'index', $institutionSubjectId = null)
    {
        $AssessmentItemExemptions = self::getDynamicTableInstance('Institution.AssessmentItemStudentExemptions');
//        Log::debug('1');
        if ($subaction == 'edit') {
            $queryString = $this->getQueryString();

            // Institution Class Details
            $institution_class_id = $queryString['class_id'] ?? null;
            $institution_id = $queryString['institution_id'] ?? null;
            if (!is_numeric($institution_class_id) || $institution_class_id < 0) {
                return;
            }
            $institution_class = $AssessmentItemExemptions::getInstitutionClassDetails($institution_class_id);
            $this->set('institution_class_name', $institution_class->name);
            $this->set('institution_class_id', $institution_class_id);
            // Assessment Details
            $assessment_id = $queryString['assessment_id'] ?? null;
            if (!is_numeric($assessment_id) || $assessment_id < 0) {
                return;
            }
            $assessment = $AssessmentItemExemptions::getAssessmentDetails($assessment_id);
            $this->set('assessment_name', $assessment->name);
            $this->set('assessment_id', $assessment_id);

            // Education Grade Details
            $education_grade_id = $assessment->education_grade_id;
            $education_grade = $AssessmentItemExemptions::getEducationGradeDetails($education_grade_id);
            $this->set('education_grade_name', $education_grade->name);
            $this->set('education_grade_id', $education_grade_id);
            // Academic Period Details
            $academic_period_id = $queryString['academic_period_id'] ?? null;
            if (!is_numeric($academic_period_id) || $academic_period_id < 0) {
                return;
            }
            $academic_period = $AssessmentItemExemptions::getAcademicPeriodDetails($academic_period_id);
            $this->set('academic_period_name', $academic_period->name);
            $this->set('academic_period_id', $academic_period_id);

            // Institution Details
            $institution_id = $queryString['institution_id'] ?? null;
            if (!is_numeric($institution_id) || $institution_id < 0) {
                return;
            }
            $institution = $AssessmentItemExemptions::getInstitutionDetails($institution_id);
            $this->set('institution_name', $institution->name);
            $this->set('institution_id', $institution_id);

            // Assessment Items
            $assessment_items = $AssessmentItemExemptions::getAssessmentItems($assessment_id);
            $this->set('assessment_items', $assessment_items);

            // Assessment Periods
            $assessment_periods = $AssessmentItemExemptions::getAssessmentPeriods($assessment_id);
            $this->set('assessment_periods', $assessment_periods);

            // View-related settings
            $this->set('ngController', 'AssessmentItemExemptionsCtrl as AssessmentItemExemptionsController');
            // POCOR-9248 start
            $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
            $backUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Results',
                '1' => $encodedQueryString,
                '?' => ['queryString' => $encodedQueryString]
            ];
            // POCOR-9248 end
            $this->set('backUrl', $backUrl);
            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institution_id])
            ];
            $this->set('alertUrl', $alertUrl);
            $this->Navigation->addCrumb(__('Assessments'), ['plugin' => $this->plugin, 'controller' => 'Institutions', 'action' => 'Assessments', 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institution_id])]);
            $this->Navigation->addCrumb(__('Results'), $backUrl);
            $this->render('assessment_item_exemptions_edit');
        } else {
            // Handle other actions
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.AssessmentItemStudentExemptions']);
        }
    }

    public function saveAssessmentItemExemptions(){
        $userId = $this->Auth->user('id');
        $this->autoRender = false;
        $Exemptions = self::getDynamicTableInstance('Institution.AssessmentItemStudentExemptions');
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestDataParams = $requestData['params'];
        $requestDataParams['created_user_id'] = $userId;
//        Log::debug($requestDataParams);
        $assessment_item_ids = empty($requestDataParams['assessment_item_ids']) ? [$requestDataParams['assessment_item_id']] : $requestDataParams['assessment_item_ids'];
//        Log::debug($assessment_item_ids);
        // If multiple assessment period IDs exist, loop through each one
        foreach ($assessment_item_ids as $assessment_item_id) {
//            Log::debug($assessment_item_id);
            // Add exempt students for each assessment period
            $requestDataParams['assessment_item_id'] = $assessment_item_id;
            $Exemptions::saveExemptions($requestDataParams);
            $Exemptions::removeExemptions($requestDataParams);

        }
        echo json_encode(['status' => 'success']);
        die;

    }
    // POCOR-8224 end
    public function reportCardGenerate()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardGenerate']);
    }

    public function Comments()
    {
        // POCOR-3983 check institution status
        $baseUrl = Router::fullBaseUrl();
        $institutionId = $this->getQueryString('institution_id');
        $institutionClassId = $this->getQueryString('institution_class_id');
        $reportCardId = $this->getQueryString('report_card_id');
        $encodedUrl = $this->request->getParam('pass')[0];
        $institutionName = $this->Institutions->get($institutionId)->name;

        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);
        if ($isActive) {
            $_edit = $this->AccessControl->check(['Institutions', 'Comments', 'edit']);
        } else {
            $_edit = false;
        }
        $queryString = $this->ControllerAction->paramsEncode(['id' =>  $institutionId, 'institution_id'=>  $institutionId] );
        // echo "<pre>";print_r($this->request->getAttribute('params')['action']);die;
        // echo "<pre>";print_r($_SESSION);die;
        // end POCOR-3983
        $userId = $this->Auth->user('id');
        $this->set('loginUserId', $userId);
        $this->set('queryString', $queryString);
        $this->set('_edit', $_edit);
        $this->set('encodedUrl', $encodedUrl);
        $this->set('institutionClassId', $institutionClassId);
        $this->set('reportCardId', $reportCardId);
        $this->set('institutionName', $institutionName);
        $this->set('institutionId', $institutionId);
        $this->set('baseUrl', $baseUrl);
        $this->set('ngController', 'InstitutionCommentsCtrl as InstitutionCommentsController');
    }

    public function resultsExport()
    {
        $classId = $this->getQueryString('class_id');
        $assessmentId = $this->getQueryString('assessment_id');
        $institutionId = $this->getQueryString('institution_id');
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

        $ClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');

        $results = $ClassStudents->generateXLXS($settings);
        $fileName = $results['file'];
        $filePath = $results['path'] . $fileName;

        $response = $this->response;
        $response->getBody(function () use ($filePath) {
            $content = file_get_contents($filePath);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return $content;
        });

        // Syntax will change in v3.4.x
        $pathInfo = pathinfo($fileName);
        $response->withType($pathInfo['extension']);
        $response->withDownload($fileName);

        $response = $response->withFile($filePath, [
            'download' => true,
            'name' => $fileName,
        ]);
        return $response;
    }

    public function StudentCompetencies($subaction = 'index')
    {
        if ($subaction == 'edit') {
            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentCompetencies',
                '0' => 'index',
                '1' => $this->ControllerAction->paramsEncode(['id' => $institutionId, 'institution_id' => $institutionId])
            ];
            $this->Navigation->addCrumb($crumbTitle, $indexUrl);
            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'StudentCompetencies', $action], $roles)) {
                    $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'StudentCompetencies'];
                    return $this->redirect($url);
                }
            }
            $tabElements = $this->getCompetencyTabElements();
            $queryString = $this->ControllerAction->getQueryString();
            if (empty($queryString)) {
                $queryString = $this->getQueryString();
            }
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'StudentCompetencies';
            $viewUrl[0] = 'view';

            $param = ['id' => $queryString['class_id'], 'institution_class_id' => $queryString['class_id']];
            $param = array_merge($queryString, $param);
            $queryString = $param;
            $viewUrl['1'] = $this->ControllerAction->paramsEncode($param);
            $viewUrl['queryString'] = $this->ControllerAction->paramsEncode($queryString);

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

    public
    function getCompetencyTabElements($options = [])
    {
        $queryString = $this->request->getQuery('queryString');
        if (empty($queryString)) {
            $queryString = $this->request->getParam('pass')[1];
        }
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

    // Assosiation feature

    public function StudentCompetencyComments($subaction = 'index')
    {
        if ($subaction == 'edit') {
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentCompetencies',
                '0' => 'index',
                '1' => $this->ControllerAction->paramsEncode(['id' => $institutionId,
                    'institution_id' => $institutionId])
            ];
            $this->Navigation->addCrumb('Student Competencies', $indexUrl);

            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'StudentCompetencyComments', $action], $roles)) {
                    $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'StudentCompetencies'];
                    return $this->redirect($url);
                }
            }

            $tabElements = $this->getCompetencyTabElements();
            $queryString = $this->ControllerAction->getQueryString();
            if (empty($queryString)) {
                $queryString = $this->getQueryString();
            }
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'StudentCompetencyComments';
            $viewUrl[0] = 'view';

            $param = ['id' => $queryString['class_id'], 'institution_class_id' => $queryString['class_id']];
            $param = array_merge($queryString, $param);
            $queryString = $param;
            $viewUrl['1'] = $this->ControllerAction->paramsEncode($param);
            $viewUrl['queryString'] = $this->ControllerAction->paramsEncode($queryString);

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
            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentOutcomes',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];
            $this->Navigation->addCrumb($crumbTitle, $indexUrl);
            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'StudentOutcomes', $action], $roles)) {
                    $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'StudentOutcomes'];
                    return $this->redirect($url);
                }
            }
            //POCOR-8566[START]
            // $queryString = $this->ControllerAction->getQueryString();
            $queryString = $this->getQueryString();
            //POCOR-8566[END]
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
            $session = $this->request->getSession();
            $roles = [];
            $classId = $this->ControllerAction->paramsDecode($classId);
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            if (!$this->AccessControl->isAdmin() && $institutionId) {
                $userId = $this->Auth->user('id');
                $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
                $AccessControl = $this->AccessControl;
                $action = 'edit';
                if (!$AccessControl->check(['Institutions', 'AllClasses', $action], $roles)) {
                    if ($AccessControl->check(['Institutions', 'Classes', $action], $roles)) {
                        $ClassTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');

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
                            $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'Classes'];
                            return $this->redirect($url);
                        }
                    } else {
                        $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]), 'action' => 'Classes'];
                        return $this->redirect($url);
                    }
                }
            }
            $viewUrl = $this->ControllerAction->url('view');
            $viewUrl['action'] = 'Classes';
            $viewUrl[0] = 'view';
            //$viewUrl[1] = $this->ControllerAction->paramsEncode(['id' => $classId['id'], 'institution_id' => $institutionId]);//POCOR-8323
            $viewUrl[1] = $this->ControllerAction->paramsEncode(['id' => $classId['id'], 'institution_id' => $institutionId, 'institution_class_id' => $classId['id']]);//POCOR-8323
            //POCOR-8107
            $configItems = self::getDynamicTableInstance('Configuration.ConfigItems');
            $configItemsData = $configItems->find()->where(['type' => 'Fields for Institutions Classes Details Page'])->toArray();
            foreach ($configItemsData as $configItemsData1) {
                if (($configItemsData1['code'] == 'class_ins_unit') && ($configItemsData1['value'] == 0)) {
                    $unitEnable = 0;
                } elseif (($configItemsData1['code'] == 'class_ins_unit') && ($configItemsData1['value'] == 1)) {
                    $unitEnable = 1;
                }
                if (($configItemsData1['code'] == 'class_ins_course') && ($configItemsData1['value'] == 0)) {
                    $courseEnable = 0;
                } elseif (($configItemsData1['code'] == 'class_ins_course') && ($configItemsData1['value'] == 1)) {
                    $courseEnable = 1;
                }
            }
            $viewUrl['unit_field'] = $unitEnable;
            $viewUrl['course_field'] = $courseEnable;
            //POCOR-8323 Starts
            $requestQuery = $this->request->getQuery();
            if(isset($requestQuery)){
                if($requestQuery['academic_period_id']){
                    $viewUrl['academic_period_id'] = $requestQuery['academic_period_id'];
                }
                if($requestQuery['education_grade_id']){
                    $viewUrl['education_grade_id'] = $requestQuery['education_grade_id'];
                }
            } //POCOR-8323 Ends

            //POCOR-8107

            $indexUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                'index',//POCOR-8323
                //'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
                $this->ControllerAction->paramsEncode(['id' => $institutionId, 'institution_id' => $institutionId])//POCOR-8323
            ];

            $alertUrl = [
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'setAlert',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])
            ];
            //POCOR-9526 start
            $LabelTable = TableRegistry::get('Labels');
            $secondarystaff = $LabelTable->find()->where(['module_name' => 'Institutions -> Classes', 'field' => 'secondary_staff_id'])->first();
            if (!empty($secondarystaff)) {
                $secondarystaffName = !empty($secondarystaff->name)
                    ? (string)$secondarystaff->name
                    : (string)$secondarystaff->field_name;
            } else {
                $secondarystaffName = 'Secondary Teacher';
            }

            $homeRoomTeacher = $LabelTable->find()->where(['module_name' => 'Institutions -> Classes', 'field' => 'staff_id'])->first();
            if (!empty($homeRoomTeacher)) {
                if (!empty($homeRoomTeacher->code) && !empty($homeRoomTeacher->name)) {
                    $homeRoomTeacherName = $homeRoomTeacher->code . ' ' . $homeRoomTeacher->name;
                } elseif (!empty($homeRoomTeacher->name)) {
                    $homeRoomTeacherName = $homeRoomTeacher->name;
                } else {
                    $homeRoomTeacherName = $homeRoomTeacher->field_name;
                }
            } else {
                $homeRoomTeacherName = 'Home Room Teacher';
            }
            //POCOR-9526 end
            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('classId', $classId['id']);
            $this->set('institutionId', $institutionId);
            $this->set('secondarystaffName', $secondarystaffName); //POCOR-9526
            $this->set('homeRoomTeacherName', $homeRoomTeacherName); // POCOR-9526
            $this->render('institution_classes_edit');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionClasses']);
        }
    }

    // POCOR-9243 start
    public function Subjects($subaction = 'index', $encodedId = null)
    {
        // ─── INDEX CASE ─────────────────────────────────────────────────────────
        if ($subaction !== 'edit') {
            $this->ControllerAction->process([
                'alias'     => __FUNCTION__,
                'className' => 'Institution.InstitutionSubjects',
            ]);
            return;
        }

        // ─── EDIT CASE ──────────────────────────────────────────────────────────

        // 1) Decode the incoming subject ID
        $decoded = $this->ControllerAction->paramsDecode($encodedId);
        $subjectId = is_array($decoded) && isset($decoded['id'])
            ? (int)$decoded['id']
            : (int)$decoded;

        // 2) Determine current institution
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
// URL for “Back to Subjects” in InstitutionsController
        $indexUrl = [
            'plugin'     => 'Institution',
            'controller' => 'Institutions',
            'action'     => 'Subjects',
            0 => 'index',
            1            => $this->ControllerAction->paramsEncode([
                'id'             => $institutionId,
                'institution_id'=> $institutionId,
            ]),
        ];
        // 3) Authorization checks
        $isAdmin = $this->AccessControl->isAdmin();
        $userId  = $this->Auth->user('id');
        $roles   = TableRegistry::getTableLocator()
            ->get('Institution.Institutions')
            ->getInstitutionRoles($userId, $institutionId);

        $canEditAll   = $this->AccessControl->check(
            ['Institutions', 'AllSubjects', 'edit'],
            $roles
        );
        $canEditOwn   = $this->AccessControl->check(
            ['Institutions', 'Subjects', 'edit'],
            $roles
        );

        // If not admin or “edit all,” enforce finer-grained rules
        if (! $isAdmin && $institutionId && ! $canEditAll) {
            if ($canEditOwn) {
                // Teacher can only edit if assigned to this subject
                $subject = TableRegistry::getTableLocator()
                    ->get('Institution.InstitutionSubjects')
                    ->get($subjectId, ['contain' => ['Teachers']]);

                $teacherIds = array_column($subject->teachers, 'id');

                if (!in_array($userId, $teacherIds, true)) {
                    // redirect back to subject list
                    return $this->redirect($indexUrl);
                }
            } else {
                // No edit permission at all: go back to institution’s Subjects list
                return $this->redirect($indexUrl);
            }
        }

        // ─── PREPARE VIEW VARIABLES ─────────────────────────────────────────────

        // URL for “View” button
        $viewUrl = [
            'plugin'     => 'Institution',
            'controller' => 'Institutions',
            'action'     => 'Subjects',
            0            => 'view',
            1            => $this->ControllerAction->paramsEncode([
                'id'                     => $subjectId,
                'institution_id'         => $institutionId,
                'institution_subject_id' => $subjectId,
            ]),
        ];

        // URL for setting alerts
        $alertUrl = [
            'plugin'       => 'Configuration',
            'controller'   => 'Configurations',
            'action'       => 'setAlert',
            0            => $this->ControllerAction->paramsEncode([
                'id'             => $institutionId,
                'institution_id'=> $institutionId,
            ]),
        ];

        // Pass data to the view
        $this->set(compact(
            'viewUrl',
            'indexUrl',
            'alertUrl',
            'subjectId',
            'institutionId'
        ));

        // 4) Render the edit template
        $this->render('institution_subjects_edit');
    }
    // POCOR-9243 end


    public function Students($pass = 'index')
    {
        if ($pass == 'add') {
            $roles = [];
            //POCOR-7485 starts for localstorage for angular 11
            $userId = $this->Auth->user('id');
            // $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            $session = $this->request->getSession();
            $session->write('Institution.Institutions.id', $institutionId);
            $institutionName = $this->Institutions->get($institutionId)->name;
            if (!$this->AccessControl->isAdmin()) {
                $roles = self::getDynamicTableInstance('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
            }
            $this->set('institutionId', $institutionId);
            $this->set('institutionName', $institutionName);
            $this->set('loginUserId', $userId);
            //POCOR-7485 ends
            $this->set('ngController', 'InstitutionsStudentsCtrl as InstitutionStudentController');
            $this->set('_createNewStudent', $this->AccessControl->check(['Institutions', 'getUniqueOpenemisId'], $roles));
            $externalDataSource = false;
            $ConfigItemTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $externalSourceType = $ConfigItemTable->find()->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])->first();
            if (!empty($externalSourceType) && $externalSourceType['value'] != 'None') {
                $externalDataSource = true;
            }
            //POCOR-8646 Start
            $labelsTable = self::getDynamicTableInstance('labels');
            $labelsData = $labelsTable->find()->where(
                [$labelsTable->aliasField('module') => 'InstitutionStudentAdd',
                    $labelsTable->aliasField('field') => 'openemis_no'])
                ->first();
            $dynamicCol = $labelsData->name;
            if(empty($dynamicCol)) {
                $dynamicCol = $labelsData->code;
            }

            $this->set('dynamicOpenemisNoHeader', $dynamicCol);
            //POCOR-8646 End
            $this->set('externalDataSource', $externalDataSource);

            $this->render('studentAdd');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Students']);
        }
    }

    //POCOR-5672 starts

    public function Staff($pass = 'index')
    {
        if ($pass == 'add') {

            $session = $this->request->getSession();
            $roles = [];
            //POCOR-7485 starts for localstorage for angular 11
            $userId = $this->Auth->user('id');
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            $session = $this->request->getSession();
            $session->write('Institution.Institutions.id', $institutionId);
            $institutionName = $this->Institutions->get($institutionId)->name;
            if (!$this->AccessControl->isAdmin()) {
                $roles = self::getDynamicTableInstance('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
            }
            $this->set('institutionId', $institutionId);
            $this->set('institutionName', $institutionName);
            $this->set('loginUserId', $userId);
            //POCOR-7485 ends
            //POCOR-8646 Start
            $labelsTable = self::getDynamicTableInstance('labels');
            $labelsData = $labelsTable->find()->where(
                [$labelsTable->aliasField('module') => 'InstitutionStaffAdd',
                    $labelsTable->aliasField('field') => 'openemis_no'])
                ->first();$dynamicCol = $labelsData->name;
            if(empty($dynamicCol)) {
                $dynamicCol = $labelsData->code;
            }
            $this->set('dynamicOpenemisNoHeader', $dynamicCol);
            //POCOR-8646 End
            $this->set('ngController', 'InstitutionsStaffCtrl as InstitutionStaffController');
            $this->set('_createNewStaff', $this->AccessControl->check(['Institutions', 'getUniqueOpenemisId'], $roles));
            $externalDataSource = false;
            $ConfigItemTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $externalSourceType = $ConfigItemTable->find()->where([$ConfigItemTable->aliasField('code') => 'external_data_source_type'])->first();
            if (!empty($externalSourceType) && $externalSourceType['value'] != 'None') {
                $externalDataSource = true;
            }
            $this->set('externalDataSource', $externalDataSource);
            $this->render('staffAdd');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Staff']);
        }
    }//POCOR-5672 ends

    public function Associations($subaction = 'index', $associationId = null)
    {
        if ($subaction == 'add') {
            $session = $this->request->getSession();

            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
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

            $academicPeriodId = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getCurrent();
            $academicPeriodOptions = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getYearList();

            $this->set('alertUrl', $alertUrl);
            $this->set('viewUrl', $viewUrl);
            $this->set('indexUrl', $indexUrl);
            $this->set('academicPeriodId', $academicPeriodId);
            $this->set('academicPeriodName', $academicPeriodOptions[$academicPeriodId]);
            $this->set('institutionId', $institutionId);

            // Start POCOR-7466
            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            //$institutionName = $session->read('Institution.Institutions.name');
            $activeInstitution = $this->Institutions->get($institutionId);
            $institutionName = $activeInstitution->name;
            $this->Navigation->addCrumb('Houses', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'Associations', 'view']);
            $header = __($institutionName);
            $this->set('contentHeader', $header . ' - Houses');
            // END POCOR-7466


            $this->render('institution_associations');
        } else if ($subaction == 'edit') {
            $session = $this->request->getSession();
            $roles = [];
            $associationId = $this->ControllerAction->paramsDecode($associationId);
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
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

            // Start POCOR-7466
            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
            //$institutionName = $session->read('Institution.Institutions.name');
            $activeInstitution = $this->Institutions->get($institutionId);
            $institutionName = $activeInstitution->name;
            $this->Navigation->addCrumb('Houses', ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'Associations', 'view']);
            $header = __($institutionName);
            $this->set('contentHeader', $header . ' - Houses');
            // END POCOR-7466

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
            // POCOR-7895: refactured, removed unnecessary lines
            $_history = $this->AccessControl->check(['Staff', 'InstitutionStaffAttendanceActivities', 'index']);
            $_excel = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'excel']);
            $_ownView = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'ownview']);
            $_otherView = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'otherview']);
            $_permissionStaffId = $this->Auth->user('id');

            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

            $excelUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'InstitutionStaffAttendancesArchive',
                'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
                'excel'
            ];


            $this->set('_ownView', $_ownView);
            $this->set('_otherView', $_otherView);
            $this->set('_permissionStaffId', $_permissionStaffId);
            $this->set('_excel', $_excel);
            $this->set('_history', $_history);
            $this->set('institution_id', $institutionId);
            $this->set('excelUrl', Router::url($excelUrl));
            $this->set('ngController', 'StaffAttendancesArchivedCtrl as $ctrl');
        }
        // $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionStaffAttendancesArchive']);
    }

    public function InstitutionStaffAttendances($pass = 'index')
    {
        if ($pass == 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAttendances']);
        } else {

            $this->Navigation->addCrumb('Staff Attendance');

            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

            $this->setInstitutionStaffAttendancesEdit();

            $this->setInstitutionStaffAttendancesHistory();

            $this->setInstitutionStaffAttendancesOwnView();

            $this->setInstitutionStaffAttendancesOwnEdit();

            $this->setInstitutionStaffAttendancesOtherView();

            $this->setInstitutionStaffAttendancesOtherEdit();

            $this->setInstitutionStaffAttendancesPermissionStaffId();

            $this->setInstitutionStaffAttendancesExcel($institutionId);

            $this->setInstitutionStaffAttendancesImport($institutionId);

            $this->setInstitutionStaffAttendancesArchive($institutionId);

            $this->set('institution_id', $institutionId);

            $this->set('ngController', 'InstitutionStaffAttendancesCtrl as $ctrl');

            $this->setInstitutionStaffAttendancesManual();
        }
    }

    /**
     * common function to get _edit access control and set it for js
     *
     */
    private
    function setInstitutionStaffAttendancesEdit()
    {
        $_edit = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'edit']);
        $this->set('_edit', $_edit);
    }

    private
    function setInstitutionStaffAttendancesHistory()
    {
        $_history = $this->AccessControl->check(['Staff', 'InstitutionStaffAttendanceActivities', 'index']);
        $this->set('_history', $_history);
    }

    private
    function setInstitutionStaffAttendancesOwnView()
    {
        $_ownView = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'ownview']);
        $this->set('_ownView', $_ownView);
    }

    private
    function setInstitutionStaffAttendancesOwnEdit()
    {
        $_ownEdit = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'ownedit']);
        $this->set('_ownEdit', $_ownEdit);
    }

    private
    function setInstitutionStaffAttendancesOtherView()
    {
        $_otherView = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'otherview']);
        $this->set('_otherView', $_otherView);
    }

    private
    function setInstitutionStaffAttendancesOtherEdit()
    {
        $_otherEdit = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'otheredit']);
        $this->set('_otherEdit', $_otherEdit);
    }

    private
    function setInstitutionStaffAttendancesPermissionStaffId()
    {
        $_permissionStaffId = $this->Auth->user('id');
        $this->set('_permissionStaffId', $_permissionStaffId);
    }

    /**
     * @param $institutionId
     */
    private
    function setInstitutionStaffAttendancesExcel($institutionId)
    {
        $_excel = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'excel']);
        $this->set('_excel', $_excel);
        $excelUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InstitutionStaffAttendances',
            'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
            'excel'
        ];
        $this->set('excelUrl', Router::url($excelUrl));
    }

//autocomplete used for InstitutionSiteShift

    /**
     * @param $institutionId
     */
    private
    function setInstitutionStaffAttendancesImport($institutionId)
    {
        $_import = $this->AccessControl->check(['Institutions', 'ImportStaffAttendances', 'add']);
        // POCOR-8944 start
        $importUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'ImportStaffAttendances',
            0 => 'add',
            1 => $this->ControllerAction->paramsEncode(['id' => $institutionId,
        'institution_id' => $institutionId]),
        ];
        // POCOR-8944 end
        $this->set('importUrl', Router::url($importUrl));
        $this->set('_import', $_import);
    }

    /**
     * @param $institutionId
     * @throws Exception
     */
    private
    function setInstitutionStaffAttendancesArchive($institutionId)
    {
        // POCOR-7895: refactured, removed unnecessary
        $has_permission_to_view_archive = $_archive = $archiveUrl = true;

        if ($has_permission_to_view_archive) {
            $archiveUrl = $this->ControllerAction->url('index');
            $archiveUrl['plugin'] = 'Institution';
            $archiveUrl['controller'] = 'Institutions';
            $archiveUrl['action'] = 'StaffAttendancesArchived';
            $archiveUrl['0'] = 'index';
            $archiveUrl['1'] = $this->ControllerAction->paramsEncode(['institution_id' => $institutionId]);
        }
        $this->set('_archive', $_archive);
        $this->set('archiveUrl', Router::url($archiveUrl));
    }

    private
    function setInstitutionStaffAttendancesManual()
    {
        // Start POCOR-5188
        $manualTable = TableRegistry::getTableLocator()->get('Manuals');
        $ManualContent = $manualTable->find()->select(['url'])->where([
            $manualTable->aliasField('function') => 'Import Staff Attendances',
            $manualTable->aliasField('module') => 'Institutions',
            $manualTable->aliasField('category') => 'Staff',
        ])->first();

        if (!empty($ManualContent['url'])) {
            $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
        } else {
            $this->set('is_manual_exist', []);
        }
        // End POCOR-5188
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        //for api purpose POCOR-5672 starts
        if ($this->request->getParam('action') == 'getEducationGrade') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getEducationGrade';
        }
        if ($this->request->getParam('action') == 'getClassOptions') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getClassOptions';
        }
        if ($this->request->getParam('action') == 'getPositionType') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getPositionType';
        }
        if ($this->request->getParam('action') == 'getFTE') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getFTE';
        }
        if ($this->request->getParam('action') == 'getShifts') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getShifts';
        }
        if ($this->request->getParam('action') == 'getPositions') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getPositions';
        }
        if ($this->request->getParam('action') == 'getStaffType') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStaffType';
        }
        if ($this->request->getParam('action') == 'studentCustomFields') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'studentCustomFields';
        }
        //POCOR-8538 start
        if ($this->request->getParam('action') == 'classCustomFields') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'classCustomFields';
        }
        //POCOR-8538 end
        if ($this->request->getParam('action') == 'staffCustomFields') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'staffCustomFields';
        }
        if ($this->request->getParam('action') == 'saveStudentData') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveStudentData';
        }
        if ($this->request->getParam('action') == 'saveStaffData') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveStaffData';
        }
        if ($this->request->getParam('action') == 'saveGuardianData') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveGuardianData';
        }
        if ($this->request->getParam('action') == 'saveDirectoryData') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveDirectoryData';
        }
        if ($this->request->getParam('action') == 'getStudentTransferReason') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStudentTransferReason';
        }
        if ($this->request->getParam('action') == 'checkStudentAdmissionAgeValidation') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'checkStudentAdmissionAgeValidation';
        }
        if ($this->request->getParam('action') == 'getStartDateFromAcademicPeriod') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStartDateFromAcademicPeriod';
        }
        if ($this->request->getParam('action') == 'checkUserAlreadyExistByIdentity') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'checkUserAlreadyExistByIdentity';
        }
        if ($this->request->getParam('action') == 'checkConfigurationForExternalSearch') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'checkConfigurationForExternalSearch';
        }
        if ($this->request->getParam('action') == 'getStaffPosititonGrades') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStaffPosititonGrades';
        }
        if ($this->request->getParam('action') == 'getCspdData') { //POCOR-6930 starts
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getCspdData';
        }
        if ($this->request->getParam('action') == 'getConfigurationForExternalSourceData') { //POCOR-6930 starts
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getConfigurationForExternalSourceData';
        }
        //POCOR-6930 ends
        if ($this->request->getParam('action') == 'getStudentAdmissionStatus') {//POCOR-7716
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getStudentAdmissionStatus';
        }
        // POCOR-8224 start
        if ($this->request->getParam('action') == 'saveAssessmentItemExemptions') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'saveAssessmentItemExemptions';
        }
        // POCOR-8224 end

        //for api purpose POCOR-5672 ends

        return $events;
    }

    public function isActionIgnored(EventInterface $event, $action)
    {
        $pass = $this->request->getParam('pass');
        if (isset($pass[0]) && $pass[0] == 'downloadFile') {
            return true;
        }
    }

    public function changeUserHeader($model, $modelAlias, $userType)
    {
        $session = $this->request->getSession();
        // add the student name to the header
        $id = 0;
        if ($session->check('Staff.Staff.id')) {
            $id = $session->read('Staff.Staff.id');
        }
        if (!empty($id)) {
            $Users = TableRegistry::getTableLocator()->get('Security.Users');
            $entity = $Users->get($id);
            $name = $entity->name;
            $crumb = Inflector::humanize(Inflector::underscore($modelAlias));
            $header = $name . ' - ' . __($crumb);
            $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
            $this->Navigation->addCrumb('Staff', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff']);
            $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $userType, 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
            $this->Navigation->addCrumb($crumb);
            $this->set('contentHeader', $header);
        }
    }

    public function beforeFilter(EventInterface $event)
    {
        //POCOR-8587 start
        $session = $this->getRequest()->getSession();
        if (!$session->check('Auth.User.id')) {
            // Session has expired or user is not logged in
            return $this->redirect(['plugin' => 'User', 'controller' => 'Users', 'action' => 'login']);
        } //POCOR-8587 end
        parent::beforeFilter($event);
        $header = __('Institutions');
        $indexUrl = ['plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Institutions',
            'index'];
        $this->Navigation->addCrumb($header, $indexUrl);
        $isInstitutionIDSkipped = $this->isInstitutionIDSkipped();
        if ($isInstitutionIDSkipped) {
            $this->set('contentHeader', $header);
            return;
        }
        $request = $this->request;
        $session = $request->getSession();
        $pass = $request->getParam('pass');
        $action = $request->getParam('action');
        $controller = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $query = $request->getQuery();
        $header = __('Institutions');
        $this->deleteGuardianFromSession($action, $pass, $session);
//        die('<pre>'.print_r($request->getAttributes()));
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        try {
            $this->checkInstitutionAccess($institutionId, $event);
            if ($event->isStopped()) {
                return false;
            }
        } catch (SecurityException $ex) {
            die($ex->getMessage());
            return;
        }
        if (empty($institutionId)) {
            return;
        }
        $institutionName = "";
        if ($this->Institutions->exists([$this->Institutions->getPrimaryKey() => $institutionId])) {
            $activeInstitution = $this->Institutions->get($institutionId);
            $institutionName = $activeInstitution->name;

            $crumb = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'dashboard',
                0 => $this->ControllerAction->paramsEncode(['institution_id' => $institutionId])
            ];

            $this->Navigation->addCrumb($institutionName, $crumb);
            $this->set('institutionName', $institutionName);
        }
        if ($action == 'StudentUser') {
            $student_id = $this->getStudentID(__FUNCTION__ . ':' . __LINE__);
        }
        if ($action == 'StaffUser') {
            $staff_id = $this->getStaffID(__FUNCTION__ . ':' . __LINE__);
        }
        $header = $institutionName . ' - ' . __(Inflector::humanize(Inflector::underscore($action)));

        if ($action == 'view') {
            $header = $institutionName . ' - ' . __('Overview');
        }
        if ($action == 'Results') {
            // POCOR-4066 - add class name to header
            $classId = $this->getClassID(__FUNCTION__ . ':' . __LINE__);
            if (!empty($classId)) {
                $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                if ($InstitutionClasses->exists([$InstitutionClasses->getPrimaryKey() => $classId])) {
                    $classEntity = $InstitutionClasses->get($classId);
                    $header = $classEntity->name . ' - ' . __('Assessments');
                } else {
                    $header = $institutionName . ' - ' . __('Assessments');
                }
            }
            // End
        }

        if ($action == 'dashboard') {
            $roles = self::getDynamicTableInstance('Institution.Institutions')->getInstitutionRoles($this->Auth->user('id'), $institutionId);
            /*$havePermissionToViewCompleteness = $this->AccessControl->check([
                'Institutions',
                'InstitutionProfileCompletness',
                'view'],
                $roles);*/
            //POCOR-8827 start
            $roles = array_values($roles);
            $roles = $roles[0];
            $havePermissionToViewCompleteness = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions')->find()
                ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityFunctions.name' => 'Institution Profile Completeness',
                    'SecurityRoleFunctions.security_role_id IS'=> $roles,
                    'SecurityRoleFunctions._view' => 1,
                ])
                ->toArray();
                //POCOR-8827 end

            if ($havePermissionToViewCompleteness) {
                $header = $institutionName . ' - ' . __('Institution Data Completeness');//POCOR-6022
            } else {
                $header = $institutionName . ' - ' . __('Dashboard');
            }

        }
        //POCOR-8333 starts
        if ($action == 'StudentHistories') {
            $queryString = $this->getQueryString();
            $studentId = $queryString['security_user_id'] ?? $queryString['student_id'];
            $Students = TableRegistry::getTableLocator()->get('Security.Users');
            $activeStudent = $Students->get($studentId);
            $studentName = $activeStudent->name;
            $queryString = $this->getQueryString();
            $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
            $this->Navigation->addCrumb('Students',
                ['plugin' => $this->getPlugin(),
                    'controller' => 'Institutions',
                    'action' => 'Students',
                    '0' => 'index',
                    '1' => $encodedQueryString]);

            $queryString['id'] = $studentId;
            $queryString['student_id'] = $studentId;
            $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
            $this->Navigation->addCrumb($studentName,
                ['plugin' => $this->getPlugin(),
                    'controller' => 'Institutions',
                    'action' => 'StudentUser',
                    '0' => 'view',
                    '1' => $encodedQueryString]);
        }//POCOR-8333 ends
        // if ($action == 'StaffHistories') {
        //     $queryString = $this->getQueryString();
        //     $staffId = $queryString['security_user_id'] ?? $queryString['student_id'];
        //     $Students = TableRegistry::getTableLocator()->get('Security.Users');
        //     $activeStaff = $Students->get($staffId);
        //     $staffName = $activeStaff->name;
        //     $queryString = $this->getQueryString();
        //     $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        //     $this->Navigation->addCrumb('Students',
        //         ['plugin' => $this->getPlugin(),
        //             'controller' => 'Institutions',
        //             'action' => 'Staff',
        //             '0' => 'index',
        //             '1' => $encodedQueryString]);

        //     $queryString['id'] = $staffId;
        //     $queryString['security_user_id'] = $staffId;
        //     $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        //     $this->Navigation->addCrumb($staffName,
        //         ['plugin' => $this->getPlugin(),
        //             'controller' => 'Institutions',
        //             'action' => 'StaffUser',
        //             '0' => 'view',
        //             '1' => $encodedQueryString]);
        // }
        $this->set('contentHeader', $header);
    }

    /**
     * @return bool
     */

    public
    function isInstitutionIDSkipped(): bool
    {
        $request = $this->request;

        $pass = $request->getParam('pass');
        if (!is_array($pass)) {
            $pass = [];
        }
        $action = $request->getParam('action');
        $controller = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $furtherAction = $pass[0] ?? null;
        // Some deployments resolve the same URL with plugin unset or with action "add"/"index" and empty pass.
        $pluginInstitution = ($plugin === 'Institution' || $plugin === null || $plugin === '');
//        Log::debug(print_r([$action,
//            $controller,
//            $plugin,
//            $furtherAction],
//            true));
// POCOR-8224 start
        $primaryActions = [
            'checkUserAlreadyExistByIdentity',
            'saveGuardianData',
            'saveStudentData',
            'saveStaffData',
            'saveDirectoryData',
            'saveAssessmentItemExemptions',
            'classCustomFields', //POCOR-8538,
            'ImportInstitutions', // POCOR-8683
            'importInstitutions', // POCOR-8683

            'checkConfigurationForExternalSearch',
            'studentCustomFields',
            'staffCustomFields',
            'HistoryPdf',
        ];

        $furtherActions = [
            'removeReport',
            'downloadFailed',
            'downloadPassed',
            'template', //POCOR-9584: template is a file-download action; skip institution ID check (same as downloadFailed/downloadPassed)
        ];

        if (in_array($action, $primaryActions, true) || in_array($furtherAction, $furtherActions, true)) {
            return true;
        }
        // POCOR-8224 end

        if (in_array($action, ['add', 'index', 'import'], true)
            && $controller === 'Institutions'
            && $pluginInstitution) {
            return true;
        }

        // /Institution/Institutions (no extra path) resolves to action Institutions with empty pass — same as index; no institution context yet.
        if ($action === 'Institutions'
            && $controller === 'Institutions'
            && $pluginInstitution
            && ($furtherAction === null || $furtherAction === '')) {
            return true;
        }

        if (($furtherAction == 'index'
                || $furtherAction == 'add'
                || $furtherAction == 'import'
                || $furtherAction == 'saveGuardianData'
                || $action == 'saveGuardianData'
                || $furtherAction == 'excel'
            )
            && ($action == 'Institutions')
            && $pluginInstitution
            && ($controller == 'Institutions')) {
            return true;
        }
        if ($furtherAction == 'download'
            && ($action == 'Expenditure'
                || $action == 'Visits'
                || $action == 'Attachments')
            && $pluginInstitution
            && ($controller == 'Institutions')) {
            return true;
        }
        if (($furtherAction == 'view'
                || $furtherAction == 'edit' || $furtherAction =='remove')
            && $action == 'Institutions'
            && $pluginInstitution
            && $controller == 'Institutions') {
            return true;
        }
        // StaffBehaviours view/edit: skip role-based SecurityAuthorize here; checkInstitutionAccess in beforeFilter will enforce institution access (avoids redirect to Dashboard when roles are null or view link came from Staff plugin)
        if (($furtherAction == 'view' || $furtherAction == 'edit')
            && $action == 'StaffBehaviours'
            && $pluginInstitution
            && $controller == 'Institutions') {
            return true;
        }
        if ($furtherAction == 'image' || $furtherAction == 'download') {
            return true;
        }
        // POCOR-8683 start
        if (($furtherAction == 'add'
                || $furtherAction == 'template'
                ||  $furtherAction == 'results')
            && $action == 'ComponentAction') {
            return true;
        }
        // POCOR-8683 end
        if ($furtherAction == 'add'
            && $action == 'ImportInstitutions') {
            return true;
        }

        if ($furtherAction == 'ajaxInstitutionsAutocomplete') {
            return true;
        }
        if ($furtherAction == 'ajaxAssessorAutocomplete') { // POCOR-9061
            return true;
        }
        // POCOR-7799 start
        if ($furtherAction == 'downloadPassed' ) {
            return true;
        }
        if ($furtherAction == 'downloadFailed') {
            return true;
        }
        // POCOR-7799 end
//        $this->log(print_r($request,true), debug);
        return false;
    }

    /**
     * @param $action
     * @param $pass
     * @param Session $session
     * @return void
     */
    public
    function deleteGuardianFromSession($action, $pass, Session $session): void
    {
        if (($action == 'StudentUser' || $action == 'StaffUser')
            && (empty($pass)
                || $pass[0] == 'view')) {
            $session->delete('Guardian.Guardians.id');
            $session->delete('Guardian.Guardians.name');
        }
    }

    private function checkInstitutionAccess($id, $event)
    {
        if (!$this->AccessControl->isAdmin()) {
            $institutionIds = $this->AccessControl->getInstitutionsByUser();

            if (!array_key_exists($id, $institutionIds)) {

                $this->Alert->error('security.noAccess');
                // If user came from Staff Behaviours view, send back to Staff Behaviours index instead of Institution index (which redirects to Dashboard)
                $action = $this->request->getParam('action');
                $pass = $this->request->getParam('pass');
                if ($action == 'StaffBehaviours' && !empty($pass[1])) {
                    try {
                        $decoded = $this->paramsDecode($pass[1]);
                        $institutionId = $decoded['institution_id'] ?? $id;
                        $staffId = $decoded['staff_id'] ?? null;
                        if (empty($staffId) && !empty($decoded['id'])) {
                            $StaffBehaviours = TableRegistry::getTableLocator()->get('Institution.StaffBehaviours');
                            $behaviour = $StaffBehaviours->get($decoded['id'], ['fields' => ['staff_id']]);
                            if ($behaviour) {
                                $staffId = $behaviour->staff_id;
                            }
                        }
                        if ($institutionId || $staffId) {
                            $params = array_filter([
                                'institution_id' => $institutionId,
                                'staff_id' => $staffId,
                                'user_id' => $decoded['user_id'] ?? $staffId,
                            ]);
                            if (!empty($params)) {
                                $url = [
                                    'plugin' => 'Staff',
                                    'controller' => 'Staff',
                                    'action' => 'Behaviours',
                                    '0' => 'index',
                                    '1' => $this->ControllerAction->paramsEncode($params),
                                ];
                                $event->stopPropagation();
                                return $this->redirect($url);
                            }
                        }
                    } catch (\Exception $e) {
                        // fall through to default redirect
                    }
                }
                $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'index'];
                $event->stopPropagation();

                return $this->redirect($url);
            }
        }
    }

// Delete commitee meeting

    public
    function getStudentID($debugString = "")
    {
        // POCOR-8115;
        // student_id should always be in query string, if not, die as an error
        $student_id = $this->getQueryString('student_id');
        if (!$student_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put student_id into query string first');
            }
        }
        return $student_id;
    }

    public
    function getStaffID($debugString = "")
    {
        // POCOR-8115;
        // staff_id should always be in query string, if not, die as an error
        $staff_id = $this->getQueryString('staff_id');
        if (!$staff_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put staff_id into query string first');
            }
        }
        return $staff_id;
    }

    /*POCOR-6286 starts*/

    public
    function getClassID($debugString = "")
    {
        // POCOR-8115;
        // class_id should always be in query string, if not, die as an error
        $class_id = $this->getQueryString('class_id');
        if (!$class_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put class_id into query string first');
            }
        }
        return $class_id;
    }

    public
    function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $isInstitutionIndex = $this->isInstitutionIDSkipped();
        $alias = $model->getAlias();
        if ($isInstitutionIndex) {
            return;
        }

        $institutionID = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        if ($this->Institutions->exists([$this->Institutions->getPrimaryKey() => $institutionID])) {
            $activeInstitution = $this->Institutions->get($institutionID);
            $institutionName = $activeInstitution->name;
            $tranlatedInstitutionName = __($institutionName);
            $this->set('contentHeader', $tranlatedInstitutionName);
            $this->set('institutionName', $tranlatedInstitutionName);
        } else {
            $alias = $model->alias;
            if ($alias == 'InstitutionMaps') {
                return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
            }
            $event->stopPropagation();
            die('No Such Institution');
            return;
        }
        $params = $this->request->getAttribute('params');
        if (isset($params['pass'][0]) && !in_array($alias, ['Infrastructures', 'Rooms'])) {
            $action = $params['pass'][0];
        }
        $isDownload = $action == 'downloadFile' ? true : false;


        $humanTitle = __(Inflector::humanize(Inflector::underscore($alias)));
        //POCOR-8333 starts
        if ($alias == 'StudentHistories') {
            $humanTitle = __(Inflector::humanize(Inflector::underscore('History')));
        }//POCOR-8333 ends

        $crumbOptions = [];
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        if ($action) {
            $crumbOptions = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $alias,
                '0' => $action,
                '1' => $encodedQueryString];
        }

        // POCOR-3983 to disable add/edit/remove action on the model depend when inactive
        $this->getStatusPermission($model);
        $studentModels = [
            'StudentProgrammes' => __('Programmes'),
            'StudentRisks' => __('Risks'),
            'StudentTextbooks' => __('Textbox'),
            'StudentAssociations' => __('Houses'), //POCOR-7938
            'StudentCurriculars' => __('Curriculars') //POCOR-6673 in student tab breadcrumb
        ];

        if (isset($studentModels[$alias])) {
            $studentID = $this->getStudentID(__FUNCTION__ . __LINE__);
            $Students = TableRegistry::getTableLocator()->get('Security.Users');

            if ($Students->exists([$Students->getPrimaryKey() => $studentID])) {
                $activeStudent = $Students->get($studentID);
                $studentName = $activeStudent->name;
                $header = __($studentName);
                $this->set('contentHeader', $header);
                $this->set('studentName', $header);

                $this->Navigation->addCrumb('Students',
                    [
                        'plugin' => $this->getPlugin(),
                        'controller' => 'Institutions',
                        'action' => 'Students',
                        '0' => 'index',
                        '1' => $encodedQueryString]);
                $queryString['id'] = $studentID;
                $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
                $this->Navigation->addCrumb($studentName,
                    ['plugin' => $this->getPlugin(),
                        'controller' => 'Institutions',
                        'action' => 'StudentUser',
                        '0' => 'view',
                        '1' => $encodedQueryString]);

                $this->Navigation->addCrumb($studentModels[$alias]);
            } else {
                $event->stopPropagation();
                die('No Such Student');
                return;
            }
            // Breadcrumb
        }

        if ($alias == 'CommitteeAttachments') {
            $this->Navigation->addCrumb('Committees',
                ['plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Committees',
                    '0' => 'index',
                    '1' => $encodedQueryString]);
            $this->Navigation->addCrumb('Attachments');
            $this->set('contentHeader', $tranlatedInstitutionName);
        }
        if ($alias == 'InstitutionMaps') {
            $this->Navigation->addCrumb('InstitutionMaps',
                ['plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'InstitutionMaps',
                    '0' => 'view',
                    '1' => $encodedQueryString]);
            $this->set('contentHeader', $tranlatedInstitutionName);
        } //End: POCOR-7048
        // Start POCOR-7466
        if ($alias == 'InstitutionAssociations') {
            $this->Navigation->addCrumb('Houses',
                ['plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Associations',
                    '0' => 'view',
                    '1' => $encodedQueryString]);
            $this->set('contentHeader', $tranlatedInstitutionName);
        }
        //POCOR-8324 Starts
        if($alias == 'Subjects' ){
            if($action == 'index'){
                $this->Navigation->addCrumb($humanTitle);
            } else{
                $crumbOptions = [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => $alias,
                    '0' => 'index',
                    '1' => $encodedQueryString];
                $this->Navigation->addCrumb($humanTitle, $crumbOptions);
            }
            $header = $tranlatedInstitutionName;
            $this->set('contentHeader', $header);
        }//POCOR-8324 Ends

        $modelsWithChangedName = ['CommitteeAttachments',
            'InstitutionMaps',
            'InstitutionAssociations',
            'Subjects'];//POCOR-8324 add Subjects
        if (!in_array($alias, $modelsWithChangedName)) {
            $this->Navigation->addCrumb($humanTitle, $crumbOptions);
            $header = $tranlatedInstitutionName;
        }


        $persona = null;
        $requestQuery = $this->request->getQuery();
        $user_id = $this->getUserID();

        if (!$user_id) {
            $user_id = $this->getStudentID();
        }
        if (!$user_id) {
            $user_id = $this->getStaffID();
        }

        if (isset($params['pass'][1])) {
            if ($model->getTable() == 'security_users' && !$isDownload) {
                if (count(explode('.', $params['pass'][1])) != 2) {
                } else {
                    if (is_numeric($user_id) && $user_id > 0) {
                        $persona = $model->get($user_id);
                    }
                }
            }
        } elseif (isset($requestQuery['user_id'])) {
            // POCOR-4577 - to check if Users association existed in model - for staff leave import
            if ($model->getAssociation('Users')) {
                $persona = $model->Users->get($user_id);
            } else {
                $Users = TableRegistry::getTableLocator()->get('Security.Users');
                $persona = $Users->get($user_id);
            }
        }
        $subHeader = $model->getHeader($alias);


        if (is_object($persona) && get_class($persona) == 'User\Model\Entity\User') {
            $header = $persona->name . ' - ' . $humanTitle;
            $model->addBehavior('Institution.InstitutionUserBreadcrumbs');
        }
        if ($alias == 'StudentUser' || $alias == 'StudentAccount') {
            $this->set('contentHeader', $header);
        }
        if ($alias == 'IndividualPromotion') {
            $subHeader = __('Individual Promotion / Repeat');
        }
        if ($alias == 'StudentRisks') {
            $subHeader = __('Risks');
        }
        if ($alias == 'Indexes') {
            $subHeader = __('Risks');
            $this->Navigation->substituteCrumb($subHeader, __('Risks'));
        }
        if ($alias == 'InstitutionStudentRisks') {
            $subHeader = __('Institution Student Risks');
            $this->Navigation->substituteCrumb($subHeader, __('Institution Student Risks'));
        }
        if ($alias == 'InstitutionAssociationStudent') {
            $subHeader = __('Associations');
        }
        if ($alias == 'InstitutionStatistics') {
            $subHeader = __('Statistics');
        }
        if ($alias == 'StudentCurriculars') { //POCOR-6673
            $subHeader = __('Curriculars');
        } // Start POCOR-7466
        if ($alias == 'InstitutionAssociations') {
            $subHeader = __('Houses');
        } // END POCOR-7466
        $header .= ' - ' . $subHeader;

        $event = new Event('Model.Navigation.breadcrumb', $this, [$this->request, $this->Navigation, $persona]);
        $event = $model->getEventManager()->dispatch($event);
        $params = [];
        if ($model->hasField('institution_id')) {
            if (!in_array($alias, ['StudentTransferIn', 'StudentTransferOut'])) {
                $model->fields['institution_id']['type'] = 'hidden';
                $model->fields['institution_id']['value'] = $institutionID;
            }
            if (!empty($queryString)) {
                $primaryKey = $model->getPrimaryKey();

                $params = [];

                if (is_array($primaryKey)) {
                    foreach ($primaryKey as $key) {
                        $params[$model->aliasField($key)] = $queryString[$key];
                    }
                } else {
                    $params[$primaryKey] = $queryString[$primaryKey];
                }
                $exists = false;

                if (in_array($alias, ['StaffTransferOut', 'StudentTransferOut'])) {
                    $params[$model->aliasField('previous_institution_id IS')] = $institutionID;
                    $pass = $this->getRequest()->getParam('pass');
                    $furtherAction = $pass[0];
                    if ($furtherAction == 'add') {
                        return true;
                    }
                    $exists = $model->exists($params);
                } elseif (in_array($alias, ['InstitutionShifts'])) { //this is to show information for the occupier
                    $params['OR'] = [
                        $model->aliasField('institution_id') => $institutionID,
                        $model->aliasField('location_institution_id') => $institutionID
                    ];
                    $exists = $model->exists($params);
                } elseif (in_array($alias, ['FeederOutgoingInstitutions'])) {
                    $params = [];
                    $params[$model->aliasField('feeder_institution_id')] = $institutionID;

                    if(isset($this->request->getParam('pass')['0']) && $this->request->getParam('pass')['0'] == 'add') {//POCOR-8691
                        $exists = true;
                    } else {
                        $exists = $model->exists($params);
                    }
                }elseif (in_array($alias, ['InstitutionAssociations'])) { //POCOR-8556
                    $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
                    $activeInstitution = $this->Institutions->get($institutionId);
                    $institutionName = $activeInstitution->name;
                    $header = $institutionName.''.$header;
                } elseif (in_array($alias, ['InstitutionAttachments'])) { //POCOR-8695
                    $exists = true;
                } else {
                    $params = [];
                    $checkExists = function ($model, $params) {
                        return $model->exists($params);
                    };
                    $event = $model->dispatchEvent('Model.isRecordExists', [], $this);
                    if (is_callable($event->getResult())) {
                        $checkExists = $event->getResult();
                    }
                    //echo "<pre>"; print_r($params); die;
                    $params[$model->aliasField('institution_id IS')] = $institutionID;
                    $exists = $checkExists($model, $params);
                }
                if ($exists != true) {
                    $checkExists = function ($model, $params) {
                        return $model->exists($params);
                    };
                    $params = [];
                    $params[$model->aliasField('institution_id IS')] = $institutionID;
                    $exists = $checkExists($model, $params);

                }
                /**
                 * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                 */

                // replaced 'action' => $alias to 'action' => $model->alias, since only the name changes but not url
                if (!$exists && !$isDownload) {
                    if(isset($this->request->getParam('pass')['0']) && $this->request->getParam('pass')['0'] != 'add') {//POCOR-8691
                        $this->Alert->info('general.notExists');//POCOR-8691
                    }
                    //                    die('Entity of ' . $alias . ' with shown params ' . print_r($params, true) . 'does not exist');
                    //                        return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $model->alias]);
                }
            }

            $this->set('contentHeader', $header);
        } else {
            if ($alias == 'ImportInstitutions') {
                $this->Navigation->addCrumb($model->getHeader($alias));
                $header = __('Institutions') . ' - ' . $model->getHeader($alias);
                $this->set('contentHeader', $header);
            } else if ($alias == 'StudentHistories') {//POCOR-8333 starts
                $Students = TableRegistry::getTableLocator()->get('Security.Users');
                $user_id = $this->getUserID();
                $activeStudent = $Students->get($user_id);
                $studentName = $activeStudent->name;
                $header = __($studentName) . ' - ' . __('History');
                $this->set('contentHeader', $header);//POCOR-8333 ends
            } elseif ($this->request->getParam('action') == 'Institutions') { // cakephp4
                $this->Alert->warning('general.notExists');
                //die('Entity of ' . $alias . ' has no Institution action');
                $event->stopPropagation();
                return $this->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
            }
        }
    }

    /*POCOR-6966 ends*/

    public
    function getStatusPermission($model)
    {

        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        $isActive = $this->Institutions->isActive($institutionId);

        // institution status is INACTIVE
        if (!$isActive) {
            if (in_array($model->getAlias(), $this->features)) { // check the feature list
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

    public
    function getUserID($debugString = "")
    {
        // POCOR-8115;
        // user_id should always be in query string, if not, die as an error
        $user_id = $this->getQueryString('security_user_id');
        if (!$user_id) {
            $user_id = $this->getQueryString('user_id');
        }
        if (!$user_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put user_id into query string first');
            }
        }
        if (is_numeric($user_id)) {
            return $user_id;
        }
        return null;
    }

    public
    function beforeQuery(EventInterface $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public
    function beforePaginate(EventInterface $event, Table $model, Query $query, ArrayObject $options)
    {

        if (!$this->request->is('ajax')) {
            $isInstitutionIndex = $this->isInstitutionIDSkipped();
            if ($isInstitutionIndex) {
                return;
            }
            $institutionID = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
            if ($model->hasField('institution_id')) {
                if (!$institutionID) {
                    $this->Alert->error('general.notExists');
                    // should redirect
                } else {
                    if (!in_array($model->getAlias(),
                        ['Programmes',
                            'StaffTransferIn',
                            'StaffTransferOut',
                            'StudentTransferIn',
                            'StudentTransferOut'])) {
                        $query->where([$model->aliasField('institution_id') => $institutionID]);
                    }
                }
            }
        }
    }

    public
    function excel($id = 0)
    {
        TableRegistry::getTableLocator()->get('Institution.Institutions')->excel($id);
        $this->autoRender = false;
    }

//POCOR-5069 starts

    public
    function dashboard()
    {
        $institutionID = $this->getInstitutionID();
        $Institutions = $this->Institutions;
        $activeInstitution = $Institutions->get($institutionID);
        $classification = $activeInstitution->classification;
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $currentPeriod = $AcademicPeriods->getCurrent();
        //POCOR-7733 start
        $session = $this->request->getSession();
        $session->write('AcademicPeriod.currentAcademicPeriod', $currentPeriod);
        $session->write('AcademicPeriod.currentAcademicPeriodName', $AcademicPeriods->get($currentPeriod)->name);
        //POCOR-7733 end
        if (empty($currentPeriod)) {
            $this->Alert->warning('Institution.Institutions.academicPeriod');
        }

        // $highChartDatas = ['{"chart":{"type":"column","borderWidth":1},"xAxis":{"title":{"text":"Position Type"},"categories":["Non-Teaching","Teaching"]},"yAxis":{"title":{"text":"Total"}},"title":{"text":"Number Of Staff"},"subtitle":{"text":"For Year 2015-2016"},"series":[{"name":"Male","data":[0,2]},{"name":"Female","data":[0,1]}]}'];
        $highChartDatas = [];

        $StaffStatuses = TableRegistry::getTableLocator()->get('Staff.StaffStatuses');
        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');

        if ($classification == $Institutions::ACADEMIC) {
            // only show student charts if institution is academic
            $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
            $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
            $statuses = $StudentStatuses->findCodeList();

            $params = [
                'conditions' => ['institution_id' => $institutionID, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN'],
                    $statuses['PROMOTED'], $statuses['REPEATED']]]
            ];
            $highChartDatas[] = $InstitutionStudents->getHighChart('student_attendance', $params);

            $params = [
                'conditions' => [
                    'institution_id' => $institutionID,
                    'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('staff_attendance', $params);

            //Students By Grade for current year, excludes transferred ,withdrawn, promoted, repeated students
            $params = [
                'conditions' => ['institution_id' => $institutionID, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN'],
                    $statuses['PROMOTED'], $statuses['REPEATED']]]
            ];

            $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_grade', $params); //POCOR-7984

            //Students By Year, excludes transferred withdrawn,promoted,repeated students
            $params = [
                'conditions' => ['institution_id' => $institutionID, 'student_status_id NOT IN ' => [$statuses['TRANSFERRED'], $statuses['WITHDRAWN'],
                    $statuses['PROMOTED'], $statuses['REPEATED'], $statuses['GRADUATED']]]
            ];

            $highChartDatas[] = $InstitutionStudents->getHighChart('number_of_students_by_year', $params);

            //Staffs By Position Type for current year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $institutionID, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_type', $params);

            //Staffs By Year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $institutionID, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_year', $params);
        } elseif ($classification == $Institutions::NON_ACADEMIC) {
            //Staffs By Position Title for current year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $institutionID, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_position', $params);

            //Staffs By Year, only shows assigned staff
            $params = [
                'conditions' => ['institution_id' => $institutionID, 'staff_status_id' => $assignedStatus]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('number_of_staff_by_year', $params);
        }

        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $roles = self::getDynamicTableInstance('Institution.Institutions')->getInstitutionRoles($userId, $institutionID);
            //POCOR-8827 start
            $roles = array_values($roles);
            $roles = $roles[0];
            $isActive = $Institutions->isActive($institutionID);
            if ($isActive) {
                $havePermissionToViewCompleteness = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions')->find()
                ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityFunctions.name' => 'Institution Profile Completeness',
                    'SecurityRoleFunctions.security_role_id IS'=> $roles,
                    'SecurityRoleFunctions._view' => 1,
                ])
                ->toArray();
                if(!empty($havePermissionToViewCompleteness)){
                    $havePermissionToViewCompleteness = true;
                }else{
                     $havePermissionToViewCompleteness = false;
                }
                //$this->set('haveProfilePermission', $this->AccessControl->check(['Institutions', 'InstitutionProfileCompletness', 'view'], $roles));
                $this->set('haveProfilePermission', $havePermissionToViewCompleteness);
                //POCOR-8827 end
            } else {
                $this->set('haveProfilePermission', false);
            }
        } else {
            $this->set('haveProfilePermission', true);
        }
        $profileData = $this->getInstituteProfileCompletnessData($institutionID);
        $this->set('instituteprofileCompletness', $profileData);
        $this->set('institutionName', $activeInstitution->name);
        $this->set('contentHeader', $activeInstitution->name);
        $this->set('highChartDatas', $highChartDatas);
        $indexDashboard = 'dashboard';
        $this->set('mini_dashboard', [
            'name' => $indexDashboard,
            'data' => [
                'model' => 'staff',
                'modelCount' => 25,
                'modelArray' => []]
        ]);

    }//POCOR-5069 ends

    /**
     * Get intitute profile completness data
     * @return array
     */
    public
    function getInstituteProfileCompletnessData($institutionId)
    {
        $data = array();
        //$data['percentage'] = 0; //POCOR-6627 - commented line;it was adding extra data in totalProfileComplete
        $profileComplete = 0;
        //Overview
        $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institutionsData = $institutions->find()
            ->select([
                'created' => $institutions->aliasField('created'), //POCOR-8074
                'modified' => $institutions->aliasField('modified'), //POCOR-8074
            ])
            ->where([$institutions->aliasField('id') => $institutionId])
            ->order([$institutions->aliasField('modified') => 'desc']) //POCOR-8074
            ->limit(1)
            ->first();
        //Events
        $calendarEvents = TableRegistry::getTableLocator()->get('Institution.CalendarEvents');
        $calendarEventsData = $calendarEvents->find()
            ->select([
                'created' => 'CalendarEvents.created',
                'modified' => 'CalendarEvents.modified',
            ])
            ->where([$calendarEvents->aliasField('institution_id') => $institutionId])
            ->order(['CalendarEvents.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Contacts
        $institutionContactPersons = TableRegistry::getTableLocator()->get('Institution.InstitutionContactPersons');
        $institutionContactPersonsData = $institutionContactPersons->find()
            ->select([
                'created' => 'InstitutionContactPersons.created',
                'modified' => 'InstitutionContactPersons.modified',
            ])
            ->where([$institutionContactPersons->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionContactPersons.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Shifts
        $institutionShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
        $institutionShiftsData = $institutionShifts->find()
            ->select([
                'created' => 'InstitutionShifts.created',
                'modified' => 'InstitutionShifts.modified',
            ])
            ->where([$institutionShifts->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionShifts.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Programmes
        $institutionProgrammes = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $institutionProgrammesData = $institutionProgrammes->find()
            ->select([
                'created' => 'InstitutionGrades.created',
                'modified' => 'InstitutionGrades.modified',
            ])
            ->where([$institutionProgrammes->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionGrades.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Classes
        $institutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $institutionClassesData = $institutionClasses->find()
            ->select([
                'created' => 'InstitutionClasses.created',
                'modified' => 'InstitutionClasses.modified',
            ])
            ->where([$institutionClasses->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionClasses.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Subjects
        $institutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
        $institutionSubjectsData = $institutionSubjects->find()
            ->select([
                'created' => 'InstitutionSubjects.created',
                'modified' => 'InstitutionSubjects.modified',
            ])
            ->where([$institutionSubjects->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionSubjects.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Textbooks
        $institutionTextbooks = TableRegistry::getTableLocator()->get('Institution.InstitutionTextbooks');
        $institutionTextbooksData = $institutionTextbooks->find()
            ->select([
                'created' => 'InstitutionTextbooks.created',
                'modified' => 'InstitutionTextbooks.modified',
            ])
            ->where([$institutionTextbooks->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionTextbooks.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Students
        $institutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $institutionStudentsData = $institutionStudents->find()
            ->select([
                'created' => 'InstitutionStudents.created',
                'modified' => 'InstitutionStudents.modified',
            ])
            ->where([$institutionStudents->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionStudents.modified' => 'desc'])
            ->limit(1)
            ->first();
        //Staff
        $institutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $institutionStaffData = $institutionStaff->find()
            ->select([
                'created' => 'Staff.created',
                'modified' => 'Staff.modified',
            ])
            ->where([$institutionStaff->aliasField('institution_id') => $institutionId])
            ->order(['Staff.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Attendance
        $institutionAttendance = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffAttendances');
        $institutionAttendanceData = $institutionAttendance->find()
            ->select([
                'created' => 'InstitutionStaffAttendances.created',
                'modified' => 'InstitutionStaffAttendances.modified',
            ])
            ->where([$institutionAttendance->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionStaffAttendances.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Behaviour
        $institutionBehaviour = TableRegistry::getTableLocator()->get('Institution.StaffBehaviours');
        $institutionBehaviourData = $institutionBehaviour->find()
            ->select([
                'created' => 'StaffBehaviours.created',
                'modified' => 'StaffBehaviours.modified',
            ])
            ->where([$institutionBehaviour->aliasField('institution_id') => $institutionId])
            ->order(['StaffBehaviours.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Positions
        $institutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
        $institutionPositionsData = $institutionPositions->find()
            ->select([
                'created' => 'InstitutionPositions.created',
                'modified' => 'InstitutionPositions.modified',
            ])
            ->where([$institutionPositions->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionPositions.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Bank Accounts
        $institutionBankAccounts = TableRegistry::getTableLocator()->get('Institution.InstitutionBankAccounts');
        $institutionBankAccountsData = $institutionBankAccounts->find()
            ->select([
                'created' => 'InstitutionBankAccounts.created',
                'modified' => 'InstitutionBankAccounts.modified',
            ])
            ->where([$institutionBankAccounts->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionBankAccounts.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Institution Fees
        $institutionInstitutionFees = TableRegistry::getTableLocator()->get('Institution.InstitutionFees');

        $institutionInstitutionFeesData = $institutionInstitutionFees->find()
            ->select([
                'created' => 'InstitutionFees.created',
                'modified' => 'InstitutionFees.modified',
            ])
            ->where([$institutionInstitutionFees->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionFees.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Infrastructures Overview
        //POCOR-6022 start
        //Land
        $institutionLand = TableRegistry::getTableLocator()->get('Institution.InstitutionLands');
        $institutionLandData = $institutionLand->find()
            ->select([
                'created' => 'InstitutionLands.created',
                'modified' => 'InstitutionLands.modified',
            ])
            ->where([$institutionLand->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionLands.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Room
        $institutionRoom = TableRegistry::getTableLocator()->get('Institution.InstitutionRooms');
        $institutionRoomData = $institutionRoom->find()
            ->select([
                'created' => 'InstitutionRooms.created',
                'modified' => 'InstitutionRooms.modified',
            ])
            ->where([$institutionRoom->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionRooms.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Building
        $institutionBuilding = TableRegistry::getTableLocator()->get('Institution.InstitutionBuildings');
        $institutionBuildingData = $institutionBuilding->find()
            ->select([
                'created' => 'InstitutionBuildings.created',
                'modified' => 'InstitutionBuildings.modified',
            ])
            ->where([$institutionBuilding->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionBuildings.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Floor
        $institutionFloor = TableRegistry::getTableLocator()->get('Institution.InstitutionFloors');
        $institutionFloorData = $institutionFloor->find()
            ->select([
                'created' => 'InstitutionFloors.created',
                'modified' => 'InstitutionFloors.modified',
            ])
            ->where([$institutionFloor->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionFloors.modified' => 'desc'])
            ->limit(1)
            ->first();
        //POCOR-6022 ends
//        $data[16]['feature'] = 'Infrastructures Overview'; //POCOR-7883

        // Infrastructures Needs
        $institutionInfrastructuresNeeds = TableRegistry::getTableLocator()->get('Institution.InfrastructureNeeds');
        $institutionInfrastructuresNeedsData = $institutionInfrastructuresNeeds->find()
            ->select([
                'created' => 'InfrastructureNeeds.created',
                'modified' => 'InfrastructureNeeds.modified',
            ])
            ->where([$institutionInfrastructuresNeeds->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureNeeds.modified' => 'desc'])
            ->limit(1)
            ->first();

        // Wash Water
        $institutionWashWater = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWaters');
        $institutionWashWaterData = $institutionWashWater->find()
            ->select([
                'created' => 'InfrastructureWashWaters.created',
                'modified' => 'InfrastructureWashWaters.modified',
            ])
            ->where([$institutionWashWater->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureWashWaters.modified' => 'desc'])
            ->limit(1)
            ->first();

        // Wash Hygiene
        $institutionWashHygiene = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashHygienes');
        $institutionWashHygieneData = $institutionWashHygiene->find()
            ->select([
                'created' => 'InfrastructureWashHygienes.created',
                'modified' => 'InfrastructureWashHygienes.modified',
            ])
            ->where([$institutionWashHygiene->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureWashHygienes.modified' => 'desc'])
            ->limit(1)
            ->first();

        // Wash Waste
        $institutionWashWaste = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWastes');
        $institutionWashWasteData = $institutionWashWaste->find()
            ->select([
                'created' => 'InfrastructureWashWastes.created',
                'modified' => 'InfrastructureWashWastes.modified',
            ])
            ->where([$institutionWashWaste->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureWashWastes.modified' => 'desc'])
            ->limit(1)
            ->first();

        // Wash Sewage
        $institutionWashSewage = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSewages');
        $institutionWashSewageData = $institutionWashSewage->find()
            ->select([
                'created' => 'InfrastructureWashSewages.created',
                'modified' => 'InfrastructureWashSewages.modified',
            ])
            ->where([$institutionWashSewage->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureWashSewages.modified' => 'desc'])
            ->limit(1)
            ->first();

        // Utilities Electricity
        $institutionUtilitiesElectricity = TableRegistry::getTableLocator()->get('Institution.InfrastructureUtilityElectricities');
        $institutionUtilitiesElectricityData = $institutionUtilitiesElectricity->find()
            ->select([
                'created' => 'InfrastructureUtilityElectricities.created',
                'modified' => 'InfrastructureUtilityElectricities.modified',
            ])
            ->where([$institutionUtilitiesElectricity->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureUtilityElectricities.modified' => 'desc'])
            ->limit(1)
            ->first();

        // Utilities Internet
        $institutionUtilitiesInternet = TableRegistry::getTableLocator()->get('Institution.InfrastructureUtilityInternets');
        $institutionUtilitiesInternetData = $institutionUtilitiesInternet->find()
            ->select([
                'created' => 'InfrastructureUtilityInternets.created',
                'modified' => 'InfrastructureUtilityInternets.modified',
            ])
            ->where([$institutionUtilitiesInternet->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureUtilityInternets.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Utilities Telephone
        $institutionUtilitiesTelephone = TableRegistry::getTableLocator()->get('Institution.InfrastructureUtilityTelephones');
        $institutionUtilitiesTelephoneData = $institutionUtilitiesTelephone->find()
            ->select([
                'created' => 'InfrastructureUtilityTelephones.created',
                'modified' => 'InfrastructureUtilityTelephones.modified',
            ])
            ->where([$institutionUtilitiesTelephone->aliasField('institution_id') => $institutionId])
            ->order(['InfrastructureUtilityTelephones.modified' => 'desc'])
            ->limit(1)
            ->first();

        // Assets
        $institutionAssets = TableRegistry::getTableLocator()->get('Institution.InstitutionAssets');
        $institutionAssetsData = $institutionAssets->find()
            ->select([
                'created' => 'InstitutionAssets.created',
                'modified' => 'InstitutionAssets.modified',
            ])
            ->where([$institutionAssets->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionAssets.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Transport
        $institutionTransport = TableRegistry::getTableLocator()->get('Institution.InstitutionBuses');
        $institutionTransportData = $institutionTransport->find()
            ->where([$institutionTransport->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionBuses.modified' => 'desc'])
            ->limit(1)
            ->first();

        //Committees
        $institutionCommittees = TableRegistry::getTableLocator()->get('Institution.InstitutionCommittees');
        $institutionCommitteesData = $institutionCommittees->find()
            ->select([
                'created' => 'InstitutionCommittees.created',
                'modified' => 'InstitutionCommittees.modified',
            ])
            ->where([$institutionCommittees->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionCommittees.modified' => 'desc'])
            ->limit(1)
            ->first();

        // config
        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $enabledTypeList = $ConfigItem
            ->find()
            ->order('label')
            ->where([
                $ConfigItem->aliasField('visible') => 1,
                $ConfigItem->aliasField('value') => 1,
                $ConfigItem->aliasField('type') => 'Institution Data Completeness'])//POCOR-6022
            ->toArray();

        foreach ($enabledTypeList as $key => $enabled) {
            $data[$key]['feature'] = $enabled->name;
            if ($enabled->name == 'Overview') {
                if (!empty($institutionsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionsData->modified) ? date("F j,Y", strtotime($institutionsData->modified)) : date("F j,Y", strtotime($institutionsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Calendar') {
                if (!empty($calendarEventsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($calendarEventsData->modified) ? date("F j,Y", strtotime($calendarEventsData->modified)) : date("F j,Y", strtotime($calendarEventsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Contacts') {
                if (!empty($institutionContactPersonsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionContactPersonsData->modified) ? date("F j,Y", strtotime($institutionContactPersonsData->modified)) : date("F j,Y", strtotime($institutionContactPersonsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Shifts') {
                if (!empty($institutionShiftsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionShiftsData->modified) ? date("F j,Y", strtotime($institutionShiftsData->modified)) : date("F j,Y", strtotime($institutionShiftsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Programmes') {
                if (!empty($institutionProgrammesData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionProgrammesData->modified) ? date("F j,Y", strtotime($institutionProgrammesData->modified)) : date("F j,Y", strtotime($institutionProgrammesData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Classes') {
                if (!empty($institutionClassesData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionClassesData->modified) ? date("F j,Y", strtotime($institutionClassesData->modified)) : date("F j,Y", strtotime($institutionClassesData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Subjects') {
                if (!empty($institutionSubjectsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionSubjectsData->modified) ? date("F j,Y", strtotime($institutionSubjectsData->modified)) : date("F j,Y", strtotime($institutionSubjectsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Textbooks') {
                if (!empty($institutionTextbooksData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionTextbooksData->modified) ? date("F j,Y", strtotime($institutionTextbooksData->modified)) : date("F j,Y", strtotime($institutionTextbooksData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Students') {
                if (!empty($institutionStudentsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionStudentsData->modified) ? date("F j,Y", strtotime($institutionStudentsData->modified)) : date("F j,Y", strtotime($institutionStudentsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Staff') {
                if (!empty($institutionStaffData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionStaffData->modified) ? date("F j,Y", strtotime($institutionStaffData->modified)) : date("F j,Y", strtotime($institutionStaffData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Attendance') {
                if (!empty($institutionAttendanceData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionAttendanceData->modified) ? date("F j,Y", strtotime($institutionAttendanceData->modified)) : date("F j,Y", strtotime($institutionAttendanceData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Behaviour') {
                if (!empty($institutionBehaviourData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionBehaviourData->modified) ? date("F j,Y", strtotime($institutionBehaviourData->modified)) : date("F j,Y", strtotime($institutionBehaviourData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Positions') {
                if (!empty($institutionPositionsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionPositionsData->modified) ? date("F j,Y", strtotime($institutionPositionsData->modified)) : date("F j,Y", strtotime($institutionPositionsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Bank Accounts') {
                if (!empty($institutionBankAccountsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionBankAccountsData->modified) ? date("F j,Y", strtotime($institutionBankAccountsData->modified)) : date("F j,Y", strtotime($institutionBankAccountsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Institution Fees') {
                if (!empty($institutionInstitutionFeesData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionInstitutionFeesData->modified) ? date("F j,Y", strtotime($institutionInstitutionFeesData->modified)) : date("F j,Y", strtotime($institutionInstitutionFeesData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            //POCOR-7883 moved from if and fixed
            if ($enabled->name == 'Infrastructures Overview') {
                if (!empty($institutionLandData) && !empty($institutionBuildingData) && !empty($institutionFloorData) && !empty($institutionRoomData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    //POCOR-6022 start
                    $modifiedDate1 = ($institutionLandData->modified) ? date("F j,Y", strtotime($institutionLandData->modified)) : date("F j,Y", strtotime($institutionLandData->created));
                    $modifiedDate2 = ($institutionBuildingData->modified) ? date("F j,Y", strtotime($institutionBuildingData->modified)) : date("F j,Y", strtotime($institutionBuildingData->created));
                    $modifiedDate3 = ($institutionFloorData->modified) ? date("F j,Y", strtotime($institutionFloorData->modified)) : date("F j,Y", strtotime($institutionFloorData->created));
                    $modifiedDate4 = ($institutionRoomData->modified) ? date("F j,Y", strtotime($institutionRoomData->modified)) : date("F j,Y", strtotime($institutionRoomData->created));
                    $modifiedDate = max($modifiedDate1, $modifiedDate2, $modifiedDate3, $modifiedDate4); //POCOR-7883 optimize
                    $data[$key]['modifiedDate'] = $modifiedDate;
                    //POCOR-6022 ends
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            } //POCOR-7883 ends
            if ($enabled->name == 'Infrastructures Needs') {
                if (!empty($institutionInfrastructuresNeedsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionInfrastructuresNeedsData->modified) ? date("F j,Y", strtotime($institutionInfrastructuresNeedsData->modified)) : date("F j,Y", strtotime($institutionInfrastructuresNeedsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Wash Water') {
                if (!empty($institutionWashWaterData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionWashWaterData->modified) ? date("F j,Y", strtotime($institutionWashWaterData->modified)) : date("F j,Y", strtotime($institutionWashWaterData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Wash Hygiene') {
                if (!empty($institutionWashHygieneData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionWashHygieneData->modified) ? date("F j,Y", strtotime($institutionWashHygieneData->modified)) : date("F j,Y", strtotime($institutionWashHygieneData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Wash Waste') {
                if (!empty($institutionWashWasteData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionWashWasteData->modified) ? date("F j,Y", strtotime($institutionWashWasteData->modified)) : date("F j,Y", strtotime($institutionWashWasteData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Wash Sewage') {
                if (!empty($institutionWashSewageData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionWashSewageData->modified) ? date("F j,Y", strtotime($institutionWashSewageData->modified)) : date("F j,Y", strtotime($institutionWashSewageData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Utilities Electricity') {
                if (!empty($institutionUtilitiesElectricityData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionUtilitiesElectricityData->modified) ? date("F j,Y", strtotime($institutionUtilitiesElectricityData->modified)) : date("F j,Y", strtotime($institutionUtilitiesElectricityData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Utilities Internet') {
                if (!empty($institutionUtilitiesInternetData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionUtilitiesInternetData->modified) ? date("F j,Y", strtotime($institutionUtilitiesInternetData->modified)) : date("F j,Y", strtotime($institutionUtilitiesInternetData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Utilities Telephone') {
                if (!empty($institutionUtilitiesTelephoneData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionUtilitiesTelephoneData->modified) ? date("F j,Y", strtotime($institutionUtilitiesTelephoneData->modified)) : date("F j,Y", strtotime($institutionUtilitiesTelephoneData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Assets') {
                if (!empty($institutionAssetsData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionAssetsData->modified) ? date("F j,Y", strtotime($institutionAssetsData->modified)) : date("F j,Y", strtotime($institutionAssetsData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Transport') {
                if (!empty($institutionTransportData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionTransportData->modified) ? date("F j,Y", strtotime($institutionTransportData->modified)) : date("F j,Y", strtotime($institutionTransportData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }
            if ($enabled->name == 'Committees') {
                if (!empty($institutionCommitteesData)) {
                    $profileComplete = $profileComplete + 1;
                    $data[$key]['complete'] = 'yes';
                    $data[$key]['modifiedDate'] = ($institutionCommitteesData->modified) ? date("F j,Y", strtotime($institutionCommitteesData->modified)) : date("F j,Y", strtotime($institutionCommitteesData->created));
                } else {
                    $data[$key]['complete'] = 'no';
                    $data[$key]['modifiedDate'] = 'Not updated';
                }
            }

        }
        $totalProfileComplete = count($data);
        $profilePercentage = 100 / $totalProfileComplete * $profileComplete;
        $profilePercentage = round($profilePercentage);
        $data['percentage'] = $profilePercentage;
        return $data;
    }

    /**
     * Get intitute profile completness data
     * @return array
     */

    public
    function getInstituteProfileCompletnessDataBAK($institutionId)
    {

        $data = array();
        $profileComplete = 0;
        // $totalProfileCount = 28;
        // check in config item

        /********************************************* */
        //Overview
        $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institutionsData = $institutions->find()
            ->select([
                'created' => 'Institutions.created',
                'modified' => 'Institutions.modified',
            ])
            ->where([$institutions->aliasField('id') => $institutionId])
            ->order(['Institutions.modified' => 'desc'])
            ->limit(1)
            ->first();;
        $data[0]['feature'] = 'Overview';
        if (!empty($institutionsData)) {
            $profileComplete = $profileComplete + 1;
            $data[0]['complete'] = 'yes';
            $data[0]['profileComplete'] = $profileComplete;
            $data[0]['modifiedDate'] = date("F j,Y", strtotime($institutionsData->modified));
        } else {
            $data[0]['complete'] = 'no';
            $data[0]['profileComplete'] = 0;
            $data[0]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Events
        $calendarEvents = TableRegistry::getTableLocator()->get('Institution.CalendarEvents');
        $calendarEventsData = $calendarEvents->find()
            ->select([
                'created' => 'CalendarEvents.created',
                'modified' => 'CalendarEvents.modified',
            ])
            ->where([$calendarEvents->aliasField('institution_id') => $institutionId])
            ->order(['CalendarEvents.modified' => 'desc'])
            ->limit(1)
            ->first();
        $data[1]['feature'] = 'Calendar';;
        if (!empty($calendarEventsData)) {
            $profileComplete = $profileComplete + 1;
            $data[1]['complete'] = 'yes';
            $data[1]['profileComplete'] = $profileComplete;
            $data[1]['modifiedDate'] = date("F j,Y", strtotime($calendarEventsData->modified));
        } else {
            $data[1]['complete'] = 'no';
            $data[1]['profileComplete'] = 0;
            $data[1]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Contacts
        $institutionContactPersons = TableRegistry::getTableLocator()->get('Institution.InstitutionContactPersons');
        $institutionContactPersonsData = $institutionContactPersons->find()
            ->select([
                'created' => 'InstitutionContactPersons.created',
                'modified' => 'InstitutionContactPersons.modified',
            ])
            ->where([$institutionContactPersons->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionContactPersons.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[2]['feature'] = 'Contacts';
        if (!empty($institutionContactPersonsData)) {
            $profileComplete = $profileComplete + 1;
            $data[2]['complete'] = 'yes';
            $data[2]['profileComplete'] = $profileComplete;
            $data[2]['modifiedDate'] = date("F j,Y", strtotime($institutionContactPersonsData->modified));
        } else {
            $data[2]['complete'] = 'no';
            $data[2]['profileComplete'] = 0;
            $data[2]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Shifts
        $institutionShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
        $institutionShiftsData = $institutionShifts->find()
            ->select([
                'created' => 'institution_shifts.created',
                'modified' => 'institution_shifts.modified',
            ])
            ->where([$institutionShifts->aliasField('institution_id') => $institutionId])
            ->order(['institution_shifts.modified' => 'desc'])
            ->limit(1)
            ->first();
        $data[3]['feature'] = 'Shifts';
        if (!empty($institutionShiftsData)) {
            $profileComplete = $profileComplete + 1;
            $data[3]['complete'] = 'yes';
            $data[3]['profileComplete'] = $profileComplete;
            $data[3]['modifiedDate'] = ($institutionShiftsData->modified) ? date("F j,Y", strtotime($institutionShiftsData->modified)) : date("F j,Y", strtotime($institutionShiftsData->created));
        } else {
            $data[3]['complete'] = 'no';
            $data[3]['profileComplete'] = 0;
            $data[3]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Programmes
        $institutionProgrammes = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $institutionProgrammesData = $institutionProgrammes->find()
            ->select([
                'created' => 'InstitutionGrades.created',
                'modified' => 'InstitutionGrades.modified',
            ])
            ->where([$institutionProgrammes->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionGrades.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[4]['feature'] = 'Programmes';
        if (!empty($institutionProgrammesData)) {
            $profileComplete = $profileComplete + 1;
            $data[4]['complete'] = 'yes';
            $data[4]['profileComplete'] = $profileComplete;
            $data[4]['modifiedDate'] = ($institutionProgrammesData->modified) ? date("F j,Y", strtotime($institutionProgrammesData->modified)) : date("F j,Y", strtotime($institutionProgrammesData->created));
        } else {
            $data[4]['complete'] = 'no';
            $data[4]['profileComplete'] = 0;
            $data[4]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Classes
        $institutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $institutionClassesData = $institutionClasses->find()
            ->select([
                'created' => 'InstitutionClasses.created',
                'modified' => 'InstitutionClasses.modified',
            ])
            ->where([$institutionClasses->aliasField('institution_id') => $institutionId])
            ->order(['institution_classes.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[5]['feature'] = 'Classes';
        if (!empty($institutionClassesData)) {
            $profileComplete = $profileComplete + 1;
            $data[5]['complete'] = 'yes';
            $data[5]['profileComplete'] = $profileComplete;
            $data[5]['modifiedDate'] = ($institutionClassesData->modified) ? date("F j,Y", strtotime($institutionClassesData->modified)) : date("F j,Y", strtotime($institutionClassesData->created));
        } else {
            $data[5]['complete'] = 'no';
            $data[5]['profileComplete'] = 0;
            $data[5]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Subjects
        $institutionSubjects = TableRegistry::getTableLocator()->get('institution_subjects');
        $institutionSubjectsData = $institutionSubjects->find()
            ->select([
                'created' => 'institution_subjects.created',
                'modified' => 'institution_subjects.modified',
            ])
            ->where([$institutionSubjects->aliasField('institution_id') => $institutionId])
            ->order(['institution_subjects.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[6]['feature'] = 'Subjects';
        if (!empty($institutionSubjectsData)) {
            $profileComplete = $profileComplete + 1;
            $data[6]['complete'] = 'yes';
            $data[6]['profileComplete'] = $profileComplete;
            $data[6]['modifiedDate'] = ($institutionSubjectsData->modified) ? date("F j,Y", strtotime($institutionSubjectsData->modified)) : date("F j,Y", strtotime($institutionSubjectsData->created));
        } else {
            $data[6]['complete'] = 'no';
            $data[6]['profileComplete'] = 0;
            $data[6]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Textbooks
        $institutionTextbooks = TableRegistry::getTableLocator()->get('institution_textbooks');
        $institutionTextbooksData = $institutionTextbooks->find()
            ->select([
                'created' => 'institution_textbooks.created',
                'modified' => 'institution_textbooks.modified',
            ])
            ->where([$institutionTextbooks->aliasField('institution_id') => $institutionId])
            ->order(['institution_textbooks.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[7]['feature'] = 'Textbooks';
        if (!empty($institutionTextbooksData)) {
            $profileComplete = $profileComplete + 1;
            $data[7]['complete'] = 'yes';
            $data[7]['profileComplete'] = $profileComplete;
            $data[7]['modifiedDate'] = ($institutionTextbooksData->modified) ? date("F j,Y", strtotime($institutionTextbooksData->modified)) : date("F j,Y", strtotime($institutionSubjectsData->created));
        } else {
            $data[7]['complete'] = 'no';
            $data[7]['profileComplete'] = 0;
            $data[7]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Students
        $institutionStudents = TableRegistry::getTableLocator()->get('institution_students');
        $institutionStudentsData = $institutionStudents->find()
            ->select([
                'created' => 'institution_students.created',
                'modified' => 'institution_students.modified',
            ])
            ->where([$institutionStudents->aliasField('institution_id') => $institutionId])
            ->order(['institution_students.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[8]['feature'] = 'Students';
        if (!empty($institutionStudentsData)) {
            $profileComplete = $profileComplete + 1;
            $data[8]['complete'] = 'yes';
            $data[8]['profileComplete'] = $profileComplete;
            $data[8]['modifiedDate'] = ($institutionStudentsData->modified) ? date("F j,Y", strtotime($institutionStudentsData->modified)) : date("F j,Y", strtotime($institutionSubjectsData->created));
        } else {
            $data[8]['complete'] = 'no';
            $data[8]['profileComplete'] = 0;
            $data[8]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Staff
        $institutionStaff = TableRegistry::getTableLocator()->get('institution_staff');
        $institutionStaffData = $institutionStaff->find()
            ->select([
                'created' => 'institution_staff.created',
                'modified' => 'institution_staff.modified',
            ])
            ->where([$institutionStaff->aliasField('institution_id') => $institutionId])
            ->order(['institution_staff.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[9]['feature'] = 'Staff';
        if (!empty($institutionStaffData)) {
            $profileComplete = $profileComplete + 1;
            $data[9]['complete'] = 'yes';
            $data[9]['profileComplete'] = $profileComplete;
            $data[9]['modifiedDate'] = ($institutionStaffData->modified) ? date("F j,Y", strtotime($institutionStaffData->modified)) : date("F j,Y", strtotime($institutionStaffData->created));
        } else {
            $data[9]['complete'] = 'no';
            $data[9]['profileComplete'] = 0;
            $data[9]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Attendance
        $institutionAttendance = TableRegistry::getTableLocator()->get('institution_staff_attendances');
        $institutionAttendanceData = $institutionAttendance->find()
            ->select([
                'created' => 'institution_staff_attendances.created',
                'modified' => 'institution_staff_attendances.modified',
            ])
            ->where([$institutionAttendance->aliasField('institution_id') => $institutionId])
            ->order(['institution_staff_attendances.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[10]['feature'] = 'Attendance';
        if (!empty($institutionAttendanceData)) {
            $profileComplete = $profileComplete + 1;
            $data[10]['complete'] = 'yes';
            $data[10]['profileComplete'] = $profileComplete;
            $data[10]['modifiedDate'] = ($institutionAttendanceData->modified) ? date("F j,Y", strtotime($institutionAttendanceData->modified)) : date("F j,Y", strtotime($institutionAttendanceData->created));
        } else {
            $data[10]['complete'] = 'no';
            $data[10]['profileComplete'] = 0;
            $data[10]['modifiedDate'] = 'Not updated';
        }

        /********************************************* */
        //Behaviour
        $institutionBehaviour = TableRegistry::getTableLocator()->get('staff_behaviours');
        $institutionBehaviourData = $institutionBehaviour->find()
            ->select([
                'created' => 'staff_behaviours.created',
                'modified' => 'staff_behaviours.modified',
            ])
            ->where([$institutionBehaviour->aliasField('institution_id') => $institutionId])
            ->order(['staff_behaviours.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[11]['feature'] = 'Behaviour';
        if (!empty($institutionBehaviourData)) {
            $profileComplete = $profileComplete + 1;
            $data[11]['complete'] = 'yes';
            $data[11]['profileComplete'] = $profileComplete;
            $data[11]['modifiedDate'] = ($institutionBehaviourData->modified) ? date("F j,Y", strtotime($institutionBehaviourData->modified)) : date("F j,Y", strtotime($institutionBehaviourData->created));;
        } else {
            $data[11]['complete'] = 'no';
            $data[11]['profileComplete'] = 0;
            $data[11]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Positions
        $institutionPositions = TableRegistry::getTableLocator()->get('institution_positions');
        $institutionPositionsData = $institutionPositions->find()
            ->select([
                'created' => 'institution_positions.created',
                'modified' => 'institution_positions.modified',
            ])
            ->where([$institutionPositions->aliasField('institution_id') => $institutionId])
            ->order(['institution_positions.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[12]['feature'] = 'Positions';
        if (!empty($institutionPositionsData)) {
            $profileComplete = $profileComplete + 1;
            $data[12]['complete'] = 'yes';
            $data[12]['profileComplete'] = $profileComplete;
            $data[12]['modifiedDate'] = ($institutionPositionsData->modified) ? date("F j,Y", strtotime($institutionPositionsData->modified)) : date("F j,Y", strtotime($institutionPositionsData->created));
        } else {
            $data[12]['complete'] = 'no';
            $data[12]['profileComplete'] = 0;
            $data[12]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Bank Accounts
        $institutionBankAccounts = TableRegistry::getTableLocator()->get('institution_bank_accounts');
        $institutionBankAccountsData = $institutionBankAccounts->find()
            ->select([
                'created' => 'institution_bank_accounts.created',
                'modified' => 'institution_bank_accounts.modified',
            ])
            ->where([$institutionBankAccounts->aliasField('institution_id') => $institutionId])
            ->order(['institution_bank_accounts.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[13]['feature'] = 'Bank Accounts';
        if (!empty($institutionBankAccountsData)) {
            $profileComplete = $profileComplete + 1;
            $data[13]['complete'] = 'yes';
            $data[13]['profileComplete'] = $profileComplete;
            $data[13]['modifiedDate'] = ($institutionBankAccountsData->modified) ? date("F j,Y", strtotime($institutionBankAccountsData->modified)) : date("F j,Y", strtotime($institutionBankAccountsData->created));
        } else {
            $data[13]['complete'] = 'no';
            $data[13]['profileComplete'] = 0;
            $data[13]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Institution Fees
        $institutionInstitutionFees = TableRegistry::getTableLocator()->get('institution_fees');
        $institutionInstitutionFeesData = $institutionInstitutionFees->find()
            ->select([
                'created' => 'institution_fees.created',
                'modified' => 'institution_fees.modified',
            ])
            ->where([$institutionInstitutionFees->aliasField('institution_id') => $institutionId])
            ->order(['institution_fees.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[14]['feature'] = 'Institution Fees';
        if (!empty($institutionInstitutionFeesData)) {
            $profileComplete = $profileComplete + 1;
            $data[14]['complete'] = 'yes';
            $data[14]['profileComplete'] = $profileComplete;
            $data[14]['modifiedDate'] = ($institutionInstitutionFeesData->modified) ? date("F j,Y", strtotime($institutionInstitutionFeesData->modified)) : date("F j,Y", strtotime($institutionInstitutionFeesData->created));
        } else {
            $data[14]['complete'] = 'no';
            $data[14]['profileComplete'] = 0;
            $data[14]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Student Fees
        // $institutionStudentFees  = TableRegistry::getTableLocator()->get('student_fees');
        // $institutionStudentFeesData = $institutionStudentFees->find()
        //      ->select([
        //          'created' => 'student_fees.created',
        //          'modified' => 'student_fees.modified',
        //      ])
        //      ->where([$institutionStudentFees->aliasField('institution_id') => $institutionId])
        //      ->limit(1)
        //      ->first();

        // $data[15]['feature'] = 'Student Fees';
        // if(!empty($institutionStudentFeesData)) {
        //  $profileComplete = $profileComplete + 1;
        //     $data[15]['complete'] = 'yes';
        //     $data[15]['modifiedDate'] = date("F j,Y",strtotime($institutionStudentFeesData->modified));
        // } else {
        //     $data[15]['complete'] = 'no';
        //     $data[15]['modifiedDate'] = 'Not updated';
        // }
        /********************************************* */
        //Infrastructures Overview
        $institutionInfrastructuresOverview = TableRegistry::getTableLocator()->get('institution_lands');
        $institutionInfrastructuresOverviewData = $institutionInfrastructuresOverview->find()
            ->select([
                'created' => 'institution_lands.created',
                'modified' => 'institution_lands.modified',
            ])
            ->where([$institutionInfrastructuresOverview->aliasField('institution_id') => $institutionId])
            ->order(['institution_lands.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[16]['feature'] = 'Infrastructures Overview';
        if (!empty($institutionInfrastructuresOverviewData)) {
            $profileComplete = $profileComplete + 1;
            $data[16]['complete'] = 'yes';
            $data[16]['profileComplete'] = $profileComplete;
            $data[16]['modifiedDate'] = ($institutionInfrastructuresOverviewData->modified) ? date("F j,Y", strtotime($institutionInfrastructuresOverviewData->modified)) : date("F j,Y", strtotime($institutionInfrastructuresOverviewData->created));
        } else {
            $data[16]['complete'] = 'no';
            $data[16]['profileComplete'] = 0;
            $data[16]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Infrastructures Needs
        $institutionInfrastructuresNeeds = TableRegistry::getTableLocator()->get('infrastructure_needs');
        $institutionInfrastructuresNeedsData = $institutionInfrastructuresNeeds->find()
            ->select([
                'created' => 'infrastructure_needs.created',
                'modified' => 'infrastructure_needs.modified',
            ])
            ->where([$institutionInfrastructuresNeeds->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_needs.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[17]['feature'] = 'Infrastructures Needs';
        if (!empty($institutionInfrastructuresNeedsData)) {
            $profileComplete = $profileComplete + 1;
            $data[17]['complete'] = 'yes';
            $data[17]['profileComplete'] = $profileComplete;
            $data[17]['modifiedDate'] = ($institutionInfrastructuresNeedsData->modified) ? date("F j,Y", strtotime($institutionInfrastructuresNeedsData->modified)) : date("F j,Y", strtotime($institutionInfrastructuresNeedsData->created));
        } else {
            $data[17]['complete'] = 'no';
            $data[17]['profileComplete'] = 0;
            $data[17]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Wash Water
        $institutionWashWater = TableRegistry::getTableLocator()->get('infrastructure_wash_waters');
        $institutionWashWaterData = $institutionWashWater->find()
            ->select([
                'created' => 'infrastructure_wash_waters.created',
                'modified' => 'infrastructure_wash_waters.modified',
            ])
            ->where([$institutionWashWater->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_wash_waters.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[18]['feature'] = 'Wash Water';
        if (!empty($institutionWashWaterData)) {
            $profileComplete = $profileComplete + 1;
            $data[18]['complete'] = 'yes';
            $data[18]['profileComplete'] = $profileComplete;
            $data[18]['modifiedDate'] = ($institutionWashWaterData->modified) ? date("F j,Y", strtotime($institutionWashWaterData->modified)) : date("F j,Y", strtotime($institutionWashWaterData->created));
        } else {
            $data[18]['complete'] = 'no';
            $data[18]['profileComplete'] = 0;
            $data[18]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Wash Hygiene
        $institutionWashHygiene = TableRegistry::getTableLocator()->get('infrastructure_wash_hygienes');
        $institutionWashHygieneData = $institutionWashHygiene->find()
            ->select([
                'created' => 'infrastructure_wash_hygienes.created',
                'modified' => 'infrastructure_wash_hygienes.modified',
            ])
            ->where([$institutionWashHygiene->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_wash_hygienes.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[19]['feature'] = 'Wash Hygiene';
        if (!empty($institutionWashHygieneData)) {
            $profileComplete = $profileComplete + 1;
            $data[19]['complete'] = 'yes';
            $data[19]['profileComplete'] = $profileComplete;
            $data[19]['modifiedDate'] = ($institutionWashHygieneData->modified) ? date("F j,Y", strtotime($institutionWashHygieneData->modified)) : date("F j,Y", strtotime($institutionWashHygieneData->created));
        } else {
            $data[19]['complete'] = 'no';
            $data[19]['profileComplete'] = 0;
            $data[19]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Wash Waste
        $institutionWashWaste = TableRegistry::getTableLocator()->get('infrastructure_wash_wastes');
        $institutionWashWasteData = $institutionWashWaste->find()
            ->select([
                'created' => 'infrastructure_wash_wastes.created',
                'modified' => 'infrastructure_wash_wastes.modified',
            ])
            ->where([$institutionWashWaste->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_wash_wastes.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[20]['feature'] = 'Wash Waste';
        if (!empty($institutionWashWasteData)) {
            $profileComplete = $profileComplete + 1;
            $data[20]['complete'] = 'yes';
            $data[20]['profileComplete'] = $profileComplete;
            $data[20]['modifiedDate'] = ($institutionWashWasteData->modified) ? date("F j,Y", strtotime($institutionWashWasteData->modified)) : date("F j,Y", strtotime($institutionWashWasteData->created));
        } else {
            $data[20]['complete'] = 'no';
            $data[20]['profileComplete'] = 0;
            $data[20]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Wash Sewage
        $institutionWashSewage = TableRegistry::getTableLocator()->get('infrastructure_wash_sewages');
        $institutionWashSewageData = $institutionWashSewage->find()
            ->select([
                'created' => 'infrastructure_wash_sewages.created',
                'modified' => 'infrastructure_wash_sewages.modified',
            ])
            ->where([$institutionWashSewage->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_wash_sewages.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[21]['feature'] = 'Wash Sewage';
        if (!empty($institutionWashSewageData)) {
            $profileComplete = $profileComplete + 1;
            $data[21]['complete'] = 'yes';
            $data[21]['profileComplete'] = $profileComplete;
            $data[21]['modifiedDate'] = ($institutionWashSewageData->modified) ? date("F j,Y", strtotime($institutionWashSewageData->modified)) : date("F j,Y", strtotime($institutionWashSewageData->created));
        } else {
            $data[21]['complete'] = 'no';
            $data[21]['profileComplete'] = 0;
            $data[21]['modifiedDate'] = 'Not updated';
        }

        /********************************************* */
        // Utilities Electricity
        $institutionUtilitiesElectricity = TableRegistry::getTableLocator()->get('infrastructure_utility_electricities');
        $institutionUtilitiesElectricityData = $institutionUtilitiesElectricity->find()
            ->select([
                'created' => 'infrastructure_utility_electricities.created',
                'modified' => 'infrastructure_utility_electricities.modified',
            ])
            ->where([$institutionUtilitiesElectricity->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_utility_electricities.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[22]['feature'] = 'Utilities Electricity';
        if (!empty($institutionUtilitiesElectricityData)) {
            $profileComplete = $profileComplete + 1;
            $data[22]['complete'] = 'yes';
            $data[22]['profileComplete'] = $profileComplete;
            $data[22]['modifiedDate'] = ($institutionUtilitiesElectricityData->modified) ? date("F j,Y", strtotime($institutionUtilitiesElectricityData->modified)) : date("F j,Y", strtotime($institutionUtilitiesElectricityData->created));
        } else {
            $data[22]['complete'] = 'no';
            $data[22]['profileComplete'] = 0;
            $data[22]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Utilities Internet
        $institutionUtilitiesInternet = TableRegistry::getTableLocator()->get('infrastructure_utility_internets');
        $institutionUtilitiesInternetData = $institutionUtilitiesInternet->find()
            ->select([
                'created' => 'infrastructure_utility_internets.created',
                'modified' => 'infrastructure_utility_internets.modified',
            ])
            ->where([$institutionUtilitiesInternet->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_utility_internets.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[23]['feature'] = 'Utilities Internet';
        if (!empty($institutionUtilitiesInternetData)) {
            $profileComplete = $profileComplete + 1;
            $data[23]['complete'] = 'yes';
            $data[23]['profileComplete'] = $profileComplete;
            $data[23]['modifiedDate'] = ($institutionUtilitiesInternetData->modified) ? date("F j,Y", strtotime($institutionUtilitiesInternetData->modified)) : date("F j,Y", strtotime($institutionUtilitiesInternetData->created));
        } else {
            $data[23]['complete'] = 'no';
            $data[23]['profileComplete'] = 0;
            $data[23]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Utilities Telephone
        $institutionUtilitiesTelephone = TableRegistry::getTableLocator()->get('infrastructure_utility_telephones');
        $institutionUtilitiesTelephoneData = $institutionUtilitiesTelephone->find()
            ->select([
                'created' => 'infrastructure_utility_telephones.created',
                'modified' => 'infrastructure_utility_telephones.modified',
            ])
            ->where([$institutionUtilitiesTelephone->aliasField('institution_id') => $institutionId])
            ->order(['infrastructure_utility_telephones.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[24]['feature'] = 'Utilities Telephone';
        if (!empty($institutionUtilitiesTelephoneData)) {
            $profileComplete = $profileComplete + 1;
            $data[24]['complete'] = 'yes';
            $data[24]['profileComplete'] = $profileComplete;
            $data[24]['modifiedDate'] = ($institutionUtilitiesTelephoneData->modified) ? date("F j,Y", strtotime($institutionUtilitiesTelephoneData->modified)) : date("F j,Y", strtotime($institutionUtilitiesTelephoneData->created));
        } else {
            $data[24]['complete'] = 'no';
            $data[24]['profileComplete'] = 0;
            $data[24]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        // Assets
        $institutionAssets = TableRegistry::getTableLocator()->get('Institution.InstitutionAssets');
        $institutionAssetsData = $institutionAssets->find()
            ->select([
                'created' => 'InstitutionAssets.created',
                'modified' => 'InstitutionAssets.modified',
            ])
            ->where([$institutionAssets->aliasField('institution_id') => $institutionId])
            ->order(['InstitutionAssets.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[25]['feature'] = 'Assets';
        if (!empty($institutionAssetsData)) {
            $profileComplete = $profileComplete + 1;
            $data[25]['complete'] = 'yes';
            $data[25]['profileComplete'] = $profileComplete;
            $data[25]['modifiedDate'] = ($institutionAssetsData->modified) ? date("F j,Y", strtotime($institutionAssetsData->modified)) : date("F j,Y", strtotime($institutionAssetsData->created));
        } else {
            $data[25]['complete'] = 'no';
            $data[25]['profileComplete'] = 0;
            $data[25]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Transport
        $institutionTransport = TableRegistry::getTableLocator()->get('institution_buses');
        $institutionTransportData = $institutionTransport->find()
            ->where([$institutionTransport->aliasField('institution_id') => $institutionId])
            ->order(['institution_buses.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[26]['feature'] = 'Transport';
        if (!empty($institutionTransportData)) {
            $profileComplete = $profileComplete + 1;
            $data[26]['complete'] = 'yes';
            $data[26]['profileComplete'] = $profileComplete;
            $data[26]['modifiedDate'] = ($institutionTransportData->modified) ? date("F j,Y", strtotime($institutionTransportData->modified)) : date("F j,Y", strtotime($institutionTransportData->created));
        } else {
            $data[26]['complete'] = 'no';
            $data[26]['profileComplete'] = 0;
            $data[26]['modifiedDate'] = 'Not updated';
        }
        /********************************************* */
        //Committees
        $institutionCommittees = TableRegistry::getTableLocator()->get('institution_committees');
        $institutionCommitteesData = $institutionCommittees->find()
            ->select([
                'created' => 'institution_committees.created',
                'modified' => 'institution_committees.modified',
            ])
            ->where([$institutionCommittees->aliasField('institution_id') => $institutionId])
            ->order(['institution_committees.modified' => 'desc'])
            ->limit(1)
            ->first();

        $data[27]['feature'] = 'Committees';
        if (!empty($institutionCommitteesData)) {
            $profileComplete = $profileComplete + 1;
            $data[27]['complete'] = 'yes';
            $data[27]['profileComplete'] = $profileComplete;
            $data[27]['modifiedDate'] = ($institutionCommitteesData->modified) ? date("F j,Y", strtotime($institutionCommitteesData->modified)) : date("F j,Y", strtotime($institutionCommitteesData->created));
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
        $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $typeList = $ConfigItem
            ->find('list', [
                'keyField' => 'name',
                'valueField' => 'name'
            ])
            ->order('type')
            ->where([
                $ConfigItem->aliasField('visible') => 1,
                $ConfigItem->aliasField('value') => 1,
                $ConfigItem->aliasField('type') => 'Institution Data Completeness'
            ])//POCOR-6022
            ->toArray();

        $typeOptions = array_keys($typeList);
        $totalProfileComplete = count($data);
        $typeListDisable = $ConfigItem
            ->find('list', [
                'keyField' => 'name',
                'valueField' => 'name'
            ])
            ->order('type')
            ->where([
                $ConfigItem->aliasField('visible') => 1,
                $ConfigItem->aliasField('value') => 0,
                $ConfigItem->aliasField('type') => 'Institution Data Completeness'
            ])//POCOR-6022
            ->toArray();
        if ($typeListDisable) {
            $countList = count($typeListDisable);
            $profileComplete = $profileComplete - $countList;
        }

        foreach ($data as $key => $featureData) {
            if (!in_array($featureData['feature'], $typeOptions)) {
                unset($data[$key]);
                $totalProfileComplete = count($data);
            }
        }

        $profilePercentage = 100 / $totalProfileComplete * $profileComplete;
        $profilePercentage = round($profilePercentage);
        $data['percentage'] = $profilePercentage;
        return $data;
    }

    public
    function ajaxInstitutionAutocomplete()
    {
        $this->ControllerAction->autoRender = false;
        $data = [];
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if ($this->request->is(['ajax'])) {
            $term = trim($this->request->getQuery('term'));
            $session = $this->request->getSession();
            $institutionId = $this->getInstitutionID();
            $params['conditions'] = [$Institutions->aliasField('id') . ' IS NOT ' => $institutionId];
            if (!empty($term)) {
                $data = $Institutions->autocomplete($term, $params);
            }

            echo json_encode($data);
            die;
        }
    }

    public
    function getCareerTabElements($options = [])
    {
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        $options['url'] = ['plugin' => 'Institution',
            'controller' => 'Institutions'];
        if ($institutionId) {
            $options['institution_id'] = $institutionId;
        }
        return TableRegistry::getTableLocator()->get('Staff.Staff')->getCareerTabElements($options);
    }

    public
    function getTrainingTabElements($options = [])
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
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
            $tabElements[$key]['url'] = array_merge($trainingUrl, ['action' => $key, 'index', $encodedQueryString]);

            if ($key == 'Courses') {
                $trainingUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
                $tabElements[$key]['url'] = array_merge($trainingUrl, ['action' => $key, 'index', $encodedQueryString]);
            }
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public
    function getProfessionalTabElements($options = [])
    {
        $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        $tabElements = TableRegistry::getTableLocator()->get('Staff.Staff')->getProfessionalTabElements($options);
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public
    function ajaxGetReportCardStatusProgress()
    {
        $this->autoRender = false;
        $dataSet = [];

        if (!is_null($this->request->getQuery('ids'))) {
            $ids = $this->request->getQuery('ids');

            $academicPeriodId = $this->request->getQuery('academic_period_id');
            $reportCardId = $this->request->getQuery('report_card_id');
            $institutionId = $this->request->getQuery('institution_id');

            $institutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $reportCardProcesses = TableRegistry::getTableLocator()->get('ReportCard.ReportCardProcesses');
            $institutionStudentsReportCards = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsReportCards');

            if (!empty($ids)) {

                $results = $institutionClasses
                    ->find()
                    ->select([
                        'id', 'name', 'institution_id',
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
                            $institutionStudentsReportCards = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentsReportCards');
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

    public
    function deleteCommiteeMeetingById()
    {
        if (!is_null($this->request->getQuery('meetingId'))) {
            $meetingId = $this->request->getQuery('meetingId');

            $users_table = TableRegistry::getTableLocator()->get('institution_committee_meeting');
            $users = $users_table->get($meetingId);
            $users_table->delete($users);
            echo "Meeting deleted successfully.";
            die;
        }
    }

    public
    function InstitutionProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionsProfile']);
    }

    public
    function StaffProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffProfiles']);
    }

    public
    function StudentProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentProfiles']);
    }

    public
    function ClassesProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ClassesProfiles']);
    }

    public
    function getAcademicPeriod()
    {
        $academic_periods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academic_periods_result = $academic_periods
            ->find()
            ->select(['id', 'name', 'current','start_date','end_date', 'start_year', 'end_year'])//POCOR-8434
            ->where(['code !=' => 'All', 'visible' => 1])
            ->order([$academic_periods->aliasField('id DESC')])
            ->toArray();
        foreach ($academic_periods_result as $result) {
            $result_array[] = array("id" => $result['id'],
                "name" => $result['name'],
                "current" => $result['current'],
                "start_date" => $result['start_date'],//POCOR-8434
                "end_date" => $result['end_date'],//POCOR-8434
                "start_year" => $result['start_year'],//POCOR-8434
                "end_year" => $result['end_year']//POCOR-8434
            );
        }
        echo json_encode($result_array);
        die;
    }

    /**
     * POCOR-8231
     * Retrieves the education grades for a given academic period and institution.
     *
     * @throws \Exception If an error occurs during the query.
     *
     */
    public function getEducationGrade()
    {
        $requestData = $this->getRequestData();
//        Log::debug(__FUNCTION__);
//        Log::debug(print_r($requestData, true));
        if (isset($requestData['institution_id'])) {
            $institutionId = $requestData['institution_id'];
        } else {
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        }
        $institutionName = '';

        if (!empty($institutionId)) {
            $activeInstitution = self::getDynamicTableInstance('Institution.Institutions')->get($institutionId);
            $institutionName = $activeInstitution->name;
        }
        if (!isset($requestData['institution_id'])) {
            $institutions = self::getDynamicTableInstance('Institution.Institutions');
            $institution = $institutions
                ->find()
                ->select(['id', 'name'])
                ->where(['name' => $institutionName])
                ->first();

            if (!empty($institution)) {
                $institutionId = $institution->id;
            }
        }

        $academicPeriodId = $requestData['academic_periods'];
        $academicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
        $academicPeriodResult = $academicPeriods
            ->find()
            ->select(['id', 'name', 'start_date', 'end_date'])
            ->where(['id' => $academicPeriodId])
            ->first();

        $startDate = date('Y-m-d', strtotime($academicPeriodResult->start_date));
        $endDate = date('Y-m-d', strtotime($academicPeriodResult->end_date));

        $institutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
        $institutionGradesResult = $institutionGrades
            ->find()
            ->select([
                'id' => $institutionGrades->aliasField('id'),
                'academic_period_id' => $institutionGrades->aliasField('academic_period_id'),
                'EducationGrades.id',
                'EducationGrades.name',
                'end_date' => $institutionGrades->aliasField('end_date'),
                'start_date' => $institutionGrades->aliasField('start_date'),
            ])
            ->innerJoin(['EducationGrades' => 'education_grades'], [
                'EducationGrades.id = ' . $institutionGrades->aliasField('education_grade_id')
            ])
            ->innerJoin(['EducationProgrammes' => 'education_programmes'], [
                'EducationProgrammes.id = EducationGrades.education_programme_id'
            ])
            ->innerJoin(['EducationCycles' => 'education_cycles'], [
                'EducationCycles.id = EducationProgrammes.education_cycle_id'
            ])
            ->innerJoin(['EducationLevels' => 'education_levels'], [
                'EducationLevels.id = EducationCycles.education_level_id'
            ])
            ->innerJoin(['EducationSystems' => 'education_systems'], [
                'EducationSystems.id = EducationLevels.education_system_id'
            ])
            ->where([
                $institutionGrades->aliasField('institution_id') => $institutionId,
                $institutionGrades->aliasField('academic_period_id') => $academicPeriodId,
                'EducationSystems.academic_period_id' => $academicPeriodId,
                'OR' => [
                    [
                        $institutionGrades->aliasField('end_date') . ' IS NOT NULL',
                        $institutionGrades->aliasField('start_date') . ' <=' => $startDate,
                        $institutionGrades->aliasField('end_date') . ' >=' => $startDate
                    ],
                    [
                        $institutionGrades->aliasField('end_date') . ' IS NOT NULL',
                        $institutionGrades->aliasField('start_date') . ' <=' => $endDate,
                        $institutionGrades->aliasField('end_date') . ' >=' => $endDate
                    ],
                    [
                        $institutionGrades->aliasField('end_date') . ' IS NOT NULL',
                        $institutionGrades->aliasField('start_date') . ' >=' => $startDate,
                        $institutionGrades->aliasField('end_date') . ' <=' => $endDate
                    ],
                    [
                        $institutionGrades->aliasField('end_date') . ' IS NULL',
                        $institutionGrades->aliasField('start_date') . ' <=' => $endDate
                    ]
                ]
            ])
            ->group([$institutionGrades->aliasField('education_grade_id')])
            ->toArray();

        $resultArray = [];
        foreach ($institutionGradesResult as $result) {
            $resultArray[] = [
                'id' => $result['id'],
                'education_grade_id' => $result['EducationGrades']['id'],
                'name' => $result['EducationGrades']['name'],
                'start_date' => $result['start_date'],
                'end_date' => $result['end_date'],
                'academic_period_id' => $result['academic_period_id']
            ];
        }
//        Log::debug(print_r($resultArray, true));
        $this->sendJsonResponse($resultArray);

    }

    /**
     * Retrieves the request data. POCOR-8231
     *
     * @return array The request data
     * @throws \Exception If there is an error in decoding the request data
     *
     */
    private function getRequestData(): array
    {
        $requestData = $this->request->input('json_decode', true);
        if ($requestData === null) {
            throw new \Exception('Invalid JSON in request data');
        }
        return $requestData['params'] ?? $requestData;
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    /**
     * Sends a JSON response. POCOR-8231
     *
     * @param array $data The response data
     * @param int $statusCode The HTTP status code (default is 200)
     * @return \Cake\Http\Response The JSON response
     *
     */
    private function sendJsonResponse(array $data, int $statusCode = 200): Response
    {
        $this->autoRender = false;
        $this->response = $this->response
            ->withStatus($statusCode)
            ->withType('application/json')
            ->withStringBody(json_encode($data, JSON_PRETTY_PRINT));
        return $this->response;
    }

    public
    function getClassOptions()
    {
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        $academic_period = $requestData['academic_period'];
        $grade_id = $requestData['grade_id'];
        $institution_id = $this->getInstitutionID();

        $institution_classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $institution_classes_result = $institution_classes
            ->find()
            ->select([
                $institution_classes->aliasField('id'),
                $institution_classes->aliasField('name')
            ])
            ->InnerJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                'InstitutionClassGrades.institution_class_id = ' . $institution_classes->aliasField('id'),
                'InstitutionClassGrades.education_grade_id = ' . $grade_id,
            ])
            ->where([
                $institution_classes->aliasField('academic_period_id') => $academic_period,
                $institution_classes->aliasField('institution_id') => $institution_id
            ])
            ->group([$institution_classes->aliasField('id')])
            ->toArray();

        foreach ($institution_classes_result as $result) {
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);
        die;
    }

    /**
     * Retrieves and returns position types. POCOR-8231
     *
     * @return void
     * @throws \Exception If there is an error in encoding JSON
     */
    public function getPositionType()
    {
        $postype = [
            'Full-Time' => 'Full-Time',
            'Part-Time' => 'Part-Time'
        ];

        $resultArray = array_map(function ($type) {
            return ["id" => $type, "name" => __($type)];
        }, $postype);

        try {
            $this->sendJsonResponse($resultArray);
        } catch (\Exception $e) {
            Log::error('Error in getPositionType: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => 'An error occurred while processing the request.'], 500);
        }
    }

    /**
     * Retrieves and returns FTE (Full-Time Equivalent) options. POCOR-8231
     *
     * @return void
     * @throws \Exception If there is an error in encoding JSON
     */
    public function getFTE()
    {
        $ftetype = [
            '0.25' => '25%',
            '0.5' => '50%',
            '0.75' => '75%'
        ];

        $resultArray = array_map(function ($key, $value) {
            return ["id" => $key, "name" => $value];
        }, array_keys($ftetype), $ftetype);

        try {
            $this->sendJsonResponse($resultArray);
        } catch (\Exception $e) {
            Log::error('Error in getFTE: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => 'An error occurred while processing the request.'], 500);
        }
    }

    public
    function getStaffPosititonGrades()
    {
        // POCOR-9037 start
        $requestData = $this->request->input('json_decode', true);
        $insPostionData = null;
        if ($requestData) {
            $requestData = $requestData['params'];
            $institution_position_id = $requestData['institution_position_id'];
            $institution_positions_tbl = self::getDynamicTableInstance('Institution.InstitutionPositions');
            if($institution_position_id){
            $insPostionData = $institution_positions_tbl->find('all', ['conditions' => ['id' => $institution_position_id]])->first();
            }
        }


        if ($insPostionData) {
            $staff_position_title_id = $insPostionData->staff_position_title_id;

            $staff_position_titles_grades_tbl = self::getDynamicTableInstance('staff_position_titles_grades');
            $staff_position_titles_grades_data = $staff_position_titles_grades_tbl->find('all')->where(['staff_position_title_id' => $staff_position_title_id])->toArray();

            $grade_ids_array = [];
            foreach ($staff_position_titles_grades_data as $grade_id => $data1) {
                $grade_ids_array[$grade_id] = $data1->staff_position_grade_id;
            }

            $staff_position_grades = self::getDynamicTableInstance('staff_position_grades');
            $result_array = [];

            if (!empty($grade_ids_array) && $grade_ids_array[0] == '-1') {
                $staff_position_grades_result = $staff_position_grades
                    ->find()
                    ->select(['id', 'name'])
                    ->where(['visible' => 1])
                    ->toArray();
            } else {
                $staff_position_grades_result = $staff_position_grades
                    ->find()
                    ->select(['id', 'name'])
                    ->where(['visible' => 1, 'id IN' => $grade_ids_array])
                    ->toArray();
            }

            foreach ($staff_position_grades_result as $result) {
                $result_array[] = ["id" => $result['id'], "name" => __($result['name'])];
            }

            echo json_encode($result_array);
        } else {
            echo json_encode([]); // Handle the case where no position data is found
        }
        // POCOR-9037 end
        die;

    }

    /**
     * Retrieves and returns staff types. POCOR-8231
     *
     * @return void
     * @throws \Exception If there is an error in encoding JSON
     */
    public
    function getStaffType()
    {
        $staffTypesTable = self::getDynamicTableInstance('Staff.StaffTypes');
        $staffTypesResult = $staffTypesTable->find()
            ->select(['id', 'name'])
            ->where(['visible' => 1])
            ->toArray();

        $resultArray = array_map(function ($result) {
            return ["id" => $result['id'], "name" => __($result['name'])];
        }, $staffTypesResult);

        try {
            $this->sendJsonResponse($resultArray);
        } catch (\Exception $e) {
            Log::error('Error in getStaffType: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => 'An error occurred while processing the request.'], 500);
        }
    }

    public
    function getShifts()
    {   //get current academic period
        $academic_periods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academic_periods_result = $academic_periods
            ->find()
            ->select(['id', 'name'])
            ->where(['current' => 1, 'visible' => 1])
            ->first();

        $academic_period_id = !empty($academic_periods_result) ? $academic_periods_result->id : 0;
        $institutionId = $this->getInstitutionID();
        $shift = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
        $shiftData = $shift->find('all',
            ['contain' => [
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
        echo json_encode($result_array);
        die;
    }

    /**
     * Retrieves institution positions based on the request data. POCOR-8231
     *

     * @throws \Exception If there is an error in decoding the request data
     *
     */
    public function getPositions()
    {
        try {
            $requestData = $this->getRequestData();
            $fte = $requestData['fte'] ?? null;
            $startDate = $requestData['startDate'] ?? null;
            $institutionId = $requestData['institution_id'] ?? null;
            $openemisNo = $requestData['openemis_no'] ?? null;

            $endDate = null; // Ensure endDate is set to null as required

            $resultArray = $this->getInstitutionPositions($institutionId, $fte, $startDate, $endDate, $openemisNo);
//            Log::debug(print_r($resultArray, true));
            return $this->sendJsonResponse($resultArray);
        } catch (\Exception $e) {
            Log::error('Error in getPositions: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => 'An error occurred while processing the request.'], 500);
        }
        die;
    }

    /**
     * Gets the institution positions based on various parameters. POCOR-8231
     *
     * @param int $institutionId The institution ID
     * @param float|null $fte The full-time equivalent value
     * @param string|null $startDate The start date
     * @param string|null $endDate The end date
     * @param string|null $openemisNo The OpenEMIS number
     * @param string $staffUserPriId The staff user primary ID
     * @throws \Exception If there is an error in retrieving positions
     */
    public function getInstitutionPositions($institutionId, $fte, $startDate, $endDate, $openemisNo, $staffUserPriId = '')
    {
        // Normalize null values
        $endDate = $endDate === 'null' ? null : new Date($endDate);
        $startDate = $startDate === 'null' ? null : new Date($startDate);

        $this->autoRender = false;
        $userId = $this->Auth->user('id');

        $StaffTable = self::getDynamicTableInstance('Institution.Staff');
        $positionTable = self::getDynamicTableInstance('Institution.InstitutionPositions');

        $selectedFTE = empty($fte) ? 0 : $fte;

        $excludePositions = $this->getExcludePositions($StaffTable, $institutionId, $selectedFTE, $startDate, $endDate);
//        Log::debug('$excludePositions');
//        Log::debug(print_r($excludePositions, true));

        if ($this->AccessControl->isAdmin()) {
            $userId = null;
            $roles = [];
        } else {
            $roles = $StaffTable->Institutions->getInstitutionRoles($userId, $institutionId);
        }
//        Log::debug('$roles');
//        Log::debug(print_r($roles, true));

        $registryAlias = $positionTable->getRegistryAlias();
//        Log::debug($registryAlias);
        $ActiveStatus = $this->Workflow->getStepsByModelCode($registryAlias, 'ACTIVE');
        $InactiveStatus = $this->Workflow->getStepsByModelCode($registryAlias, 'INACTIVE');
        $ActiveStatusId = (int)reset($ActiveStatus);
        $InactiveStatusId = (int)reset($InactiveStatus);
//        Log::debug(print_r($ActiveStatusId, true));
//        Log::debug(print_r($InactiveStatusId, true));
        if (!$ActiveStatusId) {
            return [];
        }
        $positionConditions = [
            $StaffTable->Positions->aliasField('institution_id') => $institutionId,
            $StaffTable->Positions->aliasField('status_id') => $ActiveStatusId,
        ];

        $SecurityUsers = self::getDynamicTableInstance('User.Users');
        $SecurityUsersData = $SecurityUsers->find()
            ->where([$SecurityUsers->aliasField('openemis_no') => $openemisNo])
            ->first();
        $staffUserPriId = $SecurityUsersData->id;
        $expectedStaffStatuses = $this->getSpecificInstitutionStaff($institutionId, $staffUserPriId);
//        Log::debug('$expectedStaffStatuses');
//        Log::debug(print_r($expectedStaffStatuses, true));

        if (!empty($expectedStaffStatuses && !empty($staffUserPriId))) {//POCOR-8613
            $positionConditions[$StaffTable->Positions->aliasField('staff_position_title_id') . ' NOT IN '] = $expectedStaffStatuses;
        }

        $SecurityGroupUsers = self::getDynamicTableInstance('Security.SecurityGroupUsers');
        $staffMinRole = $SecurityGroupUsers->find()
            ->contain('SecurityRoles')
            ->order(['SecurityRoles.order'])
            ->where([$SecurityGroupUsers->aliasField('security_user_id') => $this->Auth->user()['id']])
            ->first();
//        Log::debug('$staffMinRole');
//        Log::debug(print_r($staffMinRole, true));

        if ($this->Auth->user()['super_admin'] != 1 && isset($staffMinRole->security_role->order) && $staffMinRole->security_role->order > 0) {
            $positionConditions['SecurityRoles.order >= '] = $staffMinRole->security_role->order;
        }

        $staffPositionsOptions = $this->getStaffPositionsOptions($StaffTable, $positionConditions, $selectedFTE);
        $roleOptions = $this->getRoleOptions($userId, $roles);
//        Log::debug('$staffPositionsOptions');
//        Log::debug(print_r($staffPositionsOptions, true));
        $staffPositionRoles = array_column($staffPositionsOptions, 'security_role_id');
//        Log::debug('$staffPositionRoles');
//        Log::debug(print_r($staffPositionRoles, true));
        $staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));
//        Log::debug('$staffPositionsOptions');
//        Log::debug(print_r($staffPositionsOptions, true));
        $positionHeldByStaff = $this->getPositionHeldByStaff($StaffTable, $institutionId, $openemisNo);
//        Log::debug('$positionHeldByStaff');
//        Log::debug(print_r($positionHeldByStaff, true));
        $excludePositions = array_merge($excludePositions, array_column($positionHeldByStaff, 'position_id'));
//        Log::debug('$excludePositions');
//        Log::debug(print_r($positionHeldByStaff, true));
        $options = $this->formatPositionOptions($staffPositionsOptions, $excludePositions);
//        Log::debug('$options');
//        Log::debug(print_r($options, true));

        return $options;
    }

    /**
     * Retrieves the exclude positions based on various parameters. POCOR-8231
     *
     * @param Table $StaffTable The Staff Table instance
     * @param int $institutionId The institution ID
     * @param float|null $selectedFTE The selected full-time equivalent value
     * @param string|null $startDate The start date
     * @param string|null $endDate The end date
     * @return array The exclude positions
     */
    private function getExcludePositions($StaffTable, $institutionId, $selectedFTE, $startDate, $endDate)
    {
        $excludePositions = $StaffTable->find()
            ->select(['position_id' => $StaffTable->aliasField('institution_position_id')])
            ->where([$StaffTable->aliasField('institution_id') => $institutionId])
            ->group($StaffTable->aliasField('institution_position_id'))
            ->having([
                'OR' => [
                    'SUM(' . $StaffTable->aliasField('FTE') . ') >= ' => 1,
                    'SUM(' . $StaffTable->aliasField('FTE') . ') > ' => (1 - $selectedFTE),
                ]
            ])
            ->disableHydration();

        if (!empty($endDate)) {
            $excludePositions = $excludePositions->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate]);
        } else {
            $excludePositions = $excludePositions->where([
                'OR' => [
                    $StaffTable->aliasField('end_date') . ' >= ' => $startDate,
                    $StaffTable->aliasField('end_date') . ' IS NULL'
                ]
            ]);
        }

        $result = $excludePositions->toArray();
        return array_column($result, 'position_id');
    }

    /**
     * Get staff details of specific institution
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6522
     */
    private
    function getSpecificInstitutionStaff($institution_id, $staff_id)
    {
        $StaffStatusesTable = TableRegistry::getTableLocator()->get('Staff.StaffStatuses');
        $institutionPositionsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
        $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
        $alreadyAssignedStaffs = $StaffTable->find()->select([
            'institution_position_id' => $StaffTable->aliasField('institution_position_id'),
            'status_id' => $institutionPositionsTable->aliasField('status_id'),
            'staff_position_title_id' => $institutionPositionsTable->aliasField('staff_position_title_id')//POCOR-8613
        ])->innerJoin([$institutionPositionsTable->getAlias() => $institutionPositionsTable->getTable()], [
            $institutionPositionsTable->aliasField('id = ') . $StaffTable->aliasField('institution_position_id'),
        ])->where([
            $StaffTable->aliasField('institution_id') => $institution_id,
            //$StaffTable->aliasField('staff_id') => $staff_id,
            $StaffTable->aliasField('staff_status_id') => $StaffStatusesTable->getIdByCode('ASSIGNED'),
        ]);
        if(!empty($staff_id)) {
            $alreadyAssignedStaffs = $alreadyAssignedStaffs->where([$StaffTable->aliasField('staff_id') => $staff_id]);
        }
        $alreadyAssignedStaffs = $alreadyAssignedStaffs->enableHydration(false)->toArray();
        $expectedStaffStatuses = [];
        foreach ($alreadyAssignedStaffs as $staff) {
            $expectedStaffStatuses[$staff['staff_position_title_id']] = $staff['staff_position_title_id'];
        }
        return $expectedStaffStatuses;
    }


    /**
     * Retrieves the staff positions options based on various conditions. POCOR-8231
     *
     * @param \Cake\ORM\Table $StaffTable The Staff Table instance
     * @param array $positionConditions The position conditions
     * @param float|null $selectedFTE The selected full-time equivalent value
     * @return array The staff positions options
     */
    private function getStaffPositionsOptions($StaffTable, $positionConditions, $selectedFTE)
    {
        if ($selectedFTE > 0) {
            $query = $StaffTable->Positions->find()
                ->innerJoinWith('StaffPositionTitles.SecurityRoles')
                ->where($positionConditions)
                ->select([
                    'id' => 'Positions.id',
                    'institution_id' => 'Positions.institution_id',
                    'status_id' => 'Positions.status_id',
                    'position_no' => 'Positions.position_no',
                    'security_role_id' => 'SecurityRoles.id',
                    'security_role_name' => 'SecurityRoles.name',
                    'staff_position_tytle_id' => 'StaffPositionTitles.id',
                    'type' => 'StaffPositionTitles.type',
                    'position_name' => 'StaffPositionTitles.name',
                    'StaffPositionTitles.name',
                    'order_name' => 'StaffPositionTitles.order',
                    // Add other fields as needed
                ])
                ->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order']);

            $result = $query->all()->toArray();
            $resultArray = json_decode(json_encode($result), true);
//            Log::debug(print_r($result, true));
            return $resultArray;
        }
        return [];
    }

    /**
     * Retrieves the role options based on the user ID and roles. POCOR-8231
     *
     * @param int|null $userId The user ID
     * @param array $roles The roles
     * @return array The role options
     */
    private function getRoleOptions($userId, $roles)
    {
        $SecurityRolesTable = self::getDynamicTableInstance('Security.SecurityRoles');
        return array_keys($SecurityRolesTable->getRolesOptions($userId, $roles));
    }

    /**
     * Retrieves the positions held by staff based on various parameters. POCOR-8231
     *
     * @param Table $StaffTable The Staff Table instance
     * @param int $institutionId The institution ID
     * @param string $openemisNo The OpenEMIS number
     * @return array The positions held by staff
     */
    private function getPositionHeldByStaff($StaffTable, $institutionId, $openemisNo)
    {
        $query = $StaffTable->find()
            ->select(['position_id' => $StaffTable->aliasField('institution_position_id')])
            ->contain(['Users'])
            ->where([
                $StaffTable->aliasField('institution_id') => $institutionId,
                'Users.openemis_no' => $openemisNo,
                 $StaffTable->aliasField('end_date') . ' IS NULL'
            ])
            ->disableHydration();

        # Log::debug($query->sql());
        $result = $query->toArray();
        # Log::debug(print_r($result, true));

        return $result;
    }

    /**
     * Formats the staff positions options into a structured array. POCOR-8231
     *
     * @param array $staffPositionsOptions The staff positions options
     * @param array $excludePositions The exclude positions
     * @return array The formatted position options
     */
    private function formatPositionOptions($staffPositionsOptions, $excludePositions)
    {
        $types = $this->getSelectOptions('Staff.position_types');
        $options = [];
        foreach ($staffPositionsOptions as $key => $position) {
//        Log::debug(print_r($position, true));
            $name = $position['position_no'] . ' - ' . $position['position_name'];
            $type = $types[$position['type']] ? $types[$position['type']] : "";
            $options[] = [
                'value' => $position['id'],
                'group' => $type,
                'name' => $name,
                'disabled' => in_array($position['id'], $excludePositions)
            ];
        }

        return $options;
    }

    public
    function checkStudentAdmissionAgeValidation()
    {
        $requestData = $this->getRequestData();
//        $this->log($requestData);
        $start_date = '01-01-1911';
        $dateOfBirth = $this->getValue($requestData, 'date_of_birth', $start_date);
        $educationGradeId = $this->getValue($requestData, 'education_grade_id', -1);
        $academic_period_id = $this->getValue($requestData, 'academic_period_id', -1);

        $academic_periods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academic_periods_result = $academic_periods
            ->find()
            ->select(['id', 'name', 'start_date', 'end_date'])
            ->where(['id' => $academic_period_id])
            ->first();
        if ($academic_periods_result) {
            $start_date = $academic_periods_result->start_date;
        }
        $startDate = new \DateTime($start_date);
        $dob = new \DateTime($dateOfBirth);

        $interval = $dob->diff($startDate);

        $ageInYears = $interval->y;
        $ConfigItemTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $ConfigItemAgePlus = $ConfigItemTable->find('all', ['conditions' => ['code' => 'admission_age_plus']])->first();
        $ConfigItemAgeMinus = $ConfigItemTable->find('all', ['conditions' => ['code' => 'admission_age_minus']])->first();
        $EducationGradesTable = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $EducationGrades = $EducationGradesTable->find('all', ['conditions' => ['id' => $educationGradeId]])->first();
        $maxAge = ($EducationGrades->admission_age + $ConfigItemAgePlus->value);
        $minAge = $EducationGrades->admission_age - $ConfigItemAgeMinus->value;
        if ($minAge < 0) {
            $minAge = 0;
        }
        $result = array(
            "max_age" => $maxAge,
            "min_age" => $minAge,
            "student_age" => $ageInYears,
//            "startDate" => $startDate,
//            "dob" => $dob,
//            "configItemAgePlus" => $ConfigItemAgePlus,
//            "configItemAgeMinus" => $ConfigItemAgeMinus,
//            "academic_period_id" => $academic_period_id
        );
        if ($ageInYears > $maxAge || $ageInYears < $minAge) {
            $result["validation_error"] = 1;
        } else {
            $result["validation_error"] = 0;
        }
        $result_array[] = $result;
        echo json_encode($result_array);
        die;
    }

    /**
     * Gets a value from the request data with a default fallback. POCOR-8231
     *
     * @param array $requestData The request data
     * @param string $key The key to look for
     * @param mixed $default The default value if the key is not found
     * @return mixed The value from the request data or the default value
     *
     */
    private function getValue(array $requestData, string $key, $default = null)
    {
        return isset($requestData[$key]) ? $requestData[$key] : $default;
    }

    public
    function getStartDateFromAcademicPeriod()
    {
        $requestData = $this->request->input('json_decode', true);
        $academicPeriodId = $requestData['params']['academic_period_id'];

        $AcademicPeriodsTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academic_periods_result = $AcademicPeriodsTable
            ->find()
            ->where(['id' => $academicPeriodId])
            ->toArray();
        foreach ($academic_periods_result as $result) {
            $result_array[] = array("id" => $result['id'], "name" => $result['name'],
                "start_date" => date('Y-m-d', strtotime($result['start_date'])),
                "start_year" => $result['start_year'] ?? date('Y', strtotime($result['start_date'])),
                "end_date" => date('Y-m-d', strtotime($result['end_date'])),
                "end_year" => $result['end_date'] ? ($result['end_year'] ?? date('Y', strtotime($result['end_date']))) : null);
        }
        echo json_encode($result_array);
        die;
    }

    public
    function getStudentTransferReason()
    {
        $student_transfer_reasons = TableRegistry::getTableLocator()->get('Student.StudentTransferReasons');
        $student_transfer_reasons_result = $student_transfer_reasons
            ->find()
            ->select(['id', 'name'])
            ->where(['visible' => 1])
            ->order([$student_transfer_reasons->aliasField('order ASC')])
            ->toArray();
        foreach ($student_transfer_reasons_result as $result) {
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);
        die;
    }

    public
    function studentCustomFields()
    {
        $this->autoRender = false;
        $requestData = $this->getRequestData();
        $studentId = $this->getValue($requestData, 'student_id', '');
//        Log::debug($studentId);

        $prefix = 'Student';
        $tables = $this->getTables($prefix);
        $customModuleId = $this->getCustomModuleId('Student');

        $sections = $this->getSections($tables["{$prefix}CustomForms"], $tables["{$prefix}CustomFormsFields"], $customModuleId, $prefix);

        $fieldsArr = $this->getFieldsData($sections, $tables, $studentId, ['COORDINATES', 'TABLE'], $prefix, $customModuleId); // POCOR-8917
        foreach ($fieldsArr as &$item) {
            $item[$this->getPrefixedFieldName($prefix, 'custom_form_id')] = $item['custom_form_id'];
            $item[$this->getPrefixedFieldName($prefix, 'custom_field_id')] = $item['custom_field_id'];
        }
        unset($item); // Unset reference to avoid potential issues

//        Log::debug(print_r($fieldsArr, true));
        echo json_encode($fieldsArr);
        die;
    }

    /**
     * POCOR-8538
     * @return void
     * @throws Exception
     */
    public
    function classCustomFields()
    {
        $this->autoRender = false;
        $requestData = $this->getRequestData();
        $prefix = 'InstitutionClasses';
        $tables = $this->getTables($prefix);
        $customModuleId = $this->getCustomModuleId('Institution > Classes');
        $sections = $this->getSections($tables["{$prefix}CustomForms"], $tables["{$prefix}CustomFormsFields"], $customModuleId, $prefix);
        $classId = $this->getValue($requestData, 'class_id', 0) ?? 0;
        $fieldsArr = $this->getFieldsData($sections, $tables, $classId, ['COORDINATES', 'TABLE'], $prefix, $customModuleId);

        foreach ($fieldsArr as &$item) {

            $item[$this->getPrefixedFieldName($prefix, 'custom_form_id')] = $item['custom_form_id'];
            $item[$this->getPrefixedFieldName($prefix, 'custom_field_id')] = $item['custom_field_id'];
            if($item['section'] === ""){
                $item['section'] = __('Additional Information');
            }
            if(empty($item['section'])){
                $item['section'] = __('Additional Information');
            }
            if($item['section'] === null){
                $item['section'] = __('Additional Information');
            }
        }
        unset($item); // Unset reference to avoid potential issues

        echo json_encode($fieldsArr);
        die;
    }

    private function getTables($prefix)
    {
        //POCOR-8538 start

        if ($prefix != "InstitutionClasses") {
            return [
                "{$prefix}CustomForms" => self::getDynamicTableInstance("{$prefix}CustomField.{$prefix}CustomForms"),
                "{$prefix}CustomFormsFields" => self::getDynamicTableInstance("{$prefix}CustomField.{$prefix}CustomFormsFields"),
                "{$prefix}CustomFields" => self::getDynamicTableInstance("{$prefix}CustomField.{$prefix}CustomFields"),
                "{$prefix}CustomFieldOptions" => self::getDynamicTableInstance("{$prefix}CustomField.{$prefix}CustomFieldOptions"),
                "{$prefix}CustomFieldValues" => self::getDynamicTableInstance("{$prefix}CustomField.{$prefix}CustomFieldValues"),
            ];
        }
        $overallPrefix = "Institution";
        return [
            "{$prefix}CustomForms" => self::getDynamicTableInstance("{$overallPrefix}CustomField.{$overallPrefix}CustomForms"),
            "{$prefix}CustomFormsFields" => self::getDynamicTableInstance("{$overallPrefix}CustomField.{$overallPrefix}CustomFormsFields"),
            "{$prefix}CustomFields" => self::getDynamicTableInstance("{$overallPrefix}CustomField.{$overallPrefix}CustomFields"),
            "{$prefix}CustomFieldOptions" => self::getDynamicTableInstance("{$overallPrefix}CustomField.{$overallPrefix}CustomFieldOptions"),
            "{$prefix}CustomFieldValues" => self::getDynamicTableInstance("{$overallPrefix}CustomField.{$prefix}CustomFieldValues"),
        ];
        //POCOR-8538 end

    }

    private function getCustomModuleId($moduleCode)
    {
        $customModulesTable = TableRegistry::getTableLocator()->get('CustomField.CustomModules');
        return $customModulesTable
            ->find()
            ->where([$customModulesTable->aliasField('code') => $moduleCode])
            ->first()
            ->id;
    }

    private function getSections($customFormsTable, $customFormsFieldsTable, $customModuleId, $prefix)
    {
        return $customFormsTable->find()
            ->select([
                'custom_form_id' => $customFormsFieldsTable->aliasField($this->getPrefixedFieldName($prefix, 'custom_form_id')),
                'custom_field_id' => $customFormsFieldsTable->aliasField($this->getPrefixedFieldName($prefix, 'custom_field_id')),
                'section' => $customFormsFieldsTable->aliasField('section'),
            ])
            ->leftJoin([$customFormsFieldsTable->getAlias() => $customFormsFieldsTable->getTable()], [
                $customFormsFieldsTable->aliasField($this->getPrefixedFieldName($prefix, 'custom_form_id')) . ' =' . $customFormsTable->aliasField('id'),
            ])
            ->where([$customFormsTable->aliasField('custom_module_id') . ' =' . $customModuleId])
            ->where(function (QueryExpression $exp, Query $q) use ($customFormsFieldsTable) {
                return $exp->isNotNull($customFormsFieldsTable->aliasField('section'));
            })
            ->group([$customFormsFieldsTable->aliasField('section')])
            ->toArray();
    }

    private function getPrefixedFieldName($prefix, $fieldName)
    {
        $_prefix = strtolower($prefix);
        //POCOR-8538 start
        if($prefix == "InstitutionClasses"){
            $_prefix = "institution";
        }
        //POCOR-8538 end
        return "{$_prefix}_{$fieldName}";
    }

    //POCOR-8538 start
    private function getFieldsData($sections,
                                   $tables,
                                   $entityId,
                                   $excludeFieldTypes,
                                   $prefix,
                                   $customModuleId)
    {
        $fieldsArr = [];
        foreach ($sections as $section) {
            $fields = $this->getCustomFields(
                $tables["{$prefix}CustomFormsFields"],
                $tables["{$prefix}CustomFields"],
                $section->section,
                $excludeFieldTypes,
                $prefix,
                $tables["{$prefix}CustomForms"],
                $customModuleId);
            foreach ($fields as $field) {
                $fieldData = $this->getFieldData($field,
                    $tables["{$prefix}CustomFieldOptions"],
                    $tables["{$prefix}CustomFieldValues"],
                    $entityId,
                    $prefix);

                $fieldsArr[] = $fieldData;
            }
        }
        return $fieldsArr;
    }

    private function getCustomFields($customFormsFieldsTable,
                                     $customFieldsTable,
                                     $section,
                                     $excludeFieldTypes,
                                     $prefix,
                                     $customFormsTable,
                                     $customModuleId)
    {
        $result = [];
        if ($section) {
            $where = [
                $customFormsTable->aliasField('custom_module_id') => $customModuleId,
                $customFormsFieldsTable->aliasField('section') => $section,
                $customFieldsTable->aliasField('field_type NOT IN') => $excludeFieldTypes,
            ];
        } else {
            $where = [
                $customFormsTable->aliasField('custom_module_id') => $customModuleId,
                $customFormsFieldsTable->aliasField('section') => "",
                $customFieldsTable->aliasField('field_type NOT IN') => $excludeFieldTypes,
            ];
        }
        $result = $customFormsFieldsTable->find()
            ->select([
                'custom_form_id' => $customFormsFieldsTable->aliasField($this->getPrefixedFieldName($prefix, 'custom_form_id')),
                'custom_field_id' => $customFormsFieldsTable->aliasField($this->getPrefixedFieldName($prefix, 'custom_field_id')),
                'section' => $customFormsFieldsTable->aliasField('section'),
                'name' => $customFormsFieldsTable->aliasField('name'),
                'field_order' => $customFormsFieldsTable->aliasField('order'), // Change alias here
                'description' => $customFieldsTable->aliasField('description'),
                'field_type' => $customFieldsTable->aliasField('field_type'),
                'is_mandatory' => $customFieldsTable->aliasField('is_mandatory'),
                'is_unique' => $customFieldsTable->aliasField('is_unique'),
                'params' => $customFieldsTable->aliasField('params'),
            ])
            ->leftJoin([$customFieldsTable->getAlias() => $customFieldsTable->getTable()], [
                $customFieldsTable->aliasField('id =') . $customFormsFieldsTable->aliasField($this->getPrefixedFieldName($prefix, 'custom_field_id')),
            ])
            ->innerJoin([$customFormsTable->getAlias() => $customFormsTable->getTable()], [
                $customFormsTable->aliasField('id =') . $customFormsFieldsTable->aliasField($this->getPrefixedFieldName($prefix, 'custom_form_id')),
            ])
            ->where($where)
            ->order([$customFormsFieldsTable->aliasField('order') => 'ASC']);
//        Log::debug($result->sql());
        $result = $result->toArray();

        // Change 'field_order' back to 'order' in the resulting array
        $result = array_map(function ($item) {
            $item['order'] = $item['field_order'];
            unset($item['field_order']);
            return $item;
        }, $result);

        return $result;
    }
    //POCOR-8538 end





    private function getFieldData($field, $customFieldOptionsTable, $customFieldValuesTable, $entityId, $prefix)
    {
        //POCOR-8538 start
        $fieldValues = $this->getFieldValues($field, $customFieldValuesTable, $entityId, $prefix);
        $fieldData = [
            'custom_form_id' => $field->custom_form_id,
            'custom_field_id' => $field->custom_field_id,
            'section' => $field->section,
            'name' => $field->name,
            'order' => $field->order,
            'description' => $field->description,
            'field_type' => $field->field_type,
            'is_mandatory' => $field->is_mandatory,
            'is_unique' => $field->is_unique,
            'params' => $field->params,
            'values' => $fieldValues,
        ];
        //POCOR-8538 end

        if (in_array($field->field_type, ['DROPDOWN', 'CHECKBOX'])) {
            $fieldData['option'] = $this->getFieldOptions($field->custom_field_id, $customFieldOptionsTable, $prefix);
        }

        return $fieldData;
    }

    private function getFieldValues($field, $customFieldValuesTable, $entityId, $prefix)
    {
        if (empty($entityId)) {
            return '';
        }
        //POCOR-8538 end
        $fieldNameId = $this->getPrefixedFieldName($prefix, 'id');
        if ($prefix == 'InstitutionClasses') {
            $fieldNameId = 'institution_class_id';
        }
        $prefixedFieldNameId = $this->getPrefixedFieldName($prefix, 'custom_field_id');
        $where = [
            $prefixedFieldNameId => $field->custom_field_id,
            $fieldNameId => $entityId,
        ];
        $select = [
            'text_value',
            'number_value',
            'decimal_value',
            'textarea_value',
            'date_value',
            'time_value',
            $prefixedFieldNameId,
            $fieldNameId
        ];


        $fieldValues = $customFieldValuesTable->find()
            ->select($select)
            ->where($where);

        $fieldValues = $fieldValues->toArray();
        //POCOR-8538 end

        if (empty($fieldValues)) {
            return '';
        }

        return $this->formatFieldValues($fieldValues, $field->field_type);
    }

    private function formatFieldValues($fieldValues, $fieldType)
    {
        switch ($fieldType) {
            case 'TEXT':
                return $fieldValues[0]->text_value;
            case 'DECIMAL':
                return $fieldValues[0]->decimal_value;
            case 'NUMBER':
                return $fieldValues[0]->number_value;
            case 'TEXTAREA':
                return $fieldValues[0]->textarea_value;
            case 'DATE':
                return date('Y-m-d', strtotime($fieldValues[0]->date_value));
            case 'TIME':
                return date('H:i:s', strtotime($fieldValues[0]->time_value));
            case 'DROPDOWN':
                return array_map(fn($val) => ['dropdown_val' => $val->number_value], $fieldValues);
            case 'CHECKBOX':
                return array_map(fn($val) => ['checkbox_val' => $val->number_value], $fieldValues);
            default:
                return '';
        }
    }

    private function getFieldOptions($fieldId, $customFieldOptionsTable, $prefix)
    {
        $prefixedFieldName = $this->getPrefixedFieldName($prefix, 'custom_field_id');
        $optionsQuery = $customFieldOptionsTable->find()
            ->select(['option_id' => $customFieldOptionsTable->aliasField('id'),
                'option_name' => $customFieldOptionsTable->aliasField('name'),
                'is_default' => $customFieldOptionsTable->aliasField('is_default'),
                'visible' => $customFieldOptionsTable->aliasField('visible'),
                'option_order' => $customFieldOptionsTable->aliasField('order')])
            ->where([$customFieldOptionsTable->aliasField($prefixedFieldName) => $fieldId]);
//        Log::debug(print_r(['$fieldId' => $fieldId], true));
//        Log::debug(print_r(['sql' => $optionsQuery->sql()], true));
        $options = $optionsQuery->toArray();

        return array_map(function ($option) {
            return [
                'option_id' => $option->option_id,
                'option_name' => $option->option_name,
                'is_default' => $option->is_default,
                'visible' => $option->visible,
                'option_order' => $option->option_order,
            ];
        }, $options);
    }

    /**
     * Get the class capacity for academic year and education grade.
     * @return array
     * @ticket POCOR-8170
     * POCOR-8231 added and fixed
     */
    public
    function getClassCapacity()
    {
        $requestData = $this->getRequestData();
        $institution_id = $this->getValue($requestData, 'institution_id', '');
        $academic_period_id = $this->getValue($requestData, 'academic_periods', '');
        $education_grade_id = $this->getValue($requestData, 'education_grade_id', '');
        $class_id = $this->getValue($requestData, 'class_id', '');
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $query = $InstitutionClasses->find('all')
            ->select([
                'capacity',
                'total_male_students',
                'total_female_students',
                'institution_id',
                'class_number',
                'academic_period_id',
                'total_students' => new QueryExpression('total_male_students + total_female_students'),
                'capacity_status' => "(CASE WHEN (total_male_students + total_female_students) < capacity THEN 'Capacity OK' ELSE 'Exceeded Capacity' END)"
            ])
            ->where([
                'institution_id' => $institution_id,
                'academic_period_id' => $academic_period_id,
                'id' => $class_id
            ])->disableHydration();

        $result = $query->first();
        return $this->sendJsonResponse($result);
    }

    public function staffCustomFields()
    {
        $this->autoRender = false;
        $requestData = $this->getRequestData();
        $staffId = $this->getValue($requestData, 'staff_id', '');
//        Log::debug($staffId);

        $prefix = 'Staff';
        $tables = $this->getTables($prefix);
        $customModuleId = $this->getCustomModuleId('Staff');

        $sections = $this->getSections($tables["{$prefix}CustomForms"], $tables["{$prefix}CustomFormsFields"], $customModuleId, $prefix);
//        Log::debug(print_r($sections, true));
        $fieldsArr = $this->getFieldsData($sections, $tables, $staffId, ['COORDINATES', 'TABLE'], $prefix, $customModuleId); // POCOR-8917
//        Log::debug(print_r($fieldsArr, true));
        foreach ($fieldsArr as &$item) {
            $item[$this->getPrefixedFieldName($prefix, 'custom_form_id')] = $item['custom_form_id'];
            $item[$this->getPrefixedFieldName($prefix, 'custom_field_id')] = $item['custom_field_id'];
        }
        unset($item); // Unset reference to avoid potential issues

//        Log::debug(print_r($fieldsArr, true));
        echo json_encode($fieldsArr);
        die;
    }

    /**
     * Saves student data. POCOR-8231
     *
     * @return \Cake\Http\Response|null
     *
     */
    public function saveDirectoryData()
    {

        if($this->savingDirectoryData > 0) {
            return;
        }
        $this->savingDirectoryData = $this->savingDirectoryData + 1;
        $requestData = $this->getRequestData();
        $userType = $requestData['user_type'];

        if($userType === self::STUDENT){
            return $this->saveStudentData();
        }
        if($userType === self::GUARDIAN){
            return $this->saveGuardianData();
        }
        if($userType === self::OTHER){
            return $this->saveOtherData();
        }
        if($userType === self::STAFF){
            return $this->saveStaffData();
        }
        if (empty($requestData)) {
            return $this->sendJsonResponse(['message' => __('Invalid data.')], 400);
        }
        return;
    }

    /**
     * Saves student data. POCOR-8231
     *
     * @return \Cake\Http\Response|null
     *
     */
    public function saveStudentData()
    {
        if($this->savingStudentData > 0) {
            self::debug('>0');
            return;
        }
        $this->savingStudentData = $this->savingStudentData + 1;

        $requestData = $this->getRequestData();
//        Log::debug(__FUNCTION__);
//        self::debug($requestData);
        if (empty($requestData)) {
            return $this->sendJsonResponse(['message' => __('Invalid data.')], 400);
        }
        $identityValidationError = $this->validateIdentityByTypePatternOrResponse($requestData, 'Student');
        if ($identityValidationError instanceof Response) {
            return $identityValidationError;
        }
//        Log::debug(print_r($requestData, true));
        $userId = $this->request->getSession()->read('Auth.User.id') ?? 1;
        $studentData = $this->extractSecurityUserData($requestData, $userId, true);
        if ($requestData['is_diff_school'] == 1) {
            $userRecordId = $requestData['student_id'];
            $result = $this->handleStudentTransfer($requestData, $userRecordId, $userId);
            return $this->sendJsonResponse(['message' => 'success', 'id' => $userRecordId], 200);
        }
        $securityUserResult = $this->saveSecurityUser($studentData);
        if ($securityUserResult instanceof \Cake\ORM\Entity || $securityUserResult instanceof EntityInterface) { // POCOR-9011
            $userRecordId = $securityUserResult->id;
            $this->handleNationalities($requestData, $userRecordId, $userId);
            $identityResult = $this->handleIdentities($requestData, $userRecordId, $userId, 'Student');
            if ($identityResult instanceof Response) {
                return $identityResult;
            }
            $this->handleContacts($requestData, $userRecordId, $userId);
            $this->handleCustomFields('student', $requestData, $userRecordId, $userId);
            //if ($requestData['student_admission_status_value'] == 0 || strtolower($requestData['student_admission_status']) == "enrolled") {//POCOR-8434
            $saved_student = $this->handleStudentInstitutionData($requestData, $userRecordId, $userId) ?? $securityUserResult; // POCOR-8776
            //}//POCOR-8434
            //            Log::debug(print_r($studentData,true));
            return $this->sendJsonResponse(['message' => 'success', 'id' => $userRecordId, 'saved_student' => $saved_student], 200);
        } elseif ($securityUserResult instanceof Response) { // POCOR-9011
            return $securityUserResult;
        } else {
//            Log::debug(print_r($studentData,true));
            return $this->sendJsonResponse(['message' => 'Failed to save user ' . json_encode(print_r($securityUserResult,true)) ], 500);
        }
    }

    /**
     * Saves staff data. POCOR-8231
     *
     * @return \Cake\Http\Response|null
     * @throws Exception
     *
     */
    public function saveStaffData()
    {

        if($this->savingStaffData > 0) {
            return;
        }
        $this->savingStaffData = $this->savingStaffData + 1;

        $requestData = $this->getRequestData();
        if (empty($requestData)) {
            return $this->sendJsonResponse(['message' => __('Invalid data.')], 400);
        }
        $identityValidationError = $this->validateIdentityByTypePatternOrResponse($requestData, 'Staff');
        if ($identityValidationError instanceof Response) {
            return $identityValidationError;
        }
//        Log::debug(print_r($requestData, true));
        $userId = $this->request->getSession()->read('Auth.User.id') ?? 1;
        $staffData = $this->extractSecurityUserData($requestData, $userId, false,true);

        if ($requestData['is_diff_school'] == 1) {
            $userRecordId = $requestData['staff_id'];
            $result = $this->handleStaffTransfer($requestData, $userRecordId, $userId);
            return $this->sendJsonResponse(['message' => 'success', 'staff' => $result], 200);
        }
        $securityUserResult = $this->saveSecurityUser($staffData);
        if ($securityUserResult instanceof \Cake\ORM\Entity) { // POCOR-9011
            $userRecordId = $securityUserResult->id;
            $this->handleNationalities($requestData, $userRecordId, $userId);
            $identityResult = $this->handleIdentities($requestData, $userRecordId, $userId, 'Staff');
            if ($identityResult instanceof Response) {
                return $identityResult;
            }
            $this->handleContacts($requestData, $userRecordId, $userId);
            $this->handleCustomFields('staff', $requestData, $userRecordId, $userId);
            $staff = $this->handleStaffInstitutionData($requestData, $userRecordId, $userId) ?? $securityUserResult; // POCOR-8776
            $this->handleShifts($requestData, $userRecordId);
//            Log::debug(print_r($staff,true)); // POCOR-8532
//            Log::debug(print_r($staffData,true)); // POCOR-8532
            return $this->sendJsonResponse(['message' => 'success', 'staff' => $staff->toArray()], 200);
        } elseif ($securityUserResult instanceof Response) { // POCOR-9011
            return $securityUserResult;
        } else {
//            Log::debug(print_r($staffData,true));
            return $this->sendJsonResponse(['message' => 'Failed to save user.'], 500);
        }
    }

    /**
     * Extracts student data from the request. POCOR-8231
     * @param $requestData
     * @param $userId
     * @param $is_student
     * @param $is_staff
     * @param $is_guardian
     * @return array
     *
     */
    private function extractSecurityUserData($requestData, $userId, $is_student = false, $is_staff = false, $is_guardian = false)
    {
        $userData = [
            'openemis_no' => isset($requestData['openemis_no']) ? strval($requestData['openemis_no']) : null,
            'username' => isset($requestData['username']) ? strval($requestData['username']) : null,
            'first_name' => $requestData['first_name'] ?? null,
            'middle_name' => $requestData['middle_name'] ?? null,
            'third_name' => $requestData['third_name'] ?? null,
            'last_name' => $requestData['last_name'] ?? null,
            'preferred_name' => $requestData['preferred_name'] ?? null,
            'gender_id' => $requestData['gender_id'] ?? null,
            'date_of_birth' => isset($requestData['date_of_birth']) ? date('Y-m-d', strtotime($requestData['date_of_birth'])) : null,

            'password' => isset($requestData['password']) ? password_hash($requestData['password'], PASSWORD_DEFAULT) : null,
            'address' => $requestData['address'] ?? null,
            'postal_code' => $requestData['postal_code'] ?? null,
            'birthplace_area_id' => $requestData['birthplace_area_id'] ?? null,
            'address_area_id' => $requestData['address_area_id'] ?? null,
            'photo_name' => $requestData['photo_name'] ?? null,
            'photo_content' => isset($requestData['photo_base_64']) ? base64_decode($requestData['photo_base_64']) : null,
            'created_user_id' => $userId,
            'created' => date('Y-m-d H:i:s'), // POCOR-9011
            'email' => $requestData['email'] ?? null, // POCOR-9011
            'mobile_number' => $requestData['mobile_number'] ?? null, // POCOR-9011

        ];
        if ($is_student) {
            $userData['is_student'] = 1;
        }
        if ($is_staff) {
            $userData['is_staff'] = 1;
        }
        if ($is_guardian) {
            $userData['is_guardian'] = 1;
        }
        //POCOR-9590: sync_status sent by JS (1 = came from External Search, 0 = manual add)
        if (isset($requestData['sync_status'])) {
            $userData['sync_status'] = (int)$requestData['sync_status'];
        }
        return $userData;
    }

    /**
     * Saves a security user. POCOR-8231
     *
     * @param array $userData
     *
     */
    /**
     * Save or update a Security User by openemis_no.
     * Returns the saved Entity on success, or a JSON Response on failure.
     *
     * @return \Cake\Datasource\EntityInterface|\Cake\Http\Response
     */
    private function saveSecurityUser(array $userData): \Cake\Datasource\EntityInterface|Response
    {
        $securityUsers = self::getDynamicTableInstance('User.Users'); // POCOR-8706
        if($userData['mobile_number'] == ''){
            unset($userData['mobile_number']);
        }
        if($userData['email'] == ''){
            unset($userData['email']);
        }
        // Find existing by openemis_no
        $existing = $securityUsers->find()
            ->where(['openemis_no' => $userData['openemis_no'] ?? null])
            ->first();

        if ($existing) {
            // Prevent accidental username/password overwrite during update
            $userData['id'] = $existing->id;
            unset($userData['username'], $userData['password']);
            $entity = $securityUsers->patchEntity($existing, $userData);
        } else {
            // POCOR-9181 [START]
            try {
                $entity = $securityUsers->newEntity($userData);
            } catch (\Throwable $e) {
                Log::error(__FUNCTION__ . ': newEntity failed: ' . $e->getMessage());
                return $this->sendJsonResponse(
                    ['message' => 'Failed to create user', 'detail' => $e->getMessage()],
                    500
                );
            }
            // POCOR-9181 [END]
        }

        // Validation errors from patch/new stage
        if ($entity->hasErrors()) {
            return $this->sendJsonResponse(
                ['message' => 'Validation failed', 'errors' => $entity->getErrors()],
                422
            );
        }

        // Attach behavior once (avoid duplicate add on multiple calls)
        $behaviors = $securityUsers->behaviors();
        if (!$behaviors->has('MoodleCreateUser')) {
            $securityUsers->addBehavior('User.MoodleCreateUser');
        }

        try {
            $saved = $securityUsers->save($entity, ['atomic' => false]);

            if ($saved === false) {
                // buildRules or persistence failed without throwing
                return $this->sendJsonResponse(
                    ['message' => 'Failed to save user', 'errors' => $entity->getErrors()],
                    400
                );
            }

            // Success: return the persisted entity (same instance, PK set/updated)
            return $saved;

        } catch (\Throwable $e) {
            Log::error(__FUNCTION__ . ': save failed: ' . $e->getMessage());

            $msg = $e->getMessage();
            // POCOR-9011 start — keep your duplicate parsing
            if (str_contains($msg, 'Duplicate entry')) {
                if (str_contains($msg, 'email')) {
                    return $this->sendJsonResponse(['message' => 'Duplicate email'], 400);
                }
                if (str_contains($msg, 'mobile')) {
                    return $this->sendJsonResponse(['message' => 'Duplicate mobile number'], 400);
                }
            }
            // POCOR-9011 end

            return $this->sendJsonResponse(
                ['message' => 'Failed to save moodle user', 'detail' => $msg],
                500
            );
        }
    }

    /**
     * Handles the nationalities for a user. POCOR-8231
     *
     * @param array $requestData
     * @param int $userRecordId
     * @param int $userId
     *
     */
    private function handleNationalities($requestData, $userRecordId, $userId)
    {
        if (!empty($requestData['nationality_name'])) {
            $nationalitiesTbl = TableRegistry::getTableLocator()->get('FieldOption.Nationalities');
            $nationalities = $nationalitiesTbl->find()
                ->where(['name' => $requestData['nationality_name']])
                ->first();

            if (!$nationalities) {
                $nationalities = $this->createNewNationality($requestData['nationality_name'], $userId);
            }

            return $this->saveUserNationality($nationalities->id, $userRecordId, $userId);
        }
        return [];
    }

    /**
     * Creates a new nationality. POCOR-8231
     *
     * @param string $nationalityName
     * @param int $userId
     * @return \Cake\Datasource\EntityInterface|false
     *
     */
    private function createNewNationality($nationalityName, $userId)
    {
        $nationalitiesTbl = TableRegistry::getTableLocator()->get('FieldOption.Nationalities');
        $orderNationalities = $nationalitiesTbl->find()->order(['order' => 'DESC'])->first();

        $entityNationality = [
            'name' => $nationalityName,
            'order' => !empty($orderNationalities->order) ? $orderNationalities->order + 1 : 0,
            'visible' => 1,
            'editable' => 1,
            'created_user_id' => $userId,
            'created' => date('Y-m-d H:i:s')
        ];

        $entityNationalityData = $nationalitiesTbl->newEntity($entityNationality);
        try {
            return $nationalitiesTbl->save($entityNationalityData);
        } catch (\Exception $e) {
            Log::debug(__FUNCTION__);

            Log::debug('Error  create nationality1: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Saves user nationality. POCOR-8231
     *
     * @param int $nationalityId
     * @param int $userRecordId
     * @param int $userId
     *
     */
    private function saveUserNationality($nationalityId, $userRecordId, $userId)
    {
        $userNationalities = TableRegistry::getTableLocator()->get('User.Nationalities');
        $checkExistingNationalities = $userNationalities->find()
            ->where(['nationality_id' => $nationalityId, 'security_user_id' => $userRecordId])
            ->first();

        if (!$checkExistingNationalities) {
            $primaryKey = $userNationalities->getPrimaryKey();
            $hashString = [];
            foreach ($primaryKey as $key) {
                if ($key == 'nationality_id') {
                    $hashString[] = $nationalityId;
                }
                if ($key == 'security_user_id') {
                    $hashString[] = $userRecordId;
                }
            }

            $entityNationalData = [
                'id' => Security::hash(implode(',', $hashString), 'sha256'),
                'preferred' => 1,
                'nationality_id' => $nationalityId,
                'security_user_id' => $userRecordId,
                'created_user_id' => $userId,
                'created' => date('Y-m-d H:i:s')
            ];

            $entityNationalData = $userNationalities->newEntity($entityNationalData);
            try {
                $userNationalities->save($entityNationalData);
            } catch (\Exception $e) {
                Log::debug(__FUNCTION__);

                Log::debug('Error create nationality: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handles identities for a user. POCOR-8231
     *
     * @param array $requestData
     * @param int $userRecordId
     * @param int $userId
     *
     */
    private function handleIdentities($requestData, $userRecordId, $userId, string $userRole = 'Student')
    {
        // POCOR-9027 start
        $identity_number = $requestData['identity_number'] ?? null;
        $identity_type_id = $requestData['identity_type_id'] ?? null;
        $nationality_id = $requestData['nationality_id'] ?? null;
        $identityValidationError = $this->validateIdentityByTypePatternOrResponse($requestData, $userRole);
        if ($identityValidationError instanceof Response) {
            return $identityValidationError;
        }
        if ($identity_number
            && $identity_type_id
            && $nationality_id) { // POCOR-9027 end
            $userIdentities = self::getDynamicTableInstance('User.Identities');
            $checkExistingIdentities = $userIdentities->find()
                ->where([
                    'nationality_id' => $nationality_id,
                    'identity_type_id' => $identity_type_id,
                    'number' => $identity_number,
                ])->first();

            if (!$checkExistingIdentities) {
                $entityIdentitiesData = [
                    'identity_type_id' => $identity_type_id,
                    'number' => trim((string)$identity_number),
                    'nationality_id' => $nationality_id,
                    'security_user_id' => $userRecordId,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ];
                $entityIdentitiesData = $userIdentities->newEntity($entityIdentitiesData);
                if ($entityIdentitiesData->hasErrors()) {
                    return $this->sendJsonResponse([
                        'message' => __('Please enter a valid Identity Number'),
                        'errors' => $entityIdentitiesData->getErrors()
                    ], 422);
                }
                try {
                    $savedIdentity = $userIdentities->save($entityIdentitiesData, ['associated' => false]);
                    if (!$savedIdentity) {
                        return $this->sendJsonResponse(['message' => __('Please enter a valid Identity Number')], 422);
                    }
                    return $savedIdentity;
                } catch (\Exception $e) {
                    Log::debug(__FUNCTION__);
                    Log::debug('Error: ' . $e->getMessage());
                    return $this->sendJsonResponse(['message' => __('Please enter a valid Identity Number')], 422);
                }
            }
        }
        return [];
    }

    /**
     * Handles contacts for a user. POCOR-8231
     *
     * @param array $requestData
     * @param int $userRecordId
     * @param int $userId
     *
     */
    private function handleContacts($requestData, $userRecordId, $userId)
    {
        if (!empty($requestData['contact_type']) && !empty($requestData['contact_value'])) {
            return $this->saveNewUserContact($requestData['contact_type'], $requestData['contact_value'], $userRecordId, $userId);
        }
        return [];
    }

    /**
     * Saves a new user contact. POCOR-8231
     *
     * @param int $contactTypeId
     * @param string $contactValue
     * @param int $userRecordId
     * @param int $userId
     *
     */
    private function saveNewUserContact($contactTypeId, $contactValue, $userRecordId, $userId)
    {
        $userContacts = self::getDynamicTableInstance('user_contacts');

        $presentContact = $userContacts
            ->find()
            ->where([
                'contact_type_id' => $contactTypeId,
                'value' => $contactValue,
                'security_user_id' => $userRecordId
            ])
            ->first();

        if (empty($presentContact)) {
            $presentContact = $userContacts
                ->find()
                ->where([
                    'contact_type_id' => $contactTypeId,
                    'security_user_id' => $userRecordId
                ])
                ->first();

            if (!empty($presentContact)) {
                $presentContact->value = $contactValue;
                $presentContact->contact_option_id = $contactTypeId;
                $presentContact->modified = date('Y-m-d H:i:s');
                $presentContact->modified_user_id = $userId;
            } else {
                $presentContact = $userContacts->newEntity([
                    'description' => $contactTypeId,
                    'contact_option_id' => $contactTypeId,
                    'contact_type_id' => $contactTypeId,
                    'value' => $contactValue,
                    'preferred' => 1,
                    'security_user_id' => $userRecordId,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ]);
            }
            try {
                return $userContacts->save($presentContact);
            } catch (\Exception $e) {
                Log::debug(__FUNCTION__);

                Log::debug('Error: ' . $e->getMessage());
                return $e;
            }

        }
    }

    /**
     * Handles custom fields for a user. POCOR-8231
     *
     * @param string $userType
     * @param array $requestData
     * @param int $userId
     * @param int $createdUserId
     *
     */
    private function handleCustomFields($userType, $requestData, $userId, $createdUserId)
    {
        $cv = [];
        $customFields = $requestData['custom'] ?? null;
        if (!empty($customFields)) {
            $customFieldValuesTable = $this->getCustomFieldValuesTable($userType);

            // Delete existing custom fields
            $customFieldValuesTable->deleteAll([$customFieldValuesTable->aliasField($userType . '_id') => $userId]);
            $relevantFields = [
                'text_value',
                'number_value',
                'decimal_value',
                'textarea_value',
                'time_value',
                'date_value',
                'file'
            ];
            // Save new custom fields
            foreach ($customFields as $field) {
                $fieldData = [
                    'id' => Text::uuid(),
                    $userType . '_id' => $userId,
                    'created_user_id' => $createdUserId,
                    'created' => date('Y-m-d H:i:s')
                ];

                // Relevant fields to check


                $hasValue = false;

                foreach ($field as $key => $value) {

                    // Check if the current key is in the relevant fields and has a value
                    if (in_array($key, $relevantFields) && (!empty($value) || $value !== null || $value != '')) {
                        $fieldData[$key] = $value;
                        $hasValue = true;
//                        Log::debug(print_r([$key, $value], true));
                    }
                    if (!in_array($key, $relevantFields)) {
                        $fieldData[$key] = $value;
                    }
                }

                // Only create and save the entity if at least one relevant field has a value
                if ($hasValue) {
                    if (isset($fieldData['custom_field_id'])) {
                        // Copy the value from 'custom_field_id' to 'student_custom_field_id'
                        $fieldData[$userType . '_custom_field_id'] = $fieldData['custom_field_id'];
                        // Remove the old 'custom_field_id' key
                        unset($fieldData['custom_field_id']);
                    }

//                    Log::debug(print_r($fieldData, true));
                    $fieldEntity = $customFieldValuesTable->newEntity($fieldData);
                    try {
                        $cv[] = $customFieldValuesTable->save($fieldEntity);

                    } catch (\Exception $e) {
                        Log::debug(__FUNCTION__);

                        Log::debug('Error: ' . $e->getMessage());
                    }
                }
            }
        }
        return $cv;
    }

    /**
     * Get custom field values table based on user type. POCOR-8231
     *
     * @param string $userType
     * @return \Cake\ORM\Table
     *
     */
    private function getCustomFieldValuesTable($userType)
    {
        switch ($userType) {
            case 'student':
                return TableRegistry::getTableLocator()->get('StudentCustomField.StudentCustomFieldValues');
            case 'staff':
                return TableRegistry::getTableLocator()->get('StaffCustomField.StaffCustomFieldValues');
            case 'guardian':
                return TableRegistry::getTableLocator()->get('GuardianCustomField.GuardianCustomFieldValues');
            default:
                throw new InvalidArgumentException('Invalid user type');
        }
    }

    /**
     * Handles institution data for a user. POCOR-8231
     *
     * @param array $requestData
     * @param int $userRecordId
     * @param int $userId
     *
     */
    private function handleStudentInstitutionData(array $requestData, $userRecordId, $userId)
    {
        $institutionId = $requestData['institution_id'] ?? null;
        $institutionClassId = $requestData['institution_class_id'] ?? null;
        $educationGradeId = $requestData['education_grade_id'] ?? null;
        $academicPeriodId = $requestData['academic_period_id'] ?? null;
        $startDate = !empty($requestData['start_date']) ? date('Y-m-d', strtotime($requestData['start_date'])) : null;
        $endDate = !empty($requestData['end_date']) ? date('Y-m-d', strtotime($requestData['end_date'])) : null;
        //POCOR-9635: end_date sent as "0000-00-00" or "1970-01-01" from disabled form field — fall back to academic period end_date
        if (empty($endDate) || $endDate === '1970-01-01' || $endDate === '0000-00-00') {
            if (!empty($academicPeriodId)) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $period = $AcademicPeriods->find()->select(['end_date'])->where(['id' => $academicPeriodId])->first();
                $endDate = !empty($period) ? date('Y-m-d', strtotime($period->end_date)) : null;
            }
        }
        //POCOR-8434 starts
        $studentAdmissionStatus = !empty($requestData['student_admission_status']) ? $requestData['student_admission_status'] : null;//POCOR-7716
        $studentAdmissionStatusValue = !empty($requestData['student_admission_status_value']) ? $requestData['student_admission_status_value'] : null;//POCOR-7716
        //POCOR-8434 ends
        $saved_student = [];
        if ($studentAdmissionStatusValue == 0 || strtolower($studentAdmissionStatus) == "enrolled") {//POCOR-7716 (0 is set for enrolled as in table no id will be equal tp zero)
            if (!empty($educationGradeId) && !empty($academicPeriodId) && !empty($institutionId) && !empty($startDate) && !empty($endDate)) {
                $institutionStudents = self::getDynamicTableInstance('Institution.Students');
                $entityStudentsData = [
                    'id' => Text::uuid(),
                    'student_status_id' => $requestData['student_status_id'] ?? null,
                    'student_id' => $userRecordId,
                    'education_grade_id' => $educationGradeId,
                    'academic_period_id' => $academicPeriodId,
                    'start_date' => $startDate,
                    'start_year' => date('Y', strtotime($startDate)),
                    'end_date' => $endDate,
                    'end_year' => date('Y', strtotime($endDate)),
                    'institution_id' => $institutionId,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ];
                $entityStudentsData = $institutionStudents->newEntity($entityStudentsData);
                try {
                    $savedResult = $institutionStudents->save($entityStudentsData);
                    if ($savedResult !== false) {
                        $saved_student['institution_student'] = $savedResult->toArray();
                    } else {
                        //POCOR-9635: save returned false (validation failure) — toArray() on false caused fatal crash
                        Log::error('[POCOR-9635] institution_students save failed in saveStudentData for student_id=' . ($entityStudentsData->student_id ?? 'unknown') . ' institution_id=' . ($entityStudentsData->institution_id ?? 'unknown') . ' errors=' . json_encode($entityStudentsData->getErrors()));
                    }
                } catch (\Exception $exception) {
                    Log::debug(__FUNCTION__);
                    Log::debug('Error: ' . $exception->getMessage());
                }
            }
        }
        //POCOR-8434 starts
        if (!empty($institutionId) && $studentAdmissionStatusValue == 0 && strtolower($studentAdmissionStatus) == "enrolled") {
            $workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
            $workflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $workflowResults = $workflows->find()
                ->select(['workflowSteps_id' => $workflowSteps->aliasField('id')])
                ->LeftJoin([$workflowSteps->getAlias() => $workflowSteps->getTable()], [
                    $workflowSteps->aliasField('workflow_id =') . $workflows->aliasField('id'),
                    $workflowSteps->aliasField('name') => 'Approved'
                ])
                ->where([
                    $workflows->aliasField('name') => 'Student Enrolment'
                ])
                ->first();
            $workflowStepId = $workflowResults->workflowSteps_id;

            if (!empty($educationGradeId) && !empty($institutionId) && !empty($academicPeriodId) && !empty($workflowResults)) {
                $institutionStudentEnrolment = TableRegistry::getTableLocator()->get('Institution.StudentEnrolment');
                $entityEnrolmentData = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'student_id' => $userRecordId,
                    'status_id' => $workflowStepId,
                    'assignee_id' => $this->Auth->user('id'),
                    'institution_id' => $institutionId,
                    'academic_period_id' => $academicPeriodId,
                    'education_grade_id' => $educationGradeId,
                    'institution_class_id' => $institutionClassId,
                    'test_score' => '',
                    'interview_score' => '',
                    'comment' => '',
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ];
                //save in institution_student_enrolment table
                $entityEnrolmentData = $institutionStudentEnrolment->newEntity($entityEnrolmentData);
                $InstitutionEnrolmentResult = $institutionStudentEnrolment->save($entityEnrolmentData);
                unset($entityEnrolmentData);
                unset($InstitutionEnrolmentResult);
            }
        } else if(!empty($institutionId)) {
            $workflowStepId = $studentAdmissionStatusValue;

            $workflowStepsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $workflowStepData = $workflowStepsTable->find()->contain('Workflows')
                ->where([
                    $workflowStepsTable->aliasField('id') => $workflowStepId
                    ])->first();

            if($workflowStepData->workflow->name == 'Student Admission'){
                if (!empty($educationGradeId) && !empty($institutionId) && !empty($academicPeriodId)) {
                    $institutionStudentAdmission = TableRegistry::getTableLocator()->get('Institution.StudentAdmission');
                    $entityAdmissionData = [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'student_id' => $userRecordId,
                        'status_id' => $workflowStepId,//POCOR-7716
                        'assignee_id' => $this->Auth->user('id'), //POCOR7080
                        'institution_id' => $institutionId,
                        'academic_period_id' => $academicPeriodId,
                        'education_grade_id' => $educationGradeId,
                        'institution_class_id' => $institutionClassId,
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    // POCOR-9323: start save in institution_student_admission table
                    $entityAdmission = $institutionStudentAdmission->newEntity($entityAdmissionData);

// Check validation errors before attempting save
                    $errors = method_exists($entityAdmission, 'getErrors')
                        ? $entityAdmission->getErrors()
                        : $entityAdmission->errors(); // CakePHP <3.4

                    if (!empty($errors)) {
                        Log::debug('Admission validation failed; skipping candidate number gen.');
                        // optionally Log::debug(print_r($errors, true));
                        return;
                    }

// Try save
                    $admissionSaved = $institutionStudentAdmission->save($entityAdmission);

                    if (!$admissionSaved) {
                        Log::debug('Admission save failed; skipping candidate number gen.');
                        return;
                    }

                    // POCOR-9323: end
                    unset($entityAdmissionData);//POCOR-7716
                    unset($InstitutionAdmissionResult);//POCOR-7716
                }
            } else if($workflowStepData->workflow->name == 'Student Enrolment') {
                if (!empty($educationGradeId) && !empty($institutionId) && !empty($academicPeriodId)) {
                    $institutionStudentEnrolment = TableRegistry::getTableLocator()->get('Institution.StudentEnrolment');
                    $entityEnrolmentData = [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'student_id' => $userRecordId,
                        'status_id' => $workflowStepId,
                        'assignee_id' => $this->Auth->user('id'),
                        'institution_id' => $institutionId,
                        'academic_period_id' => $academicPeriodId,
                        'education_grade_id' => $educationGradeId,
                        'institution_class_id' => $institutionClassId,
                        'test_score' => '',
                        'interview_score' => '',
                        'comment' => '',
                        'created_user_id' => $userId,
                        'created' => date('Y-m-d H:i:s')
                    ];
                    //save in institution_student_enrolment table
                    $entityEnrolmentData = $institutionStudentEnrolment->newEntity($entityEnrolmentData);
                    $InstitutionEnrolmentResult = $institutionStudentEnrolment->save($entityEnrolmentData);
                    unset($entityEnrolmentData);
                    unset($InstitutionEnrolmentResult);
                }
            }
        }
        //previous code commented by Abhinav
        // $workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
        // $workflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        // $workflowResults = $workflows->find()
        //     ->select(['workflowSteps_id' => $workflowSteps->aliasField('id')])
        //     ->LeftJoin([$workflowSteps->getAlias() => $workflowSteps->getTable()], [
        //         $workflowSteps->aliasField('workflow_id =') . $workflows->aliasField('id'),
        //         $workflowSteps->aliasField('name') => 'Approved'
        //     ])
        //     ->where([
        //         $workflows->aliasField('name') => 'Student Admission'
        //     ])
        //     ->first();

        //POCOR-7716 start
        //$workflowStepId = $workflowResults->workflowSteps_id;
        // if ($studentAdmissionStatusValue !== 0 && strtolower($studentAdmissionStatus) !== "enrolled") {
        //     $workflowStepId = $studentAdmissionStatusValue;
        // }
        //POCOR-7716 end

        //POCOR-8434 ends
        if (!empty($educationGradeId) && !empty($institutionId) && !empty($academicPeriodId) && !empty($institutionClassId)) {
            $studentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
            $statuses = $studentStatuses->findCodeList();
            $institutionClassStudents = self::getDynamicTableInstance('Institution.InstitutionClassStudents');
            $entityClassData = [
                'id' => Text::uuid(),
                'student_id' => $userRecordId,
                'institution_class_id' => $institutionClassId,
                'education_grade_id' => $educationGradeId,
                'academic_period_id' => $academicPeriodId,
                'institution_id' => $institutionId,
                'student_status_id' => $statuses['CURRENT'],
                'created_user_id' => $userId,
                'created' => date('Y-m-d H:i:s')
            ];
            $entityClassData = $institutionClassStudents->newEntity($entityClassData);
            try {
                $savedClassResult = $institutionClassStudents->save($entityClassData);
                if ($savedClassResult !== false) {
                    $saved_student['institution_class_student'] = $savedClassResult->toArray();
                } else {
                    //POCOR-9635: save returned false — toArray() on false would cause fatal crash
                    Log::error('[POCOR-9635] institution_class_students save failed in saveStudentData for student_id=' . ($entityClassData->student_id ?? 'unknown') . ' institution_id=' . ($entityClassData->institution_id ?? 'unknown') . ' errors=' . json_encode($entityClassData->getErrors()));
                }
            } catch (\Exception $exception) {
                Log::debug(__FUNCTION__);
                Log::debug('Error: ' . $exception->getMessage());
            }
            self::assignStudentSubject($institutionClassId, $academicPeriodId, $userRecordId, $educationGradeId, $institutionId, $statuses['CURRENT'], $userId); // POCOR-8779
        }
        if (!empty($institutionId)) {
            self::assignStudentRoleGroup($institutionId, $userRecordId);//POCOR-8559
        }

        return $saved_student;
    }


    /**
     * POCOR-7146
     * POCOR-7224 refactored
     *
     * assign Role and group to student while creating student
     **/
    private
    static function assignStudentRoleGroup($institution_id, $student_id)
    {
        $student_role_id = self::getStudentSecurityRoleId();
        $security_group_id = self::getInstitutionSecurityGroupId($institution_id);
        //check student already exist
        $student_security_groups = self::getStudentSecurityGroups($student_id, $student_role_id);
        //check that the student is not in other groups
        if (sizeof($student_security_groups) == 0) {
            self::createNewStudentSecurityGroup($student_id, $security_group_id, $student_role_id);
            return;
        }
        //update user's security_group_id in security_group_users table
        $previous_security_group_id = self::getPreviousSecurityGroupId($institution_id, $student_id);
        //check that the student is should be transferred
        if (in_array($previous_security_group_id, $student_security_groups)) {
            $security_group = self::makeStudentSecurityGroupTransfer($student_id, $security_group_id, $previous_security_group_id, $student_role_id);
            return;
        }
        //if he/she is not transferred - create new security group
        $student_security_groups = self::getStudentSecurityGroups($student_id, $student_role_id);
        //check that the student is not in other groups
        if (sizeof($student_security_groups) == 0) {
            self::createNewStudentSecurityGroup($student_id, $security_group_id, $student_role_id);
            return;
        }
//        return ;

    }


    /**
     * @return int
     *
     */
    private
    static function getStudentSecurityRoleId(): int
    {
        $securityRolesTbl = self::getDynamicTableInstance('security_roles');
        $securityRoles = $securityRolesTbl->find()
            ->where([
                $securityRolesTbl->aliasField('code') => 'STUDENT',
            ])->first();
        $student_role_id = $securityRoles->id;
        return $student_role_id;
    }

    // POCOR-8853
    private
    static function getHomeroomTeacherSecurityRoleId(): int
    {
        $securityRolesTbl = self::getDynamicTableInstance('security_roles');
        $securityRoles = $securityRolesTbl->find()
            ->where([
                $securityRolesTbl->aliasField('code') => 'HOMEROOM_TEACHER',
            ])->first();
        $security_role_id = $securityRoles->id;
        return $security_role_id;
    }

    /**
     * @param $institutionId
     * @return integer
     *
     */
    private
    static function getInstitutionSecurityGroupId($institutionId)
    {
        $institutionTbl = self::getDynamicTableInstance('institutions');
        $security_group_id = null;
        $institutions = $institutionTbl->find()
            ->where([
                $institutionTbl->aliasField('id') => $institutionId
            ])->first();
        if (!empty($institutions)) {
            $security_group_id = $institutions->security_group_id;
        }
        if ($security_group_id != null) {
            $securityGroupInstitutionsTbl = self::getDynamicTableInstance('security_group_institutions');
            $securityGroupInstitutions = $securityGroupInstitutionsTbl->find()
                ->where([
                    $securityGroupInstitutionsTbl->aliasField('security_group_id') => $security_group_id,
                    $securityGroupInstitutionsTbl->aliasField('institution_id') => $institutions->id
                ])
                ->first();
            //save security group for institution
            if (empty($securityGroupInstitutions)) {
                $security_group_ins_data = [
                    'security_group_id' => $security_group_id,
                    'institution_id' => $institutionId,
                    'created_user_id' => 1,
                    'created' => new Time('NOW')
                ];
                $securityGroupInstitutionsEntity = $securityGroupInstitutionsTbl->newEntity($security_group_ins_data);
                $securityGroupInstitutionsTbl->save($securityGroupInstitutionsEntity);
            }
        }
        return $security_group_id;
    }

    /**
     * @param $student_id
     * @param $student_role_id
     * @return array
     *
     */
    private
    static function getStudentSecurityGroups($student_id, $student_role_id)
    {
        $securityGroupUsersTbl = self::getDynamicTableInstance('security_group_users');
        $countSecurityGroupStudent = $securityGroupUsersTbl->find('all')
            ->select('security_group_id')
            ->where([
                $securityGroupUsersTbl->aliasField('security_user_id') => $student_id,
                $securityGroupUsersTbl->aliasField('security_role_id') => $student_role_id
            ])
            ->extract('security_group_id')
            ->toArray();
        return $countSecurityGroupStudent;
    }

    /**
     * @param $student_id
     * @param $security_group_id
     * @param $student_role_id
     *
     */
    private
    static function createNewStudentSecurityGroup($student_id, $security_group_id, $student_role_id)
    {
        $id = Text::uuid();
        $securityGroupUsersTbl = self::getDynamicTableInstance('security_group_users');
        $security_group_data = [
            'id' => $id,
            'security_group_id' => $security_group_id,
            'security_user_id' => $student_id,
            'security_role_id' => $student_role_id,
            'created_user_id' => 1,
            'created' => new Time('NOW')
        ];
        $securityGroupUsersEntity = $securityGroupUsersTbl->newEntity($security_group_data);
        $newEntity = $securityGroupUsersTbl->save($securityGroupUsersEntity);
        return $newEntity;
    }

    /**
     * @param $institution_id
     * @param $student_id
     * @param $institutionTbl
     * @return mixed
     *
     */
    private
    static function getPreviousSecurityGroupId($institution_id, $student_id)
    {
        $previous_security_group_id = 0;
        $institutionTbl = self::getDynamicTableInstance('institutions');
        $InstitutionStudentsTbl = self::getDynamicTableInstance('institution_students');
        $TransfersTbl = self::getDynamicTableInstance('institution_student_transfers');
        $StudentTransfers = $InstitutionStudentsTbl
            ->find()
            ->select([
                $InstitutionStudentsTbl->aliasField('student_id'),
                $TransfersTbl->aliasField('institution_id'),
                $TransfersTbl->aliasField('previous_institution_id')
            ])
            ->leftJoin([$TransfersTbl->getAlias() => $TransfersTbl->getTable()],
                [
                    $TransfersTbl->aliasField('student_id') . '=' . $student_id,
                    $TransfersTbl->aliasField('institution_id') => $institution_id
                ]
            )
            ->where([
                $InstitutionStudentsTbl->aliasField('student_id') => $student_id,
                $InstitutionStudentsTbl->aliasField('institution_id') => $institution_id,
                $InstitutionStudentsTbl->aliasField('student_status_id') => 1//for enrolled status
            ])
            ->first();
        if (!empty($StudentTransfers)) {
            if (!empty($StudentTransfers->institution_student_transfers['previous_institution_id'])) {
                $PreviousInstitutions = $institutionTbl->find()
                    ->where([
                        $institutionTbl->aliasField('id') => $StudentTransfers->institution_student_transfers['previous_institution_id']
                    ])
                    ->first();
                $previous_security_group_id = $PreviousInstitutions->security_group_id;
            }
        }
        return $previous_security_group_id;
    }

    /**
     * @param $student_id
     * @param $security_group_id
     * @param $previous_security_group_id
     * @param $student_role_id
     *
     */
    private static function makeStudentSecurityGroupTransfer($student_id, $security_group_id, $previous_security_group_id, $student_role_id)
    {
        $securityGroupUsersTbl = self::getDynamicTableInstance('security_group_users');
        $securityGroupUsersTbl->updateAll(
            [
                'security_group_id' => $security_group_id,
                'created' => new FrozenTime('NOW')
            ],
            [
                'security_group_id' => $previous_security_group_id,
                'security_user_id' => $student_id,
                'security_role_id' => $student_role_id
            ]
        );
        return true;
    }

    /**
     * Handles student transfer. POCOR-8231
     *
     * @param array $requestData
     * @param int $userRecordId
     * @param int $userId
     *
     */
    private function handleStudentTransfer(array $requestData, $userRecordId, $userId)
    {
        if ($requestData['is_diff_school'] == 1) {
            $institution_id = $requestData['institution_id'] ?? null;
            $academicPeriodId = $requestData['academic_period_id'] ?? null;
            $educationGradeId = $requestData['education_grade_id'] ?? null;
            $previous_institution_id = $requestData['previous_institution_id'] ?? null;
            $previous_education_grade_id = $requestData['previous_education_grade_id'] ?? null;
            $previousAcademicPeriodID = $requestData['previous_academic_period_id'] ?? null;
            $student_transfer_reason_id = $requestData['student_transfer_reason_id'] ?? null;
            if(!$previous_institution_id){
                return ['error' => 'no previous_institution_id'];
            }
            if(!$previousAcademicPeriodID){
                return ['error' => 'no previousAcademicPeriodID'];
            }
            if(!$student_transfer_reason_id){
                return ['error' => 'no student_transfer_reason_id'];
            }
            $startDate = !empty($requestData['start_date']) ? date('Y-m-d', strtotime($requestData['start_date'])) : null;
            $endDate = !empty($requestData['end_date']) ? date('Y-m-d', strtotime($requestData['end_date'])) : null;

            $institutionStudentTransfers = self::getDynamicTableInstance('institution_student_transfers');
            $datatocheck = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'student_id' => $userRecordId,
                'status_id' => $this->getWorkflowStepId('Student Transfer - Receiving', 'Open'),
                'institution_id' => $institution_id,
                'academic_period_id' => $academicPeriodId,
                'education_grade_id' => $educationGradeId,
                'previous_institution_id' => $previous_institution_id,
                'previous_education_grade_id' => $previous_education_grade_id,
                'previous_academic_period_id' => $previousAcademicPeriodID
            ];

            $existingTransfer = $institutionStudentTransfers->find()
                ->where($datatocheck)
                ->first();

            if ($existingTransfer) {
                // Record already exists, handle accordingly
                Log::debug(__FUNCTION__);

                Log::debug('Student transfer record already exists.');
                return ['error' => 'Student transfer record already exists.'];

            } else {
                // No existing record found, proceed with saving the new entity
                $entityTransferData = array_merge($datatocheck, [
                    'requested_date' => null,
                    'assignee_id' => $userId,
                    'institution_class_id' => $requestData['institution_class_id'] ?? null,
                    'student_transfer_reason_id' => $student_transfer_reason_id,
                    'comment' => $requestData['comment'] ?? '',
                    'all_visible' => 1,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ]);

                $entity1 = $institutionStudentTransfers->newEntity($entityTransferData);
                try {
                    return $institutionStudentTransfers->save($entity1);
                } catch (\Exception $exception) {
                    Log::debug(__FUNCTION__);

                    Log::debug('Error in Transfer: ' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * Handles staff transfer between institutions.
     *
     * @param array $requestData
     * @param int $userRecordId
     * @param int $userId
     * @return array|null
     */
    private function handleStaffTransfer(array $requestData, $userRecordId, $userId)
    {
        if ($requestData['is_diff_school'] == 1) {
            $institutionId = $requestData['institution_id'] ?? null;
            $institutionPositionId = $requestData['institution_position_id'] ?? null;
            $staffTypeId = $requestData['staff_type_id'] ?? null;
            $previousInstitutionId = $requestData['previous_institution_id'] ?? null;
            $previousInstitutionStaffId = $requestData['previous_institution_staff_id'] ?? "";
            $staffTransferReasonId = $requestData['staff_transfer_reason_id'] ?? null;
            $is_homeroom =$requestData['is_homeroom'] ?? null; // POCOR-8532
            $fte =$requestData['fte'] ?? null; // POCOR-8532
            if (!$previousInstitutionId) {
                return ['error' => 'no previous_institution_id'];
            }

            $startDate = !empty($requestData['start_date']) ? date('Y-m-d', strtotime($requestData['start_date'])) : null;
            $endDate = !empty($requestData['end_date']) ? date('Y-m-d', strtotime($requestData['end_date'])) : null;

            $institutionStaffTransfers = self::getDynamicTableInstance('institution_staff_transfers');
            $datatocheck = [
                'new_start_date' => $startDate,
                'staff_id' => $userRecordId,
                'status_id' => $this->getWorkflowStepId('Staff Transfer - Receiving', 'Open'),
                'new_institution_id' => $institutionId,
                'new_institution_position_id' => $institutionPositionId,
                'new_staff_type_id' => $staffTypeId,
                'previous_institution_id' => $previousInstitutionId,
//                'previous_institution_staff_id' => $previousInstitutionStaffId,
            ];

            $existingTransfer = $institutionStaffTransfers->find()
                ->where($datatocheck)
                ->first();

            if ($existingTransfer) {
                Log::debug(__FUNCTION__);
                Log::debug('Staff transfer record already exists.');
                return ['error' => 'Staff transfer record already exists.'];
            } else {

                $entityTransferData = array_merge($datatocheck, [
                    'assignee_id' => $this->Auth->user('id'), //POCOR-7080
                    'new_institution_position_id' => $institutionPositionId,
                    'new_staff_type_id' => $staffTypeId,
                    'new_FTE' => $fte ?? 1, // POCOR-8532
                    'is_homeroom' => $is_homeroom ?? 0, // POCOR-7870
                    'new_start_date' => $startDate,
                    'new_end_date' => $endDate,
                    'previous_institution_staff_id' => '',
                    'previous_staff_type_id' => '',
                    'previous_FTE' => '',
                    'previous_end_date' => '',
                    'previous_effective_date' => '',
                    'previous_institution_staff_id' => '',
                    'requested_date' => null,
                    'assignee_id' => $userId,
                    'comment' => $requestData['comment'] ?? '',
                    'transfer_type' => 0,
                    'all_visible' => 1,
                    'modified_user_id' => '',
                    'modified' => '',
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ]);
//                Log::debug(print_r($entityTransferData, true));
                $entity = $institutionStaffTransfers->newEntity($entityTransferData);
                try {
                    $result = $institutionStaffTransfers->save($entity);
                        return $result->toArray();
                } catch (\Exception $exception) {
                    Log::debug(__FUNCTION__);

                    Log::debug('Error in Transfer: ' . $exception->getMessage());
                    return ['error' => 'Error in Transfer: ' . $exception->getMessage()];
                }
            }
        }

        return null;
    }

    /**
     * Gets the workflow step ID. POCOR-8231
     *
     * @param string $workflowName
     * @param string $stepName
     * @return int|null
     *
     */
    private function getWorkflowStepId($workflowName, $stepName)
    {

        $workflows = self::getDynamicTableInstance('Workflow.Workflows');
        $workflowSteps = self::getDynamicTableInstance('Workflow.WorkflowSteps');
        $workflowResult = $workflows->find()
            ->select(['workflowSteps_id' => $workflowSteps->aliasField('id')])
            ->leftJoinWith('WorkflowSteps', function ($q) use ($workflowSteps, $stepName) {
                return $q->where([$workflowSteps->aliasField('name') => $stepName]);
            })
            ->where([$workflows->aliasField('name') => $workflowName])
            ->first();

        return $workflowResult->workflowSteps_id ?? null;
    }

    /**
     * Handles institution data for a staff member.
     * POCOR-8231
     *
     * @param array $requestData
     * @param int $userRecordId
     * @param int $userId
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     *
     */
    private function handleStaffInstitutionData($requestData, $userRecordId, $userId)
    {
        $institutionId = $requestData['institution_id'] ?? null;
        if (!empty($institutionId)) {
        $institutionPositionId = $requestData['institution_position_id'] ?? null;
            $staffTypeId = $requestData['staff_type_id'] ?? null;
            $fte = $requestData['fte'] ?? null;
            $startDate = !empty($requestData['start_date']) ? date('Y-m-d', strtotime($requestData['start_date'])) : null;
            $endDate = !empty($requestData['end_date']) ? date('Y-m-d', strtotime($requestData['end_date'])) : null;
            $endYear = !empty($requestData['end_date']) ? date('Y', strtotime($requestData['end_date'])) : null;
            $staff_position_grade_id = (array_key_exists('staff_position_grade_id', $requestData)) ? $requestData['staff_position_grade_id'] : '';//POCOR-7238
            $startYear = date('Y', strtotime($startDate));
            $is_homeroom = (array_key_exists('is_homeroom', $requestData)) ? $requestData['is_homeroom'] : 0; //POCOR-5070

            $institutionStaffs = self::getDynamicTableInstance('Institution.InstitutionStaff');
            $institutionPositions = self::getDynamicTableInstance('institution_positions');
            $securityGroupUsers = self::getDynamicTableInstance('security_group_users');
            $StaffStatuses = self::getDynamicTableInstance('Staff.StaffStatuses');
            $statuses = $StaffStatuses->findCodeList();
            $position = $institutionPositions->get($institutionPositionId);
            // POCOR-8853 start
            $securityRoleID = $this->getSecurityRoleID($position->staff_position_title_id);
            $institution_security_group_id = $this->getInstitutionSecurityGroupId($institutionId);

            if($securityRoleID){
                $staff_security_group_id = $this->getSecurityGroupUserId($securityGroupUsers, $institution_security_group_id, $userRecordId, $userId, $securityRoleID) ?? null;
            }
            if($is_homeroom){
                $homeroomSecurityRoleId = self::getHomeroomTeacherSecurityRoleId();
                $homeroom_security_group_id = $this->getSecurityGroupUserId($securityGroupUsers, $institution_security_group_id, $userRecordId, $userId, $homeroomSecurityRoleId);
            }
            // POCOR-8853 end
            $entityStaffData = [
                'FTE' => $fte,
                'start_date' => $startDate,
                'start_year' => $startYear,
                'end_date' => $endDate,
                'end_year' => $endYear,
                'staff_id' => $userRecordId,
                'staff_type_id' => $staffTypeId,
                'staff_status_id' => $statuses['ASSIGNED'],
                'is_homeroom' => $is_homeroom, //POCOR-5070
                'institution_id' => $institutionId,
                'institution_position_id' => $institutionPositionId,
                'security_group_user_id' => $staff_security_group_id, // POCOR-8853
                'staff_position_grade_id' => $staff_position_grade_id,//POCOR-7238
                'created_user_id' => $userId,
                'created' => date('Y-m-d H:i:s')
            ];

            $entity = $institutionStaffs->newEntity($entityStaffData);
            $result = $institutionStaffs->save($entity, ['associated' => false]);
            return $result ?? [];
        }

    }
    /**
     * Retrieves or creates the security group user ID.
     * POCOR-8231
     *
     * @param \Cake\ORM\Table $securityGroupUsers
     * @param int $security_group_id // POCOR-8853 start
     * @param int $userRecordId
     * @param int $userId
     * @param int $securityRoleId // POCOR-8853 start
     * @return string|null
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     *
     */
    private function getSecurityGroupUserId($securityGroupUsers, $security_group_id, $userRecordId, $userId, $securityRoleId)
    {
        $groupUser = $securityGroupUsers->find()
            ->where([
                'security_user_id' => $userRecordId,
                'security_role_id' => $securityRoleId, // POCOR-8853 start
                'security_group_id' => $security_group_id // POCOR-8853 start
            ])
            ->first();

        if (empty($groupUser)) {
            $groupUserData = [
                'id' => Text::uuid(),
                'security_group_id' => $security_group_id, // POCOR-8853 start
                'security_user_id' => $userRecordId,
                'security_role_id' => $securityRoleId, // POCOR-8853 start
                'created_user_id' => $userId,
                'created' => date('Y-m-d H:i:s')
            ];
            $entity = $securityGroupUsers->newEntity($groupUserData);
            $securityGroupUsers->save($entity);
            return $entity->id;
        }

        return $groupUser->id;
    }

    /**
     * Retrieves the security role based on the position title ID.
     * POCOR-8231
     * POCOR-8853 renamed/refactured
     * @param int $staffPositionTitleId
     * @return int | null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     *
     */
    private function getSecurityRoleID($staffPositionTitleId)
    {
        $staffPositionTitles = self::getDynamicTableInstance('staff_position_titles');
        $title = $staffPositionTitles->get($staffPositionTitleId);
        return $title->security_role_id ?? null;
    }

    /**
     * Handles shifts for a staff member.
     * POCOR-8231
     *
     * @param array $requestData
     * @param int $userRecordId
     * @return void
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     *
     */
    private function handleShifts($requestData, $userRecordId)
    {
        $shiftIds = $requestData['shift_ids'] ?? [];

        if (!empty($shiftIds)) {
            $institutionStaffShifts = self::getDynamicTableInstance('institution_staff_shifts');

            foreach ($shiftIds as $shiftId) {
                $shiftData = [
                    'staff_id' => $userRecordId,
                    'shift_id' => $shiftId,
                    'created' => date('Y-m-d H:i:s')
                ];

                $entity = $institutionStaffShifts->newEntity($shiftData);
                $institutionStaffShifts->save($entity);
            }
        }
    }

    /**
     * Saves guardian data. POCOR-8337
     *
     * @return \Cake\Http\Response|null
     *
     */
    public function saveGuardianData()
    {

        if($this->savingGuardianData > 0) {
            return;
        }
        $this->savingGuardianData = $this->savingGuardianData + 1;

//        Log::debug('saveGuardianData');
        $this->autoRender = false;
        $requestData = $this->getRequestData();
//        Log::debug(print_r($requestData, true));
        if (empty($requestData)) {
            return $this->sendJsonResponse(['message' => __('Invalid data.')], 400);
        }
        $identityValidationError = $this->validateIdentityByTypePatternOrResponse($requestData, 'Guardian');
        if ($identityValidationError instanceof Response) {
            return $identityValidationError;
        }

        $userId = $this->request->getSession()->read('Auth.User.id') ?? 1;
        $userData = $this->extractSecurityUserData($requestData, $userId, false, false, true);
        $studentOpenemisNo = (isset($requestData['student_openemis_no'])) ? $requestData['student_openemis_no'] : null;
        $guardianRelationId = (isset($requestData['guardian_relation_id'])) ? $requestData['guardian_relation_id'] : null;
//        Log::debug(print_r($userData, true));
        $securityUserResult = $this->saveSecurityUser($userData);
//        Log::debug(print_r($securityUserResult, true));
        if ($securityUserResult instanceof \Cake\ORM\Entity) { // POCOR-9011
            $userRecordId = $securityUserResult->id;
            $r1 = $this->handleNationalities($requestData, $userRecordId, $userId);
            $r2 = $this->handleIdentities($requestData, $userRecordId, $userId, 'Guardian');
            if ($r2 instanceof Response) {
                return $r2;
            }
            $r3 = $this->handleContacts($requestData, $userRecordId, $userId);
            if ($studentOpenemisNo) {
            $r4 = $this->handleGuardians($guardianRelationId, $studentOpenemisNo, $userRecordId, $userId);
            }
//            Log::debug('handleNationalities');
//            Log::debug(print_r($r1, true));
//            Log::debug('handleIdentities');
//            Log::debug(print_r($r2, true));
//            Log::debug('handleContacts');
//            Log::debug(print_r($r3, true));
//            Log::debug('handleGuardians');
//            Log::debug(print_r($r4, true));
            return $this->sendJsonResponse(['message' => 'success', 'id' => $userRecordId], 200);
        } elseif ($securityUserResult instanceof Response) { // POCOR-9011
            return $securityUserResult;
        } else {
//            Log::debug(print_r($userData,true));
            return $this->sendJsonResponse(['message' => 'Failed to save user.'], 500);
        }
    }

    /**
     * Saves other user data. POCOR-8337
     *
     * @return \Cake\Http\Response|null
     *
     */
    public function saveOtherData()
    {
//        Log::debug(__FUNCTION__);
//
//        Log::debug('saveOtherData');
        $this->autoRender = false;
        $requestData = $this->getRequestData();
//        Log::debug(print_r($requestData, true));
        if (empty($requestData)) {
            return $this->sendJsonResponse(['message' => __('Invalid data.')], 400);
        }
        $identityValidationError = $this->validateIdentityByTypePatternOrResponse($requestData, 'Other');
        if ($identityValidationError instanceof Response) {
            return $identityValidationError;
        }

        $userId = $this->request->getSession()->read('Auth.User.id') ?? 1;
        $userData = $this->extractSecurityUserData($requestData, $userId, false, false, true);
        $studentOpenemisNo = (array_key_exists('student_openemis_no', $requestData)) ? $requestData['student_openemis_no'] : null;
        $guardianRelationId = (array_key_exists('guardian_relation_id', $requestData)) ? $requestData['guardian_relation_id'] : null;
//        Log::debug(print_r($userData, true));
        $securityUserResult = $this->saveSecurityUser($userData);
//        Log::debug(print_r($securityUserResult, true));
        if ($securityUserResult instanceof \Cake\ORM\Entity) { // POCOR-9011
            $userRecordId = $securityUserResult->id;
            $r1 = $this->handleNationalities($requestData, $userRecordId, $userId);
            $r2 = $this->handleIdentities($requestData, $userRecordId, $userId, 'Other');
            if ($r2 instanceof Response) {
                return $r2;
            }
            $r3 = $this->handleContacts($requestData, $userRecordId, $userId);
//            $r5 = $this->handleCustomFields('guardian', $requestData, $userRecordId, $userId);
//            Log::debug('handleNationalities');
//            Log::debug(print_r($r1, true));
//            Log::debug('handleIdentities');
//            Log::debug(print_r($r2, true));
//            Log::debug('handleContacts');
//            Log::debug(print_r($r3, true));
//            Log::debug('handleGuardians');
//            Log::debug(print_r($r4, true));
//            Log::debug('handleCustomFields');
//            Log::debug(print_r($r5, true));
            return $this->sendJsonResponse(['message' => 'success', 'id' => $userRecordId], 200);
        } elseif ($securityUserResult instanceof Response) { // POCOR-9011
            return $securityUserResult;
        } else {
//            Log::debug(print_r($userData,true));
            return $this->sendJsonResponse(['message' => 'Failed to save user.'], 500);
        }
    }

    /**
     * Handles new guardian for a student. POCOR-8231
     * @param $guardianRelationId
     * @param $studentOpenemisNo
     * @param $userRecordId
     * @param $userId
     * @return array|\Cake\Datasource\EntityInterface|Exception|false
     *
     *
     */
    private function handleGuardians($guardianRelationId, $studentOpenemisNo, $userRecordId, $userId)
    {
        if (!empty($guardianRelationId) && !empty($studentOpenemisNo)) {
            $SecurityUsers = self::getDynamicTableInstance('security_users');
            $StudentData = $SecurityUsers->find()
                ->where([
                    $SecurityUsers->aliasField('openemis_no') => $studentOpenemisNo
                ])->first();
            //get id from `security_group_users` table
            $StudentGuardians = self::getDynamicTableInstance('Student.StudentGuardians');
            $entityGuardiansData = [
                'id' => Text::uuid(),
                'student_id' => $StudentData->id,
                'guardian_id' => $userRecordId,
                'guardian_relation_id' => $guardianRelationId,
                'created_user_id' => $userId,
                'created' => date('Y-m-d H:i:s')
            ];
//            Log::debug(print_r($entityGuardiansData, true));
// Check for an existing entity based on unique fields
            $existingEntity = $StudentGuardians->find()
                ->where([$StudentGuardians->aliasField('student_id') => $StudentData->id,
                    $StudentGuardians->aliasField('guardian_id') => $userRecordId,
                    $StudentGuardians->aliasField('guardian_relation_id') => $guardianRelationId,])
                ->first();

            if ($existingEntity) {
                // If the entity already exists, update its data
                $existingEntity = $StudentGuardians->patchEntity($existingEntity, $entityGuardiansData);
                try {
                    return $StudentGuardians->save($existingEntity, ['associated' => false]);
                } catch (\Exception $e) {
                    // Handle save error
                    throw $e;
                }
            } else {
                // If the entity does not exist, create a new one
                $newEntity = $StudentGuardians->newEntity($entityGuardiansData);
                try {
                    return $StudentGuardians->save($newEntity, ['associated' => false]);
                } catch (\Exception $e) {
                    // Handle save error
                    throw $e;
                }
            }


            $entityGuardiansData = $StudentGuardians->newEntity($entityGuardiansData);
            try {
                return $StudentGuardians->save($entityGuardiansData, ['checkExisting' => true, 'associated' => false]);
            } catch (\Exception $e) {
                Log::debug(__FUNCTION__);

                Log::debug('Error: ' . $e->getMessage());
                return $e;
            }
        }
        return [];
    }

    public function checkUserAlreadyExistByIdentity()
    {
        if($this->savingDirectoryData > 0) {
            return;
        }
        $this->savingDirectoryData = $this->savingDirectoryData + 1;

        $requestData = $this->getRequestData();

        if (empty($requestData)) {
            return $this->sendJsonResponse(['user_exist' => 0, 'status_code' => 400, 'message' => __('Invalid data.')]);

        }
//        self::debug($requestData);
        $identityTypeId = $this->getValue($requestData, 'identity_type_id');
        $identityNumber = $this->getValue($requestData, 'identity_number');
        $nationalityId = $this->getValue($requestData, 'nationality_id');

        if ($this->isIdentityValid($identityTypeId, $identityNumber)) {
            $userIdentitiesTable = $this->getUserIdentitiesTable();
            $userExists = $this->checkUserExistence($userIdentitiesTable, $identityTypeId, $identityNumber, $nationalityId);
//            self::debug(__FUNCTION__);
            if ($userExists) {
                return $this->sendJsonResponse(['user_exist' => 1, 'status_code' => 200, 'message' => '']); // POCOR-8989 it is not a problem, no need to check ID validity
            }

            $message = $this->validateCustomIdentityNumber($requestData);
            if (!empty($message)) {
                return $this->sendJsonResponse(['user_exist' => 0, 'status_code' => 200, 'message' => $message]);  // POCOR-8989
            }

            //POCOR-9590: identity is well-formed, no DB collision, and pattern check (POCOR-9688) passed —
            //this is a new identity the wizard is allowed to create. The previous 400 here blocked
            //every IdentityType without a validation_pattern (e.g. NIN), breaking add-from-external-source.
            return $this->sendJsonResponse(['user_exist' => 0, 'status_code' => 200, 'message' => '']);
        } else {
            return $this->sendJsonResponse(['user_exist' => 0, 'status_code' => 400, 'message' => __('Invalid identity data.')]);
        }
    }

    private function isIdentityValid($identityTypeId, $identityNumber)
    {
        return !empty($identityTypeId) && !empty($identityNumber);
    }

    private function getUserIdentitiesTable()
    {
        return TableRegistry::getTableLocator()->get('User.Identities');
    }

    private function checkUserExistence($userIdentitiesTable, $identityTypeId, $identityNumber, $nationalityId)
    {
        $conditions = [
            $userIdentitiesTable->aliasField('identity_type_id') => $identityTypeId,
            $userIdentitiesTable->aliasField('number') => $identityNumber
        ];

        if (!empty($nationalityId)) {
            $conditions[$userIdentitiesTable->aliasField('nationality_id')] = $nationalityId;
        }

        return $userIdentitiesTable->find()->where($conditions)->count() > 0;
    }

    private
    function validateCustomIdentityNumber($options)
    {
        $pattern = '';
        $identityTypeId = null;
        if (isset($options['identity_type_id']) && $options['identity_type_id'] !== '' && $options['identity_type_id'] !== null) {
            $identityTypeId = (int)$options['identity_type_id'];
        }
        if (isset($options['identity_number']) && $options['identity_number'] !== '' && $options['identity_number'] !== null) {
            $identityNumber = trim((string)$options['identity_number']);
        } else {
            return "";
        }

        $IdentityTypes = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $identityTypesQuery = $IdentityTypes->find();
        if (!empty($identityTypeId)) {
            $identityTypesQuery->where([$IdentityTypes->aliasField('id') => $identityTypeId]);
        } elseif (!empty($options['identity_type_name'])) {
            $identityTypesQuery->where([$IdentityTypes->aliasField('name') => $options['identity_type_name']]);
        } else {
            return "";
        }

        $IdentityTypesData = $identityTypesQuery->first();
        if (empty($IdentityTypesData)) {
            return __("Please enter a valid Identity Number");
        }

        if (!empty($IdentityTypesData->validation_pattern)) {
            $pattern = '/' . $IdentityTypesData->validation_pattern . '/';
        }


        // custom validation is nullable, have to cater for the null pattern.
        if (!empty($pattern) && !preg_match($pattern, $identityNumber)) {
            return __("Please enter a valid Identity Number");
        }

        return "";
    }

    private function validateIdentityByTypePatternOrResponse(array $requestData, string $userRole = 'Student'): ?Response
    {
        $identityTypeId = isset($requestData['identity_type_id']) ? trim((string)$requestData['identity_type_id']) : '';
        $identityNumber = isset($requestData['identity_number']) ? trim((string)$requestData['identity_number']) : '';
        $nationalityId = isset($requestData['nationality_id']) ? trim((string)$requestData['nationality_id']) : '';
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $ConfigItemOptions = TableRegistry::getTableLocator()->get('Configuration.ConfigItemOptions');
        $configValues = $ConfigItems
            ->find()
            ->select([
                'code' => $ConfigItems->aliasField('code'),
                'value' => $ConfigItems->aliasField('value'),
                'config_option' => $ConfigItemOptions->aliasField('option')
            ])
            ->leftJoin([$ConfigItemOptions->getAlias() => $ConfigItemOptions->getTable()], [
                $ConfigItemOptions->aliasField('option_type') . ' = ' . $ConfigItems->aliasField('option_type'),
                $ConfigItemOptions->aliasField('value') . ' = ' . $ConfigItems->aliasField('value')
            ])
            ->where([$ConfigItems->aliasField('code IN') => [$userRole . 'Identities', $userRole . 'Nationalities']])
            ->disableHydration()
            ->all()
            ->combine('code', function ($row) {
                return $row['config_option'] ?? $row['value'];
            })
            ->toArray();

        $isMandatoryConfig = function ($value): bool {
            return $value === 'Mandatory' || (string)$value === '1';
        };
        $isIdentityMandatory = $isMandatoryConfig($configValues[$userRole . 'Identities'] ?? null);
        $isNationalityMandatory = $isMandatoryConfig($configValues[$userRole . 'Nationalities'] ?? null);

        if ($isIdentityMandatory && ($identityTypeId === '' || $identityNumber === '')) {
            return $this->sendJsonResponse(['message' => __('Please enter Identity Type and Identity Number value')], 422);
        }

        if ($identityTypeId !== '' && $identityNumber === '') {
            return $this->sendJsonResponse(['message' => __('Please enter Identity Number value')], 422);
        }

        if ($identityNumber !== '' && $identityTypeId === '') {
            return $this->sendJsonResponse(['message' => __('Please enter Identity Type value')], 422);
        }

        // Nationality is mandatory when configured, and also when saving an identity.
        if (($isNationalityMandatory || ($identityTypeId !== '' && $identityNumber !== '')) && $nationalityId === '') {
            return $this->sendJsonResponse(['message' => __('Please enter Nationality value')], 422);
        }

        $message = $this->validateCustomIdentityNumber($requestData);
        if (!empty($message)) {
            return $this->sendJsonResponse(['message' => $message], 422);
        }
        return null;
    }

    /**
     * Change for hiding/showing external search. POCOR-8231
     *
     *     POCOR-9118 refactored for OpenEMIS Core search
     */
    public function checkConfigurationForExternalSearch(): Response
    {
        $this->request->allowMethod(['post']);
        $data   = $this->request->getData();
        $params = $data['params'] ?? [];
//        Log::debug(print_r([__FUNCTION__ => $params], true));
        $identityTypeId = (int)($params['identity_type_id'] ?? 0);
        $nationalityId  = (int)($params['nationality_id'] ?? 0);

        if (!$identityTypeId || !$nationalityId) {
            return $this->sendJsonResponse([
                ['value' => 'None', 'showExternalSearch' => false]
            ]);
        }

        $configItemsTable = TableRegistry::getTableLocator()
            ->get('Configuration.ConfigItems');

        // 1) Fetch non–“OpenEMIS Core” data sources
        $regularQuery = $configItemsTable->find()
            ->select([
                'id'   => $configItemsTable->aliasField('id'),
                'name' => $configItemsTable->aliasField('name'),
            ])
            ->where([
                $configItemsTable->aliasField('type = ')  . '"External Data Source - Identity"', // POCOR-9481
                $configItemsTable->aliasField('value = ') . 1,
                $configItemsTable->aliasField('name !=') . '"OpenEMIS Core"',
            ])
            ->innerJoin(
                ['Nationalities' => 'nationalities'],
                [
                    'Nationalities.id = '            . $nationalityId, // POCOR-9481
                    'Nationalities.identity_type_id = ' . $identityTypeId,
                    'Nationalities.external_validation = ' . $configItemsTable->aliasField('id'),
                ]
            )
            ->disableHydration();

        $regularResults = $regularQuery->toArray();
//        $regularResultSql = $regularQuery->sql(); // POCOR-9481
//        Log::debug(print_r([__FUNCTION__ => $regularResultSql,
//            __LINE__ => $regularResults], true));

        // 2) Check for “OpenEMIS Core” match via ExternalDataSourceAttributes
        $openEmisCoreItem = $configItemsTable->find()
            ->select(['name'])
            ->where([
                'type'  => 'External Data Source - Identity',
                'value' => 1,
                'name'  => 'OpenEMIS Core',
            ])
            ->first();

        $coreResults = [];
        if ($openEmisCoreItem) {
            $edaTable = TableRegistry::getTableLocator()
                ->get('Configuration.ExternalDataSourceAttributes');

            $match = $edaTable->find()
                ->select(['value'])
                ->where([
                    'external_data_source_type' => 'OpenEMIS Core',
                    'attribute_field'           => 'identity_type_id',
                    'value'                     => $identityTypeId,
                ])
                ->first();

            if ($match) {
                $coreResults[] = [
                    'name' => $openEmisCoreItem->name
                ];
            }
        }

        // 3) Map both sets into the unified result format
        $results = array_merge($regularResults, $coreResults);
        $resultArray = [];

        foreach ($results as $row) {
            $resultArray[] = [
                'value'              => $row['name'],
                'showExternalSearch' => true
            ];
        }

        // 4) Fallback if nothing matched
        if (empty($resultArray)) {
            $resultArray[] = [
                'value'              => 'None',
                'showExternalSearch' => false
            ];
        }

        return $this->sendJsonResponse($resultArray);
    }


    public
    function checkUserAge()
    {
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');

        //POCOR-8209 -- Start
        $configItemResult = $ConfigItems->find()
            ->select(['code', 'value', 'default_value'])
            ->where([
                $ConfigItems->aliasField('code') . ' IN' => ['StaffMinimumAge', 'StaffMaximumAge'],
                $ConfigItems->aliasField('visible') => 1
            ])
            ->toArray();

        $minStaffDefault = $minStaffValue = $maxStaffDefault = $maxStaffValue = null;

        foreach ($configItemResult as $item) {
            if ($item->code === 'StaffMinimumAge') {
                $minStaffDefault = $item->default_value;
                $minStaffValue = $item->value;
            } elseif ($item->code === 'StaffMaximumAge') {
                $maxStaffDefault = $item->default_value;
                $maxStaffValue = $item->value;
            }
        }

        try {
            if (empty($requestData['date_of_birth'])) {
                echo json_encode(['user_exist' => 0, 'status_code' => 400, 'message' => __('Date of birth is not set')]);
                die;
            } else {
                $minValuePattern = ($minStaffValue == null || $minStaffValue == 0) ? $minStaffDefault : $minStaffValue;
                $maxValuePattern = ($maxStaffValue == null || $maxStaffValue == 0) ? $maxStaffDefault : $maxStaffValue;


                $from = date('Y', strtotime($requestData['date_of_birth']));
                $to = date('Y');
                $dateDiff = ($to - $from);

                $minValuePattern = ($minValuePattern == 0) ? '' : $minValuePattern;
                $maxValuePattern = ($maxValuePattern == 0) ? '' : $maxValuePattern;

                if ($dateDiff < $minValuePattern) {
                    echo json_encode(['user_exist' => 0, 'status_code' => 400, 'message' => __('Minimum staff age:' . $minValuePattern)]);
                } else if ($dateDiff > $maxValuePattern) {
                    echo json_encode(['user_exist' => 0, 'status_code' => 400, 'message' => __('Maximum staff age:' . $maxValuePattern)]);
                } else {
                    echo json_encode(['user_exist' => 0, 'status_code' => 200, 'message' => __('valid Age')]);
                }
                die;
            }
        } catch (Exception $e) {
            echo json_encode(['user_exist' => 0, 'status_code' => 500, 'message' => __('Error fetching configuration values')]);
            die;
        }
        //POCOR-8209 -- end
    }

    public
    function customFieldsUseJustForExample()
    {
        $this->autoRender = false;
        $requestData = json_decode('{"login_user_id":"1","openemis_no":"152227233311111222","first_name":"AMARTAA","middle_name":"","third_name":"","last_name":"Fenicott","preferred_name":"","gender_id":"1","date_of_birth":"2011-01-01","identity_number":"1231122","nationality_id":"2","username":"kkk111","password":"sdsd","postal_code":"12233","address":"sdsdsds","birthplace_area_id":"2","address_area_id":"2","identity_type_id":"160","education_grade_id":"59","academic_period_id":"30", "start_date":"01-01-2021","end_date":"31-12-2021","institution_class_id":"524","student_status_id":1,"custom":[{"student_custom_field_id":17,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":27,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":29,"text_value":"test.jpg","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":28,"text_value":"","number_value":2,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":3,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":26,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":31,"text_value":"","number_value":4,"decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":8,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":9,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":30,"text_value":"{\"latitude\":\"11.1\",\"longitude\":\"2.22\"}","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"},{"student_custom_field_id":18,"text_value":"yes","number_value":"","decimal_value":"","textarea_value":"","time_value":"","file":"","created_user_id":1,"created":"22-01-20 08:59:35"}]}', true);

        $custom = $requestData['custom'];
        //echo "<pre>"; print_r($custom); die;
        if (!empty($custom)) {
            $studentCustomFieldValues = TableRegistry::getTableLocator()->get('student_custom_field_values');
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

    public
    function Lands()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Lands']);

    }

    public
    function StudentUserExport()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StudentUserExport']);
    }

    public
    function InstitutionBuses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionBuses']);
    }

    public
    function Distributions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionDistributions']);
    }

    public
    function ViewReport_old()
    {
        ini_set('memory_limit', '-1');
        $data = $_GET;
        $explode_data = explode("/", $data['file_path']);
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
        $this->Navigation->addCrumb($data['module']);
        $header = __('Reports') . ' - ' . $data['module'];

        $inputFileName = WWW_ROOT . 'export/' . end($explode_data);

        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        if ($data['module'] == 'InstitutionStatistics') {
            $highestRow = $sheet->getHighestRow() + 1;
        }
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= 1; $row++) {
            $rowHeader = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
        }

        $rowHeaderNew = $this->array_flatten($rowHeader);
        for ($row = 2; $row <= $highestRow - 1; $row++) {
            //  Read a row of data into an array
            $rowData[] = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
            if ($this->isEmptyRow(reset($rowData))) {
                continue;
            }
            //  Insert row data array into your database of choice here
        }
        foreach ($rowData as $newKey => $newDataVal) {
            foreach ($newDataVal as $kay2 => $new_data_arr) {
                if (isset($new_data_arr)) {
                    $newArr2[] = array_combine($rowHeaderNew, $new_data_arr);
                }
            }
        }
        $this->set('rowHeader', $rowHeader);
        $this->set('newArr2', $newArr2);

        $this->set('contentHeader', $header);
    }

    public function ViewReport() //POCOR-8485
    {
        ini_set('memory_limit', '-1');
        $data = $this->request->getQuery();
        $file = $this->request->getData('file_path');
        $data['file_path'] = $this->request->getQuery('file_path');

        $replace_data = str_replace('\\', '/', $data['file_path']);
        $institutionId = $this->getInstitutionID();

        if ($data['module'] == NULL) {
           $dataModule =  $data['amp;module'];
        } else {
            $dataModule = $data['module'];
        }
        $this->Navigation->addCrumb(__($data['module']), [
            'plugin' => $this->getPlugin(),
            'controller' => $this->getName(),
            'action' => $dataModule,
            '0' => 'index',
            '1' => $this->paramsEncode(['institution_id'=> $institutionId])
        ]);

        $header = __('Reports') . ' - ' . $data['module'];

        $inputFileName = $replace_data;
        // POCOR-8289 - for view report chagne in IOFactory logic
        try {
            $inputFileType = IOFactory::identify($inputFileName);
            $objReader = IOFactory::createReader($inputFileType);
            $spreadsheet = $objReader->load($inputFileName);
        } catch (\Exception $e) {
            throw new NotFoundException(__('Error loading file: ') . $e->getMessage());
        }

        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        if ($data['module'] == 'InstitutionStatistics') {
            $highestRow = $sheet->getHighestRow() + 1;
        }
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 1; $row <= 1; $row++) {
            $rowHeader = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
        }

        $rowHeaderNew = $this->array_flatten($rowHeader);
        for ($row = 2; $row <= $highestRow - 1; $row++) {
            $rowData[] = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
            if ($this->isEmptyRow(reset($rowData))) {
                continue;
            }
        }

        foreach ($rowData as $newKey => $newDataVal) {
            foreach ($newDataVal as $kay2 => $new_data_arr) {
                if (isset($new_data_arr)) {
                    $newArr2[] = array_combine($rowHeaderNew, $new_data_arr);
                }
            }
        }

        $this->set('rowHeader', $rowHeader);
        $this->set('newArr2', $newArr2);
        $this->set('contentHeader', $header);
    }

    function array_flatten($array)
    {
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

    function isEmptyRow($row)
    {
        foreach ($row as $cell) {
            if (null !== $cell) return false;
        }
        return true;
    }

    /**
     * Get the Feature options of the Institutions Standard Report
     * @return array
     * @ticket POCOR-6493
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     */
    public
    function getInstitutionStatisticStandardReportFeature(): array
    {
        // Start POCOR-6871
        $options = [
            'Institution.InstitutionStandardMarksEntered' => __('Marks Entered by Staff'),//POCOR-6630
            'Institution.InstitutionStaffPositionProfile' => __('Staff Career'),//POCOR-6581 //POCOR-6715 //POCOR-6886(changed report name from Staff Absences to Staff Career as per client suggestion)
            'Institution.InstitutionStandardStaffSpecialNeeds' => __('Staff Special Needs'),
            'Institution.InstitutionStandardStaffTrainings' => __('Staff Training'),
            'Institution.InstitutionStandardStudentAbsences' => __('Student Absences'),//POCOR-6631
            'Institution.InstitutionStandardStudentAbsenceType' => __('Student Absence Type'),//POCOR-6632
            'Institution.StudentAttendanceSummary' => __('Student Attendance Summary Report'),//POCOR-6872
            'Institution.StudentHealths' => __('Student Health'),
            'Institution.InstitutionStandards' => __('Students') . ' ' . __('Overview'),
            'Institution.StudentSpecialNeeds' => __('Student Special Needs'),
            'StaffAppraisal.Appraisals' => __('Staff Appraisals'),
            'Institution.InstitutionConsumablesReport' => __('Consumables') //POCOR-9058
        ];
        // End POCOR-6871
        return $options;
    }

    /**
     * POCOR-7224
     * Changes to Behaviour for Withdraw.
     * Stop the behavior in add student page. If Student in pending cancellation for withdraw
     **/
    public
    function checkStudentStatus($studentId, $academicPeriodId)
    {
        $institutionStudents = TableRegistry::getTableLocator()->get('institution_students');
        $studentWithdraw = TableRegistry::getTableLocator()->get('institution_student_withdraw');

        $WorkflowStepsTable = TableRegistry::getTableLocator()->get('workflow_steps');
        $WorkflowsTable = TableRegistry::getTableLocator()->get('workflows');
        $withdrawnId = TableRegistry::getTableLocator()->get('student_statuses')->findByCode('WITHDRAWN')->first()->id;

        $stepStatusId = $WorkflowStepsTable
            ->find()
            ->leftJoin([$WorkflowsTable->getAlias() => $WorkflowsTable->getTable()],
                [$WorkflowsTable->aliasField('id') . '=' . $WorkflowStepsTable->aliasField('workflow_id')]
            )->where([
                $WorkflowsTable->aliasField('code') => 'STUDENT-WITHDRAW-001',
                $WorkflowStepsTable->aliasField('name') => 'Withdrawn'
            ])->first()->id;
        $PendingStepStatusId = $WorkflowStepsTable
            ->find()
            ->leftJoin([$WorkflowsTable->getAlias() => $WorkflowsTable->getTable()],
                [$WorkflowsTable->aliasField('id') . '=' . $WorkflowStepsTable->aliasField('workflow_id')]
            )->where([
                $WorkflowsTable->aliasField('code') => 'STUDENT-WITHDRAW-001',
                $WorkflowStepsTable->aliasField('name') => 'Pending for Cancellation'
            ])->first()->id;

        $studentdata = $institutionStudents->find()->where(['student_status_id' => $withdrawnId, 'student_id' => $studentId, 'academic_period_id' => $academicPeriodId])->first();

        if ($PendingStepStatusId != null) {
            $pendingStudentwithdraw = $studentWithdraw->find()->where(['status_id' => $PendingStepStatusId, 'student_id' => $studentId, 'academic_period_id' => $entity->academic_period_id])->first();
        }

        if (!empty($studentdata) && !empty($pendingStudentwithdraw)) {
            return false; // show message here . can not proceed
        } else {
            return true;
        }

    }

    /**
     * Get User Data from CSPD api
     * @return array
     * @ticket POCOR-6930, POCOR-7916
     * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     *
     */
    public
    function getCspdData()
    {
        error_reporting(0);
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        //$requestData['identity_number'] = 9791048083;
        if (empty($requestData)) {
            echo json_encode(['status_code' => 400, 'message' => __('Invalid data.')]);
            die;
        }

        $search_by_id = $search_by_name = $do_search = false;
        $national_no = isset($requestData['identity_number']) ? $requestData['identity_number'] : null;
        $first_name = isset($requestData['first_name']) ? $requestData['first_name'] : null;
        $second_name = isset($requestData['middle_name']) ? $requestData['middle_name'] : null;
        $third_name = isset($requestData['third_name']) ? $requestData['third_name'] : null;
        $last_name = isset($requestData['last_name']) ? $requestData['last_name'] : null;
        if (!empty($national_no)) {
            $search_by_id = true;
        }
        if (empty($national_no) &&
            (!empty($first_name) ||
                !empty($second_name) ||
                !empty($third_name) ||
                !empty($last_name))) {
            $search_by_name = true;
        }
        if ($search_by_id || $search_by_name) {
            $externalDataSourceAttributesTbl = self::getDynamicTableInstance('external_data_source_attributes');
            $externalDataSourceAttributesData = $externalDataSourceAttributesTbl
                ->find()
                ->select(['id', 'external_data_source_type', 'attribute_field', 'attribute_name', 'value'])
                ->where([
                    $externalDataSourceAttributesTbl->aliasField('external_data_source_type') => 'Jordan CSPD'
                ])
                ->disableHydration()
                ->toArray();
            $config_Array = [];
            foreach ($externalDataSourceAttributesData as $ex_key => $ex_val) {
                $config_Array[$ex_val['attribute_field']] = trim($ex_val['value']);
            }
            if (!empty($config_Array['username']) && !empty($config_Array['password']) && !empty($config_Array['url'])) {
                $soapUrl = $config_Array['url'];
                $soapUser = $config_Array['username'];
                $soapPassword = $config_Array['password'];
            }
        } else {
            echo json_encode(['status_code' => 400, 'message' => __('Invalid data.')]);
            die;
        }
        if ($search_by_id) {
            $search_string = "<tem:gePersonal>
                             <!--Optional:-->
                             <tem:nationalNo>$national_no</tem:nationalNo>
                          </tem:gePersonal>";
            // xml post structure
            $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
                       <soapenv:Header>
                            <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                                <wsse:UsernameToken wsu:Id="UsernameToken-459">
                                    <wsse:Username>' . $soapUser . '</wsse:Username>
                                    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $soapPassword . '</wsse:Password>
                                </wsse:UsernameToken>
                            </wsse:Security>
                        </soapenv:Header>
                       <soapenv:Body>' .
                $search_string .
                '</soapenv:Body>
                    </soapenv:Envelope>';// data from the form, e.g. some ID number
            $soapAction = 'http://tempuri.org/IVitalEvents/gePersonal';
        }
        if ($search_by_name) {
            $search_string = "<getPersonalByName xmlns=\"http://tempuri.org/\">
      <fisrtName>$first_name</fisrtName>
      <secondName>$second_name</secondName>
      <thirdName>$third_name</thirdName>
      <familyName>$last_name</familyName>
    </getPersonalByName>";
            $xml_post_string = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
            <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
            <wsse:UsernameToken wsu:Id="UsernameToken-459">
                <wsse:Username>MOE</wsse:Username>
                <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">P@ssw0rd</wsse:Password>
            </wsse:UsernameToken>
        </wsse:Security>
  </soap:Header>
  <soap:Body>' .
                $search_string .
                '</soap:Body>
</soap:Envelope>';
            $soapAction = 'http://tempuri.org/IVitalEvents/getPersonalByName';
        }
//$this->log($xml_post_string, 'debug');
        $response = $this->CreateUsers->getResponseForCspd($soapUrl, $soapUser, $soapPassword, $xml_post_string, $soapAction);
        if (empty($response)) {
            echo json_encode(['status_code' => 200, 'message' => __('Response is empty.')]);
            die;
        }
        $arrayCspdResponse = $this->CreateUsers->XMLtoArray($response);
        if ($search_by_name) {
            $personsFromCSPD = $arrayCspdResponse['s:Envelope']['s:Body']['getPersonalByNameResponse']['getPersonalByNameResult']['a:PERSONAL'];
            if (isset($personsFromCSPD['a:ANAME1'])) { //SINGLE RECORD
                $personsFromCSPD = [$personsFromCSPD];
            }
//            $this->log('$search_by_name', 'debug');
//            $this->log($personsFromCSPD, 'debug');
        }
        if ($search_by_id) {
            $personFromCSPD = $arrayCspdResponse['s:Envelope']['s:Body']['gePersonalResponse']['gePersonalResult'];
//            $this->log('$search_by_id', 'debug');
//            $this->log($personFromCSPD, 'debug');
            $personsFromCSPD = [$personFromCSPD];
        }
        if (empty($personsFromCSPD)) {
            echo json_encode(['status_code' => 200, 'message' => __('Response is not normal.')]);
            die;
        }
//        $this->log($personsFromCSPD, 'debug');
        foreach ($personsFromCSPD as $externalPerson) {
            $result_Array = [];
            foreach ($externalDataSourceAttributesData as $ex_key => $ex_val) {
                if (in_array($ex_val['attribute_field'], ['username', 'password', 'url'])) {
                    unset($ex_val['attribute_field']);
                } else {
                    $value = 'a:' . $ex_val['value'];
                    if ($ex_val['attribute_field'] == 'first_name_mapping') {
                        $fieldKey = 'first_name';
                    } else if ($ex_val['attribute_field'] == 'middle_name_mapping') {
                        $fieldKey = 'middle_name';
                    } else if ($ex_val['attribute_field'] == 'third_name_mapping') {
                        $fieldKey = 'third_name';
                    } else if ($ex_val['attribute_field'] == 'last_name_mapping') {
                        $fieldKey = 'last_name';
                    } else if ($ex_val['attribute_field'] == 'gender_mapping') {
                        $fieldKey = 'gender_name';
                        $genders_types = self::getDynamicTableInstance('genders');
                        $genders_types_result = $genders_types
                            ->find()
                            ->select(['id', 'name'])
                            ->where([$genders_types->aliasField('name') => $externalPerson[$value]])
                            ->first();
                        $result_Array['gender_id'] = $genders_types_result->id;
                    } else if ($ex_val['attribute_field'] == 'date_of_birth_mapping') {
                        $fieldKey = 'date_of_birth';
                        $externalPerson[$value] = date('Y-m-d', strtotime($externalPerson[$value]));
                    } else if ($ex_val['attribute_field'] == 'identity_type_mapping') {
                        $identity_types = self::getDynamicTableInstance('identity_types');
                        $identity_types_result = $identity_types
                            ->find()
                            ->select(['id', 'name'])
                            ->where([$identity_types->aliasField('default') => 1])
                            ->first();
                        $result_Array['identity_type_id'] = $identity_types_result->id;
                        $externalPerson[$value] = $identity_types_result->name;
                        $fieldKey = 'identity_type_name';
                    } else if ($ex_val['attribute_field'] == 'identity_number_mapping') {
                        $fieldKey = 'identity_number';
                    } else if ($ex_val['attribute_field'] == 'address_mapping') {
                        $fieldKey = 'address';
                    } else if ($ex_val['attribute_field'] == 'postal_mapping') {
                        $fieldKey = 'postal_code';
                    } else if ($ex_val['attribute_field'] == 'nationality_mapping') {
                        $nationalitiesTbl = self::getDynamicTableInstance('nationalities');
                        $nationalities = $nationalitiesTbl->find()
                            ->select(['id', 'name'])
                            ->where([
                                $nationalitiesTbl->aliasField('name') => $externalPerson[$value],
                                $nationalitiesTbl->aliasField('visible') => 1,
                            ])
                            ->first();
                        $result_Array['nationality_id'] = (!empty($nationalities)) ? $nationalities->id : '';
                        $externalPerson[$value] = (!empty($nationalities)) ? $nationalities->name : $externalPerson[$value];
                        $fieldKey = 'nationality_name';
                    }
                    $result_Array[$fieldKey] = $externalPerson[$value];

                    $guardian_relations = self::getDynamicTableInstance('guardian_relations');
                    $guardian_relations_result = $guardian_relations
                        ->find()
                        ->where([$guardian_relations->aliasField('international_code !=') => ''])
                        ->disableHydration()
                        ->toArray();
                    if (!empty($guardian_relations_result)) {
                        foreach ($guardian_relations_result as $gkey => $gval) {
                            if (!empty($gval['international_code']) || !empty($gval['national_code'])) {
                                if (!empty($externalPerson)) {
                                    $value = 'a:' . $gval['international_code'];
                                    if ($gval['name'] == 'Father') {
                                        $relationsfieldKey = 'father_national_no';
                                    } else if ($gval['name'] == 'Mother') {
                                        $relationsfieldKey = 'mother_national_no';
                                    }
                                    $result_Array[$relationsfieldKey] = $externalPerson[$value];
                                }
                            }
                        }
                    }
                }
            }
            $results_Array[] = $result_Array;
        }
        echo json_encode(['status_code' => 200, 'message' => __('Get user details successfully.'), 'data' => $results_Array]);
        die;
    }

    /**
     * Get Configuration For External Source Data
     * @return array
     * @ticket POCOR-6930
     **@author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
     */
    public
    function getConfigurationForExternalSourceData()
    {
        $this->autoRender = false;
        //get Configuration For External Source Data from config_items table
        $configItemsTbl = TableRegistry::getTableLocator()->get('config_items');
        $configItemsResult = $configItemsTbl
            ->find()
            ->where(['visible' => 1, 'code' => 'external_data_source_type', 'type' => 'External Data Source Identity'])
            ->disableHydration()
            ->toArray();

        if (!empty($configItemsResult)) {
            foreach ($configItemsResult as $k => $val) {
                $result_array[] = array("id" => $val['id'], "name" => $val['name'], "code" => $val['code'], "type" => $val['type'], "value" => $val['value']);
            }
        }
        echo json_encode($result_array);
        die;
    }

    public
    function Addguardian()
    {
        $qs = $this->request->getQuery('queryString');
        if($qs){
            $requestDataa = base64_decode($this->request->getQuery('queryString'));
            $requestDataa = json_decode($requestDataa, true);
        }
        if (empty($requestDataa) && isset($this->request->getParam('pass')[0])) {
            $requestDataa = base64_decode($this->request->getParam('pass')[0]);
            $requestDataa = json_decode($requestDataa, true);
        }
        if (empty($requestDataa) && isset($this->request->getParam('pass')[0])) {
            $requestDataa = $this->getQueryString();
//            $requestDataa = json_decode($requestDataa, true);
        }
//        die(print_r($requestDataa, true));
        $UsersTable = self::getDynamicTableInstance('User.Users');
        $InstitutionTable = self::getDynamicTableInstance('Institution.Institutions');

        $student_id = $requestDataa['student_id'];
        $UserData = [];
        if (isset($student_id)) {
            $UserData = $UsersTable->find('all', ['conditions' => ['id' => $student_id]])->first();
        }
        if (empty($UserData)) {
        if (isset($requestDataa['openemis_no'])) {
            $UserData = $UsersTable->find('all', ['conditions' => ['openemis_no' => $requestDataa['openemis_no']]])->first();
        }
        }
        if(!empty($UserData)){
            $studentName = $UserData->name;
            $student_id = $UserData->id;
        }
        $institution_id = $requestDataa['institution_id'];
        if (isset($institution_id)) {
            $InstitutionData = $InstitutionTable->find('all', ['conditions' => ['id' => $institution_id]])->first();
        }

//        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        $encodedQueryString = $this->paramsEncode(['id' => $institution_id, 'institution_id' => $institution_id]);
        $queryStng = $this->paramsEncode(['id' => $student_id]);
        $this->Navigation->addCrumb(__('Students'), ['plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Students',
            '0' => 'index',
            '1' => $encodedQueryString
        ]);
        $this->Navigation->addCrumb($studentName, ['plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StudentUser',
            '0' => 'view',
            '1' => $this->ControllerAction->paramsEncode([
                'id' => $student_id,
                'student_id' => $student_id,
                'institution_id' => $institution_id])]);
        $this->Navigation->addCrumb(__('Add Guardians'), []);
        $this->set('InstitutionData', $InstitutionData);
        $this->set('UserData', $UserData);
        $this->set('StudentID', $institutionId);
        $this->set('StudentID1', $studentId);
        $this->set('queryStng', $queryStng);
        $this->set('ngController', 'DirectoryaddguardianCtrl as $ctrl');
    }

    public
    function getCurricularsTabElements($options = [])
    {
        $queryString = $this->request->getQuery('queryString');
        if (empty($queryString)) {
            $queryString = $this->request->getParam('pass')[1];
        }
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

    public
    function StudentCurriculars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentCurriculars']);
    }

    public
    function beforeRender(EventInterface $event)
    {

        parent::beforeRender($event);
        $this->viewBuilder()->addHelper('ControllerAction.ControllerAction');
        $this->viewBuilder()->addHelper('ControllerAction.HtmlField');
    }

    public
    function StaffAttendancesArchived($pass = '')
    {
        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        if ($pass == 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAttendancesArchived']);
        }

        if ($pass != 'excel') {
            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

            $this->Navigation->addCrumb('Staff Attendance',
                ['plugin' => $this->getPlugin(),
                    'controller' => 'Institutions',
                    'action' => 'InstitutionStaffAttendances',
                    'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId])]);

            $this->Navigation->addCrumb('Staff Attendance Archived');

            $this->setInstitutionStaffAttendancesOwnView();

            $this->setInstitutionStaffAttendancesOtherView();

            $this->setInstitutionStaffAttendancesPermissionStaffId();

            // $this->setStaffAttendancesArchivedExcel($institutionId);
            // dd('jkbj');
            $this->set('institution_id', $institutionId);
            $this->set('ngController', 'StaffAttendancesArchivedCtrl as $ctrl');
        }
    }

    /**
     * @param $institutionId
     */
    // private
    // function setStaffAttendancesArchivedExcel($institutionId)
    // {
    //     $_excel = $this->AccessControl->check(['Institutions', 'InstitutionStaffAttendances', 'excel']);
    //     $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__); // POCOR-7895

    //     $excelUrl = [
    //         'plugin' => 'Institution',
    //         'controller' => 'Institutions',
    //         'action' => 'StaffAttendancesArchived',
    //         'institutionId' => $this->ControllerAction->paramsEncode(['id' => $institutionId]),
    //         'excel'
    //     ];
    //     // POCOR-7895: start
    //     $where = ['institution_id' => $institutionId];
    //     $table_name = 'institution_staff_attendances';
    //     $_archive_1 = ArchiveConnections::hasArchiveRecords($table_name, $where);
    //     $table_name = 'institution_staff_leave';
    //     $_archive_2 = ArchiveConnections::hasArchiveRecords($table_name, $where);
    //     if ($_excel) {
    //         if ($_archive_1 or $_archive_2) {
    //             $_excel = $_archive_1;
    //         } else {
    //             $_excel = false;
    //             $excelUrl = null;
    //         }
    //     }

    //     $this->set('_excel', $_excel);
    //     // POCOR-7895: end
    //     $this->set('excelUrl', Router::url($excelUrl));
    // }

    //POCOR-7716 start
    public
    function getStudentAdmissionStatus()
    {
        $configItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $configItemResult = $configItems->find()->where([
            $configItems->aliasField('code') => "student_admission_status"
        ])->first();
        $studentStatus = !empty($configItemResult->value) ? $configItemResult->value : $configItemResult->default_value;
        $WorkflowStepsTable = self::getDynamicTableInstance('workflow.WorkflowSteps');
        if ($studentStatus == 0) {
            $result_array[] = array("id" => 0, "name" => "Enrolled");// setting 0 for enrolled as zero is not any id in workflow step
        } else {
            //POCOR-8434 starts
            //$status = $WorkflowStepsTable->get($studentStatus)->name;
            $WorkflowRes = $WorkflowStepsTable->find()->contain('Workflows')
                            ->where([$WorkflowStepsTable->aliasField('id') => $studentStatus])
                            ->first();
            if($WorkflowRes->workflow->name == 'Student Admission'){
                $status = 'Pending Admission : '.$WorkflowRes->name;
            }else{
                $status = 'Pending Enrolment : '.$WorkflowRes->name;
            }//POCOR-8434 ends
            $result_array[] = array("id" => $studentStatus, "name" => $status);
        }
        echo json_encode($result_array);
        die;
    }
    //POCOR-7716 end

    public
    function getMessagingTabElements($options = [])
    {
        $view = $this->AccessControl->check(['Institutions', 'MessageRecipients', 'index']);
        $queryString = $this->request->getQuery('queryString');
        if (empty($queryString)) {
            $queryString = $this->request->getParam('pass')[1];
        }
        $tabElements = [
            'Messaging' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Messaging', 'view', 'queryString' => $queryString],
                'text' => __('Messaging')
            ],

        ];
        if ($view) {
            $recipientTab = ['MessageRecipients' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'MessageRecipients', 'index', 'queryString' => $queryString],
                'text' => __('Recipients')
            ]];
            $tabElements = array_merge($tabElements, $recipientTab);
        }

        return $tabElements;
    }

//POCOR-7458 start

    public function StudentClasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentClasses']);
    }

    // POCOR-8334 start

    /**
     * Displays the student dashboard.
     *
     * @param string $action
     * @param string $encodedParam
     * @return void
     */
    public function StudentDashboard(string $action, string $encodedParam): void
    {
        $params = $this->paramsDecode($encodedParam);
        $Institutions = self::getDynamicTableInstance('Institution.Institutions');

        $userID = $params['user_id'];
        $userRole = "Student";
        $institutionID = $params['institution_id'];

        $hasPermission = $this->hasPermission($userID, $institutionID, 'StudentDashboard', 'view');

        $this->personalDashboard($action, $userRole, $userID, $institutionID, $hasPermission);
    }

    /**
     * @param string $tableName
     * @return Table
     */

    /**
     * Check if the user has permission to access the specified dashboard.
     *
     * @param int $userID
     * @param int $institutionID
     * @param string $action
     * @param string $view
     * @return bool
     */
    private function hasPermission(int $userID, int $institutionID, string $action, string $view): bool
    {
        if (!$this->AccessControl->isAdmin()) {
            $userId = $this->Auth->user('id');
            $Institutions = self::getDynamicTableInstance('Institution.Institutions');
            $roles = $Institutions->getInstitutionRoles($userId, $institutionID);
            $isActive = $Institutions->isActive($institutionID);
            if ($isActive) {
                return $this->AccessControl->check(['Institutions', $action, $view], $roles);
            }
            return false;
        }
        return true;
    }

    /**
     * Displays the personal dashboard.
     *
     * @param string $action
     * @param string $userRole
     * @param int $userID
     * @param int $institutionID
     * @param bool $hasPermission
     * @return void
     */
    public function personalDashboard(string $action, string $userRole, int $userID, int $institutionID, bool $hasPermission): void
    {
        if (!$action) {
            return;
        }

        $this->set('haveProfilePermission', $hasPermission);
        $UsersTable = self::getDynamicTableInstance('User.Users');

        $user = $UsersTable->get($userID);
        $userName = $user->name;
        $header = $userName . ' - ' . $userRole . ' Dashboard';
        $this->set('contentHeader', $header);
        $this->set('userName', $userName);

        $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
        $currentPeriod = $AcademicPeriods->getCurrent();
        // POCOR-7733 start
        $session = $this->request->getSession();
        $session->write('AcademicPeriod.currentAcademicPeriod', $currentPeriod);
        $session->write('AcademicPeriod.currentAcademicPeriodName', $AcademicPeriods->get($currentPeriod)->name);
        // POCOR-7733 end

        if (empty($currentPeriod)) {
            $this->Alert->warning('Institution.Institutions.academicPeriod');
        }

        $highChartDatas = $this->getPersonalHighchartData($userID, $institutionID, $userRole);
        $profileData = $this->getPersonalProfileCompletenessData($userID, $userRole);

        $this->set('personalProfileCompletness', $profileData);
        $this->set('highChartDatas', $highChartDatas);

        $indexDashboard = 'dashboard';
        $this->set('mini_dashboard', [
            'name' => $indexDashboard,
            'data' => [
                'model' => 'staff',
                'modelCount' => 25,
                'modelArray' => []
            ]
        ]);
    }

    /**
     * Get personal profile completeness highchart data.
     *
     * @param int $userID
     * @param int $institutionID
     * @param string $userRole
     * @return array
     */
    public function getPersonalHighchartData(int $userID, int $institutionID, string $userRole): array
    {
        $StaffStatuses = self::getDynamicTableInstance('Staff.StaffStatuses');
        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
        $InstitutionStaff = self::getDynamicTableInstance('Institution.Staff');

        // only show student charts if institution is academic
        $InstitutionStudents = self::getDynamicTableInstance('Institution.Students');
        $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();
        $highChartDatas = [];

        if ($userRole === 'Student' || $userRole === 'Students') {
            $params = [
                'conditions' => [
                    'institution_id' => $institutionID,
                    'student_id' => $userID,
                    'student_status_id NOT IN ' => [
                        $statuses['TRANSFERRED'],
                        $statuses['WITHDRAWN'],
                        $statuses['PROMOTED'],
                        $statuses['REPEATED']
                    ]
                ]
            ];

            $highChartDatas[] = $InstitutionStudents->getHighChart('student_attendance', $params);
        }

        if ($userRole === 'Staff') {
            $params = [
                'conditions' => [
                    'institution_id' => $institutionID,
                    'staff_status_id' => $assignedStatus,
                    'staff_id' => $userID
                ]
            ];
            $highChartDatas[] = $InstitutionStaff->getHighChart('staff_attendance', $params);
        }

        // Students By Grade for current year, excludes transferred, withdrawn, promoted, repeated students
        $params = [
            'conditions' => [
                'institution_id' => $institutionID,
                'student_status_id NOT IN ' => [
                    $statuses['TRANSFERRED'],
                    $statuses['WITHDRAWN'],
                    $statuses['PROMOTED'],
                    $statuses['REPEATED']
                ]
            ]
        ];

        return $highChartDatas;
    }

    /**
     * Get personal profile completeness data.
     *
     * @param int $userID
     * @param string $userRole
     * @return array
     */
    public function getPersonalProfileCompletenessData(int $userID, string $userRole): array
    {
        if ($userRole === 'Students') {
            $userRole = 'Student';
        }

        $data = [];
        $profileComplete = 0;
        $usersData = [
            'Overview' => $this->getLastData($userID, 'security_users', 'id'),
            'Nationalities' => $this->getLastData($userID, 'user_nationalities', 'security_user_id'),
            'Identities' => $this->getLastData($userID, 'user_identities', 'security_user_id')
        ];

        if ($userRole === 'Staff') {
            $usersData['Contacts'] = $this->getLastData($userID, 'user_contacts', 'security_user_id');
            $usersData['Qualifications'] = $this->getLastData($userID, 'staff_qualifications', 'staff_id');
        }

        if ($userRole === 'Student') {
            $usersData['Guardians'] = $this->getLastData($userID, 'student_guardians', 'student_id');
            $usersData['Absence'] = $this->getLastData($userID, 'institution_student_absences', 'student_id');
        }

        $ConfigItem = self::getDynamicTableInstance('Configuration.ConfigItems');
        $enabledTypeList = $ConfigItem->find()
            ->select(['name' => $ConfigItem->aliasField('name')])
            ->order('label')
            ->where([
                $ConfigItem->aliasField('visible') => 1,
                $ConfigItem->aliasField('value') => 1,
                $ConfigItem->aliasField('type') => $userRole . ' Data Completeness'
            ])->toArray();

        foreach ($enabledTypeList as $key => $enabled) {
            $data[$key]['feature'] = $enabled->name;
            $singleData = $usersData[$enabled->name] ?? null;
            if (!empty($singleData)) {
                $profileComplete++;
                $data[$key]['complete'] = 'yes';
                $data[$key]['modifiedDate'] = $singleData->modified ? date("F j, Y", strtotime($singleData->modified)) : date("F j, Y", strtotime($singleData->created));
            } else {
                $data[$key]['complete'] = 'no';
                $data[$key]['modifiedDate'] = 'Not updated';
            }
        }

        $totalProfileComplete = count($data);
        $profilePercentage = $totalProfileComplete > 0 ? round((100 / $totalProfileComplete) * $profileComplete) : 0;
        $data['percentage'] = $profilePercentage;

        return $data;
    }

    /**
     * Get the last data entry for a given user from a specified table.
     *
     * @param int $userID
     * @param string $tableName
     * @param string $fieldName
     * @return \Cake\Datasource\EntityInterface|null
     */
    public function getLastData(int $userID, string $tableName, string $fieldName): ?\Cake\Datasource\EntityInterface
    {
        $table = self::getDynamicTableInstance($tableName);
        return $table->find()
            ->select([
                'created' => $table->aliasField('created'),
                'modified' => $table->aliasField('modified')
            ])
            ->where([$table->aliasField($fieldName) => $userID])
            ->orderDesc($table->aliasField('modified'))
            ->limit(1)
            ->first();
    }

    /**
     * Displays the staff dashboard.
     *
     * @param string $action
     * @param string $encodedParam
     * @return void
     */
    public function StaffDashboard(string $action, string $encodedParam): void
    {
        $params = $this->paramsDecode($encodedParam);
        $userID = $params['user_id'];
        $institutionID = $params['institution_id'];
        $userRole = "Staff";

        $hasPermission = $this->hasPermission($userID, $institutionID, 'StaffDashboard', 'view');

        $this->personalDashboard($action, $userRole, $userID, $institutionID, $hasPermission);
    }

// POCOR-8334 END

    private
    function hasPermissionToViewStudentAttendanceArchive($institutionId)
    {
        $has_permission_to_view_archive = false;
        if ($this->Auth->user('super_admin') == 1) {
            $has_permission_to_view_archive = true;
            return $has_permission_to_view_archive;
        }
        $logged_in_user_id = $this->Auth->user('id');
        $SecurityFunctionsTable = TableRegistry::getTableLocator()->get('SecurityFunctions');
        $SecurityGroupUsersTable = TableRegistry::getTableLocator()->get('security_group_users');
        $SecurityInstitutionsTable = TableRegistry::getTableLocator()->get('security_group_institutions');
        $SecurityRoleFunTable = TableRegistry::getTableLocator()->get('security_role_functions');
        $securityGroupUserViewArchiveAccessCount = $SecurityGroupUsersTable->find('all')
            ->select([$SecurityGroupUsersTable->aliasField('security_role_id'),
                    'edit' => $SecurityRoleFunTable->aliasField('_edit')
//                                $SecurityGroupUsersTable->aliasField('id')
                ]
            )
            ->distinct([$SecurityGroupUsersTable->aliasField('security_role_id'),
                'edit'])
            ->innerJoin(
                [$SecurityInstitutionsTable->getAlias() => $SecurityInstitutionsTable->getTable()],
                [
                    $SecurityInstitutionsTable->aliasField('institution_id = ') . $institutionId,
                    $SecurityInstitutionsTable->aliasField('security_group_id = ') . $SecurityGroupUsersTable->aliasField('security_group_id'),
                ]
            )->where([$SecurityGroupUsersTable->aliasField('security_user_id') => $logged_in_user_id,
            ])
            ->innerJoin(
                [$SecurityRoleFunTable->getAlias() => $SecurityRoleFunTable->getTable()],
                [
                    $SecurityRoleFunTable->aliasField('security_role_id = ') .
                    $SecurityGroupUsersTable->aliasField('security_role_id'),
                    $SecurityRoleFunTable->aliasField('_view') => '1'
                ]
            )->innerJoin([$SecurityFunctionsTable->getAlias() => $SecurityFunctionsTable->getTable()],
                [
                    $SecurityRoleFunTable->aliasField('security_function_id')
                    => $SecurityFunctionsTable->aliasField('id'),
                    $SecurityFunctionsTable->aliasField('name') => 'Student Attendance Archive'
                ])
            ->count();
//                    $this->log($securityGroupUserViewArchiveAccessCount, 'debug');
        if ($securityGroupUserViewArchiveAccessCount > 0) {
            $has_permission_to_view_archive = true;
        }
        return $has_permission_to_view_archive;
    }

    //POCOR -8333 starts
    public
    function StudentHistories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserHistories']);
    }//POCOR -8333 ends

    //POCOR -8333 starts
    public
    function StaffHistories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserHistories']);
    }//POCOR -8333 ends

    public function History()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InstitutionHistories']);
    }

    private static function debug($something)
    {
        if (is_null($something)) {
            $message = 'NULL';
        } elseif (is_bool($something)) {
            $message = $something ? 'TRUE' : 'FALSE';
        } elseif (is_array($something) || is_object($something)) {
            $message = json_encode($something, JSON_PRETTY_PRINT);
        } else {
            $message = (string)$something;
        }

        \Cake\Log\Log::debug($message);
    }


    //POCOR-7971 end
    public function getInstitutionGpaTab($action = null)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $tabElements = [
            'ReportCardGpa' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'ReportCardGpa', 0 => 'index', 1 => $encodedQueryString],
                'text' => __('GPA')
            ],
            'ReportCardCumulativeGpa' => [
                'url' => ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'ReportCardCumulativeGpa', 0 => 'index', 1 => $encodedQueryString],
                'text' => __('Cumulative GPA')
            ],

        ];
        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        $this->set('tabElements', $tabElements);
        $action = !is_null($action) ? $action : $this->request->getParam('action');
        $this->set('selectedAction', $action);
    }

    public function ReportCardGpa()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardGpa']);
    }

    public function ReportCardCumulativeGpa()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.ReportCardCumulativeGpa']);
    }

    /**
     * POCOR-8779
     * @param  $institutionClassId
     * @param  $academicPeriodId
     * @param $user_record_id
     * @param  $educationGradeId
     * @param  $institutionId
     * @param $CURRENT
     * @param  $userId
     */
    private static function assignStudentSubject( $institutionClassId,  $academicPeriodId, $user_record_id,  $educationGradeId,  $institutionId, $CURRENT,  $userId)
    {
//        Log::debug(print_r([$institutionClassId,  $academicPeriodId, $user_record_id,  $educationGradeId,  $institutionId, $CURRENT,  $userId], true));
        $institutionClassSubjects = self::getDynamicTableInstance('institution_class_subjects');
        $institutionSubjects = self::getDynamicTableInstance('institution_subjects');
        $educationGradesSubjects = self::getDynamicTableInstance('education_grades_subjects');//POCOR-7197
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
            ->LeftJoin([$institutionSubjects->getAlias() => $institutionSubjects->getTable()], [
                $institutionSubjects->aliasField('id =') . $institutionClassSubjects->aliasField('institution_subject_id')
            ])//POCOR-7197 starts
            ->InnerJoin([$educationGradesSubjects->getAlias() => $educationGradesSubjects->getTable()], [
                $institutionSubjects->aliasField('education_grade_id =') . $educationGradesSubjects->aliasField('education_grade_id'),
                $institutionSubjects->aliasField('education_subject_id =') . $educationGradesSubjects->aliasField('education_subject_id')
            ])//POCOR-7197 ends
            ->where([
                $institutionClassSubjects->aliasField('institution_class_id') => $institutionClassId,
                $institutionSubjects->aliasField('academic_period_id') => $academicPeriodId,//POCOR-7197
                $educationGradesSubjects->aliasField('auto_allocation !=') => 0//POCOR-7197
            ])
            ->toArray();
//        Log::debug(print_r($SubjectsResult, true));

        if (!empty($SubjectsResult)) {
            $count = 1;
            $institutionSubjectStudents = self::getDynamicTableInstance('institution_subject_students');
            foreach ($SubjectsResult as $skey => $sval) {
                $primaryKey = $institutionSubjectStudents->getPrimaryKey();
                $hashString = [];
                foreach ($primaryKey as $key) {
                    if ($key == 'student_id') {
                        $hashString[] = $user_record_id;
                    }
                    if ($key == 'institution_class_id') {
                        $hashString[] = $institutionClassId;
                    }
                    if ($key == 'academic_period_id') {
                        $hashString[] = $academicPeriodId;
                    }
                    if ($key == 'education_grade_id') {
                        $hashString[] = $educationGradeId;
                    }
                    if ($key == 'institution_id') {
                        $hashString[] = $institutionId;
                    }
                    if ($key == 'education_subject_id') {
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
                    'student_status_id' => $CURRENT,
                    'created_user_id' => $userId,
                    'created' => date('Y-m-d H:i:s')
                ];
                //save in institution_subject_students table
                $entitySubjectsData = $institutionSubjectStudents->newEntity($entitySubjectsData);
                $institutionSubjectStudentsResult = $institutionSubjectStudents->save($entitySubjectsData);
                $count++;
                unset($entitySubjectsData);
                unset($institutionSubjectStudentsResult);
                unset($hashString);
            }
            return $count;
        }
        return null;
    }

    public function Scanned($pass = '')
    {
        $baseUrl = Router::fullBaseUrl();
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionId();
        $institutionName = $this->Institutions->get($institutionId)->name;
        $encodedParams = $this->ControllerAction->paramsEncode(['id' => $institutionId]);
        $institutionDashborad = "{$this->plugin}/Institutions/{$encodedParams}/dashboard/{$encodedParams}";
        $institutionIndex = "Institutions/Institutions/index/";


             $url = $_SERVER['REQUEST_URI'];
             $startPos = strpos($url, '/Institution/Institutions/Scanned/index/') + strlen('/Institution/Institutions/Scanned/index/');
             $encodedPart = substr($url, $startPos);

            $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);

            $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));

            $this->Navigation->addCrumb($crumbTitle);
            //POCOR-8500 do not remove it. used in angular start
            $user = $this->getRequest()->getSession()->read('sbn');
            $pass = $this->getRequest()->getSession()->read('nbn');
            $pass = $this->paramsEncode([$pass]);
            //end
            $this->set('institution_id', $institutionId);
            $this->set('url', $encodedPart);
            $this->set('institutionName', $institutionName);
            $this->set('institutionDashborad', $institutionDashborad);
            $this->set('institutionIndexUrl', $institutionIndex);
            $this->set('baseUrl', $baseUrl);
            $this->set('user', $user);
            $this->set('pass', $pass);
            $this->render('scanned_data');
    }

    //POCOR-5208
    public function InfrastructureAttachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureAttachments']);
    }

    /**
     * POCOR-9407
     * Download an attached file for an institution custom field value.
     *
     * Looks up the file by `institution_id` and ensures both `file` (BLOB)
     * and `file_name` exist in the record. If found, it streams the file
     * back to the browser as a downloadable attachment.
     * @return \Cake\Http\Response The file download response.
     * @throws \Cake\Http\Exception\NotFoundException If no file or record is found.
     */
    public function downloadFile($id = null)
    {
        $this->autoRender = false;
        if (empty($id)) {
            throw new NotFoundException(__('Invalid file'));
        }

        // Load your custom field values table
        $InstitutionCustomFieldValues = TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionCustomFieldValues');
        $fileRecord = $InstitutionCustomFieldValues->find()
                        ->where([
                            'file IS NOT' => null,
                            'file_name IS NOT' => null,
                            'institution_id' => $this->getInstitutionID(),
                        ])->first();

        if (empty($fileRecord) || empty($fileRecord->file_name) || empty($fileRecord->file)) {
            throw new NotFoundException(__('File not found'));
        }

        $fileName = $fileRecord->file_name;
        $fileResource = $fileRecord->file;
        $this->response = $this->response
            ->withType(mime_content_type($fileResource))
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->withStringBody(stream_get_contents($fileResource));

        return $this->response;
    }

    //POCOR-9475
    public function InfrastructureElectricitiesHistory()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureElectricitiesHistory']);
    }

    //POCOR-9475
    public function InfrastructureInternetHistory()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureInternetHistory']);
    }

    //POCOR-9475
    public function InfrastructureTelephonesHistory()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.InfrastructureTelephonesHistory']);
    }

    //POCOR-4681
    public function HistoryPdf()
    {
        $this->autoRender = false;
        $pass   = $this->request->getParam('pass');
        $params = !empty($pass[0]) ? $this->paramsDecode($pass[0]) : [];

        $modelType = $params['model'] ?? 'Institution';

        // Config per model type: which ORM table to use, which FK to filter, to build the label
        $modelConfig = [
            'Institution' => [
                'tableClass'   => 'Institution.InstitutionHistories',
                'filterField'  => 'institution_id',
                'filterId'     => $params['institution_id'] ?? null,
                'entityTable'  => 'Institution.Institutions',
                'entityFields' => ['id', 'name', 'code'],
                'subjectLabel' => function ($e) {
                    return h($e->name) . ' (' . h($e->code) . ')';
                },
                'title'        => __('Institution History'),
                'filePrefix'   => 'institution_history',
            ],
            'User' => [
                'tableClass'   => 'User.UserHistories',
                'filterField'  => 'security_user_id',
                'filterId'     => $params['security_user_id'] ?? null,
                'entityTable'  => 'User.Users',
                'entityFields' => ['id', 'first_name', 'last_name'],
                'subjectLabel' => function ($e) {
                    return h(trim($e->first_name . ' ' . $e->last_name));
                },
                'title'        => __('User History'),
                'filePrefix'   => 'user_history',
            ],
        ];

        $cfg = $modelConfig[$modelType] ?? $modelConfig['Institution'];

        // Fetch subject entity (institution or user) for the PDF header
        $entityTable = TableRegistry::getTableLocator()->get($cfg['entityTable']);
        $entity      = $entityTable->get($cfg['filterId'], ['fields' => $cfg['entityFields']]);
        $subjectName = ($cfg['subjectLabel'])($entity);

        // Fetch history rows with created_user join
        $HistoriesTable = TableRegistry::getTableLocator()->get($cfg['tableClass']);
        $histories = $HistoriesTable->find()
            ->contain(['CreatedUser' => ['fields' => ['id', 'first_name', 'last_name']]])
            ->where([$HistoriesTable->aliasField($cfg['filterField']) => $cfg['filterId']])
            ->order([$HistoriesTable->aliasField('created') => 'DESC'])
            ->all();

        // Build PDF HTML
        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; font-size: 11px; }
            h2   { font-size: 14px; margin-bottom: 4px; }
            p.subtitle { font-size: 11px; color: #555; margin-top: 0; margin-bottom: 12px; }
            table { width: 100%; border-collapse: collapse; }
            th { background-color: #4a90d9; color: #fff; padding: 6px 8px; text-align: left; font-size: 11px; }
            td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 11px; vertical-align: top; }
            tr:nth-child(even) td { background-color: #f5f5f5; }
        </style></head><body>';
        $html .= '<h2>' . $cfg['title'] . '</h2>';
        $html .= '<p class="subtitle">' . $subjectName . '</p>';
        $html .= '<table><thead><tr>';
        $html .= '<th>' . __('Model') . '</th>';
        $html .= '<th>' . __('Field') . '</th>';
        $html .= '<th>' . __('Old Value') . '</th>';
        $html .= '<th>' . __('New Value') . '</th>';
        $html .= '<th>' . __('Modified By') . '</th>';
        $html .= '<th>' . __('Modified On') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($histories as $row) {
            $modifiedBy = '';
            if (!empty($row->created_user)) {
                $modifiedBy = h(trim($row->created_user->first_name . ' ' . $row->created_user->last_name));
            }
            $modifiedOn = !empty($row->created) ? $row->created->format('Y-m-d H:i') : '';
            $html .= '<tr>';
            $html .= '<td>' . h($row->model) . '</td>';
            $html .= '<td>' . h($row->field) . '</td>';
            $html .= '<td>' . h($row->old_value) . '</td>';
            $html .= '<td>' . h($row->new_value) . '</td>';
            $html .= '<td>' . $modifiedBy . '</td>';
            $html .= '<td>' . $modifiedOn . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']);
        $mpdf->WriteHTML($html);

        $filename = $cfg['filePrefix'] . '_' . $cfg['filterId'] . '_' . date('Ymd') . '.pdf';
        $mpdf->Output($filename, 'D');
        exit;
    }

}



