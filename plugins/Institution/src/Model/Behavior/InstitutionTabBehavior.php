<?php

namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class InstitutionTabBehavior extends Behavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 1111];
        $events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 1001];
        $events['ControllerAction.Model.add.beforeAction'] = 'addDeleteBeforeAction';
        $events['ControllerAction.Model.delete.beforeAction'] = 'addDeleteBeforeAction';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra = null)
    {
        $model = $this->_table;
        if (!$extra) {
            return;
        }
        $toolbarButtons = $extra['toolbarButtons'];
        $redirectURL = $extra['redirect'];

        if ($model->action == 'edit' || $model->action == 'remove') {
            $toolbarButtons = $this->fixEditBackButton($toolbarButtons);
        }

        if ($model->action == 'add' || $model->action == 'view' || $model->action == 'remove') {
            $toolbarButtons = $this->fixViewBackButton($toolbarButtons);

        }

        if ($model->action == 'add' || $model->action == 'delete' || $model->action == 'remove') {
            $redirectURL = $this->fixAddDeleteRedirectURL();
            $extra['redirect'] = $redirectURL;
            if ($model->action != 'index') {
//                die('<pre>'.print_r($extra, true));
            }
        }

        $extra['toolbarButtons'] = $toolbarButtons;
        $extra['redirect'] = $redirectURL;
//        die('<pre>' . print_r($extra, true));
    }

    /**
     * @param $toolbarButtons
     * @return mixed
     */
    private function fixEditBackButton($toolbarButtons)
    {

        $model = $this->_table;
        $queryString = $model->getQueryString();
        $queryString = $model->paramsEncode($queryString);
        if ($toolbarButtons->offsetExists('back')) {
            $toolbarButtons['back']['url'][0] = 'view';
            $toolbarButtons['back']['url'][1] = $queryString;
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
        $institutionID = $this->getInstitutionID();
        $params = $model->getQueryString();
//        $params['id'] = $institutionID;
        $params['institution_id'] = $institutionID;
        $queryString = $model->paramsEncode($params);
        if ($toolbarButtons->offsetExists('back')) {
            $toolbarButtons['back']['url'][0] = 'index';
            $toolbarButtons['back']['url'][1] = $queryString;
        }
        return $toolbarButtons;
    }

    public function getInstitutionID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('institution_id');
        return $institutionID;
    }

    public function fixAddDeleteRedirectURL()
    {
// http://localhost:8182/core/Institution/Institutions/InstitutionTransportProviders/index/eyJpbnN0aXR1dGlvbl9pZCI6Nn0.MjFlNjlhMTg1Y2I5ZGIyYzA5YWY3YzJjZjUwYWM1NWQyNmJhNTBkOGJjMjRiZmVhYTgyOGVkMDhjZjU4ZWY1Yw
        $model = $this->_table;
        $url = $model->url('index');
        $queryString = $model->getQueryString();
        $institutionID = $this->getInstitutionID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $queryString['id'] = $institutionID;
        $queryString['institution_id'] = $institutionID;
        $url['0'] = 'index';
        $url['1'] = $model->paramsEncode($queryString);
        return $url;
    }

    public function getGuardianID()
    {
        $model = $this->_table;
        $guardianID = $model->getQueryString('guardian_id');
        return $guardianID;
    }

    public function getUserID()
    {
        $model = $this->_table;
        $userID = $model->getQueryString('security_user_id');
        if (!$userID) {
            $userID = $model->getQueryString('user_id');
        }
        if (!$userID) {
            return null;
        }
        return $userID;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
        $buttons = $this->fixActionButtons($entity, $buttons);
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
        $appliedActions = [];
        if (!empty($appliedAction)) {
            $appliedActions = array_merge($appliedActions, $appliedAction);
        }

        $model = $this->_table;
        $institutionID = $this->getInstitutionID();
        $actions = ['view', 'edit'];
        foreach ($actions as $action) {
            if (isset($buttons[$action])) {
                $url = $buttons[$action]['url'];
                $url_action = $url['action'];
                $additionalParam = null;
                if (isset($appliedActions[$url_action])) {
//                    die($url_action);
                    if ($url_action == 'StudentUser' || $url_action == 'StaffUser') {
                        if (isset($url[2])) {
                            $url[1] = $url[2];
                            unset($url[2]);
                        }
                    } else {
                        if (isset($url[2])) {
                            unset($url[2]);
                        }
                        $queryString = $model->getQueryString();
                        $queryString['id'] = $entity->id;
                        $queryString['institution_id'] = $institutionID;
                        // echo "<pre>"; print_r($url_action);
                        // echo "<pre>"; print_r($appliedActions[$url_action]);
                        // die;
                        foreach ($appliedActions[$url_action] as $additionalParam) {
                            if($url_action == 'Classes' && $additionalParam == 'institution_class_id'){
                                $queryString['id'] = $entity->{$additionalParam};
                            }else if($url_action == 'Subjects' && $additionalParam == 'institution_subject_id'){
                                $queryString['institution_subject_id'] = $entity->id;
                            }else{
                                $queryString[$additionalParam] = $entity->{$additionalParam};
                            }
                        }
                        $url['1'] = $model->paramsEncode($queryString);
                    }
                    $buttons[$action]['url'] = $url;
                } else {
                    if (isset($url[2])) {
                        unset($url[2]);
                    }
                    $queryString = $model->getQueryString();
                    $queryString['id'] = $entity->id;
                    $queryString['institution_id'] = $institutionID;
                    foreach ($appliedActions[$url_action] as $additionalParam) {
                        $queryString[$additionalParam] = $entity->{$additionalParam};
                    }
                    $url['1'] = $model->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                }

            }
        }

    //    die('<pre>' . print_r($appliedActions, true) . print_r($entity, true) . '</pre><h1>BUTTONS</h1><pre>' . print_r($buttons, true));

        return $buttons;
    }

    public function addDeleteBeforeAction(Event $event = null, ArrayObject $extra = null)
    {

        //echo "<pre>"; print_r($this->_table->ControllerAction); echo'test'; die;

        //echo "<pre>"; print_r($extra); die;
        if ($extra == null) {
            return;
        }
        $model = $this->_table;
        $url = $model->url('index');
        $institutionID = $this->getInstitutionID();
        $queryString = $model->getQueryString();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $queryString['institution_id'] = $institutionID;
        $url[1] = $model->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }

    public
    function setUserTabElements($options = [])
    {
        $model = $this->_table;
        // POCOR-8074-QueryStringProfile start
        $maincontroller = $model->controller;
        $controllerName = $maincontroller->getName();
        $userRule = isset($options['userRole']) ? $options['userRole'] : 'Student';
        if ($userRule == 'Students') {
            $userRule = 'Student';
        }
        if($controllerName == 'Staff'){
            $userRule = 'Staff';
        }
        if($userRule == 'Staff'){
            $plugin = 'Staff';
            $controller = 'Staff';
        }
        $userID = $this->getStaffID();
        $queryString = $model->getQueryString();
        $tabElements = [
            $userRule . 'User' => ['text' => __('Overview')],
            $userRule . 'Account' => ['text' => __('Account')],
            'Demographic' => ['text' => __('Demographic')],
            'Identities' => ['text' => __('Identities')],
            'UserNationalities' => ['text' => __('Nationalities')], //UserNationalities is following the filename(alias) to maintain "selectedAction" select tab accordingly.
            'Contacts' => ['text' => __('Contacts')],
            'Languages' => ['text' => __('Languages')],
            'Attachments' => ['text' => __('Attachments')],
            'Comments' => ['text' => __('Comments')],
            'History' => ['text' => __('History')]
        ];
        if ($userRule == 'Student') {
            $userID = $this->getStudentID();
            //$studentLastFirstElements = ['Students' => ['text' => __('Academic')]];
            $studentLastTabElements = ['Guardians' => ['text' => __('Guardians')],
                'StudentTransport' => ['text' => __('Transport')]];
            $tabElements = array_merge($tabElements, $studentLastTabElements);
            $plugin = 'Student';
            $controller = 'Students';
        }
//        $queryString['user_id'] = $userID;
        $queryString['user_id'] = $userID;
        $queryString['security_user_id'] = $userID;
        $queryStingWithoutID = $queryString;
        unset($queryStingWithoutID['id']);
        $queryStringWithID = $queryString;
        $queryStringWithID['id'] = $userID;
//        $queryString['id'] = $userID;
        $queryStringWithID = $model->paramsEncode($queryStringWithID);
        $queryStingWithoutID = $model->paramsEncode($queryStingWithoutID);
        foreach ($tabElements as $key => $value) {
            if ($key == $userRule . 'User') {
                $tabElements[$key]['url']['plugin'] = 'Institution';
                $tabElements[$key]['url']['controller'] = 'Institutions';
                $tabElements[$key]['url']['action'] = $userRule . 'User';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $queryStringWithID;  // POCOR-8074-QueryStringProfile
            } else if ($key == $userRule . 'Account') {
                $tabElements[$key]['url']['plugin'] = 'Institution';
                $tabElements[$key]['url']['controller'] = 'Institutions';
                $tabElements[$key]['url']['action'] = $userRule . 'Account';
                $tabElements[$key]['url'][] = 'view';
                $tabElements[$key]['url'][] = $queryStringWithID; // POCOR-8074-QueryStringProfile
            } else {
                $actionURL = $key;
                if ($key == 'UserNationalities') {
                    $actionURL = 'Nationalities';
                }
                $tabElements[$key]['url'] = [  // POCOR-8074-QueryStringProfile
                    'plugin' => $plugin,
                    'controller' => $controller,
                    'action' => $actionURL,
                    '0' => 'index',
                    '1' => $queryStingWithoutID];
            }
        }
        
        $tabElements = $maincontroller->TabPermission->checkTabPermission($tabElements);
        //die('<pre>' . print_r($tabElements, true));

        $maincontroller->set('tabElements', $tabElements);
        $action = $model->getAlias();
        if ($action == 'UserLanguages') {
            $action = 'Languages';
        }
        if ($action == 'UserActivities') {
            $action = 'History';
        }
        $maincontroller->set('selectedAction', $action);
//
        return $tabElements;
    }

    public function getStaffID()
    {
        $model = $this->_table;
        $staffID = $model->getQueryString('staff_id');
        return $staffID;
    }

    public function getStudentID()
    {
        $model = $this->_table;
        $studentID = $model->getQueryString('student_id');
        return $studentID;
    }

    public function getAcademicTabElements($options = [])
    {
        //$id = (array_key_exists('id', $options)) ? $options['id'] : 0;
        $model = $this->_table;
        // POCOR-8074-QueryStringProfile start
        $maincontroller = $model->controller;
        $controllerName = $maincontroller->getName();
        $labels_tbl = TableRegistry::get('labels');   //POCOR-8056
        $curricular_label_Data = $labels_tbl->find('all',['conditions'=>['field'=>'institution_curriculars']])->first();//POCOR-8056
        if(empty($curricular_label_Data->name)){
            $curricular_label_Data->name = "Institution Curriculars";
        }

        $studentID = $this->getStudentID();
        $institutionID = $this->getInstitutionID();
        $type = (array_key_exists('type', $options)) ? $options['type'] : null;
        $tabElements = [];
        $studentTabElements = [
            'Programmes' => ['text' => __('Programmes')],
            'Classes' => ['text' => __('Classes')],
            'Subjects' => ['text' => __('Subjects')],
            'Absences' => ['text' => __('Absences')],
            'Behaviours' => ['text' => __('Behaviours')],
            'Outcomes' => ['text' => __('Outcomes')],
            'Competencies' => ['text' => __('Competencies')],
            //POCOR-7474-HINDOL TYPO FIX
            'Assessments' => ['text' => __('Assessments')], //POCOR-5786
            'ExaminationResults' => ['text' => __('Examinations')],
            'ReportCards' => ['text' => __('Report Cards')],
            'Awards' => ['text' => __('Awards')],
            //'Extracurriculars' => ['text' => __('Extracurriculars')],//POCOR-7648
            'Textbooks' => ['text' => __('Textbooks')],
            'Risks' => ['text' => __('Risks')],
            'Associations' => ['text' => __('Houses')], //POCOR-7938
            'Curriculars' => ['text' => __($curricular_label_Data->name)] //POCOR-6673
        ];

        $tabElements = array_merge($tabElements, $studentTabElements);
        $params = ['id' => $studentID,
            'student_id' => $studentID,
            'user_id' => $studentID,
            'institution_id' => $institutionID,
            'type' => $type];
        $queryString = $model->paramsEncode($params);
        // Programme & Textbooks will use institution controller, other will be still using student controller
        $institutionControllerAction = [
            'Programmes',
            'Textbooks',
            'Associations',
            'Curriculars',
            'Risks'];
        foreach ($studentTabElements as $key => $tab) {
            if (in_array($key, $institutionControllerAction)) {
                $studentUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', '0' => 'index',
                '1' => $queryString];
                $tabElements[$key]['url'] = array_merge($studentUrl, ['action' => 'Student' . $key, 'type' => $type]);
            } else {
                $studentUrl = ['plugin' => 'Student', 'controller' => 'Students'];
                $urlParams = ['action' => $key, '0' => 'index','1' => $queryString];
                $tabElements[$key]['url'] = array_merge($studentUrl, $urlParams);
            }
        }

        if (Configure::read('schoolMode')) {
            if (isset($tabElements['ExaminationResults'])) {
                unset($tabElements['ExaminationResults']);
            }
            if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
                if (isset($tabElements['Risks'])) {
                    unset($tabElements['Risks']);
                }
            }
        }
        $tabElements = $maincontroller->TabPermission->checkTabPermission($tabElements);
        return $tabElements;
    }
}
