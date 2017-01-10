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
    private function getScopes()
    {
        return $this->config();
    }

    /**
     *  Add scope and authorised actions
     *
     *  @param  array The scope and the actions
     *  @return array The list of authorised controller actions
     */
    public function addScope($scope)
    {
        $scopes = $this->getScopes();
        $scopes = $scopes + $scope;
        $this->_config = $scopes;
    }

    /**
     *  Remove scope and it's authorised actions
     *
     *  @param  string The scope
     *  @return array The list of authorised controller actions
     */
    public function removeScope($scope)
    {
        $scopes = $this->getScopes();
        if (isset($scope)) {
            unset($scopes[$scope]);
        }
        $this->_config = $scopes;
    }

    /**
     *  Get the list of the controller actions and the list of actions that the controller action can perform
     *
     *  @return array The list of authorised controller actions
     */

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
    public function isAuthorized(Event $event, $scope, $action, ArrayObject $extra)
    {
        $authorisedScopes = $this->getScopes();
        if (array_key_exists($scope, $authorisedScopes)) {
            if (in_array($action, $authorisedScopes[$scope])) {
                return true;
            }
        }
        return false;
    }
}
