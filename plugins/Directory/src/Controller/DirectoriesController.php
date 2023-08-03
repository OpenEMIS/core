<?php
namespace Directory\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use App\Controller\AppController;
use Cake\Network\Response;
use Cake\Http\Client;

class DirectoriesController extends AppController
{
    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->models = [
            // Users
            'Accounts'              => ['className' => 'Directory.Accounts', 'actions' => ['view', 'edit']],

            // Student
            //'StudentAbsences'       => ['className' => 'Directory.Absences', 'actions' => ['index', 'view']],
            //'StudentAbsences'       => ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
            'StudentBehaviours'     => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
            //'StudentExtracurriculars' => ['className' => 'Student.Extracurriculars'],

            // Staff
            'StaffPositions'        => ['className' => 'Staff.Positions', 'actions' => ['index', 'view']],
            'StaffSections'             => ['className' => 'Staff.StaffSections', 'actions' => ['index', 'view']],
            'StaffClasses'          => ['className' => 'Staff.StaffClasses', 'actions' => ['index', 'view']],
            'StaffQualifications'   => ['className' => 'Staff.Qualifications'],
            'StaffExtracurriculars'     => ['className' => 'Staff.Extracurriculars'],
            'StaffDuties'           => ['className' => 'Institution.StaffDuties', 'actions' => ['index', 'view']],
            'TrainingResults'       => ['className' => 'Staff.TrainingResults', 'actions' => ['index', 'view']],

            'ImportUsers'           => ['className' => 'Directory.ImportUsers', 'actions' => ['add']],
            'ImportSalaries'        => ['className' => 'Staff.ImportSalaries', 'actions' => ['add']]
        ];

        $this->loadComponent('Training.Training');
        $this->loadComponent('Configuration.Configuration');
        $this->loadComponent('User.Image');
        $this->loadComponent('Institution.CreateUsers');
        $this->loadModel('FieldOption.Nationalities');
        $this->loadModel('Directory.Directories');
        $this->loadModel('Directory.AreaAdministratives');
        $this->attachAngularModules();
        $this->attachAngularModulesForDirectory();
        //POCOR-5672 it is used for removing csrf token mismatch condition in directory external search 
        if ($this->request->action == 'directoryExternalSearch') {
            $this->eventManager()->off($this->Csrf);
        }//POCOR-5672 ends

