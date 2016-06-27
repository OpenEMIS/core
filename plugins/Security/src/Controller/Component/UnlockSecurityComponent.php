<?php
namespace Security\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;

class UnlockSecurityComponent extends Component {
    private $controller;

    public function initialize(array $config) 
    {
        $controller = $this->_registry->getController();
        $this->controller = $controller;
    }
    public function startup(Event $event) 
    {
        $action = $this->request->params['action'];
        // Fix for reorder
        $pass = $this->request->pass;
        if (isset($pass[0])) {
            if ($pass[0] == 'reorder') {
                $this->controller->Security->config('unlockedActions', [
                    $action
                ]);
            }
        }
    }
}