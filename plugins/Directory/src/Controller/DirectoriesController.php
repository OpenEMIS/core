<?php

namespace Directory\Controller;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use App\Controller\AppController;
use Cake\Http\Response;
use Cake\Http\Client;
use User\Controller\SyncUserTrait; //POCOR-9590

class DirectoriesController extends AppController
{
    use SyncUserTrait; //POCOR-9590

    //POCOR-9590: public — also called by DirectoriesTable::addSyncButton to avoid duplicating the ACL triple
    public function syncUserPermission(): array
    {
        return ['Directories', 'Directories', 'add'];
    }

    const STUDENT = 1;
    const STAFF = 2;
    const GUARDIAN = 3;
    const OTHER = 4;
    private $searchingAJAX = 0;

    public function initialize(): void
    {
        parent::initialize();
        $this->ControllerAction->models = [
            // Users
            'Accounts' => ['className' => 'Directory.Accounts', 'actions' => ['view', 'edit']],

            // Student
            //'StudentAbsences'       => ['className' => 'Directory.Absences', 'actions' => ['index', 'view']],
            //'StudentAbsences'       => ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
            // 'StudentBehaviours' => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
            //'StudentExtracurriculars' => ['className' => 'Student.Extracurriculars'],

            // Staff
            'StaffPositions' => ['className' => 'Staff.Positions', 'actions' => ['index', 'view']],
            'StaffSections' => ['className' => 'Staff.StaffSections', 'actions' => ['index', 'view']],
            'StaffClasses' => ['className' => 'Staff.StaffClasses', 'actions' => ['index', 'view']],
            'StaffQualifications' => ['className' => 'Staff.Qualifications'],
            'StaffExtracurriculars' => ['className' => 'Staff.Extracurriculars'],
            'StaffDuties' => ['className' => 'Institution.StaffDuties', 'actions' => ['index', 'view']],
            'TrainingResults' => ['className' => 'Staff.TrainingResults', 'actions' => ['index', 'view']],

            'ImportUsers' => ['className' => 'Directory.ImportUsers', 'actions' => ['add']],
            'ImportSalaries' => ['className' => 'Staff.ImportSalaries', 'actions' => ['add']]
        ];

        $this->loadComponent('Training.Training');
        $this->loadComponent('Configuration.Configuration');
        $this->loadComponent('User.Image');
        $this->loadComponent('Institution.CreateUsers');
        $this->Nationalities = $this->fetchTable('FieldOption.Nationalities');
        $this->Directories = $this->fetchTable('Directory.Directories');
        $this->AreaAdministratives = $this->fetchTable('Area.AreaAdministratives');
        $this->attachAngularModules();
        $this->attachAngularModulesForDirectory();
        //POCOR-5672 it is used for removing csrf token mismatch condition in directory external search
        if ($this->request->getParam('action') == 'directoryExternalSearch') {
            $this->getEventManager()->off($this->Csrf);
        }//POCOR-5672 ends

        $this->set('contentHeader', 'Directories');
    }

