<?php
namespace Security\Model\Table;


use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;


class SecurityRolesTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SecurityGroups', ['className' => 'Security.UserGroups']);

		$this->belongsToMany('SecurityFunctions', [
			'className' => 'Security.SecurityFunctions',
			'through' => 'Security.SecurityRoleFunctions'
		]);
	}

	public function findByInstitution(Query $query, $options) {
		pr($options);

		$ids = [-1, 0];
		if (array_key_exists('id', $options)) {
			// need to get the security_group_id of the institution
			$Institution = TableRegistry::get('Institution.Institutions');
			$Institution			
				->where($Institution->aliasField($Institution->primaryKey()))
				;
		} 

		return $query->where([$this->aliasField('security_group_id').' IN' => $ids]);
		// return $query->where([$this->aliasField('super_admin') => 0]);
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$types = ['user', 'system'];

		$tabElements = [
			'user' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'user'],
				'text' => $this->getMessage($this->aliasField('userRoles'))
			],
			'system' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'Roles', 'type' => 'system'],
				'text' => $this->getMessage($this->aliasField('systemRoles'))
			]
		];
		$this->controller->set('tabElements', $tabElements);

		$selectedAction = $this->request->query('type');
		if (empty($selectedAction) || !in_array($selectedAction, $types)) {
			$selectedAction = 'user';
			$this->request->query['type'] = $selectedAction;
		}
		$this->controller->set('selectedAction', $selectedAction);

		$this->ControllerAction->field('security_group_id');
		$this->ControllerAction->field('visible');
		$this->ControllerAction->field('permissions');

		if ($selectedAction == 'user') {
			$toolbarElements = [
				['name' => 'Security.Roles/controls', 'data' => [], 'options' => []]
			];
			$this->controller->set('toolbarElements', $toolbarElements);
			$this->ControllerAction->setFieldOrder(['visible', 'name', 'permissions']);
		} else {
			$this->ControllerAction->setFieldOrder(['security_group_id', 'name', 'visible']);
		}
	}

	public function onUpdateFieldSecurityGroupId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'index') {
			$attr['visible'] = false;
		}
		// TODO-jeff: need to restrict to roles that have access to their groups
		$groupOptions = $this->SecurityGroups->find('list')
			->find('byUser', ['userId' => $this->Auth->user('id')])
			->toArray();

		$selectedGroup = $this->queryString('security_group_id', $groupOptions);
		$this->advancedSelectOptions($groupOptions, $selectedGroup);
		$request->query['security_group_id'] = $selectedGroup;

		$this->controller->set('groupOptions', $groupOptions);
		$attr['options'] = $groupOptions;

		return $attr;
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$type = $request->query('type');
		
		$selectedGroup = $request->query('security_group_id');
		if ($type == 'system') {
			$options['conditions']['OR'] = [
				$this->aliasField('security_group_id') . ' = 0', // custom system defined roles
				$this->aliasField('security_group_id') . ' = -1' // fixed system defined roles
			];
		} else {
			$options['conditions'][$this->aliasField('security_group_id')] = $selectedGroup;
		}
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('order', ['type' => 'hidden', 'value' => 0, 'visible' => true]);
	}
}
