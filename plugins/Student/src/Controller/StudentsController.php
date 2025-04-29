<?php

namespace Student\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;

class StudentsController extends AppController
{
    private $features = [
        // General
        'Identities',
        'UserNationalities',
        'Contacts',
        'Guardians',
        'GuardianUser',
        'UserLanguages',
        'Attachments',
        'Comments',
        // 'UserActivities',
        // 'StudentSurveys',

        // academic
        // 'StudentClasses',
        // 'StudentSubjects',
        // 'Absences',
        // 'StudentBehaviours',
        'Awards',
        'Extracurriculars',

        // finance
        'BankAccounts',
        // 'StudentFees',

        // health
        'Healths',
        'Allergies',
        'Consultations',
        'Families',
        'Histories',
        'Immunizations',
        'Medications',
        'Tests',

        // special needs
        'SpecialNeedsReferrals',
        'SpecialNeedsAssessments',
        'SpecialNeedsServices',
        'SpecialNeedsDevices',
        'SpecialNeedsPlans',
        'SpecialNeedsDiagnostics'    //POCOR-6873
    ];

    public function initialize(): void
    {
        parent::initialize();

        $this->ControllerAction->model('Institution.StudentUser');
        $this->ControllerAction->models = [
            'Accounts' => ['className' => 'Student.Accounts', 'actions' => ['view', 'edit']],
            'Nationalities' => ['className' => 'User.Nationalities'],
            // 'Absences'          => ['className' => 'Student.Absences', 'actions' => ['index', 'view','remove']],
           // 'Behaviours' => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
            'Extracurriculars' => ['className' => 'Student.Extracurriculars', 'actions' => ['index', 'add', 'edit', 'remove', 'view']],//POCOR-6700
//            'History' => ['className' => 'User.UserActivities', 'actions' => ['index']], //POCOR-7485 cakephp4 use as a function
            'ImportStudents' => ['className' => 'Student.ImportStudents', 'actions' => ['index', 'add']],
        ];

        $this->loadComponent('User.Image');
        $this->loadComponent('Institution.InstitutionAccessControl');
        $this->attachAngularModules();

        $this->loadModel('User.UserBodyMasses');
        $this->loadModel('User.UserInsurances');

        $this->set('contentHeader', 'Students');

        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
    }

    // CAv4

    private function attachAngularModules()
    {
        $action = $this->request->getParam('action');

        switch ($action) {
            case 'Results':
                $this->Angular->addModules([
                    'alert.svc',
                    'student.results.ctrl',
                    'student.results.svc'
                ]);
                break;

            case 'ExaminationResults':
                $this->Angular->addModules([
                    'alert.svc',
                    'student.examination_results.ctrl',
                    'student.examination_results.svc'
                ]);
                break;
            case 'StudentScheduleTimetable':

                $this->Angular->addModules([
                    'studenttimetable.ctrl',
                    'studenttimetable.svc'
                ]);
                break;
        }
    }

