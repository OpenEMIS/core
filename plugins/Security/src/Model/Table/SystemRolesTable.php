<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

// Should not be in used anymore, refer to SecurityRolesTable
class SystemRolesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_roles');
        parent::initialize($config);

        $this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);

        $this->belongsToMany('SecurityFunctions', [
            'className' => 'Security.SecurityFunctions',
            'through' => 'Security.SecurityRoleFunctions'
        ]);
    }

    public function beforeAction(Event $event)
    {
        $controller = $this->controller;
        $tabElements = [
            'UserRoles' => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'UserRoles'],
                'text' => $this->getMessage('UserRoles.tabTitle')
            ],
            $this->alias() => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
                'text' => $this->getMessage($this->aliasField('tabTitle'))
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());

        $this->ControllerAction->field('security_group_id', ['visible' => false]);
        $this->ControllerAction->field('visible');
        $this->ControllerAction->field('permissions');
        $this->ControllerAction->setFieldOrder(['security_group_id', 'name', 'visible']);
    }

    public function onGetVisible(Event $event, Entity $entity)
    {
        return $entity->visible == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onGetPermissions(Event $event, Entity $entity)
    {
        $subject = $event->subject(); // ControllerActionHelper
        return '';
    }

    public function indexBeforeAction(Event $event)
    {
        $this->ControllerAction->setFieldOrder(['visible', 'name', 'permissions']);
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        // $options['conditions'][$this->aliasField('security_group_id')] = [0, -1];
        $query->where([$this->aliasField('security_group_id') . ' IN ' => [0, -1]]);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('visible', ['type' => 'hidden', 'value' => 1, 'visible' => true]);
        $this->ControllerAction->field('order', ['type' => 'hidden', 'value' => 0, 'visible' => true]);
    }
}
