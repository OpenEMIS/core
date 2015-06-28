<?php
namespace Security\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class UserGroupsTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('security_groups');
		parent::initialize($config);

		$this->belongsToMany('Users', [
			'className' => 'User.Users',
			'through' => 'Security.SecurityGroupUsers'
		]);
	}

	public function beforeAction(Event $event) {
		$controller = $this->controller;
		$tabElements = [
			$this->alias() => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
				'text' => $this->getMessage($this->aliasField('tabTitle'))
			],
			'SystemGroups' => [
				'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'SystemGroups'],
				'text' => $this->getMessage('SystemGroups.tabTitle')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('no_of_users', ['visible' => ['index' => true]]);
		$this->ControllerAction->setFieldOrder(['name', 'no_of_users']);
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		$query = $request->query;
		if (!array_key_exists('sort', $query) && !array_key_exists('direction', $query)) {
			$options['order'][$this->aliasField('name')] = 'asc';
		}
		$options['finder'] = ['notInInstitutions' => []];
		return $options;
	}

	public function findNotInInstitutions(Query $query, array $options) {
		$query->where([
			'NOT EXISTS (SELECT `id` FROM `institution_sites` WHERE `security_group_id` = `UserGroups`.`id`)'
		]);
		return $query;
	}

	public function findByUser(Query $query, array $options) {
		$userId = $options['userId'];
		$alias = $this->alias();

		$query
		->join([
			[
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUsers',
				'type' => 'LEFT',
				'conditions' => ["SecurityGroupUsers.security_group_id = $alias.id"]
			]
		])
		->where([
			'OR' => [
				"$alias.created_user_id" => $userId,
				'SecurityGroupUsers.security_user_id' => $userId
			]
		])
		->group([$this->aliasField('id')]);
		return $query;
	}

	public function onGetNoOfUsers(Event $event, Entity $entity) {
		$id = $entity->id;

		$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
		$count = $GroupUsers->findAllBySecurityGroupId($id)->count();

		return $count;
	}
}
