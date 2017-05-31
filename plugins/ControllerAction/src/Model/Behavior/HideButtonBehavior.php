<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Log\Log;

// use ControllerAction\Model\Traits\EventTrait;

class HideButtonBehavior extends Behavior
{
    private $action = null;

    public function initialize(array $config)
    {

    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();

        $events['ControllerAction.Model.addEdit.beforeAction'] = ['callable' => 'addEditBeforeAction', 'priority' => 1];
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1000];
        $events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 1000];

        return $events;
    }

    public function addEditBeforeAction(Event $event)
    {
        // button already hidden, user access from url
        $model = $this->_table;
        $session = $model->request->session();

        $sessionKey = 'HideButton.warning';
        $session->write($sessionKey, $model->alias() .'.HideButton.warning');

        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index'];

        $model->controller->redirect($url);
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if (isset($toolbarButtons['add'])) {
            unset($toolbarButtons['add']);
        }

        if (isset($toolbarButtons['edit'])) {
            unset($toolbarButtons['edit']);
        }

        if (isset($toolbarButtons['remove'])) {
            unset($toolbarButtons['remove']);
        }

        // workflow toolbarButtons
        if (isset($toolbarButtons['approve'])) {
            unset($toolbarButtons['approve']);
        }

        if (isset($toolbarButtons['reject'])) {
            unset($toolbarButtons['reject']);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $id = $this->_table->paramsEncode(['id' => $entity->id]);

        if (isset($buttons['view'])) {
            $buttons['view']['url'][1] = $id;
        }

        if (isset($buttons['edit'])) {
            unset($buttons['edit']);
        }

        if (isset($buttons['remove'])) {
            unset($buttons['remove']);
        }

        return $buttons;
    }
}
