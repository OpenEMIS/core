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
            'ControllerAction.Model.afterAction' => 'afterAction'
        ];
        return $events;
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $authenticationType = $event->subject()->request->query('authentication_type');
        $type = $event->subject()->request->query('type');
        $typeValue = 'Authentication';
        $model = $this->_table;
        $alias = str_replace('Config', '', $model->alias());
        if ($authenticationType && $authenticationType != $alias) {
            return $model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'Auth'.ucfirst(strtolower($authenticationType)),
                'authentication_type' => $authenticationType,
                'type_value' => 'Authentication',
                'type' => $type
            ]);
        } elseif ($model->table() != 'config_items' && !$authenticationType) {
            return $model->controller->redirect([
                'plugin' => 'Configuration',
                'controller' => 'Configurations',
                'action' => 'Authentication',
                'view',
                'type_value' => 'Authentication',
                'type' => $type
            ]);
        }
    }
}
