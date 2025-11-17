<?php

namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;

class UserTabBehavior extends Behavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {
//        die('<pre>'. print_r($this->_table,true));
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 1111];
        $events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 1001];
        $events['ControllerAction.Model.add.beforeAction'] = 'addDeleteBeforeAction';
        $events['ControllerAction.Model.delete.beforeAction'] = 'addDeleteBeforeAction';
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra = null)
    {
        if (!$extra) {
            return;
        }
//        die('<pre>' . print_r($extra, true));
        $toolbarButtons = $extra['toolbarButtons'];
        $redirectURL = $extra['redirect'];
        $model = $this->_table;
        if ($model->action == 'edit') {
            $toolbarButtons = $this->fixEditBackButton($toolbarButtons);
        }

        if ($model->action == 'add' || $model->action == 'view') {
            $toolbarButtons = $this->fixViewBackButton($toolbarButtons);
        }

        if ($model->action == 'index') {
            // POCOR-8527 add no records alert
            $this->addNoRecordsAlert();

        }

        if ($model->action == 'add' || $model->action == 'delete' || $model->action == 'remove') {
            $redirectURL = $this->fixAddDeleteRedirectURL();
        }

        $extra['toolbarButtons'] = $toolbarButtons;
        $extra['redirect'] = $redirectURL;
    }

    // POCOR-8527
    public function addNoRecordsAlert()
    {
        $model = $this->_table;
        $query = $model->find('all');
        $userId = $this->getUserID();

        $controller = $model->controller;
        $controllerName = $controller->getName();
        if(!$userId && $controllerName == 'Profiles'){
            $userId = $this->_table->Auth->user('id');
        }
        if ($model->hasField('security_user_id')) {
            $query->where([$model->aliasField('security_user_id IS') => $userId]);
        } else if ($model->hasField('student_id')) {
            $query->where([$model->aliasField('student_id IS') => $userId]);
        } else if ($model->hasField('staff_id')) {
            $query->where([$model->aliasField('staff_id IS') => $userId]);
        } else if ($model->hasField('user_id')) {
            $query->where([$model->aliasField('user_id IS') => $userId]);
        }
        $count = $query->count();
        if($count == 0){
            $model->controller->Alert->info('general.noData');
        }
    }

    public function fixAddDeleteRedirectURL()
    {
        $model = $this->_table;
        $url = $model->url('index');
        $queryString = $model->getQueryString();
        $userId = $this->getUserID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        if ($userId) {
            $queryString['user_id'] = $userId;
        }
        $url[1] = $model->paramsEncode($queryString);
        return $url;
    }


    /**
     * @param $toolbarButtons
     * @return mixed
     */
    private function fixEditBackButton($toolbarButtons)
    {
        $model = $this->_table;
        $params = $model->getQueryString();
        $controller = $model->controller;
        $controllerName = $controller->getName();
        $queryString = $model->paramsEncode($params);
        if ($toolbarButtons->offsetExists('back')) {
            $toolbarButtons['back']['url'][0] = 'view';
            $toolbarButtons['back']['url'][1] = $queryString;
            if($controllerName == 'Directories') {
                $url = $model->controller->referer();
                $toolbarButtons['back']['url'] = $url;
            }
        }
        if (isset($toolbarButtons['list'])) {
            $toolbarButtons['list']['url'][0] = 'index';
            $toolbarButtons['list']['url'][1] = $queryString;
        }

        return $toolbarButtons;
    }

    /**
     * @param $toolbarButtons
     * @return mixed
     */
    private function fixViewBackButton($toolbarButtons)
    {
        $model = $this->_table;
        $controller = $model->controller;
        $controllerName = $controller->getName();
        $params = $model->getQueryString();
        $userID = $this->getUserID();
        if ($userID) {
            $params['user_id'] = $userID;
        }
        $queryString = $model->paramsEncode($params);
        if ($toolbarButtons->offsetExists('back')) {
            $url = $toolbarButtons['back']['url'];
            $url['0'] = 'index';
            $url['1'] = $queryString;
            $request = $this->_table->request;

            if($controllerName == 'Directories') {
                $actions = ['StaffPayslips','StaffBankAccounts','StaffSalaries','TrainingNeeds','TrainingResults','HealthConsultations',
                'HealthFamilies','HealthHistories', 'HealthImmunizations', 'HealthMedications','HealthTests','HealthBodyMasses',
                'Employments','StaffQualifications','StaffMemberships','StaffLicenses','StaffAwards','SpecialNeedsDiagnostics',
                'SpecialNeedsDevices','SpecialNeedsServices','SpecialNeedsAssessments','HealthInsurances','SpecialNeedsPlans',
                'StudentBankAccounts','Counsellings','StudentFees','StudentLicenses','GuardianStudents'];
                $action = $request->getParam('action');
                if(isset($request->getParam('pass')[1]) && in_array($action, $actions)) {
                    $decodeQueryString = $request->getParam('pass')[1];
                    $queryString = $model->paramsDecode($decodeQueryString);
                    if(isset($queryString['id'])) {
                        unset($queryString['id']);
                    }
                    $url['1'] = $model->paramsEncode($queryString);
                } else {
                    unset($url['1']);
                }
            } else {
                unset($url['?']);
            }
            $toolbarButtons['back']['url'] = $url;
        }
        if ($toolbarButtons->offsetExists('download') && isset($request->getParam('pass')[1])) {
            $url = $toolbarButtons['download']['url'];
            $decodeQueryString = $request->getParam('pass')[1];
            $queryString = $model->paramsDecode($decodeQueryString);
            if($controllerName == 'Profiles' || $controllerName == 'Staff' || $controllerName == 'Students'){
                $actions = ['StaffQualifications', 'Qualifications'];
                $action = $request->getParam('action');
                if(isset($request->getParam('pass')[1]) && in_array($action, $actions)) {
                    $decodeQueryString = $request->getParam('pass')[1];
                    $queryString = $model->paramsDecode($decodeQueryString);
                    foreach ($queryString as $key => $value) {
                        if ($key !== 'id') {
                            unset($queryString[$key]);
                        }
                    }
                }
            }
            // $url['1'] = $model->paramsEncode($queryString);
            if($controllerName == 'Students'){
                $id = $queryString['id'];
                $url['1'] = $model->paramsEncode(['id' => $id]);
            } else {
                $url['1'] = $model->paramsEncode($queryString);
            }
            if(isset($url['?'])) {
                unset($url['?']);
            }
            $toolbarButtons['download']['url'] = $url;
        }
        // die('<pre>' . print_r($toolbarButtons, true));
        return $toolbarButtons;
    }

    public function getInstitutionID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('institution_id');
        return $institutionID;
    }

    public function getUserID()
    {
        $model = $this->_table;
        $userID = $model->getQueryString('security_user_id');
        //echo "<pre>"; print_r($userID); die;
        if (!$userID) {
            $userID = $model->getQueryString('user_id');
        }
        if (!$userID) {
            $userID = $model->getQueryString('applicant_id');

        }
        if (!$userID) {
            $userID = $model->getQueryString('student_id');
        }
        if (!$userID) {
            $userID = $model->getQueryString('staff_id');

        }
        if (!$userID) {
            $userID = $model->getQueryString('assignee_id');
        }

        $userID = is_numeric($userID) ? intval($userID) : null;

        if (!$userID) {
            return null;
        }

        return $userID;
    }


    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
        $buttons = $this->fixActionButtons($entity, $buttons);
        // POCOR-8155 Start
        if($this->_table->getAlias() == "InstitutionCases") {
            if (!$this->_table->AccessControl->isAdmin()) {
                $workflowStep = $this->_table->getWorkflowStep($entity);
                $isEditable = false;
                $isDeletable = false;
                if (!empty($workflowStep)) {
                    $isEditable = $workflowStep->is_editable == 1 ? true : false;
                    $isDeletable = $workflowStep->is_removable == 1 ? true : false;
                }
                if (isset($buttons['edit']) && !$isEditable) {
                    unset($buttons['edit']);
                }
                if (isset($buttons['remove']) && !$isDeletable) {
                    unset($buttons['remove']);
                }
            }
        }
        // POCOR-8155 End
        return $buttons;
    }

    /**
     * @param Entity $entity
     * @param array $buttons
     * @return array
     */
    private function fixActionButtons(Entity $entity, array $buttons): array
    {
        try {
            $appliedAction = $this->getConfig('appliedAction');
            if (!$appliedAction) {
                $appliedAction = $this->getConfig()['appliedAction'];
            }
        } catch (Exception $e) {
            // Handle the exception
            //echo "An error occurred: " . $e->getMessage();
            die('<pre> An error occurred:' . print_r($e->getMessage(), true));
        }

        //$action name and additional params to pass
        $appliedActions = [
            'Demographic' => [],
            'Identities' => ['identity_type_id', 'nationality_id'],
            'Nationalities' => ['nationality_id'],
            'Contacts' => ['contact_type_id'],
            'Languages' => ['language_id'],
            'Attachments' => [],
            'Comments' => ['comment_type_id'],
            'HealthConsultations' => ['health_consultation_type_id'],
            'HealthAllergies' => ['health_allergy_type_id'],
        ];
        if (!empty($appliedAction)) {
            $appliedActions = array_merge($appliedActions, $appliedAction);
        }
//        die('<pre>' . print_r($appliedActions, true));

        $model = $this->_table;
        $userID = $this->getUserID();
        $actions = ['view', 'edit'];

        foreach ($actions as $action) {
            if (isset($buttons[$action])) {
                $url = $buttons[$action]['url'];
                $url_action = $url['action'];
                $additionalParam = null;
                if (isset($appliedActions[$url_action])) {

                    if (isset($url[2])) {
                        unset($url[2]);
                    }
                    $queryString = $model->getQueryString();
                    $queryString['id'] = $entity->id;
                    if ($userID) {
                        $queryString['user_id'] = $userID;
                        $queryString['security_user_id'] = $userID;
                    }

                    foreach ($appliedActions[$url_action] as $additionalParam) {
                        $queryString[$additionalParam] = $entity->{$additionalParam};
                        if($additionalParam == 'staff_id') {
                            $queryString[$additionalParam] = $userID;
                        }
                    }

                    $url[1] = $model->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                } else { // Shikah's code[START]
                    if (isset($url[2])) {
                        unset($url[2]);
                    }
                    $queryString = $model->getQueryString();
                    $queryString['id'] = $entity->id;
                    // $queryString['institution_id'] = $institutionID;
                    if(isset($institutionID)){
                        $queryString['institution_id'] = $institutionID;
                    }
                    foreach ($appliedActions[$url_action] as $additionalParam) {
                        $queryString[$additionalParam] = $entity->{$additionalParam};
                    }
                    $url['1'] = $model->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                }
                // Shikah's code[END]
            }
        }

        //die('<pre>' . print_r($entity, true) . '</pre><h1>BUTTONS</h1><pre>' . print_r($buttons, true));

        return $buttons;
    }

    public function addDeleteBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $url = $model->url('index');
        $userId = $this->getUserID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $params = $model->getQueryString();
        if ($userId) {
            $params['user_id'] = $userId;
        }
        $url[1] = $model->paramsEncode($params);
        $extra['redirect'] = $url;
    }
}
