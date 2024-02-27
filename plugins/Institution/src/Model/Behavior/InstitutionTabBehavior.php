<?php

namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

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
            if($model->action != 'index'){
//                die('<pre>'.print_r($extra, true));
            }
        }

        $extra['toolbarButtons'] = $toolbarButtons;
        $extra['redirect'] = $redirectURL;
//        die('<pre>' . print_r($extra, true));
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

    public function getStudentID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('student_id');
        return $institutionID;
    }

    public function getStaffID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('staff_id');
        return $institutionID;
    }

    public function getGuardianID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('guardian_id');
        $institutionID = $model->getQueryString('guardian_id');
        return $institutionID;
    }

    public function getUserID()
    {
        $model = $this->_table;
        $userID = $model->getQueryString('security_user_id');
        if (!$userID) {
            $userID = $model->getQueryString('user_id');
        }
        if (!$userID) {
            $userID = $model->getQueryString();
            die('userID<pre>' . print_r($userID, true) . '</pre>');
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
        $appliedAction = $this->getConfig('appliedAction');
        //$action name and additional params to pass
        $appliedActions = [];
        if (!empty($appliedAction)) {
            $appliedActions = array_merge($appliedActions, $appliedAction);
        }
        //die('<pre>' . print_r($appliedActions, true));

        $model = $this->_table;
        $institutionID = $this->getInstitutionID();
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
                    $queryString['institution_id'] = $institutionID;
                    foreach ($appliedActions[$url_action] as $additionalParam) {
                        $queryString[$additionalParam] = $entity->{$additionalParam};
                    }
                    $url[1] = $model->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                }
            }
        }
        //die('<pre>' . print_r($entity, true) . '</pre><h1>BUTTONS</h1><pre>' . print_r($buttons, true));

        return $buttons;
    }

    public function addDeleteBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $url = $model->url('index');
        $institutionID = $this->getInstitutionID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $queryString['id'] = $institutionID;
        $queryString['institution_id'] = $institutionID;
        $url[1] = $model->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }
}
