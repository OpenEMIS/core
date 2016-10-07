<?php
namespace Restful\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class RestfulAccessControlBehavior extends Behavior {

    protected $_defaultConfig = [
    ];

    /**
     *  Get the list of the controller actions and the list of actions that the controller action can perform
     *
     *  @return array The list of authorised controller actions
     */
    private function getControllerActions()
    {
        return $this->config();
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = 'isAuthorized';
        return $events;
    }

    /**
     *  Check if the particular controller action is authorised for access
     *
     *  @return bool True if the user is authorised to access the api
     */
    public function isAuthorized(Event $event, $controllerAction, $action, ArrayObject $extra)
    {
        $authorizedControllerActions = $this->getControllerActions();
        if (array_key_exists($controllerAction, $authorizedControllerActions)) {
            if (in_array($action, $authorizedControllerActions[$controllerAction])) {
                return true;
            }
        }
        return false;
    }
}