    private function attachAngularModules()
    {
        $action = $this->request->getParam('action');

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

    // CAv4

    private function attachAngularModulesForDirectory()
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
//                    'directory.directoryaddguardian.svc'
                ]);
                break;
        }
    }

    public function Directories()
    {
        $action = $this->request->getParam('pass')[0];
        if ($action == 'add') {
            $this->attachAngularModulesForDirectory();
            $this->set('ngController', 'DirectoryAddCtrl as $ctrl');
        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.Directories']);
        }
    }

    public function StudentFees()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentFees']);
    }

    public function Histories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserHistories']);
    }

    public function StaffEmployments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Employments']);
    }

    // POCOR-9287 start
    public function StaffEntitlement()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffEntitlement']);
    }
    // POCOR-9287 end

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

    public function Memberships()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Memberships']);
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
        $comment = $this->request->getQuery('comment');
        if (!empty($comment) && $comment == 1) {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomeComments']);

        } else {
            $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentOutcomes']);
        }

    }

    // health

    public function StudentRisks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentRisks']);
    }

    public function StudentAssociations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.InstitutionAssociationStudent']);
    }

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

    public function HealthBodyMasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.BodyMasses']);
    }

    public function HealthInsurances()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Insurances']);
    }

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
    // Special Needs - End
    //POCOR-7366 start

    public function SpecialNeedsPlans()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsPlans']);
    }

    //POCOR-7366 end

    public function SpecialNeedsDiagnostics()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsDiagnostics']);
    }

    // Historical Data - End

    public function Counsellings()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.Counsellings']);
    }

    public function Employments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserEmployments']);
    }

    public function HistoricalStaffPositions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Historical.HistoricalStaffPositions']);
    }

    public function HistoricalStaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Historical.HistoricalStaffLeave']);
    }

    public function Comments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Comments']);
    }
    // End

    // AngularJS

    public function ImportStaffQualifications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.ImportStaffQualifications']);
    }

    public function Addguardian()
    {
        //POCOR-7231 :: Start
        $qs = $this->request->getQuery('queryString');
        if ($qs) {
            $requestDataa = base64_decode($this->request->getQuery('queryString'));
            $requestDataa = json_decode($requestDataa, true);
        }
        if (empty($requestDataa) && isset($this->request->getParam('pass')[0])) {
            $requestDataa = base64_decode($this->request->getParam('pass')[0]);
            $requestDataa = json_decode($requestDataa, true);
        }
//        die(print_r($requestDataa, true));
        $UsersTable = $this->getDynamicTableInstance('User.Users');
        $InstitutionTable = $this->getDynamicTableInstance('Institution.Institutions');

        if (isset($requestDataa['student_id'])) {
            $UserData = $UsersTable->find('all', ['conditions' => ['id' => $requestDataa['student_id']]])->first();
        }
        // POCOR-8231 if found skip redundant search
        if (!$UserData) {
            if (isset($requestDataa['openemis_no'])) {
                $UserData = $UsersTable->find('all', ['conditions' => ['openemis_no' => $requestDataa['openemis_no']]])->first();
            }
        }
        if (isset($requestDataa['institution_id'])) {
            $InstitutionData = $InstitutionTable->find('all', ['conditions' => ['id' => $requestDataa['institution_id']]])->first();
        }
        if ($UserData) {
            // POCOR-8231 fix crumbs
            $name = $UserData->name;
            $id = $UserData->id;
            $queryStng = $this->paramsEncode(['id' => $UserData->id]);
            $this->Navigation->addCrumb($name, [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'Directories',
                'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
        }
        $this->set('InstitutionData', $InstitutionData);
        $this->set('UserData', $UserData);
        $this->set('queryStng', $queryStng);//POCOR-7231 :: END
        $this->attachAngularModulesForDirectory();
        $this->set('ngController', 'DirectoryaddguardianCtrl as $ctrl');
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
     */
    public function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            // Log::error('Error: ' . $e->getMessage()); // POCOR-9481
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

    // End

    public function StudentResults()
    {
        $session = $this->request->getSession();

        if ($session->check('Directory.Directories.id')) {
            $studentId = $session->check('Directory.Directories.id') ? $session->read('Directory.Directories.id') : $student_id;
            $session->write('Student.Results.student_id', $studentId);

            // tabs
            $options['type'] = 'student';
            $tabElements = $this->getAcademicTabElements($options);
            $this->set('tabElements', $tabElements);
            $this->set('selectedAction', 'Results');
            // End

            $this->set('ngController', 'StudentResultsCtrl as StudentResultsController');

            // Start POCOR-5188
            $manualTable = TableRegistry::getTableLocator()->get('Manuals');
            $ManualContent = $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Assessments',
                $manualTable->aliasField('module') => 'Directory',
                $manualTable->aliasField('category') => 'Students - Academic',
            ])->first();

            if (!empty($ManualContent['url'])) {
                $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
            } else {
                $this->set('is_manual_exist', []);
            }
            // End POCOR-5188
        }
    }

    public function getAcademicTabElements($options = [])
    {
        $id = (isset($options['id'])) ? $options['id'] : 0;
        $type = (isset($options['type'])) ? $options['type'] : null;
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
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
            // 'Extracurriculars' => ['text' => __('Extracurriculars')],
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Houses')], //POCOR-7938
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Student' . $key, 'index', 'queryString' => $encodedQueryString, 'type' => $type]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function StudentExaminationResults()
    {
        $session = $this->request->getSession();

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
            $manualTable = TableRegistry::getTableLocator()->get('Manuals');
            $ManualContent = $manualTable->find()->select(['url'])->where([
                $manualTable->aliasField('function') => 'Examinations',
                $manualTable->aliasField('module') => 'Directory',
                $manualTable->aliasField('category') => 'Students - Academic',
            ])->first();

            if (!empty($ManualContent['url'])) {
                $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
            } else {
                $this->set('is_manual_exist', []);
            }
            // End POCOR-5188
        }
    }

    public function StaffAttendances()
    {
        $paramsQuery = $this->ControllerAction->getQueryString();
        $staffId = $paramsQuery['staff_id'];
        $tabElements = $this->getCareerTabElements();
        $institutionId = $this->getInstitutionID();
        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
        $this->Navigation->addCrumb($crumbTitle);
        $this->set('institution_id', $institutionId);
        $this->set('staff_id', $staffId);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', 'Attendances');
        $this->set('ngController', 'StaffAttendancesCtrl as $ctrl');

        // Start POCOR-5188
        $manualTable = TableRegistry::getTableLocator()->get('Manuals');
        $ManualContent = $manualTable->find()->select(['url'])->where([
            $manualTable->aliasField('function') => 'Attendances',
            $manualTable->aliasField('module') => 'Directory',
            $manualTable->aliasField('category') => 'Staff - Career',
        ])->first();

        if (!empty($ManualContent['url'])) {
            $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
        } else {
            $this->set('is_manual_exist', []);
        }
        // End POCOR-5188
    }

    public function getCareerTabElements($options = [])
    {
        $type = (isset($options['type'])) ? $options['type'] : null;
        $tabElements = [];
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
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
            'Associations' => ['text' => __('Houses')], //POCOR-7938
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Staff' . $key, 'index', $encodedQueryString, 'type' => 'staff']);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    private function getInstitutionID()
    {
        $session = $this->request->getSession();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = isset($this->request->getAttribute('params')['institutionId']) ?
            $this->request->getAttribute('params')['institutionId'] :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Directory', ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories']);
        $header = __('Directory');
        $session = $this->request->getSession();
        $action = $this->request->getParam('action');
        $getQuery = $this->request->getParam('pass');
        $furtherAction = $getQuery[0];
        $query = $this->request->getQuery();
        if ($action == 'StudentGuardians' && ($furtherAction == 'view' || $furtherAction == 'edit')) {
            return;
        }
        if (isset($query['user_id'])) {
            $userId = $query['user_id'];
            $Directories = TableRegistry::getTableLocator()->get('Directory.Directories');
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

        if (($action == 'Directories' && (empty($this->ControllerAction->paramsPass()) && (($this->request->getParam('pass')[0] != 'view') && ($this->request->getParam('pass')[0] != 'edit') && ($this->request->getParam('pass')[0] != 'StudentResults'))) || ($action == 'Directories' && $this->request->getParam('pass')[0] == 'index'))) {
            $session->delete('Directory.Directories.id');
            $session->delete('Staff.Staff.id');
            $session->delete('Staff.Staff.name');
            $session->delete('Student.Students.id');
            $session->delete('Student.Students.name');
            $session->delete('Guardian.Guardians.id');
            $session->delete('Guardian.Guardians.name');
        } else if (/*($action != 'StudentGuardians') && */ $session->check('Directory.Directories.id') || ($this->request->getParam('pass')[0] == 'view') || ($this->request->getParam('pass')[0] == 'edit') || ($this->request->getParam('pass')[0] == 'add') || ($this->request->getParam('pass')[0] == 'StudentResults') || ($action != 'Directories' && $this->request->getParam('pass')[0] == 'index')) {
            /*echo "<pre>"; print_r($_SESSION);
            echo "<pre>"; print_r();
            die;*/
            $id = 0;
            $requestQueryString = $this->request->getQuery();
            if (isset($this->request->getParam('pass')[0]) && ($this->request->getParam('pass')[0] == 'view' || $this->request->getParam('pass')[0] == 'edit')) {
                //$id = $this->ControllerAction->paramsDecode($this->request->pass[0])['id'];//POCOR-7485 comment
                $param = $this->ControllerAction->paramsDecode($this->request->getParam('pass')[1]);
                $id = $param['id'];
                if (isset($param['staff_id']) && !empty($param['staff_id'])) {
                    $id = $param['staff_id'];
                } else if (isset($param['security_user_id']) && !empty($param['security_user_id'])) {
                    $id = $param['security_user_id'];
                }
            } else if ($this->request->getParam('pass')[0] == 'index' && isset($requestQueryString) && !empty($requestQueryString)) {
                //$id = $this->ControllerAction->paramsDecode($this->request->getQuery('queryString'))['security_user_id'];
                if (isset($this->request->getParam('pass')[1]) && $this->ControllerAction->paramsDecode($this->request->getParam('pass')[1])['staff_id']) {
                    $id = $this->ControllerAction->paramsDecode($this->request->getParam('pass')[1])['staff_id'];
                }
                if (empty($id) && $session->check('Directory.Directories.id')) {
                    $id = $session->read('Directory.Directories.id');
                }
            } else if ($session->check('Directory.Directories.id')) {
                $id = $session->read('Directory.Directories.id');
            } else if ($session->check('Directory.Directories.primaryKey.id')) {
                $id = $session->read('Directory.Directories.primaryKey.id');
            }

            if (!empty($id)) {
                $entity = $this->Directories->get($id);
                $session->write('Directory.Directories.id', $entity->id);//POCOR-7485 add
                $name = $entity->name;
                $header = $action == 'StudentResults' ? $name . ' - ' . __('Assessments') : $name . ' - ' . __('Overview');
                $this->Navigation->addCrumb($name, ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
            }
        } elseif ($action != 'Directories' && $this->getPlugin() == 'Directory' || $this->request->getParam('pass')[0] == 'index') { // for export

        }
        $paramPass = $this->ControllerAction->paramsPass();
        if ($action == 'StudentGuardians' && empty($paramPass)) {
            $session->delete('Directory.Directories.guardianToStudent');
        }
        if ($action == 'GuardianStudents' && empty($paramPass)) {
            $session->delete('Directory.Directories.studentToGuardian');
        }
        if (($action == 'Directories') && ($this->ControllerAction->paramsPass()[0] == 'view') || empty($this->ControllerAction->paramsPass())) {
            if ($action == 'Directories') {
                $session->delete('Directory.Directories.guardianToStudent');
            }
            $session->delete('Directory.Directories.studentToGuardian');
        }

        $this->set('contentHeader', $header);
    }

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $getQuery = $this->request->getParam('pass');
        $action = $this->request->getParam('action');

        $furtherAction = $getQuery[0];
        if ($action == 'StudentGuardians' && ($furtherAction == 'view' || $furtherAction == 'edit')) {
            $studentGuardiansID = $this->getQueryString();
            if (!$studentGuardiansID) {
                return;
            }
//            die(print_r($studentGuardiansID, true));
            $StudentGuardians = $this->getDynamicTableInstance('student_guardians');
            $studentGuardiansID = $studentGuardiansID['id'];
            $StudentGuardiansRelationship = $StudentGuardians->get($studentGuardiansID);
            $studentId = $StudentGuardiansRelationship->student_id;
            $students = $this->getDynamicTableInstance('User.Users');
            if (!$studentId) {
                return;
            }
            $student = $students->get($studentId);
            $name = $student->name . ' - ' . __("Student's Guardian");
            $this->set('contentHeader', $name);
            return;
        }
        if ($model instanceof \Staff\Model\Table\StaffClassesTable || $model instanceof \Staff\Model\Table\StaffSubjectsTable) {
            $model->toggle('add', false);
        } else if ($model->getAlias() == 'Guardians') {
            $model->editButtonAction('StudentGuardianUser');
        }
        if ($model instanceof \App\Model\Table\ControllerActionTable) { // CAv4
            $alias = $model->getAlias();
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
        $session = $this->request->getSession();
        if ($session->check('Directory.Directories.id') || $session->check('Directory.Directories.primaryKey.id')) {
            $header = '';
            $userId = $session->read('Directory.Directories.id');
            $userId = $session->read('Directory.Directories.primaryKey.id');

            if (isset($this->request->getParam('pass')[0]) && ($this->request->getParam('pass')[0] == 'view' || $this->request->getParam('pass')[0] == 'edit')) {
                $param = $this->ControllerAction->paramsDecode($this->request->getParam('pass')[1]);
                $id = $param['id'];
                if (isset($param['staff_id']) && !empty($param['staff_id'])) {
                    $id = $param['staff_id'];
                } else if (isset($param['security_user_id']) && !empty($param['security_user_id'])) {
                    $id = $param['security_user_id'];
                }
                $Directories = TableRegistry::getTableLocator()->get('Directory.Directories');
                $entity = $Directories->get($id);
                $header = $entity->name;
            } else if ($session->check('Directory.Directories.name')) {
                $header = $session->read('Directory.Directories.name');
            }

            $alias = $this->request->getParam('action');//$model->getAlias();
            if ($alias == 'ComponentAction') {
                $alias = $model->getAlias();
            }
            //POCOR-5890 starts
            if ($alias == 'HealthImmunizations') {
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
                $this->Navigation->addCrumb($model->getHeader('Guardian' . $alias));
                $header = $session->read('Guardian.Guardians.name');
                $header = $header . ' - ' . $model->getHeader($alias);
            } elseif ($alias !== 'GuardianStudents' && $alias !== 'GuardianStudentUser' && $alias !== 'Directories' && !empty($guardianToStudent)) {
                $this->Navigation->addCrumb($model->getHeader('Student' . $alias));
                $header = $session->read('Student.Students.name');
                $header = $header . ' - ' . $model->getHeader($alias);
            } elseif ($alias == 'StudentAssociations') {
                $header .= ' - ' . __('Houses');
            } else {
                if ($alias == 'StudentClasses' || $alias == 'StudentSubjects') {
                    $alias = substr($alias, 7);
                }
                $this->Navigation->addCrumb($model->getHeader($alias));
                $directoryUrl =  $this->request->getAttribute('params')['pass'][0];
                if($directoryUrl == 'index'){
                    $header = $model->getHeader($alias);
                }else{
                    $header = $header . ' - ' . $model->getHeader($alias);
                }
            }

            $this->set('contentHeader', $header);

            if (!empty($guardianId) && !empty($isStudent) && !empty($studentToGuardian)) {
                $action = $this->request->getAttribute('params')['action'];
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

                if (count($this->request->getParam('pass')) > 2) {
                    $modelId = $this->request->getParam('pass')[1]; // id of the sub model
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
                if (count($this->request->getParam('pass')) > 2) {
                    $modelId = $this->request->getParam('pass')[1]; // id of the sub model
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
                if (count($this->request->getAttribute('params')['pass']) > 2) {
                    $modelId = $this->request->getAttribute('params')['pass'][1]; // id of the sub model

                    $ids = $this->ControllerAction->paramsDecode($modelId);
                    $idKey = $this->ControllerAction->getIdKeys($model, $ids);
                    $idKey[$model->aliasField('student_id')] = $userId;
                    $exists = $model->exists($idKey);
                    $primaryKey = $model->getPrimaryKey();
                    $params = [];
                    if (is_array($primaryKey)) {
                        foreach ($primaryKey as $key) {
                            $params[$model->aliasField($key)] = $ids[$key];
                        }
                    } else {
                        $params[$primaryKey] = $ids[$primaryKey];
                    }

                    $exists = false;

                    if (in_array($model->getAlias(), ['Guardians', 'StudentReportCards', 'Counsellings'])) {//POCOR-7366
                        $params[$model->aliasField('student_id')] = $session->read('Directory.Directories.id');
                        $exists = $model->exists($params);
                    } elseif (in_array($model->getAlias(), ['Students'])) {
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
            if ($model->getAlias() == 'ImportUsers') {
                $this->Navigation->addCrumb($model->getHeader($model->getAlias()));
                $header = __('Users') . ' - ' . $model->getHeader($model->getAlias());
                $this->set('contentHeader', $header);
            } else if ($model->getAlias() == 'UserHistories') {
                $userId = $this->getQueryString('security_user_id');
                $Directories = TableRegistry::getTableLocator()->get('Directory.Directories');
                $entity = $Directories->get($userId);
                $header = $entity->name;
                $pass = $this->ControllerAction->paramsEncode(['id' => $userId]);
                $this->Navigation->addCrumb($header, ['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'view', $pass]);
                $this->Navigation->addCrumb('History');
                $header = $header . ' - History';
                $this->set('contentHeader', $header);
            } else if ($model->getAlias() != 'Directories') {
                $this->Alert->warning('general.notExists');
                $event->stopPropagation();
                return $this->redirect(['plugin' => 'Directory', 'controller' => 'Directories', 'action' => 'Directories', 'index']);
            }
        }
    }

    public function beforeQuery(EventInterface $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    public function beforePaginate(EventInterface $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->getSession();
        if ($model->getAlias() != 'Directories') {
            if ($session->check('Directory.Directories.id') || $session->check('Directory.Directories.primaryKey.id')) {
                $userId = $session->read('Directory.Directories.id');
                if (empty($userId)) {
                    $userId = $session->read('Directory.Directories.primaryKey.id');
                }

                $guardianId = $session->read('Guardian.Guardians.id');
                $studentId = $session->read('Student.Students.id');
                $isGuardian = $session->read('Directory.Directories.is_guardian');
                $studentToGuardian = $session->read('Directory.Directories.studentToGuardian');
                $guardianToStudent = $session->read('Directory.Directories.guardianToStudent');

                if (!empty($studentToGuardian)) {
                    if ($model->hasField('security_user_id')) {
                        $query->where([$model->aliasField('security_user_id') => $guardianId]);
                    } else if ($model->hasField('student_id')) {
                        $query->where([$model->aliasField('student_id') => $guardianId]);
                    }
                } elseif (!empty($guardianToStudent)) {
                    if ($model->hasField('security_user_id')) {
                        $query->where([$model->aliasField('security_user_id') => $studentId]);
                    } else if ($model->hasField('student_id')) {
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
            } else if ($model->getAlias() == 'UserHistories') {

            } else {
                $this->Alert->warning('general.noData');
                $event->stopPropagation();
                return $this->redirect(['action' => 'index']);
            }
        }
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
        if (array_key_exists('queryString', $this->request->getQuery())) { //to filter if the URL already contain querystring
            $id = $this->getQueryString('security_user_id');
        }
        $plugin = $this->getPlugin();
        $name = $this->getName();
        $id = !empty($id) ? $id : ((isset($options['id'])) ? $options['id'] : $this->request->getSession()->read($plugin . '.' . $name . '.id'));

        if (isset($options['userRole']) && $options['userRole'] == 'Guardians' && isset($options['entity'])) {
            $session = $this->request->getSession();
            $id = (isset($options['id'])) ? $options['id'] : $id;
            $session->write('Guardian.Guardians.name', $options['entity']->user->name);
            $session->write('Guardian.Guardians.id', $options['entity']->user->id);
            $session->write('Directory.Directories.studentToGuardian', 'studentToGuardian');
        } elseif (isset($options['userRole']) && $options['userRole'] == 'Students' && isset($options['entity'])) {
            $session = $this->request->getSession();
            $id = (isset($options['id'])) ? $options['id'] : $id;
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
                // } else if ($key == 'Comments') {
                //     $url = [
                //         'plugin' => $plugin,
                //         'controller' => 'DirectoryComments',
                //         'action' => 'index'
                //     ];
                //     $tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url, ['security_user_id' => $id]);
            } else {
                $actionURL = $key;
                if ($key == 'UserNationalities') {
                    $actionURL = 'Nationalities';
                }
                // POCOR-8989 changed from ?queryString to /queryString/ for better compliance
                $tabElements[$key]['url'] = ['plugin' => $plugin,
                    'controller' => $name,
                    'action' => $actionURL,
                    '0' => 'index',
                    '1' => $this->ControllerAction->paramsEncode(['security_user_id' => $id])]
                ;
            }
        }

        if (isset($options['userRole']) && $options['userRole'] == 'Guardians') {
            $session = $this->request->getSession();
            $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
            $relationTabElements = [
                'Guardians' => ['text' => __('Relation')],
                'GuardianUser' => ['text' => __('Overview')]
            ];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $relationTabElements['Guardians']['url'] = array_merge($url, ['action' => 'StudentGuardians', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $relationTabElements['GuardianUser']['url'] = array_merge($url, ['action' => 'StudentGuardianUser', 'view', $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($relationTabElements, $tabElements);
            unset($tabElements[$this->getName()]);
        } elseif (isset($options['userRole']) && $options['userRole'] == 'Students') {
            $session = $this->request->getSession();
            $id = (isset($options['id'])) ? $options['id'] : $id;
            $StudentGuardianId = $session->read('Student.Guardians.primaryKey')['id'];
            $session->write('Directory.Directories.guardianToStudent', 'guardianToStudent');
            $relationTabElements = [
                'Students' => ['text' => __('Relation')],
                'StudentUser' => ['text' => __('Overview')]
            ];
            $url = ['plugin' => 'Directory', 'controller' => 'Directories'];
            $relationTabElements['Students']['url'] = array_merge($url, ['action' => 'GuardianStudents', 'view', $this->paramsEncode(['id' => $StudentGuardianId])]);
            $relationTabElements['StudentUser']['url'] = array_merge($url, ['action' => 'GuardianStudentUser', 'view', $this->paramsEncode(['id' => $id, 'StudentGuardians.id' => $StudentGuardianId])]);
            $tabElements = array_merge($relationTabElements, $tabElements);
            unset($tabElements[$this->getName()]);

            foreach ($tabElements as $key => $value) {
                if ($key == 'Students') {
                    $StudentId = $session->read('Student.Guardians.primaryKey')['id'];
                    $security_user_id = $session->read('Student.Guardians.primaryKey')['security_user_id'];
                    $tabElements[$key]['url'][1] = $this->ControllerAction->paramsEncode(['id' => $StudentId, 'security_user_id' => $security_user_id, 'userRole' => 'Students']);
                }
                if ($key == 'StudentUser' || $key == 'Accounts') {
                    $tabElements[$key]['url'][1] = $this->ControllerAction->paramsEncode(['id' => $id, 'userRole' => 'Students']);
                } else {
                    $url = $tabElements[$key]['url'];
                    $tabElements[$key]['url'] = $this->ControllerAction->setQueryString($url,
                        ['security_user_id' => $id, 'userRole' => 'Students']
                    );
                }
            }
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    // For staff

    public function getStudentGuardianTabElements($options = [])
    {
        $type = (isset($options['type'])) ? $options['type'] : null;
        $plugin = $this->getPlugin();
        $name = $this->getName();
        $tabElements = [
            'Guardians' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentGuardians', 'type' => $type],
                'text' => __('Guardians')
            ],
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }


//    public function getProfessionalTabElements($options = [])
//    {
//
//        $session = $this->request->getSession();
//        $isStudent = $session->read('Directory.Directories.is_student');
//        $isStaff = $session->read('Directory.Directories.is_staff');
//
//        $tabElements = [];
//        $directoryUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
//        $user=0;//POCOR-7528
//        if ($isStaff) {
//            $user=1;//POCOR-7528
//            $professionalTabElements = [
//                'Employments' => ['text' => __('Employments')],
//                'Qualifications' => ['text' => __('Qualifications')],
//                'Extracurriculars' => ['text' => __('Extracurriculars')],
//                'Memberships' => ['text' => __('Memberships')],
//                'Licenses' => ['text' => __('Licenses')],
//                'Awards' => ['text' => __('Awards')],
//            ];
//        } else {
//            $user=0;//POCOR-7528
//            $professionalTabElements = [
//                'Employments' => ['text' => __('Employments')],
//                'Licenses' => ['text' => __('Licenses')],
//            ];
//        }
//        $tabElements = array_merge($tabElements, $professionalTabElements);
//
//        foreach ($professionalTabElements as $key => $tab) {
//            //POCOR-7528 start
//            if($key == 'Licenses'){
//                if($user==1){
//                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' =>'Staff'.$key, 'index']);
//                }
//                else if($user==0){
//                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' =>'Student'.$key, 'index']);
//                }
//            }
//            //POCOR-7528 end
//            else if ($key != 'Employments') {
//                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' => 'Staff'.$key, 'index']);
//            }
//
//            else {
//                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' => $key, 'index']);
//            }
//        }
//        return $this->TabPermission->checkTabPermission($tabElements);
//    }

    public function getGuardianStudentTabElements($options = [])
    {
        // $type = (isset($options['type']))? $options['type']: null;
        $plugin = $this->getPlugin();
        $name = $this->getName();
        $tabElements = [
            'Students' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'GuardianStudents'],
                'text' => __('Students')
            ],
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getFinanceTabElements($options = [])
    {
        $type = (isset($options['type'])) ? $options['type'] : null;
        $plugin = $this->getPlugin();
        $name = $this->getName();
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);

        $tabElements = [];
        $studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $studentTabElements = [
            'BankAccounts' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentBankAccounts', 'index', $encodedQueryString, 'type' => $type],
                'text' => __('Bank Accounts')
            ],
            'StudentFees' => [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'StudentFees', 'index', $encodedQueryString, 'type' => $type],
                'text' => __('Fees')
            ],
        ];

        foreach ($studentTabElements as $key => $tab) {
            $studentTabElements[$key]['url'] = array_merge($studentTabElements[$key]['url'], ['type' => $type]);
        }
        return $this->TabPermission->checkTabPermission($studentTabElements);
    }

    public function getStaffFinanceTabElements($options = [])
    {
        $type = (isset($options['type'])) ? $options['type'] : null;
        $tabElements = [];
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        $staffUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $staffTabElements = [
            'BankAccounts' => ['text' => __('Bank Accounts')],
            'Salaries' => ['text' => __('Salaries')],
            'Payslips' => ['text' => __('Payslips')],
        ];

        $tabElements = array_merge($tabElements, $staffTabElements);

        foreach ($staffTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($staffUrl, ['action' => 'Staff' . $key, 'index', $encodedQueryString, 'type' => $type]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getTrainingTabElements($options = [])
    {
        $tabElements = [];
        $studentUrl = ['plugin' => 'Directory', 'controller' => 'Directories'];
        $queryString = $this->ControllerAction->getQueryString();
        if (empty($queryString)) {
            $queryString = $this->getQueryString();
        }
        $encodedQueryString = $this->ControllerAction->paramsEncode($queryString);
        $studentTabElements = [
            'TrainingNeeds' => ['text' => __('Training Needs')],
            'TrainingResults' => ['text' => __('Training Results')],
            'Courses' => ['text' => __('Courses')],
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);
        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index', $encodedQueryString]);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    /**
     * Gets a unique OpenEMIS ID. POCOR-8231-C4
     *
     * @return \Cake\Http\Response The JSON response containing the unique OpenEMIS ID
     *
     */
    public function getUniqueOpenemisId(): Response
    {
        $result = $this->CreateUsers->getUniqueOpenemisId();
        $this->autoRender = false;
        return $this->response
            ->withType('application/json')
            ->withStringBody($result);


//        return $this->sendJsonResponse($result);
    }

    /**
     * Gets an auto-generated password. POCOR-8231-C4
     *
     * @return \Cake\Http\Response The JSON response containing the auto-generated password
     *
     */
    public function getAutoGeneratedPassword(): Response
    {
        $result = $this->CreateUsers->getAutoGeneratedPassword();
        $this->autoRender = false;
        return $this->response
            ->withType('application/json')
            ->withStringBody($result);
    }

    /**
     * Gets the list of nationalities with identities. POCOR-8231-C4
     *
     * @return \Cake\Http\Response The JSON response containing the list of nationalities
     *
     */
    public function getNationalities(): Response
    {
        $nationalitiesTable = TableRegistry::getTableLocator()->get('FieldOption.Nationalities');
        $nationalities = $nationalitiesTable
            ->find()
            ->leftJoin(['IdentityTypes' => 'identity_types'], [
                $nationalitiesTable->aliasField('identity_type_id') . ' = IdentityTypes.id'
            ])
            ->select([
                'id' => $nationalitiesTable->aliasField('id'),
                'name' => $nationalitiesTable->aliasField('name'),
                'identity_type_id' => $nationalitiesTable->aliasField('identity_type_id'),
                'identity_type_name' => 'IdentityTypes.name'
            ])
            //POCOR-9542[START]
            ->order([
                $nationalitiesTable->aliasField('default') => 'DESC', // default = 1 first
                $nationalitiesTable->aliasField('name') => 'ASC'      // optional: then by name
            ])
            //POCOR-9542[END]
            ->toArray();

        $resultArray = array_map(function ($nationality) {
            return [
                "id" => intval($nationality['id'] ?? 0),
                "name" => !empty($nationality['name']) ? __($nationality['name']) : "",
                "identity_type_id" => $nationality['identity_type_id'] ?? null,
                "identity_type_name" => !empty($nationality['identity_type_name']) ? __($nationality['identity_type_name']) : null,
            ];
        }, $nationalities);

        return $this->sendJsonResponse($resultArray);
    }

    /**
     * Sends a JSON response. POCOR-8231
     *
     * @param array $response The response data
     * @return \Cake\Http\Response The JSON response
     *
     */
    private function sendJsonResponse(array $response): Response
    {
        $this->autoRender = false;
        $json_encoded = json_encode($response, JSON_PRETTY_PRINT);
        return $this->response
            ->withType('application/json')
            ->withStringBody($json_encoded);
    }

    /**
     * Gets the list of identity types. POCOR-8231-C4
     *
     * @return \Cake\Http\Response The JSON response containing the list of identity types
     *
     */
    public function getIdentityTypes(): Response
    {
        $identityTypesTable = $this->getDynamicTableInstance('identity_types');
        $identityTypes = $identityTypesTable
            ->find()
            ->select(['id', 'name', 'validation_pattern'])
            ->order(['`order`'])
            ->toArray();

        $resultArray = array_map(function ($type) {
            return [
                "id" => $type['id'],
                "name" => $type['name'],
                "validation_pattern" => $type['validation_pattern'] ?? '',
            ];
        }, $identityTypes);

        return $this->sendJsonResponse($resultArray);
    }

    /**
     * Gets the list of genders. POCOR-8231-C4
     *
     * @return \Cake\Http\Response The JSON response containing the list of genders
     *
     */
    public function getGenders(): Response
    {
        $gendersTable = $this->getDynamicTableInstance('User.Genders');
        $genders = $gendersTable
            ->find()
            ->select(['id', 'name'])
            ->toArray();

        $resultArray = array_map(function ($gender) {
            return ["id" => $gender['id'], "name" => __($gender['name'])];
        }, $genders);

        return $this->sendJsonResponse($resultArray);
    }

    /** // POCOR-9481
     * Convert gender string to OpenEMIS gender_id
     */
    private function matchGenderId(string $genderName): ?int
    {
        if (!$genderName) {
            return null;
        }

        $genderName = strtolower(trim($genderName));

        // Load genders table
        $GenderTable = $this->getDynamicTableInstance('User.Genders');

        $genders = $GenderTable
            ->find()
            ->select(['id', 'name'])
            ->toArray();

        foreach ($genders as $g) {
            if (strtolower($g['name']) === $genderName) {
                return $g['id'];
            }
        }

        // fallback: return null (OpenEMIS will handle)
        return null;
    }
    /**
     * Resolve nationality name to nationality_id.
     * If nationality does not exist → create it.
     * POCOR-9481
     */
    private function matchOrCreateNationalityId(?string $nationalityName): ?int
    {
        if (empty($nationalityName)) {
            return null;
        }

        $nationalityName = trim($nationalityName);
        if ($nationalityName === '') {
            return null;
        }

        // Table instances
        $NationalitiesTable = $this->getDynamicTableInstance('FieldOption.Nationalities');
        $UserNationalitiesTable = $this->getDynamicTableInstance('user_nationalities');

        // ------------------------------------------------------------
        // 1. SEARCH for existing nationality (case-insensitive)
        // ------------------------------------------------------------
        $existing = $NationalitiesTable
            ->find()
            ->where(['LOWER(name) =' => strtolower($nationalityName)])
            ->first();

        if ($existing) {
            return (int)$existing->id;
        }

        // ------------------------------------------------------------
        // 2. CREATE new nationality
        // ------------------------------------------------------------
        $new = $NationalitiesTable->newEntity([
            'name' => $nationalityName,
            'visible' => 1,
            'editable' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

        if (!$NationalitiesTable->save($new)) {
            Log::error("Failed to create nationality: {$nationalityName}");
            return null;
        }

//        Log::debug("Created new nationality: {$nationalityName} → ID {$new->id}");

        return (int)$new->id;
    }

    /**
     * Gets the list of user types. POCOR-8231-C4
     *
     * @return \Cake\Http\Response The JSON response containing the list of user types
     *
     */
    public function getUserType(): Response
    {
        $userTypeOptions = [
            self::STAFF => __('Staff'),
            self::STUDENT => __('Students'),
            self::GUARDIAN => __('Guardians'),
            self::OTHER => __('Others')
        ];

        $resultArray = array_map(function ($key, $val) {
            return ["id" => $key, "name" => $val];
        }, array_keys($userTypeOptions), $userTypeOptions);

        return $this->sendJsonResponse($resultArray);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        //for api purpose POCOR-5672 starts
        if ($this->request->getParam('action') == 'directoryInternalSearch') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'directoryInternalSearch';
        }
        if ($this->request->getParam('action') == 'directoryExternalSearch') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'directoryExternalSearch';
        }
        if ($this->request->getParam('action') == 'getContactType') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getContactType';
        }
        if ($this->request->getParam('action') == 'getRedirectToGuardian') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getRedirectToGuardian';
        }
        if ($this->request->getParam('action') == 'getRelationshipType') {
            $events['Controller.SecurityAuthorize.isActionIgnored'] = 'getRelationshipType';
        }//for api purpose POCOR-5672 ends
        return $events;
    }

    public function isActionIgnored(EventInterface $event, $action)
    {
        $pass = $this->request->getAttribute('params')['pass'];
        if (isset($pass[0]) && $pass[0] == 'downloadFile') {
            return true;
        }
    }

    //POCOR-7072 starts

    /**
     * Performs an internal directory search. POCOR-8231
     *
     * @return \Cake\Http\Response The JSON response containing the search results
     *
     */
    public function directoryInternalSearch(): Response
    {
//        if($this->searchingAJAX > 0){
        $this->searchingAJAX = $this->searchingAJAX + 1;
        if ($this->searchingAJAX < 2) {
            return $this->sendJsonResponse(['searching' => $this->searchingAJAX]);
        }
//        }
        $this->searchingAJAX = 1;
        $Directories = $this->getDynamicTableInstance('Directory.Directories');

        // Read JSON input
        $requestData = $this->getRequestData();

        // Get the internal search results
        $internalSearchResults = $Directories::getUserInternalSearch($requestData);
//        Log::debug(__FUNCTION__);
//        Log::debug(print_r($internalSearchResults['data'][0], true));
//        $internalSearchResults = ['mama' => $internalSearchResults['data'][0]];
        // Set response type and body
        return $this->sendJsonResponse($internalSearchResults);
    }

    /**
     * Gets the request data. POCOR-8231
     *
     * @return array The request data
     *
     */
    private function getRequestData(): array
    {
        $requestData = $this->request->input('json_decode', true);
        return $requestData['params'] ?? $requestData;
    }//POCOR-7072 ends

    public function getStaffCustomData($staff_id = null)
    {
        $staffCustomFieldValues = TableRegistry::getTableLocator()->get('StaffCustomField.StaffCustomFieldValues');
        $staffCustomFieldOptions = TableRegistry::getTableLocator()->get('StaffCustomField.StaffCustomFieldOptions');
        $staffCustomFields = TableRegistry::getTableLocator()->get('StaffCustomField.StaffCustomFields');
        $staffCustomData = $staffCustomFieldValues->find()
            ->select([
                'id' => $staffCustomFieldValues->aliasField('id'),
                'custom_id' => 'staffCustomField.id',
                'staff_id' => $staffCustomFieldValues->aliasField('staff_id'),
                'staff_custom_field_id' => $staffCustomFieldValues->aliasField('staff_custom_field_id'),
                'text_value' => $staffCustomFieldValues->aliasField('text_value'),
                'number_value' => $staffCustomFieldValues->aliasField('number_value'),
                'decimal_value' => $staffCustomFieldValues->aliasField('decimal_value'),
                'textarea_value' => $staffCustomFieldValues->aliasField('textarea_value'),
                'date_value' => $staffCustomFieldValues->aliasField('date_value'),
                'time_value' => $staffCustomFieldValues->aliasField('time_value'),
                'option_value_text' => $staffCustomFieldOptions->aliasField('name'),
                'name' => 'staffCustomField.name',
                'field_type' => 'staffCustomField.field_type',
            ])->leftJoin(
                ['staffCustomField' => 'staff_custom_fields'],
                [
                    'staffCustomField.id = ' . $staffCustomFieldValues->aliasField('staff_custom_field_id')
                ])
            ->leftJoin(
                [$staffCustomFieldOptions->getAlias() => $staffCustomFieldOptions->getgetTable()],
                [
                    $staffCustomFieldOptions->aliasField('staff_custom_field_id = ') . $staffCustomFieldValues->aliasField('staff_custom_field_id'),
                    $staffCustomFieldOptions->aliasField('id = ') . $staffCustomFieldValues->aliasField('number_value')
                ])
            ->where([
                $staffCustomFieldValues->aliasField('staff_id') => $staff_id,
            ])->enableHydration(false)->toArray();
        $custom_field = array();
        $count = 0;
        if (!empty($staffCustomData)) {
            foreach ($staffCustomData as $val) {
                $custom_field['custom_field'][$count]["id"] = (!empty($val['custom_id']) ? $val['custom_id'] : '');
                $custom_field['custom_field'][$count]["name"] = (!empty($val['name']) ? $val['name'] : '');
                $fieldTypes[$count] = (!empty($val['field_type']) ? $val['field_type'] : '');
                $fieldType = $fieldTypes[$count];
                if ($fieldType == 'TEXT') {
                    $custom_field['custom_field'][$count]["text_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                } else if ($fieldType == 'CHECKBOX') {
                    $custom_field['custom_field'][$count]["checkbox_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'NUMBER') {
                    $custom_field['custom_field'][$count]["number_value"] = (!empty($val['number_value']) ? $val['number_value'] : '');
                } else if ($fieldType == 'DECIMAL') {
                    $custom_field['custom_field'][$count]["decimal_value"] = (!empty($val['decimal_value']) ? $val['decimal_value'] : '');
                } else if ($fieldType == 'TEXTAREA') {
                    $custom_field['custom_field'][$count]["textarea_value"] = (!empty($val['textarea_value']) ? $val['textarea_value'] : '');
                } else if ($fieldType == 'DROPDOWN') {
                    $custom_field['custom_field'][$count]["dropdown_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'DATE') {
                    $custom_field['custom_field'][$count]["date_value"] = date('Y-m-d', strtotime($val->date_value));
                } else if ($fieldType == 'TIME') {
                    $custom_field['custom_field'][$count]["time_value"] = date('h:i A', strtotime($val->time_value));
                } else if ($fieldType == 'COORDINATES') {
                    $custom_field['custom_field'][$count]["cordinate_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                }
                $count++;
            }
        }
        return $custom_field;
    }

    public function getStudentCustomData($student_id = null)
    {
        $studentCustomFieldValues = TableRegistry::getTableLocator()->get('StudentCustomField.StudentCustomFieldValues');
        $studentCustomFieldOptions = TableRegistry::getTableLocator()->get('StudentCustomField.StudentCustomFieldOptions');
        $studentCustomFields = TableRegistry::getTableLocator()->get('StudentCustomField.StudentCustomFields');
        $studentCustomData = $studentCustomFieldValues->find()
            ->select([
                'id' => $studentCustomFieldValues->aliasField('id'),
                'custom_id' => 'studentCustomField.id',
                'student_id' => $studentCustomFieldValues->aliasField('student_id'),
                'student_custom_field_id' => $studentCustomFieldValues->aliasField('student_custom_field_id'),
                'text_value' => $studentCustomFieldValues->aliasField('text_value'),
                'number_value' => $studentCustomFieldValues->aliasField('number_value'),
                'decimal_value' => $studentCustomFieldValues->aliasField('decimal_value'),
                'textarea_value' => $studentCustomFieldValues->aliasField('textarea_value'),
                'date_value' => $studentCustomFieldValues->aliasField('date_value'),
                'time_value' => $studentCustomFieldValues->aliasField('time_value'),
                'option_value_text' => $studentCustomFieldOptions->aliasField('name'),
                'name' => 'studentCustomField.name',
                'field_type' => 'studentCustomField.field_type',
            ])->leftJoin(
                ['studentCustomField' => 'student_custom_fields'],
                [
                    'studentCustomField.id = ' . $studentCustomFieldValues->aliasField('student_custom_field_id')
                ])
            ->leftJoin(
                [$studentCustomFieldOptions->getAlias() => $studentCustomFieldOptions->getTable()],
                [
                    $studentCustomFieldOptions->aliasField('student_custom_field_id = ') . $studentCustomFieldValues->aliasField('student_custom_field_id'),
                    $studentCustomFieldOptions->aliasField('id = ') . $studentCustomFieldValues->aliasField('number_value')
                ])
            ->where([
                $studentCustomFieldValues->aliasField('student_id') => $student_id,
            ])
            ->disableHydration() // POCOR-8533
            ->toArray();
        $custom_field = array();
        $count = 0;
        if (!empty($studentCustomData)) {
            foreach ($studentCustomData as $val) {
                $custom_field['custom_field'][$count]["id"] = (!empty($val['custom_id']) ? $val['custom_id'] : '');
                $custom_field['custom_field'][$count]["name"] = (!empty($val['name']) ? $val['name'] : '');
                $fieldTypes[$count] = (!empty($val['field_type']) ? $val['field_type'] : '');
                $fieldType = $fieldTypes[$count];
                if ($fieldType == 'TEXT') {
                    $custom_field['custom_field'][$count]["text_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                } else if ($fieldType == 'CHECKBOX') {
                    $custom_field['custom_field'][$count]["checkbox_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'NUMBER') {
                    $custom_field['custom_field'][$count]["number_value"] = (!empty($val['number_value']) ? $val['number_value'] : '');
                } else if ($fieldType == 'DECIMAL') {
                    $custom_field['custom_field'][$count]["decimal_value"] = (!empty($val['decimal_value']) ? $val['decimal_value'] : '');
                } else if ($fieldType == 'TEXTAREA') {
                    $custom_field['custom_field'][$count]["textarea_value"] = (!empty($val['textarea_value']) ? $val['textarea_value'] : '');
                } else if ($fieldType == 'DROPDOWN') {
                    $custom_field['custom_field'][$count]["dropdown_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                } else if ($fieldType == 'DATE') {
                    $custom_field['custom_field'][$count]["date_value"] = date('Y-m-d', strtotime($val->date_value));
                } else if ($fieldType == 'TIME') {
                    $custom_field['custom_field'][$count]["time_value"] = date('h:i A', strtotime($val->time_value));
                } else if ($fieldType == 'COORDINATES') {
                    $custom_field['custom_field'][$count]["cordinate_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                }
                $count++;
            }
        }
        return $custom_field;
    }

    public function getCountInernalSearch($conditions = [], $identityNumber, $identityCondition = [], $userTypeCondition = [])
    {
        $security_users = TableRegistry::getTableLocator()->get('Security.Users');
        $userIdentities = TableRegistry::getTableLocator()->get('User.Identities');
        $genders = TableRegistry::getTableLocator()->get('User.Genders');
        $mainIdentityTypes = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $mainNationalities = TableRegistry::getTableLocator()->get('User.Nationalities');
        if ($identityNumber == '') {
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
                    'Genders_id' => $genders->aliasField('id'),
                    'Genders_name' => $genders->aliasField('name'),
                    'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id' => $mainNationalities->aliasField('id'),
                    'MainNationalities_name' => $mainNationalities->aliasField('name'),
                ])
                ->LeftJoin(['Identities' => 'user_identities'], [
                    'Identities.security_user_id' => $security_users->aliasField('id'),
                ])
                ->LeftJoin([$genders->getAlias() => $genders->getTable()], [
                    $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                ])
                ->LeftJoin([$mainIdentityTypes->getAlias() => $mainIdentityTypes->getTable()], [
                    $mainIdentityTypes->aliasField('id =') . $security_users->aliasField('identity_type_id')
                ])
                ->LeftJoin([$mainNationalities->getAlias() => $mainNationalities->getTable()], [
                    $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                ])
                ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $conditions])
                ->group([$security_users->aliasField('id')])
                ->count();
        } else {
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
                    'Genders_id' => $genders->aliasField('id'),
                    'Genders_name' => $genders->aliasField('name'),
                    'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                    'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                    'MainNationalities_id' => $mainNationalities->aliasField('id'),
                    'MainNationalities_name' => $mainNationalities->aliasField('name'),
                ])
                ->InnerJoin([$userIdentities->getAlias() => $userIdentities->getTable()], [
                    $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
                    $identityCondition
                ])
                ->LeftJoin([$genders->getAlias() => $genders->getTable()], [
                    $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                ])
                ->LeftJoin([$mainIdentityTypes->getAlias() => $mainIdentityTypes->getTable()], [
                    $mainIdentityTypes->aliasField('id =') . $security_users->aliasField('identity_type_id')
                ])
                ->LeftJoin([$mainNationalities->getAlias() => $mainNationalities->getTable()], [
                    $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                ])
                ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $userTypeCondition])
                ->group([$security_users->aliasField('id')])
                ->count();
            if ($get_result_by_identity_users_result == 0) {
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
                        'Genders_id' => $genders->aliasField('id'),
                        'Genders_name' => $genders->aliasField('name'),
                        'MainIdentityTypes_id' => $mainIdentityTypes->aliasField('id'),
                        'MainIdentityTypes_name' => $mainIdentityTypes->aliasField('name'),
                        'MainNationalities_id' => $mainNationalities->aliasField('id'),
                        'MainNationalities_name' => $mainNationalities->aliasField('name'),
                    ])
                    ->InnerJoin([$userIdentities->getAlias() => $userIdentities->getTable()], [
                        $userIdentities->aliasField('security_user_id =') . $security_users->aliasField('id'),
                        $identityCondition
                    ])
                    ->LeftJoin([$genders->getAlias() => $genders->getTable()], [
                        $genders->aliasField('id =') . $security_users->aliasField('gender_id')
                    ])
                    ->LeftJoin([$mainIdentityTypes->getAlias() => $mainIdentityTypes->getTable()], [
                        $mainIdentityTypes->aliasField('id =') . $security_users->aliasField('identity_type_id')
                    ])
                    ->LeftJoin([$mainNationalities->getAlias() => $mainNationalities->getTable()], [
                        $mainNationalities->aliasField('id =') . $security_users->aliasField('nationality_id')
                    ])
                    ->where([$security_users->aliasField('super_admin') . ' <> ' => 1, $conditions])
                    ->group([$security_users->aliasField('id')])
                    ->count();
            } else {
                $security_users_result = $get_result_by_identity_users_result;
            }
        }
        //POCOR-5672 ends
        return $security_users_result;
    }

    /**
     * Performs an external directory search. POCOR-8231
     *
     * @return \Cake\Http\Response The JSON response containing the search results
     * @throws \Exception If there is an error during the search process
     *
     */
    public function directoryExternalSearch(): Response
    {
        $requestInput = $this->getRequestData();
        $params = $requestInput['params'] ?? $requestInput;
//        Log::debug(print_r([__FUNCTION__ => $params], true)); // POCOR-9481
        $firstName = $params['first_name'] ?? null;
        $lastName = $params['last_name'] ?? null;
        $openemisNo = $params['openemis_no'] ?? null;
        $identityNumber = $params['identity_number'] ?? null;
        $nationalityID = $params['nationality_id'] ?? null;
        $dateOfBirth = !empty($params['date_of_birth']) ? date('Y-m-d', strtotime($params['date_of_birth'])) : null;
        $limit = $params['limit'] ?? 10;
        $page = $params['page'] ?? 1;
        $id = $params['id'] ?? '';
        $searchType = $params['search_type'] ?? '';
        //POCOR-9590: 'Seychellois' is an alias for 'Seychelles Civil Status' — normalize before routing
        if ($searchType === 'Seychellois') {
            $searchType = 'Seychelles Civil Status';
        }

        $ExternalAttributes = $this->getDynamicTableInstance('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalAttributes
            ->find('list', [ // POCOR-9481
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->innerJoin(['ConfigItems' => 'config_items'], [
                'ConfigItems.type' => 'External Data Source - Identity',
                $ExternalAttributes->aliasField('external_data_source_type') . ' = ConfigItems.label'
            ])
            ->where('ConfigItems.label = "' . $searchType . '"') // POCOR-9481
            ->toArray();
//        Log::debug(print_r([__FUNCTION__ => $attributes], true));
//        Log::debug(print_r([__FUNCTION__ => $searchType], true));


        $noData = json_encode(['data' => [], 'total' => 0]);
        try {
            if ($searchType === 'UNHCR') {
                $response = $this->getUNHCRData($attributes, $noData, $identityNumber, $dateOfBirth);
            } elseif ($searchType === 'Seychelles Civil Status') { // POCOR-9481
                $response = $this->getSeychellesData($attributes, $noData, $identityNumber, $dateOfBirth);
            } elseif ($searchType === 'OpenEMIS Core') {
//                $response = ['data' => ['first_name' => 'Pablo']];

                $response = $this->getOECoreData($attributes, $noData, $identityNumber);
            } else {
                $response = $this->getTokenedData($attributes, $identityNumber, $noData, $id);
            }
        } catch (\Exception $exception) {
            return $this->sendJsonResponse(['error' => $exception->getMessage()]);
        }

        return $this->sendJsonResponse($response);
    }

    /**
     * Fetches data from the UNHCR API. POCOR-8231
     *
     * @param array $attributes External data source attributes
     * @param string $noData JSON string for empty data response
     * @param string $identityNumber Identity number for the search
     * @param string $dateOfBirth Date of birth for the search
     * @return array Data fetched from UNHCR API or no data message
     * @throws \Exception If there is an error during the data fetching process
     *
     */
    private function getUNHCRData($attributes, $noData, $identityNumber, $dateOfBirth): array
    {
        $applicationId = $attributes['application_id'];
        $apiKey = $attributes['secret_code'];
        $url = $attributes['url'];
        $tokenUri = $url . "login";
        $userDataUri = $url . "validate/identity-number";

        $tokenRequestBody = [
            'api_key' => $apiKey
        ];

        $headers = [
            'Authorization' => 'Basic ' . $applicationId,
            'Content-Type' => 'application/json'
        ];

        $http = new \Cake\Http\Client();
        $response = $http->post($tokenUri, json_encode($tokenRequestBody), ['headers' => $headers]);

        $decodedResponse = $response->getJson();
        $responseData = json_decode($noData, true);

        if ($response->isOk() && isset($decodedResponse['data']['token'])) {
            $token = $decodedResponse['data']['token'];

            $headers = [
                'token' => $token,
                'Content-Type' => 'application/json'
            ];

            $userRequestBody = json_encode([
                "identity_number" => $identityNumber,
                "date_of_birth" => $dateOfBirth
            ]);

            $response = $http->post($userDataUri,
                $userRequestBody, [
                    'headers' => $headers,
                    'type' => 'json'
                ]);

            $decodedResponse = $response->getJson();
            if ($response->isOk() && isset($decodedResponse['result'])) {
                $answer = [];
                if ($decodedResponse['result']) {
                    $answer['identity_number'] = $identityNumber;
                }
                $responseData = ['data' => [$answer]];
            }
        }

        return $responseData;
    }

    /**
     * Fetches Seychelles Civil Status data using OAuth2 client_credentials.
     * POCOR-9481
     *
     * @param array $attributes External data source attributes
     * @param string $noData JSON string for empty data response
     * @param string $identityNumber National Identification Number (NIN)
     * @param string|null $dateOfBirth (Not required for Seychelles but kept for consistency)
     * @return array
     * @throws \Exception
     *
     */
    private function getSeychellesData(array $attributes, string $noData, string $identityNumber, ?string $dateOfBirth = null): array
    {
        $responseData = json_decode($noData, true);

        // Basic config
        $clientId  = $attributes['client_id'];
        $secret    = $attributes['client_secret'];
        $tokenUri  = rtrim($attributes['token_uri'], '/');
        $apiUrl    = rtrim($attributes['api_url'], '/');
        $grantType = $attributes['grant_type'];
        $scopes    = $attributes['scopes'];

        //POCOR-9590: restore prev lenient Seychelles mapping — the source returns keys with
        //different casing between its test and production payloads, so normalize keys and
        //default to the well-known Seychelles field names when a mapping is not configured.
        //(The strict ExternalIdentityMapper consolidation dropped first_name/last_name here.)
        $mapFirst       = strtolower(trim($attributes['first_name_mapping']     ?? 'givennames'));
        $mapLast        = strtolower(trim($attributes['last_name_mapping']      ?? 'presentsurname'));
        $mapFull        = strtolower(trim($attributes['full_name_mapping']      ?? 'fullname'));
        $mapDob         = strtolower(trim($attributes['date_of_birth_mapping']  ?? 'dob'));
        $mapGender      = strtolower(trim($attributes['gender_mapping']         ?? 'sex'));
        $mapNationality = strtolower(trim($attributes['nationality_mapping']    ?? 'nationality'));

        // ------------------------------------------------------------
        // TOKEN REQUEST
        // ------------------------------------------------------------
        $http = new \Cake\Http\Client();

        $tokenRequestBody = [
            'grant_type'    => $grantType,
            'client_id'     => $clientId,
            'client_secret' => $secret,
            'scope'         => $scopes
        ];

        $tokenResponse = $http->post($tokenUri, $tokenRequestBody, [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']
        ]);

        $decodedToken = $tokenResponse->getJson();

        if (!$tokenResponse->isOk() || empty($decodedToken['access_token'])) {
            return $responseData;
        }

        $accessToken = $decodedToken['access_token'];

        // ------------------------------------------------------------
        // FETCH USER DATA
        // ------------------------------------------------------------
        $ninEndpoint = $apiUrl . "/" . $identityNumber;

        $userResponse = $http->get($ninEndpoint, [], [
            'headers' => ['Authorization' => "Bearer {$accessToken}", 'Accept' => 'application/json']
        ]);

        $payload = $userResponse->getJson();

        $record = $payload['record'] ?? $payload;
        if (!$userResponse->isOk() || empty($record)) {
            return $responseData;
        }

        //POCOR-9590: restore prev lenient mapping — normalize payload keys to lowercase
        //then read via the (lowercased) configured/default mapping keys.
        $raw = $this->normalizeKeys($record);
        $mapped = [
            'identity_number' => $identityNumber,
            'first_name'      => $raw[$mapFirst] ?? '',
            'last_name'       => $raw[$mapLast] ?? '',
            'full_name'       => $raw[$mapFull] ?? '',
            'date_of_birth'   => isset($raw[$mapDob]) ? substr((string)$raw[$mapDob], 0, 10) : '',
            'gender'          => $raw[$mapGender] ?? '',
            'nationality'     => $raw[$mapNationality] ?? '',
        ];

        if (empty($mapped['full_name'])) {
            $mapped['full_name'] = trim(($mapped['first_name'] ?? '') . ' ' . ($mapped['last_name'] ?? ''));
        }

        $mapped['gender_id']      = $this->matchGenderId($mapped['gender']);
        $mapped['nationality_id'] = $this->matchOrCreateNationalityId($mapped['nationality']);

        if (isset($raw['postaladdress1'])) {
            $mapped['address'] = $raw['postaladdress1'];
        }
        if (isset($raw['district'])) {
            $mapped['district'] = $raw['district'];
        }

//        Log::debug(print_r(['SeychellesMapped' => $mapped], true));

        return [
            'data'  => [$mapped],
            'total' => 1
        ];
    }

    //POCOR-9590: restore prev helper — lowercases every payload key so mapping is
    //case-insensitive (the external source varies key casing between test and production).
    private function normalizeKeys(array $arr): array
    {
        $normalized = [];
        foreach ($arr as $key => $value) {
            $normalized[strtolower((string)$key)] = $value;
        }
        return $normalized;
    }

    //POCOR-5673 starts

    /**
     * Fetches data from the OpenEMIS Core API v5. POCOR-8872
     *
     * @param array $attributes External data source attributes
     * @param string $noData JSON string for empty data response
     * @param string $identityNumber Identity number for the search
     * @return array Data fetched from UNHCR API or no data message
     * @throws \Exception If there is an error during the data fetching process
     *
     */
    private function getOECoreData(array $attributes, string $noData, string $identityNumber): array
    {
//        Log::debug(print_r(['getOECoreData' => $attributes], true));
        $username = $attributes['username'];
        $password = $attributes['password'];
        $apiKey = $attributes['api_key'];
        $url = $attributes['api_url'];
        $tokenUri = rtrim($url, '/'); // POCOR-9071
        $tokenUri = $url . "/login";
        $userDataEndpoint = $url . "/security-users?openemis_no="; // POCOR-9071


        $tokenRequestBody = [
            'username' => $username,
            'password' => $password,
            'api_key' => $apiKey
        ];
        $headers = [
            'Content-Type' => 'application/json'
        ];

        $http = new \Cake\Http\Client();
        $response = $http->post($tokenUri, json_encode($tokenRequestBody), ['headers' => $headers]);

        $decodedResponse = $response->getJson();
//        Log::debug(print_r(['getOECoreData' => $decodedResponse], true));
        $responseData = json_decode($noData, true);
//        Log::debug(print_r([$response->isOk() => $decodedResponse['data'],
//            $tokenUri => $tokenRequestBody], true));

        if ($response->isOk() && isset($decodedResponse['data']['token'])) {
            $token = $decodedResponse['data']['token'];
            $headers = [
                'authorization' => "Bearer " . $token,
                'type' => 'application/json'
            ];
//            Log::debug(print_r(['getOECoreHeaders' => $headers], true));
            $userDataUri = $userDataEndpoint . $identityNumber;
//            $userDataUri = $userDataUri . "&_fields=first_name,middle_name,third_name,last_name,identity_number,date_of_birth,gender_id,openemis_no";
//            Log::debug(print_r(['getOECoreUserDataUri' => $userDataUri, 'token' => $token], true));
            $response = $http->get($userDataUri,
                ['openemis_no' => $identityNumber], [ // POCOR-9071
                'headers' => $headers
            ]);

            $decodedResponse = $response->getJson();
            $decodedData = $decodedResponse['data'] ?? [];
            if(isset($decodedResponse['data']['data'])) { // POCOR-9071
                $decodedData = $decodedResponse['data']['data'];
            }
            if ($response->isOk() && isset($decodedData)) {
//                Log::debug(print_r(['getOECoreDecodedData' => $decodedData], true));
                $responseData = ['data' => [], 'total' => 0];
                if (!empty($decodedData)) {
                    foreach ($decodedData as &$answer) {
                        $answer['identity_number'] = $answer['openemis_no'];
                        unset($answer['openemis_no']);
                    }
                    $responseData = ['data' => $decodedData, 'total' => count($decodedData)];
                }

            } else {
                $responseData = ['data' => [], 'total' => 0];
            }
        }
//        Log::debug(print_r(['responseData' => $responseData], true));

        return $responseData;
    }

    /**
     * Fetches data using token-based authorization. POCOR-8231
     *
     * @param array $attributes External data source attributes
     * @param string $identityNumber Identity number for the search
     * @param string $noData JSON string for empty data response
     * @param string|null $id Optional ID for specific record search
     * @return array Data fetched from external source or no data message
     * @throws \Exception If there is an error during the data fetching process
     *
     */
    private function getTokenedData($attributes, $identityNumber, $noData, $id = null): array
    {
        $ExternalAttributes = $this->getDynamicTableInstance('Configuration.ExternalDataSourceAttributes');
        $attributes = $ExternalAttributes
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->innerJoin(['ConfigItems' => 'config_items'], [
                'ConfigItems.code' => 'external_data_source_type',
                $ExternalAttributes->aliasField('external_data_source_type') . ' = ConfigItems.value'
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

        $requestData = $this->getRequestData();
        $fieldMapping = [
            '{page}' => $requestData['page'] ?? 1,
            '{limit}' => $requestData['limit'] ?? 10,
            '{first_name}' => $requestData['first_name'] ?? '',
            '{last_name}' => $requestData['last_name'] ?? '',
            '{date_of_birth}' => isset($requestData['date_of_birth']) ? date('Y-m-d', strtotime($requestData['date_of_birth'])) : '',
            '{identity_number}' => $identityNumber ?? $requestData['identity_number'] ?? ''
        ];

        $http = new Client();
        $response = $http->post($tokenUri, $data);

        $decodedResponse = $response->getJson();
        if ($response->isOk()) {
            $recordUri = $attributes['record_uri'];

            foreach ($fieldMapping as $key => $map) {
                $recordUri = str_replace($key, $map, $recordUri);
            }

            $http = new Client([
                'headers' => ['Authorization' => $decodedResponse['token_type'] . ' ' . $decodedResponse['access_token']]
            ]);

            $response = $http->get($recordUri);
            $decodedResponse = $response->getJson();

            $responseData = $response->isOk() ? $decodedResponse : json_decode($noData, true);
        } else {
            $responseData = json_decode($noData, true);
        }

        if (!empty($id)) {
            $singleUserData = array_filter($responseData['data'], fn($value) => $value['id'] == $id);
            $responseData = ['data' => $singleUserData];
        }

        return $responseData;
    }

    /**
     * Retrieves and returns contact types with their options. POCOR-8231
     *
     * @return \Cake\Http\Response|null
     *
     */
    public function getContactType(): ?Response
    {
        $contactTypes = $this->getDynamicTableInstance('contact_types');
        $contactOptions = $this->getDynamicTableInstance('contact_options');

        $contactTypesQuery = $contactTypes
            ->find()
            ->innerJoin(
                [$contactOptions->getAlias() => $contactOptions->getTable()],
                $contactOptions->aliasField('id') . ' = ' . $contactTypes->aliasField('contact_option_id')
            )
            ->select([
                'id' => $contactTypes->aliasField('id'),
                'name' => $contactTypes->aliasField('name'),
                'contact_option' => $contactOptions->aliasField('name')
            ])
            ->orderAsc($contactOptions->aliasField('order'))
            ->orderAsc($contactTypes->aliasField('order'));
//            Log::debug($contactTypesQuery->sql());
        $contactTypesResult = $contactTypesQuery->toArray();
        $resultArray = array_map(function ($result) {
            return [
                'id' => $result['id'],
                'name' => $result['contact_option'] . ' (' . $result['name'] . ')'
            ];
        }, $contactTypesResult);

        return $this->sendJsonResponse($resultArray);
    }//POCOR-5673 ends

    public function getRedirectToGuardian()
    {
        $config_items = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $config_items_result = $config_items
            ->find()
            ->where(['code' => 'RedirectToGuardian'])
            ->toArray();
        $res = false;
        foreach ($config_items_result AS $result) {
            if ($result['value'] == 1) {
                $res = true;
            }
            $result_array[] = array("redirecttoguardian_status" => $res);
        }
        echo json_encode($result_array);
        die;
    }

    public function getRelationshipType()
    {
        $resultArray = [];
        $guardian_relations = $this->getDynamicTableInstance('Student.GuardianRelations');
        $guardian_relations_result = $guardian_relations
            ->find()
            ->where(['visible' => 1])
            ->order([$guardian_relations->aliasField('order') => 'ASC'])
            ->disableHydration()
            ->toArray();
        $resultArray = array_map(function ($result) {
            return [
                'id' => $result['id'],
                'name' => __($result['name'])
            ];
        }, $guardian_relations_result);
        return $this->sendJsonResponse($resultArray);
    }

    /*POCOR-6286 starts - registering functions*/

    public function StudentAbsences()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Absences']);
    }

    public function Absences()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Absences']);
    }
    /*POCOR-6286 ends*/


    /*POCOR-6700 start - registering function*/

    public function StaffProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.StaffProfiles']);
    }

    /*POCOR-6700 ends*/

    public function StudentProfiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.StudentProfiles']);
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */

    public function StudentExtracurriculars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.Extracurriculars']);
    }

    //POCOR-8596
    public
    function StudentBehaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Student.StudentBehaviours']);
    }

}
