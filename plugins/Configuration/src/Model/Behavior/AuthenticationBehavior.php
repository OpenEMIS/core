<?php
namespace Configuration\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use OneLogin_Saml2_Constants;
use OneLogin_Saml2_Error;
use OneLogin_Saml2_Settings;

class AuthenticationBehavior extends Behavior
{
    private $alias;

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = [
            'ControllerAction.Model.beforeAction' => 'beforeAction'
        ];
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $authenticationType = $event->subject()->request->query('authentication_type');
        $model = $this->_table;
        if ($authenticationType) {
            return $model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'Auth'.ucfirst(strtolower($authenticationType)),
                'index'
            ]);
        } elseif ($model->table() != 'config_items') {
            return $model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'Authentications',
                'index'
            ]);
        }
    }
}