    public function Employments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserEmployments']);
    }

    //POCOR-7528 start

    public function Qualifications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Qualifications']);
    }

    //POCOR-7528 end

    public function Licenses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Licenses']);
    }

    public function StudentFees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentFees']);
    }

    public function Classes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentClasses']);
    }

    //POCOR-7474-HINDOL TYPO FIX

    public function Subjects()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSubjects']);
    }

    public function Assessments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.CurrentAssessments']);
    }

    public function AssessmentsArchived()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.ArchivedAssessments']);
    }

    public function Nationalities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']);
    }

    public function Languages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']);
    }

    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']);
    }

    public function BankAccounts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.BankAccounts']);
    }

    public function Identities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']);
    }

    public function Awards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']);
    }

    public function Guardians()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Guardians']);
    }

    public function GuardianUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.GuardianUser']);
    }

    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']);
    }

    public function ReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentReportCards']);
    }

    public function Demographic()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Demographic']);
    }

    public function History()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserActivities']);
    }

    public function StudentTransport()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentTransport']);
    }

    public function Outcomes()
    {
        $comment = $this->request->getQuery('comment');
        if (!empty($comment) && $comment == 1) {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomeComments']);

        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomes']);
        }
    }

    // POCOR-8299 start
    public function Absences()
    {
        $request = $this->request;
        $pass = $request->getParam('pass');
        $passAction = $pass[0] ?? null;
        if ($passAction === 'index' || $passAction === 'excel') {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Attendances', 'actions' => ['index', 'excel']]);
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Absences']);
        }
    }
    // POCOR-8299 end

    public function ArchivedAbsences()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.ArchivedAbsences']);
    }

    public function Meals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.InstitutionMealStudents']);
    }

    public function Profiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Profiles']);
    }

    // Healths
    public function Healths()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Healths']);
    }

    public function HealthAllergies()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Allergies']);
    }

    public function HealthConsultations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Consultations']);
    }

    public function HealthFamilies()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Families']);
    }

    public function HealthHistories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Histories']);
    }

    public function HealthImmunizations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Immunizations']);
    }

    public function HealthMedications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Medications']);
    }


    public function HealthTests()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Tests']);
    }
    // End Health

    // Special Needs

    public function SpecialNeedsReferrals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsReferrals']);
    }

    public function SpecialNeedsAssessments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsAssessments']);
    }

    public function SpecialNeedsServices()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsServices']);
    }

    public function SpecialNeedsDevices()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsDevices']);
    }

    public function SpecialNeedsPlans()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsPlans']);
    }

    public function SpecialNeedsDiagnostics()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsDiagnostics']);
    }
    //POCOR-6873
    // Special Needs - End

    // Visits

    public function StudentVisitRequests()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentVisitRequests']);
    }

    public function StudentVisits()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentVisits']);
    }
    // Visits - END

    public function Counsellings()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Counsellings']);
    }

    // End

    public function Competencies()
    {
        $session = $this->request->getSession();
        $studentID = $this->getStudentID();
        //if ($session->check('Student.Students.id')) {
        if ($studentID !='') {
            //$studentId = $session->read('Student.Students.id');
            $studentId = $studentID;
            $session->write('Student.Competencies.student_id', $studentId);
            // tabs
            // $options = ['type' => 'student'];
            // $tabElements = $this->getAcademicTabElements($options);
            // $this->set('tabElements', $tabElements);
            // $this->set('selectedAction', 'Competencies');
            // End

            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentCompetencies']);
        }

    }

    public
    function getAcademicTabElements($options = [])
    {
        //$tabElements = TableRegistry::get('Institution.StudentUser')->getAcademicTabElementsNew($options);//PCOOR-8388
        $this->loadModel('Institution.StudentUser');//PCOOR-8388
        $tabElements = $this->StudentUser->getAcademicTabElements($options, $this);//PCOOR-8388
        return $tabElements;
    }

    public function AssessmentItemResultsArchived()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.AssessmentItemResultsArchived']);
    }

    //POCOR-6131 - Add export Button
    public function StudentBodyMasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserBodyMasses']);
    }

    //POCOR-6131 - Add export Button

    // AngularJS

    public function StudentInsurances()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserInsurances']);
    }

    public function changeStudentHealthHeader($model, $modelAlias, $userType)
    {
        if ($this->request->getAttribute('params')['action'] == 'StudentBodyMasses') {
            $institutionId = $this->getInstitutionID();
            $studentID = $this->getStudentID();

            if (!empty($institutionId)) {
                $session = $this->request->getSession();
                $studentName = $session->read('Student.Students.name');
                $header = $studentName . ' - ' . __('Body Mass');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Body Mass'));
                $this->set('contentHeader', $header);
            }
        } else if ($this->request->getAttribute('params')['action'] == 'StudentInsurances') {
            if (!empty($institutionId)) {
                $session = $this->request->getSession();
                $studentName = $session->read('Student.Students.name');
                $header = $studentName . ' - ' . __('Insurances');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore('Student Insurances')));
                $this->Navigation->addCrumb(__('Insurances'));
                $this->set('contentHeader', $header);
            }
        }
    }

    /**
     * common function to get institution id
     * @return string|null
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public
    function getInstitutionID($debugString = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->getQueryString('institution_id');
        if (!$institution_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put institution_id into query string first');
            }
        }
        return $institution_id;
    }

    // End

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

    public function Results()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentAssisments']);
        $session = $this->request->getSession();
        $_archive = $this->AccessControl->check(['Staff', 'InstitutionStaffAttendanceActivities', 'index']);
        $archiveUrl = $this->ControllerAction->url('index');
        $archiveUrl['plugin'] = 'Student';
        $archiveUrl['controller'] = 'Students';
        $archiveUrl['action'] = 'AssessmentItemResultsArchived';
        if ($session->check('Student.Students.id')) {
            $studentId = $session->read('Student.Students.id');
            $session->write('Student.Results.student_id', $studentId);

            // tabs
            $options = ['type' => 'student'];
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('_archive', $_archive);
            $this->set('archiveUrl', Router::url($archiveUrl));
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'Results');
            // End

            $this->set('ngController', 'StudentResultsCtrl as StudentResultsController');
        }
    }

    public function InstitutionStudentAbsencesArchived()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.InstitutionStudentAbsencesArchived']);
    }

    public function ExaminationResults()
    {
        $session = $this->request->getSession();
        $studentID = $this->getStudentID();
        //if ($session->check('Student.Students.id')) {
        if ($studentID) {
            //$studentId = $session->read('Student.Students.id');
            $studentId = $studentID;
            $session->write('Student.ExaminationResults.student_id', $studentId);

            // tabs
            $options = ['type' => 'student'];
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'ExaminationResults');
            // End


            // Start POCOR-5188
            $manualTable = TableRegistry::get('Manuals');
            $ManualContent = $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Examinations',
                $manualTable->aliasField('module') => 'Institutions',
                $manualTable->aliasField('category') => 'Students - Academic',
            ])->first();

            if (!empty($ManualContent['url'])) {
                $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
            } else {
                $this->set('is_manual_exist', []);
            }
            // End POCOR-5188

            $this->set('ngController', 'StudentExaminationResultsCtrl as StudentExaminationResultsController');
        }
    }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $isInstitutionIDSkipped = $this->isStudentIDSkipped();
        if ($isInstitutionIDSkipped) {
            $header = __('Students');
            $this->set('contentHeader', $header);
            return;
        }

        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $action = $this->request->getAttribute('params')['action'];
        $institutionID = $this->getInstitutionID();
        if ($institutionID) { // POCOR-9061
            $activeInstitution = $this->Institutions->get($institutionID);
            $institutionName = $activeInstitution->name;

            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionID, 'institution_id' => $institutionID]);
            $this->Navigation->addCrumb($institutionName,
                ['plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'dashboard',
                    'institutionId' => $encodedInstitutionId,
                    $encodedInstitutionId]);
            $this->Navigation->addCrumb('Students',
                ['plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'Students',
                    0 => 'index',
                    1 => $encodedInstitutionId
                ]);
        } // POCOR-9061
        $header = __('Students');
        $checkStudentId = $this->getStudentID();

        if ($action == 'index') {
        } else if ($checkStudentId || $action == 'view' || $action == 'edit' || $action == 'Results') {
            // add the student name to the header
            $id = 0;
            if (isset($this->request->getParam('pass')[0]) && ($action == 'view' || $action == 'edit')) {
                $id = $this->request->getParam('pass')[0];
            } else if ($checkStudentId) {
                try {
                    $id = $this->paramsDecode($this->request->getQuery['queryString'])['security_user_id'];
                } catch (\Exception $exception) {
                    $id = null;
                }
                if (!$id) {
                    //$id = $session->read('Student.Students.id');
                    $id = $checkStudentId;
                }
            }
            if ($this->StudentUser->exists([$this->StudentUser->getPrimaryKey() => $id])) {

                $entity = $this->StudentUser->get($id);
                $queryString = $this->getQueryString();
                $name = $entity->name;
                $header = $action == 'Assessments' ? $name . ' - ' . __('Assessments') : $name . ' - ' . __('Overview');
                $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentUser', 'view', $this->ControllerAction->paramsEncode(['id' => $id, 'institution_id' => $institutionID, 'student_id' => $entity->id, 'institution_student_id' => $queryString['institution_student_id']])]);
            } else {
                $indexPage = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index'];
                return $this->redirect($indexPage);
            }
        }

        $this->set('contentHeader', $header);
    }

    /**
     * @return bool
     */

    public
    function isStudentIDSkipped(): bool
    {
        $request = $this->request;
        $pass = $request->getParam('pass');
        $action = $request->getParam('action');
        $controller = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $furtherAction = $pass[0];
//        Log::debug(print_r([$pass, $action, $controller, $plugin, $furtherAction], true));
        // if (($furtherAction == 'index' || $furtherAction == 'add' || $furtherAction == 'import')
        //     && ($action == 'Students')
        //     && ($plugin == 'Student')
        //     && ($controller == 'Students')) {
        //     return true;
        // }
        if ($pass[0] == 'download' && ($action == 'Qualifications') && ($plugin == 'Student') && ($controller == 'Students')) {
            return true;
        }
        if ($furtherAction == 'image' || $furtherAction == 'download' || $furtherAction == 'ajaxReferrerAutocomplete') {
            return true;
        }
//        $this->log(print_r($request,true), debug);
        return false;
    }

    // public function getUserTabElements($options = []) {
    //  $plugin = $this->plugin;
    //  $name = $this->name;

    //  $id = (isset($options['id']))? $options['id']: $this->request->session()->read($name.'.id');

    //  $tabElements = [
    //      $this->name => [
    //          'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'view', $id],
    //          'text' => __('Details')
    //      ],
    //      'Accounts' => [
    //          'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Accounts', 'view', $id],
    //          'text' => __('Account')
    //      ]
    //  ];

    //  return $tabElements;
    // }

    //Related getGuardianTabElements function in GuardiansController
