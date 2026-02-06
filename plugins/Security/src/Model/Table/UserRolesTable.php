<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

// Should not be in used anymore, refer to SecurityRolesTable
class UserRolesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_roles');
        parent::initialize($config);
        $this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);
        $this->belongsToMany('SecurityFunctions', [
            'className' => 'Security.SecurityFunctions',
            'through' => 'Security.SecurityRoleFunctions'
        ]);

        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->setConfig([
                    'filter' => 'security_group_id'
                ]);
        }
    }

    public function beforeAction(EventInterface $event)
    {
        $controller = $this->controller;
        $tabElements = [
            $this->alias() => [
                'url' => ['plugin' => $controller->getPlugin(), 'controller' => $controller->getName(), 'action' => $this->getAlias()],
                'text' => $this->getMessage($this->aliasField('tabTitle'))
            ],
            'SystemRoles' => [
                'url' => ['plugin' => $controller->getPlugin(), 'controller' => $controller->getName(), 'action' => 'SystemRoles'],
                'text' => $this->getMessage('SystemRoles.tabTitle')
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());

        $this->ControllerAction->field('security_group_id');
        $this->ControllerAction->field('visible');
        $this->ControllerAction->field('permissions');
        $this->ControllerAction->setFieldOrder(['security_group_id', 'name', 'visible']);
    }

    public function onUpdateFieldSecurityGroupId(EventInterface $event, array $attr, $action, Request $request)
    {
        if ($action == 'index') {
            $attr['visible'] = false;
        }
        // TODO-jeff: need to restrict to roles that have access to their groups
        // if is super admin, no restriction required
        $groupOptions = $this->SecurityGroups->find('list')
            ->find('byUser', ['userId' => $this->Auth->user('id')])
            ->toArray();

        $selectedGroup = $this->getQueryString('security_group_id', $groupOptions);
        $this->advancedSelectOptions($groupOptions, $selectedGroup);
        $request->query['security_group_id'] = $selectedGroup;

        $this->controller->set('groupOptions', $groupOptions);
        $attr['options'] = $groupOptions;

        return $attr;
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
        $toolbarElements = [
            ['name' => 'Security.UserRoles/controls', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);
        $this->ControllerAction->setFieldOrder(['visible', 'name', 'permissions']);
    }

    public function indexBeforePaginate(EventInterface $event, ServerRequest $request, Query $query, ArrayObject $options)
    {
        $selectedGroup = $this->request->getQuery('security_group_id');
        $query->where([$this->aliasField('security_group_id') => $selectedGroup]);
    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('visible', ['type' => 'hidden', 'value' => 1, 'visible' => true]);
        $this->ControllerAction->field('order', ['type' => 'hidden', 'value' => 0, 'visible' => true]);
    }
}
