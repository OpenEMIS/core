<?php

namespace Staff\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\Http\Client; //POCOR-9590: external identity source HTTP client

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
            'ImportStaffLeave' => ['className' => 'Institution.ImportStaffLeave', 'actions' => ['add']],
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

        $this->StaffBodyMasses = $this->fetchTable('Institution.StaffBodyMasses');
        $this->UserInsurances = $this->fetchTable('User.UserInsurances');
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
     *
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
     *
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
            //POCOR-9584: removed accidental `return $_SESSION;` that blocked the session fallback
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
        $this->Staff = $this->fetchTable('Staff.Staff');
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

        // $tabElements = TableRegistry::getTableLocator()->get('Staff.Staff')->getCareerTabElements($options);

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
        $manualTable = TableRegistry::getTableLocator()->get('Manuals');
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
//        Log::debug(print_r([__FUNCTION__ => $this->getQueryString()], true));
        if ($isInstitutionIndex) {
            return;
        }
        parent::beforeFilter($event);

        $this->Navigation->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);

        //$institutionName = $session->read('Institution.Institutions.name');
        $institutionId = $this->getInstitutionID();
        $staffId = $this->getStaffID();

        $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
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

    public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra)
    {
        $isInstitutionIndex = $this->isInstitutionIDSkipped();
        //POCOR-9584: log full request details to trace where query string gets stripped
        //Log::debug('@StaffController::onInitialize'
        //    . ' url=' . $this->request->getRequestTarget()
        //    . ' action=' . $this->request->getParam('action')
        //    . ' pass=' . json_encode($this->request->getParam('pass'))
        //    . ' query=' . json_encode($this->request->getQueryParams())
        //    . ' model=' . $model->getAlias()
        //    . ' isInstitutionIndex=' . var_export($isInstitutionIndex, true));
//        Log::debug(print_r([__FUNCTION__ => $this->getQueryString()], true));

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

            if($model->alias == 'StaffAppraisals'){ //POCOR-9584: fix assignment operator causing alias corruption for all models
                return true;
            }
            //POCOR-9584: start - only run record-ownership check for view/edit/delete; non-record sub-actions
            //            (excel, download, template, results, etc.) carry context params at pass[1], not a record ID,
            //            so decoding them as a record ID produces null which CakePHP 5 rejects with InvalidArgumentException
            $recordActions = ['view', 'edit', 'delete', 'remove'];
            if (in_array($subaction, $recordActions)) {
            //POCOR-9584: end
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
            //POCOR-9584: all import models have no staff_id in URL on results/template/download pages
            $importAliases = ['ImportStaff', 'ImportStaffLeave', 'ImportStaffQualifications', 'ImportSalaries'];
            if (in_array($model->getAlias(), $importAliases)) {
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

        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
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

    public function beforeQuery(EventInterface $event, Table $model, Query $query, ArrayObject $extra)
    {
        $this->beforePaginate($event, $model, $query, $extra);
    }

    //POCOR-7062

    public function beforePaginate(EventInterface $event, Table $model, Query $query, ArrayObject $options)
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
                $labels_tbl = TableRegistry::getTableLocator()->get('Labels');
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

        //POCOR-9584: start - pass[1] carries the encoded query string (institution_id, staff_id, user_id);
        //            append it to every tab URL so staff context is not lost when navigating between tabs
        $pass = $this->request->getParam('pass');
        $encodedQueryString = $pass[1] ?? null;
        //POCOR-9584: end

        foreach ($trainingTabElements as $key => $tab) {
            if ($key == 'Courses') {
                $coursesUrl = ['plugin' => 'Staff', 'controller' => 'Staff']; //POCOR-9584: Courses lives in Staff controller
                $tabElements[$key]['url'] = array_merge($coursesUrl, ['action' => $key, 'index']);
            } else {
                $tabElements[$key]['url'] = array_merge($trainingUrl, ['action' => $key, 'index']);
            }
            //POCOR-9584: start - append encoded params so all tabs preserve staff/institution context
            if ($encodedQueryString) {
                $tabElements[$key]['url'][1] = $encodedQueryString;
            }
            //POCOR-9584: end
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

        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');


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

        $academicPeriodId = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')
            ->getCurrent();
        $academicPeriodOptions = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')
            ->getYearList();


        $shiftOptions = TableRegistry::getTableLocator()->get('Schedule.ScheduleIntervals')
            ->getShiftOptions($academicPeriodId, false, $institutionId);

        // Required by Timetables/controls so shift/institution dropdown redirects keep the same index URL (avoid 404 on shift change)
        $pass = $this->request->getParam('pass');
        $encodedQueryString = isset($pass[1]) ? $pass[1] : null;
        $this->set('encodedQueryString', $encodedQueryString);

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
        $manualTable = TableRegistry::getTableLocator()->get('Manuals');
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
     *
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
//        Log::debug(print_r([__FUNCTION__ => $this->getQueryString()], true));

        $pass = $request->getParam('pass');
        $action = $request->getParam('action');
        $controller = $request->getParam('controller');
        $plugin = $request->getParam('plugin');
        $furtherAction = $pass[0] ?? '';
//        Log::debug(print_r([$pass, $action, $controller, $plugin, $furtherAction], true));
        //POCOR-9584: start - Support AJAX autocomplete and clean code with arrays
        $downloadActions = [
            'Qualifications',
            'EmploymentStatuses',
            'Payslips',
            'Healths',
        ];

        $ajaxActions = [
            'image',
            'download',
            'ajaxReferrerAutocomplete',
            'ajaxAssessorAutocomplete'
        ];

        if ($pass[0] == 'download' && in_array($action, $downloadActions) && ($plugin == 'Staff') && ($controller == 'Staff')) {
            return true;
        }

        if ($pass[0] == 'template'){
            return true;
        }
        if (in_array($pass[0], ['add', 'results', 'downloadFailed', 'downloadPassed']) //POCOR-9584: results + downloadFailed have no staff_id in URL
            && ($plugin == 'Staff') && ($controller == 'Staff')) {

            return true;
        }

        if (in_array($furtherAction, $ajaxActions)) {
            return true;
        }
        //POCOR-9584: end

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

    //POCOR-9590: start - Sync staff identity from external data source with review/diff page
    //POCOR-9590: PascalCase to match URL ('action' => 'SyncUser') and existing controller convention
    public function SyncUser()
    {
        //POCOR-9590: gate the action itself — the button is hidden by addSyncButton's AccessControl check, but a forged URL could otherwise reach this action without a security_functions row
        if (!$this->AccessControl->check(['Institutions', 'ImportStaff', 'add'])) {
            $this->Alert->error('You do not have permission to sync this user.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }
        //POCOR-9590: user_id is passed as encoded pass param (positional), decode it the standard way
        $pass = $this->request->getAttribute('params')['pass'] ?? [];
        $decoded = !empty($pass[0]) ? $this->ControllerAction->paramsDecode($pass[0]) : [];
        $userId = $decoded['user_id'] ?? null;

        $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
        $ExternalAttrs = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        $ConfigItems   = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $Genders       = TableRegistry::getTableLocator()->get('User.Genders');

        $user = $SecurityUsers->get($userId, ['contain' => ['Genders']]);

        //POCOR-9590: resolve active external data source dynamically (Seychelles Civil Status, OpenEMIS Identity, etc.)
        $activeItem = $ConfigItems->find()
            ->where(['code' => 'external_data_source_type'])
            ->where(['value IS NOT' => null])
            ->where(['value !=' => ''])
            ->where(['value !=' => 'None'])
            ->first();
        if (!$activeItem) {
            $this->Alert->error('No active external identity source is configured.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }
        //POCOR-9590: read .value (active source name like "Seychelles Civil Status"), not .name (form-field label "Type")
        $activeSourceType = $activeItem->value;

        // Load field mapping config for the active source
        $configs = $ExternalAttrs->find()
            ->where(['external_data_source_type' => $activeSourceType])
            ->all()
            ->combine('attribute_field', 'value')
            ->toArray();

        $tokenUrl     = $configs['token_uri'] ?? null;
        $userEndpoint = $configs['user_endpoint_uri'] ?? null;
        $clientId     = $configs['client_id'] ?? null;
        $privateKey   = $configs['private_key'] ?? null;

        if (!$tokenUrl || !$userEndpoint) {
            $this->Alert->error('External identity source is not configured.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        $http = new Client();

        // Step 1: get Bearer token (OAuth2 client_credentials — form-encoded with scope)
        //POCOR-9590: form-encoded + scope required by Seychelles Civil Status (and standard OAuth2). Same shape as getSeychellesData() reference impl.
        $tokenResponse = $http->post($tokenUrl, [
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $privateKey,
            'scope'         => $configs['scope'] ?? ($configs['scopes'] ?? ''),
        ], ['headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);

        if (!$tokenResponse->isOk()) {
            $this->Alert->error('Failed to authenticate with external identity source.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        $accessToken = $tokenResponse->getJson()['access_token'] ?? null;
        if (!$accessToken) {
            $this->Alert->error('External identity source did not return a valid token.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        //POCOR-9590: resolve external reference (see StudentsController for full rationale)
        $externalRef = $user->external_reference;
        if (empty($externalRef)) {
            $UserIdentities = TableRegistry::getTableLocator()->get('User.Identities');
            $idRow = $UserIdentities->find()
                ->where(['security_user_id' => $userId, 'identity_type_id' => $configs['identity_type_id'] ?? 0, 'preferred' => 1])
                ->first();
            $externalRef = $idRow ? $idRow->number : null;
        }
        if (empty($externalRef)) {
            $this->Alert->error('No external reference found for this user.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }
        // Step 2: fetch user data from external API
        $apiUrl      = str_replace('{external_reference}', $externalRef, $userEndpoint);
        $apiResponse = $http->get($apiUrl, [], ['headers' => ['Authorization' => 'Bearer ' . $accessToken]]);

        if (!$apiResponse->isOk()) {
            $this->Alert->error('Failed to retrieve data from external identity source.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        $apiData = $apiResponse->getJson();

        // Step 3: resolve mapped field values from API response
        $mappingKeys = ['first_name', 'middle_name', 'third_name', 'last_name', 'gender', 'date_of_birth'];
        $externalValues = [];
        foreach ($mappingKeys as $field) {
            $path = $configs[$field . '_mapping'] ?? null;
            if ($path) {
                $externalValues[$field] = $this->resolveMappingPath($apiData, $path);
            }
        }

        // Step 4: resolve gender name → gender_id
        $externalGenderId = null;
        if (!empty($externalValues['gender'])) {
            $genderRow = $Genders->find()->where(['name' => $externalValues['gender']])->first();
            $externalGenderId = $genderRow ? $genderRow->id : null;
        }

        //POCOR-9590: normalize date_of_birth on both sides (registry ISO vs OE Core FrozenDate) — compare canonical Y-m-d
        if (isset($externalValues['date_of_birth'])) {
            $externalValues['date_of_birth'] = substr((string)$externalValues['date_of_birth'], 0, 10);
        }
        $userDob = $user->date_of_birth;
        if ($userDob instanceof \DateTimeInterface) {
            $userDob = $userDob->format('Y-m-d');
        } else {
            $userDob = (string)$userDob;
        }
        // Step 5: build diff (only fields that would actually change)
        $diff = [];
        $textFields = ['first_name', 'middle_name', 'third_name', 'last_name'];
        foreach ($textFields as $field) {
            if (isset($externalValues[$field]) && (string)$externalValues[$field] !== (string)$user->$field) {
                $diff[$field] = ['current' => $user->$field, 'external' => $externalValues[$field]];
            }
        }
        if (isset($externalValues['date_of_birth']) && $externalValues['date_of_birth'] !== '' && $externalValues['date_of_birth'] !== $userDob) {
            $diff['date_of_birth'] = ['current' => $userDob, 'external' => $externalValues['date_of_birth']];
        }
        if ($externalGenderId && $externalGenderId !== $user->gender_id) {
            $diff['gender'] = [
                'current'  => $user->has('gender') ? $user->gender->name : '',
                'external' => $externalValues['gender'],
            ];
        }

        //POCOR-9590: no-diff fast path — registry data already matches OE Core, just confirm and exit
        if (empty($diff) && !$this->request->is('post')) {
            //POCOR-9590: bump status to 1 in case it had drifted to 2 but actually matches now
            if ((int)$user->sync_status !== 1) {
                $user->sync_status = 1;
                $SecurityUsers->save($user);
            }
            $this->Alert->ok('Already in sync — registry data matches.', ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        if ($this->request->is('post')) {
            // Apply all mapped fields
            foreach ($textFields as $field) {
                if (isset($externalValues[$field])) {
                    $user->$field = $externalValues[$field];
                }
            }
            //POCOR-9590: apply date_of_birth (already normalized to Y-m-d above; not in $textFields anymore)
            if (!empty($externalValues['date_of_birth'])) {
                $user->date_of_birth = $externalValues['date_of_birth'];
            }
            if ($externalGenderId) {
                $user->gender_id = $externalGenderId;
            }
            $user->sync_status = 1; //POCOR-9590: mark as synced

            if ($SecurityUsers->save($user)) {
                $this->Alert->ok('User synced successfully.', ['type' => 'string', 'reset' => true]);
            } else {
                $this->Alert->error('Failed to save synced data.', ['type' => 'string', 'reset' => true]);
            }

            $originUrl = $this->request->getSession()->read('Sync.origin_url');
            $this->request->getSession()->delete('Sync.origin_url');
            return $this->redirect($originUrl ?: $this->referer());
        }

        // GET: store origin referer and show review page
        $this->request->getSession()->write('Sync.origin_url', $this->referer());
        //POCOR-9590: pass encoded param back to view so form can POST to same encoded URL
        $encodedParams = !empty($pass[0]) ? $pass[0] : '';
        $this->set(compact('user', 'diff', 'externalValues', 'encodedParams'));
    }

    //POCOR-9590: resolve dot-notation path from nested array (e.g. "gender.name" → $data['gender']['name'])
    private function resolveMappingPath(array $data, string $path)
    {
        $value = $data;
        foreach (explode('.', $path) as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        return $value;
    }
    //POCOR-9590: end
}
