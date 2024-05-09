<?php

namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

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

    public function beforeAction(Event $event, ArrayObject $extra = null)
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

        if ($model->action == 'add' || $model->action == 'delete' || $model->action == 'remove') {
            $redirectURL = $this->fixAddDeleteRedirectURL();
        }

        $extra['toolbarButtons'] = $toolbarButtons;
        $extra['redirect'] = $redirectURL;
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
        $queryString = $model->paramsEncode($params);
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
            unset($url['?']);
            $toolbarButtons['back']['url'] = $url;
        }
//        die('<pre>' . print_r($toolbarButtons, true));
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
                    }

                    $url[1] = $model->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                    
                }
            }
        }
//        die('<pre>' . print_r($entity, true) . '</pre><h1>BUTTONS</h1><pre>' . print_r($buttons, true));

        return $buttons;
    }

    public function addDeleteBeforeAction(Event $event, ArrayObject $extra)
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