//    public function getGuardianTabElements($options = [])
//    {
//        if (isset($options['userRole']) && $options['userRole'] == 'Guardians' && isset($options['entity'])) {
//            $session = $this->request->getSession();
//            $session->write('Guardian.Guardians.name', $options['entity']->user->name);
//            $session->write('Guardian.Guardians.id', $options['entity']->user->id);
//        }
//
//        $session = $this->request->getSession();
//        $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
//        $guardianId = $session->read('Guardian.Guardians.id');
//        if (!empty($guardianId)) {
//            $id = $guardianId;
//        }
//
//        $tabElements = [
//            'Guardians' => ['text' => __('Relation')],
//            'GuardianUser' => ['text' => __('Overview')],
//            'Accounts' => ['text' => __('Account')],
//            'Demographic' => ['text' => __('Demographic')],
//            'Identities' => ['text' => __('Identities')],
//            'UserNationalities' => ['text' => __('Nationalities')], //UserNationalities is following the filename(alias) to maintain "selectedAction" select tab accordingly.
//            'Contacts' => ['text' => __('Contacts')],
//            'Languages' => ['text' => __('Languages')],
//            'Attachments' => ['text' => __('Attachments')],
//            'Comments' => ['text' => __('Comments')]
//        ];
//
//        foreach ($tabElements as $key => $value) {
//            if ($key == 'Guardians') {
//                $tabElements[$key]['url'] = ['plugin' => 'Student',
//                    'controller' => 'Students',
//                    'action' => 'Guardians',
//                    'view',
//                    $this->paramsEncode(['id' => $StudentGuardianId])
//                ];
//            } elseif ($key == 'GuardianUser') {
//                $tabElements[$key]['url'] = ['plugin' => 'Student',
//                    'controller' => 'Students',
//                    'action' => 'GuardianUser',
//                    'view',
//                    $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])
//                ];
//            } elseif ($key == 'Accounts') {
//                $tabElements[$key]['url']['plugin'] = 'Guardian';
//                $tabElements[$key]['url']['controller'] = 'Guardians';
//                $tabElements[$key]['url']['action'] = 'Accounts';
//                $tabElements[$key]['url'][] = 'view';
//                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
//            } else if ($key == 'Comments') {
//                $url = [
//                    'plugin' => 'Guardian',
//                    'controller' => 'GuardianComments',
//                    'action' => 'index'
//                ];
//                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
//            } elseif ($key == 'UserNationalities') {
//                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString(
//                    [
//                        'plugin' => 'Guardian',
//                        'controller' => 'Guardians',
//                        'action' => 'Nationalities',
//                        'index'
//                    ],
//                    ['security_user_id' => $id]
//                );
//            } else {
//                $actionURL = $key;
//                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString(
//                    [
//                        'plugin' => 'Guardian',
//                        'controller' => 'Guardians',
//                        'action' => $actionURL,
//                        'index'
//                    ],
//                    ['security_user_id' => $id]
//                );
//            }
//        };
//
//        return $this->TabPermission->checkTabPermission($tabElements);
//    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {

        $isInstitutionIndex = $this->isStudentIDSkipped();
        if ($isInstitutionIndex) {
            return;
        }
        /**
         * if student object is null, it means that students.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
         */
