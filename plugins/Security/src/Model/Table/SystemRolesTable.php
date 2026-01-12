<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

// Should not be in used anymore, refer to SecurityRolesTable
class SystemRolesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_roles');
        parent::initialize($config);

        $this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);

        $this->belongsTo('UserGroupsList', ['className' => 'Security.UserGroupsList']);


        $this->belongsToMany('SecurityFunctions', [
            'className' => 'Security.SecurityFunctions',
            'through' => 'Security.SecurityRoleFunctions'
        ]);
    }

    public function beforeAction(EventInterface $event)
    {
        $controller = $this->controller;
        $tabElements = [
            'UserRoles' => [
                'url' => ['plugin' => $controller->getPlugin(), 'controller' => $controller->getName(), 'action' => 'UserRoles'],
                'text' => $this->getMessage('UserRoles.tabTitle')
            ],
            $this->getAlias() => [
                'url' => ['plugin' => $controller->getPlugin(), 'controller' => $controller->getName(), 'action' => $this->getAlias()],
                'text' => $this->getMessage($this->aliasField('tabTitle'))
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());

        $this->ControllerAction->field('security_group_id', ['visible' => false]);
        $this->ControllerAction->field('visible');
        $this->ControllerAction->field('permissions');
        $this->ControllerAction->setFieldOrder(['security_group_id', 'name', 'visible']);
    }

    public function onGetVisible(EventInterface $event, Entity $entity)
    {
        return $entity->visible == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onGetPermissions(EventInterface $event, Entity $entity)
    {
        $subject = $event->getSubject(); // ControllerActionHelper
        return '';
    }

    public function indexBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->setFieldOrder(['visible', 'name', 'permissions']);
    }

    public function indexBeforePaginate(EventInterface $event, ServerRequest $request, Query $query, ArrayObject $options)
    {
        // $options['conditions'][$this->aliasField('security_group_id')] = [0, -1];
        $query->where([$this->aliasField('security_group_id') . ' IN ' => [0, -1]]);
    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('visible', ['type' => 'hidden', 'value' => 1, 'visible' => true]);
        $this->ControllerAction->field('order', ['type' => 'hidden', 'value' => 0, 'visible' => true]);
    }
}
