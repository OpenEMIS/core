<?php

namespace User\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Behavior;

class SetupTabBehavior extends Behavior
{
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.afterAction'] = 'afterAction';
        return $events;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $model = $this->_table;
        $controller = $model->controller;
        $controllerName = $controller->getName();

        $institutionTabControllers = ['Institution', 'Staff', 'Students'];
        if (in_array($controllerName, $institutionTabControllers)) {
            try {
                $model->setUserTabElements();
            } catch (\Exception $exception) {

            }
            return;
        }

        if ($controllerName == 'Scholarships') {
            $tabElements = $controller->ScholarshipTabs->getScholarshipApplicationTabs();
        }
        if ($controllerName == 'Profiles') {
            $tabElements = $controller->getUserTabElements();
        }
        if ($controllerName == 'Directories') {
            $tabElements = $controller->getUserTabElements();
        }
        if ($controllerName == 'Guardians') {
            $tabElements = $controller->getGuardianTabElements();
        }
        $controller->set('tabElements', $tabElements);
        $alias = $model->getAlias();
        $controller->set('selectedAction', $alias);
        if ($alias == 'UserLanguages') {
            $controller->set('selectedAction', 'Languages');
        }
        if ($alias == 'UserActivities') {
            $controller->set('selectedAction', 'History');
        }

    }
}