//        $this->log($model, 'debug');
        $session = $this->request->getSession();
        $studentID = $this->getStudentID();
        /*if($studentID == null){
            $studentID =  $this->getUserID();
        }*/

        $institutionID = $this->getInstitutionID();


        if ($studentID) { // POCOR-9061
            if ($this->StudentUser->exists([$this->StudentUser->getPrimaryKey() => $studentID])) {
                $entity = $this->StudentUser->get($studentID);
                $name = $entity->name;
            }
            $header = '';
            //$userId = $session->read('Student.Students.id');
            // POCOR-8014-n
            try {
                $userId = $this->getStudentID();
                if($userId == null){
                 $userId  = $this->getUserID();
                }
                $session->write('Student.Students.id', $userId);
                $student = $this->StudentUser->get($userId);
                $session->write('Student.Students.name', $student->name);
            } catch (\Exception $exception) {
                $userId = null;
            }
            if (!$userId) {
                $userId = $this->getStudentID();
            }

            $alias = $model->getAlias();
            if (!$this->AccessControl->isAdmin()) {
                $institutionIds = $session->read('AccessControl.Institutions.ids');
                $studentId = $this->getStudentID();
                $enrolledStatus = false;
                $InstitutionStudentsTable = TableRegistry::get('Institution.Students');
                 foreach ($institutionIds as $id) {
                    $enrolledStatus = $InstitutionStudentsTable->checkEnrolledInInstitution($studentId, $id);
                    if ($enrolledStatus) {
                        break;
                    }
                }
                $enrolledStatus = $InstitutionStudentsTable->checkEnrolledInInstitution($studentID, $institutionID);
                if (!$enrolledStatus) {
                    if ($alias != 'BankAccounts' && $alias != 'StudentFees') {
                        $this->ControllerAction->removeDefaultActions(['add', 'edit', 'remove']);
                    }

                }
            }

            // POCOR-3983 to disable add/edit/remove action on the model when institution status is inactive
            $this->getStatusPermission($model);

            $header = $name;
            if ($alias == 'ImportStudents') {
                $this->Navigation->addCrumb($model->getHeader($alias));
                $header = __('Students') . ' - ' . $model->getHeader($alias);
                $this->set('contentHeader', $header);
            }
            $primaryKey = $model->getPrimaryKey();

            //POCOR-5890 starts
            if ($model->getHeader($alias) == 'HealthImmunizations') {
                $alias = __('Vaccinations');
            }

            if($alias == 'StudentGpa' || $alias == 'Gpa'){
                $alias = 'Student GPA';
                $alias = $model->getHeader($alias);
                $alias = preg_replace('/G\s*P\s*A/', 'GPA', $alias);
                $this->Navigation->addCrumb($alias);
                $header = $header . ' - ' . $alias;

            }else{
                $this->Navigation->addCrumb($model->getHeader($alias));
                $header = $header . ' - ' . $model->getHeader($alias);
            }


            // $params = $this->request->params;
            $this->set('contentHeader', $header);

            if ($model->hasField('security_user_id')) {
                $model->fields['security_user_id']['type'] = 'hidden';
                $model->fields['security_user_id']['value'] = $studentID;
            }
            if ($model->hasField('student_id')) {
                $model->fields['student_id']['type'] = 'hidden';
                $model->fields['student_id']['value'] = $studentID;
            }
            if ($model->hasField('staff_id')) {
                $model->fields['staff_id']['type'] = 'hidden';
                $model->fields['staff_id']['value'] = $studentID;
            }
            //}

        }
    }

    public
    function getStatusPermission($model)
    {
        $institutionId = $this->getInstitutionID();
        $Institutions = TableRegistry::get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);

        // institution status is INACTIVE
        if (!$isActive) {
            if (in_array($model->getAlias(), $this->features)) { // check the feature list
                if ($model instanceof \App\Model\Table\ControllerActionTable) {
                    // CAv4 off the add/edit/remove action
                    $model->toggle('add', false);
                    $model->toggle('edit', false);
                    $model->toggle('remove', false);
                } else if ($model instanceof \App\Model\Table\AppTable) {
                    // CAv3 hide button and redirect when user change the Url
                    $model->addBehavior('ControllerAction.HideButton');
                }
            }
        }
    }

    public
    function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public
    function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->getSession();
        // POCOR-8014-n
        try {
            $userId = $this->paramsDecode($this->request->getQuery['queryString'])['security_user_id'];
        } catch (\Exception $exception) {
            $userId = null;
        }
        if (!$userId) {
            //$userId = $session->read('Student.Students.id');
            $queryString = $this->getQueryString();
            $userId = $queryString['student_id'];
        }
        if ($model->getAlias() != 'Students') {
            if ($session->check('Student.Students.id')) {
                if ($model->hasField('security_user_id')) {
                    $query->where([$model->aliasField('security_user_id') => $userId]);
                } else if ($model->hasField('student_id')) {
                    $query->where([$model->aliasField('student_id') => $userId]);
                } else if (($model->getAlias() == "StudentCompetencies") && ($model->hasField('staff_id'))) { //POCOR-7966
                } else if ($model->hasField('staff_id')) {
                    $query->where([$model->aliasField('staff_id') => $userId]);
                }
            }
        }
    }

    public  function excel($id = 0)
    {
        $this->Students->excel($id);
        $this->autoRender = false;
    }

    public
    function getFinanceTabElements($options = [])
    {
        $tabElements = [];
        $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
        $studentTabElements = [
            'BankAccounts' => ['text' => __('Bank Accounts')],
            'StudentFees' => ['text' => __('Fees')],
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);
        $queryString = $this->getQueryString();
        $userID = $this->getStudentID();
        $queryString['user_id'] = $userID;
        $queryString['id'] = $userID;
        $queryString = $this->paramsEncode($queryString);
        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
            $tabElements[$key]['url'][] = $queryString;
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    // public function getAssesmentTabElements($options = [])
    // {
    //     $queryString = $this->request->query('queryString');
    //     $tabElements = [
    //         'Competencies' => [
    //             'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentAssesments', 'view', 'queryString' => $queryString],
    //             'text' => __('Items')
    //         ]
    //     ];
    //     return $this->TabPermission->checkTabPermission($tabElements);
    // }

    public
    function getImage($id)
    {
        $this->autoRender = false;
        $this->ControllerAction->autoRender = false;
        $this->Image->getUserImage($id);
    }

    /*POCOR-6700 start - registering function*/

    public
    function getStudentGuardianTabElements($options = [])
    {
        $type = (isset($options['type'])) ? $options['type'] : null;
        $plugin = $this->getPlugin();
        $name = $this->getName();
        $tabElements = [
            'Guardians' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Guardians', 'type' => $type],
                'text' => __('Guardians')
            ],
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    /*POCOR-6700 ends*/

    //POCOR-6673

    public
    function getCompetencyTabElements($options = [])
    {
        $queryString = $this->request->getQuery('queryString');
        $tabElements = [
            'Competencies' => [
                'url' => ['plugin' => 'Student', 'controller' => 'Students', 'action' => 'StudentCompetencies', 'view', 'queryString' => $queryString],
                'text' => __('Items')
            ]
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    /*private function getInstitutionID()
    {
        $session = $this->request->getSession();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = !is_null(($this->request->getParam('institutionId'))) ?
            $this->request->getParam('institutionId') :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }*/

    public
    function StudentScheduleTimetable()
    {
        $session = $this->request->getSession();
        $studentID = $this->getStudentID();

        if ($studentID) {
            $userId = $studentID;
        } else {
            $userId = $this->Auth->user('id');
        }

        $InstitutionStudents =
            TableRegistry::get('Institution.InstitutionStudents')
                ->find()
                ->where([
                    'InstitutionStudents.student_id' => $userId
                ])
                ->enableHydration(false)
                ->first();

        $institutionId = $InstitutionStudents['institution_id'];
        $academicPeriodId = TableRegistry::get('AcademicPeriod.AcademicPeriods')
            ->getCurrent();

        $InstitutionClassStudentsResult =
            TableRegistry::get('Institution.InstitutionClassStudents')
                ->find()
                ->where([
                    'academic_period_id' => $academicPeriodId,
                    'student_id' => $userId,
                    'institution_id' => $institutionId
                ])
                ->enableHydration(false)
                ->first();

        $institutionClassId = (!empty($InstitutionClassStudentsResult)) ? $InstitutionClassStudentsResult['institution_class_id'] : 0;

        $ScheduleTimetables = TableRegistry::get('Schedule.ScheduleTimetables')
            ->find()
            ->where([
                'academic_period_id' => $academicPeriodId,
                'institution_class_id' => $institutionClassId,
                'institution_id' => $institutionId,
                'status' => 2
            ])
            ->enableHydration(false)
            ->first();

        $this->set('userId', $userId);
        $timetable_id = (isset($ScheduleTimetables['id'])) ? $ScheduleTimetables['id'] : 0;
        $this->set('timetable_id', $timetable_id);
        $this->set('academicPeriodId', $academicPeriodId);
        $this->set('institutionDefaultId', $institutionId);
        $this->set('ngController', 'StudentTimetableCtrl as $ctrl');

        // Start POCOR-5188
        $manualTable = TableRegistry::get('Manuals');
        $ManualContent = $manualTable->find()->select(['url'])->where([
            $manualTable->aliasField('function') => 'Students',
            $manualTable->aliasField('module') => 'Institutions',
            $manualTable->aliasField('category') => 'Timetable',
        ])->first();

        if (!empty($ManualContent['url'])) {
            $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
        } else {
            $this->set('is_manual_exist', []);
        }
        // End POCOR-5188

    }

    public function Extracurriculars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Extracurriculars']);

        //POCOR-8795 start
        $session = $this->request->getSession();
        $academicPeriodId = $this->request->getQuery('academic_period_id');
        $studentId = $session->read('Student.Students.id');
        $isGuardian = $session->read('Auth.User.is_guardian');
        $userData = $this->Session->read();
        $id = null;
        if (isset($this->request->getAttribute('params')['pass'][1])) {
            $id = $this->ControllerAction->paramsDecode($this->request->getAttribute('params')['pass'][1])['id'];
        }
        if ($this->controller->getName() == 'Profiles') {
            if ($isGuardian) {
                $sId = $session->read('Student.ExaminationResults.student_id');
                $studentId = (is_int($sId) && $sId)
                ? $sId
                : ($sId ? $this->ControllerAction->paramsDecode($sId)['id'] : ($studentId ?: $userData['Auth']['User']['id']));
            } else {
                $studentId = $session->read('Auth.User.id');
            }
        }

        $options = [
            'academic_period_id' => $academicPeriodId,
            'student_id' => $studentId,
            'id' => $id
        ];

        $this->set('extracurricularOptions', $options);
         //POCOR-8795 end
    }


    public
    function StudentCurriculars()
    {
        /*// tabs
            $options = ['type' => 'student'];
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'Curriculars');
            // End*/
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentCurriculars']);
    }

    public
    function HealthBodyMasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.BodyMasses']);
    }

    public
    function HealthInsurances()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Insurances']);
    }

    public
    function Comments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Comments']);
    }

    //POCOR-8596
    public
    function Behaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentBehaviours']);
    }

    public function StudentGpa()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentGpa']);
    }

}