        $this->set('contentHeader', 'Directories');
    }

    public function Directories()
    {
        $action = $this->request->pass[0];
        if($action == 'add'){
            $this->attachAngularModulesForDirectory();
            $this->set('ngController', 'DirectoryAddCtrl as $ctrl');
        }else{
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.Directories']);
        }
    }

    // CAv4
    public function StudentFees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentFees']);
    }
    public function StaffEmployments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Employments']);
    }
    public function StaffQualifications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Qualifications']);
    }
    public function StaffPositions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Positions']);
    }
    public function StaffClasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffClasses']);
    }
    public function StaffSubjects()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffSubjects']);
    }
    public function StaffEmploymentStatuses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.EmploymentStatuses']);
    }
    public function StaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Leave']);
    }
    public function StudentClasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentClasses']);
    }
    public function StudentSubjects()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentSubjects']);
    }
    public function Nationalities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserNationalities']);
    }
    public function Languages()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserLanguages']);
    }
    public function StaffMemberships()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Memberships']);
    }
    public function StaffLicenses()//POCOR-7528
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Licenses']);
    }
    public function StudentLicenses()//POCOR-7528
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Licenses']);
    }
    public function Contacts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Contacts']);
    }
    public function StudentBankAccounts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.BankAccounts']);
    }
    public function StaffBankAccounts()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.BankAccounts']);
    }
    public function StudentProgrammes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Programmes']);
    }
    public function Identities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Identities']);
    }
    public function Demographic()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Demographic']);
    }
    public function StudentAwards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']);
    }
    public function StaffAwards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']);
    }
    public function TrainingNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.TrainingNeeds']);
    }
    public function StaffAppraisals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.Appraisals']);
    }
    public function StaffDuties()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Duties']);
    }
    public function StaffAssociations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.InstitutionAssociationStaff']);
    }
    public function StudentTextbooks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Textbooks']);
    }
    public function StudentGuardians()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Guardians']);
    }
    public function StudentGuardianUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.GuardianUser']);
    }
    public function GuardianStudents()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Guardian.Students']);
    }
    public function GuardianStudentUser()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Guardian.StudentUser']);
    }
    public function StudentReportCards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentReportCards']);
    }
    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']);
    }
    public function Courses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffTrainings']);
    }
    public function StaffSalaries()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Salaries']);
    }
    public function StaffPayslips()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Payslips']);
    }
    public function StaffBehaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffBehaviours']);
    }
    public function StudentOutcomes()
    {
        $comment = $this->request->query['comment'];
        if(!empty($comment) && $comment == 1){ 
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomeComments']);
        
        }else{
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomes']);
        } 
        
    }
    public function StudentRisks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentRisks']);
    }

     public function StudentAssociations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.InstitutionAssociationStudent']);
    }
    // health
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
    // Special Needs - End
    //POCOR-7366 start
    public function Counsellings()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.Counsellings']);
    }
    //POCOR-7366 end
    public function Employments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserEmployments']);
    }

    // Historical Data - End
    public function HistoricalStaffPositions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Historical.HistoricalStaffPositions']);
    }
    public function HistoricalStaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Historical.HistoricalStaffLeave']);
    }
    public function Addguardian()
    {
        //POCOR-7231 :: Start
        $requestDataa = base64_decode($this->request->query('queryString'));
        $requestDataa = json_decode($requestDataa, true);
        $UsersTable = TableRegistry::get('User.Users');
        $InstitutionTable = TableRegistry::get('Institution.Institutions');
        $UserData = $UsersTable->find('all',['conditions'=>['id'=>$requestDataa['student_id']]])->first();
        $InstitutionData = $InstitutionTable->find('all',['conditions'=>['id'=>$requestDataa['institution_id']]])->first();
        $queryStng = $this->paramsEncode(['id' => $UserData->id]);
        $this->set('InstitutionData', $InstitutionData);
        $this->set('UserData', $UserData);
        $this->set('queryStng', $queryStng);//POCOR-7231 :: END
        $this->attachAngularModulesForDirectory();
        $this->set('ngController', 'DirectoryaddguardianCtrl as $ctrl');
    }
    // End

    // AngularJS
    public function StudentResults()
    {
        $session = $this->request->session();

        if ($session->check('Directory.Directories.id')) {
            $studentId = $session->read('Directory.Directories.id');
            $session->write('Student.Results.student_id', $studentId);

            // tabs
            $options['type'] = 'student';
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'Results');
            // End

            $this->set('ngController', 'StudentResultsCtrl as StudentResultsController');

            // Start POCOR-5188
            $manualTable = TableRegistry::get('Manuals');
            $ManualContent =   $manualTable->find()->select(['url'])->where([
                    $manualTable->aliasField('function') => 'Assessments',
                    $manualTable->aliasField('module') => 'Directory',
                    $manualTable->aliasField('category') => 'Students - Academic',
                    ])->first();
            
            if (!empty($ManualContent['url'])) {
                $this->set('is_manual_exist', ['status'=>'success', 'url'=>$ManualContent['url']]);
            }else{
                $this->set('is_manual_exist', []);
            }
            // End POCOR-5188
        }
    }

    public function StudentExaminationResults()
    {
        $session = $this->request->session();

        if ($session->check('Directory.Directories.id')) {
            $studentId = $session->read('Directory.Directories.id');
            $session->write('Student.ExaminationResults.student_id', $studentId);

            // tabs
            $options['type'] = 'student';
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'ExaminationResults');
            // End

            $this->set('ngController', 'StudentExaminationResultsCtrl as StudentExaminationResultsController');

            // Start POCOR-5188
            $manualTable = TableRegistry::get('Manuals');
            $ManualContent =   $manualTable->find()->select(['url'])->where([
                    $manualTable->aliasField('function') => 'Examinations',
                    $manualTable->aliasField('module') => 'Directory',
                    $manualTable->aliasField('category') => 'Students - Academic',
                    ])->first();
            
            if (!empty($ManualContent['url'])) {
                $this->set('is_manual_exist', ['status'=>'success', 'url'=>$ManualContent['url']]);
            }else{
                $this->set('is_manual_exist', []);
            }
            // End POCOR-5188
        }
    }

    public function StaffAttendances()
    {
        if (!empty($this->request->param('institutionId'))) {
            $institutionId = $this->ControllerAction->paramsDecode($this->request->param('institutionId'))['id'];
        } else {
            $session = $this->request->session();
            $staffId = $session->read('Staff.Staff.id');
            $institutionId = $session->read('Institution.Institutions.id');
        }
        $tabElements = $this->getCareerTabElements();

        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->param('action'))));
        $this->Navigation->addCrumb($crumbTitle);
        $this->set('institution_id', $institutionId);
        $this->set('staff_id', $staffId);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', 'Attendances');
        $this->set('ngController', 'StaffAttendancesCtrl as $ctrl');

        // Start POCOR-5188
        $manualTable = TableRegistry::get('Manuals');
        $ManualContent =   $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Attendances',
                $manualTable->aliasField('module') => 'Directory',
                $manualTable->aliasField('category') => 'Staff - Career',
                ])->first();
        
        if (!empty($ManualContent['url'])) {
            $this->set('is_manual_exist', ['status'=>'success', 'url'=>$ManualContent['url']]);
        }else{
            $this->set('is_manual_exist', []);
        }
        // End POCOR-5188
    }

    // End

    private function attachAngularModules()
    {
        $action = $this->request->action;

        switch ($action) {
            case 'StudentResults':
                $this->Angular->addModules([
                    'alert.svc',
                    'student.results.ctrl',
                    'student.results.svc'
                ]);
                break;
            case 'StudentExaminationResults':
                $this->Angular->addModules([
                    'alert.svc',
                    'student.examination_results.ctrl',
                    'student.examination_results.svc'
                ]);
                break;
            case 'StaffAttendances':
                $this->Angular->addModules([
                    'staff.attendances.ctrl',
                    'staff.attendances.svc'
                ]);
                break;
        }
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

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Directory', ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories']);
        $header = __('Directory');
        $session = $this->request->session();
        $action = $this->request->params['action'];

        $query = $this->request->query;
        // pass user id from request query and set to session
        if (array_key_exists('user_id', $query)) {
            $userId = $query['user_id'];
            $Directories = TableRegistry::get('Directory.Directories');
            $entity = $Directories->get($userId);

            $session->write('Directory.Directories.id', $entity->id);
            $session->write('Directory.Directories.name', $entity->name);

            $isStudent = $entity->is_student;
            $isStaff = $entity->is_staff;
            $isGuardian = $entity->is_guardian;

            $session->delete('Directory.Directories.is_student');
            $session->delete('Directory.Directories.is_staff');
            $session->delete('Directory.Directories.is_guardian');
            if ($isStudent) {
                $session->write('Directory.Directories.is_student', true);
                $session->write('Student.Students.id', $entity->id);
                $session->write('Student.Students.name', $entity->name);
            }

            if ($isStaff) {
                $session->write('Directory.Directories.is_staff', true);
                $session->write('Staff.Staff.id', $entity->id);
                $session->write('Staff.Staff.name', $entity->name);
            }
        }

        if ($action == 'Directories' && (empty($this->ControllerAction->paramsPass()) || $this->ControllerAction->paramsPass()[0] == 'index')) {
            $session->delete('Directory.Directories.id');
            $session->delete('Staff.Staff.id');
            $session->delete('Staff.Staff.name');
            $session->delete('Student.Students.id');
            $session->delete('Student.Students.name');
            $session->delete('Guardian.Guardians.id');
            $session->delete('Guardian.Guardians.name');
        } else if ($session->check('Directory.Directories.id') || $action == 'view' || $action == 'edit' || $action == 'StudentResults') {
            $id = 0;
            if (isset($this->request->pass[0]) && ($action == 'view' || $action == 'edit')) {
                $id = $this->ControllerAction->paramsDecode($this->request->pass[0])['id'];
            } else if ($session->check('Directory.Directories.id')) {
                $id = $session->read('Directory.Directories.id');
            }
            if (!empty($id)) {
                $entity = $this->Directories->get($id);
                $name = $entity->name;
                $header = $action == 'StudentResults' ? $name . ' - ' . __('Assessments') : $name . ' - ' . __('Overview');
                $this->Navigation->addCrumb($name, ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
            }
        }
        $paramPass = $this->ControllerAction->paramsPass();
        if ($action == 'StudentGuardians' && empty($paramPass)) {
            $session->delete('Directory.Directories.guardianToStudent');
        }
        if ($action == 'GuardianStudents' && empty($paramPass)) {
            $session->delete('Directory.Directories.studentToGuardian');
        }
        if (($action == 'Directories') && ($this->ControllerAction->paramsPass()[0] == 'view') || empty($this->ControllerAction->paramsPass())) {
            $session->delete('Directory.Directories.guardianToStudent');
            $session->delete('Directory.Directories.studentToGuardian');
        }

        $this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        if ($model instanceof \Staff\Model\Table\StaffClassesTable || $model instanceof \Staff\Model\Table\StaffSubjectsTable) {
            $model->toggle('add', false);
        } else if ($model->alias() == 'Guardians') {
            $model->editButtonAction('StudentGuardianUser');
        }

        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $alias = $model->alias();
            $includedModel = ['Leave'];

            if (in_array($alias, $includedModel)) {
                $model->toggle('add', false);
                $model->toggle('edit', false);
                $model->toggle('remove', false);
            }
        }

        /**
         * if student object is null, it means that students.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
         */
        $session = $this->request->session();
        if ($session->check('Directory.Directories.id')) {
            $header = '';
            $userId = $session->read('Directory.Directories.id');

            if ($session->check('Directory.Directories.name')) {
                $header = $session->read('Directory.Directories.name');
            }

            $alias = $model->alias;
            //POCOR-5890 starts
            if($alias == 'HealthImmunizations'){
                $alias = __('Vaccinations');     
            }
            //POCOR-5890 ends
            $guardianId = $session->read('Guardian.Guardians.id');
            $studentId = $session->read('Student.Students.id');
            $isStudent = $session->read('Directory.Directories.is_student');
            $isGuardian = $session->read('Directory.Directories.is_guardian');
            $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
            $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');

            if ($alias !== 'StudentGuardians' && $alias !== 'StudentGuardianUser' && $alias !== 'Directories' && !empty($studentToGuardian)) {
                $this->Navigation->addCrumb($model->getHeader('Guardian'. $alias));
                $header = $session->read('Guardian.Guardians.name');
                $header = $header . ' - ' . $model->getHeader($alias);
            } elseif ($alias !== 'GuardianStudents' && $alias !== 'GuardianStudentUser' && $alias !== 'Directories' && !empty($guardianToStudent)) {
                $this->Navigation->addCrumb($model->getHeader('Student'. $alias));
                $header = $session->read('Student.Students.name');
                $header = $header . ' - ' . $model->getHeader($alias);
            }elseif ($alias == 'StudentAssociations') {
                $header .= ' - '. __('Associations');
            } 
             else {
                $this->Navigation->addCrumb($model->getHeader($alias));
                $header = $header . ' - ' . $model->getHeader($alias);
            }

            $this->set('contentHeader', $header);

            if (!empty($guardianId) && !empty($isStudent) && !empty($studentToGuardian)) {
                   $action = $this->request->params['action'];
                        $paramPass = $this->ControllerAction->paramsPass();
                        if ($action == 'StudentGuardians' && !empty($paramPass)) {
                            $userId = $guardianId;
                        }
                        if (!empty($studentToGuardian)) {
                            $userId = $guardianId;
                        }

            } elseif (!empty($studentId) && !empty($isGuardian) && !empty($guardianToStudent)) {
                $userId = $studentId;
            }

            if ($model->hasField('security_user_id')) {
                $model->fields['security_user_id']['type'] = 'hidden';
                $model->fields['security_user_id']['value'] = $userId;

                if (count($this->request->pass) > 1) {
                    $modelId = $this->request->pass[1]; // id of the sub model
                    $ids = $this->ControllerAction->paramsDecode($modelId);
                    $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                    $idKey[$model->aliasField('security_user_id')] = $userId;
                    $exists = $model->exists($idKey);

                    /**
                     * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                     */
                    if (!$exists) {
                        $this->Alert->warning('general.notExists');
                        return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => $alias]);
                    }
                }
            } else if ($model->hasField('staff_id')) {
                $model->fields['staff_id']['type'] = 'hidden';
                $model->fields['staff_id']['value'] = $userId;

                if (count($this->request->pass) > 1) {
                    $modelId = $this->request->pass[1]; // id of the sub model

                    $ids = $this->ControllerAction->paramsDecode($modelId);
                    $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                    $idKey[$model->aliasField('staff_id')] = $userId;
                    $exists = $model->exists($idKey);

                    /**
                     * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                     */
                    if (!$exists) {
                        $this->Alert->warning('general.notExists');
                        return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => $alias]);
                    }
                }
            } else if ($model->hasField('student_id')) {
                $model->fields['student_id']['type'] = 'hidden';
                $model->fields['student_id']['value'] = $userId;

                if (count($this->request->pass) > 1) {
                    $modelId = $this->request->pass[1]; // id of the sub model

                    $ids = $this->ControllerAction->paramsDecode($modelId);
                    $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                    $idKey[$model->aliasField('student_id')] = $userId;
                    $exists = $model->exists($idKey);
                    $primaryKey = $model->primaryKey();
                    $params = [];
                    if (is_array($primaryKey)) {
                        foreach ($primaryKey as $key) {
                            $params[$model->aliasField($key)] = $ids[$key];
                        }
                    } else {
                        $params[$primaryKey] = $ids[$primaryKey];
                    }

                    $exists = false;

                    if (in_array($model->alias(), ['Guardians', 'StudentReportCards','Counsellings'])) {//POCOR-7366
                        $params[$model->aliasField('student_id')] = $session->read('Directory.Directories.id');
                        $exists = $model->exists($params);
                    } elseif (in_array($model->alias(), ['Students'])) {
                        $params[$model->aliasField('guardian_id')] = $session->read('Directory.Directories.id');
                        $exists = $model->exists($params);                        
                    }
                    /**
                     * if the sub model's id does not belongs to the main model through relation, redirect to sub model index page
                     */
                    if (!$exists) {
                        $this->Alert->warning('general.notExists');
                        return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => $alias]);
                    }
                }
            }
        } else {
            if ($model->alias() == 'ImportUsers') {
                $this->Navigation->addCrumb($model->getHeader($model->alias()));
                $header = __('Users') . ' - ' . $model->getHeader($model->alias());
                $this->set('contentHeader', $header);
            } else if ($model->alias() != 'Directories') {
                $this->Alert->warning('general.notExists');
                $event->stopPropagation();
                return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'index']);
            }
        }
    }

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->session();
        if ($model->alias() != 'Directories') {
            if ($session->check('Directory.Directories.id')) {
                $userId = $session->read('Directory.Directories.id');
                $guardianId = $session->read('Guardian.Guardians.id');
                $studentId = $session->read('Student.Students.id');
                $isGuardian = $session->read('Directory.Directories.is_guardian');
                $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
                $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');

                if (!empty($studentToGuardian)) {
                    if ($model->hasField('security_user_id')) {
                        $query->where([$model->aliasField('security_user_id') => $guardianId]);
                    }
                    else if ($model->hasField('student_id')) {
                        $query->where([$model->aliasField('student_id') => $guardianId]);
                    }
                } elseif (!empty($guardianToStudent)) {
                    if ($model->hasField('security_user_id')) {
                        $query->where([$model->aliasField('security_user_id') => $studentId]);
                    }
                    else if ($model->hasField('student_id')) {
                        $query->where([$model->aliasField('student_id') => $studentId]);
                    }
                } else {
                    if ($model->hasField('security_user_id')) {
                        $query->where([$model->aliasField('security_user_id') => $userId]);
                    } else if ($model->hasField('student_id')) {
                        $query->where([$model->aliasField('student_id') => $userId]);
                    } else if ($model->hasField('staff_id')) {
                        $query->where([$model->aliasField('staff_id') => $userId]);
                    }
                }
            } else {
                $this->Alert->warning('general.noData');
                $event->stopPropagation();
                return $this->redirect(['action' => 'index']);
            }
        }
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public function excel($id = 0)
    {
        $this->Students->excel($id);
        $this->autoRender = false;
    }

    public function getImage($id)
    {
        $this->autoRender = false;
        $this->ControllerAction->autoRender = false;
        $this->Image->getUserImage($id);
    }


    public function getUserTabElements($options = [])
    {
        if (array_key_exists('queryString', $this->request->query)) { //to filter if the URL already contain querystring
            $id = $this->ControllerAction->getQueryString('security_user_id');
        }

        $plugin = $this->plugin;
        $name = $this->name;

        $id = (array_key_exists('id', $options))? $options['id']: $this->request->session()->read($plugin.'.'.$name.'.id');

        if (array_key_exists('userRole', $options) && $options['userRole'] == 'Guardians' && array_key_exists('entity', $options)) {
            $session = $this->request->session();
            $session->write('Guardian.Guardians.name', $options['entity']->user->name);
            $session->write('Guardian.Guardians.id', $options['entity']->user->id);
            $session->write('Directory.Directories.studentToGuardian', 'studentToGuardian');
        } elseif (array_key_exists('userRole', $options) && $options['userRole'] == 'Students' && array_key_exists('entity', $options)) {
            $session = $this->request->session();
            $session->write('Student.Students.name', $options['entity']->user->name);
            $session->write('Student.Students.id', $options['entity']->user->id);
            $session->write('Directory.Directories.guardianToStudent', 'guardianToStudent');
        }

        $tabElements = [
            $this->name => ['text' => __('Overview')],
            'Accounts' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['text' => __('Nationalities')], //UserNationalities is following the filename(alias) to maintain "selectedAction" select tab accordingly.
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')]
        ];

        foreach ($tabElements as $key => $value) {
            if ($key == $this->name) {
                $tabElements[$key]['url']['action'] = 'Directories';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
            } else if ($key == 'Accounts') {
                $tabElements[$key]['url']['action'] = 'Accounts';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $this->ControllerAction->paramsEncode(['id' => $id]);
            } else if ($key == 'Comments') {
                $url = [
                    'plugin' => $plugin,
                    'controller' => 'DirectoryComments',
                    'action' => 'index'
                ];
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
            } else {
                $actionURL = $key;
                if ($key == 'UserNationalities') {
                    $actionURL = 'Nationalities';
                }
                $tabElements[$key]['url'] = $this->ControllerAction->setQueryString([
                                                'plugin' => $plugin,
                                                'controller' => $name,
                                                'action' => $actionURL,
                                                'index'],
                                                ['security_user_id' => $id]
                                            );
            }
        }

        if (array_key_exists('userRole', $options) && $options['userRole'] == 'Guardians') {
            $session = $this->request->session();
            $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
            $relationTabElements = [
                'Guardians' => ['text' => __('Relation')],
                'GuardianUser' => ['text' => __('Overview')]
            ];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $relationTabElements['Guardians']['url'] = array_merge($url, ['action' => 'StudentGuardians', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $relationTabElements['GuardianUser']['url'] = array_merge($url, ['action' => 'StudentGuardianUser', 'view', $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($relationTabElements, $tabElements);
            unset($tabElements[$this->name]);
        } elseif (array_key_exists('userRole', $options) && $options['userRole'] == 'Students') {
            $session = $this->request->session();
            $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
            $relationTabElements = [
                'Students' => ['text' => __('Relation')],
                'StudentUser' => ['text' => __('Overview')]
            ];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $relationTabElements['Students']['url'] = array_merge($url, ['action' => 'GuardianStudents', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $relationTabElements['StudentUser']['url'] = array_merge($url, ['action' => 'GuardianStudentUser', 'view', $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($relationTabElements, $tabElements);
            unset($tabElements[$this->name]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getStudentGuardianTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $plugin = $this->plugin;
        $name = $this->name;
        $tabElements = [
            'Guardians' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentGuardians', 'type' => $type],
                'text' => __('Guardians')
            ],
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getGuardianStudentTabElements($options = [])
    {
        // $type = (array_key_exists('type', $options))? $options['type']: null;
        $plugin = $this->plugin;
        $name = $this->name;
        $tabElements = [
            'Students' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'GuardianStudents'],
                'text' => __('Students')
            ],
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }    

    public function getAcademicTabElements($options = [])
    {
        $id = (array_key_exists('id', $options))? $options['id']: 0;
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $tabElements = [];
        $studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
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
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Associations')]
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' =>'Student'.$key, 'index', 'type' => $type]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getFinanceTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $plugin = $this->plugin;
        $name = $this->name;
        $tabElements = [];
        $studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $studentTabElements = [
            'BankAccounts' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentBankAccounts', 'type' => $type],
                'text' => __('Bank Accounts')
            ],
            'StudentFees' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentFees', 'type' => $type],
                'text' => __('Fees')
            ],
        ];

        foreach ($studentTabElements as $key => $tab) {
            $studentTabElements[$key]['url'] = array_merge($studentTabElements[$key]['url'], ['type' => $type]);
        }
        return $this->TabPermission->checkTabPermission($studentTabElements);
    }

    // For staff
    public function getCareerTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $tabElements = [];
        $studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $studentTabElements = [
            'EmploymentStatuses' => ['text' => __('Statuses')],
            'Positions' => ['text' => __('Positions')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Leave' => ['text' => __('Leave')],
            'Attendances' => ['text' => __('Attendances')],
            'Behaviours' => ['text' => __('Behaviours')],
            'Appraisals' => ['text' => __('Appraisals')],
            'Duties' => ['text' => __('Duties')],
            'Associations' => ['text' => __('Associations')]
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Staff'.$key, 'type' => 'staff']);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }


    public function getProfessionalTabElements($options = [])
    {

        $session = $this->request->session();
        $isStudent = $session->read('Directory.Directories.is_student');
        $isStaff = $session->read('Directory.Directories.is_staff');

        $tabElements = [];
        $directoryUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $user=0;//POCOR-7528 
        if ($isStaff) {
            $user=1;//POCOR-7528 
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
                'Extracurriculars' => ['text' => __('Extracurriculars')],
                'Memberships' => ['text' => __('Memberships')],
                'Licenses' => ['text' => __('Licenses')],
                'Awards' => ['text' => __('Awards')],
            ];
        } else {
            $user=0;//POCOR-7528 
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Licenses' => ['text' => __('Licenses')],
            ];
        }
        $tabElements = array_merge($tabElements, $professionalTabElements);

        foreach ($professionalTabElements as $key => $tab) {
            //POCOR-7528 start
            if($key == 'Licenses'){
                if($user==1){
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' =>'Staff'.$key, 'index']);
                }
                else if($user==0){
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' =>'Student'.$key, 'index']);
                }
            }
            //POCOR-7528 end
            else if ($key != 'Employments') {
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' => 'Staff'.$key, 'index']);
            }
         
            else {
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' => $key, 'index']);
            }
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getStaffFinanceTabElements($options = [])
    {
        $type = (array_key_exists('type', $options))? $options['type']: null;
        $tabElements = [];
        $staffUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $staffTabElements = [
            'BankAccounts' => ['text' => __('Bank Accounts')],
            'Salaries' => ['text' => __('Salaries')],
            'Payslips' => ['text' => __('Payslips')],
        ];

        $tabElements = array_merge($tabElements, $staffTabElements);

        foreach ($staffTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => 'Staff'.$key, 'type' => $type]);
        }
       
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getTrainingTabElements($options = [])
    {
        $tabElements = [];
        $studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $studentTabElements = [
            'TrainingNeeds' => ['text' => __('Training Needs')],
            'TrainingResults' => ['text' => __('Training Results')],
            'Courses' => ['text' => __('Courses')],
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index']);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
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

    public function getNationalities()
    {
        $nationalities = TableRegistry::get('nationalities');
        $nationalities_result = $nationalities
            ->find()
            ->select(['id','name'])
            ->toArray();
        foreach($nationalities_result AS $result){
            $result_array[] = array("id" => $result['id'], "name"=> $result['name']);
        }
        echo json_encode($result_array);die;
    }

    public function getIdentityTypes()
    {
        $identity_types = TableRegistry::get('identity_types');
        $identity_types_result = $identity_types
            ->find()
            ->select(['id','name'])
            ->order(['order'])
            ->toArray();
        foreach($identity_types_result AS $result){
            $result_array[] = array("id" => $result['id'], "name"=> $result['name']);
        }
        echo json_encode($result_array);die;
    }

    public function getGenders()
    {
        $genders_types = TableRegistry::get('genders');
        $genders_types_result = $genders_types
            ->find()
            ->select(['id','name'])
            ->toArray();
        foreach($genders_types_result AS $result){
            $result_array[] = array("id" => $result['id'], "name"=> $result['name']);
        }
        echo json_encode($result_array);die;
    }

    public function getUserType()
    {
        $user_type_options = [
                self::STAFF => __('Staff'),
                self::STUDENT => __('Students'),
                self::GUARDIAN => __('Guardians'),
                self::OTHER => __('Others')
            ];
        foreach($user_type_options AS $key => $val){
            $result_array[] = array("id" => $key, "name"=> $val);
        }
        echo json_encode($result_array);die;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        //for api purpose POCOR-5672 starts
        if($this->request->params['action'] == 'directoryInternalSearch'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'directoryInternalSearch';
        }
        if($this->request->params['action'] == 'directoryExternalSearch'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'directoryExternalSearch';
        }
        if($this->request->params['action'] == 'getContactType'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getContactType';
        }
        if($this->request->params['action'] == 'getRedirectToGuardian'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getRedirectToGuardian';
        }
        if($this->request->params['action'] == 'getRelationshipType'){
           $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getRelationshipType';
        }//for api purpose POCOR-5672 ends
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        $pass = $this->request->pass;
        if (isset($pass[0]) && $pass[0] == 'downloadFile') {
            return true;
        }
    }

    public function directoryInternalSearch()
    {
        $Directories = TableRegistry::get('Directory.Directories');
        $this->autoRender = false;
        $requestData = $this->request->input('json_decode', true);
        $requestDataParams = $requestData['params'];
        $internalSearchResults = $Directories::getUserInternalSearch($requestDataParams);
//        Log::write('debug', $a);
//        $institutionId = (array_key_exists('institution_id', $requestDataParams))? $requestDataParams['institution_id']: null;
//        $userTypeId = (array_key_exists('user_type_id', $requestDataParams))? $requestDataParams['user_type_id']: null;
//        $firstName = (array_key_exists('first_name', $requestDataParams))? $requestDataParams['first_name']: null;
//        $lastName = (array_key_exists('last_name', $requestDataParams))? $requestDataParams['last_name']: null;
//        $openemisNo = (array_key_exists('openemis_no', $requestDataParams))? $requestDataParams['openemis_no']: null;
//        $identityNumber = (array_key_exists('identity_number', $requestDataParams))? $requestDataParams['identity_number']: null;
//        $dateOfBirth = (array_key_exists('date_of_birth', $requestDataParams))? $requestDataParams['date_of_birth']: null;
//        $identityTypeId = (array_key_exists('identity_type_id', $requestDataParams))? $requestDataParams['identity_type_id']: null;
//        $nationalityId = (array_key_exists('nationality_id', $requestDataParams))? $requestDataParams['nationality_id']: null;
//        $limit = (array_key_exists('limit', $requestDataParams)) ? $requestDataParams['limit']: 10;
//        $page = (array_key_exists('page', $requestDataParams)) ? $requestDataParams['page']: 1;
//        $get_user_id = (array_key_exists('id', $requestDataParams)) ? $requestDataParams['id']: null;
//
//        $conditions = [];
//        $security_users = TableRegistry::get('security_users');
//        $userIdentities = TableRegistry::get('user_identities');
//        $genders = TableRegistry::get('genders');
//        $mainIdentityTypes = TableRegistry::get('identity_types');
//        $mainNationalities = TableRegistry::get('nationalities');
//        $areaAdministratives = TableRegistry::get('area_administratives');
//        $birthAreaAdministratives = TableRegistry::get('area_administratives');
//
//        if (!empty($firstName)) {
//            $conditions[$security_users->aliasField('first_name').' LIKE'] = $firstName . '%';
//        }
//        if (!empty($lastName)) {
//            $conditions[$security_users->aliasField('last_name').' LIKE'] = $lastName . '%';
//        }
//        if (!empty($openemisNo)) {
//            $conditions[$security_users->aliasField('openemis_no').' LIKE'] = $openemisNo . '%';
//        }
//        if (!empty($dateOfBirth)) {
//            $conditions[$security_users->aliasField('date_of_birth')] = date_create($dateOfBirth)->format('Y-m-d');
//        }
//
//        if (!empty($userTypeId)) {
//            //POCOR-7192 comment user_type condition starts
//            /*if($userTypeId ==1){
//                $conditions[$security_users->aliasField('is_student')] = 1;
//            }else if($userTypeId ==2){
//                $conditions[$security_users->aliasField('is_staff')] = 1;
//            }else if($userTypeId ==3){
//                $conditions[$security_users->aliasField('is_guardian')] = 1;
//            }*///POCOR-7192 Ends
//        }
//
//        //it is user for getting single user data
//        if (!empty($get_user_id)) {
//            $conditions[$security_users->aliasField('id')] = $get_user_id;
//        }
//        $totalCount = 0;
//        if($identityNumber == ''){
//            $security_users_result = $security_users
//            ->find()
//            ->select([
//                $security_users->aliasField('id'),
//                $security_users->aliasField('username'),
//                $security_users->aliasField('password'),
//                $security_users->aliasField('openemis_no'),
//                $security_users->aliasField('first_name'),
//                $security_users->aliasField('middle_name'),
//                $security_users->aliasField('third_name'),
//                $security_users->aliasField('last_name'),
//                $security_users->aliasField('preferred_name'),
//                $security_users->aliasField('email'),
//                $security_users->aliasField('address'),
//                $security_users->aliasField('postal_code'),
//                $security_users->aliasField('date_of_death'),
//                $security_users->aliasField('external_reference'),
//                $security_users->aliasField('last_login'),
//                $security_users->aliasField('photo_name'),
//                $security_users->aliasField('photo_content'),
//                $security_users->aliasField('preferred_language'),
//                $security_users->aliasField('address_area_id'),
//                $security_users->aliasField('birthplace_area_id'),
//                $security_users->aliasField('gender_id'),
//                $security_users->aliasField('date_of_birth'),
//                $security_users->aliasField('nationality_id'),
//                $security_users->aliasField('identity_number'),
//                $security_users->aliasField('super_admin'),
//                $security_users->aliasField('status'),
//                $security_users->aliasField('is_student'),
//                $security_users->aliasField('is_staff'),
//                $security_users->aliasField('is_guardian'),
//                'Genders_id'=> $genders->aliasField('id'),
//                'Genders_name'=> $genders->aliasField('name'),
//                'MainIdentityTypes_id'=> $mainIdentityTypes->aliasField('id'),
//                'MainIdentityTypes_name'=> $mainIdentityTypes->aliasField('name'),
//                'MainNationalities_id'=> $mainNationalities->aliasField('id'),
//                'MainNationalities_name'=> $mainNationalities->aliasField('name'),
//                'area_name'=> $areaAdministratives->aliasField('name'),
//                'area_code'=> $areaAdministratives->aliasField('code'),
//                'birth_area_name'=> 'birthAreaAdministratives.name',
//                'birth_area_code'=> 'birthAreaAdministratives.code',
//                'MainIdentityTypes_number'=> $userIdentities->aliasField('number'),
//            ])
//            ->LeftJoin([$userIdentities->alias() => $userIdentities->table()],[
//                $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id')
//            ])
//            ->LeftJoin([$genders->alias() => $genders->table()], [
//                $genders->aliasField('id =') . $security_users->aliasField('gender_id')
//            ])
//            ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
//                $mainIdentityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')
//            ])
//            ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
//                $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
//            ])
//            ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table()], [
//                $areaAdministratives->aliasField('id =') . $security_users->aliasField('address_area_id')
//            ])
//            ->LeftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->table()], [
//                'birthAreaAdministratives.id =' . $security_users->aliasField('birthplace_area_id')
//            ])
//            ->where([$security_users->aliasField('super_admin').' <> ' => 1, $conditions])
//            ->group([$security_users->aliasField('id')])
//            ->limit($limit)
//            ->page($page)
//            ->toArray();
//
//            $totalCount = $this->getCountInernalSearch($conditions, $identityNumber);
//        }else{
//            //POCOR-5672 start new changes searching users by identity number
//            $userTypeCondition = [];
//            if (!empty($userTypeId)) {
//                //POCOR-7192 comment user_type condition starts
//                /*if($userTypeId ==1){
//                    $userTypeCondition[$security_users->aliasField('is_student')] = 1;
//                }else if($userTypeId ==2){
//                    $userTypeCondition[$security_users->aliasField('is_staff')] = 1;
//                }else if($userTypeId ==3){
//                    $userTypeCondition[$security_users->aliasField('is_guardian')] = 1;
//                }*///POCOR-7192 ends
//            }
//            $identityCondition = [];
//            if (!empty($identityTypeId) && !empty($identityNumber) && !empty($nationalityId)) {
//                $identityCondition[$userIdentities->aliasField('identity_type_id')] = $identityTypeId;
//                $identityCondition[$userIdentities->aliasField('nationality_id')] = $nationalityId;
//                $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
//            }else if(!empty($identityTypeId) && !empty($identityNumber) && empty($nationalityId)){
//                $identityCondition[$userIdentities->aliasField('identity_type_id')] = $identityTypeId;
//                $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
//            }else if(empty($identityTypeId) && !empty($identityNumber) && empty($nationalityId)){
//                $identityCondition[$userIdentities->aliasField('number')] = $identityNumber;
//            }
//
//            $get_result_by_identity_users_result = $security_users
//                ->find()
//                ->select([
//                    $security_users->aliasField('id'),
//                    $security_users->aliasField('username'),
//                    $security_users->aliasField('password'),
//                    $security_users->aliasField('openemis_no'),
//                    $security_users->aliasField('first_name'),
//                    $security_users->aliasField('middle_name'),
//                    $security_users->aliasField('third_name'),
//                    $security_users->aliasField('last_name'),
//                    $security_users->aliasField('preferred_name'),
//                    $security_users->aliasField('email'),
//                    $security_users->aliasField('address'),
//                    $security_users->aliasField('postal_code'),
//                    $security_users->aliasField('date_of_death'),
//                    $security_users->aliasField('external_reference'),
//                    $security_users->aliasField('last_login'),
//                    $security_users->aliasField('photo_name'),
//                    $security_users->aliasField('photo_content'),
//                    $security_users->aliasField('preferred_language'),
//                    $security_users->aliasField('address_area_id'),
//                    $security_users->aliasField('birthplace_area_id'),
//                    $security_users->aliasField('gender_id'),
//                    $security_users->aliasField('date_of_birth'),
//                    $security_users->aliasField('nationality_id'),
//                    $security_users->aliasField('identity_number'),
//                    $security_users->aliasField('super_admin'),
//                    $security_users->aliasField('status'),
//                    $security_users->aliasField('is_student'),
//                    $security_users->aliasField('is_staff'),
//                    $security_users->aliasField('is_guardian'),
//                    'Genders_id'=> $genders->aliasField('id'),
//                    'Genders_name'=> $genders->aliasField('name'),
//                    'MainIdentityTypes_id'=> $mainIdentityTypes->aliasField('id'),
//                    'MainIdentityTypes_name'=> $mainIdentityTypes->aliasField('name'),
//                    'MainNationalities_id'=> $mainNationalities->aliasField('id'),
//                    'MainNationalities_name'=> $mainNationalities->aliasField('name'),
//                    'area_name'=> $areaAdministratives->aliasField('name'),
//                    'area_code'=> $areaAdministratives->aliasField('code'),
//                    'birth_area_name'=> 'birthAreaAdministratives.name',
//                    'birth_area_code'=> 'birthAreaAdministratives.code',
//                    'MainIdentityTypes_number'=> $userIdentities->aliasField('number'),
//                ])
//                ->InnerJoin([$userIdentities->alias() => $userIdentities->table()],[
//                    $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
//                    $identityCondition
//                    //$userIdentities->aliasField('number') ." LIKE '" . $identityNumber . "%'"
//                ])
//                ->LeftJoin([$genders->alias() => $genders->table()], [
//                    $genders->aliasField('id =') . $security_users->aliasField('gender_id')
//                ])
//                ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
//                    $mainIdentityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')
//                ])
//                ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
//                    $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
//                ])
//                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table()], [
//                    $areaAdministratives->aliasField('id =') . $security_users->aliasField('address_area_id')
//                ])
//                ->LeftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->table()], [
//                    'birthAreaAdministratives.id =' . $security_users->aliasField('birthplace_area_id')
//                ])
//                ->where([$security_users->aliasField('super_admin').' <> ' => 1, $userTypeCondition])
//                ->group([$security_users->aliasField('id')])
//                ->limit($limit)
//                ->page($page)
//                ->toArray();
//            if(empty($get_result_by_identity_users_result)){
//                $security_users_result = $security_users
//                    ->find()
//                    ->select([
//                        $security_users->aliasField('id'),
//                        $security_users->aliasField('username'),
//                        $security_users->aliasField('password'),
//                        $security_users->aliasField('openemis_no'),
//                        $security_users->aliasField('first_name'),
//                        $security_users->aliasField('middle_name'),
//                        $security_users->aliasField('third_name'),
//                        $security_users->aliasField('last_name'),
//                        $security_users->aliasField('preferred_name'),
//                        $security_users->aliasField('email'),
//                        $security_users->aliasField('address'),
//                        $security_users->aliasField('postal_code'),
//                        $security_users->aliasField('date_of_death'),
//                        $security_users->aliasField('external_reference'),
//                        $security_users->aliasField('last_login'),
//                        $security_users->aliasField('photo_name'),
//                        $security_users->aliasField('photo_content'),
//                        $security_users->aliasField('preferred_language'),
//                        $security_users->aliasField('address_area_id'),
//                        $security_users->aliasField('birthplace_area_id'),
//                        $security_users->aliasField('gender_id'),
//                        $security_users->aliasField('date_of_birth'),
//                        $security_users->aliasField('nationality_id'),
//                        $security_users->aliasField('identity_number'),
//                        $security_users->aliasField('super_admin'),
//                        $security_users->aliasField('status'),
//                        $security_users->aliasField('is_student'),
//                        $security_users->aliasField('is_staff'),
//                        $security_users->aliasField('is_guardian'),
//                        'Genders_id'=> $genders->aliasField('id'),
//                        'Genders_name'=> $genders->aliasField('name'),
//                        'MainIdentityTypes_id'=> $mainIdentityTypes->aliasField('id'),
//                        'MainIdentityTypes_name'=> $mainIdentityTypes->aliasField('name'),
//                        'MainNationalities_id'=> $mainNationalities->aliasField('id'),
//                        'MainNationalities_name'=> $mainNationalities->aliasField('name'),
//                        'area_name'=> $areaAdministratives->aliasField('name'),
//                        'area_code'=> $areaAdministratives->aliasField('code'),
//                        'birth_area_name'=> 'birthAreaAdministratives.name',
//                        'birth_area_code'=> 'birthAreaAdministratives.code',
//                        'MainIdentityTypes_number'=> $userIdentities->aliasField('number'),
//                    ])
//                    ->InnerJoin([$userIdentities->alias() => $userIdentities->table()],[
//                        $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
//                        $identityCondition
//                    ])
//                    ->LeftJoin([$genders->alias() => $genders->table()], [
//                        $genders->aliasField('id =') . $security_users->aliasField('gender_id')
//                    ])
//                    ->LeftJoin([$mainIdentityTypes->alias() => $mainIdentityTypes->table()], [
//                        $mainIdentityTypes->aliasField('id =') . $userIdentities->aliasField('identity_type_id')
//                    ])
//                    ->LeftJoin([$mainNationalities->alias() => $mainNationalities->table()], [
//                        $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
//                    ])
//                    ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table()], [
//                        $areaAdministratives->aliasField('id =') . $security_users->aliasField('address_area_id')
//                    ])
//                    ->LeftJoin(['birthAreaAdministratives' => $birthAreaAdministratives->table()], [
//                        'birthAreaAdministratives.id =' . $security_users->aliasField('birthplace_area_id')
//                    ])
//                    ->where([$security_users->aliasField('super_admin').' <> ' => 1, $conditions])
//                    ->group([$security_users->aliasField('id')])
//                    ->limit($limit)
//                    ->page($page)
//                    ->toArray();
//            }else{
//                $security_users_result = $get_result_by_identity_users_result;
//            }
//
//            $totalCount = $this->getCountInernalSearch($conditions, $identityNumber, $identityCondition, $userTypeCondition);//POCOR-5672 ends
//        }
//        $institutions = TableRegistry::get('institutions');
//        $institutionsTbl = $institutions
//                            ->find()
//                            ->select([
//                                'institution_name'=>$institutions->aliasField('name'),
//                                'institution_code'=>$institutions->aliasField('code')
//                            ])->where([
//                                $institutions->aliasField('id') => $institutionId
//                            ])->first();
//
//        $institutionStudents = TableRegistry::get('institution_students');
//        $institutionStaff = TableRegistry::get('institution_staff');
//
//        $result_array = [];
//        foreach($security_users_result AS $result){
//            $MainNationalities_id = !empty($result['MainNationalities_id']) ? $result['MainNationalities_id'] : '';
//            $MainNationalities_name = !empty($result['MainNationalities_name']) ? $result['MainNationalities_name'] : '';
//            $MainIdentityTypes_id = !empty($result['MainIdentityTypes_id']) ? $result['MainIdentityTypes_id'] : '';
//            $MainIdentityTypes_name = !empty($result['MainIdentityTypes_name']) ? $result['MainIdentityTypes_name'] : '';
//            $identity_number = !empty($result['MainIdentityTypes_number']) ? $result['MainIdentityTypes_number'] : '';
//
//            $UserNeeds = TableRegistry::get('user_special_needs_assessments');
//            $SpecialNeeds = $UserNeeds->find()
//                            ->where([$UserNeeds->aliasField('security_user_id') => $result['id']])
//                            ->count();
//            $has_special_needs = ($SpecialNeeds == 1) ? true : false;
//
//            $is_same_school = $is_diff_school = $academic_period_id = $academic_period_year = 0;
//            $education_grade_id = $institution_id = $institution_code = $institution_name = '';
//            $CustomDataArray = [];
//            if (!empty($userTypeId)) {
//                if($result['is_student'] == 1){
//                    $account_type = 'Student';
//                }else if($result['is_staff'] == 1){
//                    $account_type = 'Staff';
//                }else if($result['is_guardian'] == 1){
//                    $account_type = 'Guardian';
//                }else{
//                    $account_type = 'Others';
//                }
//                if($userTypeId == 1){
//                    //$account_type = 'Student';
//                    $security_user_id = $result->id;
//                    $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
//                    $statuses = $StudentStatuses->findCodeList();
//                    Log::write('debug', '$userTypeId == self::STUDENT');
//                    //POCOR-7224-HINDOL
//                    //$account_type = 'Student';
//                    $student = $Directories::getStudent($security_user_id);
//
//                    if (!empty($student)) {
//                        $institution_id = $student->institution_id;
//                        $institution_name = $student->institution_name;
//                        $institution_code = $student->institution_code;
//                        $academic_period_id = $student->academic_period_id;
//                        $academic_period_year = $student->academic_period_year;
//                        $education_grade_id = $student->education_grade_id;
//                        if ($student->institution_id == $institutionId) {
//                            $is_same_school = 1;
//                        } else {
//                            $is_diff_school = 1;
//                        }
//                    }
//
//                    $pendingTransfer = $Directories::getPendingTransfer($security_user_id);
//                    Log::write('debug', '$pendingTransfer');
//                    Log::write('debug', $pendingTransfer);
//                    $pendingWithdraw = $Directories::getPendingWithdraw($security_user_id);
//                    Log::write('debug', '$pendingWithdraw');
//                    Log::write('debug', $pendingWithdraw);
//
//                    if ($pendingTransfer) {
//                        $is_pending_transfer = 1;
//                        $pending_transfer_institution_id = $pendingTransfer->institution_id;
//                        $pending_transfer_institution_name = $pendingTransfer->institution_name;
//                        $pending_transfer_institution_code = $pendingTransfer->institution_code;
//                        $pending_transfer_academic_period_id = $pendingTransfer->academic_period_id;
//                    }
//                    if ($pendingWithdraw) {
//                        $is_pending_withdraw = 1;
//                        $pending_withdraw_institution_id = $pendingWithdraw->institution_id;
//                        $pending_withdraw_institution_name = $pendingWithdraw->institution_name;
//                        $pending_withdraw_institution_code = $pendingWithdraw->institution_code;
//                        $pending_withdraw_academic_period_id = $pendingWithdraw->academic_period_id;
//                    }
////                    $institutionStudTbl = $institutionStudents
////                                    ->find()
////                                    ->select([
////                                       'institution_id'=> $institutionStudents->aliasField('institution_id'),
////                                        'student_id'=>$institutionStudents->aliasField('student_id'),
////                                        'student_status_id'=>$institutionStudents->aliasField('student_status_id'),
////                                        'institution_name'=>$institutions->aliasField('name'),
////                                        'institution_code'=>$institutions->aliasField('code'),
////                                        'academic_period_id'=>$institutionStudents->aliasField('academic_period_id'),
////                                        'academic_period_year'=>$institutionStudents->aliasField('start_year'),
////                                        'education_grade_id'=>$institutionStudents->aliasField('education_grade_id')
////                                    ])
////                                    ->InnerJoin([$institutions->alias() => $institutions->table()], [
////                                        $institutions->aliasField('id =') . $institutionStudents->aliasField('institution_id')
////                                    ])
////                                    ->where([
////                                        $institutionStudents->aliasField('student_id') => $result['id'],
////                                        $institutionStudents->aliasField('student_status_id') => $statuses['CURRENT']
////                                    ])->first();
////                    if(!empty($institutionStudTbl)){
////                        $institution_id = $institutionStudTbl->institution_id;
////                        $institution_name = $institutionStudTbl->institution_name;
////                        $institution_code = $institutionStudTbl->institution_code;
////                        $academic_period_id = $institutionStudTbl->academic_period_id;
////                        $academic_period_year = $institutionStudTbl->academic_period_year;
////                        $education_grade_id = $institutionStudTbl->education_grade_id;
////                        if($institutionStudTbl->institution_id == $institutionId){
////                            $is_same_school = 1;
////                        }else{
////                            $is_diff_school = 1;
////                        }
////                    }
////                    //get student custom data
//                    $CustomDataArray = self::getStudentCustomData($security_user_id);
//                }else if($userTypeId == 2){
//                    //$account_type = 'Staff';
//                    $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
//                    $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
//
//                    $institutionStaffTbl = $institutionStaff
//                                    ->find()
//                                    ->select([
//                                       'institution_id'=> $institutionStaff->aliasField('institution_id'),
//                                        'staff_id'=>$institutionStaff->aliasField('staff_id'),
//                                        'institution_position_id'=>$institutionStaff->aliasField('institution_position_id'),
//                                        'staff_status_id'=>$institutionStaff->aliasField('staff_status_id'),
//                                        'institution_name'=>$institutions->aliasField('name'),
//                                        'institution_code'=>$institutions->aliasField('code')
//                                    ])
//                                    ->InnerJoin([$institutions->alias() => $institutions->table()], [
//                                        $institutions->aliasField('id =') . $institutionStaff->aliasField('institution_id')
//                                    ])
//                                    ->where([
//                                        $institutionStaff->aliasField('staff_id') => $result['id'],
//                                        $institutionStaff->aliasField('staff_status_id') => $assignedStatus,
//                                        $institutionStaff->aliasField('institution_id') => $institutionId
//                                    ])->toArray();
//
//                    if(!empty($institutionStaffTbl)){
//                        $positionArray = [];
//                        $is_same_school = 1;
//                        foreach ($institutionStaffTbl as $skey => $sval) {
//                            $institution_id = $sval->institution_id;
//                            $institution_name = $sval->institution_name;
//                            $institution_code = $sval->institution_code;
//                            $positionArray[$skey] = $sval->institution_position_id;
//                        }
//                    }else{
//                        $institutionStaffTbl = $institutionStaff
//                                    ->find()
//                                    ->select([
//                                       'institution_id'=> $institutionStaff->aliasField('institution_id'),
//                                        'staff_id'=>$institutionStaff->aliasField('staff_id'),
//                                        'institution_position_id'=>$institutionStaff->aliasField('institution_position_id'),
//                                        'staff_status_id'=>$institutionStaff->aliasField('staff_status_id'),
//                                        'institution_name'=>$institutions->aliasField('name'),
//                                        'institution_code'=>$institutions->aliasField('code')
//                                    ])
//                                    ->InnerJoin([$institutions->alias() => $institutions->table()], [
//                                        $institutions->aliasField('id =') . $institutionStaff->aliasField('institution_id')
//                                    ])
//                                    ->where([
//                                        $institutionStaff->aliasField('staff_id') => $result['id'],
//                                        $institutionStaff->aliasField('staff_status_id') => $assignedStatus
//                                    ])->toArray();
//                        if(empty($institutionStaffTbl)){
//                            $is_diff_school = 0;
//                        }else{
//                            $is_diff_school = 1;
//                        }
//                        $positionArray = [];
//                        foreach ($institutionStaffTbl as $skey => $sval) {
//                            $institution_id = $sval->institution_id;
//                            $institution_name = $sval->institution_name;
//                            $institution_code = $sval->institution_code;
//                            $positionArray[$skey] = $sval->institution_position_id;
//                        }
//                    }
//                    //get staff custom data
//                    $CustomDataArray = $this->getStaffCustomData($result['id']);
//                }else if($userTypeId ==3){
//                    //$account_type = 'Guardian';
//                }else{
//                    //$account_type = 'Others';
//                }
//            }
//
//            $result_array[] = array('id' => $result['id'],'username' => $result['username'],'password' => $result['password'],'openemis_no' => $result['openemis_no'],'first_name' => $result['first_name'],'middle_name' => $result['middle_name'],'third_name' => $result['third_name'],'last_name' => $result['last_name'],'preferred_name' => $result['preferred_name'],'email' => $result['email'],'address' => $result['address'],'postal_code' => $result['postal_code'],'gender_id' => $result['gender_id'],'external_reference' => $result['external_reference'],'last_login' => $result['last_login'],'photo_name' => $result['photo_name'],'photo_content' => $result['photo_content'],'preferred_language' => $result['preferred_language'],'address_area_id' => $result['address_area_id'],'birthplace_area_id' => $result['birthplace_area_id'],'super_admin' => $result['super_admin'],'status' => $result['status'],'is_student' => $result['is_student'],'is_staff' => $result['is_staff'],'is_guardian' => $result['is_guardian'],'name'=>$result['first_name']." ".$result['last_name'],'date_of_birth'=>$result['date_of_birth']->format('Y-m-d'),'gender'=>$result['Genders_name'],'nationality_id'=>$MainNationalities_id,'nationality'=>$MainNationalities_name,'identity_type_id'=>$MainIdentityTypes_id,'identity_type'=>$MainIdentityTypes_name,'identity_number'=>$identity_number,'has_special_needs'=>$has_special_needs,'area_name'=>$result['area_name'],'area_code'=>$result['area_code'],'birth_area_name'=>$result['birth_area_name'],'birth_area_code'=>$result['birth_area_code'], 'is_same_school'=>$is_same_school, 'is_diff_school'=>$is_diff_school, 'current_enrol_institution_id'=> $institution_id, 'current_enrol_institution_name'=> $institution_name, 'current_enrol_institution_code'=> $institution_code, 'current_enrol_academic_period_id'=> $academic_period_id, 'current_enrol_academic_period_year'=> $academic_period_year, 'current_enrol_education_grade_id'=> $education_grade_id, 'institution_name'=>$institutionsTbl->institution_name, 'institution_code'=>$institutionsTbl->institution_code, 'positions'=>$positionArray, 'account_type'=> $account_type, 'custom_data'=>$CustomDataArray);
//        }
        echo json_encode($internalSearchResults, JSON_PARTIAL_OUTPUT_ON_ERROR); die;
    }
    //POCOR-7072 starts
    public function getStaffCustomData($staff_id=null){
        $staffCustomFieldValues = TableRegistry::get('staff_custom_field_values');
        $staffCustomFieldOptions = TableRegistry::get('staff_custom_field_options');
        $staffCustomFields = TableRegistry::get('staff_custom_fields');
        $staffCustomData = $staffCustomFieldValues->find()
            ->select([
                    'id'                             => $staffCustomFieldValues->aliasField('id'),
                    'custom_id'                      => 'staffCustomField.id',
                    'staff_id'                     => $staffCustomFieldValues->aliasField('staff_id'),
                    'staff_custom_field_id'        => $staffCustomFieldValues->aliasField('staff_custom_field_id'),
                    'text_value'                     => $staffCustomFieldValues->aliasField('text_value'),
                    'number_value'                   => $staffCustomFieldValues->aliasField('number_value'),
                    'decimal_value'                  => $staffCustomFieldValues->aliasField('decimal_value'),
                    'textarea_value'                 => $staffCustomFieldValues->aliasField('textarea_value'),
                    'date_value'                     => $staffCustomFieldValues->aliasField('date_value'),
                    'time_value'                     => $staffCustomFieldValues->aliasField('time_value'),
                    'option_value_text'              => $staffCustomFieldOptions->aliasField('name'),
                    'name'                           => 'staffCustomField.name',
                    'field_type'                     => 'staffCustomField.field_type',
                ])->leftJoin(
                ['staffCustomField' => 'staff_custom_fields'],
                [
                    'staffCustomField.id = '.$staffCustomFieldValues->aliasField('staff_custom_field_id')
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
        if(!empty($staffCustomData)){
            foreach ($staffCustomData as $val) {
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
        return $custom_field;
    }

    public function getStudentCustomData($student_id=null){
        $studentCustomFieldValues = TableRegistry::get('student_custom_field_values');
        $studentCustomFieldOptions = TableRegistry::get('student_custom_field_options');
        $studentCustomFields = TableRegistry::get('student_custom_fields');
        $studentCustomData = $studentCustomFieldValues->find()
            ->select([
                    'id'                             => $studentCustomFieldValues->aliasField('id'),
                    'custom_id'                      => 'studentCustomField.id',
                    'student_id'                     => $studentCustomFieldValues->aliasField('student_id'),
                    'student_custom_field_id'        => $studentCustomFieldValues->aliasField('student_custom_field_id'),
                    'text_value'                     => $studentCustomFieldValues->aliasField('text_value'),
                    'number_value'                   => $studentCustomFieldValues->aliasField('number_value'),
                    'decimal_value'                  => $studentCustomFieldValues->aliasField('decimal_value'),
                    'textarea_value'                 => $studentCustomFieldValues->aliasField('textarea_value'),
                    'date_value'                     => $studentCustomFieldValues->aliasField('date_value'),
                    'time_value'                     => $studentCustomFieldValues->aliasField('time_value'),
                    'option_value_text'              => $studentCustomFieldOptions->aliasField('name'),
                    'name'                           => 'studentCustomField.name',
                    'field_type'                     => 'studentCustomField.field_type',
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
                $studentCustomFieldValues->aliasField('student_id') => $student_id,
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
        return $custom_field;
    }//POCOR-7072 ends

    public function getCountInernalSearch($conditions = [], $identityNumber, $identityCondition = [], $userTypeCondition = []){
        $security_users = TableRegistry::get('security_users');
        $userIdentities = TableRegistry::get('user_identities');
        $genders = TableRegistry::get('genders');
        $mainIdentityTypes = TableRegistry::get('identity_types');
        $mainNationalities = TableRegistry::get('nationalities');
        if($identityNumber == ''){
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
                    'Genders_id'=> $genders->aliasField('id'),
                    'Genders_name'=> $genders->aliasField('name'),
                    'MainIdentityTypes_id'=> $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name'=> $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id'=> $mainNationalities->aliasField('id'),
                    'MainNationalities_name'=> $mainNationalities->aliasField('name'),
                ])
                ->LeftJoin(['Identities' => 'user_identities'],[
                    'Identities.security_user_id'=> $security_users->aliasField('id'),
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
                ->where([$security_users->aliasField('super_admin').' <> ' => 1, $conditions])
                ->group([$security_users->aliasField('id')])
                ->count();
        }else{
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
                    'Genders_id'=> $genders->aliasField('id'),
                    'Genders_name'=> $genders->aliasField('name'),
                    'MainIdentityTypes_id'=> $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name'=> $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id'=> $mainNationalities->aliasField('id'),
                    'MainNationalities_name'=> $mainNationalities->aliasField('name'),
                ])
                ->InnerJoin([$userIdentities->alias() => $userIdentities->table()],[
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
                ->where([$security_users->aliasField('super_admin').' <> ' => 1, $userTypeCondition])
                ->group([$security_users->aliasField('id')])
                ->count();
            if($get_result_by_identity_users_result == 0){
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
                        'Genders_id'=> $genders->aliasField('id'),
                        'Genders_name'=> $genders->aliasField('name'),
                        'MainIdentityTypes_id'=> $mainIdentityTypes->aliasField('id'),
                        'MainIdentityTypes_name'=> $mainIdentityTypes->aliasField('name'),
                        'MainNationalities_id'=> $mainNationalities->aliasField('id'),
                        'MainNationalities_name'=> $mainNationalities->aliasField('name'),
                    ])
                    ->InnerJoin([$userIdentities->alias() => $userIdentities->table()],[
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
                    ->where([$security_users->aliasField('super_admin').' <> ' => 1, $conditions])
                    ->group([$security_users->aliasField('id')])
                    ->count();
            } else {
               $security_users_result = $get_result_by_identity_users_result;
            }   
        }
        //POCOR-5672 ends
        return $security_users_result;
    }

    public function directoryExternalSearch()
    {   
        $this->autoRender = false;
        $ExternalAttributes = TableRegistry::get('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->innerJoin(['ConfigItems' => 'config_items'], [
                'ConfigItems.code' => 'external_data_source_type',
                $ExternalAttributes->aliasField('external_data_source_type').' = ConfigItems.value'
            ])
            ->toArray();
        
        $clientId = $attributes['client_id'];
        $scope = $attributes['scope'];
        $tokenUri = $attributes['token_uri'];
        $privateKey = $attributes['private_key'];
        $token = $ExternalAttributes->generateServerAuthorisationToken($clientId, $scope, $tokenUri, $privateKey);
 
        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $token
        ];
        
        $requestData = $this->request->input('json_decode', true);
        $requestData = $requestData['params'];
        $firstName = (array_key_exists('first_name', $requestData))? $requestData['first_name']: null;
        $lastName = (array_key_exists('last_name', $requestData))? $requestData['last_name']: null;
        $openemisNo = (array_key_exists('openemis_no', $requestData))? $requestData['openemis_no']: null;
        $identityNumber = (array_key_exists('identity_number', $requestData))? $requestData['identity_number']: null;
        $dateOfBirth = (array_key_exists('date_of_birth', $requestData) && !empty($requestData['date_of_birth']))? date('Y-m-d', strtotime($requestData['date_of_birth'])): null;
        $limit = (array_key_exists('limit', $requestData)) ? $requestData['limit']: 10;
        $page = (array_key_exists('page', $requestData)) ? $requestData['page']: 1;
        $id = (array_key_exists('id', $requestData)) ? $requestData['id']: '';
        //POCOR-5672 starts new changes searching by identity number 
        if(!empty($identityNumber)){
            $fieldMapping = [
                '{page}' => $page,
                '{limit}' => $limit,
                '{first_name}' => '',
                '{last_name}' => '',
                '{date_of_birth}' => '',
                '{identity_number}' => $identityNumber
            ];//POCOR-5672 ends
        }else{
            $fieldMapping = [
                '{page}' => $page,
                '{limit}' => $limit,
                '{first_name}' => $firstName,
                '{last_name}' => $lastName,
                '{date_of_birth}' => $dateOfBirth,
                '{identity_number}' => $identityNumber
            ];
        }

        $http = new Client();
        $response = $http->post($attributes['token_uri'], $data);
        $noData = json_encode(['data' => [], 'total' => 0], JSON_PRETTY_PRINT);
        if ($response->isOK()) {
            $body = $response->body('json_decode');
            $recordUri = $attributes['record_uri'];

            foreach ($fieldMapping as $key => $map) {
                $recordUri = str_replace($key, $map, $recordUri);
            }

            $http = new Client([
                'headers' => ['Authorization' => $body->token_type.' '.$body->access_token]
            ]);

            $response = $http->get($recordUri);

            if ($response->isOK()) {
                $this->response->body(json_encode($response->body('json_decode'), JSON_PRETTY_PRINT));
            } else {
                $this->response->body($noData);
            }
        } else {
            $this->response->body($noData);
        }

        if(!empty($id)){
            $mydata = json_decode(new Response(['body' => $this->response->body(json_encode($response->body('json_decode'), JSON_PRETTY_PRINT))]));
            $singleUserData = [];
            foreach ($mydata->data as $key => $value) {
                if($value->id == $id){
                    $singleUserData['data'][] = $value;
                }
            }
            return new Response(['body' => $this->response->body(json_encode($singleUserData, JSON_PRETTY_PRINT))]);
        }

        return new Response(['body' => $this->response->body(json_encode($response->body('json_decode'), JSON_PRETTY_PRINT))]);
    }

    public function getContactType()
    {
        $contact_types = TableRegistry::get('contact_types');
        $contact_types_result = $contact_types
            ->find()
            ->select(['id','name'])
            ->toArray();
        foreach($contact_types_result AS $result){
            $result_array[] = array("id" => $result['id'], "name"=> $result['name']);
        }
        echo json_encode($result_array);die;
    }

    //POCOR-5673 starts
    public function getRedirectToGuardian()
    {
        $config_items = TableRegistry::get('config_items');
        $config_items_result = $config_items
            ->find()
            ->where(['code' => 'RedirectToGuardian'])
            ->toArray();
        $res = false;
        foreach($config_items_result AS $result){
            if($result['value'] == 1){ $res = true; }
            $result_array[] = array("redirecttoguardian_status" => $res);
        }
        echo json_encode($result_array);die;
    }

    public function getRelationshipType()
    {
        $guardian_relations = TableRegistry::get('guardian_relations');
        $guardian_relations_result = $guardian_relations
            ->find()
            ->where(['visible' => 1])
            ->toArray();
        foreach($guardian_relations_result AS $result){
            $result_array[] = array("id" => $result['id'], "name" => $result['name']);
        }
        echo json_encode($result_array);die;
    }//POCOR-5673 ends
    
    public function StudentAbsences()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Absences']);
    }

    public function Absences() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Absences']); }

    /*POCOR-6286 starts - registering functions*/
    public function StaffProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.StaffProfiles']); }

    public function StudentProfiles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.StudentProfiles']); }
    /*POCOR-6286 ends*/




   /*POCOR-6700 start - registering function*/
    public function StudentExtracurriculars() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Extracurriculars']); }
    /*POCOR-6700 ends*/
}
