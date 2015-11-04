<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

class InstitutionBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();

		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		$events['ControllerAction.Model.index.beforePaginate'] = ['callable' => 'indexBeforePaginate', 'priority' => 100];
		return $events;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$userId = $this->_table->Auth->user('id');
			$query->find('byAccess', ['userId' => $userId, 'options' => $options['query']]);
		}
	}

	public function findByAccess(Query $query, array $options) {
		$userId = $options['userId'];
		$findOptions = $options['options'];

		$queryClone = clone $query;
		// find from security areas
		$institutions = $queryClone
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
			->select(['id' => $this->_table->aliasField('id')], true);
		
		// find from security institutions
		$securityInstitution = $this->_table->find()
			->contain($findOptions['contain'])
			->select(['id' => $this->_table->aliasField('id')])
			->join($findOptions['join'])
			->innerJoin(['SecurityGroupInstitutionSite' => 'security_group_institution_sites'], [
				'SecurityGroupInstitutionSite.institution_site_id = ' . $this->_table->aliasField('id')
			])
			->innerJoin(['SecurityGroupUser' => 'security_group_users'], [
				'SecurityGroupUser.security_group_id = SecurityGroupInstitutionSite.security_group_id',
				'SecurityGroupUser.security_user_id' => $userId
			])
			->group([$this->_table->aliasField('id')]);

		$query->where([
			'OR' => [
				[$this->_table->aliasField('id').' IN ' => $institutions],
				[$this->_table->aliasField('id').' IN ' => $securityInstitution]
			]
		]);
		return $query;
	}
}