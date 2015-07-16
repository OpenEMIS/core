<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

class InstitutionBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforePaginate'] = 'indexBeforePaginate';
		return $events;
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$userId = $this->_table->Auth->user('id');

		$options['finder'] = ['byAccess' => ['userId' => $userId, 'options' => $options]];
	}

	public function findByAccess(Query $query, array $options) {
		$userId = $options['userId'];
		$findOptions = $options['options'];

		// find from security areas
		$query
		->innerJoin(['AreaAll' => 'areas'], [
			'AreaAll.lft <= Areas.lft', 
			'AreaAll.rght >= Areas.rght'
		])
		->innerJoin(['SecurityGroupArea' => 'security_group_areas'], [
			'SecurityGroupArea.area_id = AreaAll.id'
		])
		->innerJoin(['SecurityGroupUser' => 'security_group_users'], [
			'SecurityGroupUser.security_group_id = SecurityGroupArea.security_group_id',
			'SecurityGroupUser.security_user_id' => $userId
		])
		->group([$this->_table->aliasField('id')])

		// find from security institutions
		->union(
			$this->_table->find()
			->contain($findOptions['contain'])
			->select($findOptions['fields'])
			->join($findOptions['join'])
			->innerJoin(['SecurityGroupInstitutionSite' => 'security_group_institution_sites'], [
				'SecurityGroupInstitutionSite.institution_site_id = ' . $this->_table->aliasField('id')
			])
			->innerJoin(['SecurityGroupUser' => 'security_group_users'], [
				'SecurityGroupUser.security_group_id = SecurityGroupInstitutionSite.security_group_id',
				'SecurityGroupUser.security_user_id' => $userId
			])
			->group([$this->_table->aliasField('id')])
		)
		;

		return $query;
	}
}