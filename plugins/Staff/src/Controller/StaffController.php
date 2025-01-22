<?php

namespace Staff\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Event\EventInterface;//POCOR-8456

class StaffController extends AppController
{
    const APPROVED = 1;
    private $features = [
        // General
        'Identities',
        'UserNationalities',
        'Contacts',
        'UserLanguages',
        'Attachments',
        'Comments',

        // academic
        'EmploymentStatuses',
        'StaffClasses',
        'StaffSubjects',
        'Awards',
        'Memberships',
        'Licenses',

        // qualification
        'Employments',
        'Qualifications',
        'Extracurriculars',

        // finance
        'BankAccounts',
        'Salaries',
        'Payslips',

        // training
        'StaffTrainings',

        // health
        'Healths',
        'Allergies',
        'Consultations',
        'Families',
        'Histories',
        'Immunizations',
        'Medications',
        'Tests',

        // staff attendances
        'StaffAttendances',

        // special needs
        'SpecialNeedsReferrals',
        'SpecialNeedsAssessments',
        'SpecialNeedsServices',
        'SpecialNeedsDevices',
        'SpecialNeedsPlans',
        'SpecialNeedsDiagnostics'
    ];

    public function initialize(): void
    {
        parent::initialize();

        $this->ControllerAction->model('Staff.Staff');

        $this->ControllerAction->models = [
            'Accounts' => ['className' => 'Staff.Accounts', 'actions' => ['view', 'edit']],
            'StaffSurveys' => ['className' => 'Staff.StaffSurveys', 'actions' => ['view', 'edit']],//POCOR-2315
            'StaffSurveyAnswers' => ['className' => 'Staff.StaffSurveyAnswers', 'actions' => ['index', 'view', 'edit']],//POCOR-2315
            'StaffSurveyTableCells' => ['className' => 'Staff.StaffSurveyTableCells', 'actions' => ['view', 'edit']],//POCOR-2315
            'Nationalities' => ['className' => 'User.Nationalities'],
            'Positions' => ['className' => 'Staff.Positions', 'actions' => ['index', 'view']],
            'Duties' => ['className' => 'Staff.Duties', 'actions' => ['index', 'view']],
            'StaffAssociations' => ['className' => 'Staff.InstitutionAssociationStaff', 'actions' => ['index', 'view']],
            'Sections' => ['className' => 'Staff.StaffSections', 'actions' => ['index', 'view']],
            'Classes' => ['className' => 'Staff.StaffClasses', 'actions' => ['index', 'view']],
            'Qualifications' => ['className' => 'Staff.Qualifications'],
            'Extracurriculars' => ['className' => 'Staff.Extracurriculars', 'actions' => ['index', 'view', 'search']],
            'History' => ['className' => 'User.UserActivities', 'actions' => ['index']],
            'ImportStaff' => ['className' => 'Staff.ImportStaff', 'actions' => ['index', 'add']],
            'TrainingResults' => ['className' => 'Staff.TrainingResults', 'actions' => ['index', 'view']],
            'Achievements' => ['className' => 'Staff.Achievements'],
            'ImportSalaries' => ['className' => 'Staff.ImportSalaries', 'actions' => ['add']],
            'ImportStaffQualifications' => ['className' => 'Staff.ImportStaffQualifications', 'actions' => ['add']]
        ];

        $this->loadComponent('Training.Training');
        $this->loadComponent('User.Image');
        $this->loadComponent('Institution.InstitutionAccessControl');

        $this->set('contentHeader', 'Staff');

        $this->attachAngularModules();

        $this->loadModel('Institution.StaffBodyMasses');
        $this->loadModel('User.UserInsurances');
    }

    // CAv4

    private function attachAngularModules()
    {
        $action = $this->request->getAttribute('params')['action'];
        switch ($action) {
            case 'StaffAttendances':
                $this->Angular->addModules([
                    'staff.attendances.ctrl',
                    'staff.attendances.svc'
                ]);
                break;
            case 'ScheduleTimetable':
                $this->Angular->addModules([
                    'timetable.ctrl',
                    'timetable.svc'
                ]);
                break;
        }
    }

