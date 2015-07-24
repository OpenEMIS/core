<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class SystemGroupsTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('security_groups');
		parent::initialize($config);

		$this->hasOne('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'security_group_id']);
		$this->belongsToMany('Users', [
			'className' => 'User.Users',
			'joinTable' => 'security_group_users',
			'foreignKey' => 'security_group_id',
			'targetForeignKey' => 'security_user_id',
			'through' => 'Security.SecurityGroupUsers',
			'dependent' => true
		]);
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$tabElements = [
			'UserGroups' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'UserGroups'],
				'text' => $this->getMessage('UserGroups.tabTitle')
			],
			$this->alias() => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
				'text' => $this->getMessage($this->aliasField('tabTitle'))
			]
		];
		
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('no_of_users', ['visible' => ['index' => true]]);
		$this->ControllerAction->setFieldOrder(['name', 'no_of_users']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$queryParams = $request->query;

		$query->find('inInstitutions');

		if (!array_key_exists('sort', $queryParams) && !array_key_exists('direction', $queryParams)) {
			$query->order([$this->aliasField('name') => 'asc']);
		}
	}

	public function findInInstitutions(Query $query, array $options) {
		$query->join([
			[
				'table' => 'institution_sites',
				'alias' => 'Institutions',
				'type' => 'INNER',
				'conditions' => ['Institutions.security_group_id = SystemGroups.id']
			]
		]);
		return $query;
	}

	public function onGetNoOfUsers(Event $event, Entity $entity) {
		$id = $entity->id;

		$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$count = $GroupUsers->findAllBySecurityGroupId($id)->count();

		return $count;
	}
}
