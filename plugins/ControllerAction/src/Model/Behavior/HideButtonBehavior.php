<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\EventInterface;
use Cake\Log\Log;

// use ControllerAction\Model\Traits\EventTrait;

class HideButtonBehavior extends Behavior
{
    private $action = null;

    public function initialize(array $config): void
    {

    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();

        $events['ControllerAction.Model.addEdit.beforeAction'] = ['callable' => 'addEditBeforeAction', 'priority' => 1];
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1000];
        $events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 1000];

        return $events;
    }

    public function addEditBeforeAction(EventInterface $event)
    {
        // button already hidden, user access from url
        $model = $this->_table;
        $session = $model->request->getSession();

        $sessionKey = 'HideButton.warning';
        $session->write($sessionKey, $model->getAlias() .'.HideButton.warning');

        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', '0' => 'index'];

        $model->controller->redirect($url);
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if (isset($toolbarButtons['add'])) {
            $toolbarButtons->offsetUnset('add');
        }

        if (isset($toolbarButtons['edit'])) {
            $toolbarButtons->offsetUnset('edit');
        }

        if (isset($toolbarButtons['remove'])) {
            $toolbarButtons->offsetUnset('remove');
        }
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        //Log::debug('[TEMP-LOG] HideButtonBehavior::onUpdateActionButtons START');
        //Log::debug('[TEMP-LOG] HideButtonBehavior incoming buttons count: ' . count($buttons) . ', keys: ' . implode(', ', array_keys($buttons)));

        $buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);

        //Log::debug('[TEMP-LOG] HideButtonBehavior after table call, buttons count: ' . count($buttons) . ', keys: ' . implode(', ', array_keys($buttons)));

        if (isset($buttons['edit'])) {
            unset($buttons['edit']);
        }

        if (isset($buttons['remove'])) {
            unset($buttons['remove']);
        }

        //Log::debug('[TEMP-LOG] HideButtonBehavior FINAL buttons count: ' . count($buttons) . ', keys: ' . implode(', ', array_keys($buttons)));
        //Log::debug('[TEMP-LOG] HideButtonBehavior::onUpdateActionButtons END');

        return $buttons;
    }
}