    public function Employments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserEmployments']);
    }

    public function Qualifications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Qualifications']);
    }

    public function Positions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Positions']);
    }

    public function Duties()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Duties']);
    }

    public function StaffAssociations()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.InstitutionAssociationStaff']);
    }

    public function Classes()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffClasses']);
    }

    public function Subjects()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffSubjects']);
    }

    public function EmploymentStatuses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.EmploymentStatuses']);
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

    public function Licenses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Licenses']);
    }

    public function StaffSurveys()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffSurveys']);
    }

    public function StaffSurveyAnswers()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffSurveyAnswers']);
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

    public function Demographic()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Demographic']);
    }

    public function Awards()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Awards']);
    }

    public function TrainingNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.TrainingNeeds']);
    }

    public function Attachments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Attachments']);
    }

    public function Courses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffTrainings']);
    }

    public function Salaries()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Salaries']);
    }

    public function Payslips()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Payslips']);
    }


    public function StaffLeave()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffLeave']);
    }

    // POCOR-8128 start
    public function StaffEntitlement()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffEntitlement']);
    }
    // POCOR-8128 end
    // health

    public function Behaviours()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffBehaviours']);
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

    // Historical

    public function HistoricalStaffPositions()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Historical.HistoricalStaffPositions']);
    }

    //POCOR-6138 - Add export Button

    public function InstitutionStaffAttendanceActivities()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.InstitutionStaffAttendanceActivities']);
    }

    public function StaffBodyMasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.StaffBodyMasses']);
    }

    public function StaffInsurances()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.UserInsurances']);
    }
    //POCOR-6138 - Add export Button
    public function StaffAppraisals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffAppraisals']);
    }

    //POCOR-6138 - Add export Button
    /**
     * common proc to check if there is an archive
     * @return bool
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function isStaffAttendancesArchiveExists()
    {
        $staffId = $this->getStaffID();
        $institutionId = $this->getInstitutionID();
        $where = [
            ['institution_id = '.  intval($institutionId)],
            ['staff_id = ' . intval($staffId)]
        ];
        $table_name = 'institution_staff_attendances';
        $is_archive_exists = ArchiveConnections::hasArchiveRecords($table_name, $where);
        return $is_archive_exists;
    }
    // AngularJS
    public function changeHealthHeader($model, $modelAlias, $userType)
    {
        if ($this->request->getParam('action') == 'StaffBodyMasses') {
            $session = $this->request->getSession();
            $institutionId = $this->getInstitutionId();
            if (!empty($institutionId)) {
                $staffName = $session->read('Staff.Staff.name');
                $header = $staffName . ' - ' . __('Body Mass');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__('Body Mass'));
                $this->set('contentHeader', $header);
            }
        } else if ($this->request->getParam('action') == 'StaffInsurances') {
            $session = $this->request->getSession();
            $institutionId = $this->getInstitutionId();
            if (!empty($institutionId)) {
                $staffName = $session->read('Staff.Staff.name');
                $header = $staffName . ' - ' . __('Insurances');
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore('Staff Insurances')));
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
    function getInstitutionID($debugString = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->getQueryString('institution_id');
        if(empty($institution_id)) {
            $institution_id = $this->request->getQuery('institution_id');
        }
        if (!$institution_id) {
            $session = $this->request->getSession();
            return $_SESSION;
            $institution_id = $session->read('Institution.Institutions.id');
            if(!$institution_id){
                if ($debugString != "") {
                    die($debugString . 'For Developer: You should put institution_id into query string first');
                }
            }
        }
        return $institution_id;
    }


    public function StaffAttendances()
    {
        /*if (!empty($this->request->getQuery()['user_id'])) { //POCOR-7979
             //POCOR-7949
             if ((empty($_SESSION['Staff']['Staff']['id'])) || ($_SESSION['Staff']['Staff']['id'] != $this->request->query('user_id'))) {
                 $_SESSION['Staff']['Staff']['id'] = $this->request->query('user_id');
                 header('Location: index?user_id=' . $this->request->query('user_id'));
                 exit;
             }//POCOR-7949
         }*/

        $this->setEditStaffAttendances();

        $this->setStaffIdForTemplate();

        $this->setInstitutionIdForTemplate();

        $this->setTabElementsForTemplate();

        $this->setCrumbForTemplate();

        $this->setHistoryStaffAttendances();

        $this->setArchiveStaffAttendances();

        $this->set('selectedAction', 'StaffAttendances');
        $this->set('ngController', 'StaffAttendancesCtrl as $ctrl');
        $this->setManualStaffAttendances();


    }

    // Special Needs

    private function setEditStaffAttendances()
    {
        $_edit = $this->AccessControl->check(['Staff', 'StaffAttendances', 'edit']);
        $this->set('_edit', $_edit);
    }

    private function setStaffIdForTemplate()
    {
        $staffId = $this->getStaffId();
        $this->set('staff_id', $staffId);
    }

    /**
     * @return string|null
     */
    private function getStaffId()
    {
        $userId = $this->getQueryString('staff_id');
        if(empty($userId)) {
            $userId = $this->request->getQuery('user_id');
        }
        if (!$userId) {
            $userId = $this->getQueryString('user_id');
        }
        return $userId;
    }

    private function setInstitutionIdForTemplate()
    {
        $institutionId = $this->getInstitutionId();
        $this->set('institution_id', $institutionId);
    }

    private function setTabElementsForTemplate()
    {
        $tabElements = $this->getCareerTabElements();
        $this->set('tabElements', $tabElements);
    }

    public function getCareerTabElements($options = [])
    {
        $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        $this->loadModel('Staff.Staff');
        $tabElements = $this->Staff->getCareerTabElements($options, $this);
        return $this->TabPermission->checkTabPermission($tabElements);
        // $options['url'] = ['plugin' => 'Institution', 'controller' => 'Institutions'];
        // $userId = $this->getStaffId();
        // $institutionId = $this->getInstitutionId();
        // if ($userId) {
        //     $options['user_id'] = $userId;
        // }
        // if ($institutionId) {
        //     $options['institution_id'] = $institutionId;
        // }

        // $tabElements = TableRegistry::get('Staff.Staff')->getCareerTabElements($options);

        // return $this->TabPermission->checkTabPermission($tabElements);
    }
    // Special Needs - End
    // End

    private function setCrumbForTemplate()
    {
        $crumbTitle = __(Inflector::humanize(Inflector::underscore($this->request->getParam('action'))));
        $this->Navigation->addCrumb($crumbTitle);
    }

    private function setHistoryStaffAttendances()
    {
        $_history = $this->AccessControl->check(['Staff', 'InstitutionStaffAttendanceActivities', 'index']);
        $historyUrl = $this->ControllerAction->url('index');
        $historyUrl['plugin'] = 'Staff';
        $historyUrl['controller'] = 'Staff';
        $historyUrl['action'] = 'InstitutionStaffAttendanceActivities';
        $historyUrl['0'] = 'index';
        $queryString = $this->request->getAttribute('params')['pass'][1];
        $historyUrl['1'] = $queryString;
        //echo "<pre>"; print_r($queryString);
        //die;
        // $userId = $this->getStaffId();
        // $institutionId = $this->getInstitutionId();
        // if ($userId) {
        //     $options['user_id'] = $userId;
        // }
        // if ($institutionId) {
        //     $options['institution_id'] = $institutionId;
        // }
        $this->set('historyUrl', Router::url($historyUrl));
        $this->set('_history', $_history);
    }

    private function setManualStaffAttendances()
    {
        // Start POCOR-5188
        $manualTable = TableRegistry::get('Manuals');
        $ManualContent = $manualTable->find()->select(['url'])->where([
            $manualTable->aliasField('function') => 'Attendances',
            $manualTable->aliasField('module') => 'Institutions',
            $manualTable->aliasField('category') => 'Staff - Career',
        ])->first();

        if (!empty($ManualContent['url'])) {
            $this->set('is_manual_exist', ['status' => 'success', 'url' => $ManualContent['url']]);
        } else {
            $this->set('is_manual_exist', []);
        }
        // End POCOR-5188
    }

    public function InstitutionStaffAttendancesArchive()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.InstitutionStaffAttendancesArchive']);
    }

    public function SpecialNeedsReferrals()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsReferrals']);
    }

    public function Profiles()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.Profiles']);
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

    public function beforeFilter(EventInterface $event)//POCOR-8456
    {
        $isInstitutionIndex = $this->isInstitutionIDSkipped();
        if ($isInstitutionIndex) {
            return;
        }
        parent::beforeFilter($event);

        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);

        //$institutionName = $session->read('Institution.Institutions.name');
        $institutionId = $this->getInstitutionID();
        $staffId = $this->getStaffID();

        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $activeInstitution = $this->Institutions->get($institutionId);
        $institutionName = $activeInstitution->name;
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId ,'institution_id' => $institutionId]);
        $this->Navigation->addCrumb($institutionName,
            ['plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'dashboard',
                'institutionId' => $institutionId,
                $encodedInstitutionId]);
        $this->Navigation->addCrumb('Staff',
            ['plugin' => 'Institution',
                'institutionId' => $institutionId,
                'controller' => 'Institutions',
                'action' => 'Staff',
                'index',
                $encodedInstitutionId]);
        $action = $this->request->getAttribute('params')['action'];
        $header = __('Staff');

        if ($action == 'index') {
        } else if ($this->getStaffId() || $action == 'view' || $action == 'edit') {
            // add the staff name to the header
            $id = $this->getQueryString('id');
            if ($action == 'view' || $action == 'edit') {
                $id = $id;
            } else if ($this->getStaffId()) {
                $id = $staffId;
            }

            if (!empty($id)) {
                $entity = $this->Staff->get($id);
                $name = $entity->name;
                $header = $name . ' - ' . __('Overview');
                //$this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffUser', 'view', $this->ControllerAction->paramsEncode(['id' => $id])]);
                $this->Navigation->addCrumb($name, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffUser', 'view',
                 $this->ControllerAction->paramsEncode(['id' => $id,'institution_id' => $institutionId,'staff_id' => $id])]);
            }
        }
        $this->set('contentHeader', $header);
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $isInstitutionIndex = $this->isInstitutionIDSkipped();
        if ($isInstitutionIndex) {
            return;
        }
        /**
         * if student object is null, it means that student.security_user_id or users.id is not present in the session; hence, no sub model action pages can be shown
         */
        $userId = $this->getStaffId();
        if ($userId) {
            $header = '';
            // $userId = $session->read('Staff.Staff.id');

            // if ($session->check('Staff.Staff.name')) {
            //     $header = $session->read('Staff.Staff.name');
            // }
            $entity = $this->Staff->get($userId);
            $header = $entity->name;
            $primaryKey = $model->getPrimaryKey();

            $alias = $model->alias;
            //POCOR-5890 starts
            if ($alias == 'HealthImmunizations') {
                $alias = __('Vaccinations');
            }
            //POCOR-5890 ends
            $this->Navigation->addCrumb($model->getHeader($alias));
            $header = $header . ' - ' . $model->getHeader($alias);

            // $params = $this->request->params;
            $this->set('contentHeader', $header);

            // POCOR-3983 to disable add/edit/remove action on the model when institution status is inactive
            $this->getStatusPermission($model);
            $pass = $this->request->getParam('pass');
            $subaction = isset($pass[0]) ? $pass[0] : null;

            if($model->alias = 'StaffAppraisals'){
                return true;
            }
            if ($subaction != 'index') {
                if ($model->hasField('security_user_id')) {
                    $model->fields['security_user_id']['type'] = 'hidden';
                    $model->fields['security_user_id']['value'] = $userId;
                    if (count($this->request->getQueryParams()) > 1) {
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
                            return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $alias]);
                        }
                    }
                } else if ($model->hasField('staff_id')) {
                    $model->fields['staff_id']['type'] = 'hidden';
                    $model->fields['staff_id']['value'] = $userId;

                    if (count($this->request->getParam('pass')) > 1) {
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
                            return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => $alias]);
                        }
                    }
                }
            }
        } else {
            if ($model->getAlias() == 'ImportStaff') {
                $this->Navigation->addCrumb($model->getHeader($model->getAlias()));
                $header = __('Staff') . ' - ' . $model->getHeader($model->getAlias());
                $this->set('contentHeader', $header);
            } else {
                $this->Alert->warning('general.notExists');
                $event->stopPropagation();
                return $this->redirect(['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index']);
            }
        }
    }

    public function getStatusPermission($model)
    {
        $institutionId = $this->getInstitutionID();

        $Institutions = TableRegistry::get('Institution.Institutions');
        $isActive = $Institutions->isActive($institutionId);

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
                } else if ($model instanceof \App\Model\Table\AppTable) {
                    // CAv3 hide button and redirect when user change the Url
                    $model->addBehavior('ControllerAction.HideButton');
                }
            }
        }
    }

    public function beforeQuery(Event $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    //POCOR-7062

    public function beforePaginate(Event $event, Table $model, Query $query, ArrayObject $options)
    {
        $session = $this->request->getSession();

        if ($model->getAlias() != 'Staff') {
            $userId = $this->getStaffId();
            if (!$userId) {
                $this->Alert->warning('general.noData');
                $event->stopPropagation();
                return $this->redirect(['action' => 'index']);
            }
            if ($userId) {
                if ($model->hasField('security_user_id')) {
                    $query->where([$model->aliasField('security_user_id') => $userId]);
                } else if ($model->hasField('staff_id')) {
                    $query->where([$model->aliasField('staff_id') => $userId]);
                }
            }
        }


        // if ($model->getAlias() != 'Staff') {
        //     if ($session->check('Staff.Staff.id')) {
        //         $userId = $session->read('Staff.Staff.id');
        //         if ($model->hasField('security_user_id')) {
        //             $query->where([$model->aliasField('security_user_id') => $userId]);
        //         } else if ($model->hasField('staff_id')) {
        //             $query->where([$model->aliasField('staff_id') => $userId]);
        //         }
        //     } else {
        //         $this->Alert->warning('general.noData');
        //         $event->stopPropagation();
        //         return $this->redirect(['action' => 'index']);
        //     }
        // }
    }

    //POCOR-6673

    public function excel($id = 0)
    {
        $this->Staff->excel($id);
        $this->autoRender = false;
    }

    public function getUserTabElements($options = [])
    {
        $session = $this->request->getSession();
        $tabElements = $session->read('Institution.Staff.tabElements');
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getFinanceTabElements($options = [])
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $tabElements = [];
        $studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
        $studentTabElements = [
            'BankAccounts' => ['text' => __('Bank Accounts')],
            'Salaries' => ['text' => __('Salaries')],
            'Payslips' => ['text' => __('Payslips')],
        ];


        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index', $encodedQueryString]);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getTrainingTabElements($options = [])
    {
        $tabElements = [];
        $studentUrl = ['plugin' => 'Staff', 'controller' => 'Staff'];
        $studentTabElements = [
            'TrainingResults' => ['text' => __('Training Results')],
            'TrainingNeeds' => ['text' => __('Training Needs')],
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);

        foreach ($studentTabElements as $key => $tab) {
            $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => $key, 'index',]);
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    //POCOR-8056:start
    public function changeUtilitiesHeader($model, $modelAlias, $userType)
    {
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        if (!empty($institutionId)) {
            if ($this->request->getParam('action') == 'StaffCurriculars') {
                $labels_tbl = TableRegistry::get('Labels');
                $curricular_label_Data = $labels_tbl->find('all',['conditions'=>['field'=>'institution_curriculars']])->first();
                if(empty($curricular_label_Data->name)){
                    $curricular_label_Data->name = "Institution Curriculars";
                }
                $getStaffId = $this->getStaffID();
                $nameTable = TableRegistry::getTableLocator()->get('User.Users');
                $staff = $nameTable->find()->where(['id' => $getStaffId])->first();
                $staffName = $staff->first_name; // Accessing the first_name property of the retrieved staff record

                $header = $staffName . ' - ' .$curricular_label_Data->name;
                $this->Navigation->removeCrumb(Inflector::humanize(Inflector::underscore($model->getAlias())));
                $this->Navigation->addCrumb(__($curricular_label_Data->name));
                $this->set('contentHeader', $header);
            }
        }
    }
    //POCOR-8056:end

    public function getInstitutionTrainingTabElements($options = [])
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

    public function getImage($id)
    {
        $this->autoRender = false;
        $this->ControllerAction->autoRender = false;
        $this->Image->getUserImage($id);
    }

    public function ScheduleTimetable()
    {
        $userId = $this->getStaffId();
        if (!$userId) {
            $userId = $this->Auth->user('id');
        }

        $InstitutionStaff = TableRegistry::get('Institution.InstitutionStaff');
        $Institutions = TableRegistry::get('Institution.Institutions');


        $InstitutionStaff = $InstitutionStaff
            ->find()
            ->where([
                'InstitutionStaff.staff_id' => $userId,
                'InstitutionStaff.staff_status_id' => self::APPROVED
            ])
            ->enableHydration(false)
            ->first();

        $institutionId = $InstitutionStaff['institution_id'];
        if ($institutionId == null) {
            $institutionId = $this->getInstitutionID();
        }

        $selectedInstitutionOptions = $Institutions
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => $Institutions->aliasField('id'),
                'name' => $Institutions->aliasField('name'),
            ])
            ->where([
                $Institutions->aliasField('id ') => $institutionId,
            ])
            ->enableHydration(false)
            ->toArray();

        $academicPeriodId = TableRegistry::get('AcademicPeriod.AcademicPeriods')
            ->getCurrent();
        $academicPeriodOptions = TableRegistry::get('AcademicPeriod.AcademicPeriods')
            ->getYearList();


        $shiftOptions = TableRegistry::get('Schedule.ScheduleIntervals')
            ->getShiftOptions($academicPeriodId, false, $institutionId);

        $this->set('userId', $userId);
        $this->set('selectedInstitutionOptions', $selectedInstitutionOptions);
        $this->set('shiftOptions', $shiftOptions);
        $shiftDefaultId = (!is_null($this->request->getQuery('shift'))) ? $this->request->getQuery('shift') : key($shiftOptions);
        $this->set('academicPeriodId', $academicPeriodId);
        $this->set('academicPeriodName', $academicPeriodOptions[$academicPeriodId]);
        $this->set('shiftDefaultId', $shiftDefaultId);
        $this->set('institutionDefaultId', key($selectedInstitutionOptions));
        $this->set('ngController', 'TimetableCtrl as $ctrl');

        // Start POCOR-5188
        $manualTable = TableRegistry::get('Manuals');
        $ManualContent = $manualTable->find()->select(['url'])->where([
            $manualTable->aliasField('function') => 'Staff',
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

    public function SpecialNeedsDiagnostics()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'SpecialNeeds.SpecialNeedsDiagnostics']);
    }

    private function setArchiveStaffAttendances()
    {
        // POCOR-7895: removed unnecessary lines
        $_archive = $this->AccessControl->check(['Staff', 'InstitutionStaffAttendanceActivities', 'index']);
        $archiveUrl = $this->ControllerAction->url('index');
        $archiveUrl['plugin'] = 'Staff';
        $archiveUrl['controller'] = 'Staff';
        $archiveUrl['action'] = 'ArchivedAttendances';
        $archiveUrl['0'] = 'index';
        $queryString = $this->request->getAttribute('params')['pass'][1];
        $archiveUrl['1'] = $queryString;
        $this->set('_archive', $_archive);
        $this->set('archiveUrl', Router::url($archiveUrl));
    }

    public function StaffCurriculars()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.StaffCurriculars']);
    }


    /**
     * common function to get institution id
     * @return string|null
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */

    public function ArchivedAttendances()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Staff.ArchivedAttendances']);
    }

    public function Comments()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.Comments']);
    }

    public function History()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'User.UserActivities']);
    }

    public function HealthBodyMasses()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.BodyMasses']);
    }

    public function HealthInsurances()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Health.Insurances']);
    }

    public function StaffTrainingNeeds()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingNeeds']);
    }

    public function StaffTrainingApplications()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Institution.StaffTrainingApplications']);
    }

    public
    function isInstitutionIDSkipped(): bool
    {
        $request = $this->request;

        $pass = $request->getParam('pass');
        $action = $request->getParam('action');
        $controller = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $furtherAction = $pass[0];

        if ($pass[0] == 'download' && ($action == 'Qualifications' || $action == 'EmploymentStatuses' || $action == 'Payslips' || 'Healths') && ($plugin == 'Staff') && ($controller == 'Staff')) {
            return true;
        }

        return false;
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
}
