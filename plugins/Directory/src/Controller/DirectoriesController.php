<?php
namespace Directory\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use App\Controller\AppController;

class DirectoriesController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->ControllerAction->models = [
            // Users
            'Accounts'              => ['className' => 'Directory.Accounts', 'actions' => ['view', 'edit']],

            // Student
            'StudentAbsences'       => ['className' => 'Student.Absences', 'actions' => ['index', 'view']],
            'StudentBehaviours'     => ['className' => 'Student.StudentBehaviours', 'actions' => ['index', 'view']],
            'StudentExtracurriculars' => ['className' => 'Student.Extracurriculars'],

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
        $this->loadComponent('User.Image');
        $this->attachAngularModules();

        $this->set('contentHeader', 'Directories');
    }

    public function Directories()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Directory.Directories']);
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
    public function StaffLicenses()
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

                    if (in_array($model->alias(), ['Guardians'])) {
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

        if ($isStaff) {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
                'Qualifications' => ['text' => __('Qualifications')],
                'Extracurriculars' => ['text' => __('Extracurriculars')],
                'Memberships' => ['text' => __('Memberships')],
                'Licenses' => ['text' => __('Licenses')],
                'Awards' => ['text' => __('Awards')],
            ];
        } else {
            $professionalTabElements = [
                'Employments' => ['text' => __('Employments')],
            ];
        }
        $tabElements = array_merge($tabElements, $professionalTabElements);

        foreach ($professionalTabElements as $key => $tab) {
            if ($key != 'Employments') {
                $tabElements[$key]['url'] = array_merge($directoryUrl, ['action' => 'Staff'.$key, 'index']);
            } else {
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
}
